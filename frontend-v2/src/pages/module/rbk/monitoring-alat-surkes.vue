<template>
    <div class="page-content-inner surkes-monitor-page">
        <VCard class="surkes-header-card">
            <div class="header-grid">
                <div>
                    <!-- <span class="eyebrow">Monitoring Tahunan</span> -->
                    <h1>Monitoring Alat Surkes {{ activeLokasiLabel }}</h1>
                    <p>
                        Ringkasan rencana alat surkes, realisasi pendaftaran, sisa yang belum daftar, dan alat non
                        surkes pada tahun berjalan.
                    </p>
                </div>

                <div class="header-actions">
                    <VButton color="primary" icon="feather:settings" outlined @click="goToMapping">
                        Kelola Mapping
                    </VButton>
                    <VButton color="info" icon="feather:refresh-cw" :loading="loading" @click="loadMonitoring">
                        Refresh
                    </VButton>
                    <VButton color="success" icon="feather:file-text" :loading="exporting" @click="exportExcel">
                        Excel
                    </VButton>
                </div>
            </div>

            <div class="surkes-location-tabs">
                <button v-for="tab in lokasiTabs" :key="tab.value"
                    :class="['surkes-tab-button', { 'is-active': lokasiId === tab.value }]"
                    @click="switchLokasi(tab.value)">
                    <span>{{ tab.label }}</span>
                    <small>{{ tab.caption }}</small>
                </button>
            </div>

            <div class="filter-bar">
                <VField label="Tahun">
                    <VControl>
                        <Calendar v-model="year" view="year" dateFormat="yy" placeholder="Tahun"
                            inputClass="p-inputtext-lg" />
                    </VControl>
                </VField>

                <VField label="Unit">
                    <VControl>
                        <Multiselect v-model="filterMitraId" :options="optMitra" placeholder="Semua unit"
                            :searchable="true" @select="loadMonitoring" @clear="loadMonitoring" />
                    </VControl>
                </VField>

                <VField label="Lingkup">
                    <VControl>
                        <Multiselect v-model="filterLingkupId" :options="optLingkup" placeholder="Semua lingkup"
                            :searchable="true" @select="loadMonitoring" @clear="loadMonitoring" />
                    </VControl>
                </VField>

                <VField label="Cari">
                    <VControl icon="feather:search">
                        <input v-model="keyword" class="input" placeholder="Unit, alat, SN, no order..." />
                    </VControl>
                </VField>

                <div class="filter-reset">
                    <VButton color="light" icon="feather:rotate-ccw" @click="resetFilter">
                        Reset
                    </VButton>
                </div>
            </div>
        </VCard>

        <div v-if="loading" class="columns is-multiline mt-4">
            <div v-for="i in 6" :key="i" class="column is-12-mobile is-6-tablet is-4-desktop">
                <div class="skeleton-card" />
            </div>
        </div>

        <template v-else>
            <div class="summary-grid">
                <div v-for="card in summaryCards" :key="card.key"
                    :class="['summary-card', `is-${card.tone}`, 'is-drilldown']" role="button" tabindex="0"
                    @click="openSummaryDetail(card)" @keydown.enter="openSummaryDetail(card)">
                    <div class="summary-icon">
                        <i class="iconify" :data-icon="card.icon"></i>
                    </div>
                    <div>
                        <span>{{ card.label }}</span>
                        <strong>{{ formatNumber(card.value) }}</strong>
                        <small>{{ card.caption }}</small>
                    </div>
                </div>
            </div>

            <div class="columns is-multiline chart-row">
                <div class="column is-12">
                    <VCard class="chart-card unit-overview-card is-interactive-chart">
                        <div class="chart-title">
                            <div>
                                <h3>Rencana, Realisasi, dan Surkes Belum Daftar per Unit</h3>
                                <p>Seluruh unit Surkes sesuai filter lokasi {{ activeLokasiLabel }}</p>
                            </div>
                            <span>Klik batang · {{ byUnit.length }} unit</span>
                        </div>
                        <apexchart v-if="byUnit.length" :height="unitChartHeight" type="bar" :options="unitChartOptions"
                            :series="unitChartSeries" />
                        <div v-else class="empty-chart">Belum ada data unit</div>
                    </VCard>
                </div>

                <div class="column is-12-tablet is-6-desktop">
                    <VCard class="chart-card is-interactive-chart">
                        <div class="chart-title">
                            <h3>Status Surkes</h3>
                            <span>Klik bagian · {{ summary.progress }}% realisasi</span>
                        </div>
                        <apexchart v-if="hasSurkesData" height="340" type="donut" :options="statusChartOptions"
                            :series="statusChartSeries" />
                        <div v-else class="empty-chart">Belum ada data Surkes</div>
                    </VCard>
                </div>

                <div class="column is-12-tablet is-6-desktop">
                    <VCard class="chart-card is-interactive-chart">
                        <div class="chart-title">
                            <h3>Komposisi Surkes dan Non Surkes</h3>
                            <span>Klik bagian · {{ formatNumber(summary.rencana_alat_surkes + summary.alat_non_surkes) }} alat</span>
                        </div>
                        <apexchart v-if="hasCompositionData" height="340" type="donut"
                            :options="compositionChartOptions" :series="compositionChartSeries" />
                        <div v-else class="empty-chart">Belum ada data alat</div>
                    </VCard>
                </div>

                <div class="column is-12">
                    <VCard class="chart-card is-interactive-chart">
                        <div class="chart-title">
                            <h3>Sebaran per Lingkup</h3>
                            <span>Klik batang · {{ byLingkup.length }} lingkup</span>
                        </div>
                        <apexchart v-if="byLingkup.length" height="320" type="bar" :options="lingkupChartOptions"
                            :series="lingkupChartSeries" />
                        <div v-else class="empty-chart">Belum ada data lingkup</div>
                    </VCard>
                </div>
            </div>

            <VCard class="table-card">
                <div class="table-toolbar">
                    <div>
                        <h3>Daftar Monitoring Alat</h3>
                        <p>
                            {{ formatNumber(rows.length) }} alat ditampilkan dari filter aktif.
                            Duplikasi pendaftaran: {{ formatNumber(summary.duplicate_registrasi_rows) }} baris.
                        </p>
                    </div>

                    <div class="status-tabs">
                        <button v-for="item in statusOptions" :key="item.value"
                            :class="['status-tab', { 'is-active': selectedStatus === item.value }]"
                            @click="setStatus(item.value)">
                            <span>{{ item.label }}</span>
                            <strong>{{ formatNumber(item.count) }}</strong>
                        </button>
                    </div>
                </div>

                <DataTable :value="rows" :paginator="true" :rows="15" :rowsPerPageOptions="[15, 30, 50, 100]"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="Menampilkan {first} - {last} dari {totalRecords}" responsiveLayout="scroll"
                    class="p-datatable-sm monitoring-table" stripedRows>
                    <Column field="status" header="Status" :sortable="true" headerStyle="width: 170px">
                        <template #body="{ data }">
                            <span :class="['monitor-status', statusClass(data.status)]">
                                {{ data.status_label }}
                            </span>
                            <span v-if="data.lingkup_mismatch" class="mini-alert mt-2">
                                Lingkup beda
                            </span>
                        </template>
                    </Column>

                    <Column field="namaperusahaan" header="Unit" :sortable="true" headerStyle="min-width: 220px">
                        <template #body="{ data }">
                            <strong class="unit-name">{{ data.namaperusahaan || '-' }}</strong>
                            <small class="muted">ID: {{ data.unit_id || '-' }}</small>
                        </template>
                    </Column>

                    <Column field="namaproduk" header="Alat" :sortable="true" headerStyle="min-width: 280px">
                        <template #body="{ data }">
                            <div class="alat-cell">
                                <strong>{{ data.namaproduk || 'Nama alat tidak terbaca' }}</strong>
                                <small>Merk/Tipe: {{ data.namamerk || '-' }} / {{ data.namatipe || '-' }}</small>
                                <small>SN: {{ data.namaserialnumber || '-' }}</small>
                            </div>
                        </template>
                    </Column>

                    <Column header="Rencana Surkes" headerStyle="min-width: 210px">
                        <template #body="{ data }">
                            <span class="tag is-info is-light">{{ data.lingkup_mapping || 'Belum mapping' }}</span>
                            <small v-if="data.mapping_duplicate_count > 0" class="muted mt-1">
                                Duplikat mapping: {{ data.mapping_duplicate_count }}
                            </small>
                        </template>
                    </Column>

                    <Column header="Pendaftaran Tahun Ini" headerStyle="min-width: 260px">
                        <template #body="{ data }">
                            <div v-if="data.registration_count > 0" class="realisasi-cell">
                                <div>
                                    <strong>{{ data.last_nopendaftaran || '-' }}</strong>
                                    <span>{{ formatDate(data.last_tglregistrasi) }}</span>
                                </div>
                                <small>No Order: {{ data.last_noorderalat || '-' }}</small>
                                <small>Lingkup: {{ data.lingkup_registrasi || '-' }}</small>
                                <div class="order-tags">
                                    <span v-for="jenis in data.jenisorder" :key="jenis" class="tag is-light">
                                        {{ jenis }}
                                    </span>
                                    <span class="tag is-primary is-light">
                                        {{ data.registration_count }}x daftar
                                    </span>
                                </div>
                            </div>
                            <span v-else class="muted">Belum ada pendaftaran tahun ini</span>
                        </template>
                    </Column>

                    <Column field="status_pekerjaan" header="Pekerjaan" :sortable="true" headerStyle="width: 150px">
                        <template #body="{ data }">
                            <span :class="['work-status', data.status_pekerjaan === 'Selesai' ? 'is-done' : '']">
                                {{ data.status_pekerjaan }}
                            </span>
                        </template>
                    </Column>
                </DataTable>
            </VCard>
        </template>

        <VModal :title="detailTitle" :open="detailOpen" size="big" actions="right" @close="closeDetail">
            <template #content>
                <div class="detail-summary">
                    <div>
                        <span>Alat ditemukan</span>
                        <strong>{{ formatNumber(detailRows.length) }}</strong>
                    </div>
                    <div>
                        <span>Alat Surkes</span>
                        <strong>{{ formatNumber(detailSurkesCount) }}</strong>
                    </div>
                    <div>
                        <span>Alat Non Surkes</span>
                        <strong>{{ formatNumber(detailNonSurkesCount) }}</strong>
                    </div>
                    <div>
                        <span>Total pendaftaran</span>
                        <strong>{{ formatNumber(detailRegistrationCount) }}</strong>
                    </div>
                    <VField class="detail-search">
                        <VControl icon="feather:search">
                            <VInput v-model="detailKeyword" placeholder="Cari unit, alat, merk, tipe, SN, pendaftaran..." />
                        </VControl>
                    </VField>
                </div>

                <p class="detail-subtitle">{{ detailSubtitle }}</p>

                <DataTable :value="filteredDetailRows" :paginator="true" :rows="8"
                    :rowsPerPageOptions="[8, 15, 30, 50, 100]" class="p-datatable-sm detail-table"
                    responsiveLayout="scroll" stripedRows
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="Menampilkan {first} - {last} dari {totalRecords}">
                    <Column field="status" header="Status" :sortable="true" style="min-width: 170px">
                        <template #body="{ data }">
                            <span :class="['monitor-status', statusClass(data.status)]">{{ data.status_label }}</span>
                            <span v-if="data.lingkup_mismatch" class="mini-alert mt-2">Lingkup berbeda</span>
                        </template>
                    </Column>
                    <Column field="namaperusahaan" header="Unit" :sortable="true" style="min-width: 220px">
                        <template #body="{ data }">
                            <strong class="unit-name">{{ data.namaperusahaan || '-' }}</strong>
                            <small class="muted">ID: {{ data.unit_id || '-' }}</small>
                        </template>
                    </Column>
                    <Column field="namaproduk" header="Alat" :sortable="true" style="min-width: 280px">
                        <template #body="{ data }">
                            <div class="alat-cell">
                                <strong>{{ data.namaproduk || 'Nama alat tidak terbaca' }}</strong>
                                <small>Merk/Tipe: {{ data.namamerk || '-' }} / {{ data.namatipe || '-' }}</small>
                                <small>SN: {{ data.namaserialnumber || '-' }}</small>
                            </div>
                        </template>
                    </Column>
                    <Column header="Lingkup" style="min-width: 220px">
                        <template #body="{ data }">
                            <strong class="unit-name">{{ data.lingkup_mapping || 'Belum mapping' }}</strong>
                            <small class="muted">Pendaftaran: {{ data.lingkup_registrasi || '-' }}</small>
                        </template>
                    </Column>
                    <Column header="Pendaftaran Terakhir" style="min-width: 260px">
                        <template #body="{ data }">
                            <div v-if="data.registration_count > 0" class="realisasi-cell">
                                <div>
                                    <strong>{{ data.last_nopendaftaran || '-' }}</strong>
                                    <span>{{ formatDate(data.last_tglregistrasi) }}</span>
                                </div>
                                <small>No Order: {{ data.last_noorderalat || '-' }}</small>
                                <small>Jenis: {{ data.jenisorder?.join(', ') || '-' }}</small>
                            </div>
                            <span v-else class="muted">Belum ada pendaftaran tahun ini</span>
                        </template>
                    </Column>
                    <Column field="registration_count" header="Jumlah Daftar" :sortable="true" style="width: 130px">
                        <template #body="{ data }">
                            <strong>{{ formatNumber(data.registration_count) }}</strong>
                            <small v-if="data.duplicate_count > 0" class="muted">
                                {{ formatNumber(data.duplicate_count) }} duplikasi
                            </small>
                        </template>
                    </Column>
                    <Column field="status_pekerjaan" header="Pekerjaan" :sortable="true" style="min-width: 140px">
                        <template #body="{ data }">
                            <span :class="['work-status', data.status_pekerjaan === 'Selesai' ? 'is-done' : '']">
                                {{ data.status_pekerjaan }}
                            </span>
                        </template>
                    </Column>
                    <template #empty>
                        <div class="empty-detail">Tidak ada alat pada rincian yang dipilih.</div>
                    </template>
                </DataTable>
            </template>
            <template #cancel></template>
            <template #action>
                <VButton color="success" outlined icon="fas fa-file-excel"
                    :disabled="!filteredDetailRows.length" @click="exportDetailExcel">
                    Export Excel
                </VButton>
                <VButton color="dark" outlined @click="closeDetail">Tutup</VButton>
            </template>
        </VModal>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import Calendar from 'primevue/calendar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import VueApexCharts from 'vue3-apexcharts'
import { useApi } from '/@src/composable/useApi'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import * as H from '/@src/utils/appHelper'
import {
    exportMonitoringSurkesDetailExcel,
    exportMonitoringSurkesExcel,
} from '/@src/utils/monitoringSurkesExcel'

type MonitoringSummary = {
    target_mapping: number
    realisasi_unik: number
    sudah_terdaftar: number
    belum_terdaftar: number
    surkes_tanpa_mapping: number
    rencana_alat_surkes: number
    realisasi_surkes: number
    sisa_surkes_belum_daftar: number
    alat_non_surkes: number
    total_registrasi_rows: number
    duplicate_registrasi_rows: number
    duplicate_mapping_rows: number
    selesai: number
    belum_selesai: number
    lingkup_mismatch: number
    progress: number
}

type MonitoringRow = {
    key: string
    status: string
    status_label: string
    unit_id: number
    namaperusahaan: string
    mapping_id: number | null
    objectalatfk: number | null
    namaproduk: string | null
    namamerk: string | null
    namatipe: string | null
    namaserialnumber: string | null
    objectlingkupfk: number | null
    lingkup_mapping: string | null
    lingkup_registrasi: string | null
    lingkup_mismatch: boolean
    registration_count: number
    duplicate_count: number
    mapping_duplicate_count: number
    selesai_count: number
    jenisorder: string[]
    nopendaftaran: string[]
    noorderalat: string[]
    last_tglregistrasi: string | null
    last_nopendaftaran: string | null
    last_noorderalat: string | null
    last_jenisorder: string | null
    last_tgl_selesai: string | null
    status_pekerjaan: string
}

type MonitoringGroup = {
    id: number | string
    label: string
    target_mapping: number
    realisasi_unik: number
    sudah_terdaftar: number
    belum_terdaftar: number
    surkes_tanpa_mapping: number
    rencana_alat_surkes: number
    realisasi_surkes: number
    sisa_surkes_belum_daftar: number
    alat_non_surkes: number
    progress: number
}

const router = useRouter()
const route = useRoute()
const apexchart = VueApexCharts

useHead({
    title: 'Monitoring Alat Surkes - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle('Monitoring Alat Surkes')
useViewWrapper().setFullWidth(true)

const emptySummary: MonitoringSummary = {
    target_mapping: 0,
    realisasi_unik: 0,
    sudah_terdaftar: 0,
    belum_terdaftar: 0,
    surkes_tanpa_mapping: 0,
    rencana_alat_surkes: 0,
    realisasi_surkes: 0,
    sisa_surkes_belum_daftar: 0,
    alat_non_surkes: 0,
    total_registrasi_rows: 0,
    duplicate_registrasi_rows: 0,
    duplicate_mapping_rows: 0,
    selesai: 0,
    belum_selesai: 0,
    lingkup_mismatch: 0,
    progress: 0,
}

const loading = ref(false)
const exporting = ref(false)
const year = ref<Date>(new Date())
const normalizeLokasiId = (value: any) => {
    if (String(value).toLowerCase() === 'gresik') return '2'
    return String(value || '1') === '2' ? '2' : '1'
}
const lokasiId = ref(normalizeLokasiId(route.query.lokasi_id))
const lokasiTabs = [
    { value: '1', label: 'Surkes Jakarta'},
    { value: '2', label: 'Surkes Gresik'},
]
const activeLokasiLabel = computed(() => lokasiId.value === '2' ? 'Gresik' : 'Jakarta')
const filterMitraId = ref<string | null>((route.query.idunit as string) || null)
const filterLingkupId = ref<string | null>(null)
const selectedStatus = ref('all')
const keyword = ref('')
const keywordTimer = ref<any>(null)

const optMitra = ref<any[]>([])
const optLingkup = ref<any[]>([])
const summary = ref<MonitoringSummary>({ ...emptySummary })
const allRows = ref<MonitoringRow[]>([])
const byUnit = ref<MonitoringGroup[]>([])
const byLingkup = ref<MonitoringGroup[]>([])
const detailOpen = ref(false)
const detailTitle = ref('Detail Alat')
const detailSubtitle = ref('')
const detailRows = ref<MonitoringRow[]>([])
const detailKeyword = ref('')

const rows = computed(() => {
    if (selectedStatus.value === 'all') return allRows.value
    return allRows.value.filter((row) => row.status === selectedStatus.value)
})

const filteredDetailRows = computed(() => {
    const search = detailKeyword.value.trim().toLowerCase()
    if (!search) return detailRows.value

    return detailRows.value.filter((row) => [
        row.status_label,
        row.namaperusahaan,
        row.namaproduk,
        row.namamerk,
        row.namatipe,
        row.namaserialnumber,
        row.lingkup_mapping,
        row.lingkup_registrasi,
        row.last_nopendaftaran,
        row.last_noorderalat,
        row.status_pekerjaan,
        ...(row.jenisorder || []),
    ].some((value) => String(value || '').toLowerCase().includes(search)))
})

const detailSurkesCount = computed(() => {
    return detailRows.value.filter((row) => row.status !== 'alat_non_surkes').length
})
const detailNonSurkesCount = computed(() => {
    return detailRows.value.filter((row) => row.status === 'alat_non_surkes').length
})
const detailRegistrationCount = computed(() => {
    return detailRows.value.reduce((total, row) => total + Number(row.registration_count || 0), 0)
})

const getYearString = () => {
    if (year.value instanceof Date) {
        return String(year.value.getFullYear())
    }

    return String(new Date().getFullYear())
}

const hasSurkesData = computed(() => summary.value.rencana_alat_surkes > 0)
const hasCompositionData = computed(() => {
    return summary.value.rencana_alat_surkes > 0 || summary.value.alat_non_surkes > 0
})

const summaryCards = computed(() => [
    {
        key: 'target',
        label: 'Rencana Alat Surkes',
        value: summary.value.rencana_alat_surkes,
        caption: 'Alat yang dimapping surkes',
        icon: 'feather:map',
        tone: 'blue',
    },
    {
        key: 'registered',
        label: 'Realisasi Surkes',
        value: summary.value.realisasi_surkes,
        caption: `${summary.value.progress}% dari rencana`,
        icon: 'feather:check-circle',
        tone: 'green',
    },
    {
        key: 'missing',
        label: 'Surkes Belum Daftar',
        value: summary.value.sisa_surkes_belum_daftar,
        caption: 'Sudah mapping, belum daftar',
        icon: 'feather:alert-circle',
        tone: 'amber',
    },
    {
        key: 'non_sukkes',
        label: 'Alat Non Surkes',
        value: summary.value.alat_non_surkes,
        caption: 'Terdaftar non surkes, tidak termapping',
        icon: 'feather:flag',
        tone: 'red',
    },
    {
        key: 'duplicate',
        label: 'Duplikasi Registrasi',
        value: summary.value.duplicate_registrasi_rows,
        caption: 'Kalibrasi/repair alat yang sama',
        icon: 'feather:copy',
        tone: 'slate',
    },
    {
        key: 'mismatch',
        label: 'Lingkup Berbeda',
        value: summary.value.lingkup_mismatch,
        caption: 'Mapping dan realisasi tidak sama',
        icon: 'feather:shuffle',
        tone: 'violet',
    },
])

const statusOptions = computed(() => [
    {
        value: 'all',
        label: 'Semua',
        count: summary.value.rencana_alat_surkes + summary.value.alat_non_surkes,
    },
    {
        value: 'sisa_surkes_belum_daftar',
        label: 'Surkes Belum Daftar',
        count: summary.value.sisa_surkes_belum_daftar,
    },
    {
        value: 'realisasi_surkes',
        label: 'Realisasi',
        count: summary.value.realisasi_surkes,
    },
    {
        value: 'alat_non_surkes',
        label: 'Non Surkes',
        count: summary.value.alat_non_surkes,
    },
])

const statusChartSeries = computed(() => [
    summary.value.realisasi_surkes,
    summary.value.sisa_surkes_belum_daftar,
])

const statusChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
        events: {
            dataPointSelection: (_event: any, _context: any, detail: any) => {
                openStatusChartDetail(detail.dataPointIndex)
            },
        },
    },
    labels: ['Realisasi Surkes', 'Surkes Belum Daftar'],
    colors: ['#16a34a', '#f59e0b'],
    legend: {
        position: 'bottom',
        formatter: (seriesName: string, options: any) => {
            return `${seriesName}: ${formatNumber(options.w.globals.series[options.seriesIndex])}`
        },
    },
    dataLabels: {
        enabled: true,
        formatter: (val: number) => `${val.toFixed(0)}%`,
    },
    stroke: { width: 0 },
    plotOptions: {
        pie: {
            donut: {
                size: '68%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Progress',
                        formatter: () => `${summary.value.progress}%`,
                    },
                },
            },
        },
    },
}))

const compositionChartSeries = computed(() => [
    summary.value.rencana_alat_surkes,
    summary.value.alat_non_surkes,
])

const compositionChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
        events: {
            dataPointSelection: (_event: any, _context: any, detail: any) => {
                openCompositionChartDetail(detail.dataPointIndex)
            },
        },
    },
    labels: ['Alat Surkes', 'Alat Non Surkes'],
    colors: ['#2563eb', '#dc2626'],
    legend: {
        position: 'bottom',
        formatter: (seriesName: string, options: any) => {
            return `${seriesName}: ${formatNumber(options.w.globals.series[options.seriesIndex])}`
        },
    },
    dataLabels: {
        enabled: true,
        formatter: (_value: number, options: any) => {
            return formatNumber(options.w.config.series[options.seriesIndex])
        },
    },
    stroke: { width: 0 },
    plotOptions: {
        pie: {
            donut: {
                size: '68%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total Alat',
                        formatter: () => formatNumber(
                            summary.value.rencana_alat_surkes + summary.value.alat_non_surkes
                        ),
                    },
                },
            },
        },
    },
}))

const unitChartHeight = computed(() => Math.max(520, byUnit.value.length * 64 + 120))
const unitChartMax = computed(() => {
    const values = byUnit.value.flatMap((unit) => [
        Number(unit.rencana_alat_surkes || 0),
        Number(unit.realisasi_surkes || 0),
        Number(unit.sisa_surkes_belum_daftar || 0),
    ])
    const highestValue = values.length ? Math.max(...values) : 0
    return highestValue > 0 ? Math.ceil(highestValue * 1.15) : 1
})

const unitChartSeries = computed(() => [
    {
        name: 'Rencana',
        data: byUnit.value.map((u) => u.rencana_alat_surkes),
    },
    {
        name: 'Realisasi',
        data: byUnit.value.map((u) => u.realisasi_surkes),
    },
    {
        name: 'Surkes Belum Daftar',
        data: byUnit.value.map((u) => u.sisa_surkes_belum_daftar),
    },
])

const unitChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
        fontFamily: 'inherit',
        foreColor: '#64748b',
        animations: { enabled: true, speed: 450 },
        events: {
            dataPointSelection: (_event: any, _context: any, detail: any) => {
                openUnitChartDetail(detail.dataPointIndex, detail.seriesIndex)
            },
        },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 5,
            barHeight: '72%',
            dataLabels: { position: 'top' },
        },
    },
    colors: ['#2563eb', '#16a34a', '#f59e0b'],
    dataLabels: {
        enabled: true,
        formatter: (value: number) => formatNumber(value),
        textAnchor: 'start',
        offsetX: 5,
        style: {
            fontSize: '12px',
            fontWeight: 700,
            colors: ['#334155'],
        },
        background: { enabled: false },
    },
    xaxis: {
        categories: byUnit.value.map((u) => u.label || '-'),
        min: 0,
        max: unitChartMax.value,
        tickAmount: 6,
        labels: {
            formatter: (value: number) => formatNumber(Math.round(Number(value || 0))),
        },
    },
    yaxis: {
        labels: {
            maxWidth: 420,
            style: { fontSize: '12px', fontWeight: 600 },
        },
    },
    grid: {
        borderColor: '#e2e8f0',
        padding: { left: 12, right: 52, top: 0, bottom: 0 },
    },
    legend: {
        position: 'top',
        horizontalAlign: 'center',
        fontSize: '13px',
        markers: { width: 11, height: 11, radius: 3 },
    },
    tooltip: { theme: 'light' },
}))

const topLingkup = computed(() => byLingkup.value.slice(0, 12))

const lingkupChartSeries = computed(() => [
    {
        name: 'Rencana Surkes',
        data: topLingkup.value.map((item) => item.rencana_alat_surkes),
    },
    {
        name: 'Surkes Belum Daftar',
        data: topLingkup.value.map((item) => item.sisa_surkes_belum_daftar),
    },
    {
        name: 'Alat Non Surkes',
        data: topLingkup.value.map((item) => item.alat_non_surkes),
    },
])

const lingkupChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
        events: {
            dataPointSelection: (_event: any, _context: any, detail: any) => {
                openLingkupChartDetail(detail.dataPointIndex, detail.seriesIndex)
            },
        },
    },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '46%' } },
    colors: ['#2563eb', '#f59e0b', '#dc2626'],
    dataLabels: { enabled: false },
    xaxis: {
        categories: topLingkup.value.map((item) => truncate(item.label, 18)),
        labels: { rotate: -20 },
    },
    legend: { position: 'top' },
    tooltip: { theme: 'light' },
}))

watch([year, filterLingkupId], () => {
    loadMonitoring()
})

watch(keyword, () => {
    clearTimeout(keywordTimer.value)
    keywordTimer.value = setTimeout(() => loadMonitoring(), 450)
})

async function initDropdown() {
    try {
        const res = await useApi().get(`/surkes/mapping-surkes-dropdown?lokasi_id=${lokasiId.value}`)
        const data = res.data ?? res
        optMitra.value = data.mitra || []
        optLingkup.value = data.lingkup || []
    } catch (err) {
        console.error(err)
        H.alert('error', 'Gagal memuat dropdown monitoring surkes')
    }
}

async function loadMonitoring() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            year: getYearString(),
            lokasi_id: lokasiId.value,
            keyword: keyword.value,
            status: 'all',
        })

        if (filterMitraId.value) params.append('mitra_id', String(filterMitraId.value))
        if (filterLingkupId.value) params.append('lingkup_id', String(filterLingkupId.value))

        const res = await useApi().get(`/surkes/monitoring-alat?${params.toString()}`)
        const data = res.data ?? res

        summary.value = { ...emptySummary, ...(data.summary || {}) }
        allRows.value = data.rows || []
        byUnit.value = data.by_unit || []
        byLingkup.value = data.by_lingkup || []
    } catch (err) {
        console.error(err)
        H.alert('error', 'Gagal memuat monitoring alat surkes')
    } finally {
        loading.value = false
    }
}

function setStatus(status: string) {
    selectedStatus.value = status
}

async function resetFilter() {
    const shouldReloadDropdown = lokasiId.value !== '1'
    year.value = new Date()
    lokasiId.value = '1'
    filterMitraId.value = null
    filterLingkupId.value = null
    selectedStatus.value = 'all'
    keyword.value = ''
    if (shouldReloadDropdown) {
        await initDropdown()
    }
    loadMonitoring()
}

function goToMapping() {
    router.push(`/module/sysadmin/mapping-alat-to-surkes?lokasi_id=${lokasiId.value}`)
}

async function switchLokasi(value: string) {
    if (lokasiId.value === value) return
    lokasiId.value = value
    filterMitraId.value = null
    selectedStatus.value = 'all'
    await initDropdown()
    await loadMonitoring()
}

function openDetail(title: string, selectedRows: MonitoringRow[], subtitle: string) {
    detailTitle.value = title
    detailSubtitle.value = subtitle
    detailRows.value = [...selectedRows]
    detailKeyword.value = ''
    detailOpen.value = true
}

function closeDetail() {
    detailOpen.value = false
    detailKeyword.value = ''
}

function openSummaryDetail(card: any) {
    let selectedRows = [...allRows.value]

    if (card.key === 'target') {
        selectedRows = selectedRows.filter((row) => row.status !== 'alat_non_surkes')
    } else if (card.key === 'registered') {
        selectedRows = selectedRows.filter((row) => row.status === 'realisasi_surkes')
    } else if (card.key === 'missing') {
        selectedRows = selectedRows.filter((row) => row.status === 'sisa_surkes_belum_daftar')
    } else if (card.key === 'non_sukkes') {
        selectedRows = selectedRows.filter((row) => row.status === 'alat_non_surkes')
    } else if (card.key === 'duplicate') {
        selectedRows = selectedRows.filter((row) => Number(row.duplicate_count || 0) > 0)
    } else if (card.key === 'mismatch') {
        selectedRows = selectedRows.filter((row) => Boolean(row.lingkup_mismatch))
    }

    openDetail(
        `${card.label} - ${activeLokasiLabel.value}`,
        selectedRows,
        `Data alat pembentuk statistik ${String(card.label).toLowerCase()} tahun ${getYearString()}.`
    )
}

function openUnitChartDetail(dataPointIndex: number, seriesIndex: number) {
    const unit = byUnit.value[dataPointIndex]
    if (!unit) return

    const series = [
        { label: 'Rencana Surkes', status: null },
        { label: 'Realisasi Surkes', status: 'realisasi_surkes' },
        { label: 'Surkes Belum Daftar', status: 'sisa_surkes_belum_daftar' },
    ][seriesIndex]
    if (!series) return

    let selectedRows = allRows.value.filter((row) => String(row.unit_id) === String(unit.id))
    selectedRows = series.status
        ? selectedRows.filter((row) => row.status === series.status)
        : selectedRows.filter((row) => row.status !== 'alat_non_surkes')

    openDetail(
        `${series.label} - ${unit.label}`,
        selectedRows,
        `Detail alat pada batang ${series.label.toLowerCase()} untuk unit ${unit.label}.`
    )
}

function openStatusChartDetail(dataPointIndex: number) {
    const item = [
        { label: 'Realisasi Surkes', status: 'realisasi_surkes' },
        { label: 'Surkes Belum Daftar', status: 'sisa_surkes_belum_daftar' },
    ][dataPointIndex]
    if (!item) return

    openDetail(
        `${item.label} - ${activeLokasiLabel.value}`,
        allRows.value.filter((row) => row.status === item.status),
        `Detail alat dari bagian donut ${item.label.toLowerCase()}.`
    )
}

function openCompositionChartDetail(dataPointIndex: number) {
    const isSurkes = dataPointIndex === 0
    const selectedRows = allRows.value.filter((row) => (
        isSurkes ? row.status !== 'alat_non_surkes' : row.status === 'alat_non_surkes'
    ))
    const label = isSurkes ? 'Alat Surkes' : 'Alat Non Surkes'

    openDetail(
        `${label} - ${activeLokasiLabel.value}`,
        selectedRows,
        `Detail alat dari komposisi ${label.toLowerCase()} tahun ${getYearString()}.`
    )
}

function rowLingkupKey(row: MonitoringRow) {
    return String(row.objectlingkupfk || row.lingkup_registrasi || 'tanpa_lingkup')
}

function openLingkupChartDetail(dataPointIndex: number, seriesIndex: number) {
    const lingkup = topLingkup.value[dataPointIndex]
    if (!lingkup) return

    const series = [
        { label: 'Rencana Surkes', status: null },
        { label: 'Surkes Belum Daftar', status: 'sisa_surkes_belum_daftar' },
        { label: 'Alat Non Surkes', status: 'alat_non_surkes' },
    ][seriesIndex]
    if (!series) return

    let selectedRows = allRows.value.filter((row) => rowLingkupKey(row) === String(lingkup.id))
    selectedRows = series.status
        ? selectedRows.filter((row) => row.status === series.status)
        : selectedRows.filter((row) => row.status !== 'alat_non_surkes')

    openDetail(
        `${series.label} - Lingkup ${lingkup.label}`,
        selectedRows,
        `Detail alat pada lingkup ${lingkup.label} untuk seri ${series.label.toLowerCase()}.`
    )
}

function exportDetailExcel() {
    if (!filteredDetailRows.value.length) {
        H.alert('warning', 'Tidak ada data detail alat untuk diekspor')
        return
    }

    exportMonitoringSurkesDetailExcel({
        title: detailTitle.value,
        subtitle: detailSubtitle.value,
        year: getYearString(),
        lokasi: activeLokasiLabel.value,
        keyword: detailKeyword.value.trim() || '-',
        rows: filteredDetailRows.value,
    })
}

function exportExcel() {
    if (!rows.value.length && !byUnit.value.length) {
        H.alert('warning', 'Tidak ada data untuk diekspor')
        return
    }

    exporting.value = true
    try {
        const unitLabel = optMitra.value.find((item) => String(item.value) === String(filterMitraId.value))?.label
            || 'Semua unit'
        const lingkupLabel = optLingkup.value.find((item) => String(item.value) === String(filterLingkupId.value))?.label
            || 'Semua lingkup'
        const statusLabel = statusOptions.value.find((item) => item.value === selectedStatus.value)?.label || 'Semua'

        exportMonitoringSurkesExcel({
            year: getYearString(),
            lokasi: activeLokasiLabel.value,
            filters: {
                unit: unitLabel,
                lingkup: lingkupLabel,
                status: statusLabel,
                keyword: keyword.value || '-',
            },
            summary: summary.value,
            byUnit: byUnit.value,
            rows: rows.value,
        })
    } finally {
        exporting.value = false
    }
}

function statusClass(status: string) {
    if (status === 'realisasi_surkes') return 'is-ok'
    if (status === 'sisa_surkes_belum_daftar') return 'is-waiting'
    if (status === 'alat_non_surkes') return 'is-danger'
    return ''
}

function formatNumber(value: any) {
    return Number(value || 0).toLocaleString('id-ID')
}

function formatDate(value: string | null) {
    if (!value) return '-'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '-'
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

function truncate(value: string, max = 24) {
    if (!value) return '-'
    return value.length > max ? `${value.slice(0, max - 1)}...` : value
}

onMounted(async () => {
    await initDropdown()
    await loadMonitoring()
})
</script>

<style lang="scss" scoped>
@import '/@src/scss/abstracts/all';

.surkes-monitor-page {
    padding: 1rem;
}

.surkes-header-card {
    border: 1px solid #e5e7eb;
}

.header-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1.5rem;
    align-items: start;

    h1 {
        margin: 0.25rem 0 0.35rem;
        font-size: 1.85rem;
        font-weight: 800;
        color: #1f2937;
        letter-spacing: 0;
    }

    p {
        max-width: 720px;
        margin: 0;
        color: #64748b;
        line-height: 1.45;
    }
}

.eyebrow {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    background: #eef2ff;
    color: #3730a3;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.header-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    justify-content: flex-end;
}

.surkes-location-tabs {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    margin-top: 1.25rem;
    padding: 0.45rem;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    background: #f8fafc;
}

.surkes-tab-button {
    display: grid;
    gap: 0.1rem;
    min-width: 180px;
    padding: 0.7rem 1rem;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s ease;

    span {
        font-weight: 800;
        color: #334155;
    }

    small {
        font-size: 0.72rem;
        color: #94a3b8;
    }

    &.is-active {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.12);

        span {
            color: #1d4ed8;
        }
    }
}

.filter-bar {
    display: grid;
    grid-template-columns: 110px minmax(220px, 1fr) minmax(220px, 1fr) minmax(260px, 1.2fr) auto;
    gap: 0.85rem;
    align-items: end;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eef2f7;
}

.filter-reset {
    display: flex;
    align-items: end;
    padding-bottom: 0.75rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.9rem;
    margin: 1rem 0;
}

.summary-card {
    display: flex;
    gap: 0.8rem;
    min-height: 118px;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);

    span,
    small {
        display: block;
    }

    span {
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    strong {
        display: block;
        margin: 0.2rem 0;
        font-size: 1.75rem;
        line-height: 1;
        color: #111827;
    }

    small {
        color: #94a3b8;
        line-height: 1.25;
    }
}

.summary-card.is-drilldown {
    cursor: pointer;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;

    &:hover,
    &:focus-visible {
        border-color: #93b4f5;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.12);
        outline: none;
        transform: translateY(-2px);
    }
}

.summary-icon {
    display: grid;
    place-items: center;
    flex: 0 0 42px;
    width: 42px;
    height: 42px;
    border-radius: 8px;
    color: #fff;
    font-size: 1.15rem;
}

.summary-card.is-blue .summary-icon {
    background: #2563eb;
}

.summary-card.is-green .summary-icon {
    background: #16a34a;
}

.summary-card.is-amber .summary-icon {
    background: #f59e0b;
}

.summary-card.is-red .summary-icon {
    background: #dc2626;
}

.summary-card.is-slate .summary-icon {
    background: #475569;
}

.summary-card.is-violet .summary-icon {
    background: #7c3aed;
}

.chart-row {
    margin-top: 0;
}

.chart-card {
    height: 100%;
    border: 1px solid #e5e7eb;
}

.unit-overview-card {
    padding: 1.25rem 1.4rem 1rem;
}

.is-interactive-chart {
    transition: border-color 0.18s ease, box-shadow 0.18s ease;

    &:hover {
        border-color: #bfcef0;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.07);
    }
}

:deep(.apexcharts-bar-area),
:deep(.apexcharts-pie-area) {
    cursor: pointer;
}

.chart-title,
.table-toolbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.chart-title {
    margin-bottom: 0.5rem;

    h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #1f2937;
    }

    span {
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 700;
    }

    p {
        margin: 0.25rem 0 0;
        color: #94a3b8;
        font-size: 0.82rem;
    }
}

:deep(.unit-overview-card .apexcharts-data-label) {
    paint-order: stroke;
    stroke: rgba(255, 255, 255, 0.92);
    stroke-width: 3px;
    stroke-linejoin: round;
}

.empty-chart {
    display: grid;
    min-height: 240px;
    place-items: center;
    color: #94a3b8;
    border: 1px dashed #dbe3ef;
    border-radius: 8px;
    background: #f8fafc;
}

.detail-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(115px, 0.65fr)) minmax(280px, 1.5fr);
    gap: 0.75rem;
    align-items: center;
    margin-bottom: 0.75rem;
    padding: 0.85rem 1rem;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    background: #f8fafc;

    span {
        display: block;
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 600;
    }

    strong {
        display: block;
        margin-top: 0.15rem;
        color: #1f2937;
        font-size: 1.05rem;
    }
}

.detail-search {
    margin: 0;
}

.detail-subtitle {
    margin: 0 0 0.85rem;
    color: #64748b;
    font-size: 0.78rem;
}

.empty-detail {
    padding: 2rem;
    color: #94a3b8;
    text-align: center;
}

:deep(.detail-table .p-datatable-thead > tr > th) {
    border-color: #e2e8f0;
    background: #f8fafc;
    color: #475569;
    font-size: 0.72rem;
}

:deep(.detail-table .p-datatable-tbody > tr > td) {
    border-color: #e2e8f0;
    font-size: 0.76rem;
    vertical-align: top;
}

.table-card {
    margin-top: 1rem;
    border: 1px solid #e5e7eb;
}

.table-toolbar {
    margin-bottom: 1rem;

    h3 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #1f2937;
    }

    p {
        margin: 0.25rem 0 0;
        color: #64748b;
    }
}

.status-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: flex-end;
}

.status-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    height: 38px;
    padding: 0 0.85rem;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    background: #fff;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;

    span {
        font-size: 0.82rem;
        font-weight: 700;
    }

    strong {
        color: #1f2937;
    }

    &.is-active {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }
}

:deep(.monitoring-table) {
    .p-datatable-thead > tr > th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.72rem;
        text-transform: uppercase;
    }

    .p-datatable-tbody > tr > td {
        vertical-align: top;
        font-size: 0.86rem;
    }
}

.monitor-status,
.work-status,
.mini-alert {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    border-radius: 999px;
    padding: 0.25rem 0.6rem;
    font-size: 0.72rem;
    font-weight: 800;
}

.monitor-status.is-ok {
    background: #dcfce7;
    color: #166534;
}

.monitor-status.is-waiting {
    background: #fef3c7;
    color: #92400e;
}

.monitor-status.is-danger {
    background: #fee2e2;
    color: #991b1b;
}

.mini-alert {
    display: flex;
    background: #ede9fe;
    color: #5b21b6;
}

.unit-name,
.alat-cell strong {
    display: block;
    color: #1f2937;
}

.muted,
.alat-cell small,
.realisasi-cell small {
    display: block;
    color: #64748b;
    line-height: 1.45;
}

.realisasi-cell {
    display: grid;
    gap: 0.2rem;

    div:first-child {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
}

.order-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.25rem;
}

.work-status {
    background: #f1f5f9;
    color: #475569;

    &.is-done {
        background: #dcfce7;
        color: #166534;
    }
}

.skeleton-card {
    height: 132px;
    border-radius: 8px;
    background: linear-gradient(90deg, #eef2f7 25%, #f8fafc 37%, #eef2f7 63%);
    background-size: 400% 100%;
    animation: shimmer 1.2s ease infinite;
}

:global(.is-dark) {
    .surkes-header-card,
    .summary-card,
    .chart-card,
    .table-card {
        border-color: #334155;
        background: #111827;
    }

    .header-grid h1,
    .summary-card strong,
    .chart-title h3,
    .table-toolbar h3,
    .unit-name,
    .alat-cell strong {
        color: #e5e7eb;
    }

    .header-grid p,
    .summary-card span,
    .summary-card small,
    .chart-title span,
    .chart-title p,
    .table-toolbar p,
    .muted,
    .alat-cell small,
    .realisasi-cell small,
    .detail-summary span,
    .detail-subtitle {
        color: #94a3b8;
    }

    .surkes-location-tabs,
    .status-tab,
    .empty-chart,
    .detail-summary {
        border-color: #334155;
        background: #172033;
    }

    .detail-summary strong {
        color: #e5e7eb;
    }

    .surkes-tab-button span,
    .status-tab strong {
        color: #e2e8f0;
    }

    .surkes-tab-button.is-active,
    .status-tab.is-active {
        border-color: #3b82f6;
        background: #1e3a5f;
    }

    :deep(.apexcharts-text),
    :deep(.apexcharts-legend-text) {
        fill: #cbd5e1 !important;
        color: #cbd5e1 !important;
    }

    :deep(.unit-overview-card .apexcharts-data-label) {
        fill: #f8fafc !important;
        stroke: rgba(15, 23, 42, 0.94);
    }

    :deep(.apexcharts-gridline) {
        stroke: #334155;
    }

    :deep(.detail-table .p-datatable-thead > tr > th) {
        border-color: #334155;
        background: #172033;
        color: #94a3b8;
    }

    :deep(.detail-table .p-datatable-tbody > tr),
    :deep(.detail-table .p-datatable-tbody > tr > td) {
        border-color: #334155;
        background: #111827;
        color: #e5e7eb;
    }
}

@keyframes shimmer {
    0% {
        background-position: 100% 0;
    }

    100% {
        background-position: 0 0;
    }
}

@media (max-width: 1280px) {
    .summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .filter-bar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .detail-summary {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .detail-search {
        grid-column: 1 / -1;
    }
}

@media (max-width: 768px) {
    .surkes-monitor-page {
        padding: 0.5rem;
    }

    .header-grid,
    .table-toolbar {
        grid-template-columns: 1fr;
        display: grid;
    }

    .header-actions,
    .status-tabs {
        justify-content: flex-start;
    }

    .summary-grid,
    .filter-bar,
    .detail-summary {
        grid-template-columns: 1fr;
    }

    .detail-search {
        grid-column: auto;
    }
}
</style>
