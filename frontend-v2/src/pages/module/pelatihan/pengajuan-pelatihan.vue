<template>
  <div class="column">
    <VCard class="kp-header-card">
      <div class="kp-hero">
        <div class="kp-hero-left">
          <img
            src="/@src/assets/illustrations/dashboards/personal/UMRO.png"
            alt="UMRO Laboratory"
            class="kp-hero-logo"
          />
          <div class="kp-hero-text">
            <h3 class="kp-hero-title">FMMO-163-14.4.3.b-62.3 Usulan Pelatihan/Serfikasi Personel</h3>
            <!-- <p class="kp-hero-sub">Pilih pelatihan yang tersedia & ajukan</p> -->
          </div>
        </div>
        <!-- master action dihilangkan: tidak ada tombol buat/edit/hapus -->
      </div>
    </VCard>
  </div>

  <!-- DAFTAR PELATIHAN (tanpa fitur Master) -->
  <div class="column">
    <div class="columns is-variable is-5">
      <div class="column is-12">
        <VCard>
          <div class="is-flex is-align-items-center is-justify-content-space-between">
            <h3 class="title is-6">Pelatihan Tersedia</h3>
            <div class="is-flex" style="gap:.5rem">
              <VButton color="info" outlined icon="feather:refresh-ccw" @click="fetchData()">Refresh</VButton>
            </div>
          </div>

          <div class="list-view list-view-v3">
            <div class="list-view-inner mt-2" style="max-height:1000px;overflow: auto;">
              <div name="list-complete" tag="div">
                <div v-if="isPlaceLoad && dataSourceListPelatihan.length===0">
                  <VPlaceloadWrap v-for="n in 6" :key="'sk-m-'+n">
                    <VPlaceload class="mx-2 mb-3" />
                    <VPlaceload class="mx-2" />
                  </VPlaceloadWrap>
                </div>

                <div v-else-if="dataSourceListPelatihan.length===0" class="notification is-light">
                  Belum ada pelatihan tersedia.
                </div>

                <div v-else v-for="(p, idx) in dataSourceListPelatihan" :key="p.id">
                  <div class="list-view-item">
                    <div class="list-view-item-inner">
                      <VAvatar size="small" picture="/images/avatars/svg/propinsi.svg" color="primary" bordered />
                      <div class="meta-left">
                        <h3>
                          {{ p.judulpelatihan }}
                          <VTag class="ml-2" :color="(p).jenispelatihan === 'PUBLIK' ? 'info' : 'warning'" rounded>{{(p).jenispelatihan }}</VTag>
                          <!-- <span class="has-text-grey ml-1">#{{ p.id }}</span> -->
                        </h3>

                        <span>
                          <i aria-hidden="true" class="iconify" data-icon="feather:calendar"></i>
                          <span>Pendaftaran:
                            {{ H.formatDateToLocalString(p.periodependaftaranawal) }} →
                            {{ H.formatDateToLocalString(p.periodependaftaranakhir) }}
                          </span>
                          <i aria-hidden="true" class="fas fa-circle icon-separator"></i>
                          <i aria-hidden="true" class="iconify" data-icon="feather:calendar"></i>
                          <span>Pelatihan: {{ H.formatDateToLocalString(p.tglpelatihan) }}</span>
                        </span>

                        <div class="mt-2">
                          <VTag :color="statusTag(p).color as any" rounded>
                            {{ statusTag(p).text }}
                            <template v-if="statusTag(p).text==='Dibuka' && statusTag(p).daysLeft>0">
                              · {{ statusTag(p).daysLeft }} hari lagi
                            </template>
                          </VTag>
                        </div>

                        <div class="is-size-7 has-text-grey mt-2 ellipsis-2">{{ p.deskripsi }}</div>
                      </div>

                      <!-- ACTION: hanya Ajukan -->
                      <div class="meta-right flex justify-center items-center">
                        <VButton
                          :loading="submittingId===p.id"
                          :disabled="statusTag(p).text!=='Dibuka'"
                          color="primary"
                          icon="feather:send"
                          raised
                          @click="ajukanPelatihan(p)"
                        >Ajukan</VButton>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- PAGINATION LIST -->
          <VFlexPagination
            v-model:current-page="pageMaster.page"
            :item-per-page="pageMaster.limit"
            :total-items="totalMaster"
            :max-links-displayed="5"
            class="mt-3"
          >
            <template #before-navigation>
              <VFlex class="mr-4 mt-1" column-gap="1rem">
                <VField>
                  <VControl>
                    <div class="select is-rounded">
                      <select v-model="pageMaster.limit">
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
        </VCard>
      </div>
    </div>
  </div>

  <!-- RIWAYAT PENGAJUAN -->
  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
        <h3 class="title is-5 mb-2">Riwayat Pengajuan Pelatihan</h3>
        <div class="is-flex is-align-items-center" style="gap:.5rem">
          <VTag :color="'warning'" rounded>Diajukan</VTag>
          <VTag :color="'success'" rounded>Disetujui</VTag>
          <VTag :color="'danger'" rounded>Ditolak</VTag>
        </div>
      </div>

      <div class="column" v-if="isPlaceLoad">
        <VPlaceloadWrap v-for="data in 12" :key="data">
          <VPlaceload class="mx-2 mb-3" />
          <VPlaceload class="mx-2" />
        </VPlaceloadWrap>
      </div>

      <div class="column" v-else>
        <VPlaceholderPage
          v-if="dataSource.length == 0"
          title="Belum ada pengajuan pelatihan"
          subtitle="Silakan pilih pelatihan di atas lalu klik Ajukan"
          larger
        >
          <template #image>
            <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
            <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
          </template>
        </VPlaceholderPage>

        <div v-else>
          <DataTable
            :value="dataSource"
            class="p-datatable-sm"
            :loading="isLoading"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 25]"
            scrollable
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack"
            breakpoint="960px"
            sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
            showGridlines
          >
            <template #header>
              <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="is-flex is-align-items-center" style="gap:.5rem">
                  <VButton color="warning" class="mr-2 mb-3" icon="fas fa-file-excel" raised @click="exportExcel()">
                    Export Excel
                  </VButton>
                  <VButton color="info" class="mb-3" icon="feather:refresh-ccw" outlined @click="fetchDataRiwayat()">
                    Refresh
                  </VButton>
                </div>
              </div>
            </template>

            <Column field="no" header="#" frozen></Column>
              <Column field="status" header="Status" :sortable="true" style="min-width: 140px">
              <template #body="slotProps">
                <VTag class="ml-2" :color="slotProps.data.color" rounded>{{ slotProps.data.status }}</VTag>
              </template>
            </Column>
            <Column field="keteranganstatus" header="Keterangan Status" :sortable="true" style="min-width: 240px"></Column>
            <Column field="namalengkap" header="Nama Pegawai" :sortable="true" style="min-width: 240px"></Column>
            <Column field="judulpelatihan" header="Judul" :sortable="true" style="min-width: 240px"></Column>
            <Column field="jenispelatihan" header="Jenis Pelatihan" :sortable="true" style="min-width: 140px"></Column>
            <Column field="periode" header="Periode Pendaftaran" :sortable="false" style="min-width: 240px">
              <template #body="slotProps">
                <span>
                  {{ H.formatDateToLocalString(slotProps.data.periodependaftaranawal) }} →
                  {{ H.formatDateToLocalString(slotProps.data.periodependaftaranakhir) }}
                </span>
              </template>
            </Column>
            <Column field="tglpelatihan" header="Tgl Pelatihan" :sortable="true" style="min-width: 180px">
              <template #body="slotProps">
                <span>{{ H.formatDateToLocalString(slotProps.data.tglpelatihan) }}</span>
              </template>
            </Column>
            <Column field="created_at" header="Dibuat" :sortable="true" style="min-width: 200px">
              <template #body="slotProps">
                <span>{{ H.formatDateToLocalString(slotProps.data.created_at) }}</span>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </VCard>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useThemeColors } from '/@src/composable/useThemeColors'
import * as H from '/@src/utils/appHelper'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import moment from 'moment'
import * as XLSX from 'xlsx'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'

useHead({ title: 'Usulan Pelatihan - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const themeColors = useThemeColors()
const userLogin = useUserSession().getUser()
const isLoading: any = ref(false)
const isPlaceLoad: any = ref(false)
const submittingId: any = ref(null)
const dataSource: any = ref([])            // Riwayat
const dataSourceListPelatihan: any = ref([]) // Listing tersedia
const pageMaster: any = reactive({ page: 1, limit: 3 })
const totalMaster: any = ref(0)
const item: any = ref({ search: '' })

const statusColor = (status: string) => {
  if (!status) return 'warning'
  const s = (status || '').toLowerCase()
  if (s.includes('setuju')) return 'success'
  if (s.includes('tolak')) return 'danger'
  return 'warning'
}
const statusTag = (p: any) => {
  const now = moment()
  const start = moment(p.periodependaftaranawal)
  const end = moment(p.periodependaftaranakhir).endOf('day')
  if (now.isBefore(start)) return { text: 'Belum Dibuka', color: 'info', daysLeft: start.diff(now, 'days') }
  if (now.isAfter(end)) return { text: 'Ditutup', color: 'danger', daysLeft: 0 }
  return { text: 'Dibuka', color: 'success', daysLeft: end.diff(now, 'days') }
}

// ====== Fetch Pelatihan Tersedia ======
const fetchData = async () => {
  isPlaceLoad.value = true
  try {
    const res = await useApi().get(`mutu/list-pelatihan?page=${pageMaster.page}&limit=${pageMaster.limit}`)
    const list = res?.data ?? []
    dataSourceListPelatihan.value = list
    totalMaster.value = res?.length ?? res?.total ?? list.length
  } catch (e) {
    dataSourceListPelatihan.value = []
    totalMaster.value = 0
  } finally {
    isPlaceLoad.value = false
  }
}

// ====== Ajukan Pelatihan ======
const ajukanPelatihan = async (p: any) => {
  const st = statusTag(p)
  if (st.text !== 'Dibuka') {
    H.alert('error', 'Periode pendaftaran belum dibuka atau sudah ditutup.')
    return
  }
  submittingId.value = p.id
  try {
    await useApi().post('mutu/pengajuan-pelatihan', {
      pelatihanId: p.id
    })
    await fetchDataRiwayat()
  } catch (e) {
  } finally {
    submittingId.value = null
  }
}

// ====== Riwayat Pengajuan ======
const fetchDataRiwayat = async () => {
  isPlaceLoad.value = true
  const search = item.value.search ? `&search=${encodeURIComponent(item.value.search)}` : ''
  const id = `id=${userLogin.pegawai?.id || ''}`
  try {
    const res = await useApi().get(`mutu/list-pengajuan-pelatihan?${id}${search}`)
    console.log(res)
    res.forEach((e: any, i: number) => {
      e.no = i + 1
      e.color = e.color || statusColor(e.status)
    })
    dataSource.value = res
  } catch (e) {
    dataSource.value = []
  } finally {
    isPlaceLoad.value = false
  }
}

// ====== Export (riwayat) ======
const remakeData: any = ref([])
const exportExcel = () => {
  remakeData.value = (dataSource.value || []).map((e: any) => ({
    No: e.no,
    Judul: e.judul,
    PendaftaranMulai: e.pendaftaranMulai,
    PendaftaranSelesai: e.pendaftaranSelesai,
    TglPelatihan: e.tglPelatihan,
    Status: e.status,
    Dibuat: e.created_at,
  }))
  const ws = XLSX.utils.json_to_sheet(remakeData.value)
  const wb = { Sheets: { data: ws }, SheetNames: ['data'] }
  const buff: any = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })
  const data = new Blob([buff], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8' })
  const url = window.URL.createObjectURL(data)
  const a = document.createElement('a')
  a.href = url
  a.download = 'riwayat-pelatihan.xlsx'
  document.body.appendChild(a); a.click(); a.remove()
  window.URL.revokeObjectURL(url)
}

watch(() => [pageMaster.page, pageMaster.limit], () => fetchData(), { immediate: true })
fetchDataRiwayat()
</script>

<style lang="scss">
.input-calendar { min-width: 140px; }
.icon-separator { font-size: .45rem; margin: 0 .5rem; vertical-align: middle; }

@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/customer.scss';
/* Biar judul dan deskripsi rapi 2 baris */
.ellipsis-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
/* ===== Header / Hero ===== */
.kp-header-card {
  padding: 0;
  overflow: hidden;
  border-radius: 14px;
}

.kp-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 18px 22px;
  background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(59,130,246,.08));
  border: 1px solid rgba(0,0,0,.06);
}

.kp-hero-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.kp-hero-logo {
  height: 56px;               /* proporsional & konsisten */
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,.08));
}

.kp-hero-text { line-height: 1.2; }
.kp-hero-title { margin: 0; font-weight: 700; font-size: 1.35rem; letter-spacing: .2px; }
.kp-hero-sub { margin: .2rem 0 0; font-size: .95rem; color: #6b7280; }

/* Responsif */
@media (max-width: 768px) {
  .kp-hero { flex-direction: column; align-items: flex-start; padding: 16px; gap: .75rem; }
  .kp-hero-logo { height: 48px; }
}
</style>
