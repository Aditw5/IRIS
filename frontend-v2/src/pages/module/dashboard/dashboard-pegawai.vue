
<template>
  <div class="personal-dashboard personal-dashboard-v1">
    <!--Personal Dashboard V1-->
    <!--Header-->
    <div class="dashboard-header">
      <VAvatar picture="/images/avatars/svg/vuero-1.svg" size="large" />
      <div class="start">
        <h3>Welcome back, {{userLogin.pegawai.namaLengkap}}</h3>
        <p>We're very happy to see you again on your personal dashboard.</p>
      </div>
      <div class="end">
        <VButton dark="3">View Reports</VButton>
        <VButton color="primary">
              <span class="icon">
                <i aria-hidden="true" class="fas fa-plus"></i>
              </span>
              <span> Data Pegawai</span>
            </VButton>
      </div>
    </div>

    <!--Body-->
    <div class="dashboard-body">
      <div class="columns is-multiline">
        <!--Card-->
        <div class="column is-6">
          <div class="dashboard-card">
            <h4 class="dark-inverted">Rekap Pegawai</h4>

            <div class="quick-stats">
              <div class="quick-stats-inner">
                <!--Stat-->
                <div class="quick-stat">
                  <VBlock :title="item.totalPegawai? item.totalPegawai : 0" subtitle="Total Pegawai" center m-responsive t-responsive >
                    <template #icon>
                      <VIconBox color="purple" rounded>
                        <i aria-hidden="true" class="lnil lnil-analytics-alt-1" ></i>
                      </VIconBox>
                    </template>

                  </VBlock>
                </div>

                <!--Stat-->
                <div class="quick-stat" raised @click="detailAktif()">
                  <VBlock :title="item.totalPegawaiAktif? item.totalPegawaiAktif : 0" subtitle="Pegawai Aktif" center m-responsive t-responsive>
                    <template #icon>
                      <VIconBox color="orange" rounded>
                        <i aria-hidden="true" class="lnil lnil-handshake"></i>
                      </VIconBox>
                    </template>
                  </VBlock>
                </div>

                <!--Stat-->
                <div class="quick-stat">
                  <VBlock :title="item.totalPegawaiNonaktif? item.totalPegawaiNonaktif : 0" subtitle="Pegawai Nonaktif" center m-responsive t-responsive>
                    <template #icon>
                      <VIconBox color="green" rounded>
                        <i aria-hidden="true" class="lnil lnil-diamond-alt"></i>
                      </VIconBox>
                    </template>
                  </VBlock>
                </div>

                <!--Stat-->
                <div class="quick-stat">
                  <VBlock title="100" subtitle="Total Dokter" center m-responsive t-responsive>
                    <template #icon>
                      <VIconBox color="info" rounded>
                        <i aria-hidden="true" class="lnil lnil-bank"></i>
                      </VIconBox>
                    </template>
                  </VBlock>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!--Card-->
        <div class="column is-6">
          <div class="dashboard-card">
            <h4 class="dark-inverted">Rekap Jenis Kelamin </h4>
            <ApexChart id="apex-chart-17" :height="270" :type="'pie'" :series="chartJK.series" :options="chartJK">
            </ApexChart>
          </div>
        </div>

        <!--
        <div class="column is-4">
          <div class="dashboard-card is-upgrade">
            <i aria-hidden="true" class="lnil lnil-crown-alt-1"></i>
            <div class="cta-content">
              <h4>Hey Erik, you're doing great.</h4>
              <p class="white-text">Start using our team and project management tools</p>
              <a class="link inverted-text">Learn More</a>
            </div>
          </div>
        </div>-->

        <!--Card-->
        <div class="column is-6">
          <div class="dashboard-card is-gauge">
            <h4 class="dark-inverted">Pendidikan Terakhir </h4>
            <ApexChart id="apex-chart-18" :height="265" :type="'donut'" :series="chartPK.series" :options="chartPK">
            </ApexChart>
          </div>
        </div>

        <!--Card-->
        <div class="column is-6">
          <div class="dashboard-card is-gauge">
            <h4 class="dark-inverted">Jenis Pegawai </h4>
            <ApexChart id="apex-chart-17" :height="270" :type="'pie'" :series="chartJP.series" :options="chartJP">
            </ApexChart>
          </div>
        </div>

        <!--Card-->
        <div class="column is-6">
          <div class="dashboard-card is-gauge">
            <h4 class="dark-inverted">Unit Kerja </h4>
            <ApexChart id="apex-chart-18" :height="270" :type="'donut'" :series="chartUK.series" :options="chartUK">
            </ApexChart>
          </div>
        </div>
        <!--Card-->
        <div class="column is-6">
          <div class="dashboard-card is-gauge">
            <h4 class="dark-inverted">Kelompok Jabatan </h4>
            <ApexChart id="apex-chart-12" :height="265" :type="'bar'" :series="chartKJ.series" :options="chartKJ">
            </ApexChart>
          </div>
        </div>

      </div>
    </div>

    <VModal :open="modalInput" title="Pegawai Aktif" actions="right" size ="big" @close="modalInput = false">
      <template #content>
        <form class="modal-form">
          <div class="column is-3" >
            
            <VField>
              <VControl icon="feather:search" >
                <input v-model="filters" class="input custom-text-filter" placeholder="Pencarian..." />
              </VControl>
            </VField>
          </div>
   <!-- <VSimpleDatatables>
    <thead>
      <tr>
        <th scope="col" data-sortable="false">Foto</th>
        <th scope="col">Nama Lengkap</th>
        <th scope="col">Size</th>
        <th scope="col">Version</th>
        <th scope="col">Last Updated</th>
        <th scope="col" data-sortable="false"></th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(item, key) in dataSourcefiltered" :key="key">
        <td>
          <span class="light-text">{{ item.photodiri }}</span>
        </td>
        <td>
          <span class="has-dark-text dark-inverted is-font-alt is-weight-600 rem-90">{{
            item.namalengkap
          }}</span>
        </td>
        <td>
          <span class="light-text">{{ item.jeniskelamin }}</span>
        </td>
        <td>
          <span class="light-text">{{ item.jenispegawai }}</span>
        </td>
        <td>
          <WidgetDropdown />
        </td>
      </tr>
    </tbody>
  </VSimpleDatatables>-->
   
       <DataTable :value="dataSourcefiltered" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines >

            <Column field="namalengkap" header="Nama" :sortable="true"></Column>
            <Column field="jeniskelamin" header="Jenis Kelamin"></Column>
            <Column field="jenispegawai" header="Jenis Pegawai"></Column>
            <Column field="namajabatan" header="Jabatan"></Column>
            <Column field="namadepartemen" header="Unit Kerja" :sortable="true"></Column>
          </DataTable>

     
        </form>
      </template>
    </VModal>
  </div>
</template>
<script setup lang="ts">
import ApexChart from 'vue3-apexcharts'
import { ref, computed, watch, reactive } from 'vue'
import { useThemeColors } from '/@src/composable/useThemeColors'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useRoute, useRouter } from 'vue-router'
import { datatableV3 } from '/@src/data/layouts/datatable-v3'

// import { customersOptions } from '/@src/data/dashboards/personal-v1/customersChart'
import { teamGaugeOptions } from '/@src/data/dashboards/personal-v1/teamGaugeChart'
import { profitChartOptions } from '/@src/data/dashboards/personal-v1/profitChart'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
const themeColors = useThemeColors()
const userLogin = useUserSession().getUser()

let ds_PEGAWAI: any = ref([])
let dataSource: any = ref([])
let isLoading: any = ref(false)
const item: any = ref({
  aktif: true
})
const currentPage: any = ref({
  limit: 5,
  rows: 50,
})
const filters = ref('')
const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return dataSource.value
  }

  return dataSource.value.filter((items: any) => {
    return (
      items.namalengkap.match(new RegExp(filters.value, 'i'))
    )
  })
})
let chartJK: any = ref({
  series: []
})
let chartPK: any = ref({
  series: []
})
let chartJP: any = ref({
  series: []
})
let chartUK: any = ref({
  series: []
})
let chartKJ: any = ref({
  series: []
})
const customersOptions: any = {
  series: [],
  chart: {
    height: 295,
    type: 'area',
    toolbar: {
      show: false,
    },
  },
  colors: [themeColors.accent, themeColors.info, themeColors.green],
  title: {
    text: 'Customers',
    align: 'left',
  },
  legend: {
    position: 'top',
  },
  dataLabels: {
    enabled: false,
  },
  stroke: {
    width: [2, 2, 2],
    curve: 'smooth',
  },
  xaxis: {
    type: 'datetime',
    categories: [
      '2020-09-19T00:00:00.000Z',
      '2020-09-20T01:30:00.000Z',
      '2020-09-21T02:30:00.000Z',
      '2020-09-22T03:30:00.000Z',
      '2020-09-23T04:30:00.000Z',
      '2020-09-24T05:30:00.000Z',
      '2020-09-25T06:30:00.000Z',
    ],
  },
  tooltip: {
    x: {
      format: 'dd/MM/yy HH:mm',
    },
  },
}

const route = useRoute()
isLoading.value = false

const modalInput = ref(false)
const modalDetail = ref(false)

function detailAktif() {
  modalInput.value = true
}

async function fetchDataChart() {
  await useApi().get(
    `/dashboard/data-pegawai`).then((response: any) => {
      item.value = response
      chartJK.value = {
        series: response.chartJK.series,
        chart: {
          height: 295,
          type: 'pie',
        },
        colors: [
          themeColors.accent,
          themeColors.info,
          themeColors.green,
          themeColors.purple,
          themeColors.orange,
        ],
        labels: response.chartJK.labels,
        responsive: [
          {
            breakpoint: 480,
            options: {
              chart: {
                width: 315,
                toolbar: {
                  show: false,
                },
              },
              legend: {
                position: 'top',
              },
            },
          },
        ],
        legend: {
          position: 'right',
          horizontalAlign: 'center',
        },
      }

      chartPK.value = {
        series: response.chartPK.series,
        chart: {
          height: 290,
          type: 'donut',
        },
        colors: [
          themeColors.accent,
          themeColors.info,
          themeColors.green,
          themeColors.purple,
          themeColors.orange,
        ],
        labels: response.chartPK.labels,
        responsive: [
          {
            breakpoint: 480,
            options: {
              chart: {
                width: 280,
                toolbar: {
                  show: false,
                },
              },
              legend: {
                position: 'top',
              },
            },
          },
        ],
        legend: {
          position: 'right',
          horizontalAlign: 'center',
        },
      }

      chartJP.value = {
        series: response.chartJP.series,
        chart: {
          height: 295,
          type: 'pie',
        },
        colors: [
          themeColors.accent,
          themeColors.info,
          themeColors.green,
          themeColors.purple,
          themeColors.orange,
        ],
        labels: response.chartJP.labels,
        responsive: [
          {
            breakpoint: 480,
            options: {
              chart: {
                width: 315,
                toolbar: {
                  show: false,
                },
              },
              legend: {
                position: 'top',
              },
            },
          },
        ],
        legend: {
          position: 'right',
          horizontalAlign: 'center',
        },
      }

      chartUK.value = {
        series: response.chartUK.series,
        chart: {
          height: 290,
          type: 'donut',
        },
        colors: [
          themeColors.accent,
          themeColors.info,
          themeColors.green,
          themeColors.purple,
          themeColors.orange,
        ],
        labels: response.chartUK.labels,
        responsive: [
          {
            breakpoint: 480,
            options: {
              chart: {
                width: 280,
                toolbar: {
                  show: false,
                },
              },
              legend: {
                position: 'top',
              },
            },
          },
        ],
        legend: {
          position: 'right',
          horizontalAlign: 'center',
        },
      }
      chartKJ.value = {
        series: response.chartKJ.series,
        chart: {
          type: 'bar',
          height: 280,
          toolbar: {
            show: false,
          },
        },
        colors: [themeColors.green],
        plotOptions: {
          bar: {
            horizontal: true,
          },
        },
        dataLabels: {
          enabled: false,
        },
        xaxis: {
          categories: response.chartKJ.categories
        },


      }
      ds_PEGAWAI.value = response
      ds_PEGAWAI.value.total = response.length
    })
}

async function fetchDataPegawai() {
  isLoading = true
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = offset * limit - limit
  let rows: any = currentPage.value.rows
  let NmLengkap = ''

  if (item.namalengkap) NmLengkap = '&nmlengkap' + item.namalengkap

  const response = await useApi().get(
    '/dashboard/data-pegawaiaktif?'+
    NmLengkap
  )
  isLoading = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
  }

  dataSource.value = response.data
  //   dataSource.value.total = response.length
}


fetchDataPegawai()
fetchDataChart()

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

.is-navbar {
  .personal-dashboard {
    margin-top: 30px;
  }
}

.personal-dashboard-v1 {
  .dashboard-header {
    display: flex;
    align-items: center;
    font-family: var(--font);
    margin-bottom: 30px;

    .start {
      margin-left: 12px;

      h3 {
        font-family: var(--font-alt);
        font-weight: 600;
        font-size: 1.3rem;
        color: var(--dark-text);
      }
    }

    .end {
      margin-left: auto;
      display: flex;
      justify-content: flex-end;

      .button {
        margin-left: 10px;
      }
    }
  }

  .dashboard-body {
    .dashboard-card {
      @include vuero-s-card;

      font-family: var(--font);

      >h4,
      .ApexCharts-title-text {
        font-family: var(--font-alt);
        font-size: 1rem;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 12px;
      }

      .quick-stats {
        .quick-stats-inner {
          display: flex;
          flex-wrap: wrap;
          margin-left: -8px;
          margin-right: -8px;

          .quick-stat {
            width: calc(50% - 16px);
            padding: 40px 20px;
            background: var(--widget-grey);
            margin: 8px;
            border-radius: var(--radius-large);
            transition: all 0.3s; // transition-all test

            ::v-deep(.media-flex-center) {
              .flex-meta {
                span {
                  &:first-child {
                    font-size: 1.4rem;
                    font-weight: 600;
                    color: var(--dark-text);
                  }
                }
              }
            }
          }
        }
      }
    }

    .dashboard-card {
      &.is-upgrade {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        background: var(--primary-light-8);
        border-color: var(--primary-light-8);
        padding: 20px 40px;
        min-height: 320px;
        border-radius: var(--radius-large);
        box-shadow: var(--primary-box-shadow);

        .lnil,
        .lnir {
          position: absolute;
          bottom: 1rem;
          right: 1rem;
          font-size: 4rem;
          opacity: 0.3;
        }

        .cta-content {
          text-align: center;

          h4 {
            font-family: var(--font-alt);
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--smoke-white);
            margin-bottom: 8px;
          }
        }

        .link {
          display: block;
          font-family: var(--font-alt);
          font-weight: 500;
          margin-top: 0.5rem;

          &:hover,
          &:focus {
            color: var(--smoke-white);
            opacity: 0.6;
          }
        }
      }

      &.is-gauge {
        position: relative;

        .people {
          position: absolute;
          top: 80px;
          left: 0;
          right: 0;
          margin: 0 auto;
          display: flex;
          justify-content: center;
          z-index: 5;

          .v-avatar {
            margin: 0 4px;
          }
        }
      }
    }
  }
}

.is-dark {
  .personal-dashboard-v1 {
    .dashboard-header {
      .start {
        h3 {
          color: var(--dark-dark-text);
        }
      }
    }

    .dashboard-body {
      .dashboard-card {
        @include vuero-card--dark;

        &.is-upgrade {
          background: var(--primary);
          border-color: var(--primary);
          box-shadow: var(--primary-box-shadow);
        }

        .quick-stats {
          .quick-stats-inner {
            .quick-stat {
              background: var(--dark-sidebar-light-2);
              border: 1px solid var(--dark-sidebar-light-12);

              .media-flex-center {
                .flex-meta {
                  span {
                    &:first-child {
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
  .personal-dashboard-v1 {
    .dashboard-header {
      text-align: center;
      flex-direction: column;

      .start {
        margin: 10px auto;
      }

      .end {
        margin: 0;
        justify-content: space-between;
      }
    }

    .dashboard-body {
      .dashboard-card {
        .quick-stats {
          .quick-stats-inner {
            .quick-stat {
              padding: 20px;

              .media-flex-center {
                flex-direction: column;

                .flex-meta {
                  margin: 10px 0;
                  text-align: center;
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
  .personal-dashboard-v1 {
    .dashboard-body {
      .dashboard-card {
        .quick-stats {
          .quick-stats-inner {
            .quick-stat {
              padding: 20px;

              .media-flex-center {
                flex-direction: column;

                .flex-meta {
                  margin: 2px 0 0;
                  text-align: center;
                }
              }
            }
          }
        }
      }
    }
  }
}
</style>
