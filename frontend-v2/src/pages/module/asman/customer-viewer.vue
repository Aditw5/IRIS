<template>
  <div class="food-delivery-dashboard">
    <!--Left-->
    <div class="left">
      <div class="left-header">
        <div class="header-image">
          <img src="/@src/assets/illustrations/dashboards/lifestyle/customer.png" alt=""
            style="max-width:75%; margin-left: 2rem; margin-top: 5rem;" />
        </div>
        <div class="header-meta">
          <h3>
            History Order
          </h3>
          <p>Selamat Datang , {{ userLogin.pegawai.namaLengkap }}</p>
          <div class="button-wrap">
            <VButton color="warning" elevated rounded @click="$router.go(-1)">
              Kembali Ke Dashboard
            </VButton>
          </div>
        </div>
      </div>
      <div class="left-body">
        <div class="restaurants">
          <VCard class="text-center pt-0 pb-0 mb-3">
            <VRadio v-model="orderAlat" value='0' label="Order" name="outlined_radio" color="success" />
            <VRadio v-model="orderAlat" value='1' label="Detail Alat" name="outlined_radio" color="info" />
          </VCard>
          <div class="list-view list-view-v3" v-if="orderAlat == 0">
            <div class="search-menu-rad mb-2">
              <div class="search-location-rad" style="width: 100%">
                <i class="iconify" data-icon="feather:search"></i>
                <input type="text" placeholder="No Pendaftaran" v-model="item.searchHistoryKelompok"
                  v-on:keyup.enter="fetchHistoryOrderKelompok()" />
              </div>
              <VButton raised class="search-button-rad" @click="fetchHistoryOrderKelompok()" :loading="isLoading">
                Cari Data
              </VButton>
            </div>
            <VPlaceholderPage :class="[dataHistoryOrder.length !== 0 && 'is-hidden']" title="Tidak Ada Alat Hari Ini."
              subtitle="Silakan Pilih Tanggal" larger>
              <template #image>
                <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.png" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
              </template>
            </VPlaceholderPage>
            <div class="list-view-inner mt-2" style="max-height:1000px;overflow: auto;">
              <div name="list-complete" tag="div">
                <div v-for="(item, iddetail) in dataHistoryOrderKelompok" :key="iddetail">
                  <div class="list-view-item ">
                    <div class="list-view-item-inner">
                      <VAvatar size="small" picture="/images/avatars/svg/propinsi.svg" color="primary" bordered />
                      <div class="meta-left">
                        <h3>
                          {{ item.namaperusahaan }} <span><b>({{ item.nopendaftaran ?? '-' }}) </b></span>
                        </h3>
                        <span>
                          <i aria-hidden="true" class="iconify" data-icon="feather:home"></i>
                          <span>{{ item.jenisorder }}</span>
                          <i aria-hidden="true" class="fas fa-circle icon-separator"></i>
                          <i aria-hidden="true" class="iconify" data-icon="feather:calendar"></i>
                          <span>{{ item.tglregistrasi }}</span>
                        </span>
                        <br>
                        <VTag v-if="item.jenisorder == 'repair'" color="warning" rounded>Repair</VTag>
                        <VTag v-if="item.jenisorder == 'kalibrasi'" color="info" rounded>Kalibrasi</VTag>
                        <div>
                          <VTag v-if="item.verifregiscustomer == null" label="Alat Menunggu Diverifikasi"
                            :color="'warning'" />
                          <VTag v-if="item.verifregiscustomer" label="Alat Sudah Diverifikasi" :color="'success'" />
                        </div>
                        <div v-if="item.jumlahdetail && item.jumlahselesai != null"
                          style="font-weight: 600; font-size: 0.98em; color: #2abb4a; margin-top: 5px;">
                          Selesai {{ item.jumlahselesai }}/{{
                            item.jumlahdetail }}
                          <div style="background:#e9ecef; border-radius:8px; width:85%; height:7px; margin: 6px 0 0 0;">
                            <div :style="{
                              width: ((item.jumlahselesai / item.jumlahdetail) * 100) + '%',
                              background: '#53dd6c',
                              height: '100%',
                              borderRadius: '8px',
                              transition: 'width 0.5s'
                            }"></div>
                          </div>
                        </div>
                      </div>
                      <div class="meta-right flex justify-center items-center">
                        <div class="buttons">
                          <VIconButton v-tooltip.bottom.left="'Cetak AMS'" icon="feather:printer"
                            @click="cetakAmsKelompok(item)" color="warning" raised circle class="mr-2">
                          </VIconButton>
                          <VIconButton v-tooltip.bottom.left="'Download List Tools'" icon="feather:download"
                            @click="cetakAmsKelompok(item)" color="info" raised circle class="mr-2">
                          </VIconButton>
                          <VIconButton v-tooltip.bottom.left="'Detail'" label="Bottom Left" color="primary" circle
                            icon="pi pi-book" @click="getDetailVerify(item)" style="margin-right: 15px;" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <VFlexPagination v-model:current-page="currentPageHistoryKelompok.page"
              :item-per-page="currentPageHistoryKelompok.limit" :total-items="totalDataHistoryKelompok"
              :max-links-displayed="5">
              <template #before-pagination>
              </template>
              <template #before-navigation>
                <VFlex class="mr-4 mt-1" column-gap="1rem">
                  <VField>

                  </VField>
                  <VField>
                    <VControl>
                      <div class="select is-rounded">
                        <select v-model="currentPageHistoryKelompok.limit">
                          <option :value="1">1 results per page</option>
                          <option :value="5">6 results per page</option>
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

          <div class="list-view list-view-v3" v-if="orderAlat == 1">
            <div class="search-menu-rad mb-2">
              <div class="search-location-rad" style="width: 100%">
                <i class="iconify" data-icon="feather:search"></i>
                <input type="text" placeholder="Cari Nama Alat, Merk/Tipe, Serial Number, No order Alat, No Pendaftaran"
                  v-model="item.searchHistory" v-on:keyup.enter="fetchHistoryOrder()" />
              </div>
              <VButton raised class="search-button-rad" @click="fetchHistoryOrder()" :loading="isLoading">
                Cari Data
              </VButton>
            </div>
            <VPlaceholderPage :class="[dataHistoryOrder.length !== 0 && 'is-hidden']" title="Tidak Ada Alat Hari Ini."
              subtitle="Silakan Pilih Tanggal" larger>
              <template #image>
                <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.png" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
              </template>
            </VPlaceholderPage>
            <div class="list-view-inner mt-2" style="max-height:1000px;overflow: auto;">
              <div name="list-complete" tag="div">
                <div v-for="(item, rowIndex) in dataHistoryOrder" :key="rowIndex">
                  <div v-if="rowGroupMetadataHistory[item.jenisorder].index === rowIndex">
                    <span style="font-weight: bold; font-size: 16px; font-family: var(--font-alt);">
                      {{ item.jenisorder }}
                    </span>
                    <Badge :value="rowGroupMetadataHistory[item.jenisorder].size"
                      v-if="rowGroupMetadataHistory[item.jenisorder].size > 0" class="ml-2 mt-2-min" />
                  </div>

                  <div class="list-view-item">
                    <div class="list-view-item-inner">
                      <VAvatar size="small" picture="/images/avatars/svg/propinsi.svg" color="primary" bordered />
                      <div class="meta-left">
                        <h3>{{ item.namaproduk }}</h3>

                        <span class="meta-info-line">
                          <i class="iconify" data-icon="feather:home"></i>
                          <span class="meta-block">{{ item.namaperusahaan }}</span>

                          <i class="fas fa-circle icon-separator"></i>

                          <i class="iconify" data-icon="feather:calendar"></i>
                          <span class="meta-block">{{ item.tglregistrasi || '-' }}</span>

                          <i class="fas fa-circle icon-separator"></i>

                          <i class="iconify" data-icon="feather:check-circle"></i>
                          <span class="meta-block">{{ item.nopendaftaran || '-' }}</span>

                          <i class="fas fa-circle icon-separator"></i>

                          <i class="iconify" data-icon="feather:check-circle"></i>
                          <span class="meta-block">{{ item.noorderalat || '-' }}</span>
                        </span>

                        <div>
                          <VTag v-if="item.verifregiscustomer == null" label="Alat Menunggu Diverifikasi"
                            :color="'warning'" class="ml-2" />
                          <VTag v-if="item.verifregiscustomer" label="Alat Sudah Diverifikasi" :color="'success'"
                            class="ml-2" />
                        </div>

                        <div><span style="font-weight: bold;">Merk/Tipe : {{ item.namamerk ?? '-' }} - {{ item.namatipe
                        }}</span></div>
                        <div><span style="font-weight: bold;">SN : {{ item.namaserialnumber ?? '-' }}</span></div>
                        <div class="progress-tracker-wrapper" v-if="item.jenisorder == 'kalibrasi'">
                          <div class="step" :class="{ active: true }" v-tooltip.top="'Menunggu Verifikasi'">
                            <i class="fas fa-clock"></i>
                          </div>
                          <div class="connector">
                            <ProgressBar
                              v-if="!item.verifregiscustomer && !item.tglverifasman && !item.tglsetujumanagerlembarkerja"
                              mode="indeterminate" style="height: 4px" />
                            <div v-else-if="item.verifregiscustomer" class="line-done"></div>
                            <div v-else class="line-default"></div>
                          </div>
                          <div class="step" :class="{ active: item.verifregiscustomer }"
                            v-tooltip.top="`Terverifikasi ${item.tanggalverifregiscustomer ?? ''}`">
                            <i class="fas fa-check-circle"></i>
                          </div>
                          <div class="connector">
                            <ProgressBar
                              v-if="item.verifregiscustomer && !item.tglverifasman && !item.tglsetujumanagerlembarkerja"
                              mode="indeterminate" style="height: 4px" />
                            <div v-else-if="item.tglverifasman" class="line-done"></div>
                            <div v-else class="line-default"></div>
                          </div>
                          <div class="step" :class="{ active: item.tglverifasman }" v-tooltip.top="'Sedang Dikerjakan'">
                            <i class="fas fa-tools"></i>
                          </div>
                          <div class="connector">
                            <ProgressBar v-if="item.tglverifasman && !item.tglsetujumanagerlembarkerja"
                              mode="indeterminate" style="height: 4px" />
                            <div v-else-if="item.tglsetujumanagerlembarkerja" class="line-done"></div>
                            <div v-else class="line-default"></div>
                          </div>
                          <div class="step" :class="{ active: item.tglsetujumanagerlembarkerja }"
                            v-tooltip.top="`Kalibrasi Selesai ${item.tglsetujumanagerlembarkerja ?? ''}`">
                            <i class="fas fa-home"></i>
                          </div>
                        </div>

                        <div class="progress-tracker-wrapper" v-if="item.jenisorder == 'repair'">
                          <div class="step" :class="{ active: true }" v-tooltip.top="'Menunggu Verifikasi'">
                            <i class="fas fa-clock"></i>
                          </div>
                          <div class="connector">
                            <ProgressBar
                              v-if="!item.verifregiscustomer && !item.tglverifasman && !item.tglsetujumanagerlaporanrepair"
                              mode="indeterminate" style="height: 4px" />
                            <div v-else-if="item.verifregiscustomer" class="line-done"></div>
                            <div v-else class="line-default"></div>
                          </div>
                          <div class="step" :class="{ active: item.verifregiscustomer }"
                            v-tooltip.top="`Terverifikasi ${item.tanggalverifregiscustomer ?? ''}`">
                            <i class="fas fa-check-circle"></i>
                          </div>
                          <div class="connector">
                            <ProgressBar
                              v-if="item.verifregiscustomer && !item.tglverifasman && !item.tglsetujumanagerlaporanrepair"
                              mode="indeterminate" style="height: 4px" />
                            <div v-else-if="item.tglverifasman" class="line-done"></div>
                            <div v-else class="line-default"></div>
                          </div>
                          <div class="step" :class="{ active: item.tglverifasman }" v-tooltip.top="'Sedang Dikerjakan'">
                            <i class="fas fa-tools"></i>
                          </div>
                          <div class="connector">
                            <ProgressBar v-if="item.tglverifasman && !item.tglsetujumanagerlaporanrepair"
                              mode="indeterminate" style="height: 4px" />
                            <div v-else-if="item.tglsetujumanagerlaporanrepair" class="line-done"></div>
                            <div v-else class="line-default"></div>
                          </div>
                          <div class="step" :class="{ active: item.tglsetujumanagerlaporanrepair }"
                            v-tooltip.top="`Repair Selesai ${item.tglsetujumanagerlaporanrepair ?? ''}`">
                            <i class="fas fa-home"></i>
                          </div>
                        </div>
                      </div>
                      <div class="meta-right flex justify-center items-center">
                        <div class="buttons">
                          <VIconButton v-tooltip.bottom.left="'Cetak AMS'" icon="feather:printer"
                            @click="cetakAms(item)" color="warning" raised circle class="mr-2" />
                          <VIconButton v-tooltip.bottom.left="'Download List Tools'" icon="feather:download"
                            @click="downloadTools(item)" color="info" raised circle class="mr-2" />
                          <VIconButton v-tooltip.bottom.left="'Aktivitas'" icon="feather:activity"
                            @click="getDetailAlat(item)" color="info" raised circle class="mr-2">
                          </VIconButton>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <VFlexPagination v-model:current-page="currentPageHistory.page" :item-per-page="currentPageHistory.limit"
              :total-items="totalDataHistory" :max-links-displayed="5">
              <template #before-pagination>
              </template>
              <template #before-navigation>
                <VFlex class="mr-4 mt-1" column-gap="1rem">
                  <VField>

                  </VField>
                  <VField>
                    <VControl>
                      <div class="select is-rounded">
                        <select v-model="currentPageHistory.limit">
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
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import type { TinySliderInstance } from 'tiny-slider/src/tiny-slider'
import { tns } from 'tiny-slider/src/tiny-slider'
import { ref, onMounted, onUnmounted, watch, reactive, computed } from 'vue'
import { useUserSession } from '/@src/stores/userSession'
import FoodWidget from '/@src/assets/illustrations/dashboards/food/widget.svg'
import { useRoute, useRouter } from 'vue-router'
import * as foodDelivery from '/@src/data/dashboards/food-delivery'
import { followersStats } from '/@src/data/widgets/ui/followers'
import { iconList } from '/@src/data/widgets/ui/menuList'
import { onceImageErrored } from '/@src/utils/via-placeholder'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import AutoComplete from 'primevue/autocomplete'
import * as H from '/@src/utils/appHelper'
import Dialog from 'primevue/dialog';
import FileUpload from 'primevue/fileupload';
import ApexChart from 'vue3-apexcharts'
import { useThemeColors } from '/@src/composable/useThemeColors'
import { useToaster } from '/@src/composable/toaster'
import ProgressBar from 'primevue/progressbar';

useHead({
  title: 'Viewer - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const activeSection = ref('cart')
const route = useRoute()
const router = useRouter()
const userLogin = useUserSession().getUser()
let ID_UNIT = useRoute().query.idunit as string
let slider: TinySliderInstance
const sliderElement = ref<HTMLElement>()
const nextButtonElement = ref<HTMLElement>()
const prevButtonElement = ref<HTMLElement>()
const item: any = ref({})
let isLoading: any = ref(false)
const currentPage = ref({
  page: 1,
  limit: 6,
  rows: 50,
})
const currentPageHistory = ref({
  page: 1,
  limit: 5,
  rows: 50,
})

const currentPageHistoryKelompok = ref({
  page: 1,
  limit: 5,
  rows: 50,
})
const themeColors = useThemeColors()
const statusCustomer: any = ref()
const dataCustomer: any = ref()
const dataOrder: any = ref(0)
const dataHistoryOrderKelompok: any = ref(0)
const dataHistoryOrder: any = ref(0)
const countDiverifikasi: any = ref(0)
const totalDataOrder: any = ref(0)
const countBelumDiverifikasi: any = ref(0)
const dataKeranjang = ref<any[]>([])
let totalData: any = ref(0)
let totalDataHistory: any = ref(0)
let totalDataHistoryKelompok: any = ref(0)
const selectAll = ref(false)
const rowGroupMetadataHistory = ref<Record<string, { index: number; size: number }>>({})
const orderAlat: any = ref(0)

const riwayatAlat = (e: any) => {
  router.push({
    name: 'module-customer-detail-alat',
    query: {
      id_alat: e.id,
    },
  })
}

const fetchHistoryOrder = async () => {
  isLoading.value = true
  let searchHistory = ''
  if (item.value.searchHistory) searchHistory = '&search=' + item.value.searchHistory
  let limit: any = currentPageHistory.value.limit
  let page = currentPageHistory.value.page
  let offset = (page - 1) * limit


  await useApi().get(`asman/get-history-order-customer?page=${page}&offset=${offset}&limit=${limit}&rows=${currentPageHistory.value.rows}&mtrauser=${ID_UNIT}&` + searchHistory).then((response) => {
    dataHistoryOrder.value = response.data.data
    totalDataOrder.value = response.totalData
    countDiverifikasi.value = response.countVerif
    countBelumDiverifikasi.value = response.countBelumVerif
    totalDataHistory.value = response.data.total
    response.data.data.forEach((element: any, i: any) => {
      element.no = i + 1
      let ini = element.namaproduk.split(' ')
      let init = element.namaproduk.substr(0, 2)
      if (ini.length > 1) {
        init = init + ini[1].substr(0, 1)
      }
      element.initials = init
    });
    isLoading.value = false
  }).catch((err) => {
  })
  isLoading.value = false
  updateRowGroupMetaDataHistory()
}

const fetchHistoryOrderKelompok = async () => {
  isLoading.value = true
  let searchHistoryKelompok = ''
  if (item.value.searchHistoryKelompok) searchHistoryKelompok = '&search=' + item.value.searchHistoryKelompok
  let limit: any = currentPageHistoryKelompok.value.limit
  let page = currentPageHistoryKelompok.value.page
  let offset = (page - 1) * limit
  const user = dataCustomer.value

  await useApi().get(`asman/get-history-order-customer-kelompok?page=${page}&offset=${offset}&limit=${limit}&mtrauser=${ID_UNIT}&rows=${currentPageHistoryKelompok.value.rows}&` + searchHistoryKelompok).then((response) => {
    dataHistoryOrderKelompok.value = response.data
    totalDataHistoryKelompok.value = response.total
    response.data.forEach((element: any, i: any) => {
      element.no = i + 1
      let ini = element.namaperusahaan.split(' ')
      let init = element.namaperusahaan.substr(0, 2)
      if (ini.length > 1) {
        init = init + ini[1].substr(0, 1)
      }
      element.initials = init
    });
    isLoading.value = false
  }).catch((err) => {
  })
  isLoading.value = false
}

const updateRowGroupMetaDataHistory = () => {
  rowGroupMetadataHistory.value = {};
  if (dataHistoryOrder.value) {
    for (let i = 0; i < dataHistoryOrder.value.length; i++) {
      let rowData = dataHistoryOrder.value[i];
      let jenisorder = rowData.jenisorder;

      if (i == 0) {
        rowGroupMetadataHistory.value[jenisorder] = { index: 0, size: 1 };
      }
      else {
        let previousRowData = dataHistoryOrder.value[i - 1];
        let previousRowGroup = previousRowData.jenisorder;
        if (jenisorder === previousRowGroup) {
          rowGroupMetadataHistory.value[jenisorder].size++;
        }
        else {
          rowGroupMetadataHistory.value[jenisorder] = { index: i, size: 1 };
        }
      }
    }
  }
}

const getDetailVerify = (e: any) => {
  router.push({
    name: 'module-customer-detail-registrasi',
    query: {
      norec_pd: e.iddetail,
    },
  })
}

const getDetailAlat = (e: any) => {
  console.log(e)
  router.push({
    name: 'module-customer-detail-alat',
    query: {
      id_alat: e.idalat,
    },
  })
}


watch(
  () => currentPageHistory.value.page,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchHistoryOrder()
    }
  }
)
watch(
  () => currentPageHistory.value.limit,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchHistoryOrder()
    }
  }
)

watch(
  () => currentPageHistoryKelompok.value.page,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchHistoryOrderKelompok()
    }
  }
)
watch(
  () => currentPageHistoryKelompok.value.limit,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchHistoryOrderKelompok()
    }
  }
)


watch(orderAlat, (newVal) => {
  if (newVal == '0') {
    fetchHistoryOrderKelompok()
  } else if (newVal == '1') {
    fetchHistoryOrder()
  }
})

watch(selectAll, (newVal) => {
  dataKeranjang.value.forEach((item) => {
    item.checked = newVal
  })
})

watch(dataKeranjang, (newItems) => {
  const allChecked = newItems.length > 0 && newItems.every((item) => item.checked)
  selectAll.value = allChecked
}, { deep: true })


const onIndexChanged = (info: any) => {
  // direct access to info object
  const indexPrev = info.indexCached
  const indexCurrent = info.index

  // update style based on index
  info.slideItems[indexPrev].classList.remove('active')
  info.slideItems[indexCurrent].classList.add('active')
}
onMounted(() => {
  if (sliderElement.value && nextButtonElement.value && prevButtonElement.value) {
    slider = tns({
      container: sliderElement.value,
      controls: true,
      nav: false,
      mouseDrag: true,
      nextButton: nextButtonElement.value,
      prevButton: prevButtonElement.value,
      fixedWidth: 98,
      swipeAngle: false,
      items: 1,
      center: false,
      loop: true,
    })

    slider.events.on('indexChanged', onIndexChanged)
  }
})

const goTo = (index: number) => {
  if (slider) {
    slider.goTo(index)
  }
}

onUnmounted(() => {
  if (slider) {
    slider.events.off('indexChanged', onIndexChanged)
    slider.destroy()
  }
})

const cetakAmsKelompok = (e: any) => {
  if (!e.iddetail) {
    H.alert('warning', 'Data tidak valid');
    return;
  }

  H.printBlade(`registrasi/cetak-ams?norecregis=${e.iddetail}`);
};

const cetakAms = (e: any) => {
  if (!e.norec) {
    H.alert('warning', 'Data tidak valid');
    return;
  }

  H.printBlade(`registrasi/cetak-ams?norecregis=${e.norec}`);
};

const downloadTools = (item: any) => {
  const norec = item.norec;
  const token = useUserSession().token;
  // const url = `http://localhost:8000/service/registrasi/download-tools-customer?norecregis=${norec}&token=${token}`;
  const url = `/service/registrasi/download-tools-customer?norecregis=${norec}&token=${token}`;

  window.open(url, '_blank');
};

const initDashboard = async () => {
  await fetchHistoryOrder()
  await fetchHistoryOrderKelompok()
}

onMounted(() => {
  initDashboard()
})

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/customer.scss';

.meta-left {
  min-width: 0;
  flex: 1;
}

.meta-info-line {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.meta-block {
  min-width: 80px;
  display: inline-block;
  white-space: nowrap;
}

.icon-separator {
  font-size: 6px;
  color: #aaa;
}

.progress-tracker-wrapper {
  display: flex;
  align-items: center;
  margin-top: 10px;
  gap: 6px;
}

.step {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #d1d5db;
  color: white;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.step.active {
  background-color: #10b981;
}

.connector {
  flex-grow: 1;
  min-width: 40px;
}

.line-done {
  height: 4px;
  background-color: #10b981;
  border-radius: 2px;
}

.line-default {
  height: 4px;
  background-color: #d1d5db;
  border-radius: 2px;
}

.list-view-item-inner {
  min-height: 160px;
  display: flex;
}


.cart-item+.cart-item {
  border-top: 1px solid #f0f0f0;
  padding-top: 8px;
  margin-top: 8px;
}

.p-dialog.loading-dialog {
  border: none !important;
  border-radius: 0 !important;
  max-width: 100vw !important;
  max-height: 120vh !important;
}

.p-dialog.loading-dialog .p-dialog-content {
  background: transparent !important;
  box-shadow: none !important;
  padding: 0 !important;
}

.p-dialog.loading-dialog,
.p-dialog.loading-dialog.p-dialog-enter-active,
.p-dialog.loading-dialog.p-dialog-leave-active {
  animation: none !important;
  transition: none !important;
}

body>.p-dialog-mask:has(.p-dialog.loading-dialog) {
  background-color: rgba(255, 255, 255, 0.5) !important;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  animation: none !important;
}

.search-menu-rad {
  height: 56px !important;
  white-space: nowrap;
  display: flex;
  flex-shrink: 0;
  align-items: center;
  background-color: white;
  border-radius: 8px;
  width: 100%;
  padding-left: 0.75rem;

  >div:not(:last-of-type) {
    border-right: 1px solid var(--search-border-color);
  }

  .search-bar {
    height: 55px;
    width: 100%;
    position: relative;
    display: flex;
    align-items: center;
    padding-right: 1.5rem;

    .field {
      width: 100%;
    }

    .multiselect-tags {
      padding-left: 2.5rem;
    }
  }

  .search-location-rad,
  .search-job,
  .search-salary {
    display: flex;
    align-items: center;
    width: 50%;
    font-size: 14px;
    font-weight: 500;
    padding: 0 25px;
    height: 100%;
    font-family: var(--font);

    input {
      width: 100%;
      height: 90%;
      display: block;
      font-family: var(--font);
      color: var(--input-color);
      background-color: transparent;
      border: none;
    }

    svg {
      margin-right: 0.5rem;
      width: 18px;
      color: var(--primary);
      flex-shrink: 0;
    }
  }

  .search-button-rad {
    background-color: var(--primary);
    min-width: 100px;
    height: 56px !important;
    border: none;
    font-weight: 500;
    font-family: var(--font);
    padding: 0 1rem;
    border-radius: 0 0.75rem 0.75rem 0;
    color: white;
    cursor: pointer;
    margin-left: auto;
  }
}

.search-widget {
  flex: 1;
  display: inline-block;
  width: 100%;
  padding: 10px;
  background-color: var(--white);
  border-radius: 16px;
  border: 1px solid var(--fade-grey-dark-3);
  transition: all 0.3s;
}

.food-delivery-dashboard {
  display: flex;

  &.is-navbar {
    margin-top: 30px;

    >.right {
      .sticky-panel {
        height: calc(100% - 120px) !important;

        &.is-stretched {
          height: calc(100% - 120px);
          top: 100px;
        }
      }
    }
  }

  .left {
    width: 100%;

    .left-header {
      display: flex;
      align-items: center;
      padding: 10px;
      border-radius: 16px;
      background: var(--primary-light-30);
      font-family: var(--font);

      .header-image {
        position: relative;
        height: 150px;
        width: 280px;

        img {
          position: absolute;
          top: -40px;
          left: -30px;
          display: block;
        }
      }

      .header-meta {
        margin-left: 0;
        margin-bottom: 20px;

        h3 {
          font-family: var(--font-alt);
          font-weight: 700;
          font-size: 1.6rem;
        }

        p {
          font-weight: 400;
          color: var(--primary-dark-14);
          margin-bottom: 8px;
        }

        .action-link {
          span {
            font-size: 0.8rem;
            text-transform: uppercase;
            margin-right: 6px;
          }

          i {
            font-size: 12px;
          }
        }
      }
    }

    .left-body {
      .restaurants {
        .restaurants-toolbar {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin: 20px 0;
          font-family: var(--font);

          .left {
            h3 {
              font-family: var(--font-alt);
              color: var(--dark-text);
              font-size: 1.3rem;
              font-weight: 600;
            }
          }

          .right {
            display: flex;
            justify-content: flex-end;
          }
        }
      }

      .food-pills {
        position: relative;

        .food-pills-inner {
          .food-pill {
            text-align: center;
            width: 80px;
            max-width: 80px;
            height: 170px;
            background: var(--white);
            border: 1px solid var(--fade-grey-dark-3);
            border-radius: 500px;
            padding: 10px;
            margin: 0 10px;
            cursor: pointer;
            transition: all 0.3s; // transition-all test

            &.is-active {
              background: var(--primary);
              border-color: var(--primary);

              .food-pill-icon {
                border-color: var(--primary);
              }

              >span {
                color: var(--smoke-white);
              }
            }

            .food-pill-icon {
              display: flex;
              justify-content: center;
              align-items: center;
              width: 60px;
              height: 80px;
              background: var(--white);
              border: 1px solid var(--fade-grey-dark-3);
              border-radius: 500px;

              img {
                display: flex;
                height: 26px;
                width: 26px;
              }
            }

            span {
              font-family: var(--font);
              font-weight: 500;
              padding-top: 12px;
              display: block;
              transition: color 0.3s;
            }
          }
        }

        .tns-slider {
          .active {
            background: var(--primary);
            border-color: var(--primary);

            .food-pill-icon {
              border-color: var(--primary);
            }

            >span {
              color: var(--smoke-white);
            }
          }
        }

        .slick-prev::before,
        .slick-next::before {
          color: var(--muted-grey);
        }

        .slick-custom {
          position: absolute;
          top: -50px;
          display: flex;
          justify-content: center;
          align-items: center;
          border: 1px solid transparent;
          width: 30px;
          height: 30px;
          background: transparent;
          border-radius: 100px;
          cursor: pointer;
          color: var(--light-text);
          transition: all 0.3s; // transition-all test
          z-index: 25;

          &.is-prev {
            right: 30px;

            i {
              position: relative;
              left: -1px;
            }
          }

          &.is-next {
            right: 0;

            i {
              position: relative;
              right: -1px;
            }
          }

          &:hover {
            border-color: var(--fade-grey-dark-4);
            background: var(--white);
            box-shadow: var(--light-box-shadow);
          }

          svg {
            height: 16px;
            width: 16px;
            color: var(--primary);
            transition: all 0.3s; // transition-all test
          }
        }
      }

      .restaurants-list {
        padding: 30px 0;

        .restaurants-list-item {
          @include vuero-l-card;

          position: relative;
          padding: 0;
          border: none;
          background: none;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          height: 100%;
          min-height: 320px;

          .image-container {
            position: relative;

            >img {
              display: block;
              object-fit: cover;
              border-radius: 24px;
              min-height: 180px;
              max-height: 180px;
              width: 100%;
            }

            .timer {
              position: absolute;
              bottom: 10px;
              left: 10px;
              display: flex;
              justify-content: center;
              align-items: center;
              height: 50px;
              width: 50px;
              border-radius: 12px;
              background: var(--primary);
              border: 1px solid var(--primary);
              font-family: var(--font);
              text-align: center;

              span {
                display: block;

                &:first-child {
                  font-size: 1.3rem;
                  color: var(--smoke-white);
                  font-weight: 600;
                  line-height: 1;
                }

                &:nth-child(2) {
                  font-size: 0.7rem;
                  text-transform: uppercase;
                  color: var(--primary-light-40);
                }
              }
            }
          }

          .meta-container {
            display: flex;
            align-items: center;
            padding: 5px;

            .meta-icon {
              display: flex;
              justify-content: center;
              align-items: center;
              width: 46px;
              min-width: 46px;
              height: 46px;
              max-height: 46px;
              background: var(--white);
              border: 1px solid var(--fade-grey-dark-3);
              border-radius: 500px;

              img {
                display: flex;
                height: 22px;
                width: 22px;
              }
            }

            .meta-content {
              margin-left: 8px;
              font-family: var(--font);
              line-height: 1.3;

              h4 {
                font-family: var(--font-alt);
                font-weight: 600;
                font-size: 1rem;
                color: var(--dark-text);
              }

              p {
                display: flex;
                align-items: center;

                .fa-circle {
                  font-size: 5px;
                  margin: 0 10px;
                }

                .fa-star {
                  position: relative;
                  top: -1px;
                  font-size: 12px;
                  color: #fab82a;

                  +span {
                    color: var(--dark-text);
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  >.right {
    width: 30%;
    padding: 0 0 0 20px;

    .sticky-panel {
      position: fixed;
      height: calc(100% - 100px);
      transition: all 0.3s; // transition-all test
      width: 336px;

      &.is-stretched {
        height: calc(100% - 30px);
        top: 10px;
      }

      .icon-toolbar-widget {
        width: 100%;
      }

      .side-section {
        display: none;
        animation: fadeInLeft 0.5s;

        &.is-active {
          display: block;
        }
      }

      .cart-widget {
        height: calc(100% - 90px);

        .section-placeholder {
          height: calc(100% - 160px);

          img {
            max-width: 90px;
            margin: 0 auto 10px;
          }
        }

        .cart-items {
          height: calc(100% - 160px);
          border-bottom: 1px solid var(--fade-grey-dark-3);
          padding-bottom: 40px;
          overflow-y: auto;

          .cart-item {
            align-items: center;

            .price {
              margin: 0;
              font-size: 0.9rem;
              font-weight: 500;
            }
          }
        }

        .cart-button {
          .total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5px;

            span {
              font-family: var(--font);
              margin: 0;

              &:first-child {
                font-size: 1rem;
                font-weight: 500;
                color: var(--light-text);
                text-transform: uppercase;
              }

              &:nth-child(2) {
                color: var(--dark-text);
                font-weight: 600;
                font-size: 1.4rem;
              }
            }
          }
        }
      }
    }
  }
}

.is-dark {
  .food-delivery-dashboard {
    .left {
      .left-header {
        background: var(--dark-sidebar-light-2) !important;

        .header-meta {
          h3 {
            color: var(--dark-dark-text);
          }

          p {
            color: var(--primary);
          }
        }
      }

      .left-body {
        .restaurants {
          .restaurants-toolbar {
            .left {
              h3 {
                color: var(--dark-dark-text);
              }
            }
          }

          .food-pills {
            .food-pills-inner {
              .food-pill {
                background: var(--dark-sidebar-light-2) !important;
                border-color: var(--dark-sidebar-light-12) !important;

                span {
                  color: var(--dark-dark-text);
                }

                &.active {
                  background: var(--primary) !important;
                  border-color: var(--primary) !important;

                  span {
                    color: var(--white);
                  }
                }

                .food-pill-icon {
                  background: var(--fade-grey-light-3);
                  border-color: var(--fade-grey-light-3);
                }
              }

              .slick-slide {
                &.slick-current {
                  background: var(--primary) !important;
                  border-color: var(--primary) !important;

                  .food-pill-icon {
                    border-color: var(--primary) !important;
                  }

                  span {
                    color: var(--smoke-white);
                  }
                }
              }

              .slick-custom {
                &:hover {
                  border-color: var(--dark-sidebar-light-2);
                  background: var(--dark-sidebar-light-2);
                  box-shadow: var(--light-box-shadow);
                }
              }
            }
          }

          .restaurants-list {
            .restaurants-list-item {
              @include vuero-card--dark;

              background: none;
              border: none;

              .image-container {
                .timer {
                  background: var(--primary);
                  border-color: var(--primary);

                  span {
                    &:nth-child(2) {
                      color: var(--primary-light-18);
                    }
                  }
                }
              }

              .meta-container {
                .meta-icon {
                  background: var(--fade-grey-light-3);
                  border-color: var(--fade-grey-light-3);
                }

                .meta-content {
                  h4 {
                    color: var(--dark-dark-text);
                  }

                  p {
                    .fa-star {
                      color: var(--primary);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    .right {
      .cart-widget {
        .cart-items {
          border-color: var(--dark-sidebar-light-12) !important;
        }

        .cart-button {
          .total {
            span {
              &:first-child {
                color: var(--light-text);
              }

              &:nth-child(2) {
                color: var(--dark-dark-text);
              }
            }
          }
        }
      }
    }
  }
}

// Cart widget

.cart-widget {
  @include vuero-l-card;

  &.is-straight {
    @include vuero-s-card;
  }

  .cart-items {
    .cart-item {
      display: flex;
      margin: 8px 0;

      .meta {
        margin-left: 12px;
        display: flex;
        flex-direction: column;

        span {
          display: block;
          font-family: var(--font);

          &:first-child {
            font-size: 0.9rem;
            color: var(--light-text);
          }

          &:nth-child(2) {
            color: var(--dark-text);
            margin-top: auto;
            font-weight: 600;
            font-size: 1.2rem;
          }
        }
      }
    }
  }

  .cart-button {
    padding-top: 16px;

    .button {
      min-height: 50px;
      border-radius: 10px;
    }
  }
}

.is-dark {
  .cart-widget {
    @include vuero-card--dark;

    .cart-items {
      .cart-item {
        .meta {
          span {
            &:nth-child(2) {
              color: var(--primary);
            }
          }
        }
      }
    }
  }
}

@media only screen and (max-width: 767px) {
  .food-delivery-dashboard {
    flex-direction: column;

    .left,
    .right {
      width: 100%;
      padding: 0;
    }

    .left {
      .left-header {
        flex-direction: column;
        text-align: center;

        .header-image {
          img {
            left: 0;
          }
        }
      }

      .restaurants-list {
        .restaurants-list-item {
          .image-container {
            img {
              min-height: 220px !important;
              max-height: 220px !important;
            }
          }
        }
      }
    }

    .right {
      .sticky-panel {
        position: static;
        width: 100% !important;
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
  .food-delivery-dashboard {
    flex-direction: column;

    .left,
    .right {
      width: 100%;
      padding: 0;
    }

    .left {
      .restaurants-list {
        .columns {
          display: flex;

          .column {
            min-width: 50%;
          }
        }

        .restaurants-list-item {
          .image-container {
            img {
              min-height: 220px !important;
              max-height: 220px !important;
            }
          }
        }
      }
    }

    .right {
      .sticky-panel {
        position: static;
        width: 100% !important;
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: landscape) {
  .food-delivery-dashboard {
    .left {
      .left-body {
        .restaurants-list {
          .restaurants-list-item {
            .image-container {
              >img {
                min-height: 140px !important;
                max-height: 140px !important;
              }
            }
          }
        }
      }
    }

    .right {
      .sticky-panel {
        max-width: 255px;
      }
    }
  }
}


.radial-wrap {
  display: flex;
  flex-direction: column;
  height: calc(100% - 44px);

  .radial-stats {
    margin-top: auto;
    display: flex;
    padding-top: 20px;
    border-top: 1px solid var(--fade-grey-dark-3);

    .radial-stat {
      width: 50%;
      text-align: center;

      &:first-child {
        border-right: 1px solid var(--fade-grey-dark-3);
      }

      span {
        display: block;

        &:first-child {
          color: var(--light-text);
          font-size: 0.9rem;
        }

        &:nth-child(2) {
          color: var(--dark-text);
          font-size: 1.3rem;
          font-weight: 600;
        }
      }
    }
  }
}
</style>
