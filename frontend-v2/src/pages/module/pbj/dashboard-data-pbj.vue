<template>
  <div class="page-content-inner pbj-dashboard" data-testid="pbj-dashboard-page">
    <VCard class="dashboard-header">
      <div class="dashboard-heading">
        <div>
          <div class="heading-kicker">Pengendalian anggaran PBJ</div>
          <h1>Dashboard Data PBJ</h1>
          <p>
            Statistik PBJ yang telah disetujui Manager, estimasi biaya, pemakaian pagu, dan posisi proses tahun
            {{ selectedYear }}.
          </p>
        </div>

        <div class="header-actions">
          <VButton color="info" outlined icon="feather:refresh-cw" :loading="loading" @click="loadDashboard">
            Perbarui
          </VButton>
          <VButton color="success" icon="fas fa-file-excel" :disabled="!recordsForExport.length"
            @click="exportExcel">
            Excel {{ activeScopeLabel }}
          </VButton>
          <VButton color="danger" icon="fas fa-file-pdf" :disabled="!recordsForExport.length" @click="exportPdf">
            PDF {{ activeScopeLabel }}
          </VButton>
        </div>
      </div>

      <div class="dashboard-controls">
        <label class="year-control">
          <span>Tahun data</span>
          <select v-model.number="selectedYear" class="input" data-testid="pbj-year-filter">
            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </label>

        <div class="scope-switch" aria-label="Filter wilayah pengadaan" data-testid="pbj-scope-filter">
          <button v-for="scopeItem in scopeOptions" :key="scopeItem.key" type="button"
            :class="{ 'is-active': activeScope === scopeItem.key }" @click="activeScope = scopeItem.key">
            <i class="iconify" :data-icon="scopeItem.icon"></i>
            <span>{{ scopeItem.label }}</span>
          </button>
        </div>

        <div class="updated-at">
          <i class="iconify" data-icon="feather:clock"></i>
          <span>Diperbarui {{ formatDateTime(data.generated_at) }}</span>
        </div>
      </div>
    </VCard>

    <div v-if="loading" class="loading-grid">
      <VPlaceload v-for="item in 8" :key="item" class="dashboard-skeleton" />
    </div>

    <VPlaceholderPage v-else-if="!data.year" title="Data dashboard PBJ belum tersedia" larger>
      <template #image>
        <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
        <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
      </template>
    </VPlaceholderPage>

    <template v-else>
      <section class="dashboard-section special-section" data-testid="pbj-special-section">
        <div class="special-summary-grid">
          <article class="metric-card is-main is-drilldown" role="button" tabindex="0"
            @click="openSpecialDrilldown('Seluruh PBJ PRK khusus')"
            @keydown.enter="openSpecialDrilldown('Seluruh PBJ PRK khusus')">
            <div class="metric-icon"><i class="iconify" data-icon="feather:credit-card"></i></div>
            <div>
              <span>Total estimasi biaya PRK khusus</span>
              <strong>{{ formatRupiah(specialScopeSummary.total_estimasi) }}</strong>
              <small>
                Akumulasi biaya {{ formatNumber(specialScopeSummary.jumlah_pbj) }} PBJ {{ activeScopeLabel }} untuk Material ULAB,
                Pemeliharaan Tools, Kalibrasi/Sertifikasi, Jasa ULAB, dan Workshop Center
              </small>
            </div>
          </article>
          <article class="metric-card is-drilldown" role="button" tabindex="0"
            @click="openSpecialDrilldown('Daftar PBJ PRK khusus')"
            @keydown.enter="openSpecialDrilldown('Daftar PBJ PRK khusus')">
            <div class="metric-icon"><i class="iconify" data-icon="feather:file-text"></i></div>
            <div>
              <span>Jumlah pengajuan PBJ PRK khusus</span>
              <strong>{{ formatNumber(specialScopeSummary.jumlah_pbj) }} PBJ</strong>
              <small>
                Dokumen PBJ pada PRK 262N0302, 262N0303, 262N0304, 262N0309, dan 262N0310; memuat
                {{ formatNumber(specialScopeSummary.total_item) }} item barang/jasa di {{ activeScopeLabel }}
              </small>
            </div>
          </article>
          <article class="metric-card is-drilldown" role="button" tabindex="0"
            @click="openSpecialDrilldown('Kontribusi wilayah pengadaan PRK khusus')"
            @keydown.enter="openSpecialDrilldown('Kontribusi wilayah pengadaan PRK khusus')">
            <div class="metric-icon"><i class="iconify" data-icon="feather:map-pin"></i></div>
            <div>
              <span>Kontribusi estimasi per wilayah pengadaan</span>
              <strong>{{ specialLocationCaption }}</strong>
              <small>
                Perbandingan nilai estimasi kelima kelompok PRK dari total
                {{ formatRupiah(specialScopeSummary.total_estimasi) }}
              </small>
            </div>
          </article>
        </div>

        <div class="budget-grid">
          <article v-for="budget in budgetItems" :key="budget.code" class="budget-card"
            :class="[`is-${budget.budget_status || 'safe'}`, 'is-drilldown']" role="button" tabindex="0"
            @click="openSpecialDrilldown(`PBJ PRK ${budget.code} - ${budget.label}`, budget.code)"
            @keydown.enter="openSpecialDrilldown(`PBJ PRK ${budget.code} - ${budget.label}`, budget.code)">
            <div class="budget-head">
              <div>
                <span class="prk-code">PRK {{ budget.code }}</span>
                <h3>{{ budget.label }}</h3>
              </div>
              <span class="budget-percent">{{ formatPercent(budget.persentase_pagu) }}</span>
            </div>

            <div class="budget-values">
              <div>
                <span>Terpakai</span>
                <strong>{{ formatRupiah(budget.total_estimasi) }}</strong>
              </div>
              <div>
                <span>Pagu maksimum</span>
                <strong>{{ formatRupiah(budget.pagu) }}</strong>
              </div>
            </div>

            <div class="budget-track" role="progressbar" :aria-valuenow="Math.min(100, budget.persentase_pagu || 0)"
              aria-valuemin="0" aria-valuemax="100">
              <span :style="{ width: `${Math.min(100, budget.persentase_pagu || 0)}%` }"></span>
            </div>

            <div class="budget-foot">
              <span v-if="budget.melebihi_pagu > 0" class="is-over">
                Melebihi pagu {{ formatRupiah(budget.melebihi_pagu) }}
              </span>
              <span v-else>Sisa {{ formatRupiah(budget.sisa_pagu) }}</span>
              <small>{{ budget.jumlah_pbj }} PBJ · {{ budget.total_item }} item</small>
            </div>
          </article>
        </div>

        <div class="columns is-multiline chart-grid">
          <div class="column is-12-tablet is-7-desktop">
            <VCard class="chart-card">
              <div class="card-heading">
                <div>
                  <h3>Estimasi biaya per PRK khusus</h3>
                  <p>Klik batang untuk daftar PBJ. Tinggi memakai skala log agar nominal kecil tetap mudah dipilih.</p>
                </div>
                <div class="location-legend">
                  <span v-if="activeScope !== 'gresik'"><i class="is-jakarta"></i>Jakarta</span>
                  <span v-if="activeScope !== 'jakarta'"><i class="is-gresik"></i>Gresik</span>
                </div>
              </div>
              <apexchart height="340" type="bar" :options="specialChartOptions" :series="specialChartSeries" />
            </VCard>
          </div>

          <div class="column is-12-tablet is-5-desktop">
            <VCard class="chart-card special-table-card">
              <div class="card-heading">
                <div>
                  <h3>Ringkasan PRK khusus</h3>
                  <p>Total estimasi Jakarta dan Gresik.</p>
                </div>
              </div>
              <div class="table-scroll">
                <table class="business-table">
                  <thead>
                    <tr>
                      <th>PRK</th>
                      <th>PBJ</th>
                      <th v-if="activeScope !== 'gresik'" class="has-text-right">Jakarta</th>
                      <th v-if="activeScope !== 'jakarta'" class="has-text-right">Gresik</th>
                      <th class="has-text-right">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in scopedSpecialItems" :key="item.code" class="is-drilldown" tabindex="0"
                      @click="openSpecialDrilldown(`PBJ PRK ${item.code} - ${item.label}`, item.code)"
                      @keydown.enter="openSpecialDrilldown(`PBJ PRK ${item.code} - ${item.label}`, item.code)">
                      <td><strong>{{ item.code }}</strong><small>{{ item.label }}</small></td>
                      <td>{{ item.jumlah_pbj }}</td>
                      <td v-if="activeScope !== 'gresik'" class="has-text-right">
                        {{ formatRupiahCompact(specialLocationValue(item, 1)) }}
                      </td>
                      <td v-if="activeScope !== 'jakarta'" class="has-text-right">
                        {{ formatRupiahCompact(specialLocationValue(item, 2)) }}
                      </td>
                      <td class="has-text-right"><strong>{{ formatRupiahCompact(item.total_estimasi) }}</strong></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </VCard>
          </div>
        </div>
      </section>

      <section class="dashboard-section general-section" data-testid="pbj-general-section">
        <div class="summary-grid">
          <article v-for="card in summaryCards" :key="card.key" class="summary-card is-drilldown" role="button"
            tabindex="0" @click="openSummaryDrilldown(card)" @keydown.enter="openSummaryDrilldown(card)">
            <div class="summary-card-top">
              <span>{{ card.label }}</span>
              <i class="iconify" :data-icon="card.icon"></i>
            </div>
            <strong>
              {{ card.currency ? formatRupiah(card.value) : `${formatNumber(card.value)} ${card.unit || ''}` }}
            </strong>
            <small>{{ card.caption }}</small>
          </article>
        </div>

        <div class="columns is-multiline chart-grid">
          <div class="column is-12-tablet is-8-desktop">
            <VCard class="chart-card">
              <div class="card-heading">
                <div>
                  <h3>Tren estimasi biaya bulanan</h3>
                  <p>Klik batang untuk daftar PBJ bulan dan wilayah pengadaan tersebut. Tinggi memakai skala log.</p>
                </div>
                <span class="card-total">{{ formatRupiah(activeSummary.total_estimasi) }}</span>
              </div>
              <apexchart height="330" type="bar" :options="monthlyChartOptions" :series="monthlyChartSeries" />
            </VCard>
          </div>

          <div class="column is-12-tablet is-4-desktop">
            <VCard class="chart-card">
              <div class="card-heading">
                <div>
                  <h3>Posisi proses PBJ</h3>
                  <p>Klik bagian chart untuk melihat PBJ pada posisi proses tersebut.</p>
                </div>
              </div>
              <apexchart v-if="statusChartSeries.length" height="330" type="donut" :options="statusChartOptions"
                :series="statusChartSeries" />
              <div v-else class="empty-chart">Belum ada data status</div>
            </VCard>
          </div>

          <div class="column is-12">
            <VCard class="prk-lookup-card">
              <div class="prk-lookup-copy">
                <h3>Cari penggunaan PRK</h3>
                <p>Cari kode PRK lengkap, misalnya 262N0309, untuk melihat PBJ yang menggunakan kode tersebut.</p>
              </div>
              <VField class="prk-lookup-field">
                <VControl icon="feather:search">
                  <VInput v-model="prkLookupKeyword" placeholder="Contoh: 262N0309"
                    @keyup.enter="openPrkLookup" />
                </VControl>
              </VField>
              <div v-if="normalizedPrkLookup" class="prk-lookup-result">
                <span>Ditemukan</span>
                <strong>{{ formatNumber(prkLookupRows.length) }} PBJ</strong>
                <small>Total estimasi {{ formatRupiah(prkLookupTotalEstimate) }}</small>
              </div>
              <VButton color="info" outlined icon="feather:list" :disabled="!normalizedPrkLookup"
                @click="openPrkLookup">
                Lihat daftar PBJ
              </VButton>
            </VCard>
          </div>

          <div class="column is-12">
            <VCard class="chart-card">
              <div class="card-heading">
                <div>
                  <h3>PRK lain dengan estimasi terbesar</h3>
                  <p>Klik batang untuk daftar PBJ. Tinggi memakai skala log agar nominal kecil tetap mudah dipilih.</p>
                </div>
                <span class="card-total">{{ formatNumber(activeScopeData.other_prk.length) }} PRK</span>
              </div>
              <apexchart v-if="topOtherPrk.length" :height="otherPrkChartHeight" type="bar"
                :options="otherPrkChartOptions" :series="otherPrkChartSeries" />
              <div v-else class="empty-chart">Belum ada PRK lain pada filter ini</div>
            </VCard>
          </div>
        </div>

        <VCard class="records-card">
          <div class="records-heading">
            <div>
              <h3>Daftar PBJ PRK Lain - {{ activeScopeLabel }}</h3>
              <p>
                {{ formatNumber(filteredRecords.length) }} PBJ di luar PRK khusus 262N0302, 262N0303, 262N0304,
                262N0309, dan 262N0310
                pada tahun {{ selectedYear }}.
              </p>
            </div>
            <VField class="records-search">
              <VControl icon="feather:search">
                <VInput v-model="keyword" placeholder="Cari surat, PRK, No PR, No PO, atau pengaju..." />
              </VControl>
            </VField>
          </div>

          <DataTable :value="filteredRecords" :paginator="true" :rows="12" :rowsPerPageOptions="[12, 25, 50, 100]"
            class="p-datatable-sm pbj-records-table" responsiveLayout="scroll" stripedRows sortMode="multiple"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Menampilkan {first} - {last} dari {totalRecords}">
            <Column field="nosuratpbj" header="No. Surat" :sortable="true" style="min-width: 150px">
              <template #body="{ data: row }">
                <strong>{{ row.nosuratpbj || 'Draft' }}</strong>
                <small class="table-subtext">{{ formatDate(row.tanggal_acuan) }}</small>
              </template>
            </Column>
            <Column field="judulpermintaan" header="Judul Permintaan" :sortable="true" style="min-width: 280px">
              <template #body="{ data: row }">
                <span class="row-title">{{ row.judulpermintaan || '-' }}</span>
                <small class="table-subtext">{{ row.nama_pengaju || '-' }}</small>
              </template>
            </Column>
            <Column field="prk_key" header="PRK" :sortable="true" style="min-width: 130px">
              <template #body="{ data: row }">
                <span class="prk-pill" :class="{ 'is-special': row.special_prk_code }">
                  {{ row.special_prk_code ? `PRK ${row.special_prk_code}` : row.prk_key }}
                </span>
              </template>
            </Column>
            <Column field="nopr" header="No PR" :sortable="true" style="min-width: 135px">
              <template #body="{ data: row }">{{ row.nopr || '-' }}</template>
            </Column>
            <Column field="wilayah_pengadaan" header="Wilayah Pengadaan" :sortable="true" style="min-width: 170px" />
            <Column field="status_label" header="Posisi" :sortable="true" style="min-width: 170px">
              <template #body="{ data: row }">
                <span class="status-pill" :class="`is-${row.status_key}`">{{ row.status_label }}</span>
              </template>
            </Column>
            <Column field="nopo" header="No PO" :sortable="true" style="min-width: 135px">
              <template #body="{ data: row }">{{ row.nopo || '-' }}</template>
            </Column>
            <Column field="total_item" header="Item" :sortable="true" style="width: 90px" />
            <Column field="total_estimasi" header="Total Estimasi" :sortable="true" style="min-width: 170px">
              <template #body="{ data: row }">
                <strong class="money-cell">{{ formatRupiah(row.total_estimasi) }}</strong>
                <small class="table-subtext">PPN {{ row.ppn_persen }}%</small>
              </template>
            </Column>
          </DataTable>
        </VCard>
      </section>
    </template>

    <VModal :title="drilldownTitle" :open="drilldownOpen" size="big" actions="right"
      @close="closeDrilldown">
      <template #content>
        <div class="drilldown-summary">
          <div>
            <span>PBJ ditemukan</span>
            <strong>{{ formatNumber(drilldownRows.length) }}</strong>
          </div>
          <div>
            <span>Total estimasi</span>
            <strong>{{ formatRupiah(drilldownTotalEstimate) }}</strong>
          </div>
          <div>
            <span>Total nilai PO</span>
            <strong>{{ formatRupiah(drilldownTotalPo) }}</strong>
          </div>
          <VField class="drilldown-search">
            <VControl icon="feather:search">
              <VInput v-model="drilldownKeyword" placeholder="Cari surat, judul, PRK, No PR, No PO, atau pengaju..." />
            </VControl>
          </VField>
        </div>

        <p v-if="drilldownSubtitle" class="drilldown-subtitle">{{ drilldownSubtitle }}</p>

        <DataTable :value="filteredDrilldownRows" :paginator="true" :rows="8"
          :rowsPerPageOptions="[8, 15, 30, 50]" class="p-datatable-sm drilldown-table"
          responsiveLayout="scroll" stripedRows
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Menampilkan {first} - {last} dari {totalRecords}">
          <Column field="nosuratpbj" header="No. Surat" :sortable="true" style="min-width: 145px">
            <template #body="{ data: row }">
              <strong>{{ row.nosuratpbj || 'Draft' }}</strong>
              <small class="table-subtext">{{ formatDate(row.tanggal_acuan) }}</small>
            </template>
          </Column>
          <Column field="judulpermintaan" header="Judul Permintaan" :sortable="true" style="min-width: 260px">
            <template #body="{ data: row }">
              <span class="row-title">{{ row.judulpermintaan || '-' }}</span>
              <small class="table-subtext">{{ row.nama_pengaju || '-' }}</small>
            </template>
          </Column>
          <Column field="prk" header="PRK" :sortable="true" style="min-width: 170px" />
          <Column field="nopr" header="No PR" :sortable="true" style="min-width: 135px">
            <template #body="{ data: row }">{{ row.nopr || '-' }}</template>
          </Column>
          <Column field="wilayah_pengadaan" header="Wilayah Pengadaan" :sortable="true" style="min-width: 165px" />
          <Column field="status_label" header="Posisi" :sortable="true" style="min-width: 150px" />
          <Column field="nopo" header="No PO" :sortable="true" style="min-width: 135px">
            <template #body="{ data: row }">{{ row.nopo || '-' }}</template>
          </Column>
          <Column field="total_estimasi" header="Total Estimasi" :sortable="true" style="min-width: 165px">
            <template #body="{ data: row }">
              <strong class="money-cell">{{ formatRupiah(row.total_estimasi) }}</strong>
            </template>
          </Column>
          <Column field="nilai_po" header="Nilai Terkontrak" :sortable="true" style="min-width: 185px">
            <template #body="{ data: row }">
              <strong class="money-cell">{{ formatRupiah(row.nilai_po) }}</strong>
            </template>
          </Column>
          <Column header="Cetakan PBJ" style="min-width: 135px">
            <template #body="{ data: row }">
              <VButton color="danger" outlined icon="feather:printer" @click.stop="printPbj(row)">
                Cetak PBJ
              </VButton>
            </template>
          </Column>
          <template #empty>
            <div class="empty-drilldown">Tidak ada PBJ pada statistik yang dipilih.</div>
          </template>
        </DataTable>
      </template>
      <template #cancel></template>
      <template #action>
        <VButton color="success" outlined icon="fas fa-file-excel"
          :disabled="!filteredDrilldownRows.length" @click="exportDrilldownExcel">
          Export Excel
        </VButton>
        <VButton color="dark" outlined @click="closeDrilldown">Tutup</VButton>
      </template>
    </VModal>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useHead } from '@vueuse/head'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import VueApexCharts from 'vue3-apexcharts'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { useApi } from '/@src/composable/useApi'
import { useDarkmode } from '/@src/stores/darkmode'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import * as H from '/@src/utils/appHelper'
import {
  addNativeExcelCharts,
  downloadExcelFile,
  type NativeExcelChart,
} from '/@src/utils/excelNativeCharts'

type ScopeKey = 'gabungan' | 'jakarta' | 'gresik'

const apexchart = VueApexCharts
const darkmode = useDarkmode()
const viewWrapper = useViewWrapper()

useHead({ title: 'Dashboard Data PBJ - ' + import.meta.env.VITE_PROJECT })
viewWrapper.setPageTitle('Dashboard Data PBJ')
viewWrapper.setFullWidth(true)

const LOCATION_COLORS = {
  jakarta: '#2563eb',
  gresik: '#0f9f76',
  unassigned: '#d97706',
  combined: '#334155',
}
const STATUS_COLORS = [
  '#94a3b8', '#2563eb', '#4f46e5', '#7c3aed', '#0891b2', '#0f9f76', '#65a30d',
  '#ca8a04', '#ea580c', '#dc2626', '#db2777', '#9333ea', '#475569',
]

const emptySummary = () => ({
  total_pbj: 0,
  submitted: 0,
  draft: 0,
  cancelled: 0,
  total_item: 0,
  total_estimasi: 0,
  rata_rata_estimasi: 0,
  total_prk: 0,
  sudah_po: 0,
  total_nilai_po: 0,
})
const emptyScope = () => ({ summary: emptySummary(), monthly: [], status: [], other_prk: [] })
const emptyData = () => ({
  year: null,
  available_years: [],
  generated_at: null,
  scopes: { gabungan: emptyScope(), jakarta: emptyScope(), gresik: emptyScope() },
  all_data_summary: emptySummary(),
  data_quality: { without_pengadaan: emptySummary() },
  special_prk: {
    rule: 'Kode PRK lengkap: 262N0302, 262N0303, 262N0304, 262N0309, 262N0310',
    summary: { jumlah_pbj: 0, total_item: 0, total_estimasi: 0 },
    items: [],
  },
  records: [],
})

const loading = ref(false)
const selectedYear = ref(new Date().getFullYear())
const activeScope = ref<ScopeKey>('gabungan')
const keyword = ref('')
const prkLookupKeyword = ref('')
const data = ref<any>(emptyData())
const drilldownOpen = ref(false)
const drilldownTitle = ref('Daftar PBJ')
const drilldownSubtitle = ref('')
const drilldownRows = ref<any[]>([])
const drilldownKeyword = ref('')

const scopeOptions = [
  { key: 'gabungan' as ScopeKey, label: 'Gabungan', icon: 'feather:layers' },
  { key: 'jakarta' as ScopeKey, label: 'Jakarta', icon: 'feather:map-pin' },
  { key: 'gresik' as ScopeKey, label: 'Gresik', icon: 'feather:map-pin' },
]

const availableYears = computed(() => {
  const years = (data.value.available_years || []).map((year: any) => Number(year)).filter(Boolean)
  if (!years.includes(selectedYear.value)) years.push(selectedYear.value)
  return years.sort((a: number, b: number) => b - a)
})
const activeScopeLabel = computed(() => scopeOptions.find((item) => item.key === activeScope.value)?.label || 'Gabungan')
const activeScopeData = computed(() => data.value.scopes?.[activeScope.value] || emptyScope())
const activeSummary = computed(() => activeScopeData.value.summary || emptySummary())
const specialPrk = computed(() => data.value.special_prk || emptyData().special_prk)

const summaryCards = computed(() => [
  {
    key: 'total_pbj', label: 'PBJ disetujui Manager', value: activeSummary.value.total_pbj, unit: 'PBJ',
    caption: `Seluruhnya telah melewati persetujuan Manager pada tahun ${selectedYear.value}`,
    icon: 'feather:file-text',
  },
  {
    key: 'total_estimasi', label: 'Estimasi biaya PBJ disetujui Manager', value: activeSummary.value.total_estimasi,
    caption: `Akumulasi ${activeSummary.value.total_pbj} PBJ; rata-rata ${formatRupiahCompact(activeSummary.value.rata_rata_estimasi)} per PBJ`,
    icon: 'feather:credit-card', currency: true,
  },
  {
    key: 'total_item', label: 'Total item barang/jasa', value: activeSummary.value.total_item, unit: 'item',
    caption: `Diajukan melalui ${activeSummary.value.total_pbj} PBJ pada ${activeSummary.value.total_prk} PRK`,
    icon: 'feather:package',
  },
  {
    key: 'sudah_po', label: 'PBJ yang sudah memiliki PO', value: activeSummary.value.sudah_po, unit: 'PBJ',
    caption: `Dari ${activeSummary.value.total_pbj} PBJ; total nilai PO ${formatRupiahCompact(activeSummary.value.total_nilai_po)}`,
    icon: 'feather:check-circle',
  },
])

const recordsByScope = computed(() => {
  const rows = data.value.records || []
  if (activeScope.value === 'jakarta') return rows.filter((row: any) => Number(row.pengadaan_id) === 1)
  if (activeScope.value === 'gresik') return rows.filter((row: any) => Number(row.pengadaan_id) === 2)
  return rows.filter((row: any) => [1, 2].includes(Number(row.pengadaan_id)))
})
const specialRecordsByScope = computed(() => recordsByScope.value.filter((row: any) => Boolean(row.special_prk_code)))
const scopedSpecialItems = computed(() => (specialPrk.value.items || []).map((item: any) => {
  const rows = specialRecordsByScope.value.filter((row: any) => String(row.special_prk_code) === String(item.code))
  const totalEstimasi = rows.reduce((sum: number, row: any) => sum + Number(row.total_estimasi || 0), 0)
  const pagu = Number(item.pagu) > 0 ? Number(item.pagu) : null
  const percentage = pagu ? Math.round((totalEstimasi / pagu) * 10000) / 100 : null

  return {
    ...item,
    jumlah_pbj: rows.length,
    total_item: rows.reduce((sum: number, row: any) => sum + Number(row.total_item || 0), 0),
    total_estimasi: totalEstimasi,
    persentase_pagu: percentage,
    sisa_pagu: pagu !== null ? Math.max(0, pagu - totalEstimasi) : null,
    melebihi_pagu: pagu !== null ? Math.max(0, totalEstimasi - pagu) : 0,
    budget_status: pagu === null
      ? null
      : (Number(percentage) > 100 ? 'over' : (Number(percentage) >= 90 ? 'danger' : (Number(percentage) >= 75 ? 'warning' : 'safe'))),
  }
}))
const specialScopeSummary = computed(() => ({
  jumlah_pbj: specialRecordsByScope.value.length,
  total_item: specialRecordsByScope.value.reduce((sum: number, row: any) => sum + Number(row.total_item || 0), 0),
  total_estimasi: specialRecordsByScope.value.reduce((sum: number, row: any) => sum + Number(row.total_estimasi || 0), 0),
}))
const budgetItems = computed(() => scopedSpecialItems.value.filter((item: any) => Number(item.pagu) > 0))
const specialLocationCaption = computed(() => {
  const jakarta = specialRecordsByScope.value
    .filter((row: any) => Number(row.pengadaan_id) === 1)
    .reduce((sum: number, row: any) => sum + Number(row.total_estimasi || 0), 0)
  const gresik = specialRecordsByScope.value
    .filter((row: any) => Number(row.pengadaan_id) === 2)
    .reduce((sum: number, row: any) => sum + Number(row.total_estimasi || 0), 0)
  const total = jakarta + gresik
  if (!total) return 'Belum ada data'
  return `${Math.round((jakarta / total) * 100)}% Jakarta · ${Math.round((gresik / total) * 100)}% Gresik`
})
const recordsForExport = computed(() => recordsByScope.value)
const otherRecordsByScope = computed(() => recordsByScope.value.filter((row: any) => !row.special_prk_code))
const normalizedPrkLookup = computed(() => prkLookupKeyword.value.trim().toUpperCase().replace(/^PRK[\s:.-]*/, ''))
const prkLookupRows = computed(() => {
  const search = normalizedPrkLookup.value
  if (!search) return []
  return recordsByScope.value.filter((row: any) => [row.prk, row.prk_key, row.special_prk_code]
    .some((value) => String(value || '').toUpperCase().includes(search)))
})
const prkLookupTotalEstimate = computed(() => prkLookupRows.value.reduce(
  (sum: number, row: any) => sum + Number(row.total_estimasi || 0),
  0
))
const filteredRecords = computed(() => {
  const search = keyword.value.trim().toLowerCase()
  if (!search) return otherRecordsByScope.value
  return otherRecordsByScope.value.filter((row: any) => [
    row.nosuratpbj, row.judulpermintaan, row.prk, row.prk_key, row.nopr, row.nopo,
    row.nama_pengaju, row.pemohon, row.status_label,
  ].some((value) => String(value || '').toLowerCase().includes(search)))
})
const drilldownTotalEstimate = computed(() => drilldownRows.value.reduce(
  (sum: number, row: any) => sum + Number(row.total_estimasi || 0),
  0
))
const drilldownTotalPo = computed(() => drilldownRows.value.reduce(
  (sum: number, row: any) => sum + Number(row.nilai_po || 0),
  0
))
const filteredDrilldownRows = computed(() => {
  const search = drilldownKeyword.value.trim().toLowerCase()
  if (!search) return drilldownRows.value

  return drilldownRows.value.filter((row: any) => [
    row.nosuratpbj, row.judulpermintaan, row.prk, row.prk_key, row.nama_pengaju,
    row.pemohon, row.wilayah_pengadaan, row.status_label, row.nopr, row.nopo,
  ].some((value) => String(value || '').toLowerCase().includes(search)))
})

const chartBase = computed(() => ({
  chart: { toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit', animations: { speed: 350 } },
  theme: { mode: darkmode.isDark ? 'dark' : 'light' },
  grid: { borderColor: darkmode.isDark ? '#3f4654' : '#e5e7eb', strokeDashArray: 4 },
  dataLabels: { enabled: false },
  legend: { fontSize: '12px', markers: { radius: 2 } },
  tooltip: { theme: darkmode.isDark ? 'dark' : 'light' },
  noData: { text: 'Belum ada data' },
}))

const specialChartSeries = computed(() => {
  if (activeScope.value === 'jakarta') {
    return [{ name: 'Jakarta', data: scopedSpecialItems.value.map((item: any) => chartDisplayValue(item.total_estimasi)) }]
  }
  if (activeScope.value === 'gresik') {
    return [{ name: 'Gresik', data: scopedSpecialItems.value.map((item: any) => chartDisplayValue(item.total_estimasi)) }]
  }
  return [
    { name: 'Jakarta', data: scopedSpecialItems.value.map((item: any) => chartDisplayValue(item.jakarta?.total_estimasi)) },
    { name: 'Gresik', data: scopedSpecialItems.value.map((item: any) => chartDisplayValue(item.gresik?.total_estimasi)) },
  ]
})
const specialChartOptions = computed(() => ({
  ...chartBase.value,
  chart: {
    ...chartBase.value.chart,
    events: {
      dataPointSelection: (_event: any, _context: any, detail: any) => {
        const item = scopedSpecialItems.value?.[detail.dataPointIndex]
        if (!item) return
        const procurementId = specialSeriesProcurementId(detail.seriesIndex)
        const location = procurementId === 1 ? 'Jakarta' : 'Gresik'
        openSpecialDrilldown(`PBJ PRK ${item.code} - ${location}`, item.code, procurementId)
      },
    },
  },
  colors: activeScope.value === 'gresik'
    ? [LOCATION_COLORS.gresik]
    : activeScope.value === 'jakarta'
      ? [LOCATION_COLORS.jakarta]
      : [LOCATION_COLORS.jakarta, LOCATION_COLORS.gresik],
  plotOptions: { bar: { borderRadius: 3, columnWidth: '58%' } },
  xaxis: { categories: scopedSpecialItems.value.map((item: any) => `PRK ${item.code}`), labels: { style: { fontWeight: 600 } } },
  yaxis: { labels: { formatter: (value: number) => formatRupiahCompact(chartActualValue(value)) } },
  tooltip: {
    ...chartBase.value.tooltip,
    y: {
      formatter: (_value: number, detail: any) => {
        const item = scopedSpecialItems.value?.[detail.dataPointIndex]
        return formatRupiah(specialLocationValue(item, specialSeriesProcurementId(detail.seriesIndex)))
      },
    },
  },
}))

const monthlyChartSeries = computed(() => {
  const jakarta = data.value.scopes?.jakarta?.monthly || []
  const gresik = data.value.scopes?.gresik?.monthly || []
  if (activeScope.value === 'jakarta') return [{ name: 'Jakarta', data: jakarta.map((item: any) => chartDisplayValue(item.total_estimasi)) }]
  if (activeScope.value === 'gresik') return [{ name: 'Gresik', data: gresik.map((item: any) => chartDisplayValue(item.total_estimasi)) }]
  return [
    { name: 'Jakarta', data: jakarta.map((item: any) => chartDisplayValue(item.total_estimasi)) },
    { name: 'Gresik', data: gresik.map((item: any) => chartDisplayValue(item.total_estimasi)) },
  ]
})
const monthlyChartOptions = computed(() => ({
  ...chartBase.value,
  chart: {
    ...chartBase.value.chart,
    events: {
      dataPointSelection: (_event: any, _context: any, detail: any) => {
        openMonthlyDrilldown(detail.dataPointIndex, detail.seriesIndex)
      },
    },
  },
  colors: activeScope.value === 'gresik'
    ? [LOCATION_COLORS.gresik]
    : activeScope.value === 'jakarta'
      ? [LOCATION_COLORS.jakarta]
      : [LOCATION_COLORS.jakarta, LOCATION_COLORS.gresik],
  plotOptions: { bar: { borderRadius: 3, columnWidth: '62%' } },
  xaxis: { categories: (activeScopeData.value.monthly || []).map((item: any) => item.label) },
  yaxis: { labels: { formatter: (value: number) => formatRupiahCompact(chartActualValue(value)) } },
  tooltip: {
    ...chartBase.value.tooltip,
    y: { formatter: (_value: number, detail: any) => formatRupiah(monthlyActualValue(detail.seriesIndex, detail.dataPointIndex)) },
  },
}))

const statusChartSeries = computed(() => (activeScopeData.value.status || []).map((item: any) => Number(item.jumlah_pbj || 0)))
const statusChartOptions = computed(() => ({
  ...chartBase.value,
  chart: {
    ...chartBase.value.chart,
    events: {
      dataPointSelection: (_event: any, _context: any, detail: any) => {
        const index = detail.dataPointIndex >= 0 ? detail.dataPointIndex : detail.seriesIndex
        openStatusDrilldown(index)
      },
    },
  },
  labels: (activeScopeData.value.status || []).map((item: any) => item.label),
  colors: STATUS_COLORS,
  stroke: { width: 3, colors: [darkmode.isDark ? '#252a34' : '#ffffff'] },
  plotOptions: { pie: { donut: { size: '66%', labels: { show: true, total: { show: true, label: 'PBJ', formatter: () => String(activeSummary.value.total_pbj) } } } } },
  legend: { ...chartBase.value.legend, position: 'bottom' },
}))

const topOtherPrk = computed(() => (activeScopeData.value.other_prk || []).slice(0, 12))
const otherPrkChartHeight = computed(() => Math.max(340, topOtherPrk.value.length * 38))
const otherPrkChartSeries = computed(() => [{
  name: 'Total estimasi',
  data: topOtherPrk.value.map((item: any) => chartDisplayValue(item.total_estimasi)),
}])
const otherPrkChartOptions = computed(() => ({
  ...chartBase.value,
  chart: {
    ...chartBase.value.chart,
    events: {
      dataPointSelection: (_event: any, _context: any, detail: any) => openOtherPrkDrilldown(detail.dataPointIndex),
    },
  },
  colors: [LOCATION_COLORS.combined],
  plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '62%' } },
  xaxis: {
    categories: topOtherPrk.value.map((item: any) => item.prk),
    labels: { formatter: (value: number) => formatRupiahCompact(chartActualValue(value)) },
  },
  yaxis: { labels: { maxWidth: 150 } },
  tooltip: {
    ...chartBase.value.tooltip,
    y: {
      formatter: (_value: number, detail: any) => formatRupiah(topOtherPrk.value?.[detail.dataPointIndex]?.total_estimasi),
    },
  },
}))

function chartDisplayValue(value: any) {
  return Math.log10(Math.max(0, Number(value || 0)) + 1)
}

function chartActualValue(value: any) {
  return Math.max(0, Math.pow(10, Number(value || 0)) - 1)
}

function specialSeriesProcurementId(seriesIndex: number) {
  if (activeScope.value === 'jakarta') return 1
  if (activeScope.value === 'gresik') return 2
  return seriesIndex === 0 ? 1 : 2
}

function specialLocationValue(item: any, procurementId: number) {
  if (!item) return 0
  if (activeScope.value === 'jakarta' && procurementId !== 1) return 0
  if (activeScope.value === 'gresik' && procurementId !== 2) return 0
  return procurementId === 1
    ? Number(item.jakarta?.total_estimasi || 0)
    : Number(item.gresik?.total_estimasi || 0)
}

function monthlyProcurementId(seriesIndex: number) {
  if (activeScope.value === 'jakarta') return 1
  if (activeScope.value === 'gresik') return 2
  return seriesIndex === 0 ? 1 : 2
}

function monthlyActualValue(seriesIndex: number, dataPointIndex: number) {
  const scope = monthlyProcurementId(seriesIndex) === 1 ? 'jakarta' : 'gresik'
  return Number(data.value.scopes?.[scope]?.monthly?.[dataPointIndex]?.total_estimasi || 0)
}

function openDrilldown(title: string, rows: any[], subtitle = '') {
  drilldownTitle.value = title
  drilldownSubtitle.value = subtitle
  drilldownRows.value = [...rows]
  drilldownKeyword.value = ''
  drilldownOpen.value = true
}

function closeDrilldown() {
  drilldownOpen.value = false
  drilldownKeyword.value = ''
}

function openSpecialDrilldown(title: string, code?: string, procurementId?: number) {
  let rows = (data.value.records || []).filter((row: any) => (
    Boolean(row.special_prk_code) && [1, 2].includes(Number(row.pengadaan_id))
  ))
  if (code) rows = rows.filter((row: any) => String(row.special_prk_code) === String(code))
  const activeProcurementId = procurementId
    || (activeScope.value === 'jakarta' ? 1 : (activeScope.value === 'gresik' ? 2 : null))
  if (activeProcurementId) {
    rows = rows.filter((row: any) => Number(row.pengadaan_id) === Number(activeProcurementId))
  }

  openDrilldown(
    title,
    rows,
    `${formatNumber(rows.length)} PBJ pada tahun ${selectedYear.value}. Klik Cetak PBJ untuk membuka dokumennya.`
  )
}

function openPrkLookup() {
  if (!normalizedPrkLookup.value) {
    return H.alert('warning', 'Masukkan kode PRK yang ingin dicari.')
  }
  if (!prkLookupRows.value.length) {
    return H.alert('warning', `Tidak ditemukan PBJ dengan PRK ${normalizedPrkLookup.value} pada filter ${activeScopeLabel.value}.`)
  }

  openDrilldown(
    `Hasil pencarian PRK ${normalizedPrkLookup.value} - ${activeScopeLabel.value}`,
    prkLookupRows.value,
    `${formatNumber(prkLookupRows.value.length)} PBJ menggunakan kode yang cocok dengan pencarian ${normalizedPrkLookup.value}.`
  )
}

function openSummaryDrilldown(card: any) {
  let rows = [...recordsByScope.value]
  if (card.key === 'sudah_po') {
    rows = rows.filter((row: any) => String(row.nopo || '').trim() !== '')
  }

  openDrilldown(
    `${card.label} - ${activeScopeLabel.value}`,
    rows,
    `Data pembentuk statistik ${card.label.toLowerCase()} tahun ${selectedYear.value}.`
  )
}

function openMonthlyDrilldown(dataPointIndex: number, seriesIndex: number) {
  const monthData = activeScopeData.value.monthly?.[dataPointIndex]
  if (!monthData) return
  const procurementId = monthlyProcurementId(seriesIndex)
  const location = procurementId === 1 ? 'Jakarta' : 'Gresik'
  const rows = (data.value.records || []).filter((row: any) => {
    const rowMonth = Number(String(row.tanggal_acuan || '').slice(5, 7))
    return Number(row.pengadaan_id) === procurementId && rowMonth === Number(monthData.month)
  })

  openDrilldown(
    `PBJ ${monthData.label} ${selectedYear.value} - ${location}`,
    rows,
    `Daftar PBJ yang membentuk batang bulan ${monthData.label} untuk wilayah pengadaan ${location}.`
  )
}

function openStatusDrilldown(dataPointIndex: number) {
  const status = activeScopeData.value.status?.[dataPointIndex]
  if (!status) return
  const rows = recordsByScope.value.filter((row: any) => row.status_key === status.key)

  openDrilldown(
    `Posisi ${status.label} - ${activeScopeLabel.value}`,
    rows,
    `PBJ dengan posisi proses terakhir ${status.label}.`
  )
}

function openOtherPrkDrilldown(dataPointIndex: number) {
  const prk = topOtherPrk.value?.[dataPointIndex]
  if (!prk) return
  const rows = recordsByScope.value.filter((row: any) => row.prk_key === prk.prk)

  openDrilldown(
    `PBJ PRK ${prk.prk} - ${activeScopeLabel.value}`,
    rows,
    `Daftar PBJ yang membentuk statistik PRK ${prk.prk}.`
  )
}

function printPbj(row: any) {
  if (!row?.norec) return H.alert('warning', 'Data PBJ tidak memiliki identitas cetak.')
  H.printBlade(`pbj/cetak-pbj?pdf=true&norec=${encodeURIComponent(row.norec)}`)
}

function formatNumber(value: any) {
  return new Intl.NumberFormat('id-ID').format(Number(value || 0))
}

function formatRupiah(value: any) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0))
}

function formatRupiahCompact(value: any) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', notation: 'compact', maximumFractionDigits: 1 }).format(Number(value || 0))
}

function formatPercent(value: any) {
  return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value || 0))}%`
}

function formatDate(value: any) {
  if (!value) return '-'
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
}

function formatDateTime(value: any) {
  if (!value) return '-'
  const date = value instanceof Date ? value : new Date(String(value).replace(' ', 'T'))
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(date)
}

function scopeSpecialTotal(item: any) {
  if (activeScope.value === 'jakarta') return Number(item.jakarta?.total_estimasi || 0)
  if (activeScope.value === 'gresik') return Number(item.gresik?.total_estimasi || 0)
  return Number(item.total_estimasi || 0)
}

async function loadDashboard() {
  loading.value = true
  try {
    const response: any = await useApi().get(`/pbj/dashboard-data?tahun=${selectedYear.value}`)
    data.value = response?.data || response || emptyData()
    if (Number(data.value.year)) selectedYear.value = Number(data.value.year)
  } catch (error) {
    data.value = emptyData()
    H.alert('error', 'Dashboard PBJ gagal dimuat. Silakan coba kembali.')
  } finally {
    loading.value = false
  }
}

function exportExcel() {
  if (!recordsForExport.value.length) return H.alert('warning', 'Tidak ada data PBJ untuk diekspor.')

  const workbook = XLSX.utils.book_new()
  const specialRows = specialRecordsByScope.value
  const specialSheetName = 'PBJ PRK Khusus'
  const specialHeaders = [
    'No. Surat',
    'Judul Permintaan',
    'PRK',
    'No PR',
    'Wilayah Pengadaan',
    'Posisi',
    'No PO',
    'Total Estimasi',
    'Nilai Terkontrak',
  ]
  const specialDetailSheet = XLSX.utils.aoa_to_sheet([
    specialHeaders,
    ...specialRows.map((row: any) => [
      row.nosuratpbj || 'Draft',
      row.judulpermintaan || '-',
      row.prk || row.prk_key || '-',
      row.nopr || '-',
      row.wilayah_pengadaan || '-',
      row.status_label || '-',
      row.nopo || '-',
      Number(row.total_estimasi || 0),
      Number(row.nilai_po || 0),
    ]),
  ])
  specialDetailSheet['!autofilter'] = {
    ref: `A1:I${Math.max(1, specialRows.length + 1)}`,
  }
  specialDetailSheet['!cols'] = [
    { wch: 18 }, { wch: 42 }, { wch: 22 }, { wch: 16 }, { wch: 24 },
    { wch: 22 }, { wch: 16 }, { wch: 20 }, { wch: 20 }, { wch: 3 },
    { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 },
    { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 },
    { hidden: true }, { hidden: true }, { hidden: true },
  ]
  for (let rowNumber = 2; rowNumber <= specialRows.length + 1; rowNumber += 1) {
    for (const column of ['H', 'I']) {
      if (specialDetailSheet[`${column}${rowNumber}`]) {
        specialDetailSheet[`${column}${rowNumber}`].z = '"Rp" #,##0'
      }
    }
  }

  const chartItems = scopedSpecialItems.value
  const locationSeries = activeScope.value === 'gabungan'
    ? [
      {
        name: 'Jakarta',
        color: LOCATION_COLORS.jakarta,
        values: chartItems.map((item: any) => specialLocationValue(item, 1)),
      },
      {
        name: 'Gresik',
        color: LOCATION_COLORS.gresik,
        values: chartItems.map((item: any) => specialLocationValue(item, 2)),
      },
    ]
    : [{
      name: activeScopeLabel.value,
      color: activeScope.value === 'jakarta' ? LOCATION_COLORS.jakarta : LOCATION_COLORS.gresik,
      values: chartItems.map((item: any) => Number(item.total_estimasi || 0)),
    }]
  XLSX.utils.sheet_add_aoa(
    specialDetailSheet,
    [
      ['PRK', ...locationSeries.map((series) => series.name)],
      ...chartItems.map((item: any, index: number) => [
        `PRK ${item.code}`,
        ...locationSeries.map((series) => Number(series.values[index] || 0)),
      ]),
    ],
    { origin: 'T1' }
  )

  const statusCounts = new Map<string, { label: string; count: number }>()
  specialRows.forEach((row: any) => {
    const key = String(row.status_key || row.status_label || 'belum-diketahui')
    const current = statusCounts.get(key)
    statusCounts.set(key, {
      label: row.status_label || 'Belum diketahui',
      count: Number(current?.count || 0) + 1,
    })
  })
  const statusItems = Array.from(statusCounts.values())
    .sort((a, b) => b.count - a.count)
  if (!statusItems.length) statusItems.push({ label: 'Belum ada data', count: 0 })
  const statusHeaderRow = chartItems.length + 4
  XLSX.utils.sheet_add_aoa(
    specialDetailSheet,
    [
      ['Posisi', 'Jumlah PBJ'],
      ...statusItems.map((item) => [item.label, item.count]),
    ],
    { origin: `T${statusHeaderRow}` }
  )
  XLSX.utils.book_append_sheet(workbook, specialDetailSheet, specialSheetName)

  const summaryRows = [
    ['Dashboard Data PBJ', `${activeScopeLabel.value} - ${selectedYear.value}`],
    ['PBJ Disetujui Manager', activeSummary.value.total_pbj],
    ['Jumlah item', activeSummary.value.total_item],
    ['Jumlah PRK', activeSummary.value.total_prk],
    ['Total estimasi biaya', activeSummary.value.total_estimasi],
    ['Rata-rata estimasi', activeSummary.value.rata_rata_estimasi],
    ['Sudah memiliki PO', activeSummary.value.sudah_po],
    ['Total nilai PO', activeSummary.value.total_nilai_po],
  ]
  const summarySheet = XLSX.utils.aoa_to_sheet(summaryRows)
  summarySheet['!cols'] = [{ wch: 28 }, { wch: 24 }]
  XLSX.utils.book_append_sheet(workbook, summarySheet, 'Ringkasan')

  const specialSummarySheet = XLSX.utils.json_to_sheet(scopedSpecialItems.value.map((item: any) => ({
    PRK: item.code,
    Detail: item.label,
    'Jumlah PBJ': item.jumlah_pbj,
    'Estimasi Jakarta': specialLocationValue(item, 1),
    'Estimasi Gresik': specialLocationValue(item, 2),
    [`Estimasi ${activeScopeLabel.value}`]: Number(item.total_estimasi || 0),
    'Pagu Maksimum': item.pagu,
    'Persentase Pagu': item.pagu ? (Number(item.total_estimasi || 0) / Number(item.pagu)) * 100 : null,
    'Sisa Pagu Gabungan': item.sisa_pagu,
  })))
  specialSummarySheet['!cols'] = [{ wch: 10 }, { wch: 28 }, { wch: 14 }, { wch: 18 }, { wch: 18 }, { wch: 20 }, { wch: 18 }, { wch: 18 }, { wch: 20 }]
  XLSX.utils.book_append_sheet(workbook, specialSummarySheet, 'Ringkasan PRK Khusus')

  const otherPrkSheet = XLSX.utils.json_to_sheet((activeScopeData.value.other_prk || []).map((item: any) => ({
    PRK: item.prk,
    'Jumlah PBJ': item.jumlah_pbj,
    'Jumlah Item': item.total_item,
    'Total Estimasi': item.total_estimasi,
    'Rata-rata Estimasi': item.rata_rata,
  })))
  otherPrkSheet['!cols'] = [{ wch: 22 }, { wch: 14 }, { wch: 14 }, { wch: 20 }, { wch: 20 }]
  XLSX.utils.book_append_sheet(workbook, otherPrkSheet, 'PRK Lain')

  const detailSheet = XLSX.utils.json_to_sheet(recordsForExport.value.map((row: any, index: number) => ({
    No: index + 1,
    'No Surat PBJ': row.nosuratpbj || 'Draft',
    Tanggal: formatDate(row.tanggal_acuan),
    'Wilayah Pengadaan': row.wilayah_pengadaan,
    'Nama Pengaju': row.nama_pengaju,
    Pemohon: row.pemohon,
    'Judul Permintaan': row.judulpermintaan,
    PRK: row.prk,
    'No PR': row.nopr,
    Posisi: row.status_label,
    'No PO': row.nopo,
    'Jumlah Item': row.total_item,
    Subtotal: row.subtotal,
    'PPN (%)': row.ppn_persen,
    'Nilai PPN': row.nilai_ppn,
    'Total Estimasi Biaya': row.total_estimasi,
    'Nilai Terkontrak': row.nilai_po,
  })))
  detailSheet['!cols'] = [
    { wch: 6 }, { wch: 18 }, { wch: 16 }, { wch: 12 }, { wch: 24 }, { wch: 24 }, { wch: 40 },
    { wch: 28 }, { wch: 16 }, { wch: 22 }, { wch: 16 }, { wch: 12 }, { wch: 18 }, { wch: 10 },
    { wch: 18 }, { wch: 22 }, { wch: 18 },
  ]
  XLSX.utils.book_append_sheet(workbook, detailSheet, 'Daftar PBJ')

  const specialStartRow = 2
  const specialEndRow = Math.max(specialStartRow, chartItems.length + 1)
  const statusStartRow = statusHeaderRow + 1
  const statusEndRow = statusHeaderRow + statusItems.length
  const charts: NativeExcelChart[] = [
    {
      title: `Estimasi per PRK Khusus - ${activeScopeLabel.value}`,
      categoryRange: `$T$${specialStartRow}:$T$${specialEndRow}`,
      categories: chartItems.map((item: any) => `PRK ${item.code}`),
      series: locationSeries.map((series, index) => {
        const column = index === 0 ? 'U' : 'V'
        return {
          name: series.name,
          titleCell: `$${column}$1`,
          valueRange: `$${column}$${specialStartRow}:$${column}$${specialEndRow}`,
          values: series.values,
          color: series.color,
        }
      }),
      from: { col: 10, row: 0 },
      to: { col: 18, row: 18 },
      valueFormat: '"Rp" #,##0',
    },
    {
      title: 'Posisi Proses PBJ PRK Khusus',
      categoryRange: `$T$${statusStartRow}:$T$${statusEndRow}`,
      categories: statusItems.map((item) => item.label),
      series: [{
        name: 'Jumlah PBJ',
        titleCell: `$U$${statusHeaderRow}`,
        valueRange: `$U$${statusStartRow}:$U$${statusEndRow}`,
        values: statusItems.map((item) => item.count),
        color: LOCATION_COLORS.combined,
      }],
      from: { col: 10, row: 19 },
      to: { col: 18, row: 38 },
      horizontal: true,
      showValues: true,
      valueFormat: '0',
    },
  ]
  const workbookBytes = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' })
  const chartedWorkbook = addNativeExcelCharts(workbookBytes, specialSheetName, charts)
  downloadExcelFile(
    chartedWorkbook,
    `dashboard-pbj-${activeScope.value}-${selectedYear.value}.xlsx`
  )
}

function exportDrilldownExcel() {
  const rows = filteredDrilldownRows.value
  if (!rows.length) return H.alert('warning', 'Tidak ada data rincian PBJ untuk diekspor.')

  const totalEstimasi = rows.reduce(
    (sum: number, row: any) => sum + Number(row.total_estimasi || 0),
    0
  )
  const totalNilaiPo = rows.reduce(
    (sum: number, row: any) => sum + Number(row.nilai_po || 0),
    0
  )
  const workbook = XLSX.utils.book_new()
  const summarySheet = XLSX.utils.aoa_to_sheet([
    ['Rincian PBJ', drilldownTitle.value],
    ['Keterangan', drilldownSubtitle.value || '-'],
    ['Jumlah PBJ', rows.length],
    ['Total estimasi', totalEstimasi],
    ['Total nilai PO', totalNilaiPo],
    ['Filter pencarian', drilldownKeyword.value.trim() || '-'],
  ])
  summarySheet['!cols'] = [{ wch: 24 }, { wch: 52 }]
  summarySheet.B4.z = '"Rp" #,##0'
  summarySheet.B5.z = '"Rp" #,##0'
  XLSX.utils.book_append_sheet(workbook, summarySheet, 'Ringkasan')

  const detailSheet = XLSX.utils.json_to_sheet(rows.map((row: any, index: number) => ({
    No: index + 1,
    'No Surat PBJ': row.nosuratpbj || 'Draft',
    Tanggal: formatDate(row.tanggal_acuan),
    'Judul Permintaan': row.judulpermintaan || '-',
    'Nama Pengaju': row.nama_pengaju || '-',
    Pemohon: row.pemohon || '-',
    PRK: row.prk || row.prk_key || '-',
    'No PR': row.nopr || '-',
    'Wilayah Pengadaan': row.wilayah_pengadaan || '-',
    Posisi: row.status_label || '-',
    'No PO': row.nopo || '-',
    'Jumlah Item': Number(row.total_item || 0),
    Subtotal: Number(row.subtotal || 0),
    'PPN (%)': Number(row.ppn_persen || 0),
    'Nilai PPN': Number(row.nilai_ppn || 0),
    'Total Estimasi': Number(row.total_estimasi || 0),
    'Nilai Terkontrak': Number(row.nilai_po || 0),
  })))
  detailSheet['!cols'] = [
    { wch: 6 }, { wch: 18 }, { wch: 16 }, { wch: 42 }, { wch: 24 }, { wch: 24 },
    { wch: 22 }, { wch: 16 }, { wch: 24 }, { wch: 22 }, { wch: 16 }, { wch: 12 },
    { wch: 18 }, { wch: 10 }, { wch: 18 }, { wch: 20 }, { wch: 18 },
  ]
  for (let rowNumber = 2; rowNumber <= rows.length + 1; rowNumber += 1) {
    for (const column of ['M', 'O', 'P', 'Q']) {
      if (detailSheet[`${column}${rowNumber}`]) detailSheet[`${column}${rowNumber}`].z = '"Rp" #,##0'
    }
  }
  XLSX.utils.book_append_sheet(workbook, detailSheet, 'Daftar PBJ')

  const safeTitle = String(drilldownTitle.value || 'rincian-pbj')
    .replace(/[<>:"/\\|?*\u0000-\u001F]/g, '-')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 80)
  XLSX.writeFile(workbook, `${safeTitle || 'rincian-pbj'}.xlsx`)
}

function exportPdf() {
  if (!recordsForExport.value.length) return H.alert('warning', 'Tidak ada data PBJ untuk diekspor.')

  const doc = new jsPDF('landscape', 'mm', 'a4')
  doc.setFont('helvetica', 'bold')
  doc.setFontSize(16)
  doc.text('Dashboard Data PBJ', 14, 15)
  doc.setFont('helvetica', 'normal')
  doc.setFontSize(9)
  doc.text(`Tahun ${selectedYear.value} | Wilayah Pengadaan: ${activeScopeLabel.value} | Dicetak: ${formatDateTime(new Date())}`, 14, 21)

  autoTable(doc, {
    startY: 26,
    theme: 'grid',
    head: [['Total PBJ', 'Jumlah Item', 'Jumlah PRK', 'Total Estimasi', 'Rata-rata', 'Sudah PO']],
    body: [[
      formatNumber(activeSummary.value.total_pbj),
      formatNumber(activeSummary.value.total_item),
      formatNumber(activeSummary.value.total_prk),
      formatRupiah(activeSummary.value.total_estimasi),
      formatRupiah(activeSummary.value.rata_rata_estimasi),
      formatNumber(activeSummary.value.sudah_po),
    ]],
    headStyles: { fillColor: [30, 64, 175] },
    styles: { fontSize: 8 },
  })

  let nextY = ((doc as any).lastAutoTable?.finalY || 42) + 7
  doc.setFont('helvetica', 'bold')
  doc.setFontSize(11)
  doc.text('PRK Khusus 262N0302 / 262N0303 / 262N0304 / 262N0309 / 262N0310', 14, nextY)
  autoTable(doc, {
    startY: nextY + 3,
    theme: 'striped',
    head: [['PRK', 'Detail', 'PBJ', 'Estimasi Jakarta', 'Estimasi Gresik', `Estimasi ${activeScopeLabel.value}`, 'Pagu', '% Pagu']],
    body: specialPrk.value.items.map((item: any) => [
      item.code,
      item.label,
      activeScope.value === 'gabungan' ? item.jumlah_pbj : activeScope.value === 'jakarta' ? item.jakarta.jumlah_pbj : item.gresik.jumlah_pbj,
      formatRupiah(item.jakarta.total_estimasi),
      formatRupiah(item.gresik.total_estimasi),
      formatRupiah(scopeSpecialTotal(item)),
      item.pagu ? formatRupiah(item.pagu) : '-',
      item.pagu ? formatPercent((scopeSpecialTotal(item) / Number(item.pagu)) * 100) : '-',
    ]),
    headStyles: { fillColor: [15, 159, 118] },
    styles: { fontSize: 7.5 },
  })

  nextY = ((doc as any).lastAutoTable?.finalY || nextY + 20) + 7
  doc.setFont('helvetica', 'bold')
  doc.setFontSize(11)
  doc.text(`Daftar PBJ ${activeScopeLabel.value}`, 14, nextY)
  autoTable(doc, {
    startY: nextY + 3,
    theme: 'grid',
    head: [['No', 'No Surat', 'Tanggal', 'Judul Permintaan', 'PRK', 'No PR', 'Posisi', 'No PO', 'Item', 'Total Estimasi']],
    body: recordsForExport.value.map((row: any, index: number) => [
      index + 1,
      row.nosuratpbj || 'Draft',
      formatDate(row.tanggal_acuan),
      row.judulpermintaan || '-',
      row.special_prk_code ? `PRK ${row.special_prk_code}` : row.prk_key,
      row.nopr || '-',
      row.status_label,
      row.nopo || '-',
      row.total_item,
      formatRupiah(row.total_estimasi),
    ]),
    headStyles: { fillColor: [51, 65, 85] },
    styles: { fontSize: 6.2, cellPadding: 1.3 },
    columnStyles: {
      0: { cellWidth: 8 }, 1: { cellWidth: 24 }, 2: { cellWidth: 20 }, 3: { cellWidth: 55 },
      4: { cellWidth: 22 }, 5: { cellWidth: 24 }, 6: { cellWidth: 27 }, 7: { cellWidth: 24 },
      8: { cellWidth: 10 }, 9: { cellWidth: 30 },
    },
    didDrawPage: () => {
      doc.setFont('helvetica', 'normal')
      doc.setFontSize(7)
      doc.text(`U-LAB | Dashboard PBJ ${selectedYear.value}`, 14, 202)
    },
  })

  doc.save(`dashboard-pbj-${activeScope.value}-${selectedYear.value}.pdf`)
}

watch(selectedYear, (newYear, oldYear) => {
  if (newYear && oldYear && newYear !== oldYear) loadDashboard()
})

onMounted(loadDashboard)
</script>

<style lang="scss" scoped>
.pbj-dashboard {
  --pbj-surface: #ffffff;
  --pbj-surface-soft: #f8fafc;
  --pbj-border: #e2e8f0;
  --pbj-text: #172033;
  --pbj-muted: #64748b;
  --pbj-blue: #2563eb;
  --pbj-green: #0f9f76;
  --pbj-navy: #1e40af;
  --pbj-amber: #d97706;
  --pbj-red: #dc2626;
  color: var(--pbj-text);
  font-family: inherit;
  padding-bottom: 3rem;
}

.dashboard-header,
.chart-card,
.prk-lookup-card,
.records-card {
  background: var(--pbj-surface);
  border: 1px solid var(--pbj-border);
  box-shadow: 0 8px 24px rgb(15 23 42 / 5%);
}

.dashboard-header {
  padding: 1.4rem 1.5rem;
}

.dashboard-heading,
.dashboard-controls,
.card-heading,
.records-heading,
.budget-head,
.budget-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.heading-kicker {
  color: var(--pbj-blue);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.dashboard-heading h1 {
  color: var(--pbj-text);
  font-size: 1.6rem;
  font-weight: 700;
  line-height: 1.25;
  margin: .3rem 0 .35rem;
}

.dashboard-heading p,
.card-heading p,
.records-heading p {
  color: var(--pbj-muted);
  font-size: .9rem;
  margin: 0;
}

.header-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: .65rem;
}

.dashboard-controls {
  border-top: 1px solid var(--pbj-border);
  margin-top: 1.25rem;
  padding-top: 1rem;
}

.year-control {
  align-items: center;
  display: flex;
  gap: .65rem;
}

.year-control span {
  color: var(--pbj-muted);
  font-size: .78rem;
  font-weight: 600;
  white-space: nowrap;
}

.year-control .input {
  background: var(--pbj-surface);
  border-color: var(--pbj-border);
  color: var(--pbj-text);
  min-width: 105px;
}

.scope-switch {
  background: var(--pbj-surface-soft);
  border: 1px solid var(--pbj-border);
  border-radius: 10px;
  display: inline-flex;
  padding: 4px;
}

.scope-switch button {
  align-items: center;
  background: transparent;
  border: 0;
  border-radius: 7px;
  color: var(--pbj-muted);
  cursor: pointer;
  display: flex;
  font: inherit;
  font-size: .82rem;
  font-weight: 600;
  gap: .4rem;
  padding: .55rem .85rem;
}

.scope-switch button.is-active {
  background: var(--pbj-surface);
  box-shadow: 0 2px 8px rgb(15 23 42 / 9%);
  color: var(--pbj-blue);
}

.updated-at {
  align-items: center;
  color: var(--pbj-muted);
  display: flex;
  font-size: .75rem;
  gap: .45rem;
}

.loading-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 1.5rem;
}

.dashboard-skeleton {
  border-radius: 12px;
  height: 140px;
}

.dashboard-section {
  margin-top: 2rem;
}

.special-summary-grid,
.summary-grid,
.budget-grid {
  display: grid;
  gap: 1rem;
}

.special-summary-grid {
  grid-template-columns: 1.45fr 1fr 1fr;
}

.metric-card,
.summary-card,
.budget-card {
  background: var(--pbj-surface);
  border: 1px solid var(--pbj-border);
  border-radius: 12px;
  box-shadow: 0 6px 18px rgb(15 23 42 / 4%);
}

.is-drilldown {
  cursor: pointer;
}

article.is-drilldown {
  transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}

article.is-drilldown:hover,
article.is-drilldown:focus-visible {
  border-color: #9bb8f5;
  box-shadow: 0 10px 24px rgb(37 99 235 / 10%);
  outline: none;
  transform: translateY(-2px);
}

:deep(.apexcharts-bar-area),
:deep(.apexcharts-pie-area) {
  cursor: pointer;
}

.metric-card {
  align-items: center;
  display: flex;
  gap: 1rem;
  min-height: 120px;
  padding: 1.15rem;
}

.metric-card.is-main {
  border-left: 4px solid var(--pbj-navy);
}

.metric-icon {
  align-items: center;
  background: #edf3ff;
  border-radius: 10px;
  color: var(--pbj-blue);
  display: flex;
  flex: 0 0 46px;
  font-size: 1.2rem;
  height: 46px;
  justify-content: center;
}

.metric-card span,
.metric-card small,
.summary-card span,
.summary-card small,
.budget-card span,
.budget-card small {
  color: var(--pbj-muted);
  display: block;
}

.metric-card span,
.summary-card span {
  font-size: .76rem;
  font-weight: 600;
}

.metric-card strong {
  color: var(--pbj-text);
  display: block;
  font-size: 1.35rem;
  line-height: 1.3;
  margin: .25rem 0;
}

.metric-card small,
.summary-card small {
  font-size: .72rem;
}

.budget-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 1rem;
}

.budget-card {
  border-top: 3px solid var(--pbj-green);
  padding: 1.15rem;
}

.budget-card.is-warning { border-top-color: var(--pbj-amber); }
.budget-card.is-danger,
.budget-card.is-over { border-top-color: var(--pbj-red); }

.prk-code {
  color: var(--pbj-blue) !important;
  font-size: .72rem;
  font-weight: 800;
  letter-spacing: .04em;
}

.budget-card h3 {
  color: var(--pbj-text);
  font-size: 1rem;
  margin: .15rem 0 0;
}

.budget-percent {
  color: var(--pbj-text) !important;
  font-size: 1.35rem !important;
  font-weight: 750;
}

.budget-values {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin: 1rem 0;
}

.budget-values span {
  font-size: .7rem;
}

.budget-values strong {
  color: var(--pbj-text);
  display: block;
  font-size: .95rem;
  margin-top: .15rem;
}

.budget-track {
  background: #e2e8f0;
  border-radius: 999px;
  height: 9px;
  overflow: hidden;
}

.budget-track span {
  background: var(--pbj-green);
  border-radius: inherit;
  display: block;
  height: 100%;
}

.is-warning .budget-track span { background: var(--pbj-amber); }
.is-danger .budget-track span,
.is-over .budget-track span { background: var(--pbj-red); }

.budget-foot {
  color: var(--pbj-muted);
  font-size: .76rem;
  margin-top: .65rem;
}

.budget-foot .is-over {
  color: var(--pbj-red);
  font-weight: 700;
}

.chart-grid {
  margin-top: .35rem;
}

.chart-card {
  height: 100%;
  padding: 1.1rem;
}

.prk-lookup-card {
  align-items: center;
  display: grid;
  gap: 1rem;
  grid-template-columns: minmax(260px, 1.1fr) minmax(280px, 1fr) auto auto;
  padding: 1rem 1.1rem;
}

.prk-lookup-copy h3 {
  color: var(--pbj-text);
  font-size: .98rem;
  font-weight: 700;
  margin: 0 0 .2rem;
}

.prk-lookup-copy p,
.prk-lookup-result span,
.prk-lookup-result small {
  color: var(--pbj-muted);
  font-size: .73rem;
  margin: 0;
}

.prk-lookup-field {
  margin: 0;
}

.prk-lookup-result {
  min-width: 145px;
}

.prk-lookup-result span,
.prk-lookup-result strong,
.prk-lookup-result small {
  display: block;
}

.prk-lookup-result strong {
  color: var(--pbj-text);
  font-size: 1rem;
  margin: .08rem 0;
}

.card-heading {
  align-items: flex-start;
  margin-bottom: .75rem;
}

.card-heading h3,
.records-heading h3 {
  color: var(--pbj-text);
  font-size: .98rem;
  font-weight: 700;
  margin: 0 0 .2rem;
}

.card-heading p,
.records-heading p {
  font-size: .76rem;
}

.card-total {
  color: var(--pbj-text);
  font-size: .8rem;
  font-weight: 700;
  white-space: nowrap;
}

.location-legend {
  display: flex;
  gap: .8rem;
}

.location-legend span {
  align-items: center;
  color: var(--pbj-muted);
  display: flex;
  font-size: .72rem;
  gap: .35rem;
}

.location-legend i {
  border-radius: 2px;
  display: inline-block;
  height: 9px;
  width: 9px;
}

.location-legend .is-jakarta { background: var(--pbj-blue); }
.location-legend .is-gresik { background: var(--pbj-green); }

.table-scroll {
  overflow-x: auto;
}

.business-table {
  border-collapse: collapse;
  min-width: 580px;
  width: 100%;
}

.business-table th,
.business-table td {
  border-bottom: 1px solid var(--pbj-border);
  color: var(--pbj-text);
  font-size: .73rem;
  padding: .75rem .55rem;
  vertical-align: middle;
}

.business-table th {
  color: var(--pbj-muted);
  font-size: .67rem;
  font-weight: 700;
  text-transform: uppercase;
}

.business-table td small {
  color: var(--pbj-muted);
  display: block;
  font-size: .65rem;
  margin-top: .1rem;
}

.business-table tr.is-drilldown:hover td,
.business-table tr.is-drilldown:focus-visible td {
  background: var(--pbj-surface-soft);
}

.summary-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.summary-card {
  min-height: 125px;
  padding: 1rem;
}

.summary-card-top {
  align-items: center;
  display: flex;
  justify-content: space-between;
}

.summary-card-top i {
  color: var(--pbj-blue);
  font-size: 1.1rem;
}

.summary-card > strong {
  color: var(--pbj-text);
  display: block;
  font-size: 1.2rem;
  line-height: 1.35;
  margin: .65rem 0 .25rem;
}

.empty-chart {
  align-items: center;
  color: var(--pbj-muted);
  display: flex;
  height: 280px;
  justify-content: center;
}

.records-card {
  margin-top: .75rem;
  padding: 1.1rem;
}

.records-heading {
  margin-bottom: 1rem;
}

.records-search {
  margin: 0;
  min-width: 340px;
}

.drilldown-summary {
  align-items: center;
  background: var(--pbj-surface-soft);
  border: 1px solid var(--pbj-border);
  border-radius: 10px;
  display: grid;
  gap: 1rem;
  grid-template-columns: 130px 190px 190px minmax(260px, 1fr);
  margin-bottom: .75rem;
  padding: .85rem 1rem;
}

.drilldown-summary span {
  color: var(--pbj-muted);
  display: block;
  font-size: .7rem;
  font-weight: 600;
}

.drilldown-summary strong {
  color: var(--pbj-text);
  display: block;
  font-size: 1rem;
  margin-top: .15rem;
}

.drilldown-search {
  margin: 0;
}

.drilldown-subtitle {
  color: var(--pbj-muted);
  font-size: .76rem;
  margin: 0 0 .85rem;
}

.empty-drilldown {
  color: var(--pbj-muted);
  padding: 2rem;
  text-align: center;
}

:deep(.drilldown-table .p-datatable-thead > tr > th) {
  background: var(--pbj-surface-soft);
  border-color: var(--pbj-border);
  color: var(--pbj-muted);
  font-size: .72rem;
}

:deep(.drilldown-table .p-datatable-tbody > tr) {
  background: var(--pbj-surface);
  color: var(--pbj-text);
}

:deep(.drilldown-table .p-datatable-tbody > tr > td) {
  border-color: var(--pbj-border);
  font-size: .74rem;
}

.table-subtext {
  color: var(--pbj-muted);
  display: block;
  font-size: .68rem;
  margin-top: .15rem;
}

.row-title,
.money-cell {
  color: var(--pbj-text);
}

.prk-pill,
.status-pill {
  border-radius: 999px;
  display: inline-flex;
  font-size: .68rem;
  font-weight: 700;
  padding: .3rem .55rem;
}

.prk-pill {
  background: #f1f5f9;
  color: #475569;
}

.prk-pill.is-special {
  background: #eaf0ff;
  color: var(--pbj-navy);
}

.status-pill { background: #eef2ff; color: #4338ca; }
.status-pill.is-draft { background: #f1f5f9; color: #64748b; }
.status-pill.is-rendal { background: #ecfeff; color: #0e7490; }
.status-pill.is-inventory { background: #ecfdf5; color: #047857; }
.status-pill.is-pengadaan { background: #f7fee7; color: #4d7c0f; }
.status-pill.is-dibatalkan { background: #fef2f2; color: #b91c1c; }

:deep(.pbj-records-table .p-datatable-thead > tr > th) {
  background: var(--pbj-surface-soft);
  border-color: var(--pbj-border);
  color: var(--pbj-muted);
  font-size: .72rem;
}

:deep(.pbj-records-table .p-datatable-tbody > tr) {
  background: var(--pbj-surface);
  color: var(--pbj-text);
}

:deep(.pbj-records-table .p-datatable-tbody > tr > td) {
  border-color: var(--pbj-border);
  font-size: .75rem;
}

:global(.is-dark) .pbj-dashboard {
  --pbj-surface: #252a34;
  --pbj-surface-soft: #20242d;
  --pbj-border: #3b4250;
  --pbj-text: #f1f5f9;
  --pbj-muted: #a5b0c2;
}

:global(.is-dark) .metric-icon,
:global(.is-dark) .prk-pill.is-special {
  background: #26375f;
}

:global(.is-dark) .prk-pill {
  background: #343b48;
  color: #cbd5e1;
}

@media (max-width: 1100px) {
  .dashboard-heading,
  .dashboard-controls {
    align-items: flex-start;
    flex-direction: column;
  }

  .header-actions { justify-content: flex-start; }
  .special-summary-grid { grid-template-columns: 1fr 1fr; }
  .special-summary-grid .is-main { grid-column: 1 / -1; }
  .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .loading-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .prk-lookup-card { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 700px) {
  .dashboard-header { padding: 1rem; }
  .header-actions { width: 100%; }
  .header-actions :deep(.button) { flex: 1 1 auto; }
  .scope-switch { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); width: 100%; }
  .scope-switch button { justify-content: center; padding-inline: .4rem; }
  .special-summary-grid,
  .summary-grid,
  .budget-grid,
  .loading-grid { grid-template-columns: 1fr; }
  .special-summary-grid .is-main { grid-column: auto; }
  .records-heading { align-items: flex-start; flex-direction: column; }
  .records-search { min-width: 0; width: 100%; }
  .drilldown-summary { grid-template-columns: 1fr; }
  .prk-lookup-card { grid-template-columns: 1fr; }
  .budget-values { grid-template-columns: 1fr; }
  .card-heading { flex-direction: column; }
}
</style>
