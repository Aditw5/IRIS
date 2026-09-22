<template>
  <ConfirmDialog />

  <div class="column">
    <VCard class="kp-header-card">
      <div class="kp-hero">
        <div class="kp-hero-left">
          <img src="/@src/assets/illustrations/dashboards/personal/UMRO.png" alt="UMRO Laboratory"
            class="kp-hero-logo" />
          <div class="kp-hero-text">
            <h3 class="kp-hero-title">Super Admin Progress PBJ</h3>
            <p class="kp-hero-sub">Update Progress Permintaan Barang dan Jasa (Step 4 - 12)</p>
          </div>
        </div>
        <VButton to="/module/pbj/dashboard-data-pbj" color="info" outlined icon="feather:bar-chart-2">
          Dashboard Data PBJ
        </VButton>
      </div>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
        <h3 class="title is-5 mb-2">Riwayat Pengajuan PBJ</h3>
        <div class="is-flex is-align-items-center" style="gap:.5rem">
          <VTag color="info" rounded>Super Admin</VTag>
          <VTag color="warning" rounded>Update Progress</VTag>
        </div>
      </div>

      <div class="legend-steps mb-4">
        <div class="legend-title">Legenda Progress</div>
        <div class="legend-scroller">
          <div v-for="s in stepsLegend" :key="s.step" class="legend-item">
            <span class="legend-num">{{ s.step }}</span>
            <span class="legend-label">{{ s.label }}</span>
          </div>
        </div>
      </div>

      <div class="filter-card mb-4">
        <div class="filter-head">
          <div>  
          </div>
          <div class="filter-count">
            <span class="filter-count-number">{{ filteredDataSourceRiwayat.length }}</span>
            <span class="filter-count-label">data tampil</span>
          </div>
        </div>

        <div class="filter-grid">
          <VField label="Cari semua data">
            <VControl icon="feather:search">
              <VInput v-model.trim="filters.keyword" placeholder="No PBJ, judul, pengaju, PR, PO, catatan..." />
            </VControl>
          </VField>

          <VField label="Lokasi">
            <VControl>
              <select v-model="filters.lokasi" class="input">
                <option value="">Semua Lokasi</option>
                <option v-for="opt in lokasiOptions" :key="'lokasi-' + opt" :value="opt">
                  {{ opt }}
                </option>
              </select>
            </VControl>
          </VField>

          <VField label="Status">
            <VControl>
              <select v-model="filters.status" class="input">
                <option value="">Semua Status</option>
                <option v-for="opt in statusOptions" :key="'status-' + opt" :value="opt">
                  {{ opt }}
                </option>
              </select>
            </VControl>
          </VField>

          <VField label="Progress">
            <VControl>
              <select v-model="filters.progressStep" class="input">
                <option value="">Semua Progress</option>
                <option v-for="s in stepsLegend" :key="'progress-' + s.step" :value="String(s.step)">
                  Step {{ s.step }} - {{ s.label }}
                </option>
              </select>
            </VControl>
          </VField>

          <VField label="Prioritas">
            <VControl>
              <select v-model="filters.prioritas" class="input">
                <option value="">Semua Prioritas</option>
                <option v-for="opt in prioritasOptions" :key="'prioritas-' + opt" :value="opt">
                  {{ opt }}
                </option>
              </select>
            </VControl>
          </VField>

          <VField label="Jenis Pengadaan">
            <VControl>
              <select v-model="filters.pengadaan" class="input">
                <option value="">Semua Pengadaan</option>
                <option v-for="opt in pengadaanOptions" :key="'pengadaan-' + opt" :value="opt">
                  {{ opt }}
                </option>
              </select>
            </VControl>
          </VField>

          <VField label="Nomor Surat PBJ">
            <VControl>
              <VInput v-model.trim="filters.noSurat" placeholder="Contoh: 086/LAB/2026" />
            </VControl>
          </VField>

          <VField label="Tanggal Pengajuan Mulai">
            <VControl>
              <input v-model="filters.tglAjuanDari" class="input" type="date" />
            </VControl>
          </VField>

          <VField label="Tanggal Pengajuan Sampai">
            <VControl>
              <input v-model="filters.tglAjuanSampai" class="input" type="date" />
            </VControl>
          </VField>

          <VField label="Ketersediaan Informasi Harga (IH)">
            <VControl>
              <select v-model="filters.adaIh" class="input">
                <option value="">Semua</option>
                <option value="ya">Ada IH</option>
                <option value="tidak">Tidak Ada IH</option>
              </select>
            </VControl>
          </VField>
        </div>

        <div class="filter-actions">
          <VButton color="dark" outlined icon="feather:rotate-ccw" @click="resetFilters()">
            Reset Filter
          </VButton>
          <VButton color="info" outlined icon="feather:refresh-ccw" @click="fetchDataRiwayat()">
            Refresh Data
          </VButton>
        </div>
      </div>

      <div class="column" v-if="isPlaceLoad">
        <VPlaceloadWrap v-for="data in 12" :key="data">
          <VPlaceload class="mx-2 mb-3" />
          <VPlaceload class="mx-2" />
        </VPlaceloadWrap>
      </div>

      <div class="column" v-else>
        <VPlaceholderPage v-if="filteredDataSourceRiwayat.length == 0" title="Belum ada data PBJ sesuai filter" larger>
          <template #image>
            <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
            <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
          </template>
        </VPlaceholderPage>

        <div v-else>
          <DataTable v-model:expandedRows="expandedRows" dataKey="norec" :value="filteredDataSourceRiwayat"
            class="p-datatable-sm" :loading="isLoading" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
            scrollable
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
            <ColumnGroup type="header">
              <Row>
                <Column header="Informasi" :colspan="2"
                  style="background-color: #e0f2fe; color: #0369a1; font-weight: bold; text-align: center" />
                <Column header="Kontrol" :colspan="4" style="background-color: #f8fafc; text-align: center" />
                <Column header="Cetak" :colspan="2" style="background-color: #f1f5f9; text-align: center" />
                <Column header="Aktivitas" :colspan="1" style="background-color: #f8fafc; text-align: center" />
                <Column header="Data Pengajuan PBJ" :colspan="16"
                  style="background-color: #e0f2fe; color: #0369a1; font-weight: bold; text-align: center" />
                <Column header="Rendal" :colspan="2"
                  style="background-color: #fef9c3; color: #a16207; font-weight: bold; text-align: center" />
                <Column header="Inventory" :colspan="4"
                  style="background-color: #dcfce7; color: #15803d; font-weight: bold; text-align: center" />
                <Column header="Pengadaan" :colspan="9"
                  style="background-color: #ffedd5; color: #c2410c; font-weight: bold; text-align: center" />
                <Column header="BA1" :colspan="4"
                  style="background-color: #f3e8ff; color: #7e22ce; font-weight: bold; text-align: center" />
                <Column header="BA2" :colspan="3"
                  style="background-color: #fae8ff; color: #a21caf; font-weight: bold; text-align: center" />
                <Column header="Pembayaran" :colspan="4"
                  style="background-color: #e0e7ff; color: #4338ca; font-weight: bold; text-align: center" />
                <Column header="Pembebanan" :colspan="3"
                  style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: center" />
                <Column header="Limit" :colspan="2"
                  style="background-color: #fee2e2; color: #991b1b; font-weight: bold; text-align: center" />
              </Row>
              <Row>
                <Column header="Info" :frozen="isColumnFrozen('info')" style="background-color: #f8fafc" />
                <Column header="No Surat PBJ" :frozen="isColumnFrozen('noSuratPbj')"
                  style="background-color: #e0f2fe; color: #0369a1; font-weight: bold" />
                <Column header="Detail" :frozen="isColumnFrozen('detail')" style="background-color: #f8fafc" />
                <Column header="Status" :frozen="isColumnFrozen('status')" style="background-color: #f8fafc" />
                <Column header="Progress" :frozen="isColumnFrozen('progress')" style="background-color: #f8fafc" />
                <Column header="Aksi" :frozen="isColumnFrozen('aksi')" style="background-color: #f8fafc" />
                <Column header="PBJ" :frozen="isColumnFrozen('cetakPbj')" style="background-color: #f8fafc" />
                <Column header="IH" :frozen="isColumnFrozen('cetakIh')" style="background-color: #f8fafc" />
                <Column header="Aktv" :frozen="isColumnFrozen('aktivitas')" style="background-color: #f8fafc" />
                <Column header="Tgl Ajuan" :frozen="isColumnFrozen('tglPengajuan')" style="background-color: #f0f9ff" />
                <Column header="Nama Pengaju" :frozen="isColumnFrozen('namaPengaju')" style="background-color: #f0f9ff" />
                <Column header="Judul" :frozen="isColumnFrozen('judul')" style="background-color: #f0f9ff" />
                <Column header="Pemohon" :frozen="isColumnFrozen('pemohon')" style="background-color: #f0f9ff" />
                <Column header="Prioritas" :frozen="isColumnFrozen('prioritas')" style="background-color: #f0f9ff" />
                <Column header="Pengadaan" :frozen="isColumnFrozen('pengadaan')" style="background-color: #f0f9ff" />
                <Column header="Anggaran" :frozen="isColumnFrozen('anggaran')" style="background-color: #f0f9ff" />
                <Column header="User" :frozen="isColumnFrozen('user')" style="background-color: #f0f9ff" />
                <Column header="Bidang" :frozen="isColumnFrozen('bidang')" style="background-color: #f0f9ff" />
                <Column header="Kepada" :frozen="isColumnFrozen('kepada')" style="background-color: #f0f9ff" />
                <Column header="PRK" :frozen="isColumnFrozen('prk')" style="background-color: #f0f9ff" />
                <Column header="WO" :frozen="isColumnFrozen('wo')" style="background-color: #f0f9ff" />
                <Column header="Project" :frozen="isColumnFrozen('project')" style="background-color: #f0f9ff" />
                <Column header="Noproyek" :frozen="isColumnFrozen('noProyek')" style="background-color: #f0f9ff" />
                <Column header="Costcode" :frozen="isColumnFrozen('costcode')" style="background-color: #f0f9ff" />
                <Column header="Catatan" :frozen="isColumnFrozen('catatan')" style="background-color: #f0f9ff" />
                <Column header="No PR" :frozen="isColumnFrozen('noPr')" style="background-color: #fefce8" />
                <Column header="Tgl PR" :frozen="isColumnFrozen('tglPr')" style="background-color: #fefce8" />
                <Column header="Tgl RO" :frozen="isColumnFrozen('tglRo')" style="background-color: #f0fdf4" />
                <Column header="Tgl HPE" :frozen="isColumnFrozen('tglHpe')" style="background-color: #f0fdf4" />
                <Column header="Tgl RKS" :frozen="isColumnFrozen('tglRks')" style="background-color: #f0fdf4" />
                <Column header="Tgl Serah" :frozen="isColumnFrozen('tglSerah')" style="background-color: #f0fdf4" />
                <Column header="Aanwz" :frozen="isColumnFrozen('aanwijzing')" style="background-color: #fff7ed" />
                <Column header="Klrtk" :frozen="isColumnFrozen('klartek')" style="background-color: #fff7ed" />
                <Column header="Buka" :frozen="isColumnFrozen('bukaPenawaran')" style="background-color: #fff7ed" />
                <Column header="Tgl PP" :frozen="isColumnFrozen('tglPp')" style="background-color: #fff7ed" />
                <Column header="No PO" :frozen="isColumnFrozen('noPo')" style="background-color: #fff7ed" />
                <Column header="Nilai PO" :frozen="isColumnFrozen('nilaiPo')" style="background-color: #fff7ed" />
                <Column header="Tgl PO" :frozen="isColumnFrozen('tglPo')" style="background-color: #fff7ed" />
                <Column header="Plksn" :frozen="isColumnFrozen('pelaksana')" style="background-color: #fff7ed" />
                <Column header="HPS" :frozen="isColumnFrozen('hps')" style="background-color: #fff7ed" />
                <Column header="SPPP" :frozen="isColumnFrozen('sppp')" style="background-color: #faf5ff" />
                <Column header="Mulai" :frozen="isColumnFrozen('mulai')" style="background-color: #faf5ff" />
                <Column header="Selesai" :frozen="isColumnFrozen('selesai')" style="background-color: #faf5ff" />
                <Column header="Denda" :frozen="isColumnFrozen('denda')" style="background-color: #faf5ff" />
                <Column header="No BA" :frozen="isColumnFrozen('noBa')" style="background-color: #fdf4ff" />
                <Column header="Posisi" :frozen="isColumnFrozen('posisiBa')" style="background-color: #fdf4ff" />
                <Column header="Status" :frozen="isColumnFrozen('statusBa')" style="background-color: #fdf4ff" />
                <Column header="Verif Invoice" :frozen="isColumnFrozen('verifInvoice')" style="background-color: #eef2ff" />
                <Column header="No BKK" :frozen="isColumnFrozen('noBkk')" style="background-color: #eef2ff" />
                <Column header="Status" :frozen="isColumnFrozen('statusPaid')" style="background-color: #eef2ff" />
                <Column header="Ket" :frozen="isColumnFrozen('ketPembayaran')" style="background-color: #eef2ff" />
                <Column header="No Jurnal" :frozen="isColumnFrozen('noJurnal')" style="background-color: #f8fafc" />
                <Column header="Periode" :frozen="isColumnFrozen('periode')" style="background-color: #f8fafc" />
                <Column header="Ket" :frozen="isColumnFrozen('ketPembebanan')" style="background-color: #f8fafc" />
                <Column header="Periode Limit" :frozen="isColumnFrozen('periodeLimit')" style="background-color: #fef2f2" />
                <Column header="Ket" :frozen="isColumnFrozen('ketLimit')" style="background-color: #fef2f2" />
              </Row>
            </ColumnGroup>

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
                <div class="freeze-column-control">
                  <span class="freeze-column-label">Freeze kolom</span>
                  <MultiSelect v-model="frozenColumns" :options="frozenColumnOptions" optionLabel="label"
                    optionValue="key" display="chip" filter appendTo="body" :maxSelectedLabels="3"
                    selectedItemsLabel="{0} kolom dipilih" placeholder="Pilih kolom yang di-freeze"
                    class="freeze-column-select" />
                  <VButton color="dark" outlined icon="feather:rotate-ccw" @click="resetFrozenColumns()">
                    Default
                  </VButton>
                </div>
              </div>
            </template>

            <Column field="no" :frozen="isColumnFrozen('info')" />
            <Column field="nosuratpbj" :frozen="isColumnFrozen('noSuratPbj')"
              style="min-width: 170px; font-weight: 600" />
            <Column expander :frozen="isColumnFrozen('detail')" style="width:3rem" />

            <Column field="statusText" :frozen="isColumnFrozen('status')" style="min-width: 180px">
              <template #body="slotProps">
                <VTag class="ml-2" :color="slotProps.data.color" rounded>
                  {{ slotProps.data.statusText }}
                </VTag>
              </template>
            </Column>

            <Column :frozen="isColumnFrozen('progress')" style="min-width: 400px">
              <template #body="{ data: row }">
                <div class="progress-cell">
                  <div class="progress-text">
                    {{ row.progress || getProgressLabel(normalizeStep(row.progress_step)) || '-' }}
                  </div>
                  <div class="progress-stepper">
                    <div class="bar-wrap">
                      <ProgressBar :value="stepPercent(row.progress_step)" :showValue="false" style="height:10px" />
                      <div class="step-dots">
                        <span v-for="n in 12" :key="'dot-' + n" class="step-dot"
                          :class="{ active: n <= (normalizeStep(row.progress_step) || 1) }" />
                      </div>
                    </div>
                    <div class="step-caption">
                      Step {{ normalizeStep(row.progress_step) || 1 }}/12
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="Aksi" :frozen="isColumnFrozen('aksi')" style="min-width: 220px; text-align: center;">
              <template #body="{ data: row }">
                <div class="is-flex is-align-items-center" style="gap:.5rem">
                  <VIconButton v-tooltip.top.left="approveTooltip(row)" color="success" outlined circle
                    icon="fas fa-check" :disabled="!canApproveRow(row) || !!inventorySubmittingNorec"
                    @click="confirmStepAction(row, false)" />
                  <VIconButton v-tooltip.top.left="rejectTooltip(row)" color="danger" outlined circle icon="feather:x"
                    :disabled="!canRejectRow(row) || !!inventorySubmittingNorec"
                    @click="confirmStepAction(row, true)" />
                  <VTag v-if="getNextStep(row) === 8" color="warning" rounded>
                    Menunggu Asman
                  </VTag>
                  <VTag v-else-if="normalizeStep(row.progress_step) >= 12" color="success" rounded>
                    Selesai
                  </VTag>
                </div>
              </template>
            </Column>

            <Column :frozen="isColumnFrozen('cetakPbj')" style="min-width: 100px">
              <template #body="slotProps">
                <VIconButton class="mr-3" color="danger" outlined circle icon="feather:printer"
                  @click="cetakPBJ(slotProps.data)" />
              </template>
            </Column>

            <Column :frozen="isColumnFrozen('cetakIh')" style="text-align:center; min-width: 90px" row-clickable
              @row-click="lihatDetailIht">
              <template #body="slotProps">
                <VIconButton v-if="slotProps.data.dataListIht" color="warning" outlined circle icon="feather:printer"
                  @click="lihatDetailIht(slotProps.data.dataListIht)" />
              </template>
            </Column>

            <Column :frozen="isColumnFrozen('aktivitas')" style="min-width: 100px">
              <template #body="slotProps">
                <VIconButton v-tooltip.bottom.left="'Aktivitas'" icon="feather:activity"
                  @click="detailOrder(slotProps.data)" color="info" raised circle class="mr-2" />
              </template>
            </Column>

            <Column field="tglpengajuan" :frozen="isColumnFrozen('tglPengajuan')" style="min-width: 140px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpengajuan">{{ H.formatDateIndo(slotProps.data.tglpengajuan) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="namalengkap" :frozen="isColumnFrozen('namaPengaju')" style="min-width: 200px" />
            <Column field="judulpermintaan" :frozen="isColumnFrozen('judul')" style="min-width: 100px" />
            <Column field="pemohonpbj" :frozen="isColumnFrozen('pemohon')" style="min-width: 100px" />
            <Column field="prioritaspbj" :frozen="isColumnFrozen('prioritas')" style="min-width: 120px" />
            <Column field="pengadaanpbj" :frozen="isColumnFrozen('pengadaan')" style="min-width: 100px" />
            <Column :frozen="isColumnFrozen('anggaran')" style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ kebutuhanText(data) }}</span>
              </template>
            </Column>
            <Column :frozen="isColumnFrozen('user')" style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ userText(data) }}</span>
              </template>
            </Column>
            <Column :frozen="isColumnFrozen('bidang')" style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ bidangText(data) }}</span>
              </template>
            </Column>
            <Column :frozen="isColumnFrozen('kepada')" style="min-width: 240px">
              <template #body="{ data }">
                <span>{{ kepadaText(data) }}</span>
              </template>
            </Column>
            <Column field="prk" :frozen="isColumnFrozen('prk')" style="min-width: 100px" />
            <Column field="wo" :frozen="isColumnFrozen('wo')" style="min-width: 100px" />
            <Column field="project" :frozen="isColumnFrozen('project')" style="min-width: 100px" />
            <Column field="noproyek" :frozen="isColumnFrozen('noProyek')" style="min-width: 100px" />
            <Column field="costcode" :frozen="isColumnFrozen('costcode')" style="min-width: 100px" />
            <Column field="notes" :frozen="isColumnFrozen('catatan')" style="min-width: 240px">
              <template #body="slotProps">
                <span>{{ slotProps.data.notes || '-' }}</span>
              </template>
            </Column>
            <Column field="nopr" :frozen="isColumnFrozen('noPr')" style="min-width: 240px" />
            <Column field="tglpembuatanpr" :frozen="isColumnFrozen('tglPr')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpembuatanpr">{{ H.formatDateToLocalString(slotProps.data.tglpembuatanpr)
                }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenerimaanro" :frozen="isColumnFrozen('tglRo')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanro">{{ H.formatDateToLocalString(slotProps.data.tglpenerimaanro)
                }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenerimaanhpe" :frozen="isColumnFrozen('tglHpe')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanhpe">{{
                  H.formatDateToLocalString(slotProps.data.tglpenerimaanhpe) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenerimaanrks" :frozen="isColumnFrozen('tglRks')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanrks">{{
                  H.formatDateToLocalString(slotProps.data.tglpenerimaanrks) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenyerahandokumen" :frozen="isColumnFrozen('tglSerah')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenyerahandokumen">{{
                  H.formatDateToLocalString(slotProps.data.tglpenyerahandokumen) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglaanwijzing" :frozen="isColumnFrozen('aanwijzing')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglaanwijzing">{{ H.formatDateToLocalString(slotProps.data.tglaanwijzing)
                }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglklartek" :frozen="isColumnFrozen('klartek')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglklartek">{{ H.formatDateToLocalString(slotProps.data.tglklartek) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpembukaanpenawaran" :frozen="isColumnFrozen('bukaPenawaran')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpembukaanpenawaran">{{
                  H.formatDateToLocalString(slotProps.data.tglpembukaanpenawaran) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpp" :frozen="isColumnFrozen('tglPp')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpp">{{ H.formatDateToLocalString(slotProps.data.tglpp) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="nopo" :frozen="isColumnFrozen('noPo')" style="min-width: 100px" />
            <Column field="nilaipoppn" :frozen="isColumnFrozen('nilaiPo')" style="min-width: 100px">
              <template #body="slotProps">
                <span>{{ formatRupiah(slotProps.data.nilaipoppn) }}</span>
              </template>
            </Column>
            <Column field="tglpo" :frozen="isColumnFrozen('tglPo')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpo">{{ H.formatDateToLocalString(slotProps.data.tglpo) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="pelaksana" :frozen="isColumnFrozen('pelaksana')" style="min-width: 100px" />
            <Column field="hps" :frozen="isColumnFrozen('hps')" style="min-width: 100px">
              <template #body="slotProps">
                <span>{{ formatRupiah(slotProps.data.hps) }}</span>
              </template>
            </Column>
            <Column field="sppp" :frozen="isColumnFrozen('sppp')" style="min-width: 200px" />
            <Column field="tglmulairealisasi" :frozen="isColumnFrozen('mulai')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglmulairealisasi">{{
                  H.formatDateToLocalString(slotProps.data.tglmulairealisasi) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglselesairealisasi" :frozen="isColumnFrozen('selesai')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglselesairealisasi">{{
                  H.formatDateToLocalString(slotProps.data.tglselesairealisasi) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="dendahari" :frozen="isColumnFrozen('denda')" style="min-width: 100px" />
            <Column field="noba" :frozen="isColumnFrozen('noBa')" style="min-width: 100px" />
            <Column field="posisiba" :frozen="isColumnFrozen('posisiBa')" style="min-width: 100px" />
            <Column field="statusba" :frozen="isColumnFrozen('statusBa')" style="min-width: 100px" />
            <Column field="verifikasiinvoice" :frozen="isColumnFrozen('verifInvoice')" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.verifikasiinvoice">{{
                  H.formatDateToLocalString(slotProps.data.verifikasiinvoice) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="nobkk" :frozen="isColumnFrozen('noBkk')" style="min-width: 100px" />
            <Column field="statuspaid" :frozen="isColumnFrozen('statusPaid')" style="min-width: 100px" />
            <Column field="ketverifpembayaran" :frozen="isColumnFrozen('ketPembayaran')" style="min-width: 100px" />
            <Column field="nojurnal" :frozen="isColumnFrozen('noJurnal')" style="min-width: 100px" />
            <Column field="periode" :frozen="isColumnFrozen('periode')" style="min-width: 100px" />
            <Column field="ketverifpembebanan" :frozen="isColumnFrozen('ketPembebanan')" style="min-width: 100px" />
            <Column field="periodepermintaanlimit" :frozen="isColumnFrozen('periodeLimit')" style="min-width: 100px" />
            <Column field="ketverifpermitaanlimit" :frozen="isColumnFrozen('ketLimit')" style="min-width: 100px" />

            <template #expansion="{ data }">
              <div class="p-4 bg-white border-round-md shadow-1">
                <h5 class="mb-2">Detail PBJ untuk No Surat {{ data.nosuratpbj }}</h5>
                <DataTable :value="data.detailList" class="p-datatable-sm" tableStyle="min-width:40rem" scrollable
                  responsiveLayout="stack" breakpoint="960px" showGridlines>
                  <Column field="no" header="No" style="width:60px; min-width:60px" />
                  <Column field="namaitem" header="Nama Item" style="min-width: 160px" />
                  <Column field="uraianitem" header="Uraian" style="min-width: 220px" />
                  <Column field="stockcode" header="Stock Code" style="min-width: 120px" />
                  <Column field="banyak" header="Banyak" style="min-width: 100px" />
                  <Column field="satuan" header="Satuan" style="min-width: 100px" />
                  <Column field="hargasatuan" header="Harga Satuan" style="min-width: 140px">
                    <template #body="sp">
                      {{ formatRupiah(sp.data.hargasatuan) }}
                    </template>
                  </Column>
                  <Column field="keterangan" header="Keterangan" style="min-width: 220px" />
                </DataTable>
              </div>
            </template>
          </DataTable>
        </div>
      </div>
    </VCard>
  </div>

  <div :class="['modal', modalApproval ? 'is-active' : '']">
    <div class="modal-background" @click="closeApprovalModal()" />
    <div class="modal-card approval-modal-card" style="width: 900px; max-width: calc(100vw - 32px);">
      <header class="modal-card-head">
        <p class="modal-card-title">
          {{ modalTitle }}
        </p>
        <button class="delete" aria-label="close" @click="closeApprovalModal()" />
      </header>

      <section class="modal-card-body approval-form-body">
        <div class="approval-form-hint">
          <i class="fas fa-circle-info" aria-hidden="true" />
          <span>Tidak ada field yang wajib secara khusus. Isi minimal satu field untuk mengaktifkan tombol Simpan.</span>
        </div>

        <template v-if="approvalStatusBaru === 5">
          <VField label="Nomor Purchase Requisition (PR)">
            <VControl>
              <VInput v-model.trim="approvalNoPR" placeholder="Contoh: PR-00123/NP/2025" />
            </VControl>
          </VField>

          <VField label="Tanggal Pembuatan Purchase Requisition (PR)">
            <VDatePicker v-model="approvalTglPembuatannPR" :popover="datePickerPopover" is24hr mode="dateTime"
              style="width: 100%;">
              <template #default="{ inputValue, inputEvents }">
                <VField>
                  <VControl icon="feather:calendar" fullwidth>
                    <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih tanggal dan waktu"
                      aria-label="Tanggal Pembuatan Purchase Requisition (PR)" />
                  </VControl>
                </VField>
              </template>
            </VDatePicker>
          </VField>

          <VField label="Keterangan (opsional)">
            <VControl>
              <textarea class="textarea" rows="4" v-model="approvalNote"
                placeholder="Contoh: Disetujui oleh atasan, silakan lanjut proses." />
            </VControl>
          </VField>
        </template>

        <template v-else-if="approvalStatusBaru === 9">
          <div class="columns is-multiline">
            <div class="column is-6">
              <VField label="Tanggal Aanwijzing">
                <VDatePicker v-model="f.tglAanwijzing" :popover="datePickerPopover" is24hr mode="dateTime"
                  style="width:100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih tanggal dan waktu"
                          aria-label="Tanggal Aanwijzing" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>

            <div class="column is-6">
              <VField label="Tanggal Klarifikasi Teknis (Klartek)">
                <VDatePicker v-model="f.tglKlartek" :popover="datePickerPopover" is24hr mode="dateTime"
                  style="width:100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih tanggal dan waktu"
                          aria-label="Tanggal Klarifikasi Teknis (Klartek)" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>

            <div class="column is-6">
              <VField label="Tanggal Pembukaan Penawaran">
                <VDatePicker v-model="f.tglPembukaanPenawaran" :popover="datePickerPopover" is24hr mode="dateTime"
                  style="width:100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih tanggal dan waktu"
                          aria-label="Tanggal Pembukaan Penawaran" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>

            <div class="column is-6">
              <VField label="Tanggal PP">
                <VDatePicker v-model="f.tglPP" :popover="datePickerPopover" mode="dateTime" is24hr
                  style="width:100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih tanggal dan waktu"
                          aria-label="Tanggal PP" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>

            <div class="column is-6">
              <VField label="Nomor Purchase Order (PO)">
                <VControl>
                  <VInput v-model="f.noPO" placeholder="Mis. 45000xxxxx" />
                </VControl>
              </VField>
            </div>

            <div class="column is-6">
              <VField label="Nilai Purchase Order (PO) termasuk PPN">
                <VControl>
                  <VInput v-model="f.nilaiPOPPN" inputmode="numeric" placeholder="Masukkan angka saja"
                    @input="digitsOnly('nilaiPOPPN')" />
                </VControl>
              </VField>
            </div>

            <div class="column is-6">
              <VField label="Tanggal Purchase Order (PO)">
                <VDatePicker v-model="f.tglPO" :popover="datePickerPopover" mode="dateTime" is24hr
                  style="width:100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih tanggal dan waktu"
                          aria-label="Tanggal Purchase Order (PO)" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>

            <div class="column is-6">
              <VField label="Pelaksana / Vendor">
                <VControl>
                  <VInput v-model="f.pelaksana" placeholder="Nama Pelaksana / Vendor" />
                </VControl>
              </VField>
            </div>

            <div class="column is-6">
              <VField label="Harga Perkiraan Sendiri (HPS)">
                <VControl>
                  <VInput v-model="f.hps" inputmode="numeric" placeholder="Masukkan angka saja"
                    @input="digitsOnly('hps')" />
                </VControl>
              </VField>
            </div>
          </div>

          <VField label="Keterangan (opsional)">
            <VControl>
              <textarea class="textarea" rows="4" v-model="approvalNote"
                placeholder="Contoh: Disetujui, lanjut proses." />
            </VControl>
          </VField>
        </template>

        <template v-else-if="approvalStatusBaru === 11">
          <div class="columns is-multiline">
            <div class="column is-12">
              <VField label="SPPP (Surat Pemberitahuan Pelaksanaan Pekerjaan)">
                <VControl>
                  <VInput v-model="f.sppp" placeholder="Isi nomor/uraian SPPP" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Tanggal Mulai Realisasi">
                <VDatePicker v-model="f.tglMulaiRealisasi" :popover="datePickerPopover" is24hr mode="dateTime"
                  style="width:100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih tanggal dan waktu"
                          aria-label="Tanggal Mulai Realisasi" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Tanggal Selesai Realisasi">
                <VDatePicker v-model="f.tglSelesaiRealisasi" :popover="datePickerPopover" is24hr mode="dateTime"
                  style="width:100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih tanggal dan waktu"
                          aria-label="Tanggal Selesai Realisasi" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Denda (dalam hari)">
                <VControl>
                  <VInput v-model="f.dendaHari" inputmode="numeric" placeholder="Masukkan angka hari"
                    @input="digitsOnly('dendaHari')" />
                </VControl>
              </VField>
            </div>
          </div>

          <VField label="Keterangan (opsional)">
            <VControl>
              <textarea class="textarea" rows="4" v-model="approvalNote"
                placeholder="Contoh: Disetujui, data lengkap." />
            </VControl>
          </VField>
        </template>

        <template v-else-if="approvalStatusBaru === 15">
          <h4 class="title is-6 mb-3">SEKRETARIS TIM BA</h4>
          <div class="columns is-multiline">
            <div class="column is-12">
              <VField label="Nomor Berita Acara (BA)">
                <VControl>
                  <VInput v-model="f.noba" placeholder="Contoh: 001/BA-UMRO/2025" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Posisi dalam Tim Berita Acara (BA)">
                <VControl>
                  <VInput v-model="f.posisiba" placeholder="Contoh: Sekretaris Tim BA" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Status Berita Acara (BA)">
                <VControl>
                  <VInput v-model="f.statusba" placeholder="Contoh: Aktif / Selesai / Proses" />
                </VControl>
              </VField>
            </div>
          </div>

          <VField label="Keterangan (opsional)">
            <VControl>
              <textarea class="textarea" rows="4" v-model="approvalNote"
                placeholder="Contoh: Disetujui, data lengkap." />
            </VControl>
          </VField>
        </template>

        <template v-else-if="approvalStatusBaru === 17">
          <h4 class="title is-6 mb-3">PEMBAYARAN</h4>
          <div class="columns is-multiline">
            <div class="column is-4">
              <VField class="is-rounded-select_Z is-autocomplete-select" label="Status Pembayaran (Paid/No)">
                <VControl icon="fa:search" fullwidth class="prime-auto">
                  <AutoComplete v-model="f.statuspay" :suggestions="d_statuspaidpbj"
                    @complete="fetchStatusPaidPBJ($event)" :optionLabel="'label'" :dropdown="true" :minLength="0"
                    class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                    placeholder="Pilih status pembayaran" />
                </VControl>
              </VField>
            </div>

            <div class="column is-4">
              <VField label="Nomor BKK (Bukti Kas Keluar)">
                <VControl>
                  <VInput v-model="f.nobkk" placeholder="Contoh: BKK/UMRO/0001/2025" />
                </VControl>
              </VField>
            </div>

            <div class="column is-4">
              <VField label="Tanggal Verifikasi Invoice">
                <VDatePicker v-model="f.verifikasiinvoice" :popover="datePickerPopover" is24hr mode="dateTime"
                  style="width: 100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih tanggal dan waktu"
                          aria-label="Tanggal Verifikasi Invoice" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>
          </div>

          <VField label="Keterangan (opsional)">
            <VControl>
              <textarea class="textarea" rows="4" v-model="approvalNote"
                placeholder="Contoh: Disetujui, invoice dan BKK valid." />
            </VControl>
          </VField>
        </template>

        <template v-else-if="approvalStatusBaru === 19">
          <h4 class="title is-6 mb-3">PEMBEBANAN</h4>
          <div class="columns is-multiline">
            <div class="column is-6">
              <VField label="Nomor Jurnal">
                <VControl>
                  <VInput v-model="f.nojurnal" placeholder="Contoh: JRN/UMRO/0001/2025" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Periode Pembebanan">
                <VControl>
                  <VInput v-model="f.periode" placeholder="Contoh: Jan 2025 / 2025-01" />
                </VControl>
              </VField>
            </div>
          </div>

          <VField label="Keterangan (opsional)">
            <VControl>
              <textarea class="textarea" rows="4" v-model="approvalNote"
                placeholder="Contoh: Disetujui, pembebanan dicatat." />
            </VControl>
          </VField>
        </template>

        <template v-else-if="approvalStatusBaru === 21">
          <h4 class="title is-6 mb-3">PERIODE PERMINTAAN LIMIT</h4>
          <div class="columns is-multiline">
            <div class="column is-12">
              <VField label="Periode Permintaan Limit">
                <VControl>
                  <VInput v-model="f.periodepermintaanlimit" placeholder="Contoh: Jan–Mar 2025 / 2025-01 s.d 2025-03" />
                </VControl>
              </VField>
            </div>
          </div>

          <VField label="Keterangan (opsional)">
            <VControl>
              <textarea class="textarea" rows="4" v-model="approvalNote"
                placeholder="Contoh: Disetujui, periode limit sesuai kebutuhan." />
            </VControl>
          </VField>
        </template>

        <template v-else>
          <VField label="Keterangan Penolakan">
            <VControl>
              <textarea class="textarea" rows="5" v-model="approvalNote"
                placeholder="Contoh: Ditolak karena dokumen tidak lengkap." />
            </VControl>
          </VField>
        </template>

        <div v-if="approvalError" class="notification is-danger is-light mt-3">
          <i class="fas fa-triangle-exclamation" />
          <span class="ml-2">{{ approvalError }}</span>
        </div>
      </section>

      <footer class="modal-card-foot is-justify-content-space-between">
        <div>
          <VButton color="dark" outlined icon="feather:x" @click="closeApprovalModal()">Batal</VButton>
        </div>
        <div>
          <VButton :loading="approvalSubmitting" :disabled="!canSubmitApproval" color="primary" raised icon="feather:check"
            @click="submitApprovalWithNote()">
            Simpan
          </VButton>
        </div>
      </footer>
    </div>
  </div>

  <VModal title="" :open="modalRiwayat" noclose size="big" actions="right" @close="modalRiwayat = false"
    cancelLabel="Tutup">
    <template #content>
      <div class="column is-12">
        <div class="column" v-for="data in 3" :key="data" style="text-align:center" v-if="isLoadDataDeatilOrder">
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
                  <span>{{ item.date ? H.formatDateIndo(item.date) : '-' }}</span>
                </div>
                <div :class="'dot is-' + listColor[index + 1]"></div>
                <div class="content-wrap is-grey">
                  <div class="content-box">
                    <div class="box-text" style="width:70%">
                      <div class="meta-text">
                        <p>
                          <span>
                            {{ item.type }}
                            <span v-if="item.nama"> : {{ item.nama }}</span>
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
      </div>
    </template>
    <template #action />
  </VModal>

  <VModal title="" :open="detaiIhtModalOpen" size="medium" actions="right" @close="detaiIhtModalOpen = false">
    <template #content>
      <div class="mb-3">
        <VTag color="info" rounded>
          User upload sebanyak: {{ detailIsiIht?.length || 0 }} file
        </VTag>
      </div>

      <DataTable :value="detailIsiIht" :rows="10" :rowsPerPageOptions="[5, 10, 15]" class="p-datatable-sm">
        <Column header="No" style="width:70px; text-align:center">
          <template #body="slotProps">
            {{ (slotProps.index ?? 0) + 1 }}
          </template>
        </Column>
        <Column field="namafileiht" header="Nama File" />
        <Column header="Cetak IH" style="text-align:center; width:120px">
          <template #body="slotProps">
            <VIconButton class="mr-3" color="danger" outlined circle icon="feather:printer"
              @click="cetakIHT(slotProps.data)" />
          </template>
        </Column>
      </DataTable>
    </template>
  </VModal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ColumnGroup from 'primevue/columngroup'
import Row from 'primevue/row'
import ConfirmDialog from 'primevue/confirmdialog'
import AutoComplete from 'primevue/autocomplete'
import ProgressBar from 'primevue/progressbar'
import MultiSelect from 'primevue/multiselect'
import { useConfirm } from 'primevue/useconfirm'
import * as XLSX from 'xlsx'
import { useThemeColors } from '/@src/composable/useThemeColors'
import * as H from '/@src/utils/appHelper'
import { useApi } from '/@src/composable/useApi'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'

useHead({ title: 'Super Admin Progress PBJ - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

let listColor: any = ref(Object.keys(useThemeColors()))
const confirm = useConfirm()
// Render the calendar against the viewport so modal-body scrolling cannot crop it.
const datePickerPopover = { positionFixed: true }
const isLoading: any = ref(false)
const isPlaceLoad: any = ref(false)
const dataSourceRiwayat: any = ref([])
const item: any = ref({ search: '' })
const expandedRows = ref(null)
let modalRiwayat: any = ref(false)
let isLoadDataDeatilOrder: any = ref(false)
const detaiIhtModalOpen = ref(false)
let detailIsiIht: any = ref([])

type FrozenColumnOption = { key: string; label: string }

const defaultFrozenColumns = ['info', 'noSuratPbj', 'judul']
const frozenColumnOptions: FrozenColumnOption[] = [
  { key: 'info', label: 'Info' },
  { key: 'noSuratPbj', label: 'No Surat PBJ' },
  { key: 'detail', label: 'Detail' },
  { key: 'status', label: 'Status' },
  { key: 'progress', label: 'Progress' },
  { key: 'aksi', label: 'Aksi' },
  { key: 'cetakPbj', label: 'Cetak PBJ' },
  { key: 'cetakIh', label: 'Cetak IH' },
  { key: 'aktivitas', label: 'Aktivitas' },
  { key: 'tglPengajuan', label: 'Tanggal Pengajuan' },
  { key: 'namaPengaju', label: 'Nama Pengaju' },
  { key: 'judul', label: 'Judul' },
  { key: 'pemohon', label: 'Pemohon' },
  { key: 'prioritas', label: 'Prioritas' },
  { key: 'pengadaan', label: 'Pengadaan' },
  { key: 'anggaran', label: 'Anggaran' },
  { key: 'user', label: 'User' },
  { key: 'bidang', label: 'Bidang' },
  { key: 'kepada', label: 'Kepada' },
  { key: 'prk', label: 'PRK' },
  { key: 'wo', label: 'WO' },
  { key: 'project', label: 'Project' },
  { key: 'noProyek', label: 'No Proyek' },
  { key: 'costcode', label: 'Cost Code' },
  { key: 'catatan', label: 'Catatan' },
  { key: 'noPr', label: 'No PR' },
  { key: 'tglPr', label: 'Tanggal PR' },
  { key: 'tglRo', label: 'Tanggal RO' },
  { key: 'tglHpe', label: 'Tanggal HPE' },
  { key: 'tglRks', label: 'Tanggal RKS' },
  { key: 'tglSerah', label: 'Tanggal Serah' },
  { key: 'aanwijzing', label: 'Aanwijzing' },
  { key: 'klartek', label: 'Klarifikasi Teknis' },
  { key: 'bukaPenawaran', label: 'Buka Penawaran' },
  { key: 'tglPp', label: 'Tanggal PP' },
  { key: 'noPo', label: 'No PO' },
  { key: 'nilaiPo', label: 'Nilai PO' },
  { key: 'tglPo', label: 'Tanggal PO' },
  { key: 'pelaksana', label: 'Pelaksana' },
  { key: 'hps', label: 'HPS' },
  { key: 'sppp', label: 'SPPP' },
  { key: 'mulai', label: 'Mulai' },
  { key: 'selesai', label: 'Selesai' },
  { key: 'denda', label: 'Denda' },
  { key: 'noBa', label: 'No BA' },
  { key: 'posisiBa', label: 'Posisi BA' },
  { key: 'statusBa', label: 'Status BA' },
  { key: 'verifInvoice', label: 'Verifikasi Invoice' },
  { key: 'noBkk', label: 'No BKK' },
  { key: 'statusPaid', label: 'Status Pembayaran' },
  { key: 'ketPembayaran', label: 'Keterangan Pembayaran' },
  { key: 'noJurnal', label: 'No Jurnal' },
  { key: 'periode', label: 'Periode' },
  { key: 'ketPembebanan', label: 'Keterangan Pembebanan' },
  { key: 'periodeLimit', label: 'Periode Limit' },
  { key: 'ketLimit', label: 'Keterangan Limit' },
]
const frozenColumns = ref<string[]>([...defaultFrozenColumns])
const isColumnFrozen = (key: string) => frozenColumns.value.includes(key)
const resetFrozenColumns = () => {
  frozenColumns.value = [...defaultFrozenColumns]
}

interface TimelineItem {
  date: string | null
  type: string
  nama: string
}

const timelineItems = ref<TimelineItem[]>([])

type StepLegend = { step: number; label: string }
const stepsLegend: StepLegend[] = [
  { step: 1, label: 'Diajukan' },
  { step: 2, label: 'Diverifikasi Asman' },
  { step: 3, label: 'Diverifikasi Manager' },
  { step: 4, label: 'Diverifikasi Rendal' },
  { step: 5, label: 'Diverifikasi Inventory' },
  { step: 6, label: 'Diverifikasi Pengadaan' },
  { step: 7, label: 'Diverifikasi BA 1' },
  { step: 8, label: 'Diverifikasi Asman untuk BA 1' },
  { step: 9, label: 'Diverifikasi BA 2' },
  { step: 10, label: 'Diverifikasi Pembayaran' },
  { step: 11, label: 'Diverifikasi Pembebanan' },
  { step: 12, label: 'Diverifikasi Permintaan Limit' },
]


const createEmptyFilters = () => ({
  keyword: '',
  lokasi: '',
  status: '',
  progressStep: '',
  prioritas: '',
  pengadaan: '',
  noSurat: '',
  pengaju: '',
  judul: '',
  pemohon: '',
  bidang: '',
  user: '',
  kepada: '',
  noPr: '',
  noPo: '',
  statusBa: '',
  statusPaid: '',
  tglAjuanDari: '',
  tglAjuanSampai: '',
  adaIh: '',
})

const filters = ref(createEmptyFilters())

const resetFilters = () => {
  filters.value = createEmptyFilters()
}

const textValue = (value: any) => {
  if (value === null || value === undefined) return ''
  return String(value).trim()
}

const textLower = (value: any) => textValue(value).toLowerCase()

const includesFilter = (source: any, filter: any) => {
  const f = textLower(filter)
  if (!f) return true
  return textLower(source).includes(f)
}

const getOptionLabel = (value: any) => {
  if (value === null || value === undefined || value === '') return ''
  if (typeof value === 'object') {
    return textValue(value.label || value.namalengkap || value.nama || value.name || value.value || value.id)
  }
  return textValue(value)
}

const uniqueOptions = (rows: any[], getter: (row: any) => any, fixed: string[] = []) => {
  const map = new Map<string, string>()

  fixed.forEach((e) => {
    if (textValue(e)) map.set(textLower(e), textValue(e))
  })

    ; (rows || []).forEach((row) => {
      const raw = getOptionLabel(getter(row))
      if (raw) map.set(textLower(raw), raw)
    })

  return Array.from(map.values()).sort((a, b) => a.localeCompare(b))
}

const rowLokasiText = (row: any) => {
  const lokasi = firstVal(row, [
    'lokasi',
    'lokasipbj',
    'lokasi_pbj',
    'namalokasi',
    'nama_lokasi',
    'lokasikalibrasi',
    'lokasi_kalibrasi',
    'lokasiunit',
    'lokasi_unit',
    'unitlokasi',
    'unit_lokasi',
    'cabang',
    'namacabang',
    'nama_cabang',
    'site',
    'sitepbj',
    'area',
  ])

  return lokasi ? textValue(lokasi) : ''
}

const rowGlobalText = (row: any) =>
  [
    row?.nosuratpbj,
    row?.tglpengajuan,
    row?.namalengkap,
    row?.judulpermintaan,
    row?.pemohonpbj,
    row?.prioritaspbj,
    row?.pengadaanpbj,
    kebutuhanText(row),
    userText(row),
    bidangText(row),
    kepadaText(row),
    row?.prk,
    row?.wo,
    row?.project,
    row?.noproyek,
    row?.costcode,
    row?.notes,
    row?.nopr,
    row?.nopo,
    row?.pelaksana,
    row?.sppp,
    row?.noba,
    row?.posisiba,
    row?.statusba,
    row?.nobkk,
    row?.statuspaid,
    row?.nojurnal,
    row?.periode,
    row?.periodepermintaanlimit,
    row?.statusText,
    row?.progress,
    rowLokasiText(row),
  ]
    .map((e) => textValue(e))
    .join(' | ')

const dateTimeValue = (value: any, endOfDay = false) => {
  if (!value) return null

  let d: Date
  if (value instanceof Date) {
    d = value
  } else {
    const str = String(value)
    d = new Date(str.length === 10 ? `${str}T00:00:00` : str)
  }

  if (isNaN(d.getTime())) return null
  if (endOfDay) d.setHours(23, 59, 59, 999)
  else d.setHours(0, 0, 0, 0)

  return d.getTime()
}

const isDateInRange = (value: any, start: string, end: string) => {
  if (!start && !end) return true

  const target = dateTimeValue(value)
  if (target === null) return false

  const min = start ? dateTimeValue(start) : null
  const max = end ? dateTimeValue(end, true) : null

  if (min !== null && target < min) return false
  if (max !== null && target > max) return false

  return true
}

const lokasiOptions = computed(() => uniqueOptions(dataSourceRiwayat.value, rowLokasiText, ['Gresik', 'Jakarta']))
const statusOptions = computed(() => uniqueOptions(dataSourceRiwayat.value, (row) => row?.statusText))
const prioritasOptions = computed(() => uniqueOptions(dataSourceRiwayat.value, (row) => row?.prioritaspbj))
const pengadaanOptions = computed(() => uniqueOptions(dataSourceRiwayat.value, (row) => row?.pengadaanpbj))
const statusBaOptions = computed(() => uniqueOptions(dataSourceRiwayat.value, (row) => row?.statusba))
const statusPaidOptions = computed(() => uniqueOptions(dataSourceRiwayat.value, (row) => row?.statuspaid))

const filteredDataSourceRiwayat = computed(() => {
  return (dataSourceRiwayat.value || []).filter((row: any) => {
    const f = filters.value

    if (!includesFilter(rowGlobalText(row), f.keyword)) return false
    if (f.lokasi && !includesFilter(rowLokasiText(row), f.lokasi)) return false
    if (f.status && textLower(row?.statusText) !== textLower(f.status)) return false
    if (f.progressStep && String(normalizeStep(row?.progress_step)) !== String(f.progressStep)) return false
    if (f.prioritas && textLower(row?.prioritaspbj) !== textLower(f.prioritas)) return false
    if (f.pengadaan && textLower(row?.pengadaanpbj) !== textLower(f.pengadaan)) return false
    if (!includesFilter(row?.nosuratpbj, f.noSurat)) return false
    if (!includesFilter(row?.namalengkap, f.pengaju)) return false
    if (!includesFilter(row?.judulpermintaan, f.judul)) return false
    if (!includesFilter(row?.pemohonpbj, f.pemohon)) return false
    if (!includesFilter(bidangText(row), f.bidang)) return false
    if (!includesFilter(userText(row), f.user)) return false
    if (!includesFilter(kepadaText(row), f.kepada)) return false
    if (!includesFilter(row?.nopr, f.noPr)) return false
    if (!includesFilter(row?.nopo, f.noPo)) return false
    if (f.statusBa && textLower(row?.statusba) !== textLower(f.statusBa)) return false
    if (f.statusPaid && textLower(row?.statuspaid) !== textLower(f.statusPaid)) return false
    if (!isDateInRange(row?.tglpengajuan, f.tglAjuanDari, f.tglAjuanSampai)) return false

    if (f.adaIh === 'ya' && !row?.dataListIht) return false
    if (f.adaIh === 'tidak' && row?.dataListIht) return false

    return true
  })
})

const normalizeStep = (val: any): number => {
  if (val === null || val === undefined || val === '') return 0
  if (typeof val === 'number' && !isNaN(val)) return val

  const str = String(val).trim()
  const match = str.match(/\d+/)
  return match ? Number(match[0]) : 0
}

const getNextStep = (row: any): number => {
  const doneStep = normalizeStep(row?.progress_step)
  return doneStep + 1
}

const firstVal = (row: any, keys: string[]) => {
  for (const k of keys) {
    const v = row?.[k]
    if (v !== null && v !== undefined && String(v).trim() !== '') return v
  }
  return null
}

const pickSmart = (row: any, base: string) => {
  const mode = firstVal(row, [`${base}_mode`, `${base}mode`, `${base}Mode`])
  const manual = firstVal(row, [`${base}manual`, `${base}_manual`, `${base}Manual`])
  const label = firstVal(row, [`${base}_label`, `${base}label`, `${base}Label`])
  const fallback = firstVal(row, [base])

  const m = (mode || '').toString().toLowerCase()
  if (m === 'manual') return manual ? String(manual) : '-'
  if (label) return String(label)
  if (fallback) return String(fallback)
  return '-'
}

const kebutuhanText = (row: any) => pickSmart(row, 'kebutuhan')
const userText = (row: any) => pickSmart(row, 'user')
const bidangText = (row: any) => pickSmart(row, 'bidang')
const kepadaText = (row: any) => pickSmart(row, 'kepada')

const getProgressLabel = (step: number) => {
  return stepsLegend.find((s) => s.step === step)?.label || 'Selesai'
}

const getRowStatus = (row: any) => {
  const doneStep = normalizeStep(row?.progress_step)
  const nextStep = doneStep + 1

  if (doneStep >= 12) return { text: 'Selesai', color: 'success' }
  if (nextStep === 8) return { text: 'Menunggu Asman BA1', color: 'warning' }
  if (nextStep >= 4 && nextStep <= 12) return { text: `Siap diproses ${getProgressLabel(nextStep)}`, color: 'info' }

  return { text: row?.progress || 'Diproses', color: 'warning' }
}

const stepPercent = (step: any): number => {
  const s = Math.max(1, Math.min(12, normalizeStep(step) || 1))
  return Math.round((s / 12) * 100)
}

const detailOrder = async (row: any) => {
  modalRiwayat.value = true
  isLoadDataDeatilOrder.value = true
  timelineItems.value = []
  try {
    const res = await useApi().get(`/pbj/get-pengajuan-pbj-detail?norec=${row.norec}`)
    timelineItems.value = res.timeline || []
  } catch (e) {
    timelineItems.value = []
  } finally {
    isLoadDataDeatilOrder.value = false
  }
}

const decorateRows = (rows: any[]) =>
  (rows || []).map((e: any, i: number) => {
    e.no = i + 1
    e.progress_step = normalizeStep(e.progress_step)
    const m = getRowStatus(e)
    e.statusText = m.text
    e.color = m.color
    return e
  })

const fetchDataRiwayat = async () => {
  isPlaceLoad.value = true
  try {
    const search = item.value.search ? `?search=${encodeURIComponent(item.value.search)}` : ''
    const res = await useApi().get(`pbj/get-riwayat-pbj-super-admin${search}`)

      ; (res || []).forEach((e: any) => {
        e.progress_step = normalizeStep(e.progress_step)
        e.detailList = (e.detailList || []).map((d: any, idx: number) => ({
          ...d,
          no: idx + 1,
        }))
      })

    dataSourceRiwayat.value = decorateRows(res || [])
  } catch (e) {
    dataSourceRiwayat.value = []
  } finally {
    isPlaceLoad.value = false
  }
}

const rupiahFmt = new Intl.NumberFormat('id-ID')
const formatRupiah = (val: any) => {
  if (val === null || val === undefined || val === '') return ''
  const n = Number(val)
  return isNaN(n) ? '' : rupiahFmt.format(n)
}

const cetakPBJ = (e: any) => {
  H.printBlade(`pbj/cetak-pbj?pdf=true&norec=${e.norec}`)
}

const cetakIHT = (item: any) => {
  H.printBlade(`pbj/cetak-pbj-iht?norec=${item.norec}`)
}

const lihatDetailIht = (e: any) => {
  detailIsiIht.value = e
  detaiIhtModalOpen.value = true
}

type ActionConfig = {
  step: number
  label: string
  approveStatus: number
  rejectStatus: number
  endpoint: string
  statusField: string
  noteField: string
}

const getActionConfig = (row: any): ActionConfig | null => {
  const step = getNextStep(row)

  if (step === 4) {
    return {
      step: 4,
      label: 'Rendal',
      approveStatus: 5,
      rejectStatus: 6,
      endpoint: 'pbj/approval-rendal',
      statusField: 'statusorderrendal',
      noteField: 'ketverifrendal',
    }
  }

  if (step === 5) {
    return {
      step: 5,
      label: 'Inventory',
      approveStatus: 7,
      rejectStatus: 8,
      endpoint: 'pbj/approval-inven1',
      statusField: 'statusordinven1',
      noteField: 'ketverifinven1',
    }
  }

  if (step === 6) {
    return {
      step: 6,
      label: 'Pengadaan',
      approveStatus: 9,
      rejectStatus: 10,
      endpoint: 'pbj/approval-pengadaan',
      statusField: 'statusorderpengadaan',
      noteField: 'ketverifpengadaan',
    }
  }

  if (step === 7) {
    return {
      step: 7,
      label: 'BA1',
      approveStatus: 11,
      rejectStatus: 12,
      endpoint: 'pbj/approval-ba1',
      statusField: 'statusorderba1',
      noteField: 'ketverifba1',
    }
  }

  if (step === 9) {
    return {
      step: 9,
      label: 'BA2',
      approveStatus: 15,
      rejectStatus: 16,
      endpoint: 'pbj/approval-ba2',
      statusField: 'statusorderba2',
      noteField: 'ketverifba2',
    }
  }

  if (step === 10) {
    return {
      step: 10,
      label: 'Pembayaran',
      approveStatus: 17,
      rejectStatus: 18,
      endpoint: 'pbj/approval-pembayaran',
      statusField: 'statusorderpembayaran',
      noteField: 'ketverifpembayaran',
    }
  }

  if (step === 11) {
    return {
      step: 11,
      label: 'Pembebanan',
      approveStatus: 19,
      rejectStatus: 20,
      endpoint: 'pbj/approval-pembebanan',
      statusField: 'statusorderpembebanan',
      noteField: 'ketverifpembebanan',
    }
  }

  if (step === 12) {
    return {
      step: 12,
      label: 'Permintaan Limit',
      approveStatus: 21,
      rejectStatus: 22,
      endpoint: 'pbj/approval-permintaan-limit',
      statusField: 'statusorderpermintaanlimit',
      noteField: 'ketverifpermitaanlimit',
    }
  }

  return null
}

const canApproveRow = (row: any) => {
  const nextStep = getNextStep(row)
  return nextStep >= 4 && nextStep <= 12 && nextStep !== 8 && !!getActionConfig(row)
}

const canRejectRow = (row: any) => {
  const nextStep = getNextStep(row)
  return nextStep >= 4 && nextStep <= 12 && nextStep !== 8 && !!getActionConfig(row)
}

const approveTooltip = (row: any) => {
  const nextStep = getNextStep(row)
  const cfg = getActionConfig(row)

  if (nextStep === 8) return 'Step 8 otoritas Asman'
  if (!cfg) return 'Tidak ada aksi'
  return `Setujui ${cfg.label}`
}

const rejectTooltip = (row: any) => {
  const nextStep = getNextStep(row)
  const cfg = getActionConfig(row)

  if (nextStep === 8) return 'Step 8 otoritas Asman'
  if (!cfg) return 'Tidak ada aksi'
  return `Tolak ${cfg.label}`
}

const modalApproval = ref(false)
const approvalTargetRow: any = ref(null)
const approvalStatusBaru = ref<number>(0)
const approvalNote = ref('')
const approvalError = ref('')
const approvalSubmitting = ref(false)
const inventorySubmittingNorec = ref<string | null>(null)
const approvalNoPR = ref('')
const approvalTglPembuatannPR = ref<Date | null>(null)
const approvalConfig = ref<ActionConfig | null>(null)
const approvalIsReject = ref(false)

const createEmptyForm = () => ({
  tglAanwijzing: null as Date | null,
  tglKlartek: null as Date | null,
  tglPembukaanPenawaran: null as Date | null,
  tglPP: null as Date | null,
  noPO: '',
  nilaiPOPPN: '',
  tglPO: null as Date | null,
  pelaksana: '',
  hps: '',

  sppp: '',
  tglMulaiRealisasi: null as Date | null,
  tglSelesaiRealisasi: null as Date | null,
  dendaHari: '',

  noba: '',
  posisiba: '',
  statusba: '',

  statuspay: null as any,
  nobkk: '',
  verifikasiinvoice: null as Date | null,

  nojurnal: '',
  periode: '',

  periodepermintaanlimit: '',
})

const f = ref(createEmptyForm())

const resetApprovalForm = () => {
  approvalNoPR.value = ''
  approvalTglPembuatannPR.value = null
  approvalNote.value = ''
  approvalError.value = ''
  approvalSubmitting.value = false
  f.value = createEmptyForm()
}

const closeApprovalModal = () => {
  modalApproval.value = false
  approvalTargetRow.value = null
  approvalStatusBaru.value = 0
  approvalConfig.value = null
  approvalIsReject.value = false
  resetApprovalForm()
}

const approvalStatusBaruLabel = computed(() => (approvalIsReject.value ? 'Penolakan' : 'Persetujuan'))

const modalTitle = computed(() => {
  const label = approvalConfig.value?.label || ''
  return `Keterangan ${approvalStatusBaruLabel.value}${label ? ' - ' + label : ''}`
})

const confirmStepAction = (row: any, isReject = false) => {
  const nextStep = getNextStep(row)
  const cfg = getActionConfig(row)

  if (nextStep === 8) {
    H.alert('warning', 'Step 8 adalah otoritas Asman dan tidak diproses dari halaman ini.')
    return
  }

  if (!cfg) {
    H.alert('warning', `Next progress step "${nextStep}" tidak dikenali atau tidak bisa diproses.`)
    return
  }

  const isInventoryApproval = cfg.step === 5 && !isReject
  confirm.require({
    message: isInventoryApproval
      ? `Simpan persetujuan Inventory untuk No Surat PBJ ${row.nosuratpbj || '-'}? Tidak ada form tanggal yang perlu diisi.`
      : `Yakin ingin ${isReject ? 'MENOLAK' : 'MENYETUJUI'} proses ${cfg.label} untuk PBJ ini?`,
    header: isInventoryApproval ? 'Simpan Approval Inventory' : (isReject ? 'Konfirmasi Penolakan' : 'Konfirmasi Persetujuan'),
    icon: 'pi pi-question-circle',
    acceptLabel: isInventoryApproval ? 'Simpan' : (isReject ? 'Tolak' : 'Setujui'),
    rejectLabel: 'Batal',
    acceptClass: isReject ? 'p-button-danger' : 'p-button-success',
    rejectClass: 'p-button-text',
    accept: () => isInventoryApproval
      ? submitInventoryStatusOnly(row, cfg)
      : openApprovalModal(row, cfg, isReject),
  })
}

const openApprovalModal = (row: any, cfg: ActionConfig, isReject = false) => {
  resetApprovalForm()
  approvalTargetRow.value = row
  approvalConfig.value = cfg
  approvalIsReject.value = isReject
  approvalStatusBaru.value = isReject ? cfg.rejectStatus : cfg.approveStatus
  modalApproval.value = true
}

const submitInventoryStatusOnly = async (row: any, cfg: ActionConfig) => {
  if (!row?.norec || inventorySubmittingNorec.value) return

  inventorySubmittingNorec.value = row.norec
  try {
    await useApi().post(cfg.endpoint, {
      norec: row.norec,
      [cfg.statusField]: cfg.approveStatus,
      superadmin_partial: true,
    })
    H.alert('success', 'Approval Inventory berhasil disimpan.')
    await fetchDataRiwayat()
  } catch (e) {
    H.alert('error', 'Approval Inventory gagal disimpan. Silakan coba lagi.')
  } finally {
    inventorySubmittingNorec.value = null
  }
}

const fmtDate = (d: Date | null) => (d ? H.formatDate(d, 'YYYY-MM-DD hh:mm:ss') : null)

const digitsOnly = (key: string) => {
  const val = (f.value as any)[key]
    ; (f.value as any)[key] = String(val || '').replace(/[^\d]/g, '')
}

const d_statuspaidpbj = ref<any[]>([])

const fetchStatusPaidPBJ = async (event: any) => {
  const query = encodeURIComponent(event.query || '')
  d_statuspaidpbj.value = await useApi().get(
    `general/dropdown/statuspaidpbj_m?select=id,statuspaid&param_search=statuspaid&query=${query}&limit=10`
  )
}

const hasTextValue = (value: unknown) => String(value ?? '').trim().length > 0
const hasDateValue = (value: unknown) => value instanceof Date && !isNaN(value.getTime())

const hasAnyApprovalInput = computed(() => {
  if (hasTextValue(approvalNote.value)) return true
  if (approvalIsReject.value) return false

  if (approvalStatusBaru.value === 5) {
    return hasTextValue(approvalNoPR.value) || hasDateValue(approvalTglPembuatannPR.value)
  }

  if (approvalStatusBaru.value === 9) {
    return [f.value.tglAanwijzing, f.value.tglKlartek, f.value.tglPembukaanPenawaran, f.value.tglPP, f.value.tglPO]
      .some(hasDateValue)
      || [f.value.noPO, f.value.nilaiPOPPN, f.value.pelaksana, f.value.hps].some(hasTextValue)
  }

  if (approvalStatusBaru.value === 11) {
    return [f.value.tglMulaiRealisasi, f.value.tglSelesaiRealisasi].some(hasDateValue)
      || [f.value.sppp, f.value.dendaHari].some(hasTextValue)
  }

  if (approvalStatusBaru.value === 15) {
    return [f.value.noba, f.value.posisiba, f.value.statusba].some(hasTextValue)
  }

  if (approvalStatusBaru.value === 17) {
    return hasDateValue(f.value.verifikasiinvoice)
      || hasTextValue(f.value.nobkk)
      || hasTextValue(f.value.statuspay?.value ?? f.value.statuspay)
  }

  if (approvalStatusBaru.value === 19) {
    return [f.value.nojurnal, f.value.periode].some(hasTextValue)
  }

  if (approvalStatusBaru.value === 21) {
    return hasTextValue(f.value.periodepermintaanlimit)
  }

  return false
})

const canSubmitApproval = computed(() => hasAnyApprovalInput.value && !approvalSubmitting.value)

const validateApproval = () => {
  approvalError.value = ''

  if (!hasAnyApprovalInput.value) {
    approvalError.value = 'Isi minimal satu field sebelum menyimpan.'
    return false
  }

  if (
    approvalStatusBaru.value === 11
    && hasDateValue(f.value.tglMulaiRealisasi)
    && hasDateValue(f.value.tglSelesaiRealisasi)
    && f.value.tglSelesaiRealisasi!.getTime() < f.value.tglMulaiRealisasi!.getTime()
  ) {
    approvalError.value = 'Tanggal Selesai Realisasi tidak boleh lebih awal dari Tanggal Mulai Realisasi.'
    return false
  }

  return true
}

const buildPayload = () => {
  if (!approvalTargetRow.value || !approvalConfig.value) return null

  const payload: any = {
    norec: approvalTargetRow.value.norec,
    [approvalConfig.value.statusField]: approvalStatusBaru.value,
    superadmin_partial: true,
  }

  const addText = (key: string, value: unknown) => {
    if (hasTextValue(value)) payload[key] = String(value).trim()
  }
  const addDate = (key: string, value: Date | null) => {
    if (hasDateValue(value)) payload[key] = fmtDate(value)
  }

  addText(approvalConfig.value.noteField, approvalNote.value)

  if (approvalStatusBaru.value === 5) {
    addText('nopr', approvalNoPR.value)
    addDate('tglpembuatanpr', approvalTglPembuatannPR.value)
  }

  if (approvalStatusBaru.value === 9) {
    addDate('tglaanwijzing', f.value.tglAanwijzing)
    addDate('tglklartek', f.value.tglKlartek)
    addDate('tglpembukaanpenawaran', f.value.tglPembukaanPenawaran)
    addDate('tglpp', f.value.tglPP)
    addText('nopo', f.value.noPO)
    if (hasTextValue(f.value.nilaiPOPPN)) payload.nilaipoppn = Number(f.value.nilaiPOPPN)
    addDate('tglpo', f.value.tglPO)
    addText('pelaksana', f.value.pelaksana)
    if (hasTextValue(f.value.hps)) payload.hps = Number(f.value.hps)
  }

  if (approvalStatusBaru.value === 11) {
    addText('sppp', f.value.sppp)
    addDate('tglmulairealisasi', f.value.tglMulaiRealisasi)
    addDate('tglselesairealisasi', f.value.tglSelesaiRealisasi)
    if (hasTextValue(f.value.dendaHari)) payload.dendahari = Number(f.value.dendaHari)
  }

  if (approvalStatusBaru.value === 15) {
    addText('noba', f.value.noba)
    addText('posisiba', f.value.posisiba)
    addText('statusba', f.value.statusba)
  }

  if (approvalStatusBaru.value === 17) {
    addDate('verifikasiinvoice', f.value.verifikasiinvoice)
    addText('nobkk', f.value.nobkk)
    addText('statuspembayaran', f.value.statuspay?.value ?? f.value.statuspay)
  }

  if (approvalStatusBaru.value === 19) {
    addText('nojurnal', f.value.nojurnal)
    addText('periode', f.value.periode)
  }

  if (approvalStatusBaru.value === 21) {
    addText('periodepermintaanlimit', f.value.periodepermintaanlimit)
  }

  return payload
}

const submitApprovalWithNote = async () => {
  approvalError.value = ''

  if (!approvalTargetRow.value || approvalStatusBaru.value === 0 || !approvalConfig.value) return
  if (!validateApproval()) return

  const payload = buildPayload()
  if (!payload) return

  approvalSubmitting.value = true
  try {
    await useApi().post(approvalConfig.value.endpoint, payload)
    closeApprovalModal()
    await fetchDataRiwayat()
  } catch (e) {
    approvalError.value = 'Gagal menyimpan approval. Coba lagi.'
  } finally {
    approvalSubmitting.value = false
  }
}

const remakeData: any = ref([])
const exportExcel = () => {
  remakeData.value = (filteredDataSourceRiwayat.value || []).map((e: any) => ({
    No: e.no,
    'No Surat PBJ': e.nosuratpbj,
    'Tanggal Pengajuan': H.formatDateToLocalString(e.tglpengajuan),
    'Nama Pengaju': e.namalengkap,
    Lokasi: rowLokasiText(e),
    'Judul Permintaan': e.judulpermintaan,
    'Pemohon PBJ': e.pemohonpbj,
    'Pengadaan PBJ': e.pengadaanpbj,
    Status: e.statusText,
    Progress: e.progress,
    Step: e.progress_step,
    'No PR': e.nopr,
    'Tgl Pembuatan PR': e.tglpembuatanpr ? H.formatDateToLocalString(e.tglpembuatanpr) : '',
    'Tgl RO': e.tglpenerimaanro ? H.formatDateToLocalString(e.tglpenerimaanro) : '',
    'Tgl HPE': e.tglpenerimaanhpe ? H.formatDateToLocalString(e.tglpenerimaanhpe) : '',
    'Tgl RKS': e.tglpenerimaanrks ? H.formatDateToLocalString(e.tglpenerimaanrks) : '',
    'Tgl Serah Dokumen': e.tglpenyerahandokumen ? H.formatDateToLocalString(e.tglpenyerahandokumen) : '',
    'No PO': e.nopo,
    'Nilai PO + PPN': e.nilaipoppn,
    Pelaksana: e.pelaksana,
    HPS: e.hps,
    SPPP: e.sppp,
    'No BA': e.noba,
    'Posisi BA': e.posisiba,
    'Status BA': e.statusba,
    'No BKK': e.nobkk,
    'Status Paid': e.statuspaid,
    'No Jurnal': e.nojurnal,
    Periode: e.periode,
    'Periode Permintaan Limit': e.periodepermintaanlimit,
  }))

  const ws = XLSX.utils.json_to_sheet(remakeData.value)
  const wb = { Sheets: { data: ws }, SheetNames: ['data'] }
  const buff: any = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })
  const data = new Blob([buff], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8',
  })
  const url = window.URL.createObjectURL(data)
  const a = document.createElement('a')
  a.href = url
  a.download = 'super-admin-progress-pbj.xlsx'
  document.body.appendChild(a)
  a.click()
  a.remove()
  window.URL.revokeObjectURL(url)
}

fetchDataRiwayat()
</script>

<style lang="scss">
.input-calendar {
  min-width: 140px;
}

.freeze-column-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: auto;
}

.freeze-column-label {
  color: #475569;
  font-size: 0.85rem;
  font-weight: 600;
  white-space: nowrap;
}

.freeze-column-select {
  min-width: 340px;
  max-width: 480px;
}

@media (max-width: 768px) {
  .freeze-column-control {
    width: 100%;
    align-items: stretch;
    flex-wrap: wrap;
    margin-left: 0;
  }

  .freeze-column-label {
    width: 100%;
  }

  .freeze-column-select {
    flex: 1 1 240px;
    min-width: 0;
  }
}

.icon-separator {
  font-size: 0.45rem;
  margin: 0 0.5rem;
  vertical-align: middle;
}

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
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(59, 130, 246, 0.08));
  border: 1px solid rgba(0, 0, 0, 0.06);
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
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.08));
}

.kp-hero-text {
  line-height: 1.2;
}

.kp-hero-title {
  margin: 0;
  font-weight: 700;
  font-size: 1.35rem;
  letter-spacing: 0.2px;
}

.kp-hero-sub {
  margin: 0.2rem 0 0;
  font-size: 0.95rem;
  color: #6b7280;
}

@media (max-width: 768px) {
  .kp-hero {
    flex-direction: column;
    align-items: flex-start;
    padding: 16px;
    gap: 0.75rem;
  }

  .kp-hero-logo {
    height: 48px;
  }
}

/* ===== Progress cell in table ===== */
.progress-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.progress-text {
  font-weight: 600;
  color: var(--dark-text);
  margin-bottom: 2px;
}

.progress-stepper .bar-wrap {
  position: relative;
}

/* Posisi 3 titik di atas ProgressBar */
.progress-stepper .step-dots {
  position: absolute;
  left: 0;
  right: 0;
  top: -6px;
  display: flex;
  justify-content: space-between;
  padding: 0 2px;
  pointer-events: none;
}

.progress-stepper .step-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid #d5d7da;
  background: #f4f5f7;
  box-sizing: border-box;
}

.progress-stepper .step-dot.active {
  border-color: #22c55e;
  background: #22c55e;
}

.progress-stepper .step-caption {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 6px;
}

/* ====== Legenda Progress (baru) ====== */
.legend-steps {
  padding: 10px 12px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.03), rgba(16, 185, 129, 0.03));
}

.legend-title {
  font-weight: 700;
  margin-bottom: 10px;
  font-size: 0.95rem;
  color: var(--dark-text);
}

.legend-scroller {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: max-content;
  gap: 10px 14px;
  overflow-x: auto;
  padding-bottom: 6px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.6);
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 999px;
  padding: 6px 10px;
  white-space: nowrap;
}

.legend-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  background: #f8fafc;
  font-weight: 700;
  font-size: 0.8rem;
}

.legend-label {
  font-size: 0.85rem;
  color: #374151;
}

/* ===================================== */

.is-dark {
  .personal-dashboard-v2 {

    .dashboard-header,
    .dashboard-card {
      @include vuero-card--dark;
    }

    .home-header {
      .cta {
        background: var(--primary-light-2);
        box-shadow: var(--primary-box-shadow);
      }
    }
  }

  /* Dark mode adjust untuk step dots */
  .progress-stepper .step-dot {
    border-color: #394150;
    background: #111827;
  }

  .progress-stepper .step-dot.active {
    border-color: #22c55e;
    background: #22c55e;
  }

  /* Dark mode untuk legenda */
  .legend-steps {
    border-color: #2a2f3a;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.06), rgba(16, 185, 129, 0.06));
  }

  .legend-item {
    background: #0b1220;
    border-color: #2a2f3a;
  }

  .legend-num {
    border-color: #394150;
    background: #0b1220;
    color: #e5e7eb;
  }

  .legend-label {
    color: #e5e7eb;
  }
}

.kp-header-card {
  overflow: hidden;
}

.kp-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.kp-hero-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.kp-hero-logo {
  width: 56px;
  height: 56px;
  object-fit: contain;
}

.kp-hero-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin: 0;
}

.kp-hero-sub {
  margin: 0;
  color: #64748b;
}

.legend-steps {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}

.legend-title {
  font-weight: 700;
  margin-bottom: 10px;
  color: #334155;
}

.legend-scroller {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding-bottom: 4px;
}

.legend-item {
  min-width: 180px;
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 10px 12px;
  border-radius: 10px;
}

.legend-num {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  background: #0ea5e9;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: .85rem;
  flex-shrink: 0;
}

.legend-label {
  font-size: .92rem;
  color: #334155;
}

.progress-cell {
  display: flex;
  flex-direction: column;
  gap: .5rem;
}

.progress-text {
  font-weight: 600;
  color: #334155;
}

.progress-stepper {
  display: flex;
  flex-direction: column;
  gap: .35rem;
}

.bar-wrap {
  position: relative;
}

.step-dots {
  position: absolute;
  left: 0;
  top: 50%;
  width: 100%;
  transform: translateY(-50%);
  display: flex;
  justify-content: space-between;
  padding: 0 2px;
  pointer-events: none;
}

.step-dot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
  background: #cbd5e1;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px #cbd5e1;
}

.step-dot.active {
  background: #10b981;
  box-shadow: 0 0 0 1px #10b981;
}

.step-caption {
  font-size: .82rem;
  color: #64748b;
}

/*
 * main.scss membatasi semua label form menjadi 160px dan satu baris.
 * Form di halaman ini memerlukan label lengkap agar istilah PBJ tidak terpotong.
 */
.filter-card .field > .label,
.approval-modal-card .field > .label {
  width: 100% !important;
  max-width: none !important;
  height: auto !important;
  min-height: 1.2em;
  white-space: normal !important;
  overflow: visible !important;
  text-overflow: clip !important;
  overflow-wrap: anywhere;
  line-height: 1.35;
}

.approval-modal-card {
  max-height: calc(100vh - 32px);
}

.approval-modal-card .modal-card-title {
  white-space: normal;
  overflow-wrap: anywhere;
  line-height: 1.3;
}

.approval-form-body {
  overflow-y: auto;
}

.approval-form-hint {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  margin-bottom: 18px;
  padding: 10px 12px;
  border: 1px solid #bae6fd;
  border-radius: 8px;
  background: #f0f9ff;
  color: #075985;
  font-size: 0.88rem;
  line-height: 1.45;
}

.approval-form-hint i {
  margin-top: 3px;
  flex: 0 0 auto;
}

.approval-modal-card .columns.is-multiline > .column > .field > .label {
  min-height: 2.5em;
}

.approval-modal-card .modal-card-foot {
  flex-wrap: wrap;
  gap: 12px;
}

.is-dark .approval-form-hint {
  border-color: rgba(56, 189, 248, 0.35);
  background: rgba(14, 165, 233, 0.1);
  color: #bae6fd;
}


.filter-card {
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: #ffffff;
  padding: 16px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.filter-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.filter-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #334155;
}

.filter-subtitle {
  margin: 4px 0 0;
  font-size: 0.86rem;
  color: #64748b;
}

.filter-count {
  min-width: 92px;
  border-radius: 12px;
  padding: 10px 12px;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  display: flex;
  flex-direction: column;
  align-items: center;
  line-height: 1.1;
}

.filter-count-number {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0284c7;
}

.filter-count-label {
  margin-top: 3px;
  font-size: 0.75rem;
  color: #0369a1;
  white-space: nowrap;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(180px, 1fr));
  gap: 12px 14px;
}

.filter-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 14px;
  flex-wrap: wrap;
}

@media (max-width: 1280px) {
  .filter-grid {
    grid-template-columns: repeat(3, minmax(180px, 1fr));
  }
}

@media (max-width: 960px) {
  .filter-grid {
    grid-template-columns: repeat(2, minmax(160px, 1fr));
  }
}

@media (max-width: 640px) {
  .filter-head {
    flex-direction: column;
  }

  .filter-count {
    width: 100%;
    align-items: flex-start;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .filter-actions {
    justify-content: stretch;
  }

  .filter-actions .button {
    width: 100%;
  }
}

.is-dark {
  .filter-card {
    background: #0b1220;
    border-color: #2a2f3a;
    box-shadow: none;
  }

  .filter-title {
    color: #e5e7eb;
  }

  .filter-subtitle {
    color: #94a3b8;
  }

  .filter-count {
    background: rgba(14, 165, 233, 0.08);
    border-color: rgba(14, 165, 233, 0.25);
  }
}


@media (max-width: 768px) {
  .approval-modal-card {
    max-width: calc(100vw - 16px) !important;
    max-height: calc(100vh - 16px);
  }

  .approval-modal-card .columns.is-multiline > .column > .field > .label {
    min-height: 0;
  }
}
</style>
