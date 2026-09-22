<template>
  <div class="synergy-pagi-wrap">

    <VCard class="mb-5 pagi-header-card">
      <div class="synergy-header">
        <div class="header-left">
          <h3 class="title is-4 mb-1">Synergy</h3>
          <p class="subtitle is-6 has-text-grey mt-1">
            Monitoring progress per unit
          </p>
        </div>

        <div class="synergy-actions">
          <div class="field mr-2 mb-0" style="width: 200px;">
            <VControl icon="feather:search">
              <input v-model="searchKeyword" class="input" placeholder="Cari Unit..." @keyup.enter="loadUnits" />
            </VControl>
          </div>

          <div class="field mr-2 mb-0">
            <VControl>
              <div class="select">
                <select v-model="sortBy" @change="loadUnits">
                  <option value="total">Urut: Total Terbanyak</option>
                  <option value="selesai">Urut: Selesai Terbanyak</option>
                  <option value="belum">Urut: Belum Terbanyak</option>
                </select>
              </div>
            </VControl>
          </div>

          <div class="year-picker-wrapper mr-2">
            <Calendar v-model="year" view="year" dateFormat="yy" placeholder="Tahun" inputClass="p-inputtext-lg"
              style="width: 100px;" />
          </div>

          <div class="field export-scope-field mb-0">
            <label class="label is-small mb-1">Cakupan Excel</label>
            <VControl>
              <div class="select">
                <select v-model="exportScope" :disabled="exporting">
                  <option value="current">
                    Sesuai Tab ({{ activeTab === 'jakarta' ? 'Jakarta' : 'Gresik' }})
                  </option>
                  <option value="all">Keseluruhan (Jakarta + Gresik)</option>
                </select>
              </div>
            </VControl>
          </div>

          <div class="buttons is-flex-wrap-nowrap mt-5">
            <VButton color="danger" icon="feather:file-text" class="mr-2" :loading="exportingPdf" @click="exportPdf">
              PDF
            </VButton>
            <VButton color="success" icon="feather:download" class="mr-2" :loading="exporting" @click="exportExcel">
              Excel
            </VButton>
          </div>

          <VButton icon="feather:refresh-cw" color="info" :loading="loading" @click="loadUnits">
            Refresh
          </VButton>
        </div>
      </div>
    </VCard>

    <div class="global-notes-section mb-4">
      <div class="card">
        <header class="card-header">
          <p class="card-header-title">
            <span class="icon mr-2 has-text-warning"><i class="iconify" data-icon="feather:alert-circle"></i></span>
            Catatan Utama: {{ activeTab === 'jakarta' ? 'Jakarta' : 'Gresik' }}
          </p>
          <a class="card-header-icon" aria-label="more options">
            <span class="tag is-info is-light">{{ globalNotes.length }} Catatan</span>
          </a>
        </header>
        <div class="card-content">
          <div class="columns">
            <div class="column is-4">
              <div class="field">
                <label class="label is-small">
                  {{ editingGlobalNorec ? 'Edit Catatan' : 'Tulis Info / Pengumuman' }}
                </label>
                <div class="control">
                  <textarea class="textarea is-small" rows="8" placeholder="Tulis catatan utama untuk lokasi ini..."
                    v-model="newGlobalNote"></textarea>
                </div>
              </div>
              <div class="field has-text-right">
                <VButton v-if="editingGlobalNorec" color="light" class="mr-2" @click="cancelEditGlobalNote">
                  Batal
                </VButton>
                <VButton color="primary" :loading="sendingGlobalNote" @click="saveGlobalNote">
                  {{ editingGlobalNorec ? 'Update' : 'Kirim' }}
                </VButton>
              </div>
            </div>

            <div class="column is-8">
              <div class="global-notes-list">
                <div v-if="loadingGlobalNotes" class="has-text-centered p-4">
                  <i class="iconify fa-spin" data-icon="feather:loader"></i>
                </div>
                <div v-else-if="globalNotes.length === 0" class="has-text-centered has-text-grey p-4">
                  <small>Belum ada catatan utama untuk {{ activeTab }}.</small>
                </div>
                <div v-else v-for="gNote in globalNotes" :key="gNote.norec" class="global-note-item">
                  <div class="is-flex is-justify-content-space-between mb-1">
                    <strong>{{ gNote.nama_petugas }}</strong>
                    <div class="is-flex is-align-items-center">

                      <small class="has-text-grey mr-3 has-text-right" style="line-height: 1.2;">
                        <div>{{ gNote.formatted_date }}</div>
                        <div v-if="isEdited(gNote)" class="has-text-grey-light is-size-7 is-italic">
                          (Diedit: {{ formatDate(gNote.updated_at) }})
                        </div>
                      </small>

                      <a class="has-text-info is-clickable mr-2" @click="editGlobalNote(gNote)" title="Edit Catatan">
                        <i class="iconify" data-icon="feather:edit-2"></i>
                      </a>

                      <a class="has-text-danger is-clickable" @click="deleteGlobalNote(gNote.norec)"
                        title="Hapus Catatan">
                        <i class="iconify" data-icon="feather:trash-2"></i>
                      </a>
                    </div>
                  </div>
                  <p>{{ gNote.notes }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tabs-wrapper">
      <VTabs slider align="centered" v-model:selected="activeTab"
        :tabs="[{ label: 'Jakarta', value: 'jakarta' }, { label: 'Gresik', value: 'gresik' }]">
        <template #tab="{ activeValue }">
          <div class="synergy-tab-body">

            <section v-if="!loading" class="synergy-summary-section mt-4" aria-label="Statistik Synergy">
              <div class="synergy-summary-grid">
                <article v-for="card in summaryCards" :key="card.key" class="synergy-summary-card" role="button"
                  tabindex="0" :data-tone="card.tone" @click="openSummaryDrilldown(card)"
                  @keydown.enter="openSummaryDrilldown(card)">
                  <div class="summary-card-heading">
                    <span class="summary-card-icon">
                      <i class="iconify" :data-icon="card.icon"></i>
                    </span>
                    <i class="iconify summary-card-arrow" data-icon="feather:arrow-up-right"></i>
                  </div>
                  <strong>{{ formatNumber(card.value) }}</strong>
                  <span>{{ card.label }}</span>
                  <small>{{ card.caption }}</small>
                </article>
              </div>
            </section>

            <div v-if="loading" class="columns is-multiline mt-2">
              <div v-for="i in 3" :key="i" class="column is-12-mobile is-6-tablet is-4-desktop">
                <div class="pagi-skeleton-card" />
              </div>
            </div>

            <div v-else class="columns mt-2">

              <div class="column is-12-mobile is-8-desktop">
                <div class="columns is-multiline">

                <div v-if="units.length === 0" class="column is-12">
                  <div class="placeholder-nodata">
                    <h3>Tidak ada data ditemukan</h3>
                  </div>
                </div>

                <div v-else v-for="u in units" :key="u.mitra_id" class="column is-12-mobile is-6-tablet">
                  <div class="card pagi-unit-card">
                    <div class="card-image">
                      <div class="chart-wrapper">
                        <!-- CHART UTAMA (TOTAL) -->
                        <apexchart height="100%" width="100%" :type="'bar'" :options="getChartOptions(u)"
                          :series="getChartSeries(u)" />
                      </div>
                    </div>

                    <div class="card-content">
                      <div class="media-flex">
                        <div class="media-text">
                          <h3 class="title is-5 is-bold mb-2 text-truncate" :title="u.namaperusahaan">
                            {{ u.namaperusahaan }}
                          </h3>
                          <p class="subtitle is-7 has-text-dark mt-2">
                            Progress: <span class="has-text-primary is-bold">{{ calcPercent(u) }}%</span>
                          </p>

                          <!-- TAMPILKAN JUMLAH SURKES & NON -->
                          <div class="is-flex is-align-items-center mt-2">
                            <span class="tag is-success is-light mr-2">
                              Surkes: <strong class="ml-1">{{ u.chartTotal?.surkes?.jumlah ?? 0 }}</strong>
                            </span>
                            <span class="tag is-warning">
                              Non Surkes: <strong class="ml-1">{{ u.chartTotal?.non_surkes?.jumlah ?? 0 }}</strong>
                            </span>
                          </div>

                        </div>
                      </div>

                      <div v-if="u.chartLingkup && u.chartLingkup.length > 0" class="scope-chart-section">
                        <hr class="dropdown-divider my-3">
                        <p class="is-size-7 has-text-weight-bold has-text-grey mb-2">Sebaran Lingkup</p>
                        <div class="scope-chart-wrapper">
                          <apexchart type="bar" height="150" :options="getScopeOptions(u)"
                            :series="getScopeSeries(u)" />
                        </div>
                      </div>

                      <div class="content-body">
                        <div class="level is-mobile has-text-centered is-stats">
                          <div class="level-item">
                            <div>
                              <p class="heading">Total</p>
                              <p class="title is-6">{{ u.chartTotal?.jumlah ?? 0 }}</p>
                            </div>
                          </div>
                          <div class="level-item">
                            <div>
                              <p class="heading">Selesai</p>
                              <p class="title is-6 has-text-success">{{ u.chartTotal?.jumlahselesai ?? 0 }}</p>
                            </div>
                          </div>
                          <div class="level-item">
                            <div>
                              <p class="heading">Belum</p>
                              <p class="title is-6 has-text-warning">{{ u.chartTotal?.jumlahbelumselesai ?? 0 }}</p>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="card-footer-action is-flex is-justify-content-end">
                        <a class="action-link mr-4" tabindex="0" @click="goToDetail(u)">
                          Detail
                          <i class="iconify" data-icon="feather:arrow-right"></i>
                        </a>
                        <a class="action-link" tabindex="0" @click="openNotesModal(u)">
                          Catatan
                          <div class="icon-with-badge ml-1">
                            <i class="iconify" data-icon="feather:message-square"></i>
                            <span v-if="u.total_notes && u.total_notes > 0" class="badge-bubble bounce-in">
                              {{ u.total_notes > 9 ? '9+' : u.total_notes }}
                            </span>
                          </div>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>

                </div>
              </div>

              <div class="column is-12-mobile is-4-desktop">
                <div class="sticky-column">
                  <div class="card">
                    <header class="card-header">
                      <p class="card-header-title">
                        <span class="icon is-small mr-2 text-primary">
                          <i class="iconify" data-icon="feather:bar-chart-2"></i>
                        </span>
                        Total Order Per Unit
                      </p>
                    </header>
                    <div class="card-content pa-0">
                      <div class="chart-scroll-container">
                        <apexchart v-if="units.length > 0" type="bar" :height="dynamicChartHeight"
                          :options="summaryChartOptions" :series="summaryChartSeries" />
                        <div v-else class="has-text-centered has-text-grey py-5">
                          Data belum tersedia
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </template>
      </VTabs>
    </div>

    <div :class="['modal', { 'is-active': isModalOpen }]">
      <div class="modal-background" @click="closeModal"></div>
      <div class="modal-card">
        <header class="modal-card-head">
          <p class="modal-card-title">
            Catatan: {{ selectedUnit?.namaperusahaan }}
          </p>
          <button class="delete" aria-label="close" @click="closeModal"></button>
        </header>

        <section class="modal-card-body">

          <div class="notification is-light mb-4 py-2">
            <div class="level is-mobile">
              <div class="level-item has-text-centered">
                <div>
                  <p class="heading mb-0">Total</p>
                  <span class="title is-5">{{ selectedUnit?.chartTotal?.jumlah ?? 0 }}</span>
                </div>
              </div>
              <div class="level-item has-text-centered">
                <div>
                  <p class="heading mb-0">Selesai</p>
                  <span class="title is-5 has-text-success">{{ selectedUnit?.chartTotal?.jumlahselesai ?? 0 }}</span>
                </div>
              </div>
              <div class="level-item has-text-centered">
                <div>
                  <p class="heading mb-0">Belum</p>
                  <span class="title is-5 has-text-warning">{{ selectedUnit?.chartTotal?.jumlahbelumselesai ?? 0
                  }}</span>
                </div>
              </div>
            </div>

            <!-- TAMBAHAN SURKES & NON DI MODAL (TIDAK MENGGANGGU YANG LAMA) -->
            <div class="is-flex is-justify-content-center mt-2">
              <span class="tag is-info is-light mr-2">
                Surkes: <strong class="ml-1">{{ selectedUnit?.chartTotal?.surkes?.jumlah ?? 0 }}</strong>
              </span>
              <span class="tag is-dark is-light">
                Non: <strong class="ml-1">{{ selectedUnit?.chartTotal?.non_surkes?.jumlah ?? 0 }}</strong>
              </span>
            </div>
          </div>

          <div class="field">
            <label class="label">
              {{ editingUnitNorec ? 'Edit Catatan Ini' : 'Catatan Baru' }}
            </label>
            <div class="control">
              <textarea class="textarea" rows="2" placeholder="Tulis kendala atau update status..."
                v-model="newNote"></textarea>
            </div>
          </div>
          <div class="field has-text-right">
            <VButton v-if="editingUnitNorec" color="light" class="mr-2" @click="cancelEditUnitNote">
              Batal
            </VButton>
            <VButton color="primary" :loading="sendingNote" @click="saveNote">
              {{ editingUnitNorec ? 'Update' : 'Kirim Catatan' }}
            </VButton>
          </div>

          <hr class="dropdown-divider my-4">

          <h4 class="title is-6 mb-3">Riwayat Catatan</h4>

          <div v-if="isLoadingNotes" class="has-text-centered py-4">
            <i class="iconify is-size-4 fa-spin" data-icon="feather:loader"></i>
            <p>Memuat catatan...</p>
          </div>

          <div v-else-if="unitNotes.length === 0" class="has-text-centered has-text-grey my-5">
            <i class="iconify is-size-3 mb-2" data-icon="feather:clipboard"></i>
            <p>Belum ada catatan.</p>
          </div>

          <div v-else class="timeline-wrapper">
            <div v-for="note in unitNotes" :key="note.id" class="media note-item">
              <figure class="media-left">
                <span class="icon is-medium has-text-info">
                  <i class="iconify" data-icon="feather:message-circle"></i>
                </span>
              </figure>
              <div class="media-content">
                <div class="content">
                  <p>
                    <strong>{{ note.nama_petugas || note.created_by }}</strong>
                    <small class="has-text-grey ml-2">
                      {{ note.formatted_date }}
                      <span v-if="isEdited(note)" class="has-text-grey-light is-italic ml-1">
                        (Diedit: {{ formatDate(note.updated_at) }})
                      </span>
                    </small>
                    <br>
                    <span style="white-space: pre-wrap;">{{ note.note || note.notes }}</span>
                  </p>

                  <div class="is-flex">
                    <a class="has-text-info is-clickable mr-3" @click="editUnitNote(note)" title="Edit Catatan">
                      <i class="iconify" data-icon="feather:edit-2"></i>
                    </a>
                    <a class="has-text-danger is-clickable" @click="deleteUnitNote(note.norec)" title="Hapus Catatan">
                      <i class="iconify" data-icon="feather:trash-2"></i>
                    </a>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <VModal :title="drilldownTitle" :open="drilldownOpen" size="big" actions="right"
      @close="closeSummaryDrilldown">
      <template #content>
        <div class="drilldown-summary-bar">
          <div>
            <span>Data ditemukan</span>
            <strong>{{ formatNumber(filteredDrilldownRows.length) }} alat</strong>
          </div>
          <div>
            <span>Lokasi dan tahun</span>
            <strong>{{ activeTab === 'jakarta' ? 'Jakarta' : 'Gresik' }} · {{ getYearString() }}</strong>
          </div>
          <VField class="drilldown-search">
            <VControl icon="feather:search">
              <VInput v-model="drilldownKeyword" placeholder="Cari order, pendaftaran, alat, SN, atau unit..." />
            </VControl>
          </VField>
        </div>

        <p class="drilldown-subtitle">{{ drilldownSubtitle }}</p>

        <DataTable :value="filteredDrilldownRows" :paginator="true" :rows="8"
          :rowsPerPageOptions="[8, 15, 30, 50]" class="p-datatable-sm synergy-drilldown-table"
          responsiveLayout="scroll" stripedRows
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Menampilkan {first} - {last} dari {totalRecords}">
          <Column field="nopendaftaran" header="No. Pendaftaran" :sortable="true" style="min-width: 150px">
            <template #body="{ data: row }">
              <strong>{{ row.nopendaftaran || '-' }}</strong>
              <small class="table-subtext">{{ formatRegistrationDate(row.tglregistrasi) }}</small>
            </template>
          </Column>
          <Column field="noorderalat" header="No. Order Alat" :sortable="true" style="min-width: 155px" />
          <Column field="namaproduk" header="Alat" :sortable="true" style="min-width: 230px">
            <template #body="{ data: row }">
              <span class="table-main-text">{{ row.namaproduk || '-' }}</span>
              <small class="table-subtext">SN: {{ row.namaserialnumber || '-' }}</small>
            </template>
          </Column>
          <Column field="namaperusahaan" header="Unit Pemilik" :sortable="true" style="min-width: 240px" />
          <Column field="lingkup" header="Lingkup" :sortable="true" style="min-width: 130px" />
          <Column header="Surkes" style="min-width: 115px">
            <template #body="{ data: row }">
              <span :class="['tag', Number(row.jenissurkesfk) === 1 ? 'is-success is-light' : 'is-warning is-light']">
                {{ Number(row.jenissurkesfk) === 1 ? 'SURKES' : 'NON SURKES' }}
              </span>
            </template>
          </Column>
          <Column header="Pengerjaan" style="min-width: 125px">
            <template #body="{ data: row }">
              <span :class="['tag', toBool(row.selesai) ? 'is-success is-light' : 'is-warning is-light']">
                {{ toBool(row.selesai) ? 'Selesai' : 'Belum Selesai' }}
              </span>
            </template>
          </Column>
          <Column header="Pengambilan" style="min-width: 130px">
            <template #body="{ data: row }">
              <span :class="[
                'tag pickup-status-tag',
                toBool(row.isterima) ? 'is-picked-up' : 'is-waiting-pickup',
              ]">
                {{ toBool(row.isterima) ? 'Sudah Diambil' : 'Belum Diambil' }}
              </span>
            </template>
          </Column>
          <template #empty>
            <div class="has-text-centered has-text-grey py-5">
              Tidak ada order pada statistik ini.
            </div>
          </template>
        </DataTable>
      </template>
      <template #cancel></template>
      <template #action>
        <VButton color="dark" outlined @click="closeSummaryDrilldown">Tutup</VButton>
      </template>
    </VModal>

  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import Calendar from 'primevue/calendar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { useApi } from '/@src/composable/useApi'
import * as H from '/@src/utils/appHelper'
import { buildSynergyExcel, type SynergyExcelLocation } from '/@src/utils/synergyExcel'
import { useViewWrapper } from '/@src/stores/viewWrapper';
import { useHead } from '@vueuse/head';
import { useRouter } from 'vue-router'

useHead({
  title: 'Synergy - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const apexchart = VueApexCharts

type ChartTotal = {
  jumlah: number
  jumlahselesai: number
  jumlahbelumselesai: number
  jumlahdiambil: number
  jumlahbelumdiambil: number
  surkes?: { jumlah: number; selesai: number; belum: number }
  non_surkes?: { jumlah: number; selesai: number; belum: number }
}

type ScopeRow = {
  lingkup: string
  total_jumlah: number
  total_selesai: number
  total_belum: number
  surkes_jumlah: number
  surkes_selesai: number
  surkes_belum: number
  non_jumlah: number
  non_selesai: number
  non_belum: number
}

type UnitRow = {
  mitra_id: number
  namaperusahaan: string
  total_notes?: number
  chartTotal?: ChartTotal
  chartLingkup?: ScopeRow[]
  details?: SynergyDetailRow[]
}

type SynergyDetailRow = {
  detail_norec: string
  mitra_id: number
  namaperusahaan: string
  nopendaftaran: string
  noorderalat: string
  namaproduk: string
  namaserialnumber: string
  lingkup: string
  jenisorder: string
  tglregistrasi: string
  jenissurkesfk: number | string | null
  selesai: boolean | number | string | null
  isterima: boolean | number | string | null
}

type SummaryCard = {
  key: 'total' | 'surkes' | 'non_surkes' | 'selesai' | 'belum' | 'diambil' | 'belum_diambil'
  label: string
  value: number
  caption: string
  icon: string
  tone: string
}

const router = useRouter()
const loading = ref(false)
const exporting = ref(false)
const exportingPdf = ref(false)
const year = ref<Date>(new Date())
const units = ref<UnitRow[]>([])
const activeTab = ref('jakarta')
const exportScope = ref<'current' | 'all'>('current')
const searchKeyword = ref('')
const sortBy = ref('total')
let searchTimeout: any = null
const isModalOpen = ref(false)
const selectedUnit = ref<any>(null)
const unitNotes = ref<any[]>([])
const isLoadingNotes = ref(false)
const newNote = ref('')
const sendingNote = ref(false)
const editingUnitNorec = ref<string | null>(null) // State untuk edit unit note

const globalNotes = ref<any[]>([])
const newGlobalNote = ref('')
const loadingGlobalNotes = ref(false)
const sendingGlobalNote = ref(false)
const editingGlobalNorec = ref<string | null>(null) // State untuk edit global note
const drilldownOpen = ref(false)
const drilldownTitle = ref('')
const drilldownSubtitle = ref('')
const drilldownRows = ref<SynergyDetailRow[]>([])
const drilldownKeyword = ref('')
const getYearString = () => String(year.value.getFullYear())

function toBool(value: unknown) {
  if (typeof value === 'boolean') return value
  if (typeof value === 'number') return value === 1
  return ['1', 'true', 't', 'yes', 'y'].includes(String(value ?? '').trim().toLowerCase())
}

function formatNumber(value: unknown) {
  return new Intl.NumberFormat('id-ID').format(Number(value || 0))
}

function formatRegistrationDate(value: string) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const synergyDetails = computed<SynergyDetailRow[]>(() => units.value.flatMap((unit) => (
  (unit.details || []).map((row) => ({
    ...row,
    mitra_id: row.mitra_id ?? unit.mitra_id,
    namaperusahaan: row.namaperusahaan || unit.namaperusahaan,
  }))
)))

const summaryCards = computed<SummaryCard[]>(() => {
  const rows = synergyDetails.value
  const count = (predicate: (row: SynergyDetailRow) => boolean) => rows.filter(predicate).length

  return [
    {
      key: 'total',
      label: 'Total Alat',
      value: rows.length,
      caption: 'Seluruh alat pada lokasi dan tahun aktif',
      icon: 'feather:package',
      tone: 'navy',
    },
    {
      key: 'surkes',
      label: 'Pendaftaran SURKES',
      value: count((row) => Number(row.jenissurkesfk) === 1),
      caption: 'Klik untuk melihat order SURKES',
      icon: 'feather:shield',
      tone: 'teal',
    },
    {
      key: 'non_surkes',
      label: 'Pendaftaran Non SURKES',
      value: count((row) => Number(row.jenissurkesfk) !== 1),
      caption: 'Klik untuk melihat order Non SURKES',
      icon: 'feather:file-minus',
      tone: 'purple',
    },
    {
      key: 'selesai',
      label: 'Selesai',
      value: count((row) => toBool(row.selesai)),
      caption: 'Pekerjaan sudah disetujui Manager',
      icon: 'feather:check-circle',
      tone: 'green',
    },
    {
      key: 'belum',
      label: 'Belum Selesai',
      value: count((row) => !toBool(row.selesai)),
      caption: 'Masih dalam proses pengerjaan',
      icon: 'feather:clock',
      tone: 'amber',
    },
    {
      key: 'diambil',
      label: 'Sudah Diambil',
      value: count((row) => toBool(row.isterima)),
      caption: 'Sudah tercatat pada tanda selesai terima',
      icon: 'feather:log-out',
      tone: 'blue',
    },
    {
      key: 'belum_diambil',
      label: 'Belum Diambil',
      value: count((row) => !toBool(row.isterima)),
      caption: 'Belum tercatat pada tanda selesai terima',
      icon: 'feather:archive',
      tone: 'red',
    },
  ]
})

const filteredDrilldownRows = computed(() => {
  const keyword = drilldownKeyword.value.trim().toLowerCase()
  if (!keyword) return drilldownRows.value

  return drilldownRows.value.filter((row) => [
    row.nopendaftaran,
    row.noorderalat,
    row.namaproduk,
    row.namaserialnumber,
    row.namaperusahaan,
    row.lingkup,
  ].some((value) => String(value || '').toLowerCase().includes(keyword)))
})

function openSummaryDrilldown(card: SummaryCard) {
  const filters: Record<SummaryCard['key'], (row: SynergyDetailRow) => boolean> = {
    total: () => true,
    surkes: (row) => Number(row.jenissurkesfk) === 1,
    non_surkes: (row) => Number(row.jenissurkesfk) !== 1,
    selesai: (row) => toBool(row.selesai),
    belum: (row) => !toBool(row.selesai),
    diambil: (row) => toBool(row.isterima),
    belum_diambil: (row) => !toBool(row.isterima),
  }

  drilldownRows.value = synergyDetails.value.filter(filters[card.key])
  drilldownKeyword.value = ''
  drilldownTitle.value = `${card.label} · ${activeTab.value === 'jakarta' ? 'Jakarta' : 'Gresik'}`
  drilldownSubtitle.value = `${formatNumber(drilldownRows.value.length)} alat pembentuk statistik ${card.label.toLowerCase()} tahun ${getYearString()}, lengkap dengan nomor order dan unit pemilik.`
  drilldownOpen.value = true
}

function closeSummaryDrilldown() {
  drilldownOpen.value = false
  drilldownRows.value = []
  drilldownKeyword.value = ''
}

function calcPercent(u: UnitRow) {
  const total = u.chartTotal?.jumlah ?? 0
  const done = u.chartTotal?.jumlahselesai ?? 0
  if (!total) return 0
  return Math.round((done / total) * 100)
}

watch([year, activeTab], () => {
  units.value = []
  cancelEditGlobalNote()
  loadUnits()
  loadGlobalNotes()
})

watch(searchKeyword, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadUnits(), 500)
})

// --- DATE HELPER ---
function isEdited(item: any) {
  if (item.created_at && item.updated_at) {
    return item.created_at !== item.updated_at
  }
  return false
}

function formatDate(dateStr: string) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: '2-digit' }) +
    ' ' +
    d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}
// -------------------

async function loadUnits() {
  loading.value = true
  try {
    const lokasiId = activeTab.value === 'gresik' ? 2 : 1
    const params = new URLSearchParams({
      year: getYearString(),
      lokasi_id: String(lokasiId),
      keyword: searchKeyword.value,
      sort_by: sortBy.value
    })
    const res = await useApi().get(`laporan/get-isi-synergy-pagi?${params.toString()}`)
    units.value = (res.data ?? []) as UnitRow[]
  } catch (err: any) {
    H.alert('error', 'Gagal memuat data')
  } finally {
    loading.value = false
  }
}

async function loadGlobalNotes() {
  loadingGlobalNotes.value = true
  try {
    const lokasiId = activeTab.value === 'gresik' ? 2 : 1
    const tahun = getYearString()
    const res = await useApi().get(`laporan/get-global-notes?lokasi_id=${lokasiId}&tahun=${tahun}`)
    globalNotes.value = Array.isArray(res) ? res : (res || [])

  } catch (err) {
    console.error('Failed load global notes', err)
    globalNotes.value = []
  } finally {
    loadingGlobalNotes.value = false
  }
}

// --- GLOBAL NOTE ACTION ---
function editGlobalNote(note: any) {
  newGlobalNote.value = note.notes
  editingGlobalNorec.value = note.norec
}

function cancelEditGlobalNote() {
  newGlobalNote.value = ''
  editingGlobalNorec.value = null
}

async function saveGlobalNote() {
  if (!newGlobalNote.value.trim()) {
    H.alert('warning', 'Catatan utama tidak boleh kosong')
    return
  }
  sendingGlobalNote.value = true
  try {
    const lokasiId = activeTab.value === 'gresik' ? 2 : 1
    const payload: any = {
      lokasi_id: lokasiId,
      notes: newGlobalNote.value,
      year: getYearString(),
    }
    // Logic update
    if (editingGlobalNorec.value) {
      payload.norec = editingGlobalNorec.value
    }

    await useApi().post('laporan/save-global-note', payload)

    cancelEditGlobalNote()
    await loadGlobalNotes()
  } catch (err: any) {
    H.alert('error', 'Gagal simpan catatan utama')
  } finally {
    sendingGlobalNote.value = false
  }
}

async function deleteGlobalNote(norec: string) {
  try {
    if (editingGlobalNorec.value === norec) {
      cancelEditGlobalNote()
    }
    await useApi().post('laporan/delete-global-note', { norec })
    await loadGlobalNotes()
  } catch (err) {
    H.alert('error', 'Gagal menghapus catatan')
  }
}

// --- UNIT NOTE ACTION ---
async function deleteUnitNote(norec: string) {
  try {
    if (editingUnitNorec.value === norec) {
      cancelEditUnitNote()
    }
    await useApi().post('laporan/delete-unit-note', { norec })
    await loadNotes(selectedUnit.value.mitra_id)
    const targetItem = units.value.find(i => i.mitra_id === selectedUnit.value.mitra_id)
    if (targetItem) {
      const currentCount = targetItem.total_notes || 0
      targetItem.total_notes = currentCount - 1
    }
  } catch (err) {
    H.alert('error', 'Gagal menghapus catatan')
  }
}

// --- HELPER FUNCTION EXPORT ---
async function fetchAllUnitNotes(sourceUnits: UnitRow[] = units.value) {
  const tahun = getYearString()
  const unitsWithNotes = Array.from(new Map(
    sourceUnits
      .filter(u => (u.total_notes || 0) > 0)
      .map(unit => [unit.mitra_id, unit]),
  ).values())
  const notesMap = new Map()

  if (unitsWithNotes.length > 0) {
    const promises = unitsWithNotes.map(async (u) => {
      try {
        const res = await useApi().get(`laporan/get-notes-unit?unitfk=${u.mitra_id}&tahun=${tahun}`)
        if (Array.isArray(res) && res.length > 0) {
          notesMap.set(u.mitra_id, res)
        }
      } catch (e) {
        console.error(`Gagal ambil note untuk unit ${u.mitra_id}`, e)
      }
    })
    await Promise.all(promises)
  }
  return notesMap
}

function formatNoteString(unitId: number, notesMap: Map<any, any>) {
  if (notesMap.has(unitId)) {
    const notesArr = notesMap.get(unitId)
    return notesArr.map((n: any) => {
      return `[${n.formatted_date}] ${n.nama_petugas || 'Admin'}: ${n.note || n.notes}`
    }).join('\n')
  }
  return '-'
}

// --- EXPORT PDF FUNCTION ---
const exportPdf = async () => {
  exportingPdf.value = true
  try {
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
    const tahun = getYearString()
    const notesMap = await fetchAllUnitNotes()

    // Header Info
    doc.setFontSize(16)
    doc.text(`Laporan Synergy Pagi - ${activeTab.value === 'jakarta' ? 'Jakarta' : 'Gresik'}`, 14, 15)
    doc.setFontSize(10)
    doc.text(`Tahun: ${tahun}`, 14, 21)
    doc.text(`Tanggal Export: ${new Date().toLocaleDateString('id-ID')}`, 14, 26)

    // ==========================================
    // TABEL 1: REKAP PROGRESS (Updated Columns)
    // ==========================================
    const head = [['No', 'Perusahaan', 'Total', 'Surkes', 'Non-S', 'Selesai', 'Belum', 'Prog', 'Catatan']]

    const body = units.value.map((u, idx) => [
      idx + 1,
      u.namaperusahaan,
      u.chartTotal?.jumlah ?? 0,
      u.chartTotal?.surkes?.jumlah ?? 0,
      u.chartTotal?.non_surkes?.jumlah ?? 0,
      u.chartTotal?.jumlahselesai ?? 0,
      u.chartTotal?.jumlahbelumselesai ?? 0,
      `${calcPercent(u)}%`,
      formatNoteString(u.mitra_id, notesMap)
    ])

    autoTable(doc, {
      head: head,
      body: body,
      startY: 32,
      styles: { fontSize: 8, cellPadding: 2 },
      headStyles: {
        fillColor: [66, 133, 244],
        halign: 'center',
        valign: 'middle'
      },
      columnStyles: {
        0: { cellWidth: 10, halign: 'center' }, // No
        1: { cellWidth: 50 }, // Perusahaan
        2: { cellWidth: 15, halign: 'center', fontStyle: 'bold' }, // Total
        3: { cellWidth: 15, halign: 'center' }, // Surkes
        4: { cellWidth: 15, halign: 'center' }, // Non-S
        5: { cellWidth: 15, halign: 'center' }, // Selesai
        6: { cellWidth: 15, halign: 'center' }, // Belum
        7: { cellWidth: 15, halign: 'center' }, // Prog
        8: { cellWidth: 'auto' } // Catatan
      },
      margin: { top: 30 }
    })

    // ==========================================
    // TABEL 2: DETAIL LINGKUP (Updated Columns)
    // ==========================================
    doc.addPage()
    doc.setFontSize(14)
    doc.text("Detail Lingkup Pekerjaan", 14, 15)

    const scopeBody: any[] = []

    // Grand Totals Accumulators
    let grandTotal = 0
    let grandSurkes = 0
    let grandNonSurkes = 0
    let grandSelesai = 0
    let grandBelum = 0

    units.value.forEach(u => {
      if (u.chartLingkup && u.chartLingkup.length > 0) {
        u.chartLingkup.forEach((scope: any, index) => {
          // Mapping data from backend response
          const jml = parseInt(scope.total_jumlah ?? 0)
          const surkes = parseInt(scope.surkes_jumlah ?? 0)
          const non = parseInt(scope.non_jumlah ?? 0)
          const sel = parseInt(scope.total_selesai ?? 0)
          const bel = parseInt(scope.total_belum ?? 0)

          // Akumulasi Grand Total
          grandTotal += jml
          grandSurkes += surkes
          grandNonSurkes += non
          grandSelesai += sel
          grandBelum += bel

          const row: any[] = []

          // Logic Merge Row untuk Nama Perusahaan
          if (index === 0) {
            row.push({
              content: u.namaperusahaan,
              rowSpan: u.chartLingkup?.length ?? 1,
              styles: { valign: 'middle', fontStyle: 'bold' }
            })
          }

          row.push(scope.lingkup)
          row.push(jml)
          row.push(surkes)
          row.push(non)
          row.push(sel)
          row.push(bel)

          scopeBody.push(row)
        })
      }
    })

    // Tambahkan Baris Grand Total di Bawah
    scopeBody.push([
      {
        content: 'TOTAL KESELURUHAN',
        colSpan: 2,
        styles: { fontStyle: 'bold', halign: 'right', fillColor: [230, 230, 230] }
      },
      { content: grandTotal, styles: { fontStyle: 'bold', halign: 'center', fillColor: [230, 230, 230] } },
      { content: grandSurkes, styles: { fontStyle: 'bold', halign: 'center', fillColor: [230, 230, 230] } },
      { content: grandNonSurkes, styles: { fontStyle: 'bold', halign: 'center', fillColor: [230, 230, 230] } },
      { content: grandSelesai, styles: { fontStyle: 'bold', halign: 'center', fillColor: [230, 230, 230] } },
      { content: grandBelum, styles: { fontStyle: 'bold', halign: 'center', fillColor: [230, 230, 230] } }
    ])

    autoTable(doc, {
      head: [['Perusahaan', 'Lingkup', 'Total', 'Surkes', 'Non-S', 'Selesai', 'Belum']],
      body: scopeBody,
      startY: 20,
      styles: { fontSize: 8, cellPadding: 2 },
      headStyles: {
        fillColor: [46, 204, 113], // Green
        halign: 'center',
        valign: 'middle'
      },
      columnStyles: {
        0: { cellWidth: 70 }, // Perusahaan
        2: { cellWidth: 20, halign: 'center' }, // Total
        3: { cellWidth: 20, halign: 'center' }, // Surkes
        4: { cellWidth: 20, halign: 'center' }, // Non
        5: { cellWidth: 20, halign: 'center' }, // Selesai
        6: { cellWidth: 20, halign: 'center' }  // Belum
      },
      theme: 'grid'
    })

    // ==========================================
    // TABEL 3: CATATAN UTAMA
    // ==========================================
    if (globalNotes.value.length > 0) {
      doc.addPage()
      doc.setFontSize(14)
      doc.text("Catatan Utama (Global Notes)", 14, 15)

      const globalBody = globalNotes.value.map((n: any) => [
        n.formatted_date,
        n.nama_petugas,
        n.notes
      ])

      autoTable(doc, {
        head: [['Tanggal', 'Oleh', 'Isi Catatan']],
        body: globalBody,
        startY: 20,
        styles: { fontSize: 9 },
        headStyles: { fillColor: [241, 196, 15], textColor: [50, 50, 50] },
        columnStyles: { 2: { cellWidth: 120 } }
      })
    }

    const dateStr = new Date().toLocaleDateString('id-ID').replace(/\//g, '-')
    const fileName = `Laporan_Synergy_${activeTab.value}_${dateStr}.pdf`
    doc.save(fileName)

  } catch (err) {
    console.error('Export PDF Error', err)
    H.alert('error', 'Gagal export PDF')
  } finally {
    exportingPdf.value = false
  }
}

// --- EXPORT EXCEL FUNCTION ---
const exportLocations = [
  { key: 'jakarta' as const, label: 'Jakarta', lokasiId: 1 },
  { key: 'gresik' as const, label: 'Gresik', lokasiId: 2 },
]

function normalizeApiArray<T>(response: any): T[] {
  if (Array.isArray(response)) return response as T[]
  if (Array.isArray(response?.data)) return response.data as T[]
  return []
}

async function fetchUnitsForExcel(lokasiId: number) {
  const params = new URLSearchParams({
    year: getYearString(),
    lokasi_id: String(lokasiId),
    keyword: searchKeyword.value,
    sort_by: sortBy.value,
  })
  const response = await useApi().get(`laporan/get-isi-synergy-pagi?${params.toString()}`)
  return normalizeApiArray<UnitRow>(response)
}

async function fetchGlobalNotesForExcel(lokasiId: number) {
  const response = await useApi().get(
    `laporan/get-global-notes?lokasi_id=${lokasiId}&tahun=${getYearString()}`,
  )
  return normalizeApiArray<any>(response)
}

function getExportDateStamp(date: Date) {
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  return `${day}-${month}-${date.getFullYear()}`
}

const exportExcel = async () => {
  exporting.value = true
  try {
    const selectedLocations = exportScope.value === 'all'
      ? exportLocations
      : exportLocations.filter(location => location.key === activeTab.value)

    const locations: SynergyExcelLocation[] = await Promise.all(
      selectedLocations.map(async (location) => {
        const isCurrentLocation = location.key === activeTab.value
        const [locationUnits, locationNotes] = await Promise.all([
          exportScope.value === 'current' && isCurrentLocation
            ? Promise.resolve(units.value)
            : fetchUnitsForExcel(location.lokasiId),
          exportScope.value === 'current' && isCurrentLocation
            ? Promise.resolve(globalNotes.value)
            : fetchGlobalNotesForExcel(location.lokasiId),
        ])

        return {
          key: location.key,
          label: location.label,
          units: locationUnits,
          globalNotes: locationNotes,
        }
      }),
    )

    const allUnits = locations.flatMap(location => location.units) as UnitRow[]
    const notesMap = await fetchAllUnitNotes(allUnits)
    const generatedAt = new Date()
    const excelBytes = buildSynergyExcel({
      year: getYearString(),
      generatedAt,
      searchKeyword: searchKeyword.value,
      locations,
      notesMap,
    })

    const scopeName = exportScope.value === 'all'
      ? 'Jakarta_Gresik'
      : selectedLocations[0].label
    const fileName = `Laporan_Synergy_${scopeName}_${getExportDateStamp(generatedAt)}`
    saveAsExcelFile(excelBytes, fileName)

  } catch (err) {
    console.error('Export Error', err)
    H.alert('error', 'Gagal melakukan export excel')
  } finally {
    exporting.value = false
  }
}

const saveAsExcelFile = (buffer: Uint8Array, fileName: string) => {
  const EXCEL_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8'
  const EXCEL_EXTENSION = '.xlsx'

  const data: Blob = new Blob([buffer], {
    type: EXCEL_TYPE
  })
  const url = window.URL.createObjectURL(data)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', fileName + EXCEL_EXTENSION)

  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

const goToDetail = (e: any) => {
  router.push({
    name: 'module-laporan-synergy-detail',
    query: {
      id_unit: e.mitra_id,
      year: getYearString(),
    },
  })
}

function openNotesModal(unit: UnitRow) {
  selectedUnit.value = unit
  newNote.value = ''
  unitNotes.value = []
  isModalOpen.value = true
  cancelEditUnitNote()
  loadNotes(unit.mitra_id)
}

function closeModal() {
  isModalOpen.value = false
  selectedUnit.value = null
  unitNotes.value = []
  cancelEditUnitNote()
}

async function loadNotes(mitraId: any) {
  isLoadingNotes.value = true
  try {
    const tahun = getYearString()
    const res = await useApi().get(`laporan/get-notes-unit?unitfk=${mitraId}&tahun=${tahun}`)
    unitNotes.value = Array.isArray(res) ? res : (res || [])
  } catch (error) {
    console.error("Gagal load notes", error)
  } finally {
    isLoadingNotes.value = false
  }
}

// --- UNIT NOTE EDIT/SAVE LOGIC ---
function editUnitNote(note: any) {
  newNote.value = note.note || note.notes
  editingUnitNorec.value = note.norec
}

function cancelEditUnitNote() {
  newNote.value = ''
  editingUnitNorec.value = null
}

async function saveNote() {
  if (!newNote.value.trim()) {
    H.alert('error', 'Catatan tidak boleh kosong')
    return
  }
  sendingNote.value = true
  try {
    const payload: any = {
      unitfk: selectedUnit.value.mitra_id,
      notes: newNote.value,
      year: getYearString()
    }
    if (editingUnitNorec.value) {
      payload.norec = editingUnitNorec.value
    }

    await useApi().post('laporan/save-notes-per-unit', payload)

    cancelEditUnitNote()
    await loadNotes(selectedUnit.value.mitra_id)

    if (!payload.norec) {
      const targetItem = units.value.find(i => i.mitra_id === selectedUnit.value.mitra_id)
      if (targetItem) {
        const currentCount = targetItem.total_notes || 0
        targetItem.total_notes = currentCount + 1
      }
    }
  } catch (err: any) {
    console.error(err)
  } finally {
    sendingNote.value = false
  }
}

function getChartSeries(u: UnitRow) {
  if (!u) return []
  const done = u.chartTotal?.jumlahselesai ?? 0
  const pending = u.chartTotal?.jumlahbelumselesai ?? 0
  const pickedUp = u.chartTotal?.jumlahdiambil ?? 0
  const waitingPickup = u.chartTotal?.jumlahbelumdiambil ?? 0
  return [{ name: 'Jumlah', data: [done, pending, pickedUp, waitingPickup] }]
}

function getChartOptions(u: UnitRow) {
  return {
    chart: { type: 'bar', toolbar: { show: false }, sparkline: { enabled: false }, parentHeightOffset: 0 },
    plotOptions: { bar: { borderRadius: 6, horizontal: true, barHeight: '58%', distributed: true } },
    colors: ['#10b981', '#f59e0b', '#2563eb', '#ef4444'],
    dataLabels: { enabled: true, formatter: (val: any) => val > 0 ? val : '', offsetX: 0, style: { colors: ['#fff'] } },
    grid: { show: false, padding: { top: 0, right: 25, bottom: 0, left: 10 } },
    xaxis: {
      categories: ['Selesai', 'Belum', 'Sudah Diambil', 'Belum Diambil'],
      labels: { show: false },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: { labels: { show: true, style: { colors: '#fff', fontSize: '13px', fontWeight: 700 }, offsetX: 5 } },
    tooltip: { theme: 'light', y: { formatter: (val: any) => val + " Alat" } },
    legend: { show: false }
  }
}

function getScopeSeries(u: UnitRow) {
  if (!u.chartLingkup || u.chartLingkup.length === 0) return []
  const data = u.chartLingkup.map(i => (i.total_jumlah ?? 0))
  return [{
    name: 'Total Alat',
    data: data
  }]
}

function getScopeOptions(u: UnitRow) {
  const categories = u.chartLingkup?.map(i => i.lingkup) || []
  return {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: {
      bar: {
        borderRadius: 4,
        horizontal: true,
        barHeight: '60%',
        distributed: true
      }
    },
    colors: ['#00cfdd', '#797bf2', '#f59e0b', '#10b981', '#ef4444'],
    dataLabels: {
      enabled: true,
      textAnchor: 'start',
      style: { colors: ['#fff'], fontSize: '10px' },
      formatter: function (val: number) { return val },
      offsetX: 0
    },
    xaxis: {
      categories: categories,
      labels: { show: false },
      axisBorder: { show: false },
      axisTicks: { show: false }
    },
    yaxis: {
      labels: {
        show: true,
        style: { fontSize: '10px', colors: ['#666'] },
        maxWidth: 100
      }
    },
    grid: {
      show: false,
      padding: { top: 0, right: 0, bottom: 0, left: 10 }
    },
    legend: { show: false },
    tooltip: {
      y: { formatter: (val: any) => val + " Alat" }
    }
  }
}

const dynamicChartHeight = computed(() => {
  const itemCount = units.value.length
  const calculatedHeight = 50 + (itemCount * 30)
  return Math.max(calculatedHeight, 400)
})

const summaryChartSeries = computed(() => {
  const data = units.value.map(u => u.chartTotal?.jumlah ?? 0)
  return [{
    name: 'Total Order',
    data: data
  }]
})

const summaryChartOptions = computed(() => {
  const categories = units.value.map(u => u.namaperusahaan)
  return {
    chart: {
      type: 'bar',
      toolbar: { show: false }
    },
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 4,
        barHeight: '70%',
        distributed: false
      }
    },
    colors: ['#3b82f6'],
    dataLabels: {
      enabled: true,
      textAnchor: 'start',
      style: {
        colors: ['#fff']
      },
      formatter: function (val: any) {
        return val
      },
      offsetX: 0,
    },
    xaxis: {
      categories: categories,
      labels: {
        show: true
      }
    },
    yaxis: {
      labels: {
        show: true,
        style: {
          fontSize: '11px',
          fontFamily: 'Roboto, sans-serif',
        },
        maxWidth: 200
      }
    },
    grid: {
      xaxis: {
        lines: { show: true }
      },
      yaxis: {
        lines: { show: false }
      },
    },
    tooltip: {
      theme: 'light',
      y: {
        formatter: function (val: any) {
          return val + " Alat"
        }
      }
    }
  }
})

onMounted(() => {
  loadUnits()
  loadGlobalNotes()
})
</script>

<style scoped lang="scss">
.synergy-pagi-wrap {
  padding: 12px;
}

.synergy-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-left {
  flex: 1;
  min-width: 200px;
}

.synergy-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.export-scope-field {
  min-width: 245px;
}

.export-scope-field .select,
.export-scope-field select {
  width: 100%;
}

.synergy-summary-section {
  width: 100%;
}

.synergy-summary-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  margin-bottom: 8px;
}

.synergy-summary-card {
  --summary-accent: #3b82f6;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-top: 3px solid var(--summary-accent);
  border-radius: 12px;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
  cursor: pointer;
  min-height: 132px;
  min-width: 0;
  padding: 14px;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;

  &[data-tone='navy'] {
    --summary-accent: #283e59;
  }

  &[data-tone='teal'] {
    --summary-accent: #10b981;
  }

  &[data-tone='purple'] {
    --summary-accent: #8b5cf6;
  }

  &[data-tone='green'] {
    --summary-accent: #22c55e;
  }

  &[data-tone='amber'] {
    --summary-accent: #f59e0b;
  }

  &[data-tone='blue'] {
    --summary-accent: #0ea5e9;
  }

  &[data-tone='red'] {
    --summary-accent: #ef4444;
  }

  &:hover,
  &:focus-visible {
    border-color: color-mix(in srgb, var(--summary-accent) 50%, #e5e7eb);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.1);
    outline: none;
    transform: translateY(-2px);
  }

  strong,
  span,
  small {
    display: block;
  }

  > strong {
    color: #1f2937;
    font-size: 1.45rem;
    line-height: 1.2;
    margin: 10px 0 4px;
  }

  > span {
    color: #374151;
    font-size: 0.76rem;
    font-weight: 700;
    line-height: 1.25;
  }

  > small {
    color: #9ca3af;
    font-size: 0.66rem;
    line-height: 1.3;
    margin-top: 4px;
  }
}

.summary-card-heading {
  align-items: center;
  display: flex;
  justify-content: space-between;
}

.summary-card-icon {
  align-items: center;
  background: color-mix(in srgb, var(--summary-accent) 12%, #fff);
  border-radius: 9px;
  color: var(--summary-accent);
  display: flex !important;
  height: 32px;
  justify-content: center;
  width: 32px;
}

.summary-card-arrow {
  color: #cbd5e1;
  font-size: 0.9rem;
}

.select select,
.input,
:deep(.p-calendar .p-inputtext) {
  height: 40px;
  border-color: #dbdbdb;
}

.pagi-unit-card {
  border: 1px solid #e1e4e8;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  transition: all 0.3s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);

  &:hover {
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
    transform: translateY(-4px);
  }

  .card-image {
    height: 230px;
    background: linear-gradient(135deg, #283e59 0%, #1e2f44 100%);
    position: relative;
  }

  .chart-wrapper {
    padding: 12px 12px 0 12px;
    position: absolute;
    inset: 0;
  }

  .card-content {
    padding: 16px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
  }

  .content-body {
    margin-top: auto;
    margin-bottom: 16px;
    padding: 8px 0;
    border-top: 1px solid #f0f2f5;
    border-bottom: 1px solid #f0f2f5;
  }

  .card-footer-action {
    text-align: right;

    .action-link {
      font-weight: 700;
      color: #00cfdd;
      cursor: pointer;
      display: inline-flex;
      gap: 6px;
      font-size: 0.9rem;

      &:hover {
        color: #009ba6;
      }
    }
  }
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.drilldown-summary-bar {
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  display: grid;
  gap: 16px;
  grid-template-columns: minmax(140px, 0.7fr) minmax(180px, 0.9fr) minmax(260px, 1.6fr);
  margin-bottom: 12px;
  padding: 14px;

  span,
  strong {
    display: block;
  }

  span {
    color: #94a3b8;
    font-size: 0.7rem;
  }

  strong {
    color: #1f2937;
    font-size: 0.9rem;
    margin-top: 3px;
  }
}

.drilldown-search {
  margin: 0;
}

.drilldown-subtitle {
  color: #64748b;
  font-size: 0.8rem;
  margin-bottom: 14px;
}

.table-main-text,
.table-subtext {
  display: block;
}

.table-main-text {
  color: #1f2937;
  font-weight: 600;
}

.table-subtext {
  color: #94a3b8;
  font-size: 0.7rem;
  margin-top: 2px;
}

.pickup-status-tag {
  border: 0;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
  color: #fff !important;
  font-weight: 800;

  &.is-picked-up {
    background: #2563eb !important;
  }

  &.is-waiting-pickup {
    background: #ef4444 !important;
  }
}

@media (max-width: 1200px) {
  .synergy-summary-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .synergy-summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .drilldown-summary-bar {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .drilldown-search {
    grid-column: 1 / -1;
  }
}

@media (max-width: 520px) {
  .synergy-pagi-wrap {
    padding: 6px;
  }

  .synergy-header {
    padding: 14px;
  }

  .synergy-actions,
  .synergy-actions .field,
  .synergy-actions .year-picker-wrapper {
    width: 100% !important;
  }

  .synergy-summary-grid,
  .drilldown-summary-bar {
    grid-template-columns: 1fr;
  }

  .drilldown-search {
    grid-column: auto;
  }
}

.sticky-column {
  @media (min-width: 1024px) {
    position: sticky;
    top: 90px;
    z-index: 5;
    max-height: calc(100vh - 100px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
}

.chart-scroll-container {
  max-height: calc(100vh - 160px);
  overflow-y: auto;
  padding: 10px 10px 10px 0;

  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-thumb {
    background-color: #dbdbdb;
    border-radius: 4px;
  }

  &::-webkit-scrollbar-track {
    background: #f1f1f1;
  }
}

.global-notes-list {
  max-height: 200px;
  overflow-y: auto;
  padding-right: 10px;
}

.global-note-item {
  padding: 12px;
  border-bottom: 1px solid #eee;
  background: #f9f9f9;
  border-radius: 6px;
  margin-bottom: 8px;

  strong {
    font-size: 0.9rem;
    color: #283e59;
  }

  small {
    font-size: 0.8rem;
  }

  p {
    font-size: 0.95rem;
    color: #1a1a1a;
    line-height: 1.4;
    margin-top: 4px;
    white-space: pre-wrap;
  }
}

.placeholder-nodata {
  text-align: center;
  padding: 40px;

  h3 {
    font-weight: 600;
    color: #283e59;
  }
}

.pagi-skeleton-card {
  height: 300px;
  background: linear-gradient(90deg, #f0f2f5, #e6e8eb, #f0f2f5);
  background-size: 200%;
  border-radius: 12px;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }

  100% {
    background-position: -200% 0;
  }
}

.is-bold {
  font-weight: 700 !important;
}

.modal-card {
  width: 600px;
  max-width: 95%;
  border-radius: 12px;
}

.timeline-wrapper {
  max-height: 300px;
  overflow-y: auto;
  padding-right: 5px;
}

.note-item {
  border-bottom: 1px dashed #eee;
  padding-bottom: 10px;
  margin-bottom: 10px;

  &:last-child {
    border-bottom: none;
  }
}

.icon-with-badge {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.badge-bubble {
  position: absolute;
  top: -8px;
  right: -10px;
  background-color: #ef4444;
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #fff;
  box-shadow: 0 2px 5px rgba(239, 68, 68, 0.4);
  z-index: 10;
}

.bounce-in {
  animation: bounceIn 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes bounceIn {
  0% {
    transform: scale(0);
  }

  80% {
    transform: scale(1.1);
  }

  100% {
    transform: scale(1);
  }
}
</style>
