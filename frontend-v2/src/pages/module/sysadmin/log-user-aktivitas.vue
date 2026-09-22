<template>
  <div class="columns is-multiline">
    <VCard style="padding-bottom: 0px">
      <div class="column c-title pt-2 mb-5">
        <label class="title-page">Log Aktivitas User</label>
      </div>
      <div class="column is-12">
        <div class="columns  is-multiline">
          <div class="column is-4">
            <VField label="Periode" style="margin-bottom: 6px;" />
            <VDatePicker v-model="item.filterTgl" is-range color="pink" trim-weeks rounded>
              <template #default="{ inputValue, inputEvents }">
                <VField addons>
                  <VControl icon="feather:calendar">
                    <VInput :value="inputValue.start" v-on="inputEvents.start" />
                  </VControl>
                  <VControl>
                    <VButton static><i class="fas fa-arrow-right" aria-hidden="true"></i></VButton>
                  </VControl>
                  <VControl icon="feather:calendar">
                    <VInput :value="inputValue.end" v-on="inputEvents.end" />
                  </VControl>
                </VField>
              </template>
            </VDatePicker>
          </div>

          <div class="column is-2">
            <VField label="Nama User">
              <VControl icon="feather:bookmark">
                <VInput type="text" v-model="item.nama" placeholder="Nama User" />
              </VControl>
            </VField>
          </div>
          <div class="column is-2">
            <VField label="Keterangan">
              <VControl icon="feather:activity">
                <VInput type="text" v-model="item.ket" placeholder="Keterangan" />
              </VControl>
            </VField>
          </div>
          <div class="column is-1">
            <VField label="Rows">
              <VControl icon="feather:bar-chart">
                <VInput type="text" v-model="item.rows" placeholder="Rows" />
              </VControl>
            </VField>
          </div>
          <div class="column is-2">
            <VField v-slot="{ id }" class="is-icon-select" label="View">
              <VControl>
                <Multiselect v-model="selectView" :attrs="{ id }" placeholder="Select View" label="name" :options="d_View"
                  :searchable="true" track-by="name" mode="single" @select="changeView(selectView)" autocomplete="off">
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
          <div class="column btn-search">
            <VIconButton type="button" color="success" class="is-rounded" rounded raised icon="fas fa-search"
              @click="cariRiwayat()" :loading="isLoading">

            </VIconButton>

          </div>

        </div>
      </div>


      <div class="column is-12 ">
        <div class="column" v-if="isLoading">
          <VPlaceloadWrap v-for="data in 10">
            <VPlaceload class="mx-2 mb-3" />
          </VPlaceloadWrap>
        </div>
        <div class="column" v-else>
          <VPlaceholderPage v-if="d_Log.length == 0" title="Data Tidak di Temukan." subtitle="Silakan gunakan filter lain"
            larger>
            <template #image>
              <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
              <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
            </template>
          </VPlaceholderPage>
          <div v-else>
            <div class="column" v-if="selectView == 'grid'">
              <div class="table-scroll">
                <DataTable :value="d_Log" :rows="20" v-model:expandedRows="expandedRows"
                  :rowsPerPageOptions="[5, 10, 15, 20]" :loading="isLoading" class="p-datatable-sm"
                  responsiveLayout="stack" breakpoint="960px" selectionMode="single" sortMode="multiple" dataKey="norec"
                  paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                  paginator currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
                  <!-- <Column expander style="width: 30px" /> -->
                  <Column field="no" header="No" style="width:40px"></Column>
                  <Column field="tanggal" header="Tanggal"></Column>
                  <!-- <Column field="kelompok" header="Kelompok User"></Column> -->
                  <Column field="username" header="Username"></Column>
                  <Column field="namalengkap" header="Nama Lengkap"></Column>
                  <Column field="jenis" header="Jenis"></Column>
                  <Column field="keterangan" header="Deskripsi"></Column>

                  <!-- <template #expansion="slotProps">
                    <div class="p-3">
                      <div class="text-center additional-info">
                        <p>{{ slotProps.data.keterangan }}</p>
                      </div>
                    </div>
                  </template> -->
                </DataTable>
              </div>
            </div>
            <div class="column" v-else>
              <div class="tile-grid tile-grid-v3">
                <div class="tile is-ancestor" v-for="(item, i) in d_Log">
                  <div class="tile is-parent">
                    <a class="tile is-child tile-grid-item is-medium">
                      <div class="tile-grid-item-inner">

                        <div class="meta">
                          <div class="tile-title">
                            <h3 class="dark-inverted" data-filter-match>
                              {{ item.keterangan }}
                            </h3>
                            <p>
                              {{ item.jenis }}
                            </p>
                          </div>
                          <div class="tile-meta">
                            <VAvatar size="small" :initials="item.initials" :color="item.color" />
                            <div class="meta-inner">
                              <span class="dark-inverted" data-filter-match> {{ item.namalengkap }} <a style="
    color: var(--light-text);">({{ item.username }})</a></span>
                              <span> {{ H.formatDateIndo(item.tanggal) }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </a>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </VCard>
  </div>
</template>
<script  setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, reactive } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm'
import { useHead } from '@vueuse/head'
import moment from 'moment'
import * as H from '/@src/utils/appHelper'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import sleep from '/@src/utils/sleep'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useThemeColors } from '/@src/composable/useThemeColors'
let isLoading = ref(false)
const d_Log: any = ref([])
const d_KelompokUser: any = ref([]);
const input: any = ref({})
const expandedRows = ref();
const jenis = ref('')
const listColor: any = ref(Object.keys(useThemeColors()))

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
useHead({
  title: 'Log Aktivitas User - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const item: any = ref({
  aktif: true,
  isKK: false,
  filterTgl: reactive({
    start: new Date(),
    end: new Date(),
  }),
  rows:50
})

async function cariRiwayat() {
  isLoading.value = true;
  let startDate = moment(item.value.filterTgl.start).format('YYYY-MM-DD');
  let endDate = moment(item.value.filterTgl.end).format('YYYY-MM-DD');
  let kelompokuser = item.value.kelompok ?? "";
  let nama = item.value.nama ?? ""
  let ket = item.value.ket ?? ""
  let rows = item.value.rows ?? ""
  useApi().get(
    `/general/list-log-user?tglAwal=${startDate}&tglAkhir=${endDate}&kelompok=${kelompokuser}&nama=${nama}&keterangan=${ket}&rows=${rows}`).then(async (response: any) => {
      d_Log.value = response
      let x = 0
      for (let i = 0; i < response.data.length; i++) {
        const element = response.data[i];

        element.no = i + 1
        let ini = element.namalengkap.split(' ')
        let init = element.namalengkap.substr(0, 1)
        if (ini.length > 1) {
          init = init + ini[1].substr(0, 1)
        }
        element.initials = init
        if (x > 5) {
          x = 0
        }
        element.color = listColor.value[x]
        x++
      }
      d_Log.value = response.data
      await sleep(2000)
      expandAll()
      isLoading.value = false
    }).catch((e: any) => {
      isLoading.value = false
    })

}

const getListKelompokUser = async () => {
  const response = await useApi().get(`/general/dropdown/kelompokuser_s?select=id,kelompokuser`)
  d_KelompokUser.value = response;
}
const expandAll = () => {
  expandedRows.value = d_Log.value.filter((p: any) => p.norec);
};
const changeView = (e: any) => {
  selectView.value = e
}
cariRiwayat();
getListKelompokUser();

</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';

.title-page {
  position: relative;
  font-size: 17px;
  display: block;
  margin-bottom: 3px;
  margin-top: 8px;
  font-weight: 600;
}

.btn-search {
  display: flex;
  align-items: center;
  margin-top: 14px;
}

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

.tile-grid-v3 {
  .tile {
    &.is-ancestor {
      margin-left: -0.5rem;
      margin-right: -0.5rem;
      margin-top: -0.5rem;
    }

    &.is-parent {
      padding: 0.5rem;
    }
  }

  .tile-grid-item {
    @include vuero-s-card;

    padding: 10px;
    border-radius: 16px;

    &.is-medium {
      max-height: 132px;

      .tile-grid-item-inner {
        display: flex;
        height: 100%;

        >img {
          display: block;
          border-radius: 12px;
          width: 100%;
          max-width: 110px;
          min-width: 110px;
          height: 100%;
          min-height: 110px;
          max-height: 110px;
          object-fit: cover;
        }

        .meta {
          margin-left: 12px;
          display: flex;
          flex-direction: column;
          justify-content: space-between;

          .tile-title {
            h3 {
              font-family: var(--font);
              font-family: var(--font-alt);
              font-size: 1rem;
              font-weight: 600;
              color: var(--dark-text);
              line-height: 1.3;
            }
          }

          .tile-meta {
            display: flex;
            align-items: center;
            margin-top: auto;
            padding: 0 0 5px;

            .v-avatar {
              max-width: 26px;
              max-height: 26px;
              min-width: 26px;

              .avatar {
                max-width: 26px;
                max-height: 26px;
                min-width: 26px;
              }
            }

            .meta-inner {
              margin-left: 8px;
              line-height: 1.2;

              span {
                display: block;
                font-weight: 400;

                &:first-child {
                  color: var(--dark-text);
                  font-family: var(--font-alt);
                  font-size: 0.85rem;
                  font-weight: 600;
                }

                &:nth-child(2) {
                  font-size: 0.8rem;
                  font-family: var(--font);
                  color: var(--light-text);
                }
              }
            }
          }
        }
      }
    }

    &.is-large {
      .tile-grid-item-inner {
        display: flex;
        flex-direction: column;
        height: 100%;

        >img {
          display: block;
          border-radius: 12px;
          width: 100%;
          height: 180px;
          object-fit: cover;
        }

        .meta {
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          flex: 1 1 0;
          padding: 0 5px;

          .tile-title {
            padding-top: 10px;

            h3 {
              font-family: var(--font);
              font-size: 1.2rem;
              font-weight: 500;
              color: var(--dark-text);
              line-height: 1.1;
            }
          }

          .tile-meta {
            display: flex;
            align-items: center;
            margin-top: auto;
            padding: 0 0 5px;

            .v-avatar {
              max-width: 26px;
              max-height: 26px;
              min-width: 26px;

              .avatar {
                max-width: 26px;
                max-height: 26px;
                min-width: 26px;
              }
            }

            .meta-inner {
              margin-left: 8px;
              line-height: 1.2;

              span {
                display: block;
                font-weight: 400;

                &:first-child {
                  color: var(--dark-text);
                  font-size: 0.9rem;
                  font-weight: 500;
                }

                &:nth-child(2) {
                  font-size: 0.8rem;
                  color: var(--light-text);
                }
              }
            }
          }
        }
      }
    }

    &.is-wide {
      .tile-grid-item-inner {
        display: flex;
        height: 100%;

        >img {
          display: block;
          border-radius: 12px;
          width: 100%;
          max-width: 280px;
          height: 100%;
          min-height: 160px;
          object-fit: cover;
        }

        .meta {
          margin-left: 12px;
          display: flex;
          flex-direction: column;
          justify-content: space-between;

          .tile-title {
            padding-top: 5px;

            h3 {
              font-family: var(--font);
              font-size: 1.3rem;
              font-weight: 500;
              color: var(--dark-text);
              line-height: 1.1;
            }

            p {
              color: var(--light-text);
              font-size: 0.95rem;
              padding-top: 5px;
            }
          }

          .tile-meta {
            display: flex;
            align-items: center;
            margin-top: auto;
            padding: 0 0 5px;

            .v-avatar {
              max-width: 26px;
              max-height: 26px;
              min-width: 26px;

              .avatar {
                max-width: 26px;
                max-height: 26px;
                min-width: 26px;
              }
            }

            .meta-inner {
              margin-left: 8px;
              line-height: 1.2;

              span {
                display: block;
                font-weight: 400;

                &:first-child {
                  color: var(--dark-text);
                  font-size: 0.9rem;
                  font-weight: 500;
                }

                &:nth-child(2) {
                  font-size: 0.8rem;
                  color: var(--light-text);
                }
              }
            }
          }
        }
      }
    }

    &.is-tall {
      .tile-grid-item-inner {
        display: flex;
        flex-direction: column;
        height: 100%;

        >img {
          display: block;
          border-radius: 12px;
          width: 100%;

          // max-width: 110px;
          height: 220px;
          object-fit: cover;
        }

        .meta {
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          flex: 1 1 0;
          padding: 0 5px;

          .tile-title {
            padding-top: 10px;

            h3 {
              font-family: var(--font);
              font-size: 1.2rem;
              font-weight: 500;
              color: var(--dark-text);
              line-height: 1.1;
            }

            p {
              color: var(--light-text);
              font-size: 0.95rem;
              padding-top: 5px;
            }
          }

          .tile-meta {
            display: flex;
            align-items: center;
            margin-top: auto;
            padding: 0 0 5px;

            .v-avatar {
              max-width: 26px;
              max-height: 26px;
              min-width: 26px;

              .avatar {
                max-width: 26px;
                max-height: 26px;
                min-width: 26px;
              }
            }

            .meta-inner {
              margin-left: 8px;
              line-height: 1.2;

              span {
                display: block;
                font-weight: 400;

                &:first-child {
                  color: var(--dark-text);
                  font-size: 0.9rem;
                  font-weight: 500;
                }

                &:nth-child(2) {
                  font-size: 0.8rem;
                  color: var(--light-text);
                }
              }
            }
          }
        }
      }
    }
  }
}

.is-dark {
  .tile-grid {
    .tile-grid-item {
      @include vuero-card--dark;
    }
  }

  .tile-grid-v3 {
    .tile-grid-item {
      @include vuero-card--dark;
    }
  }
}

@media only screen and (max-width: 767px) {
  .tile-grid-v3 {
    .tile-grid-item {
      max-height: 132px !important;

      .tile-grid-item-inner {
        display: flex !important;
        flex-direction: row !important;
        height: 100% !important;

        >img {
          display: block !important;
          border-radius: 12px !important;
          width: 100% !important;
          max-width: 110px !important;
          min-width: 110px !important;
          height: 100% !important;
          min-height: 110px !important;
          max-height: 110px !important;
          object-fit: cover !important;
        }

        .meta {
          margin-left: 12px !important;
          display: flex !important;
          flex-direction: column !important;
          justify-content: space-between !important;

          .tile-title {
            h3 {
              font-family: var(--font) !important;
              font-size: 1.1rem !important;
              font-weight: 500;
              color: var(--dark-text) !important;
              line-height: 1.1 !important;
            }

            p {
              display: none !important;
            }
          }

          .tile-meta {
            display: flex !important;
            align-items: center !important;
            margin-top: auto !important;
            padding: 0 0 5px !important;

            .v-avatar {
              max-width: 26px !important;
              max-height: 26px !important;
              min-width: 26px !important;

              .avatar {
                max-width: 26px !important;
                max-height: 26px !important;
                min-width: 26px !important;
              }
            }

            .meta-inner {
              margin-left: 8px !important;
              line-height: 1.2 !important;

              span {
                display: block !important;
                font-weight: 400 !important;

                &:first-child {
                  color: var(--dark-text) !important;
                  font-size: 0.9rem !important;
                  font-weight: 500 !important;
                }

                &:nth-child(2) {
                  font-size: 0.8rem !important;
                  color: var(--light-text) !important;
                }
              }
            }
          }
        }
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
  .tile-grid-v3 {
    .tile-grid-item {
      max-height: 132px !important;

      .tile-grid-item-inner {
        display: flex !important;
        flex-direction: row !important;
        height: 100% !important;

        >img {
          display: block !important;
          border-radius: 12px !important;
          width: 100% !important;
          max-width: 110px !important;
          min-width: 110px !important;
          height: 100% !important;
          min-height: 110px !important;
          max-height: 110px !important;
          object-fit: cover !important;
        }

        .meta {
          margin-left: 12px !important;
          display: flex !important;
          flex-direction: column !important;
          justify-content: space-between !important;

          .tile-title {
            h3 {
              font-family: var(--font) !important;
              font-size: 1.1rem !important;
              font-weight: 500;
              color: var(--dark-text) !important;
              line-height: 1.1 !important;
            }

            p {
              display: block;
              max-width: 460px;
            }
          }

          .tile-meta {
            display: flex !important;
            align-items: center !important;
            margin-top: auto !important;
            padding: 0 0 5px !important;

            .v-avatar {
              max-width: 26px !important;
              max-height: 26px !important;
              min-width: 26px !important;

              .avatar {
                max-width: 26px !important;
                max-height: 26px !important;
                min-width: 26px !important;
              }
            }

            .meta-inner {
              margin-left: 8px !important;
              line-height: 1.2 !important;

              span {
                display: block !important;
                font-weight: 400 !important;

                &:first-child {
                  color: var(--dark-text) !important;
                  font-size: 0.9rem !important;
                  font-weight: 500 !important;
                }

                &:nth-child(2) {
                  font-size: 0.8rem !important;
                  color: var(--light-text) !important;
                }
              }
            }
          }
        }
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: landscape) {
  .tile-grid-v3 {
    .tile-grid-item {
      &.is-medium {
        .tile-grid-item-inner {
          .tile-title {
            h3 {
              font-size: 1rem !important;
            }
          }
        }
      }

      &.is-large {
        .tile-grid-item-inner {
          .tile-title {
            h3 {
              font-size: 1.1rem !important;
            }
          }
        }
      }

      &.is-tall {
        .tile-grid-item-inner {
          .tile-title {
            h3 {
              font-size: 1.2rem !important;
            }
          }
        }
      }

      &.is-wide {
        .tile-grid-item-inner {
          >img {
            max-width: 180px;
            min-height: 160px;
          }

          .tile-title {
            h3 {
              font-size: 1.2rem !important;
            }
          }
        }
      }
    }
  }
}
</style>

