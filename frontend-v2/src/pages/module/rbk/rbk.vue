<template>
    <div class="rbk-wrap">

        <VCard class="mb-5 rbk-header-card">
            <div class="rbk-header">
                <div class="header-left">
                    <h3 class="title is-4 mb-1">RBK Monitoring </h3>
                    <p class="subtitle is-6 has-text-grey mt-1">
                        Monitoring Rencana Biaya Kerja (Manpower, Material, Mobilisasi)
                    </p>
                </div>

                <div class="rbk-actions">
                    <VButton icon="feather:plus-circle" color="primary" @click="openAddUnitModal">
                        Input Unit RBK
                    </VButton>
                    

                    <div class="field mr-2 mb-0" style="width: 220px;">
                        <VControl icon="feather:search">
                            <input v-model="searchKeyword" class="input" placeholder="Cari Unit..."
                                @keyup.enter="loadUnits" />
                        </VControl>
                    </div>

                    <div class="field mr-2 mb-0">
                        <VControl>
                            <div class="select">
                                <select v-model="sortBy" @change="loadUnits">
                                    <option value="total_rencana">Urut: Total Rencana</option>
                                    <option value="total_realisasi">Urut: Total Realisasi</option>
                                    <option value="progress">Urut: Progress (%)</option>
                                </select>
                            </div>
                        </VControl>
                    </div>

                    <div class="year-picker-wrapper mr-2">
                        <Calendar v-model="year" view="year" dateFormat="yy" placeholder="Tahun"
                            inputClass="p-inputtext-lg" style="width: 100px;" />
                    </div>

                    <VButton icon="feather:refresh-cw" color="info" :loading="loading" @click="loadUnits">
                        Refresh
                    </VButton>

                    <VButton icon="feather:file-text" color="danger" :loading="exportingPdf"
                        @click="exportPdfCurrentTab">
                        PDF {{ activeTab === 'jakarta' ? 'Jakarta' : 'Gresik' }}
                    </VButton>

                    <VButton icon="feather:layers" color="warning" :loading="exportingPdfAll"
                        @click="exportPdfAllLokasi">
                        PDF Semua
                    </VButton>

                    <VButton icon="feather:file" color="success" :loading="exportingExcelCurrent"
                        @click="exportExcelCurrentTab">
                        Excel {{ activeTab === 'jakarta' ? 'Jakarta' : 'Gresik' }}
                    </VButton>

                    <VButton icon="feather:grid" color="success" outlined :loading="exportingExcelAll"
                        @click="exportExcelAllLokasi">
                        Excel Semua
                    </VButton>
                </div>
            </div>
        </VCard>

        <div class="tabs-wrapper">
            <VTabs slider align="centered" v-model:selected="activeTab" :tabs="[
                { label: 'Jakarta', value: 'jakarta' },
                { label: 'Gresik', value: 'gresik' }
            ]">
                <template #tab="{ activeValue }">

                    <div v-if="loading" class="columns is-multiline mt-2">
                        <div v-for="i in 3" :key="i" class="column is-12-mobile is-6-tablet is-4-desktop">
                            <div class="rbk-skeleton-card" />
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

                                <div v-else v-for="u in units" :key="u.unit_id" class="column is-12-mobile is-6-tablet">
                                    <div class="card rbk-unit-card">
                                        <div class="card-image header-dark">
                                            <div class="chart-wrapper">
                                                <apexchart height="90" width="100%" :type="'bar'"
                                                    :options="getTotalChartOptions(u)"
                                                    :series="getTotalChartSeries(u)" />
                                            </div>
                                        </div>

                                        <div class="card-content">
                                            <div class="unit-info-block mb-4">
                                                <h3 class="title is-5 is-bold mb-3 has-text-dark"
                                                    :title="u.namaperusahaan">
                                                    {{ u.namaperusahaan }}
                                                </h3>

                                                <div class="detail-rows">
                                                    <div class="detail-item">
                                                        <span class="lbl">No Surat</span>
                                                        <span class="val">: {{ u.no_surat || '-' }}</span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span class="lbl">No PRK</span>
                                                        <span class="val">: {{ u.no_prk || '-' }}</span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span class="lbl">Cost Code</span>
                                                        <span class="val is-code">: {{ u.cost_code || '-' }}</span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span class="lbl">Jadwal Kalibrasi</span>
                                                        <span class="val has-text-primary is-bold">
                                                            : {{ u.jadwal_kalibrasi || '-' }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <p class="subtitle is-7 mt-3 mb-0">
                                                    Progress Anggaran:
                                                    <span class="has-text-success is-bold">
                                                        {{ u.summary?.progress ?? 0 }}%
                                                    </span>
                                                    <span v-if="!u.has_rbk"
                                                        class="tag is-warning is-light is-tiny ml-2">
                                                        Belum ada RBK
                                                    </span>
                                                </p>

                                            </div>

                                            <div class="alat-monitoring-grid mb-4">
                                                <section class="alat-monitoring-card is-surkes">
                                                    <div class="alat-monitoring-head">
                                                        <div>
                                                            <span class="alat-monitoring-kicker">Monitoring Alat</span>
                                                            <h4>Surkes</h4>
                                                        </div>
                                                        <span class="alat-progress-badge">
                                                            {{ u.alat_monitoring?.surkes?.progress ?? 0 }}%
                                                        </span>
                                                    </div>

                                                    <div class="alat-stat-grid">
                                                        <button type="button" class="alat-stat is-plan"
                                                            title="Lihat daftar alat rencana Surkes"
                                                            @click.stop="openAlatMonitoringModal(u, 'rencana')">
                                                            <span>Rencana</span>
                                                            <strong>{{ u.alat_monitoring?.surkes?.rencana ?? 0 }}</strong>
                                                        </button>
                                                        <button type="button" class="alat-stat is-realized"
                                                            title="Lihat daftar alat yang sudah direalisasikan"
                                                            @click.stop="openAlatMonitoringModal(u, 'realisasi')">
                                                            <span>Realisasi</span>
                                                            <strong>{{ u.alat_monitoring?.surkes?.realisasi ?? 0 }}</strong>
                                                        </button>
                                                        <button type="button" class="alat-stat is-pending"
                                                            title="Lihat daftar alat yang belum didaftarkan"
                                                            @click.stop="openAlatMonitoringModal(u, 'belum')">
                                                            <span>Belum</span>
                                                            <strong>{{ u.alat_monitoring?.surkes?.belum ?? 0 }}</strong>
                                                        </button>
                                                    </div>

                                                    <div class="alat-progress-track" aria-hidden="true">
                                                        <span
                                                            :style="{ width: `${Math.min(Math.max(Number(u.alat_monitoring?.surkes?.progress ?? 0), 0), 100)}%` }" />
                                                    </div>
                                                    <p class="alat-progress-copy">
                                                        Realisasi dari rencana alat Surkes
                                                    </p>
                                                </section>

                                                <section class="alat-monitoring-card is-non-surkes is-clickable"
                                                    role="button" tabindex="0" title="Lihat daftar alat Non-Surkes"
                                                    @click="openAlatMonitoringModal(u, 'non_surkes')"
                                                    @keydown.enter.prevent="openAlatMonitoringModal(u, 'non_surkes')"
                                                    @keydown.space.prevent="openAlatMonitoringModal(u, 'non_surkes')">
                                                    <div class="alat-monitoring-head">
                                                        <div>
                                                            <span class="alat-monitoring-kicker">Monitoring Alat</span>
                                                            <h4>Non-Surkes</h4>
                                                        </div>
                                                        <i class="iconify" data-icon="feather:box"></i>
                                                    </div>
                                                    <div class="non-surkes-value">
                                                        <strong>{{ u.alat_monitoring?.non_surkes?.jumlah ?? 0 }}</strong>
                                                        <span>alat terdaftar</span>
                                                    </div>
                                                    <p class="alat-progress-copy">Ditampilkan terpisah dari Surkes</p>
                                                </section>
                                            </div>

                                            <div class="rbk-kategori-grid">
                                                <div class="rbk-kat-box">
                                                    <p class="heading-kat">MANPOWER</p>
                                                    <div class="val-row">
                                                        <span class="l">R:</span>
                                                        <span class="r">
                                                            {{ formatRupiah(u.summary?.kategori?.manpower?.rencana ?? 0)
                                                            }}
                                                        </span>
                                                    </div>
                                                    <div class="val-row">
                                                        <span class="l">A:</span>
                                                        <span class="r has-text-grey">
                                                            {{ formatRupiah(u.summary?.kategori?.manpower?.realisasi ??
                                                            0) }}
                                                        </span>
                                                    </div>
                                                    <div class="val-row highlight">
                                                        <span class="l">Sisa:</span>
                                                        <span class="r"
                                                            :class="{ 'has-text-danger': (u.summary?.kategori?.manpower?.sisa ?? 0) < 0 }">
                                                            {{ formatRupiah(u.summary?.kategori?.manpower?.sisa ?? 0) }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="rbk-kat-box">
                                                    <p class="heading-kat">MATERIAL</p>
                                                    <div class="val-row">
                                                        <span class="l">R:</span>
                                                        <span class="r">
                                                            {{ formatRupiah(u.summary?.kategori?.material?.rencana ?? 0)
                                                            }}
                                                        </span>
                                                    </div>
                                                    <div class="val-row">
                                                        <span class="l">A:</span>
                                                        <span class="r has-text-grey">
                                                            {{ formatRupiah(u.summary?.kategori?.material?.realisasi ??
                                                            0) }}
                                                        </span>
                                                    </div>
                                                    <div class="val-row highlight">
                                                        <span class="l">Sisa:</span>
                                                        <span class="r"
                                                            :class="{ 'has-text-danger': (u.summary?.kategori?.material?.sisa ?? 0) < 0 }">
                                                            {{ formatRupiah(u.summary?.kategori?.material?.sisa ?? 0) }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="rbk-kat-box">
                                                    <p class="heading-kat">MOBILISASI</p>
                                                    <div class="val-row">
                                                        <span class="l">R:</span>
                                                        <span class="r">
                                                            {{ formatRupiah(u.summary?.kategori?.mobilisasi?.rencana ??
                                                            0) }}
                                                        </span>
                                                    </div>
                                                    <div class="val-row">
                                                        <span class="l">A:</span>
                                                        <span class="r has-text-grey">
                                                            {{ formatRupiah(u.summary?.kategori?.mobilisasi?.realisasi
                                                            ?? 0) }}
                                                        </span>
                                                    </div>
                                                    <div class="val-row highlight">
                                                        <span class="l">Sisa:</span>
                                                        <span class="r"
                                                            :class="{ 'has-text-danger': (u.summary?.kategori?.mobilisasi?.sisa ?? 0) < 0 }">
                                                            {{ formatRupiah(u.summary?.kategori?.mobilisasi?.sisa ?? 0)
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="content-body mt-4">
                                                <div class="level is-mobile has-text-centered is-stats">
                                                    <div class="level-item">
                                                        <div>
                                                            <p class="heading">Total Rencana</p>
                                                            <p class="title is-6">
                                                                {{ formatShortRupiah(u.summary?.total_rencana ?? 0) }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="level-item">
                                                        <div>
                                                            <p class="heading">Realisasi</p>
                                                            <p class="title is-6 has-text-success">
                                                                {{ formatShortRupiah(u.summary?.total_realisasi ?? 0) }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="level-item">
                                                        <div>
                                                            <p class="heading">Sisa</p>
                                                            <p class="title is-6" :class="[
                                                                (u.summary?.total_sisa ?? 0) < 0
                                                                    ? 'has-text-danger'
                                                                    : 'has-text-warning-dark'
                                                            ]">
                                                                {{ formatShortRupiah(u.summary?.total_sisa ?? 0) }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                class="card-footer-action is-flex is-justify-content-end pt-2 border-top">
                                                <a class="action-link mr-4" tabindex="0" @click="openRbkModal(u)">
                                                    RBK
                                                    <i class="iconify" data-icon="feather:edit-2"></i>
                                                </a>
                                                <a class="action-link" tabindex="0" @click="openRealisasiModal(u)">
                                                    Detail PBJ
                                                    <i class="iconify" data-icon="feather:file-text"></i>
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
                                            Total RBK (Rencana) per Unit
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
                </template>
            </VTabs>
        </div>

        <!-- DETAIL ALAT PEMBENTUK MONITORING -->
        <div v-if="isAlatMonitoringModalOpen" class="rbk-hybrid-overlay" role="dialog" aria-modal="true"
            :aria-label="alatMonitoringModalTitle">
            <button class="rbk-hybrid-backdrop" aria-label="Tutup modal detail alat"
                @click="closeAlatMonitoringModal"></button>
            <article class="rbk-hybrid-dialog rbk-alat-dialog"
                :class="{ 'is-fullscreen': isAlatMonitoringFullscreen }">
                <header class="rbk-hybrid-header rbk-alat-header">
                    <div class="rbk-hybrid-title-wrap">
                        <span class="rbk-hybrid-title-icon">
                            <i class="iconify" data-icon="feather:activity"></i>
                        </span>
                        <div>
                            <p class="rbk-hybrid-eyebrow">Detail Monitoring Alat</p>
                            <h2>{{ alatMonitoringModalTitle }}</h2>
                            <p>{{ selectedAlatMonitoringUnit?.namaperusahaan || '-' }} · Tahun {{ getYearString() }}</p>
                        </div>
                    </div>
                    <div class="rbk-hybrid-header-actions">
                        <button class="rbk-excel-btn" type="button"
                            :disabled="exportingAlatMonitoring || loadingAlatMonitoring || !filteredAlatMonitoringRows.length"
                            @click="exportAlatMonitoringExcel">
                            <i class="iconify" :class="{ 'fa-spin': exportingAlatMonitoring }"
                                :data-icon="exportingAlatMonitoring ? 'feather:loader' : 'feather:download'"></i>
                            <span>{{ exportingAlatMonitoring ? 'Membuat...' : 'Export Excel' }}</span>
                        </button>
                        <button class="rbk-modal-icon-btn" type="button"
                            :title="isAlatMonitoringFullscreen ? 'Pulihkan ukuran' : 'Perbesar penuh'"
                            @click="isAlatMonitoringFullscreen = !isAlatMonitoringFullscreen">
                            <i class="iconify"
                                :data-icon="isAlatMonitoringFullscreen ? 'feather:minimize-2' : 'feather:maximize-2'"></i>
                        </button>
                        <button class="rbk-modal-icon-btn is-close" type="button" title="Tutup"
                            @click="closeAlatMonitoringModal">
                            <i class="iconify" data-icon="feather:x"></i>
                        </button>
                    </div>
                </header>

                <section class="rbk-hybrid-body rbk-alat-body">
                    <div class="rbk-alat-summary">
                        <div>
                            <strong>Daftar alat sesuai angka yang diklik</strong>
                            <p>{{ alatMonitoringModalSubtitle }}</p>
                        </div>
                        <span class="rbk-alat-count">
                            {{ loadingAlatMonitoring ? 'Memuat data...' : `${alatMonitoringRows.length} alat ditemukan` }}
                        </span>
                    </div>

                    <div class="rbk-alat-toolbar">
                        <div class="rbk-alat-mini-stats">
                            <span><b>{{ alatMonitoringRows.length }}</b> total alat</span>
                            <span class="is-success"><b>{{ alatMonitoringSelesai }}</b> selesai</span>
                            <span class="is-warning"><b>{{ alatMonitoringBelumSelesai }}</b> belum selesai</span>
                        </div>
                        <VField class="rbk-alat-search">
                            <VControl icon="feather:search">
                                <VInput v-model="alatMonitoringKeyword"
                                    placeholder="Cari alat, SN, nomor order, lingkup, atau pelaksana..." />
                            </VControl>
                        </VField>
                    </div>

                    <div v-if="loadingAlatMonitoring" class="rbk-modal-loading">
                        <i class="iconify fa-spin" data-icon="feather:loader"></i>
                        <span>Memuat detail dan progres alat...</span>
                    </div>
                    <DataTable v-else :value="filteredAlatMonitoringRows" :rows="15"
                        :rowsPerPageOptions="[15, 30, 50, 100]" paginator showGridlines stripedRows scrollable
                        scrollHeight="58vh" responsiveLayout="scroll" class="p-datatable-sm rbk-alat-table"
                        tableStyle="min-width: 118rem"
                        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        currentPageReportTemplate="Menampilkan {first} sampai {last} dari {totalRecords} alat">
                        <template #empty>Tidak ada alat yang sesuai dengan kategori atau pencarian.</template>
                        <Column header="No" style="width: 4rem">
                            <template #body="{ index }">{{ index + 1 }}</template>
                        </Column>
                        <Column field="namaproduk" header="Nama Alat" sortable style="min-width: 17rem">
                            <template #body="{ data }">
                                <strong class="rbk-alat-name">{{ data.namaproduk || 'Nama alat tidak tersedia' }}</strong>
                            </template>
                        </Column>
                        <Column field="namaserialnumber" header="Serial Number" sortable style="min-width: 11rem" />
                        <Column field="last_noorderalat" header="No. Order Alat" sortable style="min-width: 11rem" />
                        <Column field="namamerk" header="Merk" sortable style="min-width: 9rem" />
                        <Column field="namatipe" header="Tipe" sortable style="min-width: 9rem" />
                        <Column field="last_jenisorder" header="Jenis Order" sortable style="min-width: 8rem" />
                        <Column header="Ruang Lingkup" style="min-width: 13rem">
                            <template #body="{ data }">
                                <strong class="rbk-alat-name">{{ data.lingkup_mapping || '-' }}</strong>
                                <small v-if="data.lingkup_registrasi" class="rbk-alat-muted">
                                    Daftar: {{ data.lingkup_registrasi }}
                                </small>
                            </template>
                        </Column>
                        <Column field="pelaksana" header="Pelaksana" sortable style="min-width: 11rem" />
                        <Column field="lokasi" header="ULAB" sortable style="min-width: 7rem" />
                        <Column field="status_pekerjaan" header="Status" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span :class="['rbk-work-badge', data.status_pekerjaan === 'Selesai' ? 'is-done' : '']">
                                    {{ data.status_pekerjaan || '-' }}
                                </span>
                            </template>
                        </Column>
                        <Column field="progress_persen" header="Progres" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <div class="rbk-row-progress">
                                    <div><span :style="{ width: `${Math.min(Math.max(Number(data.progress_persen || 0), 0), 100)}%` }"></span></div>
                                    <strong>{{ Number(data.progress_persen || 0) }}%</strong>
                                </div>
                            </template>
                        </Column>
                        <Column field="status_terakhir" header="Posisi / Status Terakhir" sortable
                            style="min-width: 19rem">
                            <template #body="{ data }">
                                <span :class="['rbk-last-status', data.status_pekerjaan === 'Selesai' ? 'is-done' : '']">
                                    {{ data.status_terakhir || data.status_label || '-' }}
                                </span>
                            </template>
                        </Column>
                        <Column field="status_pengambilan" header="Pengambilan" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <span :class="['rbk-pickup-badge', data.status_pengambilan === 'Sudah Diambil' ? 'is-picked' : '']">
                                    {{ data.status_pengambilan || '-' }}
                                </span>
                            </template>
                        </Column>
                        <Column field="last_tglregistrasi" header="Registrasi Terakhir" sortable style="min-width: 11rem">
                            <template #body="{ data }">{{ formatAlatMonitoringDate(data.last_tglregistrasi) }}</template>
                        </Column>
                    </DataTable>
                </section>
            </article>
        </div>

        <!-- MODAL TAMBAH UNIT RBK -->
        <div :class="['modal', { 'is-active': isAddUnitModalOpen }]">
            <div class="modal-background" @click="closeAddUnitModal"></div>
            <div class="modal-card add-unit-modal">
                <header class="modal-card-head">
                    <div>
                        <p class="modal-card-title">Input Unit RBK</p>
                        <p class="modal-card-subtitle">
                            Pilih unit yang belum terdaftar, lalu tentukan lokasi RBK-nya.
                        </p>
                    </div>
                    <button class="delete" aria-label="close" :disabled="savingUnit"
                        @click="closeAddUnitModal"></button>
                </header>

                <section class="modal-card-body">
                    <div class="field">
                        <label class="label">Unit</label>
                        <div class="control">
                            <Multiselect v-model="addUnitForm.unit_id" :options="unitOptions"
                                placeholder="Cari dan pilih unit..." :searchable="true" :loading="loadingUnitOptions"
                                :disabled="loadingUnitOptions || savingUnit" noOptionsText="Tidak ada unit tersedia"
                                noResultsText="Unit tidak ditemukan" />
                        </div>
                        <p class="help">Hanya unit aktif yang belum menjadi unit RBK yang ditampilkan.</p>
                    </div>

                    <div class="field mt-5">
                        <label class="label">Lokasi RBK</label>
                        <div class="location-options">
                            <label :class="['location-option', { 'is-selected': addUnitForm.lokasi_id === 1 }]">
                                <input v-model="addUnitForm.lokasi_id" type="radio" :value="1"
                                    :disabled="savingUnit" />
                                <span class="location-icon">
                                    <i class="iconify" data-icon="feather:map-pin"></i>
                                </span>
                                <span>
                                    <strong>Jakarta</strong>
                                    <small>Lokasi Surkes 1</small>
                                </span>
                            </label>

                            <label :class="['location-option', { 'is-selected': addUnitForm.lokasi_id === 2 }]">
                                <input v-model="addUnitForm.lokasi_id" type="radio" :value="2"
                                    :disabled="savingUnit" />
                                <span class="location-icon">
                                    <i class="iconify" data-icon="feather:map-pin"></i>
                                </span>
                                <span>
                                    <strong>Gresik</strong>
                                    <small>Lokasi Surkes 2</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </section>

                <footer class="modal-card-foot is-justify-content-flex-end">
                    <VButton :disabled="savingUnit" @click="closeAddUnitModal">Batal</VButton>
                    <VButton color="primary" icon="feather:save" :loading="savingUnit"
                        :disabled="!addUnitForm.unit_id || loadingUnitOptions" @click="saveUnitRbk">
                        Simpan Unit RBK
                    </VButton>
                </footer>
            </div>
        </div>

        <!-- MODAL RBK -->
        <div :class="['modal', { 'is-active': isRbkModalOpen }]">
            <div class="modal-background" @click="closeRbkModal"></div>
            <div class="modal-card modal-wide">
                <header class="modal-card-head">
                    <p class="modal-card-title">
                        RBK: {{ selectedUnit?.namaperusahaan }} ({{ getYearString() }} -
                        {{ activeTab === 'jakarta' ? 'Jakarta' : 'Gresik' }})
                    </p>
                    <button class="delete" aria-label="close" @click="closeRbkModal"></button>
                </header>

                <section class="modal-card-body">

                    <div class="columns">
                        <div class="column is-3">
                            <div class="field">
                                <label class="label">Judul RBK</label>
                                <div class="control">
                                    <input class="input" v-model="rbkForm.judul"
                                        placeholder="Contoh: RBK Kalibrasi Non Mechanical - UP Arun" />
                                </div>
                            </div>
                        </div>

                        <div class="column is-3">
                            <div class="field">
                                <label class="label">No Surat</label>
                                <div class="control">
                                    <input class="input" v-model="rbkForm.no_surat"
                                        placeholder="Contoh: RBK Kalibrasi Non Mechanical - UP Arun" />
                                </div>
                            </div>
                        </div>

                        <div class="column is-2">
                            <div class="field">
                                <label class="label">No PRK</label>
                                <div class="control">
                                    <input class="input" v-model="rbkForm.no_prk"
                                        placeholder="Contoh: RBK Kalibrasi Non Mechanical - UP Arun" />
                                </div>
                            </div>
                        </div>

                        <div class="column is-2">
                            <div class="field">
                                <label class="label">COST CODE</label>
                                <div class="control">
                                    <input class="input" v-model="rbkForm.cost_code"
                                        placeholder="Contoh: RBK Kalibrasi Non Mechanical - UP Arun" />
                                </div>
                            </div>
                        </div>

                        <div class="column is-2">
                            <div class="field">
                                <label class="label">Jadwal Kalibrasi</label>
                                <div class="control">
                                    <input class="input" v-model="rbkForm.jadwal_kalibrasi"
                                        placeholder="Contoh: Januari - Desember / SMT1/SMT2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="rbkHeaderInfo"
                        class="is-flex is-justify-content-space-between is-align-items-center mb-2">
                        <small class="has-text-grey">
                            Dibuat oleh:
                            <strong>{{ rbkHeaderInfo?.created_by_nama || rbkHeaderInfo?.created_by || '-' }}</strong>
                            <span v-if="rbkHeaderInfo?.created_at" class="ml-2">
                                ({{ formatDate(rbkHeaderInfo.created_at) }})
                            </span>
                        </small>
                        <small class="has-text-grey-light">
                            No. RBK: {{ rbkHeaderInfo?.norec || '-' }}
                        </small>
                    </div>

                    <hr class="dropdown-divider my-3" />

                    <div class="rbk-section">
                        <div class="rbk-section-title">
                            <strong>Manpower</strong>
                            <VButton color="primary" size="small" @click="addItem('manpower')">
                                + Tambah
                            </VButton>
                        </div>

                        <div class="table-container">
                            <table class="table is-fullwidth is-striped is-hoverable rbk-table">
                                <thead>
                                    <tr>
                                        <th style="width: 44px;">No</th>
                                        <th>Uraian</th>
                                        <th style="width: 110px;">Qty</th>
                                        <th style="width: 120px;">Satuan</th>
                                        <th style="width: 180px;" class="has-text-right">Nilai (Rp)</th>
                                        <th style="width: 70px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(it, idx) in rbkItemsBy('manpower')" :key="it._key">
                                        <td>{{ idx + 1 }}</td>
                                        <td>
                                            <input class="input is-small" v-model="it.uraian"
                                                placeholder="Contoh: Pelaksana Teknik..." />
                                        </td>
                                        <td>
                                            <input class="input is-small" type="number" v-model.number="it.qty" />
                                        </td>
                                        <td>
                                            <input class="input is-small" v-model="it.satuan" placeholder="Lot/Org" />
                                        </td>
                                        <td class="has-text-right">
                                            <input class="input is-small has-text-right" type="text"
                                                :value="formatNumberForInput(it.nilai_rencana)"
                                                @input="(e) => it.nilai_rencana = parseMoneyInput(e)" />
                                        </td>
                                        <td class="has-text-centered">
                                            <a class="has-text-danger is-clickable" @click="removeLocalItem(it._key)">
                                                <i class="iconify" data-icon="feather:trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="rbkItemsBy('manpower').length === 0">
                                        <td colspan="6" class="has-text-centered has-text-grey">Belum ada item</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rbk-section">
                        <div class="rbk-section-title">
                            <strong>Material</strong>
                            <VButton color="primary" size="small" @click="addItem('material')">
                                + Tambah
                            </VButton>
                        </div>

                        <div class="table-container">
                            <table class="table is-fullwidth is-striped is-hoverable rbk-table">
                                <thead>
                                    <tr>
                                        <th style="width: 44px;">No</th>
                                        <th>Uraian</th>
                                        <th style="width: 110px;">Qty</th>
                                        <th style="width: 120px;">Satuan</th>
                                        <th style="width: 180px;" class="has-text-right">Nilai (Rp)</th>
                                        <th style="width: 70px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(it, idx) in rbkItemsBy('material')" :key="it._key">
                                        <td>{{ idx + 1 }}</td>
                                        <td>
                                            <input class="input is-small" v-model="it.uraian"
                                                placeholder="Contoh: Material Kalibrasi" />
                                        </td>
                                        <td>
                                            <input class="input is-small" type="number" v-model.number="it.qty" />
                                        </td>
                                        <td>
                                            <input class="input is-small" v-model="it.satuan" placeholder="Lot" />
                                        </td>
                                        <td class="has-text-right">
                                            <input class="input is-small has-text-right" type="text"
                                                :value="formatNumberForInput(it.nilai_rencana)"
                                                @input="(e) => it.nilai_rencana = parseMoneyInput(e)" />
                                        </td>
                                        <td class="has-text-centered">
                                            <a class="has-text-danger is-clickable" @click="removeLocalItem(it._key)">
                                                <i class="iconify" data-icon="feather:trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="rbkItemsBy('material').length === 0">
                                        <td colspan="6" class="has-text-centered has-text-grey">Belum ada item</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rbk-section">
                        <div class="rbk-section-title">
                            <strong>Mobilisasi</strong>
                            <VButton color="primary" size="small" @click="addItem('mobilisasi')">
                                + Tambah
                            </VButton>
                        </div>

                        <div class="table-container">
                            <table class="table is-fullwidth is-striped is-hoverable rbk-table">
                                <thead>
                                    <tr>
                                        <th style="width: 44px;">No</th>
                                        <th>Uraian</th>
                                        <th style="width: 110px;">Qty</th>
                                        <th style="width: 120px;">Satuan</th>
                                        <th style="width: 180px;" class="has-text-right">Nilai (Rp)</th>
                                        <th style="width: 70px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(it, idx) in rbkItemsBy('mobilisasi')" :key="it._key">
                                        <td>{{ idx + 1 }}</td>
                                        <td>
                                            <input class="input is-small" v-model="it.uraian"
                                                placeholder="Contoh: Mobilisasi Tools" />
                                        </td>
                                        <td>
                                            <input class="input is-small" type="number" v-model.number="it.qty" />
                                        </td>
                                        <td>
                                            <input class="input is-small" v-model="it.satuan" placeholder="Lot" />
                                        </td>
                                        <td class="has-text-right">
                                            <input class="input is-small has-text-right" type="text"
                                                :value="formatNumberForInput(it.nilai_rencana)"
                                                @input="(e) => it.nilai_rencana = parseMoneyInput(e)" />
                                        </td>
                                        <td class="has-text-centered">
                                            <a class="has-text-danger is-clickable" @click="removeLocalItem(it._key)">
                                                <i class="iconify" data-icon="feather:trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="rbkItemsBy('mobilisasi').length === 0">
                                        <td colspan="6" class="has-text-centered has-text-grey">Belum ada item</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <hr class="dropdown-divider my-3" />

                    <div class="is-flex is-justify-content-space-between is-align-items-center">
                        <div class="rbk-total-box">
                            <div><strong>Total Manpower:</strong> {{ formatRupiah(calcTotalBy('manpower')) }}</div>
                            <div><strong>Total Material:</strong> {{ formatRupiah(calcTotalBy('material')) }}</div>
                            <div><strong>Total Mobilisasi:</strong> {{ formatRupiah(calcTotalBy('mobilisasi')) }}</div>
                            <div class="grand"><strong>BIAYA TOTAL:</strong> {{ formatRupiah(calcGrandTotal()) }}</div>
                        </div>
                        <div>
                            <VButton color="primary" :loading="savingRbk" @click="saveRbk">
                                Simpan RBK
                            </VButton>
                        </div>
                    </div>

                </section>
            </div>
        </div>

        <!-- MODAL REALISASI HYBRID -->
        <Teleport to="body">
            <div v-if="isRealisasiModalOpen" class="rbk-hybrid-overlay" role="dialog" aria-modal="true">
                <button class="rbk-hybrid-backdrop" aria-label="Tutup modal" @click="closeRealisasiModal"></button>
                <article class="rbk-hybrid-dialog" :class="{ 'is-fullscreen': isRealisasiFullscreen }">
                    <header class="rbk-hybrid-header">
                        <div class="rbk-hybrid-title-wrap">
                            <span class="rbk-hybrid-title-icon"><i class="iconify" data-icon="feather:activity"></i></span>
                            <div>
                                <p class="rbk-hybrid-eyebrow">Realisasi RBK Hybrid</p>
                                <h2>{{ selectedUnit?.namaperusahaan }}</h2>
                                <p>{{ getYearString() }} · {{ activeTab === 'jakarta' ? 'Jakarta' : 'Gresik' }}</p>
                            </div>
                        </div>
                        <div class="rbk-hybrid-header-actions">
                            <button class="rbk-excel-btn" type="button"
                                :disabled="exportingExcel || loadingRbkDetail || loadingRealisasi || !rbkDetail?.header"
                                title="Export data dan chart ke Excel" @click="exportRealisasiExcel">
                                <i class="iconify" :class="{ 'fa-spin': exportingExcel }"
                                    :data-icon="exportingExcel ? 'feather:loader' : 'feather:download'"></i>
                                <span>{{ exportingExcel ? 'Membuat...' : 'Excel' }}</span>
                            </button>
                            <button class="rbk-modal-icon-btn" type="button"
                                :title="isRealisasiFullscreen ? 'Pulihkan ukuran' : 'Perbesar penuh'"
                                @click="toggleRealisasiFullscreen">
                                <i class="iconify"
                                    :data-icon="isRealisasiFullscreen ? 'feather:minimize-2' : 'feather:maximize-2'"></i>
                            </button>
                            <button class="rbk-modal-icon-btn is-close" type="button" title="Tutup"
                                @click="closeRealisasiModal">
                                <i class="iconify" data-icon="feather:x"></i>
                            </button>
                        </div>
                    </header>

                    <section class="rbk-hybrid-body">
                        <div v-if="loadingRbkDetail" class="rbk-modal-loading">
                            <i class="iconify fa-spin" data-icon="feather:loader"></i>
                            <span>Memuat detail RBK...</span>
                        </div>

                        <div v-else-if="!rbkDetail?.header" class="notification is-warning is-light">
                            RBK belum dibuat untuk unit ini ({{ getYearString() }} -
                            {{ activeTab === 'jakarta' ? 'Jakarta' : 'Gresik' }}).
                        </div>

                        <template v-else>
                            <div class="rbk-hybrid-info">
                                <i class="iconify" data-icon="feather:info"></i>
                                <span>
                                    Total realisasi merupakan gabungan tarikan PBJ dan input manual dari halaman ini.
                                    Draft PBJ langsung dihitung; data yang dihapus, dinonaktifkan, atau dibatalkan tidak
                                    ikut. Nilai PBJ sudah direkonsiliasi dengan PPN.
                                </span>
                            </div>

                            <div class="rbk-overview-grid">
                                <div class="rbk-overview-card">
                                    <span>Total Rencana</span>
                                    <strong>{{ formatRupiah(rbkDetail.summary?.total?.rencana ?? 0) }}</strong>
                                </div>
                                <div class="rbk-overview-card is-success">
                                    <span>Realisasi Hybrid</span>
                                    <strong>{{ formatRupiah(rbkDetail.summary?.total?.realisasi ?? 0) }}</strong>
                                </div>
                                <div class="rbk-overview-card"
                                    :class="{ 'is-danger': (rbkDetail.summary?.total?.sisa ?? 0) < 0 }">
                                    <span>Sisa</span>
                                    <strong>{{ formatRupiah(rbkDetail.summary?.total?.sisa ?? 0) }}</strong>
                                </div>
                                <div class="rbk-overview-card is-primary">
                                    <span>Progress</span>
                                    <strong>{{ rbkDetail.summary?.total?.progress ?? 0 }}%</strong>
                                </div>
                            </div>

                            <div class="rbk-category-grid">
                                <div v-for="category in realisasiCategories" :key="category.key"
                                    class="rbk-category-card">
                                    <div class="rbk-category-card-title">
                                        <i class="iconify" :data-icon="category.icon"></i>
                                        <span>{{ category.label }}</span>
                                    </div>
                                    <div><span>Rencana</span><strong>{{
                                        formatRupiah(rbkDetail.summary?.[category.key]?.rencana ?? 0) }}</strong></div>
                                    <div class="is-realization"><span>Realisasi Hybrid</span><strong>{{
                                        formatRupiah(rbkDetail.summary?.[category.key]?.realisasi ?? 0) }}</strong></div>
                                    <div :class="{ 'has-negative': (rbkDetail.summary?.[category.key]?.sisa ?? 0) < 0 }">
                                        <span>Sisa</span><strong>{{
                                            formatRupiah(rbkDetail.summary?.[category.key]?.sisa ?? 0) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="rbk-hybrid-content-grid">
                                <aside class="rbk-manual-card">
                                    <div class="rbk-section-heading">
                                        <div>
                                            <span class="rbk-source-badge is-manual">Manual</span>
                                            <h3>{{ editingManualNorec ? 'Edit Realisasi Manual' : 'Tambah Realisasi Manual' }}</h3>
                                        </div>
                                        <button v-if="editingManualNorec" type="button" class="rbk-link-button"
                                            @click="resetManualForm">Batal edit</button>
                                    </div>

                                    <div class="field">
                                        <label class="label">Item Rencana RBK</label>
                                        <div class="control select is-fullwidth">
                                            <select v-model="manualForm.item_norec">
                                                <option value="" disabled>Pilih item rencana</option>
                                                <option v-for="item in rbkDetail.items" :key="item.norec" :value="item.norec">
                                                    {{ categoryLabel(item.kategori) }} — {{ item.uraian }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div v-if="manualSelectedItem" class="rbk-selected-plan">
                                        <span>{{ categoryLabel(manualSelectedItem.kategori) }}</span>
                                        <strong>Sisa kategori {{ formatRupiah(manualSelectedCategorySisa) }}</strong>
                                    </div>

                                    <div class="field">
                                        <label class="label">Nilai Realisasi</label>
                                        <div class="control has-icons-left">
                                            <input class="input" type="text" inputmode="numeric" placeholder="0"
                                                :value="formatNumberForInput(manualForm.nilai_realisasi)"
                                                @input="handleManualMoneyInput" />
                                            <span class="icon is-left has-text-grey">Rp</span>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label class="label">Tanggal</label>
                                        <input v-model="manualForm.tanggal" class="input" type="date" />
                                    </div>
                                    <div class="field">
                                        <label class="label">Keterangan</label>
                                        <textarea v-model="manualForm.keterangan" class="textarea" rows="4"
                                            placeholder="Jelaskan realisasi manual..."></textarea>
                                    </div>
                                    <button type="button" class="button is-primary is-fullwidth"
                                        :class="{ 'is-loading': savingManualRealisasi }" :disabled="savingManualRealisasi"
                                        @click="saveManualRealisasi">
                                        <span class="icon"><i class="iconify"
                                                :data-icon="editingManualNorec ? 'feather:save' : 'feather:plus-circle'"></i></span>
                                        <span>{{ editingManualNorec ? 'Simpan Perubahan' : 'Tambah Realisasi' }}</span>
                                    </button>
                                </aside>

                                <main class="rbk-history-card">
                                    <div class="rbk-section-heading">
                                        <div>
                                            <p class="rbk-hybrid-eyebrow">Rincian pemotongan</p>
                                            <h3>Realisasi PBJ dan Manual</h3>
                                        </div>
                                        <div class="rbk-source-legend">
                                            <span><i class="is-pbj"></i> PBJ</span>
                                            <span><i class="is-manual"></i> Manual</span>
                                        </div>
                                    </div>

                                    <div v-if="loadingRealisasi" class="rbk-modal-loading is-small">
                                        <i class="iconify fa-spin" data-icon="feather:loader"></i>
                                        <span>Memuat realisasi...</span>
                                    </div>
                                    <div v-else-if="realisasiRows.length === 0" class="rbk-empty-state">
                                        <i class="iconify" data-icon="feather:inbox"></i>
                                        <p>Belum ada PBJ yang cocok atau realisasi manual.</p>
                                    </div>

                                    <div v-else class="rbk-history-list">
                                        <section v-for="category in realisasiCategories" :key="category.key"
                                            v-show="getGroupedRealisasiRows(category.key).length" class="rbk-history-category">
                                            <div class="rbk-history-category-title">
                                                <span>{{ category.label }}</span>
                                                <small>{{ getGroupedRealisasiRows(category.key).length }} baris</small>
                                            </div>
                                            <article v-for="row in getGroupedRealisasiRows(category.key)"
                                                :key="row.group_key" class="rbk-history-item"
                                                :class="row.source === 'pbj' ? 'is-pbj' : 'is-manual'">
                                                <div class="rbk-history-accent"></div>
                                                <div class="rbk-history-main">
                                                    <div class="rbk-history-topline">
                                                        <div>
                                                            <span class="rbk-source-badge"
                                                                :class="row.source === 'pbj' ? 'is-pbj' : 'is-manual'">
                                                                {{ row.source === 'pbj' ? `PBJ · ${row.items.length} item` : 'Manual' }}
                                                            </span>
                                                            <strong>{{ formatRupiah(row.nilai_realisasi) }}</strong>
                                                            <small>{{ formatDate(row.tanggal || row.created_at) }}</small>
                                                        </div>
                                                        <button v-if="row.source === 'pbj'" type="button"
                                                            class="button is-small is-danger is-outlined" @click="printPbj(row)">
                                                            <span class="icon"><i class="iconify" data-icon="feather:printer"></i></span>
                                                            <span>Cetak PBJ</span>
                                                        </button>
                                                        <button v-else type="button" class="button is-small is-info is-outlined"
                                                            @click="editManualRealisasi(row)">
                                                            <span class="icon"><i class="iconify" data-icon="feather:edit-2"></i></span>
                                                            <span>Edit</span>
                                                        </button>
                                                    </div>

                                                    <template v-if="row.source === 'pbj'">
                                                        <p class="rbk-pbj-group-title">
                                                            {{ row.judulpermintaan || `PBJ ${row.nosuratpbj || '-'}` }}
                                                        </p>
                                                        <div class="rbk-pbj-item-list">
                                                            <div v-for="(item, itemIndex) in row.items"
                                                                :key="item.pbj_detail_norec || item.norec"
                                                                class="rbk-pbj-item-row">
                                                                <span class="rbk-pbj-item-number">{{ itemIndex + 1 }}</span>
                                                                <div class="rbk-pbj-item-content">
                                                                    <p v-if="item.pbj_head" class="rbk-history-head">
                                                                        Head: {{ item.pbj_head }}
                                                                    </p>
                                                                    <p class="rbk-history-title">{{ item.uraian || '-' }}</p>
                                                                    <p class="rbk-history-description">{{ item.keterangan || '-' }}</p>
                                                                </div>
                                                                <div class="rbk-pbj-item-value">
                                                                    <strong>{{ formatRupiah(item.nilai_realisasi) }}</strong>
                                                                    <small v-if="Number(item.pbj_ppn_persen || 0) > 0">
                                                                        {{ formatRupiah(item.nilai_subtotal_detail) }} + PPN
                                                                        {{ formatRupiah(item.nilai_ppn_detail) }}
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <template v-else>
                                                        <p class="rbk-history-title">{{ row.uraian || '-' }}</p>
                                                        <p class="rbk-history-description">{{ row.keterangan || '-' }}</p>
                                                    </template>

                                                    <div v-if="row.source === 'pbj'" class="rbk-history-meta">
                                                        <span v-if="Number(row.pbj_ppn_persen || 0) > 0">
                                                            Subtotal {{ formatRupiah(row.nilai_subtotal_detail) }} + PPN
                                                            {{ formatRupiah(row.nilai_ppn_detail) }} ({{ row.pbj_ppn_persen }}%)
                                                        </span>
                                                        <span>User Unit: {{ row.user_unit_nama || '-' }}</span>
                                                        <span>PBJ: {{ row.nosuratpbj || '-' }}</span>
                                                        <span>PRK: {{ row.prk || '-' }}</span>
                                                        <span>Cost Code: {{ row.costcode || '-' }}</span>
                                                        <span>Cocok: {{ formatPbjMatch(row.match_by) }}</span>
                                                    </div>
                                                    <div v-else class="rbk-history-meta">
                                                        <span>Oleh: {{ row.created_by_nama || row.created_by || '-' }}</span>
                                                    </div>
                                                </div>
                                            </article>
                                        </section>
                                    </div>
                                </main>
                            </div>
                        </template>
                    </section>
                </article>
            </div>
        </Teleport>

        <!-- Modal realisasi lama dipertahankan sementara sebagai referensi struktur data. -->
        <div v-if="false" :class="['modal', { 'is-active': isRealisasiModalOpen }]">
            <div class="modal-background" @click="closeRealisasiModal"></div>
            <div class="modal-card modal-wide">
                <header class="modal-card-head">
                    <p class="modal-card-title">
                        Detail Realisasi PBJ: {{ selectedUnit?.namaperusahaan }}
                    </p>
                    <button class="delete" aria-label="close" @click="closeRealisasiModal"></button>
                </header>

                <section class="modal-card-body">

                    <div v-if="loadingRbkDetail" class="has-text-centered p-4">
                        <i class="iconify fa-spin" data-icon="feather:loader"></i>
                    </div>

                    <template v-else>
                        <div v-if="!rbkDetail?.header" class="notification is-warning is-light">
                            RBK belum dibuat untuk unit ini ({{ getYearString() }} -
                            {{ activeTab === 'jakarta' ? 'Jakarta' : 'Gresik' }}).
                            Silakan buat RBK dulu.
                        </div>

                        <template v-else>
                            <div class="notification is-info is-light mb-4">
                                Realisasi bersifat otomatis dan langsung dibaca dari detail PBJ, termasuk yang masih
                                draft. Detail/PBJ yang dihapus, dinonaktifkan, atau dibatalkan tidak ikut mengurangi RBK.
                                Pencocokan wajib sesuai User Unit, lalu diverifikasi memakai PRK dan Cost Code (boleh
                                tidak lengkap). Data yang bertentangan atau ambigu tidak dimasukkan. Koreksi kategori
                                dilakukan melalui penanda <strong>kategorirbk</strong> pada detail PBJ. Nilai detail
                                sudah direkonsiliasi ke Total Estimasi PBJ, termasuk alokasi PPN jika ada.
                            </div>

                            <div class="notification is-light mb-4 py-2">
                                <div class="level is-mobile">
                                    <div class="level-item has-text-centered">
                                        <div>
                                            <p class="heading mb-0">Total Rencana</p>
                                            <span class="title is-5">
                                                {{ formatRupiah(rbkDetail?.summary?.total?.rencana ?? 0) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="level-item has-text-centered">
                                        <div>
                                            <p class="heading mb-0">Realisasi</p>
                                            <span class="title is-5 has-text-success">
                                                {{ formatRupiah(rbkDetail?.summary?.total?.realisasi ?? 0) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="level-item has-text-centered">
                                        <div>
                                            <p class="heading mb-0">Sisa</p>
                                            <span class="title is-5" :class="[
                                                (rbkDetail?.summary?.total?.sisa ?? 0) < 0
                                                    ? 'has-text-danger'
                                                    : 'has-text-warning'
                                            ]">
                                                {{ formatRupiah(rbkDetail?.summary?.total?.sisa ?? 0) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="level-item has-text-centered">
                                        <div>
                                            <p class="heading mb-0">Progress</p>
                                            <span class="title is-5 has-text-primary">
                                                {{ rbkDetail?.summary?.total?.progress ?? 0 }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="has-text-centered mt-2">
                                    <small class="has-text-grey">
                                        RBK dibuat oleh:
                                        <strong>
                                            {{ rbkDetail?.header?.created_by_nama || rbkDetail?.header?.created_by ||
                                            '-' }}
                                        </strong>
                                        <span v-if="rbkDetail?.header?.created_at" class="ml-2">
                                            ({{ formatDate(rbkDetail.header.created_at) }})
                                        </span>
                                    </small>
                                </div>
                            </div>

                            <div class="columns is-multiline mb-2">
                                <div v-for="kategori in ['manpower', 'material', 'mobilisasi']" :key="kategori"
                                    class="column is-4">
                                    <div class="box py-3 px-4">
                                        <p class="has-text-weight-bold is-uppercase mb-2">{{ kategori }}</p>
                                        <div class="is-flex is-justify-content-space-between">
                                            <span>Rencana</span>
                                            <strong>{{ formatRupiah(rbkDetail?.summary?.[kategori]?.rencana ?? 0) }}</strong>
                                        </div>
                                        <div class="is-flex is-justify-content-space-between has-text-success">
                                            <span>Realisasi PBJ</span>
                                            <strong>{{ formatRupiah(rbkDetail?.summary?.[kategori]?.realisasi ?? 0) }}</strong>
                                        </div>
                                        <div class="is-flex is-justify-content-space-between"
                                            :class="{ 'has-text-danger': (rbkDetail?.summary?.[kategori]?.sisa ?? 0) < 0 }">
                                            <span>Sisa</span>
                                            <strong>{{ formatRupiah(rbkDetail?.summary?.[kategori]?.sisa ?? 0) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="columns">
                                <div class="column is-12">
                                    <h4 class="title is-6 mb-2">Rencana RBK per Item</h4>
                                    <div class="table-container">
                                        <table class="table is-fullwidth is-striped is-hoverable rbk-table">
                                            <thead>
                                                <tr>
                                                    <th>Kategori</th>
                                                    <th>Uraian</th>
                                                    <th class="has-text-right">Rencana</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="it in rbkDetail.items" :key="it.norec">
                                                    <td class="is-capitalized">{{ it.kategori }}</td>
                                                    <td class="text-truncate" :title="it.uraian">{{ it.uraian }}</td>
                                                    <td class="has-text-right">{{ formatRupiah(it.nilai_rencana ?? 0) }}
                                                    </td>
                                                </tr>
                                                <tr v-if="rbkDetail.items.length === 0">
                                                    <td colspan="3" class="has-text-centered has-text-grey">Belum ada
                                                        item</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <h4 class="title is-6 mb-2">Detail Item PBJ yang Memotong RBK</h4>
                                    <div class="timeline-wrapper">
                                        <div v-if="loadingRealisasi" class="has-text-centered py-4">
                                            <i class="iconify is-size-4 fa-spin" data-icon="feather:loader"></i>
                                            <p>Memuat...</p>
                                        </div>

                                        <div v-else-if="realisasiRows.length === 0"
                                            class="has-text-centered has-text-grey my-5">
                                            <p>Belum ada detail PBJ yang cocok dengan PRK atau Cost Code RBK ini.</p>
                                        </div>

                                        <div v-else>
                                            <div v-if="getRealisasiRows('manpower').length" class="category-group mb-4">
                                                <p
                                                    class="category-label is-size-7 has-text-weight-bold has-text-grey is-uppercase mb-2">
                                                    Manpower
                                                </p>

                                                <div v-for="r in getRealisasiRows('manpower')" :key="r.norec"
                                                    class="media note-item">
                                                    <figure class="media-left">
                                                        <span class="icon is-medium has-text-info">
                                                            <i class="iconify" data-icon="feather:minus-circle"></i>
                                                        </span>
                                                    </figure>

                                                    <div class="media-content">
                                                        <div class="content">
                                                            <p>
                                                                <strong>{{ formatRupiah(r.nilai_realisasi) }}</strong>
                                                                <small class="has-text-grey ml-2">
                                                                    {{ formatDate(r.tanggal || r.created_at) }}
                                                                </small>
                                                                <br />
                                                                <small v-if="Number(r.pbj_ppn_persen || 0) > 0"
                                                                    class="has-text-grey">
                                                                    Subtotal {{ formatRupiah(r.nilai_subtotal_detail) }} +
                                                                    alokasi PPN {{ formatRupiah(r.nilai_ppn_detail) }}
                                                                    ({{ r.pbj_ppn_persen }}%)
                                                                </small>
                                                                <br v-if="Number(r.pbj_ppn_persen || 0) > 0" />
                                                                <small v-if="r.pbj_head" class="has-text-weight-semibold">
                                                                    Head: {{ r.pbj_head }}
                                                                </small>
                                                                <br v-if="r.pbj_head" />
                                                                <small
                                                                    class="is-italic has-text-grey-light mb-1 d-block">
                                                                    {{ r.uraian }}
                                                                </small>
                                                                <br />
                                                                <span style="white-space: pre-wrap;">{{ r.keterangan ||
                                                                    '-' }}</span>
                                                                <br />
                                                                <small class="has-text-grey-light">
                                                                    Oleh: {{ r.created_by_nama || r.created_by || '-' }}
                                                                </small>
                                                                <br />
                                                                <small class="has-text-weight-semibold">
                                                                    User Unit PBJ: {{ r.user_unit_nama || '-' }}
                                                                </small>
                                                                <br />
                                                                <small class="has-text-grey">
                                                                    PBJ: {{ r.nosuratpbj || '-' }} · PRK: {{ r.prk || '-' }} ·
                                                                    Cost Code: {{ r.costcode || '-' }} · Cocok via:
                                                                    {{ formatPbjMatch(r.match_by) }}
                                                                </small>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-if="getRealisasiRows('material').length" class="category-group mb-4">
                                                <p
                                                    class="category-label is-size-7 has-text-weight-bold has-text-grey is-uppercase mb-2">
                                                    Material
                                                </p>

                                                <div v-for="r in getRealisasiRows('material')" :key="r.norec"
                                                    class="media note-item">
                                                    <figure class="media-left">
                                                        <span class="icon is-medium has-text-info">
                                                            <i class="iconify" data-icon="feather:minus-circle"></i>
                                                        </span>
                                                    </figure>

                                                    <div class="media-content">
                                                        <div class="content">
                                                            <p>
                                                                <strong>{{ formatRupiah(r.nilai_realisasi) }}</strong>
                                                                <small class="has-text-grey ml-2">
                                                                    {{ formatDate(r.tanggal || r.created_at) }}
                                                                </small>
                                                                <br />
                                                                <small v-if="Number(r.pbj_ppn_persen || 0) > 0"
                                                                    class="has-text-grey">
                                                                    Subtotal {{ formatRupiah(r.nilai_subtotal_detail) }} +
                                                                    alokasi PPN {{ formatRupiah(r.nilai_ppn_detail) }}
                                                                    ({{ r.pbj_ppn_persen }}%)
                                                                </small>
                                                                <br v-if="Number(r.pbj_ppn_persen || 0) > 0" />
                                                                <small v-if="r.pbj_head" class="has-text-weight-semibold">
                                                                    Head: {{ r.pbj_head }}
                                                                </small>
                                                                <br v-if="r.pbj_head" />
                                                                <small
                                                                    class="is-italic has-text-grey-light mb-1 d-block">
                                                                    {{ r.uraian }}
                                                                </small>
                                                                <br />
                                                                <span style="white-space: pre-wrap;">{{ r.keterangan ||
                                                                    '-' }}</span>
                                                                <br />
                                                                <small class="has-text-grey-light">
                                                                    Oleh: {{ r.created_by_nama || r.created_by || '-' }}
                                                                </small>
                                                                <br />
                                                                <small class="has-text-weight-semibold">
                                                                    User Unit PBJ: {{ r.user_unit_nama || '-' }}
                                                                </small>
                                                                <br />
                                                                <small class="has-text-grey">
                                                                    PBJ: {{ r.nosuratpbj || '-' }} · PRK: {{ r.prk || '-' }} ·
                                                                    Cost Code: {{ r.costcode || '-' }} · Cocok via:
                                                                    {{ formatPbjMatch(r.match_by) }}
                                                                </small>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-if="getRealisasiRows('mobilisasi').length"
                                                class="category-group mb-4">
                                                <p
                                                    class="category-label is-size-7 has-text-weight-bold has-text-grey is-uppercase mb-2">
                                                    Mobilisasi
                                                </p>

                                                <div v-for="r in getRealisasiRows('mobilisasi')" :key="r.norec"
                                                    class="media note-item">
                                                    <figure class="media-left">
                                                        <span class="icon is-medium has-text-info">
                                                            <i class="iconify" data-icon="feather:minus-circle"></i>
                                                        </span>
                                                    </figure>

                                                    <div class="media-content">
                                                        <div class="content">
                                                            <p>
                                                                <strong>{{ formatRupiah(r.nilai_realisasi) }}</strong>
                                                                <small class="has-text-grey ml-2">
                                                                    {{ formatDate(r.tanggal || r.created_at) }}
                                                                </small>
                                                                <br />
                                                                <small v-if="Number(r.pbj_ppn_persen || 0) > 0"
                                                                    class="has-text-grey">
                                                                    Subtotal {{ formatRupiah(r.nilai_subtotal_detail) }} +
                                                                    alokasi PPN {{ formatRupiah(r.nilai_ppn_detail) }}
                                                                    ({{ r.pbj_ppn_persen }}%)
                                                                </small>
                                                                <br v-if="Number(r.pbj_ppn_persen || 0) > 0" />
                                                                <small v-if="r.pbj_head" class="has-text-weight-semibold">
                                                                    Head: {{ r.pbj_head }}
                                                                </small>
                                                                <br v-if="r.pbj_head" />
                                                                <small
                                                                    class="is-italic has-text-grey-light mb-1 d-block">
                                                                    {{ r.uraian }}
                                                                </small>
                                                                <br />
                                                                <span style="white-space: pre-wrap;">{{ r.keterangan ||
                                                                    '-' }}</span>
                                                                <br />
                                                                <small class="has-text-grey-light">
                                                                    Oleh: {{ r.created_by_nama || r.created_by || '-' }}
                                                                </small>
                                                                <br />
                                                                <small class="has-text-weight-semibold">
                                                                    User Unit PBJ: {{ r.user_unit_nama || '-' }}
                                                                </small>
                                                                <br />
                                                                <small class="has-text-grey">
                                                                    PBJ: {{ r.nosuratpbj || '-' }} · PRK: {{ r.prk || '-' }} ·
                                                                    Cost Code: {{ r.costcode || '-' }} · Cocok via:
                                                                    {{ formatPbjMatch(r.match_by) }}
                                                                </small>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </template>

                </section>
            </div>
        </div>

    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import Calendar from 'primevue/calendar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useApi } from '/@src/composable/useApi'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useHead } from '@vueuse/head'
import { exportRbkRealisasiExcel } from '/@src/utils/rbkExcel'
import { exportRbkMonitoringExcel } from '/@src/utils/rbkMonitoringExcel'
import { exportMonitoringSurkesDetailExcel } from '/@src/utils/monitoringSurkesExcel'

useHead({
    title: 'RBK Monitoring - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const apexchart = VueApexCharts

type UnitRow = {
    unit_id: number
    namaperusahaan: string
    no_surat: string
    no_prk: string
    cost_code: string
    jadwal_kalibrasi: string
    has_rbk: boolean
    summary: {
        total_rencana: number
        total_realisasi: number
        total_sisa: number
        progress: number
        kategori: {
            manpower: { rencana: number; realisasi: number; sisa: number }
            material: { rencana: number; realisasi: number; sisa: number }
            mobilisasi: { rencana: number; realisasi: number; sisa: number }
        }
    }
    alat_monitoring: {
        surkes: {
            rencana: number
            realisasi: number
            belum: number
            progress: number
        }
        non_surkes: {
            jumlah: number
        }
    }
}

type MonitoringAlatUnit = {
    id: number | string
    rencana_alat_surkes?: number
    target_mapping?: number
    realisasi_surkes?: number
    sisa_surkes_belum_daftar?: number
    alat_non_surkes?: number
    progress?: number
}

type AlatMonitoringKind = 'rencana' | 'realisasi' | 'belum' | 'non_surkes'

type AlatMonitoringRow = {
    key: string
    status: string
    status_label: string
    unit_id: number
    namaperusahaan: string
    namaproduk: string | null
    namamerk: string | null
    namatipe: string | null
    namaserialnumber: string | null
    lingkup_mapping: string | null
    lingkup_registrasi: string | null
    registration_count: number
    duplicate_count: number
    jenisorder: string[]
    last_tglregistrasi: string | null
    last_nopendaftaran: string | null
    last_noorderalat: string | null
    last_jenisorder: string | null
    status_pekerjaan: string
    status_terakhir?: string
    status_pengambilan?: string
    progress_persen?: number
    pelaksana?: string
    lokasi?: string
}

type UnitOption = {
    value: number
    label: string
}

type RbkItem = {
    norec: string
    kategori: 'manpower' | 'material' | 'mobilisasi'
    uraian: string
    qty: number
    satuan: string
    nilai_rencana: number
    urutan: number
    total_realisasi?: number
}

type RbkDetail = {
    header: any
    items: RbkItem[]
    summary: any
}

type LocalItem = {
    _key: string
    norec?: string
    kategori: 'manpower' | 'material' | 'mobilisasi'
    uraian: string
    qty: number
    satuan: string
    nilai_rencana: number
    urutan: number
}

const loading = ref(false)
const exportingPdf = ref(false)
const exportingPdfAll = ref(false)
const exportingExcelCurrent = ref(false)
const exportingExcelAll = ref(false)

const year = ref<Date>(new Date())
const activeTab = ref('jakarta')
const searchKeyword = ref('')
const sortBy = ref('total_rencana')
let searchTimeout: any = null

const units = ref<UnitRow[]>([])

// DETAIL MONITORING ALAT PER UNIT
const isAlatMonitoringModalOpen = ref(false)
const isAlatMonitoringFullscreen = ref(false)
const loadingAlatMonitoring = ref(false)
const exportingAlatMonitoring = ref(false)
const selectedAlatMonitoringUnit = ref<UnitRow | null>(null)
const selectedAlatMonitoringKind = ref<AlatMonitoringKind>('rencana')
const alatMonitoringRows = ref<AlatMonitoringRow[]>([])
const alatMonitoringKeyword = ref('')
let alatMonitoringRequestId = 0

// TAMBAH UNIT RBK
const isAddUnitModalOpen = ref(false)
const loadingUnitOptions = ref(false)
const savingUnit = ref(false)
const unitOptions = ref<UnitOption[]>([])
const addUnitForm = ref({
    unit_id: null as number | null,
    lokasi_id: 1,
})

// RBK MODAL
const isRbkModalOpen = ref(false)
const selectedUnit = ref<any>(null)
const savingRbk = ref(false)

const rbkForm = ref({
    judul: '',
    no_surat: '',
    no_prk: '',
    cost_code: '',
    jadwal_kalibrasi: '',
})

const rbkHeaderInfo = ref<any>(null)
const rbkItems = ref<LocalItem[]>([])

// REALISASI MODAL
const isRealisasiModalOpen = ref(false)
const isRealisasiFullscreen = ref(false)
const loadingRbkDetail = ref(false)
const rbkDetail = ref<RbkDetail | null>(null)
const realisasiRows = ref<any[]>([])
const loadingRealisasi = ref(false)
const exportingExcel = ref(false)
const savingManualRealisasi = ref(false)
const editingManualNorec = ref<string | null>(null)
const realisasiCategories = [
    { key: 'manpower', label: 'Manpower', icon: 'feather:users' },
    { key: 'material', label: 'Material', icon: 'feather:package' },
    { key: 'mobilisasi', label: 'Mobilisasi', icon: 'feather:truck' },
] as const

const manualForm = ref({
    item_norec: '',
    nilai_realisasi: 0,
    tanggal: getTodayInputValue(),
    keterangan: '',
})

const manualSelectedItem = computed(() => {
    return rbkDetail.value?.items?.find((item) => String(item.norec) === String(manualForm.value.item_norec)) ?? null
})

const manualSelectedCategorySisa = computed(() => {
    const category = manualSelectedItem.value?.kategori
    if (!category) return 0
    return Number(rbkDetail.value?.summary?.[category]?.sisa ?? 0)
})

const getYearString = () => String(year.value.getFullYear())
const getLokasiId = () => (activeTab.value === 'gresik' ? 2 : 1)

const alatMonitoringKindMeta: Record<AlatMonitoringKind, { title: string; subtitle: string }> = {
    rencana: {
        title: 'Rencana Alat Surkes',
        subtitle: 'Seluruh alat rencana Surkes, termasuk yang sudah direalisasikan dan yang belum didaftarkan.',
    },
    realisasi: {
        title: 'Realisasi Alat Surkes',
        subtitle: 'Alat rencana Surkes yang sudah memiliki pendaftaran pada tahun terpilih.',
    },
    belum: {
        title: 'Alat Surkes Belum Daftar',
        subtitle: 'Alat yang sudah masuk rencana Surkes tetapi belum memiliki pendaftaran pada tahun terpilih.',
    },
    non_surkes: {
        title: 'Alat Non-Surkes',
        subtitle: 'Alat terdaftar sebagai Non-Surkes dan ditampilkan terpisah dari rencana Surkes.',
    },
}

const alatMonitoringModalTitle = computed(() => alatMonitoringKindMeta[selectedAlatMonitoringKind.value].title)
const alatMonitoringModalSubtitle = computed(() => alatMonitoringKindMeta[selectedAlatMonitoringKind.value].subtitle)

const filteredAlatMonitoringRows = computed(() => {
    const keyword = alatMonitoringKeyword.value.trim().toLocaleLowerCase('id-ID')
    if (!keyword) return alatMonitoringRows.value

    return alatMonitoringRows.value.filter((row) => [
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
        row.last_jenisorder,
        row.pelaksana,
        row.lokasi,
        row.status_pekerjaan,
        row.status_terakhir,
        row.status_pengambilan,
        ...(row.jenisorder || []),
    ].some((value) => String(value || '').toLocaleLowerCase('id-ID').includes(keyword)))
})

const alatMonitoringSelesai = computed(() => (
    alatMonitoringRows.value.filter((row) => row.status_pekerjaan === 'Selesai').length
))
const alatMonitoringBelumSelesai = computed(() => alatMonitoringRows.value.length - alatMonitoringSelesai.value)

watch([year, activeTab], () => {
    units.value = []
    loadUnits()
})

watch(searchKeyword, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => loadUnits(), 500)
})

function toSafeNumber(value: unknown) {
    const parsed = Number(value ?? 0)
    return Number.isFinite(parsed) ? parsed : 0
}

function mergeAlatMonitoring(rbkUnits: UnitRow[], monitoringUnits: MonitoringAlatUnit[]) {
    const monitoringByUnit = new Map(
        monitoringUnits.map((item) => [String(item.id), item])
    )

    return rbkUnits.map((unit) => {
        const monitoring = monitoringByUnit.get(String(unit.unit_id))
        const rencana = toSafeNumber(
            monitoring?.rencana_alat_surkes ?? monitoring?.target_mapping
        )
        const realisasi = toSafeNumber(monitoring?.realisasi_surkes)
        const calculatedProgress = rencana > 0
            ? Math.round((realisasi / rencana) * 1000) / 10
            : 0

        return {
            ...unit,
            alat_monitoring: {
                surkes: {
                    rencana,
                    realisasi,
                    belum: toSafeNumber(monitoring?.sisa_surkes_belum_daftar),
                    progress: monitoring?.progress === undefined
                        ? calculatedProgress
                        : toSafeNumber(monitoring.progress),
                },
                non_surkes: {
                    jumlah: toSafeNumber(monitoring?.alat_non_surkes),
                },
            },
        }
    }) as UnitRow[]
}

async function fetchMonitoringAlatByUnit(lokasiId: number) {
    const params = new URLSearchParams({
        year: getYearString(),
        lokasi_id: String(lokasiId),
        summary_only: '1',
    })

    try {
        const res = await useApi().get(`/surkes/monitoring-alat?${params.toString()}`)
        const data = res.data ?? res
        return (data.by_unit ?? []) as MonitoringAlatUnit[]
    } catch (error) {
        console.error('Gagal memuat ringkasan alat per unit', error)
        return []
    }
}

function filterAlatMonitoringByKind(rows: AlatMonitoringRow[], kind: AlatMonitoringKind) {
    if (kind === 'rencana') return rows.filter((row) => row.status !== 'alat_non_surkes')
    if (kind === 'realisasi') return rows.filter((row) => row.status === 'realisasi_surkes')
    if (kind === 'belum') return rows.filter((row) => row.status === 'sisa_surkes_belum_daftar')
    return rows.filter((row) => row.status === 'alat_non_surkes')
}

async function openAlatMonitoringModal(unit: UnitRow, kind: AlatMonitoringKind) {
    const requestId = ++alatMonitoringRequestId
    selectedAlatMonitoringUnit.value = unit
    selectedAlatMonitoringKind.value = kind
    alatMonitoringRows.value = []
    alatMonitoringKeyword.value = ''
    isAlatMonitoringFullscreen.value = false
    isAlatMonitoringModalOpen.value = true
    loadingAlatMonitoring.value = true

    const params = new URLSearchParams({
        year: getYearString(),
        lokasi_id: String(getLokasiId()),
        mitra_id: String(unit.unit_id),
        status: 'all',
    })

    try {
        const response = await useApi().get(`/surkes/monitoring-alat?${params.toString()}`)
        if (requestId !== alatMonitoringRequestId) return
        const data = response.data ?? response
        alatMonitoringRows.value = filterAlatMonitoringByKind(
            (data.rows ?? []) as AlatMonitoringRow[],
            kind,
        )
    } catch (error) {
        if (requestId !== alatMonitoringRequestId) return
        console.error('Gagal memuat detail monitoring alat RBK', error)
        H.alert('error', 'Gagal memuat daftar dan progres alat')
    } finally {
        if (requestId === alatMonitoringRequestId) loadingAlatMonitoring.value = false
    }
}

function closeAlatMonitoringModal() {
    alatMonitoringRequestId++
    isAlatMonitoringModalOpen.value = false
    isAlatMonitoringFullscreen.value = false
    loadingAlatMonitoring.value = false
    alatMonitoringKeyword.value = ''
}

function formatAlatMonitoringDate(value: string | null) {
    if (!value) return '-'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '-'
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function exportAlatMonitoringExcel() {
    if (!filteredAlatMonitoringRows.value.length || !selectedAlatMonitoringUnit.value) {
        H.alert('warning', 'Tidak ada detail alat untuk diekspor')
        return
    }

    exportingAlatMonitoring.value = true
    try {
        const fileName = exportMonitoringSurkesDetailExcel({
            title: `${alatMonitoringModalTitle.value} - ${selectedAlatMonitoringUnit.value.namaperusahaan}`,
            subtitle: alatMonitoringModalSubtitle.value,
            year: getYearString(),
            lokasi: getCurrentLokasiName(),
            keyword: alatMonitoringKeyword.value.trim() || '-',
            rows: filteredAlatMonitoringRows.value,
        })
        H.alert('success', `Excel berhasil diunduh: ${fileName}`)
    } catch (error) {
        console.error('Gagal export detail monitoring alat', error)
        H.alert('error', 'Gagal membuat Excel detail alat')
    } finally {
        exportingAlatMonitoring.value = false
    }
}

async function fetchUnitsWithMonitoring(lokasiId: number) {
    const params = new URLSearchParams({
        year: getYearString(),
        lokasi_id: String(lokasiId),
        keyword: searchKeyword.value,
        sort_by: sortBy.value,
        source_pbj: '1',
    })

    const [rbkResponse, monitoringUnits] = await Promise.all([
        useApi().get(`rbk/get-units-monitoring?${params.toString()}`),
        fetchMonitoringAlatByUnit(lokasiId),
    ])

    return mergeAlatMonitoring((rbkResponse.data ?? []) as UnitRow[], monitoringUnits)
}

async function loadUnits() {
    loading.value = true
    try {
        units.value = await fetchUnitsWithMonitoring(getLokasiId())
    } catch (err) {
        H.alert('error', 'Gagal memuat data RBK')
    } finally {
        loading.value = false
    }
}

async function openAddUnitModal() {
    addUnitForm.value = {
        unit_id: null,
        lokasi_id: getLokasiId(),
    }
    isAddUnitModalOpen.value = true
    loadingUnitOptions.value = true

    try {
        const res = await useApi().get('rbk/get-unit-candidates')
        const registeredUnitIds = new Set(units.value.map((unit) => String(unit.unit_id)))
        const registeredUnitNames = new Set(
            units.value.map((unit) => unit.namaperusahaan.trim().toLocaleLowerCase('id-ID'))
        )
        unitOptions.value = ((res?.data ?? []) as UnitOption[]).filter(
            (unit) =>
                !registeredUnitIds.has(String(unit.value)) &&
                !registeredUnitNames.has(unit.label.trim().toLocaleLowerCase('id-ID'))
        )
    } catch (err) {
        unitOptions.value = []
        H.alert('error', 'Gagal memuat daftar unit yang tersedia')
    } finally {
        loadingUnitOptions.value = false
    }
}

function closeAddUnitModal() {
    if (savingUnit.value) return
    isAddUnitModalOpen.value = false
    unitOptions.value = []
    addUnitForm.value.unit_id = null
}

async function saveUnitRbk() {
    const unitId = Number(addUnitForm.value.unit_id)
    const lokasiId = Number(addUnitForm.value.lokasi_id)

    if (!unitId) {
        H.alert('warning', 'Pilih unit terlebih dahulu')
        return
    }

    if (![1, 2].includes(lokasiId)) {
        H.alert('warning', 'Pilih lokasi Jakarta atau Gresik')
        return
    }

    savingUnit.value = true
    try {
        await useApi().post('rbk/add-unit', {
            unit_id: unitId,
            lokasi_id: lokasiId,
        })

        isAddUnitModalOpen.value = false
        unitOptions.value = []
        addUnitForm.value.unit_id = null
        activeTab.value = lokasiId === 2 ? 'gresik' : 'jakarta'
        await loadUnits()
    } catch (err) {
        // Pesan validasi dari backend sudah ditampilkan oleh useApi.
    } finally {
        savingUnit.value = false
    }
}

async function fetchUnitsForExport(lokasiId: number) {
    return fetchUnitsWithMonitoring(lokasiId)
}

function getLokasiNameById(lokasiId: number) {
    return lokasiId === 2 ? 'Gresik' : 'Jakarta'
}

function getCurrentLokasiName() {
    return activeTab.value === 'gresik' ? 'Gresik' : 'Jakarta'
}

async function exportPdfCurrentTab() {
    exportingPdf.value = true
    try {
        let dataExport = units.value

        if (!dataExport.length) {
            dataExport = await fetchUnitsForExport(getLokasiId())
        }

        if (!dataExport.length) {
            H.alert('warning', `Data ${getCurrentLokasiName()} kosong, tidak ada yang diexport.`)
            return
        }

        const html = buildExportHtml([
            {
                lokasi: getCurrentLokasiName(),
                data: dataExport,
            },
        ])

        openPrintPdf(html, `RBK Monitoring ${getCurrentLokasiName()} ${getYearString()}`)
    } catch (e) {
        console.error(e)
        H.alert('error', 'Gagal export PDF')
    } finally {
        exportingPdf.value = false
    }
}

async function exportPdfAllLokasi() {
    exportingPdfAll.value = true
    try {
        const [jakarta, gresik] = await Promise.all([
            fetchUnitsForExport(1),
            fetchUnitsForExport(2),
        ])

        if (!jakarta.length && !gresik.length) {
            H.alert('warning', 'Data Jakarta dan Gresik kosong, tidak ada yang diexport.')
            return
        }

        const html = buildExportHtml([
            {
                lokasi: 'Jakarta',
                data: jakarta,
            },
            {
                lokasi: 'Gresik',
                data: gresik,
            },
        ])

        openPrintPdf(html, `RBK Monitoring Semua Lokasi ${getYearString()}`)
    } catch (e) {
        console.error(e)
        H.alert('error', 'Gagal export PDF semua lokasi')
    } finally {
        exportingPdfAll.value = false
    }
}

async function exportExcelCurrentTab() {
    exportingExcelCurrent.value = true
    try {
        let dataExport = units.value

        if (!dataExport.length) {
            dataExport = await fetchUnitsForExport(getLokasiId())
        }

        if (!dataExport.length) {
            H.alert('warning', `Data ${getCurrentLokasiName()} kosong, tidak ada yang diexport.`)
            return
        }

        exportRbkMonitoringExcel({
            year: getYearString(),
            filter: searchKeyword.value || '-',
            sortLabel: getSortLabel(),
            sections: [{ lokasi: getCurrentLokasiName(), data: dataExport }],
            fileLabel: getCurrentLokasiName(),
        })
    } catch (error) {
        console.error(error)
        H.alert('error', 'Gagal export Excel')
    } finally {
        exportingExcelCurrent.value = false
    }
}

async function exportExcelAllLokasi() {
    exportingExcelAll.value = true
    try {
        const [jakarta, gresik] = await Promise.all([
            fetchUnitsForExport(1),
            fetchUnitsForExport(2),
        ])

        if (!jakarta.length && !gresik.length) {
            H.alert('warning', 'Data Jakarta dan Gresik kosong, tidak ada yang diexport.')
            return
        }

        exportRbkMonitoringExcel({
            year: getYearString(),
            filter: searchKeyword.value || '-',
            sortLabel: getSortLabel(),
            sections: [
                { lokasi: 'Jakarta', data: jakarta },
                { lokasi: 'Gresik', data: gresik },
            ],
            fileLabel: 'Semua_Lokasi',
        })
    } catch (error) {
        console.error(error)
        H.alert('error', 'Gagal export Excel semua lokasi')
    } finally {
        exportingExcelAll.value = false
    }
}

function buildExportHtml(sections: Array<{ lokasi: string; data: UnitRow[] }>) {
    const now = new Date()
    const tanggalCetak = now.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }) + ' ' + now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    })

    const totalSemuaRencana = sections.reduce((acc, sec) => {
        return acc + sec.data.reduce((x, u) => x + Number(u.summary?.total_rencana ?? 0), 0)
    }, 0)

    const totalSemuaRealisasi = sections.reduce((acc, sec) => {
        return acc + sec.data.reduce((x, u) => x + Number(u.summary?.total_realisasi ?? 0), 0)
    }, 0)

    const totalSemuaSisa = sections.reduce((acc, sec) => {
        return acc + sec.data.reduce((x, u) => x + Number(u.summary?.total_sisa ?? 0), 0)
    }, 0)

    const sectionHtml = sections.map((section, sectionIndex) => {
        const totalRencana = section.data.reduce((acc, u) => acc + Number(u.summary?.total_rencana ?? 0), 0)
        const totalRealisasi = section.data.reduce((acc, u) => acc + Number(u.summary?.total_realisasi ?? 0), 0)
        const totalSisa = section.data.reduce((acc, u) => acc + Number(u.summary?.total_sisa ?? 0), 0)
        const avgProgress = section.data.length
            ? Math.round(section.data.reduce((acc, u) => acc + Number(u.summary?.progress ?? 0), 0) / section.data.length)
            : 0
        const totalSurkesRencana = section.data.reduce(
            (acc, u) => acc + Number(u.alat_monitoring?.surkes?.rencana ?? 0),
            0
        )
        const totalSurkesRealisasi = section.data.reduce(
            (acc, u) => acc + Number(u.alat_monitoring?.surkes?.realisasi ?? 0),
            0
        )
        const totalSurkesBelum = section.data.reduce(
            (acc, u) => acc + Number(u.alat_monitoring?.surkes?.belum ?? 0),
            0
        )
        const totalNonSurkes = section.data.reduce(
            (acc, u) => acc + Number(u.alat_monitoring?.non_surkes?.jumlah ?? 0),
            0
        )
        const progressSurkes = totalSurkesRencana > 0
            ? Math.round((totalSurkesRealisasi / totalSurkesRencana) * 1000) / 10
            : 0

        const rows = section.data.map((u, idx) => buildExportUnitCard(u, idx + 1)).join('')

        return `
            <section class="${sectionIndex > 0 ? 'page-section page-break' : 'page-section'}">
                <div class="section-title">
                    <div>
                        <h2>Lokasi ${escapeHtml(section.lokasi)}</h2>
                        <p>${section.data.length} unit ditampilkan</p>
                    </div>
                </div>

                <div class="summary-grid">
                    <div class="summary-box">
                        <span>Total Rencana</span>
                        <strong>${formatRupiah(totalRencana)}</strong>
                    </div>
                    <div class="summary-box success">
                        <span>Total Realisasi</span>
                        <strong>${formatRupiah(totalRealisasi)}</strong>
                    </div>
                    <div class="summary-box warning">
                        <span>Total Sisa</span>
                        <strong>${formatRupiah(totalSisa)}</strong>
                    </div>
                    <div class="summary-box info">
                        <span>Rata-rata Progress</span>
                        <strong>${avgProgress}%</strong>
                    </div>
                </div>

                <div class="alat-summary-grid">
                    <div class="alat-summary-box plan">
                        <span>Surkes - Rencana</span>
                        <strong>${totalSurkesRencana} alat</strong>
                    </div>
                    <div class="alat-summary-box realized">
                        <span>Surkes - Realisasi</span>
                        <strong>${totalSurkesRealisasi} alat</strong>
                    </div>
                    <div class="alat-summary-box pending">
                        <span>Surkes - Belum</span>
                        <strong>${totalSurkesBelum} alat</strong>
                    </div>
                    <div class="alat-summary-box progress">
                        <span>Progress Surkes</span>
                        <strong>${progressSurkes}%</strong>
                    </div>
                    <div class="alat-summary-box non-surkes">
                        <span>Non-Surkes (Terpisah)</span>
                        <strong>${totalNonSurkes} alat</strong>
                    </div>
                </div>

                <div class="unit-list">
                    ${rows || `<div class="empty">Tidak ada data pada lokasi ${escapeHtml(section.lokasi)}</div>`}
                </div>
            </section>
        `
    }).join('')

    return `
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>RBK Monitoring ${getYearString()}</title>
            <style>
                @page {
                    size: A4 portrait;
                    margin: 12mm;
                }

                * {
                    box-sizing: border-box;
                }

                body {
                    margin: 0;
                    padding: 0;
                    font-family: Arial, Helvetica, sans-serif;
                    color: #111827;
                    background: #fff;
                    font-size: 11px;
                }

                .doc-header {
                    border: 1px solid #cbd5e1;
                    border-radius: 10px;
                    padding: 14px 16px;
                    margin-bottom: 14px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 20px;
                    background: #f8fafc;
                }

                .doc-title h1 {
                    margin: 0;
                    font-size: 20px;
                    line-height: 1.2;
                    letter-spacing: .2px;
                }

                .doc-title p {
                    margin: 5px 0 0;
                    color: #64748b;
                    font-size: 11px;
                }

                .doc-meta {
                    text-align: right;
                    font-size: 10px;
                    color: #334155;
                    line-height: 1.55;
                    min-width: 210px;
                }

                .grand-summary {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 8px;
                    margin-bottom: 14px;
                }

                .grand-box {
                    border: 1px solid #cbd5e1;
                    border-radius: 8px;
                    padding: 10px;
                    background: #fff;
                }

                .grand-box span {
                    display: block;
                    color: #64748b;
                    text-transform: uppercase;
                    font-size: 9px;
                    font-weight: bold;
                    letter-spacing: .4px;
                    margin-bottom: 5px;
                }

                .grand-box strong {
                    display: block;
                    font-size: 14px;
                    color: #0f172a;
                }

                .page-section {
                    margin-top: 10px;
                }

                .page-break {
                    page-break-before: always;
                }

                .section-title {
                    border-left: 5px solid #2563eb;
                    padding: 8px 10px;
                    margin: 12px 0 10px;
                    background: #eff6ff;
                    border-radius: 8px;
                }

                .section-title h2 {
                    margin: 0;
                    font-size: 15px;
                    color: #1e3a8a;
                }

                .section-title p {
                    margin: 3px 0 0;
                    color: #64748b;
                    font-size: 10px;
                }

                .summary-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 8px;
                    margin-bottom: 12px;
                }

                .summary-box {
                    border: 1px solid #bfdbfe;
                    border-radius: 8px;
                    padding: 8px;
                    background: #eff6ff;
                }

                .summary-box.success {
                    border-color: #bbf7d0;
                    background: #f0fdf4;
                }

                .summary-box.warning {
                    border-color: #fed7aa;
                    background: #fff7ed;
                }

                .summary-box.info {
                    border-color: #bae6fd;
                    background: #f0f9ff;
                }

                .summary-box span {
                    display: block;
                    font-size: 9px;
                    color: #64748b;
                    margin-bottom: 4px;
                    text-transform: uppercase;
                    font-weight: bold;
                }

                .summary-box strong {
                    display: block;
                    font-size: 12px;
                    color: #0f172a;
                }

                .alat-summary-grid {
                    display: grid;
                    grid-template-columns: repeat(5, 1fr);
                    gap: 6px;
                    margin: -4px 0 12px;
                }

                .alat-summary-box {
                    border: 1px solid #cbd5e1;
                    border-radius: 7px;
                    padding: 7px 8px;
                    background: #fff;
                }

                .alat-summary-box span {
                    display: block;
                    margin-bottom: 3px;
                    color: #64748b;
                    font-size: 8px;
                    font-weight: bold;
                    text-transform: uppercase;
                }

                .alat-summary-box strong {
                    color: #0f172a;
                    font-size: 11px;
                }

                .alat-summary-box.plan { border-color: #bfdbfe; background: #eff6ff; }
                .alat-summary-box.realized { border-color: #bbf7d0; background: #f0fdf4; }
                .alat-summary-box.pending { border-color: #fed7aa; background: #fff7ed; }
                .alat-summary-box.progress { border-color: #a7f3d0; background: #ecfdf5; }
                .alat-summary-box.non-surkes { border-color: #ddd6fe; background: #f5f3ff; }

                .unit-card {
                    border: 1px solid #dbe1e8;
                    border-radius: 10px;
                    margin-bottom: 10px;
                    overflow: hidden;
                    page-break-inside: avoid;
                }

                .unit-card-header {
                    background: #1f2937;
                    color: #fff;
                    padding: 9px 11px;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 10px;
                }

                .unit-card-header h3 {
                    margin: 0;
                    font-size: 13px;
                    line-height: 1.25;
                }

                .unit-card-header .no {
                    min-width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    background: #2563eb;
                    color: #fff;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 12px;
                }

                .unit-body {
                    padding: 10px 11px;
                }

                .unit-info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 6px 12px;
                    margin-bottom: 10px;
                }

                .info-row {
                    display: flex;
                    gap: 5px;
                    line-height: 1.35;
                }

                .info-row .label {
                    width: 82px;
                    color: #64748b;
                    flex-shrink: 0;
                }

                .info-row .value {
                    font-weight: 600;
                    color: #111827;
                    word-break: break-word;
                }

                .progress-row {
                    margin-bottom: 10px;
                    display: flex;
                    gap: 10px;
                    align-items: center;
                }

                .progress-bar {
                    flex: 1;
                    height: 8px;
                    border-radius: 99px;
                    overflow: hidden;
                    background: #e5e7eb;
                }

                .progress-fill {
                    height: 8px;
                    background: #10b981;
                    border-radius: 99px;
                }

                .progress-text {
                    width: 42px;
                    text-align: right;
                    font-weight: bold;
                    color: #059669;
                }

                .alat-unit-grid {
                    display: grid;
                    grid-template-columns: 2fr 1fr;
                    gap: 7px;
                    margin: 0 0 10px;
                }

                .alat-unit-panel {
                    border: 1px solid #dbeafe;
                    border-radius: 8px;
                    padding: 8px;
                    background: #f8fbff;
                }

                .alat-unit-panel.non-surkes {
                    border-color: #ddd6fe;
                    background: #faf8ff;
                }

                .alat-unit-title {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 8px;
                    margin-bottom: 6px;
                }

                .alat-unit-title strong {
                    color: #1e3a8a;
                    font-size: 10px;
                }

                .alat-unit-title span {
                    color: #059669;
                    font-size: 10px;
                    font-weight: bold;
                }

                .alat-unit-stats {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 5px;
                }

                .alat-unit-stat {
                    border-right: 1px solid #dbeafe;
                }

                .alat-unit-stat:last-child { border-right: 0; }

                .alat-unit-stat small {
                    display: block;
                    color: #64748b;
                    font-size: 8px;
                }

                .alat-unit-stat strong {
                    display: block;
                    margin-top: 2px;
                    color: #0f172a;
                    font-size: 12px;
                }

                .alat-unit-panel.non-surkes > strong {
                    display: block;
                    margin-top: 8px;
                    color: #6d28d9;
                    font-size: 18px;
                }

                .alat-unit-panel.non-surkes > small {
                    color: #64748b;
                    font-size: 8px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                th {
                    background: #f1f5f9;
                    color: #334155;
                    font-size: 9px;
                    text-transform: uppercase;
                    padding: 6px;
                    border: 1px solid #cbd5e1;
                }

                td {
                    padding: 6px;
                    border: 1px solid #cbd5e1;
                    vertical-align: top;
                    font-size: 10px;
                }

                .text-right {
                    text-align: right;
                }

                .text-center {
                    text-align: center;
                }

                .total-row td {
                    background: #f8fafc;
                    font-weight: bold;
                }

                .negative {
                    color: #dc2626;
                    font-weight: bold;
                }

                .positive {
                    color: #059669;
                    font-weight: bold;
                }

                .warning-text {
                    color: #d97706;
                    font-weight: bold;
                }

                .empty {
                    padding: 20px;
                    text-align: center;
                    color: #64748b;
                    border: 1px dashed #cbd5e1;
                    border-radius: 8px;
                }

                .footer {
                    margin-top: 16px;
                    padding-top: 8px;
                    border-top: 1px solid #cbd5e1;
                    color: #64748b;
                    font-size: 9px;
                    text-align: right;
                }

                @media print {
                    .no-print {
                        display: none !important;
                    }

                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                }
            </style>
        </head>
        <body>
            <div class="doc-header">
                <div class="doc-title">
                    <h1>RBK Monitoring</h1>
                    <p>Monitoring Rencana Biaya Kerja (Manpower, Material, Mobilisasi)</p>
                </div>
                <div class="doc-meta">
                    <div><strong>Tahun:</strong> ${escapeHtml(getYearString())}</div>
                    <div><strong>Filter:</strong> ${escapeHtml(searchKeyword.value || '-')}</div>
                    <div><strong>Urutan:</strong> ${escapeHtml(getSortLabel())}</div>
                    <div><strong>Tanggal Cetak:</strong> ${escapeHtml(tanggalCetak)}</div>
                </div>
            </div>

            <div class="grand-summary">
                <div class="grand-box">
                    <span>Total Rencana Semua</span>
                    <strong>${formatRupiah(totalSemuaRencana)}</strong>
                </div>
                <div class="grand-box">
                    <span>Total Realisasi Semua</span>
                    <strong>${formatRupiah(totalSemuaRealisasi)}</strong>
                </div>
                <div class="grand-box">
                    <span>Total Sisa Semua</span>
                    <strong>${formatRupiah(totalSemuaSisa)}</strong>
                </div>
            </div>

            ${sectionHtml}

            <div class="footer">
                Dicetak dari Sistem U-LAB - RBK Monitoring
            </div>

            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 400);
                }
            <\/script>
        </body>
        </html>
    `
}

function buildExportUnitCard(u: UnitRow, no: number) {
    const progress = Number(u.summary?.progress ?? 0)
    const alatSurkes = u.alat_monitoring?.surkes
    const alatNonSurkes = u.alat_monitoring?.non_surkes

    const manpower = u.summary?.kategori?.manpower
    const material = u.summary?.kategori?.material
    const mobilisasi = u.summary?.kategori?.mobilisasi

    return `
        <div class="unit-card">
            <div class="unit-card-header">
                <div>
                    <h3>${escapeHtml(u.namaperusahaan || '-')}</h3>
                </div>
                <div class="no">${no}</div>
            </div>

            <div class="unit-body">
                <div class="unit-info-grid">
                    <div class="info-row">
                        <span class="label">No Surat</span>
                        <span class="value">: ${escapeHtml(u.no_surat || '-')}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">No PRK</span>
                        <span class="value">: ${escapeHtml(u.no_prk || '-')}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Cost Code</span>
                        <span class="value">: ${escapeHtml(u.cost_code || '-')}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Jadwal</span>
                        <span class="value">: ${escapeHtml(u.jadwal_kalibrasi || '-')}</span>
                    </div>
                </div>

                <div class="progress-row">
                    <strong>Progress Anggaran</strong>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${Math.min(Math.max(progress, 0), 100)}%;"></div>
                    </div>
                    <div class="progress-text">${progress}%</div>
                </div>

                <div class="alat-unit-grid">
                    <div class="alat-unit-panel">
                        <div class="alat-unit-title">
                            <strong>ALAT SURKES</strong>
                            <span>${Number(alatSurkes?.progress ?? 0)}%</span>
                        </div>
                        <div class="alat-unit-stats">
                            <div class="alat-unit-stat">
                                <small>Rencana</small>
                                <strong>${Number(alatSurkes?.rencana ?? 0)}</strong>
                            </div>
                            <div class="alat-unit-stat">
                                <small>Realisasi</small>
                                <strong>${Number(alatSurkes?.realisasi ?? 0)}</strong>
                            </div>
                            <div class="alat-unit-stat">
                                <small>Belum</small>
                                <strong>${Number(alatSurkes?.belum ?? 0)}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="alat-unit-panel non-surkes">
                        <div class="alat-unit-title">
                            <strong>ALAT NON-SURKES</strong>
                        </div>
                        <strong>${Number(alatNonSurkes?.jumlah ?? 0)}</strong>
                        <small>alat terdaftar (terpisah)</small>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-right">Rencana</th>
                            <th class="text-right">Realisasi</th>
                            <th class="text-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${buildExportKategoriRow('Manpower', manpower)}
                        ${buildExportKategoriRow('Material', material)}
                        ${buildExportKategoriRow('Mobilisasi', mobilisasi)}
                        <tr class="total-row">
                            <td>Total</td>
                            <td class="text-right">${formatRupiah(u.summary?.total_rencana ?? 0)}</td>
                            <td class="text-right positive">${formatRupiah(u.summary?.total_realisasi ?? 0)}</td>
                            <td class="text-right ${Number(u.summary?.total_sisa ?? 0) < 0 ? 'negative' : 'warning-text'}">
                                ${formatRupiah(u.summary?.total_sisa ?? 0)}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `
}

function buildExportKategoriRow(label: string, data: any) {
    const sisa = Number(data?.sisa ?? 0)

    return `
        <tr>
            <td>${escapeHtml(label)}</td>
            <td class="text-right">${formatRupiah(data?.rencana ?? 0)}</td>
            <td class="text-right">${formatRupiah(data?.realisasi ?? 0)}</td>
            <td class="text-right ${sisa < 0 ? 'negative' : 'warning-text'}">${formatRupiah(sisa)}</td>
        </tr>
    `
}

function openPrintPdf(html: string, title: string) {
    const printWindow = window.open('', '_blank')

    if (!printWindow) {
        H.alert('error', 'Popup diblokir browser. Izinkan popup untuk export PDF.')
        return
    }

    printWindow.document.open()
    printWindow.document.write(html)
    printWindow.document.close()
    printWindow.document.title = title
}

function escapeHtml(value: any) {
    const text = String(value ?? '')
    return text
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;')
}

function getSortLabel() {
    if (sortBy.value === 'total_realisasi') return 'Total Realisasi'
    if (sortBy.value === 'progress') return 'Progress (%)'
    return 'Total Rencana'
}

function getTotalChartSeries(u: UnitRow) {
    const real = u.summary?.total_realisasi ?? 0
    const sisa = u.summary?.total_sisa ?? 0
    return [{ data: [real, sisa] }]
}

function getTotalChartOptions(u: UnitRow) {
    return {
        chart: {
            type: 'bar',
            height: 90,
            toolbar: { show: false },
            sparkline: { enabled: false },
            fontFamily: 'Roboto, sans-serif',
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: true,
                barHeight: '60%',
                distributed: true,
            },
        },
        colors: ['#10b981', '#f59e0b'],
        dataLabels: {
            enabled: true,
            formatter: (val: any) => (val !== 0 ? formatShortRupiah(val) : ''),
            textAnchor: 'start',
            style: {
                colors: ['#fff'],
                fontSize: '12px',
                fontWeight: 700,
            },
            offsetX: 0,
            dropShadow: { enabled: true, top: 1, left: 1, blur: 1, opacity: 0.5 },
        },
        grid: {
            show: false,
            padding: { top: -15, right: 10, bottom: -15, left: 10 },
        },
        xaxis: {
            categories: ['Realisasi', 'Sisa'],
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                show: true,
                style: { colors: '#fff', fontSize: '13px', fontWeight: 600 },
                offsetX: 0,
            },
        },
        tooltip: { theme: 'dark', y: { formatter: (val: any) => formatRupiah(val) } },
        legend: { show: false },
    }
}

const dynamicChartHeight = computed(() => {
    const itemCount = units.value.length
    const calculatedHeight = 50 + (itemCount * 30)
    return Math.max(calculatedHeight, 400)
})

const summaryChartSeries = computed(() => {
    const data = units.value.map((u) => u.summary?.total_rencana ?? 0)
    return [{ name: 'Total Rencana', data }]
})

const summaryChartOptions = computed(() => {
    const categories = units.value.map((u) => u.namaperusahaan)
    return {
        chart: { type: 'bar', toolbar: { show: false } },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%', distributed: false } },
        colors: ['#3b82f6'],
        dataLabels: {
            enabled: true,
            textAnchor: 'start',
            style: { colors: ['#fff'] },
            formatter: (val: any) => formatShortRupiah(val),
            offsetX: 0,
        },
        xaxis: { categories, labels: { show: true } },
        yaxis: {
            labels: { show: true, style: { fontSize: '11px', fontFamily: 'Roboto, sans-serif' }, maxWidth: 200 },
        },
        grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
        tooltip: { theme: 'light', y: { formatter: (val: any) => formatRupiah(val) } },
    }
})

// RBK EDITOR
function openRbkModal(u: UnitRow) {
    selectedUnit.value = u
    isRbkModalOpen.value = true

    rbkForm.value = {
        judul: '',
        no_surat: '',
        no_prk: '',
        cost_code: '',
        jadwal_kalibrasi: '',
    }
    rbkItems.value = []
    rbkHeaderInfo.value = null

    loadRbkDetailForEdit(u.unit_id)
}

function closeRbkModal() {
    isRbkModalOpen.value = false
    selectedUnit.value = null
    rbkItems.value = []
    rbkHeaderInfo.value = null
}

async function loadRbkDetailForEdit(unitfk: number) {
    try {
        const params = new URLSearchParams({
            unitfk: String(unitfk),
            tahun: getYearString(),
            lokasi_id: String(getLokasiId()),
            source_pbj: '1',
        })
        const res = await useApi().get(`rbk/get-rbk-by-unit?${params.toString()}`)

        if (res?.data?.header) {
            rbkHeaderInfo.value = res.data.header

            rbkForm.value.judul = res.data.header.judul || ''
            rbkForm.value.no_surat = res.data.header.no_surat || ''
            rbkForm.value.no_prk = res.data.header.no_prk || ''
            rbkForm.value.cost_code = res.data.header.cost_code || ''
            rbkForm.value.jadwal_kalibrasi = res.data.header.jadwal_kalibrasi || ''

            const items = (res.data.items || []) as RbkItem[]
            rbkItems.value = items.map((it, idx) => ({
                _key: `${it.norec || idx}-${Date.now()}`,
                norec: it.norec,
                kategori: it.kategori,
                uraian: it.uraian || '',
                qty: Number(it.qty ?? 1),
                satuan: it.satuan || '',
                nilai_rencana: Number(it.nilai_rencana ?? 0),
                urutan: Number(it.urutan ?? idx),
            }))
        }
    } catch (e) {
        rbkHeaderInfo.value = null
    }
}

function addItem(kategori: 'manpower' | 'material' | 'mobilisasi') {
    rbkItems.value.push({
        _key: `${kategori}-${Date.now()}-${Math.random().toString(16).slice(2)}`,
        kategori,
        uraian: '',
        qty: 1,
        satuan: 'Lot',
        nilai_rencana: 0,
        urutan: rbkItems.value.filter((i) => i.kategori === kategori).length,
    })
}

function removeLocalItem(key: string) {
    rbkItems.value = rbkItems.value.filter((i) => i._key !== key)
}

function rbkItemsBy(kategori: 'manpower' | 'material' | 'mobilisasi') {
    return rbkItems.value.filter((i) => i.kategori === kategori)
}

function calcTotalBy(kategori: 'manpower' | 'material' | 'mobilisasi') {
    return rbkItemsBy(kategori).reduce((acc, it) => acc + (Number(it.nilai_rencana) || 0), 0)
}

function calcGrandTotal() {
    return calcTotalBy('manpower') + calcTotalBy('material') + calcTotalBy('mobilisasi')
}

async function saveRbk() {
    if (!selectedUnit.value) return

    const filtered = rbkItems.value.filter((i) => (i.uraian || '').trim().length > 0)
    if (filtered.length === 0) {
        H.alert('warning', 'Minimal isi 1 item RBK')
        return
    }

    savingRbk.value = true
    try {
        const payload = {
            unitfk: selectedUnit.value.unit_id,
            lokasi_id: getLokasiId(),
            tahun: Number(getYearString()),
            judul: rbkForm.value.judul,
            no_surat: rbkForm.value.no_surat,
            no_prk: rbkForm.value.no_prk,
            cost_code: rbkForm.value.cost_code,
            jadwal_kalibrasi: rbkForm.value.jadwal_kalibrasi,
            items: filtered.map((i, idx) => ({
                norec: i.norec,
                kategori: i.kategori,
                uraian: i.uraian,
                qty: Number(i.qty ?? 1),
                satuan: i.satuan,
                nilai_rencana: Number(i.nilai_rencana ?? 0),
                urutan: idx,
            })),
        }

        await useApi().post('rbk/save-rbk', payload)
        H.alert('success', 'RBK berhasil disimpan')
        await loadUnits()
        closeRbkModal()
    } catch (err: any) {
        console.error(err)
        H.alert('error', 'Gagal simpan RBK')
    } finally {
        savingRbk.value = false
    }
}

// REALISASI MODAL
function openRealisasiModal(u: UnitRow) {
    selectedUnit.value = u
    isRealisasiModalOpen.value = true
    isRealisasiFullscreen.value = false

    rbkDetail.value = null
    realisasiRows.value = []
    resetManualForm()

    loadRbkDetailAndRealisasi(u.unit_id)
}

function closeRealisasiModal() {
    isRealisasiModalOpen.value = false
    isRealisasiFullscreen.value = false
    rbkDetail.value = null
    realisasiRows.value = []
    loadingRbkDetail.value = false
    loadingRealisasi.value = false
    resetManualForm()
}

function toggleRealisasiFullscreen() {
    isRealisasiFullscreen.value = !isRealisasiFullscreen.value
}

async function loadRbkDetailAndRealisasi(unitfk: number) {
    loadingRbkDetail.value = true
    try {
        const params = new URLSearchParams({
            unitfk: String(unitfk),
            tahun: getYearString(),
            lokasi_id: String(getLokasiId()),
            source_pbj: '1',
        })
        const res = await useApi().get(`rbk/get-rbk-by-unit?${params.toString()}`)
        rbkDetail.value = (res?.data ?? null) as RbkDetail | null

        if (rbkDetail.value?.header) {
            await loadRealisasi(unitfk)
        }
    } catch (e) {
        console.error('loadRbkDetailAndRealisasi error', e)
        rbkDetail.value = null
        realisasiRows.value = []
    } finally {
        loadingRbkDetail.value = false
    }
}

async function loadRealisasi(unitfk: number) {
    loadingRealisasi.value = true
    try {
        const params = new URLSearchParams({
            unitfk: String(unitfk),
            tahun: getYearString(),
            lokasi_id: String(getLokasiId()),
            source_pbj: '1',
        })
        const res = await useApi().get(`rbk/get-realisasi-by-unit?${params.toString()}`)
        realisasiRows.value = Array.isArray(res?.data) ? res.data : (res?.data ?? [])
    } catch (e) {
        console.error('loadRealisasi error', e)
        realisasiRows.value = []
    } finally {
        loadingRealisasi.value = false
    }
}

function getRealisasiRows(kategori: string) {
    return realisasiRows.value.filter((r) => (r.kategori || '').toLowerCase() === kategori.toLowerCase())
}

const groupedRealisasiByCategory = computed<Record<string, any[]>>(() => {
    const result: Record<string, any[]> = {}

    for (const category of realisasiCategories) {
        const groups = new Map<string, any>()
        const categoryRows = realisasiRows.value.filter(
            (row) => String(row.kategori || '').toLowerCase() === category.key
        )

        for (const row of categoryRows) {
            const isPbj = row.source === 'pbj'
            const sourceKey = isPbj
                ? String(row.pbj_norec || row.pbj_detail_norec || row.norec)
                : String(row.norec)
            const groupKey = `${category.key}:${isPbj ? 'pbj' : 'manual'}:${sourceKey}`

            if (!groups.has(groupKey)) {
                groups.set(groupKey, {
                    ...row,
                    group_key: groupKey,
                    items: [row],
                    nilai_realisasi: Number(row.nilai_realisasi || 0),
                    nilai_subtotal_detail: Number(row.nilai_subtotal_detail || 0),
                    nilai_ppn_detail: Number(row.nilai_ppn_detail || 0),
                })
                continue
            }

            const group = groups.get(groupKey)
            group.items.push(row)
            group.nilai_realisasi += Number(row.nilai_realisasi || 0)
            group.nilai_subtotal_detail += Number(row.nilai_subtotal_detail || 0)
            group.nilai_ppn_detail += Number(row.nilai_ppn_detail || 0)
        }

        result[category.key] = Array.from(groups.values())
    }

    return result
})

function getGroupedRealisasiRows(kategori: string) {
    return groupedRealisasiByCategory.value[String(kategori).toLowerCase()] ?? []
}

function exportRealisasiExcel() {
    if (!rbkDetail.value?.header || !selectedUnit.value) {
        H.alert('warning', 'Data realisasi RBK belum tersedia')
        return
    }

    exportingExcel.value = true
    try {
        const total = rbkDetail.value.summary?.total || {}
        const fileName = exportRbkRealisasiExcel({
            unitName: selectedUnit.value.namaperusahaan || 'Unit RBK',
            year: getYearString(),
            location: activeTab.value === 'jakarta' ? 'Jakarta' : 'Gresik',
            total: {
                rencana: Number(total.rencana || 0),
                realisasi: Number(total.realisasi || 0),
                sisa: Number(total.sisa || 0),
                progress: Number(total.progress || 0),
            },
            categories: realisasiCategories.map((category) => {
                const summary = rbkDetail.value?.summary?.[category.key] || {}
                return {
                    key: category.key,
                    label: category.label,
                    summary: {
                        rencana: Number(summary.rencana || 0),
                        realisasi: Number(summary.realisasi || 0),
                        sisa: Number(summary.sisa || 0),
                    },
                }
            }),
        })
        H.alert('success', `Excel berhasil diunduh: ${fileName}`)
    } catch (error) {
        console.error('exportRealisasiExcel error', error)
        H.alert('error', 'Gagal membuat Excel realisasi RBK')
    } finally {
        exportingExcel.value = false
    }
}

function categoryLabel(kategori: string) {
    const found = realisasiCategories.find((item) => item.key === String(kategori).toLowerCase())
    return found?.label ?? kategori
}

function getTodayInputValue() {
    const now = new Date()
    const offset = now.getTimezoneOffset()
    return new Date(now.getTime() - offset * 60_000).toISOString().slice(0, 10)
}

function toDateInputValue(value: any) {
    if (!value) return getTodayInputValue()
    const match = String(value).match(/^\d{4}-\d{2}-\d{2}/)
    return match?.[0] ?? getTodayInputValue()
}

function resetManualForm() {
    editingManualNorec.value = null
    manualForm.value = {
        item_norec: '',
        nilai_realisasi: 0,
        tanggal: getTodayInputValue(),
        keterangan: '',
    }
}

function handleManualMoneyInput(event: Event) {
    const target = event.target as HTMLInputElement
    const clean = target.value.replace(/\D/g, '')
    manualForm.value.nilai_realisasi = Number(clean || 0)
    target.value = clean ? Number(clean).toLocaleString('id-ID') : ''
}

function editManualRealisasi(row: any) {
    editingManualNorec.value = String(row.norec)
    manualForm.value = {
        item_norec: String(row.item_norec || row.rbk_item_norec || ''),
        nilai_realisasi: Number(row.nilai_realisasi || 0),
        tanggal: toDateInputValue(row.tanggal || row.created_at),
        keterangan: row.keterangan || '',
    }
}

async function saveManualRealisasi() {
    if (!manualForm.value.item_norec) {
        H.alert('warning', 'Pilih item rencana RBK terlebih dahulu')
        return
    }
    if (Number(manualForm.value.nilai_realisasi) <= 0) {
        H.alert('warning', 'Nilai realisasi harus lebih dari 0')
        return
    }

    savingManualRealisasi.value = true
    try {
        const payload: Record<string, any> = {
            item_norec: manualForm.value.item_norec,
            nilai_realisasi: Number(manualForm.value.nilai_realisasi),
            tanggal: manualForm.value.tanggal,
            keterangan: manualForm.value.keterangan,
            source_hybrid: true,
        }

        if (editingManualNorec.value) {
            payload.norec = editingManualNorec.value
            await useApi().post('rbk/update-realisasi', payload)
            H.alert('success', 'Realisasi manual berhasil diperbarui')
        } else {
            await useApi().post('rbk/save-realisasi', payload)
            H.alert('success', 'Realisasi manual berhasil ditambahkan')
        }

        resetManualForm()
        if (selectedUnit.value?.unit_id) {
            await loadRbkDetailAndRealisasi(selectedUnit.value.unit_id)
        }
        await loadUnits()
    } catch (error) {
        console.error('saveManualRealisasi error', error)
        H.alert('error', 'Gagal menyimpan realisasi manual')
    } finally {
        savingManualRealisasi.value = false
    }
}

function printPbj(row: any) {
    if (!row?.pbj_norec) {
        H.alert('warning', 'Data PBJ tidak ditemukan')
        return
    }
    H.printBlade(`pbj/cetak-pbj?pdf=true&norec=${encodeURIComponent(row.pbj_norec)}`)
}

function formatPbjMatch(matchBy: string) {
    const labels: Record<string, string> = {
        user_unit: 'User Unit',
        prk: 'PRK',
        cost_code: 'Cost Code',
    }

    return String(matchBy || '')
        .split('+')
        .map((key) => labels[key] || key)
        .filter(Boolean)
        .join(' + ') || '-'
}

// FORMAT
function formatNumberForInput(val: any) {
    if (val === null || val === undefined || val === '') return ''
    const n = Number(val)
    if (isNaN(n)) return ''
    return n.toLocaleString('id-ID')
}

function parseMoneyInput(e: any) {
    let val = e.target.value
    const clean = val.replace(/\D/g, '')
    const num = clean ? parseInt(clean, 10) : 0
    if (clean) {
        e.target.value = num.toLocaleString('id-ID')
    } else {
        e.target.value = ''
    }
    return num
}

function formatRupiah(val: any) {
    const n = Number(val ?? 0)
    return (n < 0 ? '-' : '') + 'Rp' + Math.abs(n).toLocaleString('id-ID')
}

function formatShortRupiah(val: any) {
    const n = Number(val ?? 0)
    const absN = Math.abs(n)
    let res = String(n)

    if (absN >= 1_000_000_000) res = (absN / 1_000_000_000).toFixed(1).replace('.0', '') + ' M'
    else if (absN >= 1_000_000) res = (absN / 1_000_000).toFixed(1).replace('.0', '') + ' Jt'
    else if (absN >= 1_000) res = (absN / 1_000).toFixed(1).replace('.0', '') + ' Rb'

    return (n < 0 ? '-' : '') + res
}

function formatDate(dateStr: string) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return (
        d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: '2-digit' }) +
        ' ' +
        d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    )
}

onMounted(() => {
    loadUnits()
})
</script>

<style scoped lang="scss">
.rbk-wrap {
    padding: 12px;
}

.rbk-header {
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

.rbk-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.add-unit-modal {
    width: min(860px, calc(100vw - 40px));
    max-height: calc(100vh - 40px);

    .modal-card-head {
        align-items: flex-start;
        padding: 26px 30px;
    }

    .modal-card-subtitle {
        color: #64748b;
        font-size: 0.9rem;
        margin-top: 4px;
    }

    .modal-card-body {
        min-height: 430px;
        padding: 30px;
    }

    .modal-card-foot {
        padding: 20px 30px;
    }
}

.location-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.location-option {
    align-items: center;
    border: 1px solid #dbe3ef;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    gap: 12px;
    padding: 14px;
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;

    input {
        margin: 0;
    }

    .location-icon {
        align-items: center;
        background: #eff6ff;
        border-radius: 8px;
        color: #2563eb;
        display: flex;
        font-size: 20px;
        height: 38px;
        justify-content: center;
        width: 38px;
    }

    strong,
    small {
        display: block;
    }

    small {
        color: #64748b;
        margin-top: 2px;
    }

    &.is-selected {
        background: #eff6ff;
        border-color: #2563eb;
        box-shadow: 0 0 0 1px #2563eb;
    }
}

@media (max-width: 600px) {
    .add-unit-modal {
        width: calc(100vw - 20px);

        .modal-card-head,
        .modal-card-body,
        .modal-card-foot {
            padding-left: 18px;
            padding-right: 18px;
        }

        .modal-card-body {
            min-height: 360px;
        }
    }

    .location-options {
        grid-template-columns: 1fr;
    }
}

.select select,
.input,
:deep(.p-calendar .p-inputtext) {
    height: 40px;
    border-color: #dbdbdb;
}

.rbk-unit-card {
    border: 1px solid #e1e4e8;
    border-radius: 8px;
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

    .card-image.header-dark {
        height: auto;
        min-height: 80px;
        background: #1f2937;
        position: relative;
        padding: 12px 16px;
        border-bottom: 1px solid #374151;

        .chart-wrapper {
            position: relative;
            z-index: 1;
        }
    }

    .card-content {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .unit-info-block {
        .detail-rows {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            font-size: 0.9rem;

            .lbl {
                width: 90px;
                color: #6b7280;
                flex-shrink: 0;
            }

            .val {
                color: #1f2937;
                font-weight: 500;
                word-break: break-word;

                &.is-code {
                    font-family: monospace;
                    font-size: 0.95em;
                }
            }
        }
    }

    .content-body {
        margin-top: 12px;
        margin-bottom: 12px;
        padding: 8px 0;
    }

    .card-footer-action {
        margin-top: auto;
        text-align: right;
        border-top: 1px solid #f3f4f6;

        .action-link {
            font-weight: 600;
            color: #2563eb;
            cursor: pointer;
            display: inline-flex;
            gap: 6px;
            font-size: 0.85rem;
            align-items: center;

            &:hover {
                color: #1d4ed8;
                text-decoration: underline;
            }
        }
    }
}

.alat-monitoring-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(150px, 1fr);
    gap: 10px;
}

.alat-monitoring-card {
    min-width: 0;
    padding: 12px;
    border: 1px solid #dbeafe;
    border-radius: 8px;
    background: #f8fbff;

    &.is-non-surkes {
        border-color: #ddd6fe;
        background: #faf8ff;
    }
}

.alat-monitoring-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;

    h4 {
        margin: 1px 0 0;
        color: #1e3a8a;
        font-size: 0.88rem;
        font-weight: 600;
    }

    .iconify {
        color: #7c3aed;
        font-size: 1.15rem;
    }
}

.alat-monitoring-kicker {
    display: block;
    color: #64748b;
    font-size: 0.62rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.alat-progress-badge {
    padding: 3px 7px;
    border: 1px solid #a7f3d0;
    border-radius: 99px;
    color: #047857;
    background: #ecfdf5;
    font-size: 0.72rem;
    font-weight: 600;
}

.alat-stat-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px;
    margin-top: 11px;
}

.alat-stat {
    min-width: 0;
    padding-right: 6px;
    padding-left: 0;
    border-top: 0;
    border-bottom: 0;
    border-left: 0;
    border-right: 1px solid #dbeafe;
    text-align: left;
    background: transparent;
    cursor: pointer;
    transition: transform 150ms ease, background-color 150ms ease;

    &:hover,
    &:focus-visible {
        border-radius: 6px;
        outline: none;
        background: rgba(37, 99, 235, 0.08);
        transform: translateY(-1px);
    }

    &:last-child {
        padding-right: 0;
        border-right: 0;
    }

    span,
    strong {
        display: block;
    }

    span {
        overflow: hidden;
        color: #64748b;
        font-size: 0.65rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    strong {
        margin-top: 2px;
        color: #1e40af;
        font-size: 1.05rem;
        font-weight: 600;
    }

    &.is-realized strong { color: #059669; }
    &.is-pending strong { color: #d97706; }
}

.alat-progress-track {
    height: 5px;
    margin-top: 10px;
    overflow: hidden;
    border-radius: 99px;
    background: #dbeafe;

    span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: #10b981;
    }
}

.alat-progress-copy {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 0.62rem;
    line-height: 1.35;
}

.non-surkes-value {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-top: 12px;

    strong {
        color: #6d28d9;
        font-size: 1.45rem;
        font-weight: 600;
    }

    span {
        color: #64748b;
        font-size: 0.66rem;
    }
}

.alat-monitoring-card.is-clickable {
    cursor: pointer;
    transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;

    &:hover,
    &:focus-visible {
        border-color: #a78bfa;
        outline: none;
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.12);
        transform: translateY(-1px);
    }
}

.rbk-alat-dialog {
    width: min(1760px, 98vw);
    height: min(940px, 94vh);
}

.rbk-alat-header {
    min-height: 82px;
    background: linear-gradient(120deg, #0f4c81, #2563eb 62%, #0ea5e9);
}

.rbk-alat-body {
    display: flex;
    padding: 16px 18px 18px;
    flex-direction: column;
    overflow: hidden;
}

.rbk-alat-summary {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 12px;
    padding: 13px 16px;
    border: 1px solid #bae6fd;
    border-left: 5px solid #0ea5e9;
    border-radius: 11px;
    background: #eff8ff;

    strong {
        color: #1e293b;
        font-size: 0.9rem;
    }

    p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 0.76rem;
    }
}

.rbk-alat-count {
    flex: 0 0 auto;
    padding: 8px 13px;
    border-radius: 999px;
    color: #fff;
    background: #0ea5e9;
    font-size: 0.76rem;
    font-weight: 700;
}

.rbk-alat-toolbar {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}

.rbk-alat-mini-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;

    span {
        padding: 7px 10px;
        border: 1px solid #dbe4ef;
        border-radius: 8px;
        color: #475569;
        background: #fff;
        font-size: 0.72rem;

        &.is-success {
            border-color: #a7f3d0;
            color: #047857;
            background: #ecfdf5;
        }

        &.is-warning {
            border-color: #fed7aa;
            color: #c2410c;
            background: #fff7ed;
        }
    }
}

.rbk-alat-search {
    width: min(440px, 100%);
    margin-bottom: 0 !important;
}

.rbk-alat-table {
    min-height: 0;
    flex: 1 1 auto;
    overflow: hidden;
}

.rbk-alat-name {
    display: block;
    color: #334155;
    font-size: 0.78rem;
    line-height: 1.35;
}

.rbk-alat-muted {
    display: block;
    margin-top: 3px;
    color: #64748b;
    font-size: 0.69rem;
}

.rbk-work-badge,
.rbk-last-status,
.rbk-pickup-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1.35;
}

.rbk-work-badge {
    padding: 5px 9px;
    color: #b45309;
    background: #fef3c7;

    &.is-done {
        color: #047857;
        background: #d1fae5;
    }
}

.rbk-last-status {
    padding: 7px 10px;
    border: 1px solid #fed7aa;
    color: #9a3412;
    background: #fff7ed;

    &.is-done {
        border-color: #86efac;
        color: #15803d;
        background: #dcfce7;
    }
}

.rbk-pickup-badge {
    padding: 6px 10px;
    color: #fff;
    background: #ef4444;

    &.is-picked {
        background: #2563eb;
    }
}

.rbk-row-progress {
    display: flex;
    align-items: center;
    gap: 7px;

    > div {
        width: 72px;
        height: 7px;
        overflow: hidden;
        border-radius: 99px;
        background: #e2e8f0;

        span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #0ea5e9, #10b981);
        }
    }

    strong {
        color: #334155;
        font-size: 0.72rem;
    }
}

:deep(.rbk-alat-table .p-datatable-wrapper) {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}

:deep(.rbk-alat-table .p-datatable-thead > tr > th) {
    color: #334155;
    background: #f1f5f9;
    font-size: 0.72rem;
    white-space: normal;
}

:deep(.rbk-alat-table .p-datatable-tbody > tr > td) {
    color: #475569;
    font-size: 0.74rem;
    vertical-align: top;
}

@media (max-width: 1100px) {
    .alat-monitoring-grid {
        grid-template-columns: 1fr;
    }

    .rbk-alat-toolbar,
    .rbk-alat-summary {
        align-items: stretch;
        flex-direction: column;
    }

    .rbk-alat-search {
        width: 100%;
    }
}

:global(.is-dark) {
    .rbk-unit-card {
        border-color: #334155;
        background: #111827;

        .card-content,
        .unit-info-block .detail-item .val,
        .title.has-text-dark {
            color: #e5e7eb !important;
        }

        .card-footer-action {
            border-top-color: #334155;
        }
    }

    .alat-monitoring-card {
        border-color: #1e3a5f;
        background: #142033;

        &.is-non-surkes {
            border-color: #47366d;
            background: #211a33;
        }
    }

    .alat-monitoring-head h4,
    .alat-stat strong {
        color: #bfdbfe;
    }

    .alat-monitoring-kicker,
    .alat-progress-copy,
    .alat-stat span,
    .non-surkes-value span,
    .rbk-unit-card .unit-info-block .detail-item .lbl {
        color: #94a3b8;
    }

    .alat-stat {
        border-right-color: #334155;
    }

    .alat-stat.is-realized strong { color: #6ee7b7; }
    .alat-stat.is-pending strong { color: #fbbf24; }
    .non-surkes-value strong { color: #c4b5fd; }
    .alat-progress-track { background: #263a58; }

    .rbk-alat-summary {
        border-color: #1e4f70;
        background: #10263a;

        strong { color: #e2e8f0; }
        p { color: #94a3b8; }
    }

    .rbk-alat-mini-stats span,
    .rbk-alat-name {
        border-color: #334155;
        color: #cbd5e1;
        background: #172033;
    }

    .rbk-kat-box {
        border-color: #334155;
        background: #172033;

        .heading-kat,
        .val-row .l,
        .val-row.highlight .l {
            color: #94a3b8;
        }

        .heading-kat,
        .val-row.highlight {
            border-color: #334155;
        }

        .val-row .r {
            color: #e2e8f0;
        }
    }
}

.rbk-kategori-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    margin-top: 8px;

    @media (min-width: 769px) {
        grid-template-columns: 1fr 1fr 1fr;
    }
}

.rbk-kat-box {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 10px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);

    .heading-kat {
        font-size: 0.7rem;
        font-weight: 700;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #f3f4f6;
        padding-bottom: 4px;
    }

    .val-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
        font-size: 0.8rem;

        .l {
            color: #9ca3af;
            font-weight: 500;
            margin-right: 4px;
        }

        .r {
            font-weight: 600;
            color: #374151;
        }

        &.highlight {
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px dashed #f3f4f6;

            .l {
                color: #4b5563;
            }
        }
    }
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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

.placeholder-nodata {
    text-align: center;
    padding: 40px;

    h3 {
        font-weight: 600;
        color: #283e59;
    }
}

.rbk-skeleton-card {
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

.modal-wide {
    width: 1500px;
    max-width: 96%;
    border-radius: 12px;
}

.rbk-hybrid-overlay {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.rbk-hybrid-backdrop {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: blur(4px);
    cursor: default;
}

.rbk-hybrid-dialog {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    width: min(1480px, 96vw);
    height: min(900px, 92vh);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.28);
    border-radius: 20px;
    background: #f6f8fc;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.38);
    transition: width 180ms ease, height 180ms ease, border-radius 180ms ease;

    &.is-fullscreen {
        width: 100vw;
        height: 100vh;
        max-width: none;
        max-height: none;
        border: 0;
        border-radius: 0;
    }
}

.rbk-hybrid-header {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    min-height: 88px;
    padding: 18px 24px;
    color: #fff;
    background: #2563eb;
}

.rbk-hybrid-title-wrap {
    display: flex;
    align-items: center;
    min-width: 0;
    gap: 14px;

    h2 {
        overflow: hidden;
        margin: 1px 0 2px;
        color: #fff;
        font-size: 1.2rem;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    p:last-child {
        margin: 0;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.8rem;
    }
}

.rbk-hybrid-title-icon {
    display: grid;
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.14);
    font-size: 1.35rem;
}

.rbk-hybrid-eyebrow {
    margin: 0;
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.rbk-hybrid-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rbk-excel-btn {
    display: inline-flex;
    height: 40px;
    padding: 0 14px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border: 1px solid rgba(255, 255, 255, 0.32);
    border-radius: 11px;
    color: #fff;
    background: rgba(255, 255, 255, 0.16);
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;

    &:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.26);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.rbk-modal-icon-btn {
    display: grid;
    width: 40px;
    height: 40px;
    padding: 0;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.28);
    border-radius: 11px;
    color: #fff;
    background: rgba(255, 255, 255, 0.12);
    cursor: pointer;

    &:hover {
        background: rgba(255, 255, 255, 0.23);
    }

    &.is-close:hover {
        background: rgba(239, 68, 68, 0.8);
    }
}

.rbk-hybrid-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    padding: 22px;
}

.rbk-modal-loading,
.rbk-empty-state {
    display: flex;
    min-height: 240px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 10px;
    color: #64748b;
    font-size: 0.9rem;

    .iconify {
        font-size: 1.8rem;
    }

    &.is-small {
        min-height: 150px;
    }
}

.rbk-hybrid-info {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 16px;
    padding: 12px 14px;
    border: 1px solid #bae6fd;
    border-radius: 12px;
    color: #075985;
    background: #f0f9ff;
    font-size: 0.8rem;
    line-height: 1.5;

    .iconify {
        flex: 0 0 auto;
        margin-top: 2px;
    }
}

.rbk-overview-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 14px;
}

.rbk-overview-card {
    display: flex;
    min-width: 0;
    padding: 14px 16px;
    flex-direction: column;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #fff;

    span {
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 600;
    }

    strong {
        overflow: hidden;
        margin-top: 3px;
        color: #0f172a;
        font-size: 1.12rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &.is-success {
        border-color: #a7f3d0;
        background: #ecfdf5;

        strong { color: #047857; }
    }

    &.is-primary {
        border-color: #bfdbfe;
        background: #eff6ff;

        strong { color: #1d4ed8; }
    }

    &.is-danger {
        border-color: #fecaca;
        background: #fef2f2;

        strong { color: #dc2626; }
    }
}

.rbk-category-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.rbk-category-card {
    padding: 13px 15px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #fff;

    > div:not(.rbk-category-card-title) {
        display: flex;
        justify-content: space-between;
        padding: 3px 0;
        color: #64748b;
        font-size: 0.78rem;

        strong { color: #1e293b; }
        &.is-realization strong { color: #059669; }
        &.has-negative strong { color: #dc2626; }
    }
}

.rbk-category-card-title {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 7px;
    color: #334155;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
}

.rbk-hybrid-content-grid {
    display: grid;
    grid-template-columns: minmax(300px, 340px) minmax(0, 1fr);
    align-items: start;
    gap: 16px;
}

.rbk-manual-card,
.rbk-history-card {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 5px 18px rgba(15, 23, 42, 0.05);
}

.rbk-manual-card {
    position: sticky;
    top: 0;
    padding: 18px;
}

.rbk-history-card {
    min-width: 0;
    padding: 18px;
}

.rbk-section-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;

    h3 {
        margin: 4px 0 0;
        color: #1e293b;
        font-size: 1rem;
        font-weight: 700;
    }

    .rbk-hybrid-eyebrow { color: #64748b; }
}

.rbk-source-badge {
    display: inline-flex;
    margin-right: 8px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;

    &.is-pbj {
        color: #0369a1;
        background: #e0f2fe;
    }

    &.is-manual {
        color: #7c3aed;
        background: #ede9fe;
    }
}

.rbk-link-button {
    padding: 0;
    border: 0;
    color: #0284c7;
    background: transparent;
    cursor: pointer;
    font-size: 0.72rem;
}

.rbk-selected-plan {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin: -4px 0 14px;
    padding: 9px 10px;
    border-radius: 9px;
    color: #475569;
    background: #f8fafc;
    font-size: 0.7rem;

    strong { color: #0f766e; }
}

.rbk-source-legend {
    display: flex;
    gap: 12px;
    color: #64748b;
    font-size: 0.7rem;

    span { display: flex; align-items: center; gap: 5px; }
    i { width: 8px; height: 8px; border-radius: 50%; }
    i.is-pbj { background: #0ea5e9; }
    i.is-manual { background: #8b5cf6; }
}

.rbk-history-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.rbk-history-category-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;

    small { color: #94a3b8; font-weight: 500; letter-spacing: 0; text-transform: none; }
}

.rbk-history-item {
    position: relative;
    display: flex;
    overflow: hidden;
    margin-bottom: 9px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
}

.rbk-history-accent {
    width: 4px;
    flex: 0 0 4px;
    background: #0ea5e9;

    .is-manual & { background: #8b5cf6; }
}

.rbk-history-main {
    min-width: 0;
    flex: 1;
    padding: 12px 14px;
}

.rbk-history-topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;

    > div:first-child { display: flex; align-items: center; flex-wrap: wrap; gap: 5px; }
    strong { color: #0f172a; font-size: 0.95rem; }
    small { color: #94a3b8; font-size: 0.7rem; }
}

.rbk-history-head {
    margin: 9px 0 2px;
    color: #0f766e;
    font-size: 0.75rem;
    font-weight: 700;
}

.rbk-pbj-group-title {
    margin: 10px 0 8px;
    color: #1e3a8a;
    font-size: 0.82rem;
    font-weight: 700;
}

.rbk-pbj-item-list {
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
}

.rbk-pbj-item-row {
    display: grid;
    grid-template-columns: 24px minmax(0, 1fr) minmax(145px, auto);
    align-items: start;
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid #e2e8f0;

    &:last-child {
        border-bottom: 0;
    }
}

.rbk-pbj-item-number {
    display: grid;
    width: 22px;
    height: 22px;
    place-items: center;
    border-radius: 50%;
    color: #1d4ed8;
    background: #dbeafe;
    font-size: 0.66rem;
    font-weight: 800;
}

.rbk-pbj-item-content {
    min-width: 0;

    .rbk-history-head,
    .rbk-history-title {
        margin-top: 0;
    }
}

.rbk-pbj-item-value {
    display: flex;
    align-items: flex-end;
    flex-direction: column;
    text-align: right;

    strong {
        color: #0f172a;
        font-size: 0.8rem;
    }

    small {
        margin-top: 2px;
        color: #94a3b8;
        font-size: 0.64rem;
    }
}

.rbk-history-title {
    margin: 8px 0 2px;
    color: #334155;
    font-size: 0.82rem;
    font-weight: 700;
}

.rbk-history-description {
    margin: 0;
    color: #64748b;
    font-size: 0.78rem;
    line-height: 1.45;
    white-space: pre-wrap;
}

.rbk-history-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 12px;
    margin-top: 8px;
    color: #94a3b8;
    font-size: 0.68rem;
}

@media (max-width: 960px) {
    .rbk-hybrid-overlay { padding: 8px; }
    .rbk-hybrid-dialog { width: 100%; height: calc(100vh - 16px); border-radius: 14px; }
    .rbk-overview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rbk-category-grid,
    .rbk-hybrid-content-grid { grid-template-columns: 1fr; }
    .rbk-manual-card { position: static; }
}

@media (max-width: 560px) {
    .rbk-hybrid-header { padding: 14px; }
    .rbk-hybrid-title-icon { display: none; }
    .rbk-excel-btn { width: 40px; padding: 0; }
    .rbk-excel-btn span { display: none; }
    .rbk-hybrid-body { padding: 12px; }
    .rbk-overview-grid { grid-template-columns: 1fr; }
    .rbk-history-topline { align-items: flex-start; flex-direction: column; }
    .rbk-pbj-item-row { grid-template-columns: 22px minmax(0, 1fr); }
    .rbk-pbj-item-value { grid-column: 2; align-items: flex-start; text-align: left; }
    .rbk-source-legend { display: none; }
}

.rbk-section {
    margin-bottom: 18px;
}

.rbk-section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.rbk-table :deep(input.input.is-small) {
    height: 34px;
}

.rbk-total-box {
    font-size: 0.95rem;

    .grand {
        margin-top: 6px;
        font-size: 1rem;
        color: #111827;
    }
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

.note-actions {
    margin-top: 8px;
    display: flex;
    justify-content: flex-end;
}

.is-selected-row {
    background: #eef9ff !important;
}

.has-text-warning-dark {
    color: #d97706 !important;
}

.detail-rows {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    font-size: 0.85rem;
    line-height: 1.4;
}

.detail-item .lbl {
    width: 110px;
    color: #7a7a7a;
    flex-shrink: 0;
}

.detail-item .val {
    color: #2d3436;
    word-break: break-word;
    flex: 1;
}

.is-code {
    font-family: 'monospace';
    background: #f5f5f5;
    padding: 1px 4px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.is-bold {
    font-weight: 700;
}

.has-text-primary {
    color: #00d1b2 !important;
}

</style>
