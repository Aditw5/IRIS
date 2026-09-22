<template>
  <ConfirmDialog />
  <div class="form-layout is-stacked">
    <div class="form-outer" style="margin-top: 15px">
      <div class="form-body p-2">
        <div class="business-dashboard hr-dashboard">
          <div class="column is-12">
            <div class="block-header">
              <div class="left">
                <div class="current-user" style="text-align: center;">
                  <h3>{{ mitra.namaperusahaan }}</h3>
                </div>
              </div>
              <div class="center">
                <div class="columns">
                  <div class="column">
                    <h4 class="block-heading">Tanggal Registrasi</h4>
                    <p class="block-text">{{ mitra.tglregistrasi }}</p>
                    <h4 class="block-heading">No Pendaftaran</h4>
                    <p class="block-text">{{ mitra.nopendaftaran }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="column is-12" v-if="isLoadDataOrder">
            <VCard>
              <VPlaceloadWrap v-for="data in 25">
                <VPlaceload class="mx-2 mb-3" />
                <VPlaceload class="mx-2" />
              </VPlaceloadWrap>
            </VCard>
          </div>
          <div class="column is-12" v-else>
            <VCard>
              <div class="column is-12">
                <Fieldset legend="Data Alat" :toggleable="true">
                  <div class="timeline-wrapper">
                    <div class="timeline-wrapper-inner">
                      <div class="timeline-container">
                        <div class="timeline-item is-unread" v-for="(items, index) in detailOrderLayanan"
                          :key="items.norec">
                          <div :class="'dot is-' + listColor[index + 1]"></div>
                          <div class="content-wrap is-grey">
                            <div class="content-box">
                              <div class="status"></div>
                              <VIconBox size="medium" :color="listColor[index + 1]" rounded>
                                <i class="iconify" data-icon="feather:package" aria-hidden="true"></i>
                              </VIconBox>
                              <div class="box-text" style="width:70%">
                                <div class="meta-text">
                                  <p>
                                    <span>{{ items.namaproduk }}</span>
                                  </p>
                                  <table class="tb-order">
                                    <tr>
                                      <td>Lingkup</td>
                                      <td>:</td>
                                      <td>{{ items.lingkupkalibrasi }} </td>
                                    </tr>
                                    <tr v-if="item.jenisorder == 'kalibrasi'">
                                      <td>Lokasi</td>
                                      <td>:</td>
                                      <td>{{ items.lokasi }} </td>
                                    </tr>
                                    <tr v-if="item.jenisorder == 'repair'">
                                      <td>Lokasi Repair</td>
                                      <td>:</td>
                                      <td>{{ items.lokasirepair }} </td>
                                    </tr>
                                    <tr>
                                      <td>Penyelias Teknik </td>
                                      <td>:</td>
                                      <td class="font-values">{{ items.penyeliateknik }}</td>
                                    </tr>
                                    <tr>
                                      <td>Pelaksana Teknik</td>
                                      <td>:</td>
                                      <td>{{ items.pelaksanateknik }} </td>
                                    </tr>
                                    <tr v-if="item.jenisorder == 'kalibrasi'">
                                      <td>Durasi</td>
                                      <td>:</td>
                                      <td>
                                        <VTag v-if="items.durasikalbrasi" color="warning" rounded> {{
                                          items.durasikalbrasi }}
                                        </VTag>
                                      </td>
                                    </tr>
                                  </table>

                                </div>
                              </div>
                              <div class="box-end" style="width: 30%">
                                <div class="columns is-multiline">
                                  <div class="column is-6" style="margin-top: 0.5rem;">
                                    <VIconButton v-tooltip.bottom.left="'Aktivitas'" icon="feather:activity"
                                      @click="detailOrder(items)" color="info" raised circle class="mr-2">
                                    </VIconButton>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </Fieldset>
              </div>
            </VCard>
          </div>
        </div>
      </div>
    </div>
  </div>
  <VModal :open="modalDetailOrder" noclose size="big" actions="right" @close="modalDetailOrder = false, clear()"
    cancelLabel="Tutup">
    <template #content>
      <div class="business-dashboard hr-dashboard">
        <div class="columns is-multiline">
          <div class="column is-12 p-0">
            <div class="block-header">
              <div class="left column is-12 ">
                <div class="current-user">
                  <h3>{{ item.namaproduk }}</h3>
                </div>
              </div>
              <!-- <div class="left column is-6 p-0">
                <div>
                  <div>
                    <h4 class="block-heading">No. Pendaftaran</h4>
                    <p class="block-hext">{{ item.nopendaftaran }}</p>
                    <h4 class="block-heading">Tgl Registrasi</h4>
                    <p class="block-hext">{{ item.tglregistrasi }}</p>
                  </div>
                </div>
              </div> -->
            </div>

          </div>
        </div>
      </div>
      <div class="column is-12">
        <Fieldset legend="Data Alat" :toggleable="true">
          <div class="column" v-for="(data) in 3" style="text-align:center" v-if="isLoadDataDeatilOrder">
            <div class="columns is-multiline">
              <div class="column is-2" style="margin-top: 27px;">
                <VPlaceload class="mx-2" />
              </div>
              <div class="column">
                <VPlaceloadText :lines="4" width="75%" last-line-width="20%" />
              </div>

            </div>
          </div>
          <div class="timeline-wrapper" v-else>
            <div class="timeline-wrapper-inner">
              <div class="timeline-container">
                <div class="timeline-item is-unread" v-for="(item, index) in timelineItems" :key="index">
                  <div class="date">
                    <span>{{ H.formatDateIndo(item.date) }}</span>
                  </div>
                  <div :class="'dot is-' + listColor[index + 1]"></div>
                  <div class="content-wrap is-grey">
                    <div class="content-box">
                      <div class="status"></div>
                      <div class="box-text" style="width:70%">
                        <div class="meta-text">
                          <p>
                            <span>
                              {{ item.type }} : {{ item.nama }}
                            </span>
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Fieldset>
      </div>
    </template>
    <template #action>
    </template>
  </VModal>
</template>
<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useWindowScroll } from '@vueuse/core'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useRoute, useRouter } from 'vue-router'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import AutoComplete from 'primevue/autocomplete';
import Dialog from 'primevue/dialog';
import { useConfirm } from "primevue/useconfirm"
import { useToaster } from '/@src/composable/toaster'
import ConfirmDialog from 'primevue/confirmdialog'
import { useThemeColors } from '/@src/composable/useThemeColors'

useHead({
  title: 'Detail Registrasi Produk - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
let NOREC_PD = useRoute().query.norec_pd as string
const themeColors = useThemeColors()
const mitra: any = ref({})
const dataSource: any = ref({})
const d_Ruangan: any = ref([])
const d_Dokter: any = ref([])
const d_KelompokPasien: any = ref([])
const d_Rekanan: any = ref([])
const d_Kelas: any = ref([])
const itemSource: any = ref([])
const modalKonsultasi: any = ref(false)
const modalChangeDokter: any = ref(false)
const modalChangeRekanan: any = ref(false)
const modalChangeDate: any = ref(false)
const btnLoadSimpan: any = ref(false)
const btnLoadDelete: any = ref(false)
const isPlaceLoad: any = ref(true)
const isLoadingTT: any = ref(false)
const isPlaceLoadHead: any = ref(true)
const isDisabled: any = ref(true)
const isLoadingKonsul: any = ref(false)
const timelineItems = ref([])
let detailOrderLayanan: any = ref(0)
let isLoadDataOrder: any = ref(false)
let isLoadDataDeatilOrder: any = ref(false)
let listColor: any = ref(Object.keys(useThemeColors()))
let modalDetailOrder: any = ref(false)
const { y } = useWindowScroll()
const isStuck = computed(() => {
  return y.value > 30
})
const item: any = ref({})

const confirm = useConfirm();

const headerMitra = async () => {
  await useApi().get(`/asman/header-mitra?norec_pd=${NOREC_PD}`)
    .then((response: any) => {
      mitra.value = response.mitra
    })
}

const orderVerify = async () => {
  detailOrderLayanan.value = []
  isLoadDataOrder.value = true
  const response = await useApi().get(`/asman/layanan-verif?norec_pd=${NOREC_PD}`)
  response.detail.forEach((element: any, i: any) => {
    element.no = i + 1
  });
  isLoadDataOrder.value = false
  detailOrderLayanan.value = response.detail
}

const detailOrder = async (e) => {
  modalDetailOrder.value = true
  item.value.namaproduk = e.namaproduk
  isLoadDataDeatilOrder.value = true
  const response = await useApi().get(`/asman/detail-produk?norec_pd=${e.norec_detail}`)
  timelineItems.value = response.timeline // <-- satu array urut, langsung untuk v-for
  isLoadDataDeatilOrder.value = false
}



headerMitra()
orderVerify()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/bedah.scss';

.tabs-wrapper.is-slider .tabs,
.tabs-wrapper-alt.is-slider .tabs {
  position: relative;
  background: var(--fade-grey-light-2);
  border: 1px solid var(--fade-grey);
  max-width: 400px;
  height: 35px;
  border-bottom: none;

}

.tb-order .text-value {
  font-family: var(--font-alt);
  color: var(--dark-text);
  font-weight: 400;
  font-size: 12px;
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
      font-size: 0.95rem;
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
              font-size: 0.85rem;
              color: white;
            }

            &:nth-child(2) {
              font-size: 0.8rem;
              color: white;
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

.is-dark {
  .user-grid {
    .grid-item {
      @include vuero-card--dark;
    }
  }

  .user-grid-v2 {
    .grid-item-wrap {
      border-color: var(--dark-sidebar-light-12);

      .grid-item-head {
        background: var(--dark-sidebar-light-4);
      }
    }
  }
}

.user-grid-v2 .grid-item-wrap .grid-item-head.is-registrasi {
  background: var(--success) !important
}

.user-grid-v2 .grid-item-wrap .grid-item-head {
  padding: 10px;
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
</style>