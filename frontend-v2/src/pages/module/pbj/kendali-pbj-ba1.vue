<template>
  <ConfirmDialog />

  <div class="column">
    <VCard class="kp-header-card">
      <div class="kp-hero">
        <div class="kp-hero-left">
          <img src="/@src/assets/illustrations/dashboards/personal/UMRO.png" alt="UMRO Laboratory"
            class="kp-hero-logo" />
          <div class="kp-hero-text">
            <h3 class="kp-hero-title">Kendali Pengajuan PBJ</h3>
            <p class="kp-hero-sub">Permintaan Barang dan Jasa</p>
          </div>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
        <h3 class="title is-5 mb-2">Pengajuan</h3>
        <div class="is-flex is-align-items-center" style="gap:.5rem">
          <VTag :color="'warning'" rounded>Diajukan </VTag>
        </div>
      </div>

      <div class="column" v-if="isPlaceLoad">
        <VPlaceloadWrap v-for="data in 12" :key="data">
          <VPlaceload class="mx-2 mb-3" />
          <VPlaceload class="mx-2" />
        </VPlaceloadWrap>
      </div>

      <div class="column" v-else>
        <VPlaceholderPage v-if="dataSourcePengajuan.length == 0" title="Belum ada pengajuan PBJ" larger>
          <template #image>
            <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
            <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
          </template>
        </VPlaceholderPage>

        <div v-else>
          <DataTable v-model:expandedRows="expandedRows" dataKey="norec" :value="dataSourcePengajuan"
            class="p-datatable-sm" :loading="isLoading" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
            scrollable
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

            <ColumnGroup type="header">
              <Row>
                <Column header="Info" :rowspan="2" :frozen="true" style="background-color: #f8fafc" />
                <Column header="Detail" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Status" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Aksi" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Cetak" :colspan="2" style="background-color: #f1f5f9; text-align: center" />
                <Column header="Aktv" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Data Pengajuan PBJ" :colspan="15"
                  style="background-color: #e0f2fe; color: #0369a1; font-weight: bold; text-align: center" />
                <Column header="Rendal" :colspan="2"
                  style="background-color: #fef9c3; color: #a16207; font-weight: bold; text-align: center" />
                <Column header="Inventory" :colspan="4"
                  style="background-color: #dcfce7; color: #15803d; font-weight: bold; text-align: center" />
                <Column header="Pengadaan" :colspan="9"
                  style="background-color: #ffedd5; color: #c2410c; font-weight: bold; text-align: center" />
              </Row>
              <Row>
                <Column header="PBJ" style="background-color: #f8fafc" />
                <Column header="IH" style="background-color: #f8fafc" />
                <Column header="No Surat PBJ" style="background-color: #f0f9ff" />
                <Column header="Nama Pengaju" style="background-color: #f0f9ff" />
                <Column header="Judul" style="background-color: #f0f9ff" />
                <Column header="Pemohon" style="background-color: #f0f9ff" />
                <Column header="Pengadaan" style="background-color: #f0f9ff" />
                <Column header="Tgl Ajuan" style="background-color: #f0f9ff" />
                <Column header="Anggaran" style="background-color: #f0f9ff" />
                <Column header="User" style="background-color: #f0f9ff" />
                <Column header="Bidang" style="background-color: #f0f9ff" />
                <Column header="Kepada" style="background-color: #f0f9ff" />
                <Column header="PRK" style="background-color: #f0f9ff" />
                <Column header="WO" style="background-color: #f0f9ff" />
                <Column header="Project" style="background-color: #f0f9ff" />
                <Column header="Noproyek" style="background-color: #f0f9ff" />
                <Column header="Costcode" style="background-color: #f0f9ff" />
                <Column header="No PR" style="background-color: #fefce8" />
                <Column header="Tgl PR" style="background-color: #fefce8" />
                <Column header="Tgl RO" style="background-color: #f0fdf4" />
                <Column header="Tgl HPE" style="background-color: #f0fdf4" />
                <Column header="Tgl RKS" style="background-color: #f0fdf4" />
                <Column header="Tgl Serah" style="background-color: #f0fdf4" />
                <Column header="Aanwz" style="background-color: #fff7ed" />
                <Column header="Klrtk" style="background-color: #fff7ed" />
                <Column header="Buka" style="background-color: #fff7ed" />
                <Column header="Tgl PP" style="background-color: #fff7ed" />
                <Column header="No PO" style="background-color: #fff7ed" />
                <Column header="Nilai PO" style="background-color: #fff7ed" />
                <Column header="Tgl PO" style="background-color: #fff7ed" />
                <Column header="Plksn" style="background-color: #fff7ed" />
                <Column header="HPS" style="background-color: #fff7ed" />
              </Row>
            </ColumnGroup>

            <template #header>
              <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="is-flex is-align-items-center" style="gap:.5rem">
                  <VButton color="warning" class="mr-2 mb-3" icon="fas fa-file-excel" raised @click="exportExcel()">
                    Export Excel
                  </VButton>
                  <VButton color="info" class="mb-3" icon="feather:refresh-ccw" outlined @click="fetchDataPengajuan()">
                    Refresh
                  </VButton>
                </div>
              </div>
            </template>

            <Column field="no" frozen></Column>
            <Column expander style="width:3rem" />
            <Column field="statusText" style="min-width: 140px">
              <template #body="slotProps">
                <VTag class="ml-2" :color="slotProps.data.color" rounded>
                  {{ slotProps.data.statusText }}
                </VTag>
              </template>
            </Column>
            <Column field="Aksi" style="min-width: 120px; text-align: center;">
              <template #body="slotProps">
                <VIconButton v-tooltip.top.left="'Setujui'" color="success" outlined circle icon="fas fa-check"
                  :disabled="Number(slotProps.data.statusorderba1) !== 0"
                  @click="confirmApproval(slotProps.data, 11)" />
                <VIconButton v-tooltip.top.left="'Tolak'" class="ml-3" color="danger" outlined circle icon="feather:x"
                  :disabled="Number(slotProps.data.statusorderba1) !== 0"
                  @click="confirmApproval(slotProps.data, 12)" />
              </template>
            </Column>
            <Column style="min-width: 100px">
              <template #body="slotProps">
                <VIconButton class="mr-3" color="danger" outlined circle icon="feather:printer"
                  @click="cetakPBJ(slotProps.data)" />
              </template>
            </Column>
            <Column style="text-align:center; min-width: 90px" row-clickable @row-click="lihatDetailIht">
              <template #body="slotProps">
                <VIconButton v-if="slotProps.data.dataListIht" color="warning" outlined circle icon="feather:printer"
                  @click="lihatDetailIht(slotProps.data.dataListIht)" />
              </template>
            </Column>
            <Column style="min-width: 100px">
              <template #body="slotProps">
                <VIconButton v-tooltip.bottom.left="'Aktivitas'" icon="feather:activity"
                  @click="detailOrder(slotProps.data)" color="info" raised circle class="mr-2">
                </VIconButton>
              </template>
            </Column>
            <Column field="nosuratpbj" style="min-width: 240px" />
            <Column field="namalengkap" style="min-width: 200px" />
            <Column field="judulpermintaan" style="min-width: 100px" />
            <Column field="pemohonpbj" style="min-width: 100px" />
            <Column field="pengadaanpbj" style="min-width: 100px" />
            <Column field="tglpengajuan" style="min-width: 150px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpengajuan">{{ H.formatDateIndo(slotProps.data.tglpengajuan) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ kebutuhanText(data) }}</span>
              </template>
            </Column>
            <Column style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ userText(data) }}</span>
              </template>
            </Column>
            <Column style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ bidangText(data) }}</span>
              </template>
            </Column>
            <Column style="min-width: 240px">
              <template #body="{ data }">
                <span>{{ kepadaText(data) }}</span>
              </template>
            </Column>
            <Column field="prk" style="min-width: 100px" />
            <Column field="wo" style="min-width: 100px" />
            <Column field="project" style="min-width: 100px" />
            <Column field="noproyek" style="min-width: 100px" />
            <Column field="costcode" style="min-width: 100px" />

            <Column field="nopr" style="min-width: 240px" />
            <Column field="tglpembuatanpr" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpembuatanpr">{{ H.formatDateToLocalString(slotProps.data.tglpembuatanpr)
                }}</span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglpenerimaanro" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanro">{{ H.formatDateToLocalString(slotProps.data.tglpenerimaanro)
                }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenerimaanhpe" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanhpe">{{
                  H.formatDateToLocalString(slotProps.data.tglpenerimaanhpe) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenerimaanrks" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanrks">{{
                  H.formatDateToLocalString(slotProps.data.tglpenerimaanrks) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenyerahandokumen" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenyerahandokumen">{{
                  H.formatDateToLocalString(slotProps.data.tglpenyerahandokumen) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglaanwijzing" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglaanwijzing">
                  {{ H.formatDateToLocalString(slotProps.data.tglaanwijzing) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglklartek" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglklartek">
                  {{ H.formatDateToLocalString(slotProps.data.tglklartek) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpembukaanpenawaran" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpembukaanpenawaran">
                  {{ H.formatDateToLocalString(slotProps.data.tglpembukaanpenawaran) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpp" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpp">
                  {{ H.formatDateToLocalString(slotProps.data.tglpp) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="nopo" style="min-width: 100px" />
            <Column field="nilaipoppn" style="min-width: 100px" />
            <Column field="tglpo" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpo">
                  {{ H.formatDateToLocalString(slotProps.data.tglpo) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="pelaksana" style="min-width: 100px" />
            <Column field="hps" style="min-width: 100px" />

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

    <VCard class="mt-5">
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
        <h3 class="title is-5 mb-2">Riwayat Pengajuan PBJ</h3>
        <div class="is-flex is-align-items-center" style="gap:.5rem">
          <VTag :color="'success'" rounded>Disetujui</VTag>
          <VTag :color="'danger'" rounded>Ditolak</VTag>
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

      <div class="column" v-if="isPlaceLoad">
        <VPlaceloadWrap v-for="data in 12" :key="data">
          <VPlaceload class="mx-2 mb-3" />
          <VPlaceload class="mx-2" />
        </VPlaceloadWrap>
      </div>

      <div class="column" v-else>
        <VPlaceholderPage v-if="dataSourceRiwayat.length == 0" title="Belum ada pengajuan PBJ yang Diverifikasi" larger>
          <template #image>
            <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
            <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
          </template>
        </VPlaceholderPage>

        <div v-else>
          <DataTable v-model:expandedRows="expandedRows" dataKey="norec" :value="dataSourceRiwayat"
            class="p-datatable-sm" :loading="isLoading" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
            scrollable
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

            <ColumnGroup type="header">
              <Row>
                <Column header="Info" :rowspan="2" :frozen="true" style="background-color: #f8fafc" />
                <Column header="Detail" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Status" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Progress" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Cetak PBJ" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Cetak IH" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Aktivitas" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Data Pengajuan PBJ" :colspan="10"
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
                <Column header="No Surat PBJ" :sortable="true" style="background-color: #f0f9ff" />
                <Column header="Tanggal Pengajuan" style="background-color: #f0f9ff" />
                <Column header="Nama Pegawai" style="background-color: #f0f9ff" />
                <Column header="Judul" style="background-color: #f0f9ff" />
                <Column header="Pemohon" style="background-color: #f0f9ff" />
                <Column header="Pengadaan" style="background-color: #f0f9ff" />
                <Column header="Dasar Anggaran" style="background-color: #f0f9ff" />
                <Column header="User" style="background-color: #f0f9ff" />
                <Column header="Bidang" style="background-color: #f0f9ff" />
                <Column header="Kepada" style="background-color: #f0f9ff" />
                <Column header="No PR" style="background-color: #fefce8" />
                <Column header="Tgl PR" style="background-color: #fefce8" />
                <Column header="Tgl RO" style="background-color: #f0fdf4" />
                <Column header="Tgl HPE" style="background-color: #f0fdf4" />
                <Column header="Tgl RKS" style="background-color: #f0fdf4" />
                <Column header="Tgl Penyerahan" style="background-color: #f0fdf4" />
                <Column header="Aanwijzing" style="background-color: #fff7ed" />
                <Column header="Klartek" style="background-color: #fff7ed" />
                <Column header="Pembukaan" style="background-color: #fff7ed" />
                <Column header="Tgl PP" style="background-color: #fff7ed" />
                <Column header="No PO" style="background-color: #fff7ed" />
                <Column header="Nilai PO" style="background-color: #fff7ed" />
                <Column header="Tgl PO" style="background-color: #fff7ed" />
                <Column header="Pelaksana" style="background-color: #fff7ed" />
                <Column header="HPS" style="background-color: #fff7ed" />
                <Column header="SPPP" style="background-color: #faf5ff" />
                <Column header="Mulai" style="background-color: #faf5ff" />
                <Column header="Selesai" style="background-color: #faf5ff" />
                <Column header="Denda" style="background-color: #faf5ff" />
                <Column header="No BA" style="background-color: #fdf4ff" />
                <Column header="Posisi" style="background-color: #fdf4ff" />
                <Column header="Status" style="background-color: #fdf4ff" />
                <Column header="Verif Invoice" style="background-color: #eef2ff" />
                <Column header="No BKK" style="background-color: #eef2ff" />
                <Column header="Status" style="background-color: #eef2ff" />
                <Column header="Ket" style="background-color: #eef2ff" />
                <Column header="No Jurnal" style="background-color: #f8fafc" />
                <Column header="Periode" style="background-color: #f8fafc" />
                <Column header="Ket" style="background-color: #f8fafc" />
                <Column header="Periode Limit" style="background-color: #fef2f2" />
                <Column header="Ket" style="background-color: #fef2f2" />
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
              </div>
            </template>

            <Column field="no" frozen></Column>
            <Column expander style="width:3rem" />
            <Column field="statusText" style="min-width: 140px">
              <template #body="slotProps">
                <VTag class="ml-2" :color="slotProps.data.color" rounded>
                  {{ slotProps.data.statusText }}
                </VTag>
              </template>
            </Column>
            <Column style="min-width: 400px">
              <template #body="{ data: row }">
                <div class="progress-cell">
                  <div class="progress-text">
                    {{ row.progress || '-' }}
                  </div>
                  <div class="progress-stepper">
                    <div class="bar-wrap">
                      <ProgressBar :value="stepPercent(row.progress_step)" :showValue="false" style="height:10px" />
                      <div class="step-dots">
                        <span v-for="n in 12" :key="'dot-' + n" class="step-dot"
                          :class="{ active: n <= (Number(row.progress_step || 1)) }"></span>
                      </div>
                    </div>
                    <div class="step-caption">
                      Step {{ Number(row.progress_step || 1) }}/12
                    </div>
                  </div>
                </div>
              </template>
            </Column>
            <Column style="min-width: 100px">
              <template #body="slotProps">
                <VIconButton class="mr-3" color="danger" outlined circle icon="feather:printer"
                  @click="cetakPBJ(slotProps.data)" />
              </template>
            </Column>
            <Column style="text-align:center; min-width: 90px" row-clickable @row-click="lihatDetailIht">
              <template #body="slotProps">
                <VIconButton v-if="slotProps.data.dataListIht" color="warning" outlined circle icon="feather:printer"
                  @click="lihatDetailIht(slotProps.data.dataListIht)" />
              </template>
            </Column>
            <Column style="min-width: 100px">
              <template #body="slotProps">
                <VIconButton v-tooltip.bottom.left="'Aktivitas'" icon="feather:activity"
                  @click="detailOrder(slotProps.data)" color="info" raised circle class="mr-2">
                </VIconButton>
              </template>
            </Column>

            <Column field="nosuratpbj" style="min-width: 160px" />
            <Column field="tglpengajuan" style="min-width: 140px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpengajuan">{{ H.formatDateIndo(slotProps.data.tglpengajuan) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="namalengkap" style="min-width: 200px" />
            <Column field="judulpermintaan" style="min-width: 100px" />
            <Column field="pemohonpbj" style="min-width: 100px" />
            <Column field="pengadaanpbj" style="min-width: 100px" />
            <Column style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ kebutuhanText(data) }}</span>
              </template>
            </Column>
            <Column style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ userText(data) }}</span>
              </template>
            </Column>
            <Column style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ bidangText(data) }}</span>
              </template>
            </Column>
            <Column style="min-width: 240px">
              <template #body="{ data }">
                <span>{{ kepadaText(data) }}</span>
              </template>
            </Column>

            <Column field="nopr" style="min-width: 240px" />
            <Column field="tglpembuatanpr" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpembuatanpr">{{ H.formatDateToLocalString(slotProps.data.tglpembuatanpr)
                }}</span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglpenerimaanro" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanro">{{ H.formatDateToLocalString(slotProps.data.tglpenerimaanro)
                }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenerimaanhpe" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanhpe">{{
                  H.formatDateToLocalString(slotProps.data.tglpenerimaanhpe) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenerimaanrks" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanrks">{{
                  H.formatDateToLocalString(slotProps.data.tglpenerimaanrks) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpenyerahandokumen" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenyerahandokumen">{{
                  H.formatDateToLocalString(slotProps.data.tglpenyerahandokumen) }}</span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglaanwijzing" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglaanwijzing">{{ H.formatDateToLocalString(slotProps.data.tglaanwijzing)
                }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglklartek" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglklartek">{{ H.formatDateToLocalString(slotProps.data.tglklartek) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpembukaanpenawaran" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpembukaanpenawaran">{{
                  H.formatDateToLocalString(slotProps.data.tglpembukaanpenawaran) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglpp" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpp">{{ H.formatDateToLocalString(slotProps.data.tglpp) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="nopo" style="min-width: 100px" />
            <Column field="nilaipoppn" style="min-width: 100px" />
            <Column field="tglpo" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpo">{{ H.formatDateToLocalString(slotProps.data.tglpo) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="pelaksana" style="min-width: 100px" />
            <Column field="hps" style="min-width: 100px" />

            <Column field="sppp" style="min-width: 200px" />
            <Column field="tglmulairealisasi" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglmulairealisasi">{{
                  H.formatDateToLocalString(slotProps.data.tglmulairealisasi) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="tglselesairealisasi" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglselesairealisasi">{{
                  H.formatDateToLocalString(slotProps.data.tglselesairealisasi) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="dendahari" style="min-width: 100px" />

            <Column field="noba" style="min-width: 100px" />
            <Column field="posisiba" style="min-width: 100px" />
            <Column field="statusba" style="min-width: 100px" />

            <Column field="verifikasiinvoice" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.verifikasiinvoice">{{
                  H.formatDateToLocalString(slotProps.data.verifikasiinvoice) }}</span>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="nobkk" style="min-width: 100px" />
            <Column field="statuspaid" style="min-width: 100px" />
            <Column field="ketverifpembayaran" style="min-width: 100px" />

            <Column field="nojurnal" style="min-width: 100px" />
            <Column field="periode" style="min-width: 100px" />
            <Column field="ketverifpembebanan" style="min-width: 100px" />

            <Column field="periodepermintaanlimit" style="min-width: 100px" />
            <Column field="ketverifpermitaanlimit" style="min-width: 100px" />

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
    <div class="modal-background" @click="closeApprovalModal()"></div>
    <div class="modal-card" style="width: 740px; height: 600px; max-width: 92vw">
      <header class="modal-card-head">
        <p class="modal-card-title">Keterangan {{ approvalStatusBaruLabel }}</p>
        <button class="delete" aria-label="close" @click="closeApprovalModal()"></button>
      </header>
      <section class="modal-card-body">
        <template v-if="approvalStatusBaru === 11">
          <div class="columns is-multiline">
            <div class="column is-12">
              <VField class="vreq" label="SPPP (Surat Pemberitahuan Pelaksanaan Pekerjaan)">
                <VControl>
                  <VInput v-model="f.sppp" placeholder="Isi nomor/uraian SPPP" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField class="vreq" label="TANGGAL MULAI (REALISASI)">
                <VDatePicker v-model="f.tglMulaiRealisasi" is24hr mode="dateTime" style="width:100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>
            <div class="column is-6">
              <VField class="vreq" label="TANGGAL SELESAI (REALISASI)">
                <VDatePicker v-model="f.tglSelesaiRealisasi" is24hr mode="dateTime" style="width:100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>
            <div class="column is-6">
              <VField class="vreq" label="DENDA (DALAM HARI)">
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
                placeholder="Contoh: Disetujui, data lengkap."></textarea>
            </VControl>
          </VField>
        </template>
        <template v-else>
          <VField label="Keterangan (opsional)">
            <VControl>
              <textarea class="textarea" rows="5" v-model="approvalNote"
                placeholder="Contoh: Ditolak karena ..."></textarea>
            </VControl>
          </VField>
        </template>
        <div v-if="approvalError" class="notification is-danger is-light">
          <i class="fas fa-triangle-exclamation"></i>
          <span class="ml-2">{{ approvalError }}</span>
        </div>
      </section>
      <footer class="modal-card-foot is-justify-content-space-between">
        <div>
          <VButton color="dark" outlined icon="feather:x" @click="closeApprovalModal()">Batal</VButton>
        </div>
        <div>
          <VButton :loading="approvalSubmitting" color="primary" raised icon="feather:check"
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
        <div class="column" v-for="(data) in 3" style="text-align:center" v-if="isLoadDataDeatilOrder">
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
    <template #action></template>
  </VModal>
  <VModal title="" :open="detaiIhtModalOpen" size="medium" actions="right" @close="detaiIhtModalOpen = false">
    <template #content>
      <div class="mb-3">
        <VTag color="info" rounded>
          User upload sebanyak: {{ (detailIsiIht?.length || 0) }} file
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
import { useConfirm } from 'primevue/useconfirm'
import * as XLSX from 'xlsx'
import ProgressBar from 'primevue/progressbar'
import { useThemeColors } from '/@src/composable/useThemeColors'
import * as H from '/@src/utils/appHelper'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'

useHead({ title: 'Kendali PBJ BA 1 - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

let listColor: any = ref(Object.keys(useThemeColors()))
const themeColors = useThemeColors()
const userLogin = useUserSession().getUser()
const isLoading: any = ref(false)
const isPlaceLoad: any = ref(false)
const dataSourcePengajuan: any = ref([])
const dataSourceRiwayat: any = ref([])
const item: any = ref({ search: '' })
const confirm = useConfirm()
const expandedRows = ref(null)
let modalRiwayat: any = ref(false)
let isLoadDataDeatilOrder: any = ref(false)
interface TimelineItem {
  date: string | null
  type: string
  nama: string
}
const timelineItems = ref<TimelineItem[]>([])
const detaiIhtModalOpen = ref(false)
let detailIsiIht: any = ref([])

const mapAsmanStatus = (val: any) => {
  const n = Number(val)
  if (n === 13) return { text: 'Disetujui', color: 'success' }
  if (n === 14) return { text: 'Ditolak', color: 'danger' }
  return { text: 'Diajukan', color: 'warning' }
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


const stepPercent = (step: any): number => {
  const s = Math.max(1, Math.min(12, Number(step || 1)))
  return Math.round((s / 12) * 100)
}

const cetakIHT = (item: any) => {
  H.printBlade(`pbj/cetak-pbj-iht?norec=${item.norec}`);
};

const lihatDetailIht = (e: any) => {
  detailIsiIht.value = e;
  detaiIhtModalOpen.value = true;
};

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
    const m = mapAsmanStatus(e.statusorderba1)
    e.statusText = m.text
    e.color = m.color
    return e
  })

const fetchDataPengajuan = async () => {
  isPlaceLoad.value = true
  const search = item.value.search ? `&search=${encodeURIComponent(item.value.search)}` : ''
  try {
    const res = await useApi().get(`pbj/get-pengajuan-pbj-ba1?${search}`)
    res.forEach((e: any) => {
      e.detailList = (e.detailList || []).map((d: any, idx: number) => ({
        ...d,
        no: idx + 1,
      }))
    })
    dataSourcePengajuan.value = decorateRows(res)
  } catch (e) {
    dataSourcePengajuan.value = []
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

const fetchDataRiwayat = async () => {
  isPlaceLoad.value = true
  const search = item.value.search ? `&search=${encodeURIComponent(item.value.search)}` : ''
  try {
    const res = await useApi().get(`pbj/get-riwayat-pbj-ba1?${search}`)
    res.forEach((e: any) => {
      e.detailList = (e.detailList || []).map((d: any, idx: number) => ({
        ...d,
        no: idx + 1,
      }))
    })
    dataSourceRiwayat.value = decorateRows(res)
  } catch (e) {
    dataSourceRiwayat.value = []
  } finally {
    isPlaceLoad.value = false
  }
}

const modalApproval = ref(false)
const approvalTargetRow: any = ref(null)
const approvalStatusBaru = ref<11 | 12 | 0>(0)
const approvalStatusBaruLabel = computed(() => (approvalStatusBaru.value === 11 ? 'Persetujuan' : 'Penolakan'))
const approvalNote = ref('')
const approvalError = ref('')
const approvalSubmitting = ref(false)

const f = ref<{
  sppp: string
  tglMulaiRealisasi: Date | null
  tglSelesaiRealisasi: Date | null
  dendaHari: string
  nilaiSLA: string
  konfirmasiRendalProyek: string
  statusKatimpro: string
}>({
  sppp: '',
  tglMulaiRealisasi: null,
  tglSelesaiRealisasi: null,
  dendaHari: '',
  nilaiSLA: '',
  konfirmasiRendalProyek: '',
  statusKatimpro: '',
})

const digitsOnly = (key: 'dendaHari' | 'nilaiSLA') => {
  const raw = (f.value as any)[key] ?? ''
    ; (f.value as any)[key] = String(raw).replace(/[^\d]/g, '')
}

const isBlankDate = (v: unknown) => !v || (v instanceof Date && isNaN(v.getTime()))

const confirmApproval = (row: any, statusBaru: 11 | 12) => {
  const isApprove = statusBaru === 11
  confirm.require({
    message: `Yakin ingin ${isApprove ? 'MENYETUJUI' : 'MENOLAK'} pengajuan ini?`,
    header: isApprove ? 'Konfirmasi Persetujuan' : 'Konfirmasi Penolakan',
    icon: 'pi pi-question-circle',
    acceptLabel: isApprove ? 'Setujui' : 'Tolak',
    rejectLabel: 'Batal',
    acceptClass: isApprove ? 'p-button-success' : 'p-button-danger',
    rejectClass: 'p-button-text',
    accept: () => openApprovalModal(row, statusBaru),
  })
}

const openApprovalModal = (row: any, statusBaru: 11 | 12) => {
  approvalTargetRow.value = row
  approvalStatusBaru.value = statusBaru
  approvalNote.value = ''
  approvalError.value = ''
  f.value = {
    sppp: '',
    tglMulaiRealisasi: null,
    tglSelesaiRealisasi: null,
    dendaHari: '',
    nilaiSLA: '',
    konfirmasiRendalProyek: '',
    statusKatimpro: '',
  }
  modalApproval.value = true
}

const closeApprovalModal = () => {
  modalApproval.value = false
  approvalTargetRow.value = null
  approvalStatusBaru.value = 0
  approvalNote.value = ''
  approvalError.value = ''
  f.value = {
    sppp: '',
    tglMulaiRealisasi: null,
    tglSelesaiRealisasi: null,
    dendaHari: '',
    nilaiSLA: '',
    konfirmasiRendalProyek: '',
    statusKatimpro: '',
  }
}

const submitApprovalWithNote = async () => {
  approvalError.value = ''
  if (approvalStatusBaru.value === 11) {
    if (!f.value.sppp) { H.alert('warning', 'SPPP (Surat Pemberitahuan Pelaksanaan Pekerjaan) harus di isi'); return }
    if (isBlankDate(f.value.tglMulaiRealisasi)) { H.alert('warning', 'Tanggal Mulai (Realisasi) harus di isi'); return }
    if (isBlankDate(f.value.tglSelesaiRealisasi)) { H.alert('warning', 'Tanggal Selesai (Realisasi) harus di isi'); return }
    if (f.value.tglMulaiRealisasi && f.value.tglSelesaiRealisasi) {
      if (f.value.tglSelesaiRealisasi.getTime() < f.value.tglMulaiRealisasi.getTime()) {
        H.alert('warning', 'Tanggal Selesai (Realisasi) tidak boleh lebih awal dari Tanggal Mulai (Realisasi)'); return
      }
    }
    if (!f.value.dendaHari) { H.alert('warning', 'Denda (dalam hari) harus di isi'); return }
  }
  if (!approvalTargetRow.value || approvalStatusBaru.value === 0) return
  const payload: any = {
    norec: approvalTargetRow.value.norec,
    statusorderba1: approvalStatusBaru.value,
    ketverifba1: approvalNote.value || null,
    sppp: f.value.sppp || null,
    tglmulairealisasi: f.value.tglMulaiRealisasi ? H.formatDate(f.value.tglMulaiRealisasi, 'YYYY-MM-DD hh:mm:ss') : null,
    tglselesairealisasi: f.value.tglSelesaiRealisasi ? H.formatDate(f.value.tglSelesaiRealisasi, 'YYYY-MM-DD hh:mm:ss') : null,
    dendahari: f.value.dendaHari ? Number(f.value.dendaHari) : null,
  }
  approvalSubmitting.value = true
  try {
    await useApi().post('pbj/approval-ba1', payload)
    closeApprovalModal()
    await Promise.all([fetchDataPengajuan(), fetchDataRiwayat()])
  } catch (e) {
    approvalError.value = 'Gagal menyimpan approval. Coba lagi.'
  } finally {
    approvalSubmitting.value = false
  }
}

const remakeData: any = ref([])
const exportExcel = () => {
  remakeData.value = (dataSourcePengajuan.value || []).map((e: any) => ({
    No: e.no,
    'No Surat PBJ': e.nosuratpbj,
    'Judul Permintaan': e.judulpermintaan,
    'Pemohon PBJ': e.pemohonpbj,
    'Pengadaan PBJ': e.pengadaanpbj,
    'Tanggal Pengajuan': H.formatDateToLocalString(e.tglpengajuan),
    Status: e.statusText,
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
  a.download = 'pengajuan-pbj.xlsx'
  document.body.appendChild(a)
  a.click()
  a.remove()
  window.URL.revokeObjectURL(url)
}

const cetakPBJ = (e: any) => {
  H.printBlade(`pbj/cetak-pbj?pdf=true&norec=${e.norec}`)
}

fetchDataPengajuan()
fetchDataRiwayat()
</script>

<style lang="scss">
.input-calendar {
  min-width: 140px;
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

/* --- Tanda bintang merah untuk field required --- */
.vreq .label::after {
  content: ' *';
  color: #ef4444;
  margin-left: 4px;
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
</style>
