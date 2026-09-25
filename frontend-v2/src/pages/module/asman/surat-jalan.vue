<template>
  <ConfirmDialog />
  <div class="column">
    <VCard class="kp-header-card">
      <div class="kp-hero">
        <div class="kp-hero-left">
          <img src="/@src/assets/illustrations/dashboards/personal/UMRO.png" alt="UMRO Laboratory"
            class="kp-hero-logo" />
          <div class="kp-hero-text">
            <h3 class="kp-hero-title">Persetujuan Surat Jalan</h3>
            <p class="kp-hero-sub">
              Halaman approval Asman untuk menyetujui atau menolak surat jalan
            </p>
          </div>
        </div>

        <div class="is-flex is-align-items-center" style="gap:.5rem">
          <VButton color="info" icon="feather:refresh-ccw" outlined @click="fetchData()">
            Refresh
          </VButton>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="columns is-multiline">
        <div class="column is-4">
          <VDatePicker v-model="item.filterTgl" is-range color="pink" trim-weeks>
            <template #default="{ inputValue, inputEvents }">
              <VField addons>
                <VControl icon="feather:calendar">
                  <VInput :value="inputValue.start" v-on="inputEvents.start" />
                </VControl>
                <VControl>
                  <VButton static>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                  </VButton>
                </VControl>
                <VControl icon="feather:calendar">
                  <VInput :value="inputValue.end" v-on="inputEvents.end" />
                </VControl>
              </VField>
            </template>
          </VDatePicker>
        </div>

        <div class="column is-3">
          <VField label="Status">
            <VControl>
              <div class="select is-rounded is-fullwidth">
                <select v-model="item.status" @change="fetchData()">
                  <option value="1">Diajukan</option>
                  <option value="2">Disetujui Asman</option>
                  <option value="3">Ditolak Asman</option>
                  <option value="all">Semua Status</option>
                </select>
              </div>
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Cari">
            <VControl icon="feather:search">
              <VInput v-model="item.search" class="is-rounded"
                placeholder="No surat / no pendaftaran / tujuan / pembawa..." @keyup.enter="fetchData()" />
            </VControl>
          </VField>
        </div>

        <div class="column is-1 mt-5">
          <VButton color="success" icon="feather:search" raised :loading="isLoading" @click="fetchData()">
            Cari
          </VButton>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
        <h3 class="title is-5 mb-0">Daftar Surat Jalan</h3>
        <div class="is-flex is-align-items-center" style="gap:.5rem">
          <VTag color="warning" rounded>Diajukan</VTag>
          <VTag color="success" rounded>Disetujui</VTag>
          <VTag color="danger" rounded>Ditolak</VTag>
        </div>
      </div>

      <div class="column" v-if="isPlaceLoad">
        <VPlaceloadWrap v-for="data in 10" :key="data">
          <VPlaceload class="mx-2 mb-3" />
          <VPlaceload class="mx-2" />
        </VPlaceloadWrap>
      </div>

      <div class="column" v-else>
        <VPlaceholderPage v-if="dataSource.length === 0" title="Tidak ada surat jalan"
          subtitle="Belum ada data sesuai filter yang dipilih" larger>
          <template #image>
            <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
            <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
          </template>
        </VPlaceholderPage>

        <DataTable v-else :value="dataSource" class="p-datatable-sm" :loading="isLoading" :paginator="true" :rows="10"
          :rowsPerPageOptions="[5, 10, 25, 50]" scrollable
          paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
          responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
          <Column field="no" header="#" frozen style="min-width:70px"></Column>

          <Column field="status" header="Status" frozen style="min-width:160px">
            <template #body="slotProps">
              <VTag :color="slotProps.data.color" rounded>
                {{ slotProps.data.status }}
              </VTag>
            </template>
          </Column>

          <Column field="nosuratjalan" header="No Surat Jalan" :sortable="true" style="min-width:220px"></Column>
          <Column field="sumber" header="Sumber" :sortable="true" style="min-width:130px"></Column>
          <Column field="nopendaftaran" header="No Pendaftaran" :sortable="true" style="min-width:220px"></Column>
          <Column field="diberikankepada" header="Diberikan Kepada" :sortable="true" style="min-width:260px"></Column>
          <Column field="tujuan" header="Tujuan" :sortable="true" style="min-width:260px"></Column>
          <Column field="namapegawaibawa" header="Yang Membawa" :sortable="true" style="min-width:220px"></Column>
          <Column field="namapetugas" header="Diajukan Oleh" :sortable="true" style="min-width:220px"></Column>

          <Column field="jumlahbarang" header="Jumlah Barang" style="min-width:150px">
            <template #body="slotProps">
              <VTag color="info" rounded>
                {{ slotProps.data.jumlahbarang || 0 }} Barang
              </VTag>
            </template>
          </Column>

          <Column field="tanggalsurat" header="Tanggal Surat" :sortable="true" style="min-width:180px">
            <template #body="slotProps">
              <span>{{ H.formatDateToLocalString(slotProps.data.tanggalsurat) }}</span>
            </template>
          </Column>

          <Column field="tglajukan" header="Tanggal Diajukan" :sortable="true" style="min-width:190px">
            <template #body="slotProps">
              <span>{{ H.formatDateToLocalString(slotProps.data.tglajukan || slotProps.data.created_at) }}</span>
            </template>
          </Column>

          <Column field="keteranganstatus" header="Keterangan Status" style="min-width:260px"></Column>

          <Column header="Aksi" frozen alignFrozen="right" style="min-width:220px">
            <template #body="slotProps">
              <div class="is-flex" style="gap:.35rem">
                <VIconButton color="info" icon="feather:eye" circle raised v-tooltip.bubble="'Detail'"
                  @click="openDetail(slotProps.data)" />

                <VIconButton v-if="Number(slotProps.data.statussuratjalan) === 1" color="success" icon="feather:check"
                  circle raised v-tooltip.bubble="'Setujui'" @click="setujuiSuratJalan(slotProps.data)" />

                <VIconButton v-if="Number(slotProps.data.statussuratjalan) === 1" color="danger" icon="feather:x" circle
                  raised v-tooltip.bubble="'Tolak'" @click="openTolak(slotProps.data)" />

                <VIconButton v-if="Number(slotProps.data.statussuratjalan) === 2" color="primary" icon="feather:printer"
                  circle raised v-tooltip.bubble="'Cetak Surat Jalan'" @click="cetakSuratJalan(slotProps.data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </VCard>
  </div>

  <VModal :open="modalDetail" title="Detail Surat Jalan" size="big" actions="right" cancelLabel="Tutup"
    @close="modalDetail = false">
    <template #content>
      <div v-if="detailSurat.head">
        <div class="sj-detail-head">
          <div>
            <h3>{{ detailSurat.head.nosuratjalan }}</h3>
            <p>{{ detailSurat.head.diberikankepada }}</p>
          </div>
          <VTag :color="detailSurat.head.color" rounded>
            {{ detailSurat.head.status }}
          </VTag>
        </div>

        <div class="columns is-multiline mt-3">
          <div class="column is-4">
            <b>No Pendaftaran</b>
            <p>{{ detailSurat.head.nopendaftaran || '-' }}</p>
          </div>
          <div class="column is-4">
            <b>Tujuan</b>
            <p>{{ detailSurat.head.tujuan || '-' }}</p>
          </div>
          <div class="column is-4">
            <b>Yang Membawa</b>
            <p>{{ detailSurat.head.namapegawaibawa || '-' }}</p>
          </div>
          <div class="column is-4">
            <b>Kendaraan</b>
            <p>{{ detailSurat.head.kendaraan || '-' }}</p>
          </div>
          <div class="column is-4">
            <b>Nomor Polisi</b>
            <p>{{ detailSurat.head.nomorpolisi || '-' }}</p>
          </div>
          <div class="column is-4">
            <b>Pengemudi</b>
            <p>{{ detailSurat.head.pengemudi || '-' }}</p>
          </div>
          <div class="column is-4">
            <b>Diajukan Oleh</b>
            <p>{{ detailSurat.head.namapetugas || '-' }}</p>
          </div>
          <div class="column is-4">
            <b>Keterangan Status</b>
            <p>{{ detailSurat.head.keteranganstatus || '-' }}</p>
          </div>
        </div>

        <DataTable :value="detailSurat.detail" class="p-datatable-sm mt-3" scrollable responsiveLayout="stack"
          breakpoint="960px" showGridlines>
          <Column field="nourut" header="No" style="min-width:70px"></Column>
          <Column field="namabarang" header="Nama Barang" style="min-width:220px"></Column>
          <Column field="merk_tipe" header="Merk/Tipe" style="min-width:220px">
            <template #body="slotProps">
              <span>{{ slotProps.data.namamerk || '-' }} / {{ slotProps.data.namatipe || '-' }}</span>
            </template>
          </Column>
          <Column field="namaserialnumber" header="S/N" style="min-width:180px"></Column>
          <Column field="jumlah" header="Jumlah" style="min-width:120px">
            <template #body="slotProps">
              <span>{{ slotProps.data.jumlah }} {{ slotProps.data.satuan }}</span>
            </template>
          </Column>
          <Column field="ket" header="Ket" style="min-width:160px"></Column>
          <Column header="Foto" style="min-width:250px">
            <template #body="slotProps">
              <div class="sj-photo-list mini">
                <div v-for="foto in slotProps.data.foto" :key="foto.norec" class="sj-photo"
                  @click="previewImage(getImageUrl(foto.filepath))">
                  <img :src="getImageUrl(foto.filepath)" alt="Foto" />
                </div>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </template>

    <template #action>
      <VButton v-if="detailSurat.head && Number(detailSurat.head.statussuratjalan) === 1" color="success"
        icon="feather:check" raised :loading="isSaving" @click="setujuiSuratJalan(detailSurat.head)">
        Setujui
      </VButton>

      <VButton v-if="detailSurat.head && Number(detailSurat.head.statussuratjalan) === 1" color="danger"
        icon="feather:x" raised :loading="isSaving" @click="openTolak(detailSurat.head)">
        Tolak
      </VButton>
    </template>
  </VModal>

  <VModal :open="modalTolak" title="Tolak Surat Jalan" size="medium" actions="right" cancelLabel="Batal"
    @close="closeTolak()">
    <template #content>
      <div v-if="selectedRow" class="notification is-danger is-light">
        <b>{{ selectedRow.nosuratjalan }}</b>
        <br />
        {{ selectedRow.diberikankepada }}
      </div>

      <VField>
        <VLabel class="required-field">Alasan Penolakan</VLabel>
        <VControl>
          <textarea v-model="formTolak.keteranganstatus" class="textarea" rows="5"
            placeholder="Tulis alasan penolakan surat jalan..."></textarea>
        </VControl>
      </VField>
    </template>

    <template #action>
      <VButton color="danger" icon="feather:x" :loading="isSaving" raised @click="tolakSuratJalan()">
        Tolak Surat Jalan
      </VButton>
    </template>
  </VModal>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import * as H from '/@src/utils/appHelper'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { resolvePublicFileUrl } from '/@src/utils/publicFileUrl'

useHead({ title: 'Persetujuan Surat Jalan - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const userLogin = useUserSession().getUser()

const isLoading = ref(false)
const isPlaceLoad = ref(false)
const isSaving = ref(false)

const modalDetail = ref(false)
const modalTolak = ref(false)

const dataSource: any = ref([])
const selectedRow: any = ref(null)
const confirmDialog = useConfirm()
const item: any = reactive({
  search: '',
  status: 'all',
  filterTgl: {
    start: new Date(new Date().getFullYear(), 0, 1),
    end: new Date()
  }
})

const formTolak: any = reactive({
  keteranganstatus: ''
})

const detailSurat: any = reactive({
  head: null,
  detail: []
})

const getNamaPegawaiLogin = () => {
  return userLogin?.pegawai?.namaLengkap
    || userLogin?.pegawai?.namalengkap
    || userLogin?.namaLengkap
    || userLogin?.name
    || ''
}

const getIdPegawaiLogin = () => {
  return userLogin?.pegawai?.id || userLogin?.id || ''
}

const statusColor = (status: any) => {
  const s = Number(status)
  if (s === 2) return 'success'
  if (s === 3) return 'danger'
  return 'warning'
}

const statusText = (status: any) => {
  const s = Number(status)
  if (s === 2) return 'Disetujui Asman'
  if (s === 3) return 'Ditolak Asman'
  return 'Diajukan'
}

const getImageUrl = (path: any) => {
  if (!path) return '/images/other/no_image.jpg'
  return resolvePublicFileUrl(path, 'produk')
}

const previewImage = (url: string) => {
  if (url) window.open(url, '_blank')
}

const fetchData = async () => {
  let dari = ''
  let sampai = ''

  if (item.filterTgl.start) {
    dari = H.formatDate(item.filterTgl.start, 'YYYY-MM-DD 00:00')
  }

  if (item.filterTgl.end) {
    sampai = H.formatDate(item.filterTgl.end, 'YYYY-MM-DD 23:59')
  }

  const search = item.search ? `&search=${encodeURIComponent(item.search)}` : ''
  const status = item.status ? `&status=${item.status}` : ''

  isLoading.value = true
  isPlaceLoad.value = true

  try {
    const res = await useApi().get(
      `asman/get-surat-jalan?dari=${dari}&sampai=${sampai}${status}${search}`
    )

    const list = Array.isArray(res) ? res : []
    list.forEach((x: any, i: number) => {
      x.no = i + 1
      x.status = x.status || statusText(x.statussuratjalan)
      x.color = x.color || statusColor(x.statussuratjalan)
    })

    dataSource.value = list
  } catch (e) {
    console.error(e)
    dataSource.value = []
  } finally {
    isLoading.value = false
    isPlaceLoad.value = false
  }
}

const openDetail = async (row: any) => {
  detailSurat.head = null
  detailSurat.detail = []
  modalDetail.value = true

  try {
    const res = await useApi().get(`asman/detail-surat-jalan?norec=${row.norec}`)

    detailSurat.head = res?.head ?? null
    detailSurat.detail = res?.detail ?? []
  } catch (e) {
    console.error(e)
    detailSurat.head = null
    detailSurat.detail = []
  }
}

const setujuiSuratJalan = async (row: any) => {
  if (!row?.norec) {
    H.alert('warning', 'Data surat jalan tidak valid.')
    return
  }

  confirmDialog.require({
    message: `Setujui surat jalan ${row.nosuratjalan}?`,
    header: 'Konfirmasi Persetujuan',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Setujui',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-success',
    rejectClass: 'p-button-secondary p-button-outlined',
    accept: async () => {
      isSaving.value = true

      try {
        const res = await useApi().post('asman/save-setujui-surat-jalan', {
          norec: row.norec,
          asmanfk: getIdPegawaiLogin(),
          namaasman: getNamaPegawaiLogin()
        })

        H.alert('success', res?.message || 'Surat jalan berhasil disetujui.')

        modalDetail.value = false
        await fetchData()
      } catch (e: any) {
        console.error(e)
        H.alert('error', e?.response?.data?.message || 'Gagal menyetujui surat jalan.')
      } finally {
        isSaving.value = false
      }
    }
  })
}

const openTolak = (row: any) => {
  selectedRow.value = row
  formTolak.keteranganstatus = ''
  modalTolak.value = true
}

const closeTolak = () => {
  modalTolak.value = false
  selectedRow.value = null
  formTolak.keteranganstatus = ''
}

const tolakSuratJalan = async () => {
  if (!selectedRow.value?.norec) {
    H.alert('warning', 'Data surat jalan tidak valid.')
    return
  }

  if (!formTolak.keteranganstatus || String(formTolak.keteranganstatus).trim() === '') {
    H.alert('warning', 'Alasan penolakan wajib diisi.')
    return
  }

  isSaving.value = true

  try {
    const res = await useApi().post('asman/save-tolak-surat-jalan', {
      norec: selectedRow.value.norec,
      keteranganstatus: formTolak.keteranganstatus,
      asmanfk: getIdPegawaiLogin(),
      namaasman: getNamaPegawaiLogin()
    })

    H.alert('success', res?.message || 'Surat jalan berhasil ditolak.')

    closeTolak()
    modalDetail.value = false
    await fetchData()
  } catch (e: any) {
    console.error(e)
    H.alert('error', e?.response?.data?.message || 'Gagal menolak surat jalan.')
  } finally {
    isSaving.value = false
  }
}

const cetakSuratJalan = (row: any) => {
  if (!row?.norec) {
    H.alert('warning', 'Data surat jalan tidak valid.')
    return
  }

  H.printBlade(`registrasi/cetak-surat-jalan?pdf=true&norec=${row.norec}`)
}

fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/customer.scss';

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
  background: linear-gradient(135deg, rgba(16, 185, 129, .08), rgba(59, 130, 246, .08));
  border: 1px solid rgba(0, 0, 0, .06);
}

.kp-hero-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.kp-hero-logo {
  height: 56px;
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, .08));
}

.kp-hero-text {
  line-height: 1.2;
}

.kp-hero-title {
  margin: 0;
  font-weight: 700;
  font-size: 1.35rem;
  letter-spacing: .2px;
}

.kp-hero-sub {
  margin: .2rem 0 0;
  font-size: .95rem;
  color: #6b7280;
}

.sj-detail-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 14px;
  background: #fafafa;
}

.sj-detail-head h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: #111827;
}

.sj-detail-head p {
  margin-top: 4px;
  color: #6b7280;
}

.sj-photo-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.sj-photo-list.mini {
  gap: 6px;
}

.sj-photo {
  position: relative;
  width: 88px;
  height: 88px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
  overflow: hidden;
  cursor: pointer;
}

.sj-photo-list.mini .sj-photo {
  width: 48px;
  height: 48px;
}

.sj-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

@media (max-width: 768px) {
  .kp-hero {
    flex-direction: column;
    align-items: flex-start;
    padding: 16px;
    gap: .75rem;
  }

  .kp-hero-logo {
    height: 48px;
  }
}
</style>
