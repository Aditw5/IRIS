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
                                                    Progress:
                                                    <span class="has-text-success is-bold">
                                                        {{ u.summary?.progress ?? 0 }}%
                                                    </span>
                                                    <span v-if="!u.has_rbk"
                                                        class="tag is-warning is-light is-tiny ml-2">
                                                        Belum ada RBK
                                                    </span>
                                                </p>

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
                                                    Realisasi
                                                    <i class="iconify" data-icon="feather:trending-down"></i>
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

        <!-- MODAL REALISASI -->
        <div :class="['modal', { 'is-active': isRealisasiModalOpen }]">
            <div class="modal-background" @click="closeRealisasiModal"></div>
            <div class="modal-card modal-wide">
                <header class="modal-card-head">
                    <p class="modal-card-title">
                        Realisasi RBK: {{ selectedUnit?.namaperusahaan }}
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

                            <div class="columns">
                                <div class="column is-7">
                                    <h4 class="title is-6 mb-2">Daftar Item & Sisa</h4>
                                    <div class="table-container">
                                        <table class="table is-fullwidth is-striped is-hoverable rbk-table">
                                            <thead>
                                                <tr>
                                                    <th>Kategori</th>
                                                    <th>Uraian</th>
                                                    <th class="has-text-right">Rencana</th>
                                                    <th class="has-text-right">Realisasi</th>
                                                    <th class="has-text-right">Sisa</th>
                                                    <th style="width: 60px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="it in rbkDetail.items" :key="it.norec"
                                                    :class="{ 'is-selected-row': selectedItem?.norec === it.norec }">
                                                    <td class="is-capitalized">{{ it.kategori }}</td>
                                                    <td class="text-truncate" :title="it.uraian">{{ it.uraian }}</td>
                                                    <td class="has-text-right">{{ formatRupiah(it.nilai_rencana ?? 0) }}
                                                    </td>
                                                    <td class="has-text-right">{{ formatRupiah(it.total_realisasi ?? 0)
                                                        }}</td>
                                                    <td class="has-text-right"
                                                        :class="{ 'has-text-danger': calcSisaItem(it) < 0 }">
                                                        {{ formatRupiah(calcSisaItem(it)) }}
                                                    </td>
                                                    <td class="has-text-centered">
                                                        <a class="has-text-info is-clickable" @click="selectItem(it)">
                                                            <i class="iconify" data-icon="feather:chevrons-right"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr v-if="rbkDetail.items.length === 0">
                                                    <td colspan="6" class="has-text-centered has-text-grey">Belum ada
                                                        item</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <h4 class="title is-6 mb-2">Riwayat Realisasi</h4>
                                    <div class="timeline-wrapper">
                                        <div v-if="loadingRealisasi" class="has-text-centered py-4">
                                            <i class="iconify is-size-4 fa-spin" data-icon="feather:loader"></i>
                                            <p>Memuat...</p>
                                        </div>

                                        <div v-else-if="realisasiRows.length === 0"
                                            class="has-text-centered has-text-grey my-5">
                                            <p>Belum ada realisasi.</p>
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
                                                            </p>

                                                            <div class="note-actions">
                                                                <VButton color="warning" size="small" outlined
                                                                    @click="openEditRealisasi(r)">
                                                                    Edit
                                                                </VButton>
                                                            </div>
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
                                                            </p>

                                                            <div class="note-actions">
                                                                <VButton color="warning" size="small" outlined
                                                                    @click="openEditRealisasi(r)">
                                                                    Edit
                                                                </VButton>
                                                            </div>
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
                                                            </p>

                                                            <div class="note-actions">
                                                                <VButton color="warning" size="small" outlined
                                                                    @click="openEditRealisasi(r)">
                                                                    Edit
                                                                </VButton>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="column is-5">
                                    <h4 class="title is-6 mb-2">Input Realisasi</h4>

                                    <div class="box">
                                        <div class="field">
                                            <label class="label is-small">Item</label>
                                            <div class="control">
                                                <input class="input is-small"
                                                    :value="selectedItem ? selectedItem.uraian : ''" disabled
                                                    placeholder="Pilih item dulu..." />
                                            </div>
                                            <p v-if="selectedItem" class="help"
                                                :class="{ 'is-danger': selectedItemSisa < 0 }">
                                                Sisa: <strong>{{ formatRupiah(selectedItemSisa) }}</strong>
                                                <span v-if="selectedItemSisa < 0">(Over Budget)</span>
                                            </p>
                                        </div>

                                        <div class="columns is-mobile">
                                            <div class="column is-6">
                                                <div class="field">
                                                    <label class="label is-small">Nilai Realisasi (Rp)</label>
                                                    <div class="control">
                                                        <input class="input is-small" type="text"
                                                            :value="formatNumberForInput(realisasiForm.nilai)"
                                                            @input="(e) => realisasiForm.nilai = parseMoneyInput(e)"
                                                            :disabled="!selectedItem" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="column is-6">
                                                <div class="field">
                                                    <label class="label is-small">Tanggal</label>
                                                    <div class="control">
                                                        <input class="input is-small" type="datetime-local"
                                                            v-model="realisasiForm.tanggal" :disabled="!selectedItem" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="field">
                                            <label class="label is-small">Keterangan</label>
                                            <div class="control">
                                                <textarea class="textarea is-small" rows="2"
                                                    v-model="realisasiForm.keterangan" :disabled="!selectedItem"
                                                    placeholder="Contoh: Pengantaran SMT 1 / Pembayaran tahap 1 / dll"></textarea>
                                            </div>
                                        </div>

                                        <div class="has-text-right">
                                            <VButton color="primary" size="small" :loading="savingRealisasi"
                                                :disabled="!selectedItem" @click="saveRealisasi">
                                                Simpan Realisasi
                                            </VButton>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </template>

                </section>
            </div>
        </div>

        <!-- MODAL EDIT REALISASI -->
        <div :class="['modal', { 'is-active': isEditRealisasiModalOpen }]">
            <div class="modal-background" @click="closeEditRealisasiModal"></div>
            <div class="modal-card" style="width: 700px; max-width: 96%;">
                <header class="modal-card-head">
                    <p class="modal-card-title">Edit Riwayat Realisasi</p>
                    <button class="delete" aria-label="close" @click="closeEditRealisasiModal"></button>
                </header>

                <section class="modal-card-body">
                    <div class="field">
                        <label class="label">Item</label>
                        <div class="control">
                            <input class="input" :value="editRealisasiForm.uraian" disabled />
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column is-6">
                            <div class="field">
                                <label class="label">Nilai Realisasi (Rp)</label>
                                <div class="control">
                                    <input class="input" type="text"
                                        :value="formatNumberForInput(editRealisasiForm.nilai)"
                                        @input="(e) => editRealisasiForm.nilai = parseMoneyInput(e)" />
                                </div>
                            </div>
                        </div>

                        <div class="column is-6">
                            <div class="field">
                                <label class="label">Tanggal</label>
                                <div class="control">
                                    <input class="input" type="datetime-local" v-model="editRealisasiForm.tanggal" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Keterangan</label>
                        <div class="control">
                            <textarea class="textarea" rows="4" v-model="editRealisasiForm.keterangan"
                                placeholder="Ubah keterangan realisasi..."></textarea>
                        </div>
                    </div>
                </section>

                <footer class="modal-card-foot is-justify-content-end">
                    <VButton color="light" @click="closeEditRealisasiModal">
                        Batal
                    </VButton>
                    <VButton color="warning" :loading="savingEditRealisasi" @click="updateRealisasi">
                        Simpan Perubahan
                    </VButton>
                </footer>
            </div>
        </div>

    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import Calendar from 'primevue/calendar'
import { useApi } from '/@src/composable/useApi'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useHead } from '@vueuse/head'

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

const year = ref<Date>(new Date())
const activeTab = ref('jakarta')
const searchKeyword = ref('')
const sortBy = ref('total_rencana')
let searchTimeout: any = null

const units = ref<UnitRow[]>([])

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
const loadingRbkDetail = ref(false)
const rbkDetail = ref<RbkDetail | null>(null)
const selectedItem = ref<RbkItem | null>(null)
const realisasiRows = ref<any[]>([])
const loadingRealisasi = ref(false)
const savingRealisasi = ref(false)

const realisasiForm = ref({
    nilai: 0,
    tanggal: '',
    keterangan: '',
})

// EDIT REALISASI
const isEditRealisasiModalOpen = ref(false)
const savingEditRealisasi = ref(false)
const editingRealisasi = ref<any>(null)

const editRealisasiForm = ref({
    norec: '',
    rbk_item_norec: '',
    uraian: '',
    nilai: 0,
    tanggal: '',
    keterangan: '',
})

const getYearString = () => String(year.value.getFullYear())
const getLokasiId = () => (activeTab.value === 'gresik' ? 2 : 1)

watch([year, activeTab], () => {
    units.value = []
    loadUnits()
})

watch(searchKeyword, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => loadUnits(), 500)
})

async function loadUnits() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            year: getYearString(),
            lokasi_id: String(getLokasiId()),
            keyword: searchKeyword.value,
            sort_by: sortBy.value,
        })
        const res = await useApi().get(`rbk/get-units-monitoring?${params.toString()}`)
        units.value = (res.data ?? []) as UnitRow[]
    } catch (err) {
        H.alert('error', 'Gagal memuat data RBK')
    } finally {
        loading.value = false
    }
}

async function fetchUnitsForExport(lokasiId: number) {
    const params = new URLSearchParams({
        year: getYearString(),
        lokasi_id: String(lokasiId),
        keyword: searchKeyword.value,
        sort_by: sortBy.value,
    })

    const res = await useApi().get(`rbk/get-units-monitoring?${params.toString()}`)
    return (res.data ?? []) as UnitRow[]
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
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${Math.min(Math.max(progress, 0), 100)}%;"></div>
                    </div>
                    <div class="progress-text">${progress}%</div>
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

    rbkDetail.value = null
    selectedItem.value = null
    realisasiRows.value = []
    realisasiForm.value = {
        nilai: 0,
        tanggal: '',
        keterangan: '',
    }

    loadRbkDetailAndRealisasi(u.unit_id)
}

function closeRealisasiModal() {
    isRealisasiModalOpen.value = false
    selectedItem.value = null
    rbkDetail.value = null
    realisasiRows.value = []
    loadingRbkDetail.value = false
    loadingRealisasi.value = false
}

async function loadRbkDetailAndRealisasi(unitfk: number) {
    loadingRbkDetail.value = true
    try {
        const params = new URLSearchParams({
            unitfk: String(unitfk),
            tahun: getYearString(),
            lokasi_id: String(getLokasiId()),
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

function selectItem(it: RbkItem) {
    selectedItem.value = it
    realisasiForm.value.nilai = 0
    realisasiForm.value.keterangan = ''
    if (!realisasiForm.value.tanggal) {
        const now = new Date()
        const pad = (n: number) => String(n).padStart(2, '0')
        const v = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`
        realisasiForm.value.tanggal = v
    }
}

function calcSisaItem(it: any) {
    const rencana = Number(it.nilai_rencana ?? 0)
    const real = Number(it.total_realisasi ?? 0)
    return rencana - real
}

const selectedItemSisa = computed(() => {
    if (!selectedItem.value) return 0
    return calcSisaItem(selectedItem.value)
})

async function saveRealisasi() {
    if (!selectedUnit.value || !selectedItem.value) return

    const nilai = Number(realisasiForm.value.nilai ?? 0)
    if (!nilai || nilai <= 0) {
        H.alert('warning', 'Nilai realisasi harus > 0')
        return
    }

    savingRealisasi.value = true
    try {
        const payload = {
            unitfk: selectedUnit.value.unit_id,
            lokasi_id: getLokasiId(),
            tahun: Number(getYearString()),
            rbk_item_norec: selectedItem.value.norec,
            nilai_realisasi: nilai,
            tanggal: realisasiForm.value.tanggal,
            keterangan: realisasiForm.value.keterangan,
        }

        await useApi().post('rbk/save-realisasi', payload)
        H.alert('success', 'Realisasi berhasil disimpan')

        await loadRbkDetailAndRealisasi(selectedUnit.value.unit_id)
        await loadUnits()

        realisasiForm.value.nilai = 0
        realisasiForm.value.keterangan = ''
    } catch (e: any) {
        console.error('saveRealisasi error', e)
        if (e.response?.data?.message === 'melebihi_sisa') {
            H.alert('error', 'Gagal: Backend menolak input melebihi sisa anggaran.')
        } else {
            H.alert('error', 'Gagal simpan realisasi')
        }
    } finally {
        savingRealisasi.value = false
    }
}

// EDIT REALISASI
function toDateTimeLocal(dateStr: string) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return ''
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function openEditRealisasi(row: any) {
    editingRealisasi.value = row
    editRealisasiForm.value = {
        norec: row.norec || '',
        rbk_item_norec: row.rbk_item_norec || row.rbkitemfk || row.rbk_item_fk || '',
        uraian: row.uraian || '',
        nilai: Number(row.nilai_realisasi ?? 0),
        tanggal: toDateTimeLocal(row.tanggal || row.created_at),
        keterangan: row.keterangan || '',
    }
    isEditRealisasiModalOpen.value = true
}

function closeEditRealisasiModal() {
    isEditRealisasiModalOpen.value = false
    editingRealisasi.value = null
    editRealisasiForm.value = {
        norec: '',
        rbk_item_norec: '',
        uraian: '',
        nilai: 0,
        tanggal: '',
        keterangan: '',
    }
}

async function updateRealisasi() {
    if (!selectedUnit.value || !editRealisasiForm.value.norec) {
        H.alert('warning', 'Data realisasi tidak valid')
        return
    }

    const nilai = Number(editRealisasiForm.value.nilai ?? 0)
    if (!nilai || nilai <= 0) {
        H.alert('warning', 'Nilai realisasi harus > 0')
        return
    }

    savingEditRealisasi.value = true
    try {
        const payload = {
            norec: editRealisasiForm.value.norec,
            unitfk: selectedUnit.value.unit_id,
            lokasi_id: getLokasiId(),
            tahun: Number(getYearString()),
            rbk_item_norec: editRealisasiForm.value.rbk_item_norec,
            nilai_realisasi: nilai,
            tanggal: editRealisasiForm.value.tanggal,
            keterangan: editRealisasiForm.value.keterangan,
        }

        await useApi().post('rbk/update-realisasi', payload)

        closeEditRealisasiModal()

        await loadRbkDetailAndRealisasi(selectedUnit.value.unit_id)
        await loadUnits()
    } catch (e: any) {
        console.error('updateRealisasi error', e)
        if (e.response?.data?.message === 'melebihi_sisa') {
            H.alert('error', 'Gagal: nilai edit melebihi sisa anggaran.')
        } else {
            H.alert('error', 'Gagal update realisasi')
        }
    } finally {
        savingEditRealisasi.value = false
    }
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
