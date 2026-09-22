<template>
  <div class="synergy-detail-wrap">

    <div class="mb-5">
      <div class="level is-mobile">
        <div class="level-left">
          <VButton icon="feather:arrow-left" @click="goBack">Kembali</VButton>
        </div>
        <div class="level-right">
          <div class="buttons">
            <VButton color="danger" icon="feather:file-text" :loading="exportingPdf" @click="exportPdf" class="mr-2">
              PDF
            </VButton>
            <VButton color="success" icon="feather:file-text" :loading="exportingExcel" @click="exportExcel">
              Excel
            </VButton>
          </div>
        </div>
      </div>

      <VCard class="mt-3">
        <div class="level is-mobile p-4">
          <div class="level-left">
            <div>
              <h2 class="title is-4 mb-2">{{ unitName }}</h2>
              <p class="mt-2 subtitle is-6 has-text-grey">Detail progress pengerjaan alat (Tahun {{ TAHUN }})</p>
            </div>
          </div>
          <div class="level-right has-text-right">
            <div>
              <p class="heading mb-1">Total Alat</p>
              <p class="title is-4">{{ items.length }}</p>
            </div>
          </div>
        </div>
      </VCard>
    </div>

    <div class="unit-notes-section mb-5">
      <div class="card">
        <header class="card-header">
          <p class="card-header-title">
            <span class="icon mr-2 has-text-warning"><i class="iconify" data-icon="feather:message-square"></i></span>
            Catatan Unit: {{ unitName }}
          </p>
          <a class="card-header-icon" aria-label="more options">
            <span class="tag is-info is-light">{{ dashUnitNotes.length }} Catatan</span>
          </a>
        </header>
        <div class="card-content">
          <div class="columns">
            <div class="column is-4">
              <div class="field">
                <label class="label is-small">
                  {{ editingDashUnitNorec ? 'Edit Catatan Unit' : 'Tulis Catatan Unit Baru' }}
                </label>
                <div class="control">
                  <textarea class="textarea is-small" rows="5"
                    placeholder="Tulis kendala, status pembayaran, atau info penting terkait unit ini..."
                    v-model="newDashUnitNote"></textarea>
                </div>
              </div>
              <div class="field has-text-right">
                <VButton v-if="editingDashUnitNorec" color="light" class="mr-2" @click="cancelEditDashUnitNote">
                  Batal
                </VButton>
                <VButton color="primary" :loading="sendingDashUnitNote" @click="saveDashUnitNote">
                  {{ editingDashUnitNorec ? 'Update' : 'Kirim' }}
                </VButton>
              </div>
            </div>

            <div class="column is-8">
              <div class="unit-notes-list">
                <div v-if="loadingDashUnitNotes" class="has-text-centered p-4">
                  <i class="iconify fa-spin" data-icon="feather:loader"></i>
                </div>
                <div v-else-if="dashUnitNotes.length === 0" class="has-text-centered has-text-grey p-4">
                  <small>Belum ada catatan khusus untuk unit ini di tahun {{ TAHUN }}.</small>
                </div>
                <div v-else v-for="note in dashUnitNotes" :key="note.norec" class="unit-note-item">
                  <div class="is-flex is-justify-content-space-between mb-1">
                    <strong>{{ note.nama_petugas }}</strong>
                    <div class="is-flex is-align-items-center">
                      <small class="has-text-grey mr-3 has-text-right" style="line-height: 1.2;">
                        <div>{{ note.formatted_date }}</div>
                        <div v-if="isEdited(note)" class="has-text-grey-light is-size-7 is-italic">
                          (Diedit: {{ formatDate(note.updated_at) }})
                        </div>
                      </small>

                      <a class="has-text-info is-clickable mr-2" @click="editDashUnitNote(note)" title="Edit Catatan">
                        <i class="iconify" data-icon="feather:edit-2"></i>
                      </a>

                      <a class="has-text-danger is-clickable" @click="deleteDashUnitNote(note.norec)"
                        title="Hapus Catatan">
                        <i class="iconify" data-icon="feather:trash-2"></i>
                      </a>
                    </div>
                  </div>
                  <p>{{ note.notes }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <section v-if="!loading" class="detail-summary-section mb-5" aria-label="Statistik detail Synergy">
      <div class="detail-summary-grid">
        <article v-for="card in detailSummaryCards" :key="card.key" class="detail-summary-card" role="button"
          tabindex="0" :data-tone="card.tone" @click="openDetailSummary(card)"
          @keydown.enter="openDetailSummary(card)">
          <div class="detail-summary-heading">
            <span class="detail-summary-icon">
              <i class="iconify" :data-icon="card.icon"></i>
            </span>
            <i class="iconify detail-summary-arrow" data-icon="feather:arrow-up-right"></i>
          </div>
          <strong>{{ formatNumber(card.value) }}</strong>
          <span>{{ card.label }}</span>
          <small>{{ card.caption }}</small>
        </article>
      </div>
    </section>

    <div class="columns is-vcentered is-multiline mb-5">
      <div class="column is-12-mobile is-4-tablet is-3-desktop">
        <VControl icon="feather:search">
          <input v-model="search" class="input" placeholder="Cari Nama Alat / SN..." />
        </VControl>
      </div>
      <div class="column is-6-mobile is-4-tablet is-2-desktop">
        <div class="select is-fullwidth">
          <select v-model="filterStatus">
            <option value="all">Semua Status</option>
            <option value="selesai">Selesai (100%)</option>
            <option value="proses">Sedang Proses</option>
          </select>
        </div>
      </div>
      <div class="column is-6-mobile is-4-tablet is-2-desktop">
        <div class="select is-fullwidth">
          <select v-model="filterType">
            <option value="all">Semua Tipe</option>
            <option value="kalibrasi">Kalibrasi</option>
            <option value="repair">Repair</option>
          </select>
        </div>
      </div>
      <div class="column is-6-mobile is-4-tablet is-2-desktop">
        <div class="select is-fullwidth">
          <select v-model="filterNotes">
            <option value="all">Semua Catatan</option>
            <option value="with_notes">Ada Catatan</option>
            <option value="no_notes">Tanpa Catatan</option>
          </select>
        </div>
      </div>
      <div class="column is-6-mobile is-4-tablet is-2-desktop">
        <div class="select is-fullwidth">
          <select v-model="filterSurkes">
            <option value="all">Status Surkes</option>
            <option value="surkes">SURKES</option>
            <option value="non_surkes">NON SURKES</option>
          </select>
        </div>
      </div>
      <div class="column is-6-mobile is-4-tablet is-2-desktop">
        <div class="select is-fullwidth">
          <select v-model="sortTglMasuk">
            <option value="none">Urut Tgl Masuk</option>
            <option value="oldest">Terlama</option>
            <option value="newest">Terbaru</option>
          </select>
        </div>
      </div>
      <div class="column is-6-mobile is-4-tablet is-2-desktop">
        <VControl icon="feather:calendar">
          <input v-model="filterTanggalMasuk" class="input" type="date" title="Pilih tanggal masuk spesifik" />
        </VControl>
      </div>
      <div class="column is-6-mobile is-4-tablet is-3-desktop">
        <MultiSelect v-model="filterScope" :options="availableScopes" placeholder="Pilih Lingkup" display="chip"
          class="custom-multiselect" :maxSelectedLabels="1" />
      </div>

      <div class="column is-6-mobile is-6-tablet is-3-desktop">
        <MultiSelect v-model="filterPelaksana" :options="availablePelaksana" placeholder="Pilih Pelaksana"
          display="chip" class="custom-multiselect" :maxSelectedLabels="1" />
      </div>
      <div class="column is-6-mobile is-6-tablet is-3-desktop">
        <MultiSelect v-model="filterPenyelia" :options="availablePenyelia" placeholder="Pilih Penyelia" display="chip"
          class="custom-multiselect" :maxSelectedLabels="1" />
      </div>

      <div class="column is-12-mobile is-12-tablet is-narrow-desktop">
        <VButton icon="feather:refresh-cw" color="light" class="is-fullwidth" :disabled="!hasActiveFilters"
          @click="clearFilters">
          Reset Filter
        </VButton>
      </div>
    </div>

    <div class="columns is-multiline">

      <div class="column is-12-mobile is-8-desktop">

        <div v-if="loading" class="columns is-multiline">
          <div v-for="i in 3" :key="i" class="column is-12-mobile is-6-tablet is-6-desktop">
            <div class="skeleton-card"></div>
          </div>
        </div>

        <div v-else-if="filteredItems.length === 0" class="has-text-centered py-6 card">
          <p class="has-text-grey mt-3">Tidak ada alat yang sesuai filter.</p>
        </div>

        <div v-else class="columns is-multiline">
          <div v-for="item in filteredItems" :key="item.norec" class="column is-12-mobile is-6-tablet is-6-desktop">
            <div class="card tool-card" :style="item.jenissurkesfk != 1 ? 'background: #f7e486;' : ''">
              <div class="card-content">

                <div class="mb-4">
                  <div class="is-flex is-justify-content-space-between is-align-items-start">
                    <div class="tags are-small mb-0">
                      <span :class="['tag', item.isRepair ? 'is-warning' : 'is-info']">
                        {{ item.isRepair ? 'REPAIR' : 'KALIBRASI' }}
                      </span>
                      <span class="tag is-light is-primary">
                        {{ item.lingkup }}
                      </span>
                      <span :class="['tag', item.jenissurkesfk == 1 ? 'is-success' : 'is-warning']">
                        {{ item.jenissurkesfk == 1 ? 'SURKES' : 'NON SURKES' }}
                      </span>
                      <span :class="[
                        'tag pickup-status-tag',
                        toBool(item.isterima) ? 'is-picked-up' : 'is-waiting-pickup',
                      ]">
                        <i class="iconify mr-1"
                          :data-icon="toBool(item.isterima) ? 'feather:check-circle' : 'feather:clock'"></i>
                        {{ toBool(item.isterima) ? 'SUDAH DIAMBIL' : 'BELUM DIAMBIL' }}
                      </span>
                    </div>
                    <span class="has-text-grey is-size-7 is-flex is-align-items-center ml-2">
                      <i class="iconify mr-1" data-icon="feather:calendar"></i>
                      {{ item.tglMasuk }}
                    </span>
                  </div>
                </div>

                <div class="tool-info mb-5">
                  <h3 class="title is-5 mb-3 tool-name" :title="item.namaproduk">
                    {{ item.namaproduk }}
                  </h3>

                  <div class="metadata-rows mb-4">
                    <div class="metadata-row mb-1">
                      <span class="meta-label">No Pendaftaran</span>
                      <div class="meta-value-group">
                        <span class="meta-value">{{ item.nopendaftaran }}</span>
                        <button class="copy-icon-btn" @click.stop="copyToClipboard(item.nopendaftaran)" title="Salin">
                          <i class="iconify" data-icon="feather:copy"></i>
                        </button>
                      </div>
                    </div>
                    <div class="metadata-row">
                      <span class="meta-label">No Order Alat</span>
                      <div class="meta-value-group">
                        <span class="meta-value">{{ item.noorderalat }}</span>
                        <button class="copy-icon-btn" @click.stop="copyToClipboard(item.noorderalat)" title="Salin">
                          <i class="iconify" data-icon="feather:copy"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="columns is-mobile is-gapless is-multiline mb-2 info-grid">
                    <div class="column is-6 pr-2">
                      <div class="info-box">
                        <span class="label-tiny">S/N</span>
                        <span class="value-text has-text-weight-bold">{{ item.namaserialnumber }}</span>
                      </div>
                    </div>
                    <div class="column is-6 pl-2">
                      <div class="info-box">
                        <span class="label-tiny">Merk/Tipe</span>
                        <span class="value-text">{{ item.namamerk }} / {{ item.namatipe }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="personnel-section mt-3 pt-3 border-top-dashed">
                    <div class="columns is-mobile is-variable is-1">
                      <div class="column is-6">
                        <p class="label-tiny mb-1">Pelaksana</p>
                        <div class="is-flex is-align-items-center">
                          <div class="icon-circle is-small is-info-light mr-2">
                            <i class="iconify" data-icon="feather:user"></i>
                          </div>
                          <span class="is-size-7 has-text-dark text-truncate" :title="item.pelaksanateknik">
                            {{ item.pelaksanateknik || '-' }}
                          </span>
                        </div>
                      </div>
                      <div class="column is-6">
                        <p class="label-tiny mb-1">Penyelia</p>
                        <div class="is-flex is-align-items-center">
                          <div class="icon-circle is-small is-warning-light mr-2">
                            <i class="iconify" data-icon="feather:check-circle"></i>
                          </div>
                          <span class="is-size-7 has-text-dark text-truncate" :title="item.penyeliateknik">
                            {{ item.penyeliateknik || '-' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="progress-section mb-5">
                  <div class="is-flex is-justify-content-space-between mb-2">
                    <small class="has-text-grey has-text-weight-medium">Progress Pengerjaan</small>
                    <strong :class="getProgressColorText(item.progress)">{{ item.progress }}%</strong>
                  </div>
                  <ProgressBar :value="item.progress" :showValue="false" :class="getProgressClass(item.progress)"
                    style="height: 10px" />
                </div>

                <div class="latest-status-box mb-4">
                  <div class="icon-text is-align-items-start">
                    <span class="icon has-text-info mr-2">
                      <i class="iconify" data-icon="feather:activity"></i>
                    </span>
                    <div class="status-text">
                      <span class="is-size-7 has-text-grey-light is-block lh-1 mb-1">Status Terkini</span>
                      <span class="is-size-7 has-text-weight-semibold has-text-dark is-block line-height-normal">
                        {{ item.latestStatus || 'Menunggu Proses' }}
                      </span>
                    </div>
                  </div>
                </div>

                <div
                  class="has-text-right pt-2 border-top-dashed is-flex is-justify-content-flex-end is-align-items-center">

                  <a class="action-link mr-4" @click="openDetailProgress(item)">
                    Riwayat
                    <i class="iconify ml-1" data-icon="feather:list"></i>
                  </a>

                  <a class="action-link mr-4" @click="openNotesModal(item)">
                    Catatan
                    <div class="icon-with-badge ml-1">
                      <i class="iconify" data-icon="feather:message-square"></i>
                      <span v-if="item.total_notes && item.total_notes > 0" class="badge-bubble bounce-in">
                        {{ item.total_notes > 9 ? '9+' : item.total_notes }}
                      </span>
                    </div>
                  </a>

                  <a class="action-link" @click="toggleTimeline(item)">
                    {{ item.showTimeline ? 'Tutup' : 'Ringkasan' }}
                    <i :class="['iconify transition-icon ml-1', item.showTimeline ? 'rotate-180' : '']"
                      data-icon="feather:chevron-down"></i>
                  </a>
                </div>

                <div v-if="item.showTimeline" class="simple-timeline-container mt-4 pt-3 fade-in">
                  <div v-for="(log, idx) in item.timeline" :key="idx" class="simple-timeline-item">
                    <div class="simple-timeline-marker"></div>
                    <div class="simple-timeline-content">
                      <p class="heading mb-1">{{ log.date }}</p>
                      <p class="title is-size-7 mb-1">{{ log.type }}</p>
                      <p class="mt-2 subtitle is-size-7 has-text-grey">
                        <i class="iconify is-size-7 mr-1" data-icon="feather:user"></i>
                        {{ log.nama }}
                      </p>
                    </div>
                  </div>
                  <div v-if="item.timeline.length === 0" class="has-text-centered is-size-7 has-text-grey py-2">
                    Belum ada riwayat aktivitas.
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="column is-12-mobile is-4-desktop">
        <div class="sticky-column">

          <div class="card mb-5">
            <header class="card-header">
              <p class="card-header-title">
                <span class="icon is-small mr-2 text-primary">
                  <i class="iconify" data-icon="feather:pie-chart"></i>
                </span>
                Distribusi Lingkup
              </p>
            </header>
            <div class="card-content">
              <div v-if="loading" class="has-text-centered py-6">
                <i class="iconify fa-spin is-size-4" data-icon="feather:loader"></i>
              </div>
              <div v-else class="chart-wrapper">
                <VueApexCharts v-if="chartSeries[0].data.length > 0" type="bar" height="280" :options="chartOptions"
                  :series="chartSeries" />
                <div v-else class="has-text-centered py-4 has-text-grey">
                  Data tidak tersedia.
                </div>
              </div>
            </div>
          </div>

          <div class="card mb-5">
            <header class="card-header">
              <p class="card-header-title">
                <span class="icon is-small mr-2 text-info">
                  <i class="iconify" data-icon="feather:users"></i>
                </span>
                Sebaran Pelaksana
              </p>
            </header>
            <div class="card-content">
              <div v-if="loading" class="has-text-centered py-6">
                <i class="iconify fa-spin is-size-4" data-icon="feather:loader"></i>
              </div>
              <div v-else class="chart-wrapper">
                <VueApexCharts v-if="chartSeriesPelaksana[0].data.length > 0" type="bar" height="350"
                  :options="chartOptionsPelaksana" :series="chartSeriesPelaksana" />
                <div v-else class="has-text-centered py-4 has-text-grey">
                  Data tidak tersedia.
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <header class="card-header">
              <p class="card-header-title">
                <span class="icon is-small mr-2 text-success">
                  <i class="iconify" data-icon="feather:percent"></i>
                </span>
                Status Persentase
              </p>
            </header>
            <div class="card-content">
              <div v-if="loading" class="has-text-centered py-6">
                <i class="iconify fa-spin is-size-4" data-icon="feather:loader"></i>
              </div>
              <div v-else class="chart-wrapper">
                <VueApexCharts v-if="chartSeriesProgress.length > 0" type="donut" height="320"
                  :options="chartOptionsProgress" :series="chartSeriesProgress" />
                <div v-else class="has-text-centered py-4 has-text-grey">
                  Data tidak tersedia.
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>

    <VModal :title="detailDrilldownTitle" :open="detailDrilldownOpen" size="big" actions="right"
      @close="closeDetailSummary">
      <template #content>
        <div class="detail-drilldown-summary">
          <div>
            <span>Data ditemukan</span>
            <strong>{{ formatNumber(filteredDetailDrilldownRows.length) }} alat</strong>
          </div>
          <div>
            <span>Unit dan tahun</span>
            <strong>{{ unitName }} · {{ TAHUN }}</strong>
          </div>
          <VField class="detail-drilldown-search">
            <VControl icon="feather:search">
              <VInput v-model="detailDrilldownKeyword"
                placeholder="Cari order, pendaftaran, alat, SN, atau status..." />
            </VControl>
          </VField>
        </div>

        <p class="detail-drilldown-subtitle">{{ detailDrilldownSubtitle }}</p>

        <DataTable :value="filteredDetailDrilldownRows" :paginator="true" :rows="8"
          :rowsPerPageOptions="[8, 15, 30, 50]" class="p-datatable-sm detail-drilldown-table"
          responsiveLayout="scroll" stripedRows
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Menampilkan {first} - {last} dari {totalRecords}">
          <Column field="nopendaftaran" header="No. Pendaftaran" :sortable="true" style="min-width: 150px">
            <template #body="{ data: row }">
              <strong>{{ row.nopendaftaran || '-' }}</strong>
              <small class="detail-table-subtext">{{ row.tglMasuk || '-' }}</small>
            </template>
          </Column>
          <Column field="noorderalat" header="No. Order Alat" :sortable="true" style="min-width: 155px" />
          <Column field="namaproduk" header="Alat" :sortable="true" style="min-width: 230px">
            <template #body="{ data: row }">
              <span class="detail-table-main">{{ row.namaproduk || '-' }}</span>
              <small class="detail-table-subtext">SN: {{ row.namaserialnumber || '-' }}</small>
            </template>
          </Column>
          <Column field="namaperusahaan" header="Unit Pemilik" :sortable="true" style="min-width: 230px" />
          <Column field="lingkup" header="Lingkup" :sortable="true" style="min-width: 130px" />
          <Column header="Surkes" style="min-width: 115px">
            <template #body="{ data: row }">
              <span :class="['tag', Number(row.jenissurkesfk) === 1 ? 'is-success is-light' : 'is-warning is-light']">
                {{ Number(row.jenissurkesfk) === 1 ? 'SURKES' : 'NON SURKES' }}
              </span>
            </template>
          </Column>
          <Column field="progress" header="Pengerjaan" :sortable="true" style="min-width: 135px">
            <template #body="{ data: row }">
              <span :class="['tag', Number(row.progress) === 100 ? 'is-success is-light' : 'is-warning is-light']">
                {{ Number(row.progress) === 100 ? 'Selesai' : `Proses ${row.progress || 0}%` }}
              </span>
            </template>
          </Column>
          <Column header="Pengambilan" style="min-width: 135px">
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
        <VButton color="dark" outlined @click="closeDetailSummary">Tutup</VButton>
      </template>
    </VModal>

    <VModal :open="modalDetailOpen" title="" noclose size="big" actions="right" @close="closeDetailModal"
      cancelLabel="Tutup">
      <template #content>

        <div v-if="isLoadDataDetail" class="p-6">
          <div v-for="n in 3" :key="n" class="mb-4">
            <div class="columns is-vcentered">
              <div class="column is-2">
                <div class="skeleton-line" style="height:40px; width:40px; border-radius:50%"></div>
              </div>
              <div class="column">
                <div class="skeleton-line" style="height:20px; width:80%"></div>
              </div>
            </div>
          </div>
        </div>

        <div v-else>
          <div class="business-dashboard hr-dashboard">

            <div class="column is-12">
              <div class="block-header is-align-items-start">

                <div class="header-img-container mr-4">
                  <figure class="image is-96x96">
                    <img :src="detailAlat.fotoproduk
                      ? '/produk/' + detailAlat.fotoproduk
                      : '/images/other/no_image.jpg'
                      " :data-fallback="'/images/other/no_image.jpg'" class="header-thumb" />
                  </figure>
                </div>

                <div class="left flex-grow-1">
                  <div class="current-user" style="text-align: left;">
                    <h3 class="title is-5 mb-1">{{ detailAlat.namaproduk }}</h3>
                    <div class="columns mt-1 is-gapless">
                      <div class="column is-narrow mr-5">
                        <p class="heading is-size-7 has-text-grey mb-0">Merk/Tipe</p>
                        <p class="has-text-weight-medium is-size-7 has-text-dark">
                          {{ detailAlat.namamerk }} / {{ detailAlat.namatipe }}
                        </p>
                      </div>
                      <div class="column is-narrow">
                        <p class="heading is-size-7 has-text-grey mb-0">S/N</p>
                        <p class="has-text-weight-medium is-size-7 has-text-dark">
                          {{ detailAlat.namaserialnumber }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="right has-text-centered ml-4 border-left pl-4" style="min-width: 120px;">
                  <QrcodeVue :value="getQRCodeValue" :size="90" render-as="svg" />
                  <br />
                  <VIconButton v-tooltip.bottom="'Cetak Barcode'" icon="feather:printer" color="warning" raised circle
                    class="mt-2 is-small" @click="cetakQRAlat" />
                </div>

              </div>
            </div>

            <div class="column is-12 mt-2">
              <div class="timeline-wrapper">
                <div class="timeline-wrapper-inner">
                  <div class="timeline-container">

                    <div v-if="detailTimeline.length === 0" class="has-text-centered py-4">
                      <p class="has-text-grey">Belum ada riwayat pengerjaan.</p>
                    </div>

                    <div class="timeline-item is-unread" v-for="(log, index) in detailTimeline" :key="index">
                      <div class="date">
                        <span>{{ log.date_indo }}</span>
                      </div>

                      <div :class="['dot', index === 0 ? 'is-success' : 'is-info']"></div>

                      <div class="content-wrap is-grey">
                        <div class="content-box">
                          <div class="box-text" style="width:100%">
                            <div class="meta-text">
                              <table class="tb-order mt-2">
                                <tr>
                                  <td>No Pendaftaran</td>
                                  <td>:</td>
                                  <td>
                                    {{ log.nopendaftaran ?? '-' }}
                                    <button @click="copyToClipboard(log.nopendaftaran)" v-if="log.nopendaftaran"
                                      class="copy-button">
                                      <i class="iconify" data-icon="feather:copy"
                                        style="font-size: 12px; margin-left: 4px;"></i>
                                    </button>
                                  </td>
                                </tr>
                                <tr>
                                  <td>No Noorder Alat</td>
                                  <td>:</td>
                                  <td>
                                    {{ log.noorderalat ?? '-' }}
                                    <button @click="copyToClipboard(log.noorderalat)" v-if="log.noorderalat"
                                      class="copy-button">
                                      <i class="iconify" data-icon="feather:copy"
                                        style="font-size: 12px; margin-left: 4px;"></i>
                                    </button>
                                  </td>
                                </tr>
                                <tr>
                                  <td>Lingkup</td>
                                  <td>:</td>
                                  <td>{{ log.lingkupkalibrasi ?? '-' }} </td>
                                </tr>
                                <tr v-if="log.jenisorder == 'kalibrasi'">
                                  <td>Lokasi</td>
                                  <td>:</td>
                                  <td>{{ log.lokasi ?? '-' }} </td>
                                </tr>
                                <tr v-if="log.jenisorder == 'repair'">
                                  <td>Lokasi Repair</td>
                                  <td>:</td>
                                  <td>{{ log.lokasirepair ?? '-' }} </td>
                                </tr>
                                <tr v-if="log.jenisorder == 'kalibrasi' && log.isVendor == null">
                                  <td>Durasi</td>
                                  <td>:</td>
                                  <td>
                                    <VTag color="warning" rounded>
                                      {{ log.durasikalbrasi }}
                                    </VTag>
                                  </td>
                                </tr>
                                <tr v-if="
                                  log.jenisorder == 'kalibrasi' &&
                                  log.tglsetujumanagerlembarkerja &&
                                  log.isVendor == null
                                ">
                                  <td>Durasi Penyelesaian</td>
                                  <td>:</td>
                                  <td>
                                    <VTag v-if="log.durasikalbrasi" color="info" rounded>
                                      {{ log.durasi_proses }}
                                    </VTag>
                                  </td>
                                </tr>
                                <tr v-if="log.bintangpenilaian != null">
                                  <td>Penilaian Pelanggan</td>
                                  <td>:</td>
                                  <td>
                                    <span v-for="n in 5" :key="'star-' + n">
                                      <i class="fa" :class="n <= log.bintangpenilaian
                                        ? 'fa-star has-text-warning'
                                        : 'fa-star has-text-grey-light'
                                        "></i>
                                    </span>
                                    <span class="has-text-grey ml-2">
                                      ({{ log.bintangpenilaian }} dari 5)
                                    </span>
                                  </td>
                                </tr>
                              </table>

                              <div class="buttons are-small mt-3">
                                <VButton v-if="
                                  log.jenisorder == 'kalibrasi' &&
                                  log.tglsetujumanagerlembarkerja != null &&
                                  log.isverifikasi == null &&
                                  log.isVendor == true
                                " icon="feather:printer" color="info" outlined @click="cetakSertiVendor(log)">
                                  Sertifikat Vendor
                                </VButton>

                                <VButton v-if="
                                  log.jenisorder == 'kalibrasi' &&
                                  log.tglsetujumanagerlembarkerja != null &&
                                  log.isverifikasi == null &&
                                  log.isVendor == null
                                " icon="feather:printer" color="info" outlined
                                  @click="cetakSertifikatLembarKerja(log)">
                                  Cetak Sertifikat
                                </VButton>

                                <VButton v-if="
                                  log.jenisorder == 'kalibrasi' &&
                                  log.tglsetujumanagerlembarkerja != null &&
                                  log.isverifikasi == true
                                " icon="feather:printer" color="success" outlined @click="cetakLaporanVerfikasi(log)">
                                  Laporan Verifikasi
                                </VButton>

                                <VButton v-if="
                                  log.jenisorder == 'repair' &&
                                  log.tglsetujumanagerlaporanrepair != null &&
                                  log.isVendor == null
                                " icon="feather:printer" color="warning" outlined @click="cetakLaporanRepair(log)">
                                  Laporan Repair
                                </VButton>

                                <VButton v-if="
                                  log.jenisorder == 'repair' &&
                                  log.tglsetujumanagerlaporanrepair != null &&
                                  log.isVendor == true
                                " icon="feather:printer" color="warning" outlined @click="cetakSertiVendor(log)">
                                  Laporan Repair
                                </VButton>
                              </div>

                            </div>
                          </div>
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
    </VModal>

    <VModal :open="modalNotesOpen" title="" noclose size="medium" actions="right" @close="closeNotesModal"
      cancelLabel="Tutup">
      <template #content>
        <header class="modal-card-head" style="margin: -24px -24px 20px -24px; border-radius: 0;">
          <p class="modal-card-title">Catatan: {{ detailAlat.namaproduk }}</p>
          <button class="delete" @click="closeNotesModal"></button>
        </header>

        <div class="field mb-4">
          <label class="label">
            {{ editingNoteNorec ? 'Edit Catatan' : 'Catatan Baru' }}
          </label>
          <div class="control">
            <textarea class="textarea" rows="3" placeholder="Tulis catatan di sini..." v-model="newNote"></textarea>
          </div>
        </div>
        <div class="field has-text-right mb-5">
          <VButton v-if="editingNoteNorec" color="light" class="mr-2" @click="cancelEditDetailNote">Batal</VButton>
          <VButton color="primary" :loading="sendingNote" @click="saveNote">
            {{ editingNoteNorec ? 'Update' : 'Kirim Catatan' }}
          </VButton>
        </div>

        <hr class="dropdown-divider mb-4">
        <h4 class="title is-6 mb-3">Riwayat Catatan</h4>
        <div v-if="isLoadingNotes" class="has-text-centered py-4"><i class="iconify fa-spin"
            data-icon="feather:loader"></i></div>
        <div v-else-if="unitNotes.length === 0" class="has-text-centered has-text-grey py-4">
          <p>Belum ada catatan.</p>
        </div>
        <div v-else class="detail-notes-list">
          <div v-for="note in unitNotes" :key="note.norec" class="note-card mb-3">
            <div class="is-flex is-justify-content-space-between mb-1">
              <strong class="has-text-dark is-size-7">{{ note.nama_petugas }}</strong>

              <small class="has-text-grey is-size-7 has-text-right">
                {{ note.formatted_date }}
                <span v-if="isEdited(note)" class="has-text-grey-light is-italic ml-1">
                  (Diedit: {{ formatDate(note.updated_at) }})
                </span>
              </small>

            </div>
            <p class="has-text-grey-dark is-size-7" style="white-space: pre-wrap;">{{ note.notes }}</p>

            <div class="is-flex mt-2">
              <a class="has-text-info is-clickable mr-3" @click="editDetailNote(note)" title="Edit">
                <i class="iconify" data-icon="feather:edit-2"></i>
              </a>
              <a class="has-text-danger is-clickable" @click="deleteDetailNote(note.norec)" title="Hapus">
                <i class="iconify" data-icon="feather:trash-2"></i>
              </a>
            </div>

          </div>
        </div>
      </template>
    </VModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProgressBar from 'primevue/progressbar'
import MultiSelect from 'primevue/multiselect'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import QrcodeVue from 'qrcode.vue'
import VueApexCharts from 'vue3-apexcharts'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { useApi } from '/@src/composable/useApi'
import { useToaster } from '/@src/composable/toaster'
import * as H from '/@src/utils/appHelper'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'

useHead({
  title: 'Detail Synergy - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)


const route = useRoute()
const router = useRouter()
const ID_UNIT = route.query.id_unit as string
const TAHUN = route.query.year as string
const loading = ref(false)
const exportingExcel = ref(false)
const exportingPdf = ref(false)
const unitName = ref('Detail Unit')
const items = ref<any[]>([])
const search = ref('')
const filterStatus = ref('all')
const filterType = ref('all')
const filterNotes = ref('all')
const filterSurkes = ref('all')
const sortTglMasuk = ref<'none' | 'oldest' | 'newest'>('none')
const filterTanggalMasuk = ref('')
const filterScope = ref<string[]>([])
const filterPelaksana = ref<string[]>([])
const filterPenyelia = ref<string[]>([])

type DetailSummaryKey = 'total' | 'surkes' | 'non_surkes' | 'selesai' | 'belum' | 'diambil' | 'belum_diambil'

type DetailSummaryCard = {
  key: DetailSummaryKey
  label: string
  value: number
  caption: string
  icon: string
  tone: string
}

const detailDrilldownOpen = ref(false)
const detailDrilldownTitle = ref('')
const detailDrilldownSubtitle = ref('')
const detailDrilldownRows = ref<any[]>([])
const detailDrilldownKeyword = ref('')

const modalDetailOpen = ref(false)
const isLoadDataDetail = ref(false)
const isOwner = ref(true)
const detailAlat = ref<any>({})
const detailTimeline = ref<any[]>([])
const currentDetailId = ref('')
const currentDetailNorec = ref('')

// State untuk Modal Catatan Per Alat
const modalNotesOpen = ref(false)
const unitNotes = ref<any[]>([])
const newNote = ref('')
const sendingNote = ref(false)
const isLoadingNotes = ref(false)
const editingNoteNorec = ref<string | null>(null)

// --- NEW STATE: Catatan Per UNIT (Dashboard atas) ---
const dashUnitNotes = ref<any[]>([])
const newDashUnitNote = ref('')
const loadingDashUnitNotes = ref(false)
const sendingDashUnitNote = ref(false)
const editingDashUnitNorec = ref<string | null>(null)

function toBool(value: unknown) {
  if (typeof value === 'boolean') return value
  if (typeof value === 'number') return value === 1
  return ['1', 'true', 't', 'yes', 'y'].includes(String(value ?? '').trim().toLowerCase())
}

function formatNumber(value: unknown) {
  return new Intl.NumberFormat('id-ID').format(Number(value || 0))
}

const detailSummaryCards = computed<DetailSummaryCard[]>(() => {
  const rows = items.value
  const count = (predicate: (row: any) => boolean) => rows.filter(predicate).length

  return [
    {
      key: 'total',
      label: 'Total Order Alat',
      value: rows.length,
      caption: 'Seluruh order pada unit dan tahun aktif',
      icon: 'feather:package',
      tone: 'navy',
    },
    {
      key: 'surkes',
      label: 'SURKES',
      value: count((row) => Number(row.jenissurkesfk) === 1),
      caption: 'Order dengan kategori SURKES',
      icon: 'feather:shield',
      tone: 'teal',
    },
    {
      key: 'non_surkes',
      label: 'Non SURKES',
      value: count((row) => Number(row.jenissurkesfk) !== 1),
      caption: 'Order di luar kategori SURKES',
      icon: 'feather:file-minus',
      tone: 'purple',
    },
    {
      key: 'selesai',
      label: 'Selesai',
      value: count((row) => Number(row.progress) === 100),
      caption: 'Pengerjaan sudah 100%',
      icon: 'feather:check-circle',
      tone: 'green',
    },
    {
      key: 'belum',
      label: 'Belum Selesai',
      value: count((row) => Number(row.progress) < 100),
      caption: 'Pengerjaan masih berjalan',
      icon: 'feather:clock',
      tone: 'amber',
    },
    {
      key: 'diambil',
      label: 'Sudah Diambil',
      value: count((row) => toBool(row.isterima)),
      caption: 'Sudah masuk tanda selesai terima',
      icon: 'feather:log-out',
      tone: 'blue',
    },
    {
      key: 'belum_diambil',
      label: 'Belum Diambil',
      value: count((row) => !toBool(row.isterima)),
      caption: 'Belum masuk tanda selesai terima',
      icon: 'feather:archive',
      tone: 'red',
    },
  ]
})

const filteredDetailDrilldownRows = computed(() => {
  const keyword = detailDrilldownKeyword.value.trim().toLowerCase()
  if (!keyword) return detailDrilldownRows.value

  return detailDrilldownRows.value.filter((row) => [
    row.nopendaftaran,
    row.noorderalat,
    row.namaproduk,
    row.namaserialnumber,
    row.namaperusahaan,
    row.lingkup,
    row.latestStatus,
  ].some((value) => String(value || '').toLowerCase().includes(keyword)))
})

function openDetailSummary(card: DetailSummaryCard) {
  const filters: Record<DetailSummaryKey, (row: any) => boolean> = {
    total: () => true,
    surkes: (row) => Number(row.jenissurkesfk) === 1,
    non_surkes: (row) => Number(row.jenissurkesfk) !== 1,
    selesai: (row) => Number(row.progress) === 100,
    belum: (row) => Number(row.progress) < 100,
    diambil: (row) => toBool(row.isterima),
    belum_diambil: (row) => !toBool(row.isterima),
  }

  detailDrilldownRows.value = items.value.filter(filters[card.key])
  detailDrilldownKeyword.value = ''
  detailDrilldownTitle.value = `${card.label} · ${unitName.value}`
  detailDrilldownSubtitle.value = `${formatNumber(detailDrilldownRows.value.length)} alat pembentuk statistik ${card.label.toLowerCase()}, lengkap dengan nomor order dan status pengambilannya.`
  detailDrilldownOpen.value = true
}

function closeDetailSummary() {
  detailDrilldownOpen.value = false
  detailDrilldownRows.value = []
  detailDrilldownKeyword.value = ''
}

const availableScopes = computed(() => {
  const scopes = new Set(items.value.map(i => i.lingkup).filter(Boolean))
  return Array.from(scopes)
})

const availablePelaksana = computed(() => {
  const list = new Set(items.value.map(i => i.pelaksanateknik).filter(Boolean))
  return Array.from(list).sort()
})

const availablePenyelia = computed(() => {
  const list = new Set(items.value.map(i => i.penyeliateknik).filter(Boolean))
  return Array.from(list).sort()
})

const hasActiveFilters = computed(() => {
  return (
    search.value !== '' ||
    filterStatus.value !== 'all' ||
    filterType.value !== 'all' ||
    filterNotes.value !== 'all' ||
    filterSurkes.value !== 'all' ||
    sortTglMasuk.value !== 'none' ||
    filterTanggalMasuk.value !== '' ||
    filterScope.value.length > 0 ||
    filterPelaksana.value.length > 0 ||
    filterPenyelia.value.length > 0
  )
})

function clearFilters() {
  search.value = ''
  filterStatus.value = 'all'
  filterType.value = 'all'
  filterNotes.value = 'all'
  filterSurkes.value = 'all'
  sortTglMasuk.value = 'none'
  filterTanggalMasuk.value = ''
  filterScope.value = []
  filterPelaksana.value = []
  filterPenyelia.value = []
}

const filteredItems = computed(() => {
  const filtered = items.value.filter(item => {
    const searchMatch =
      (item.namaproduk || '').toLowerCase().includes(search.value.toLowerCase()) ||
      (item.namaserialnumber || '').toLowerCase().includes(search.value.toLowerCase())

    let statusMatch = true
    if (filterStatus.value === 'selesai') statusMatch = item.progress === 100
    if (filterStatus.value === 'proses') statusMatch = item.progress < 100

    let typeMatch = true
    if (filterType.value === 'repair') typeMatch = item.isRepair
    if (filterType.value === 'kalibrasi') typeMatch = !item.isRepair

    let notesMatch = true
    if (filterNotes.value === 'with_notes') {
      notesMatch = (item.total_notes && item.total_notes > 0)
    } else if (filterNotes.value === 'no_notes') {
      notesMatch = (!item.total_notes || item.total_notes === 0)
    }

    let surkesMatch = true
    if (filterSurkes.value === 'surkes') {
      surkesMatch = Number(item.jenissurkesfk) === 1
    } else if (filterSurkes.value === 'non_surkes') {
      surkesMatch = Number(item.jenissurkesfk) !== 1
    }

    const tglMasukDate = parseTglMasuk(item.tglMasuk)
    const tglMasukMatch = filterTanggalMasuk.value
      ? tglMasukDate !== null && toDateKey(tglMasukDate) === filterTanggalMasuk.value
      : true

    let scopeMatch = true
    if (filterScope.value.length > 0) {
      scopeMatch = filterScope.value.includes(item.lingkup)
    }

    let pelaksanaMatch = true
    if (filterPelaksana.value.length > 0) {
      pelaksanaMatch = filterPelaksana.value.includes(item.pelaksanateknik)
    }

    let penyeliaMatch = true
    if (filterPenyelia.value.length > 0) {
      penyeliaMatch = filterPenyelia.value.includes(item.penyeliateknik)
    }

    return searchMatch && statusMatch && typeMatch && notesMatch && surkesMatch && tglMasukMatch && scopeMatch && pelaksanaMatch && penyeliaMatch
  })

  if (sortTglMasuk.value === 'none') {
    return filtered
  }

  return filtered.sort((a, b) => {
    const aDate = parseTglMasuk(a.tglMasuk)
    const bDate = parseTglMasuk(b.tglMasuk)
    const aTime = aDate?.getTime() ?? null
    const bTime = bDate?.getTime() ?? null

    if (aTime === null && bTime === null) return 0
    if (aTime === null) return 1
    if (bTime === null) return -1

    return sortTglMasuk.value === 'oldest'
      ? aTime - bTime
      : bTime - aTime
  })
})

/* --- HELPER DATES --- */
function parseTglMasuk(value: any): Date | null {
  if (!value) return null
  if (value instanceof Date && !Number.isNaN(value.getTime())) return value

  const rawValue = String(value).trim()
  const isoMatch = rawValue.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/)
  if (isoMatch) {
    return new Date(Number(isoMatch[1]), Number(isoMatch[2]) - 1, Number(isoMatch[3]))
  }

  const dateParts = rawValue.match(/^(\d{1,2})\s+([A-Za-z.]+)\s+(\d{4})/)
  if (dateParts) {
    const monthMap: Record<string, number> = {
      jan: 0, januari: 0,
      feb: 1, februari: 1,
      mar: 2, maret: 2,
      apr: 3, april: 3,
      may: 4, mei: 4,
      jun: 5, juni: 5,
      jul: 6, juli: 6,
      aug: 7, agu: 7, agustus: 7,
      sep: 8, sept: 8, september: 8,
      oct: 9, okt: 9, oktober: 9,
      nov: 10, november: 10,
      dec: 11, des: 11, desember: 11,
    }
    const month = monthMap[dateParts[2].toLowerCase().replace('.', '')]
    if (month !== undefined) {
      return new Date(Number(dateParts[3]), month, Number(dateParts[1]))
    }
  }

  const parsed = new Date(rawValue)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

function toDateKey(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

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

/* --- LOGIC CATATAN UNIT (DASHBOARD) --- */
async function loadDashUnitNotes() {
  loadingDashUnitNotes.value = true
  try {
    const res = await useApi().get(`laporan/get-notes-unit?unitfk=${ID_UNIT}&tahun=${TAHUN}`)
    dashUnitNotes.value = Array.isArray(res) ? res : (res || [])
  } catch (err) {
    console.error("Gagal load unit notes", err)
  } finally {
    loadingDashUnitNotes.value = false
  }
}

function editDashUnitNote(note: any) {
  newDashUnitNote.value = note.note || note.notes
  editingDashUnitNorec.value = note.norec
}

function cancelEditDashUnitNote() {
  newDashUnitNote.value = ''
  editingDashUnitNorec.value = null
}

async function saveDashUnitNote() {
  if (!newDashUnitNote.value.trim()) {
    H.alert('warning', 'Catatan tidak boleh kosong')
    return
  }
  sendingDashUnitNote.value = true
  try {
    const payload: any = {
      unitfk: ID_UNIT,
      notes: newDashUnitNote.value,
      year: TAHUN
    }
    if (editingDashUnitNorec.value) {
      payload.norec = editingDashUnitNorec.value
    }

    await useApi().post('laporan/save-notes-per-unit', payload)

    cancelEditDashUnitNote()
    await loadDashUnitNotes()
  } catch (err) {
    H.alert('error', 'Gagal simpan catatan unit')
  } finally {
    sendingDashUnitNote.value = false
  }
}

async function deleteDashUnitNote(norec: string) {
  try {
    if (editingDashUnitNorec.value === norec) {
      cancelEditDashUnitNote()
    }
    await useApi().post('laporan/delete-unit-note', { norec })
    await loadDashUnitNotes()
  } catch (err) {
    H.alert('error', 'Gagal menghapus catatan')
  }
}

/* --- CHART LOGIC START --- */
const scopeStats = computed(() => {
  const counts: Record<string, number> = {}
  items.value.forEach(item => {
    const lingkup = item.lingkup || 'Lainnya'
    counts[lingkup] = (counts[lingkup] || 0) + 1
  })
  const sortedKeys = Object.keys(counts).sort()
  const data = sortedKeys.map(key => counts[key])
  return { categories: sortedKeys, data }
})

const chartSeries = computed(() => {
  return [{
    name: 'Jumlah Alat',
    data: scopeStats.value.data
  }]
})

const chartOptions = computed(() => {
  return {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: {
      bar: { borderRadius: 4, horizontal: false, columnWidth: '55%', distributed: true, dataLabels: { position: 'top' } }
    },
    dataLabels: { enabled: true, offsetY: -20, style: { fontSize: '12px', colors: ["#304758"] } },
    xaxis: { categories: scopeStats.value.categories, labels: { style: { fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { show: false },
    colors: ['#00cfdd', '#797bf2', '#f59e0b', '#10b981', '#ef4444', '#3b82f6'],
    legend: { show: false },
    grid: { borderColor: '#f1f1f1', padding: { top: 20 } },
    tooltip: { y: { formatter: (val: any) => val + " alat" } }
  }
})

const pelaksanaStats = computed(() => {
  const counts: Record<string, number> = {}
  items.value.forEach(item => {
    const p = item.pelaksanateknik || 'Belum Ditunjuk'
    counts[p] = (counts[p] || 0) + 1
  })
  const sortedKeys = Object.keys(counts).sort((a, b) => counts[b] - counts[a])
  const topKeys = sortedKeys.slice(0, 15)
  const data = topKeys.map(key => counts[key])
  return { categories: topKeys, data }
})

const chartSeriesPelaksana = computed(() => {
  return [{ name: 'Jumlah Alat', data: pelaksanaStats.value.data }]
})

const chartOptionsPelaksana = computed(() => {
  return {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: {
      bar: { borderRadius: 4, horizontal: true, barHeight: '70%', distributed: true, dataLabels: { position: 'bottom' } }
    },
    dataLabels: {
      enabled: true, textAnchor: 'start', style: { colors: ['#fff'], fontSize: '11px' },
      formatter: function (val: number, opt: any) { return opt.w.globals.labels[opt.dataPointIndex] + ":  " + val },
      offsetX: 0, dropShadow: { enabled: true }
    },
    xaxis: { categories: pelaksanaStats.value.categories, labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { show: false } },
    colors: ['#00cfdd', '#797bf2', '#f59e0b', '#10b981', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899'],
    legend: { show: false },
    grid: { borderColor: '#f1f1f1', xaxis: { lines: { show: false } }, yaxis: { lines: { show: false } }, padding: { left: 0, right: 0 } },
    tooltip: { y: { formatter: (val: any) => val + " alat" } }
  }
})

const progressStats = computed(() => {
  const counts: Record<number, number> = {}
  items.value.forEach(item => {
    const p = item.progress != null ? item.progress : 0
    counts[p] = (counts[p] || 0) + 1
  })
  const sortedKeys = Object.keys(counts).map(Number).sort((a, b) => b - a)
  const series = sortedKeys.map(key => counts[key])
  const labels = sortedKeys.map(key => `${key}%`)
  return { labels, series }
})

const chartSeriesProgress = computed(() => progressStats.value.series)

const chartOptionsProgress = computed(() => {
  return {
    chart: {
      type: 'donut',
      toolbar: { show: false }
    },
    labels: progressStats.value.labels,
    colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'],
    plotOptions: {
      pie: {
        donut: {
          size: '65%',
          labels: {
            show: true,
            name: {
              show: true,
              fontSize: '16px',
              fontWeight: 600
            },
            value: {
              show: true,
              fontSize: '16px',
              fontWeight: 700,
              formatter: function (val: any, opts: any) {
                return `${opts.w.config.series[opts.seriesIndex]} alat`
              }
            },
            total: {
              show: true,
              showAlways: true,
              label: 'Total Alat',
              color: '#373d3f',
              formatter: function (w: any) {
                return w.globals.seriesTotals.reduce((a: any, b: any) => a + b, 0)
              }
            }
          }
        }
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (_val: number, opts: any) {
        return `${opts.w.config.series[opts.seriesIndex]} alat`
      },
      style: {
        fontSize: '12px',
        fontWeight: 700,
        colors: ['#ffffff']
      },
      dropShadow: {
        enabled: false
      }
    },
    stroke: {
      width: 2,
      colors: ['#fff']
    },
    legend: {
      position: 'bottom',
      formatter: function (seriesName: string, opts: any) {
        const total = opts.w.globals.series[opts.seriesIndex]
        return `${seriesName} (${total} alat)`
      }
    },
    tooltip: {
      y: {
        formatter: (val: any) => val + ' alat'
      }
    }
  }
})
/* --- CHART LOGIC END --- */

const getQRCodeValue = computed(() => `https://ulabumro.id/module/customer/detail-alat?id_alat=${currentDetailId.value}`)

function goBack() { router.back() }
function toggleTimeline(item: any) { item.showTimeline = !item.showTimeline }
function getProgressColorText(val: number) { return val === 100 ? 'has-text-success' : (val > 50 ? 'has-text-info' : 'has-text-warning') }
function getProgressClass(val: number) { return val === 100 ? 'p-progressbar-success' : 'p-progressbar-info' }

async function loadDetail() {
  loading.value = true
  try {
    const res = await useApi().get(`laporan/get-unit-items?unit_id=${ID_UNIT}&year=${TAHUN}`)
    items.value = res || []
    if (items.value.length > 0) unitName.value = items.value[0].namaperusahaan
  } catch (err: any) { console.error(err) } finally { loading.value = false }
}


async function openDetailProgress(item: any) {
  modalDetailOpen.value = true
  isLoadDataDetail.value = true
  detailAlat.value = { ...item }
  currentDetailId.value = item.id_alat
  detailTimeline.value = []

  try {
    const res: any = await useApi().get(`/customer/history-alat?id_alat=${currentDetailId.value}`)

    isOwner.value = res?.is_owner ?? true
    if (!isOwner.value) { isLoadDataDetail.value = false; return }

    if (res.detail) {
      detailTimeline.value = res.detail.map((d: any) => ({
        ...d,
        date_indo: d.tglregistrasi ? new Date(d.tglregistrasi).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-',
      }))
    }

  } catch (err) { console.error(err) } finally { isLoadDataDetail.value = false }
}

function closeDetailModal() { modalDetailOpen.value = false }


async function openNotesModal(item: any) {
  modalNotesOpen.value = true
  detailAlat.value = { ...item }
  currentDetailNorec.value = item.norec
  unitNotes.value = []
  newNote.value = ''

  cancelEditDetailNote() // Reset state edit

  isLoadingNotes.value = true
  try {
    const res = await useApi().get(`laporan/get-notes-peralat?norecalatfk=${currentDetailNorec.value}`)
    unitNotes.value = res
  } catch (err) { console.error(err) } finally { isLoadingNotes.value = false }
}

function editDetailNote(note: any) {
  newNote.value = note.note || note.notes
  editingNoteNorec.value = note.norec
}

function cancelEditDetailNote() {
  newNote.value = ''
  editingNoteNorec.value = null
}

async function saveNote() {
  if (!newNote.value.trim()) {
    H.alert('warning', 'Catatan tidak boleh kosong')
    return
  }
  sendingNote.value = true
  try {
    const payload: any = {
      norecalatfk: currentDetailNorec.value,
      notes: newNote.value
    }
    // Jika edit mode
    if (editingNoteNorec.value) {
      payload.norec = editingNoteNorec.value
    }

    await useApi().post('laporan/save-notes-peralat', payload)

    // Reset form
    cancelEditDetailNote()

    // Reload
    const res = await useApi().get(`laporan/get-notes-peralat?norecalatfk=${currentDetailNorec.value}`)
    unitNotes.value = res

    // Increment counter if insert
    if (!payload.norec) {
      const targetItem = items.value.find(i => i.norec === currentDetailNorec.value)
      if (targetItem) {
        const currentCount = targetItem.total_notes || 0
        targetItem.total_notes = currentCount + 1
      }
    }

  } catch (err) {
    console.error(err)
  } finally {
    sendingNote.value = false
  }
}

function closeNotesModal() {
  modalNotesOpen.value = false
  cancelEditDetailNote()
}

async function deleteDetailNote(norec: string) {
  try {
    if (editingNoteNorec.value === norec) {
      cancelEditDetailNote()
    }

    await useApi().post('laporan/delete-detail-note', { norec })
    const res = await useApi().get(`laporan/get-notes-peralat?norecalatfk=${currentDetailNorec.value}`)
    unitNotes.value = res
    const targetItem = items.value.find(i => i.norec === currentDetailNorec.value)
    if (targetItem) {
      const currentCount = targetItem.total_notes || 0
      targetItem.total_notes = currentCount - 1
    }
  } catch (err) {
    H.alert('error', 'Gagal menghapus catatan')
  }
}

// --- HELPER UNTUK FETCH CATATAN ---
async function fetchNotesForExport(itemsToProcess: any[]) {
  const itemsWithNotes = itemsToProcess.filter(i => (i.total_notes || 0) > 0)
  const notesMap = new Map()

  if (itemsWithNotes.length > 0) {
    const promises = itemsWithNotes.map(async (i) => {
      try {
        const res = await useApi().get(`laporan/get-notes-peralat?norecalatfk=${i.norec}`)
        if (Array.isArray(res) && res.length > 0) {
          notesMap.set(i.norec, res)
        }
      } catch (e) {
        console.error(`Gagal ambil note alat ${i.namaproduk}`, e)
      }
    })
    await Promise.all(promises)
  }
  return notesMap
}

function formatNotes(item: any, notesMap: Map<any, any>) {
  if (notesMap.has(item.norec)) {
    const notesArr = notesMap.get(item.norec)
    return notesArr.map((n: any) => `[${n.formatted_date}] ${n.nama_petugas}: ${n.notes}`).join('\n')
  }
  return '-'
}

// --- EXPORT PDF FUNCTION ---
const exportPdf = async () => {
  exportingPdf.value = true
  try {
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
    const itemsToProcess = filteredItems.value
    const notesMap = await fetchNotesForExport(itemsToProcess)

    // Header Info
    doc.setFontSize(16)
    doc.text(`Laporan Detail Synergy - ${unitName.value}`, 14, 15)
    doc.setFontSize(10)
    doc.text(`Tahun: ${TAHUN} | Total Alat: ${items.value.length}`, 14, 21)
    doc.text(`Tanggal Export: ${new Date().toLocaleDateString('id-ID')}`, 14, 26)

    // Tabel Data Alat
    // Kolom bertambah satu: 'Status Surkes' di indeks ke-7
    const head = [['No', 'Nama Produk', 'Merk/Tipe', 'S/N', 'No Pend.', 'Lingkup', 'Status', 'Status Surkes', 'Pelaksana', 'Catatan']]

    const body = itemsToProcess.map((item, idx) => [
      idx + 1,
      item.namaproduk,
      `${item.namamerk} / ${item.namatipe}`,
      item.namaserialnumber,
      item.nopendaftaran,
      item.lingkup,
      `${item.progress}% (${item.latestStatus || '-'})`,
      item.jenissurkesfk == 1 ? 'SURKES' : 'NON SURKES', // Logic Surkes
      item.pelaksanateknik || '-',
      formatNotes(item, notesMap)
    ])

    autoTable(doc, {
      head: head,
      body: body,
      startY: 32,
      styles: { fontSize: 8 },
      headStyles: { fillColor: [66, 133, 244] }, // Google Blue
      columnStyles: {
        // Sesuaikan lebar kolom agar muat
        0: { cellWidth: 8 },  // No
        1: { cellWidth: 35 }, // Nama Produk
        // ... kolom lain auto
        7: { cellWidth: 20 }, // Status Surkes
        9: { cellWidth: 50 }  // Catatan (Indeks bergeser ke 9)
      },
      margin: { top: 30 }
    })

    // ===== TAMBAHAN: TABEL CATATAN UNIT (Baru) =====
    let finalY = (doc as any).lastAutoTable.finalY + 10

    // Jika ada catatan unit, tampilkan
    if (dashUnitNotes.value.length > 0) {
      if (finalY > 160) { // Cek halaman cukup
        doc.addPage()
        finalY = 20
      }

      doc.setFontSize(12)
      doc.text("Riwayat Catatan Unit", 14, finalY)

      const noteBody = dashUnitNotes.value.map(n => [
        n.formatted_date,
        n.nama_petugas,
        n.notes
      ])

      autoTable(doc, {
        head: [['Tanggal', 'Oleh', 'Isi Catatan']],
        body: noteBody,
        startY: finalY + 5,
        styles: { fontSize: 8 },
        headStyles: { fillColor: [241, 196, 15], textColor: [50, 50, 50] }, // Yellowish
        columnStyles: { 2: { cellWidth: 150 } }
      })

      // Update finalY lagi setelah tabel catatan
      finalY = (doc as any).lastAutoTable.finalY + 10
    }
    // ============================================

    // Tabel Statistik
    // Cek jika halaman tidak cukup
    if (finalY > 180) {
      doc.addPage()
      finalY = 20
    }

    doc.setFontSize(12)
    doc.text("Statistik Ringkas", 14, finalY)

    // Statistik Lingkup (Mini Table)
    const scopeBody = scopeStats.value.categories.map((c, i) => [c, scopeStats.value.data[i]])
    autoTable(doc, {
      head: [['Lingkup', 'Jml']],
      body: scopeBody,
      startY: finalY + 5,
      theme: 'grid',
      styles: { fontSize: 8 },
      margin: { left: 14 },
      tableWidth: 80
    })

    // Save
    const fileName = `Synergy_Detail_${unitName.value}_${TAHUN}.pdf`.replace(/\s+/g, '_')
    doc.save(fileName)

  } catch (err) {
    console.error('Export PDF Error', err)
    H.alert('error', 'Gagal export PDF')
  } finally {
    exportingPdf.value = false
  }
}

// --- EXPORT EXCEL FUNCTION ---
const exportExcel = async () => {
  exportingExcel.value = true
  try {
    const wb = XLSX.utils.book_new()
    const itemsToProcess = filteredItems.value
    const notesMap = await fetchNotesForExport(itemsToProcess)

    // Sheet 1: Data Alat
    const headerInfo = [
      ['Laporan Detail Synergy'],
      ['Unit', unitName.value],
      ['Tahun', TAHUN],
      ['Tanggal Export', new Date().toLocaleDateString('id-ID')],
      []
    ]

    const dataAlat = itemsToProcess.map((item, idx) => {
      return {
        'No': idx + 1,
        'Nama Produk': item.namaproduk,
        'Merk': item.namamerk,
        'Tipe': item.namatipe,
        'S/N': item.namaserialnumber,
        'No Pendaftaran': item.nopendaftaran,
        'No Order Alat': item.noorderalat,
        'Lingkup': item.lingkup,
        'Jenis Order': item.isRepair ? 'REPAIR' : 'KALIBRASI',
        'Tgl Masuk': item.tglMasuk,
        'Progress (%)': item.progress + '%',
        'Status Terkini': item.latestStatus || '-',
        'Status Surkes': item.jenissurkesfk == 1 ? 'SURKES' : 'NON SURKES', // Kolom Baru
        'Pelaksana': item.pelaksanateknik || '-',
        'Penyelia': item.penyeliateknik || '-',
        'Isi Catatan': formatNotes(item, notesMap)
      }
    })

    const wsAlat = XLSX.utils.aoa_to_sheet(headerInfo)
    XLSX.utils.sheet_add_json(wsAlat, dataAlat, { origin: "A6" })

    // Sesuaikan lebar kolom (Total ada 16 kolom sekarang)
    wsAlat['!cols'] = [
      { wch: 5 },  // No
      { wch: 30 }, // Nama Produk
      { wch: 15 }, // Merk
      { wch: 15 }, // Tipe
      { wch: 20 }, // S/N
      { wch: 20 }, // No Pend
      { wch: 20 }, // No Order
      { wch: 15 }, // Lingkup
      { wch: 10 }, // Jenis Order
      { wch: 15 }, // Tgl Masuk
      { wch: 10 }, // Progress
      { wch: 25 }, // Status Terkini
      { wch: 15 }, // Status Surkes (BARU)
      { wch: 20 }, // Pelaksana
      { wch: 20 }, // Penyelia
      { wch: 60 }  // Catatan
    ]
    XLSX.utils.book_append_sheet(wb, wsAlat, "Data Alat")

    // Sheet 2: Data Statistik
    const dataLingkup = scopeStats.value.categories.map((cat, i) => ({
      'Lingkup Pekerjaan': cat, 'Jumlah Alat': scopeStats.value.data[i]
    }))
    const dataPelaksana = pelaksanaStats.value.categories.map((cat, i) => ({
      'Nama Pelaksana': cat, 'Jumlah Alat': pelaksanaStats.value.data[i]
    }))

    const wsStats = XLSX.utils.aoa_to_sheet([["STATISTIK LINGKUP"]])
    XLSX.utils.sheet_add_json(wsStats, dataLingkup, { origin: "A2" })
    XLSX.utils.sheet_add_aoa(wsStats, [[""]], { origin: -1 })
    XLSX.utils.sheet_add_aoa(wsStats, [["STATISTIK PELAKSANA"]], { origin: -1 })
    XLSX.utils.sheet_add_json(wsStats, dataPelaksana, { origin: -1, skipHeader: false })
    wsStats['!cols'] = [{ wch: 30 }, { wch: 15 }]
    XLSX.utils.book_append_sheet(wb, wsStats, "Data Statistik")

    // ===== TAMBAHAN: SHEET 3 (CATATAN UNIT) =====
    if (dashUnitNotes.value.length > 0) {
      const dataNotes = dashUnitNotes.value.map(n => ({
        'Tanggal': n.formatted_date,
        'Oleh': n.nama_petugas,
        'Isi Catatan': n.notes
      }))
      const wsNotes = XLSX.utils.json_to_sheet(dataNotes)
      wsNotes['!cols'] = [{ wch: 20 }, { wch: 20 }, { wch: 80 }]
      XLSX.utils.book_append_sheet(wb, wsNotes, "Catatan Unit")
    }
    // ============================================

    const fileName = `Synergy_Detail_${unitName.value}_${TAHUN}.xlsx`.replace(/\s+/g, '_')
    const excelBuffer = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })
    saveAsExcelFile(excelBuffer, fileName)

  } catch (err) {
    console.error('Export Excel Error', err)
    H.alert('error', 'Gagal export Excel')
  } finally {
    exportingExcel.value = false
  }
}

const saveAsExcelFile = (buffer: any, fileName: string) => {
  let EXCEL_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8';
  let EXCEL_EXTENSION = '.xlsx';
  const data: Blob = new Blob([buffer], { type: EXCEL_TYPE });
  const url = window.URL.createObjectURL(data);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', fileName);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function copyToClipboard(text: string) {
  if (!text) return
  navigator.clipboard.writeText(text).then(() => { useToaster().info(text, 'Copied to clipboard') })
}
function cetakQRAlat() { H.printBlade(`customer/cetak-qr-alat?pdf=true&idalat=${currentDetailId.value}`) }
function cetakSertiVendor(item: any) { H.printBlade(`registrasi/cetak-sertifikat-vendor?norec=${item.norec_detail}`) }
function cetakSertifikatLembarKerja(item: any) { H.printBlade(`asman/cetak-sertifikat-lembar-kerja?pdf=true&norec=${item.norec}&norec_detail=${item.norec_detail}`) }
function cetakLaporanVerfikasi(item: any) { H.printBlade(`asman/cetak-laporan-verifikasi?pdf=true&norec=${item.norec}&norec_detail=${item.norec_detail}`) }
function cetakLaporanRepair(item: any) { H.printBlade(`asman/cetak-laporan-repair?pdf=true&norec=${item.norec}&norec_detail=${item.norec_detail}`) }

onMounted(() => {
  loadDetail()
  loadDashUnitNotes() // Call load notes unit
})
</script>

<style scoped lang="scss">
@import '/@src/scss/abstracts/all';

.synergy-detail-wrap {
  padding: 12px;
}

.detail-summary-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

.detail-summary-card {
  --detail-summary-accent: #3b82f6;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-top: 3px solid var(--detail-summary-accent);
  border-radius: 12px;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
  cursor: pointer;
  min-height: 128px;
  min-width: 0;
  padding: 14px;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;

  &[data-tone='navy'] {
    --detail-summary-accent: #283e59;
  }

  &[data-tone='teal'] {
    --detail-summary-accent: #10b981;
  }

  &[data-tone='purple'] {
    --detail-summary-accent: #8b5cf6;
  }

  &[data-tone='green'] {
    --detail-summary-accent: #22c55e;
  }

  &[data-tone='amber'] {
    --detail-summary-accent: #f59e0b;
  }

  &[data-tone='blue'] {
    --detail-summary-accent: #0ea5e9;
  }

  &[data-tone='red'] {
    --detail-summary-accent: #ef4444;
  }

  &:hover,
  &:focus-visible {
    border-color: color-mix(in srgb, var(--detail-summary-accent) 50%, #e5e7eb);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.1);
    outline: none;
    transform: translateY(-2px);
  }

  > strong,
  > span,
  > small {
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

.detail-summary-heading {
  align-items: center;
  display: flex;
  justify-content: space-between;
}

.detail-summary-icon {
  align-items: center;
  background: color-mix(in srgb, var(--detail-summary-accent) 12%, #fff);
  border-radius: 9px;
  color: var(--detail-summary-accent);
  display: flex !important;
  height: 32px;
  justify-content: center;
  width: 32px;
}

.detail-summary-arrow {
  color: #cbd5e1;
  font-size: 0.9rem;
}

.detail-drilldown-summary {
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  display: grid;
  gap: 16px;
  grid-template-columns: minmax(140px, 0.7fr) minmax(220px, 1fr) minmax(260px, 1.6fr);
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

.detail-drilldown-search {
  margin: 0;
}

.detail-drilldown-subtitle {
  color: #64748b;
  font-size: 0.8rem;
  margin-bottom: 14px;
}

.detail-table-main,
.detail-table-subtext {
  display: block;
}

.detail-table-main {
  color: #1f2937;
  font-weight: 600;
}

.detail-table-subtext {
  color: #94a3b8;
  font-size: 0.7rem;
  margin-top: 2px;
}

.pickup-status-tag {
  border: 0;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
  color: #fff !important;
  font-weight: 800;
  letter-spacing: 0.02em;

  &.is-picked-up {
    background: #2563eb !important;
  }

  &.is-waiting-pickup {
    background: #ef4444 !important;
  }
}

@media (max-width: 1200px) {
  .detail-summary-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .detail-summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .detail-drilldown-summary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .detail-drilldown-search {
    grid-column: 1 / -1;
  }
}

@media (max-width: 520px) {
  .synergy-detail-wrap {
    padding: 6px;
  }

  .detail-summary-grid,
  .detail-drilldown-summary {
    grid-template-columns: 1fr;
  }

  .detail-drilldown-search {
    grid-column: auto;
  }
}

/* Styles for Unit Notes Section */
.unit-notes-list {
  max-height: 200px;
  overflow-y: auto;
  padding-right: 10px;
}

.unit-note-item {
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

/* Sticky Column for Chart */
.sticky-column {
  @media (min-width: 1024px) {
    position: sticky;
    top: 90px;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
    padding-right: 5px;
  }

  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-thumb {
    background-color: #dbdbdb;
    border-radius: 4px;
  }
}

/* === BADGE STYLES === */
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

/* CARD & UTILS */
.skeleton-card {
  height: 280px;
  background: #f0f2f5;
  border-radius: 12px;
  animation: pulse 1.5s infinite;
}

.tool-card {
  border-radius: 12px;
  border: 1px solid #e1e4e8;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
  transition: all 0.2s ease;
  height: 100%;
  background: #fff;
  display: flex;
  flex-direction: column;

  &:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    border-color: #d0d7de;
  }

  .card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
  }
}

.tool-name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #283e59;
  line-height: 1.4;
}

.info-box {
  background: #f9fafb;
  border-radius: 6px;
  padding: 8px 10px;
  border: 1px solid #f0f2f5;
  height: 100%;
}

.label-tiny {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #9ca3af;
  display: block;
  margin-bottom: 2px;
}

.value-text {
  font-size: 0.8rem;
  color: #374151;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.icon-circle {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.is-info-light {
  background: #e0f2fe;
  color: #0284c7;
}

.is-warning-light {
  background: #fef3c7;
  color: #d97706;
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.metadata-rows {
  width: 100%;
}

.metadata-row {
  display: flex;
  align-items: center;
  font-size: 0.85rem;
}

.meta-label {
  color: #9ca3af;
  width: 100px;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.meta-value-group {
  display: flex;
  align-items: center;
  flex: 1;
  overflow: hidden;
}

.meta-value {
  color: #1f2937;
  font-weight: 600;
  margin-right: 8px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.copy-icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #00cfdd;
  display: flex;
  align-items: center;
  padding: 2px;
  border-radius: 4px;
  transition: all 0.2s;
  flex-shrink: 0;

  &:hover {
    background-color: #e0f2fe;
    color: #009ba6;
  }

  .iconify {
    font-size: 14px;
  }
}

.latest-status-box {
  background: #f9fafb;
  border-radius: 8px;
  padding: 10px;
  border: 1px solid #f0f2f5;
  min-height: 60px;
  margin-top: auto;
}

.border-top-dashed {
  border-top: 1px dashed #e5e7eb;
}

.action-link {
  font-size: 0.85rem;
  font-weight: 600;
  color: #00cfdd;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  transition: color 0.2s;

  &:hover {
    color: #009ba6;
  }
}

.transition-icon {
  transition: transform 0.3s ease;
}

.rotate-180 {
  transform: rotate(180deg);
}

.fade-in {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-5px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* TIMELINE */
.simple-timeline-container {
  border-top: 1px solid #f0f2f5;
  position: relative;
}

.simple-timeline-item {
  position: relative;
  padding-left: 24px;
  padding-bottom: 20px;
  border-left: 2px solid #eef0f3;
  margin-left: 6px;

  &:last-child {
    border-left: 2px solid transparent;
    padding-bottom: 0;
  }
}

.simple-timeline-marker {
  position: absolute;
  left: -6px;
  top: 0;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #fff;
  border: 3px solid #00cfdd;
  box-shadow: 0 0 0 2px rgba(0, 207, 221, 0.2);
}

.simple-timeline-content {
  top: -5px;
  position: relative;
}

.simple-timeline-content .heading {
  font-size: 0.75rem;
  color: #9ca3af;
  letter-spacing: 0.5px;
}

.simple-timeline-content .title {
  font-weight: 700;
  color: #374151;
  line-height: 1.4;
}

/* MODAL & OTHERS */
.block-header {
  display: flex;
  align-items: flex-start;
  background: #fff;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  margin-bottom: 1rem;
}

.header-thumb {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 6px;
  border: 1px solid #f0f0f0;
}

.border-left {
  border-left: 1px dashed #dbdbdb;
}

.flex-grow-1 {
  flex-grow: 1;
}

.timeline-wrapper .timeline-wrapper-inner {
  padding-left: 20px;
}

.timeline-wrapper .timeline-wrapper-inner .timeline-container {
  position: relative;
}

.timeline-wrapper .timeline-wrapper-inner .timeline-container::before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  left: 26px;
  width: 2px;
  background: #e5e7eb;
  z-index: 0;
}

.timeline-item {
  display: flex;
  margin-bottom: 20px;
  position: relative;
  z-index: 1;
}

.timeline-item .date {
  width: 80px;
  text-align: right;
  font-size: 0.8rem;
  color: #6b7280;
  padding-right: 15px;
  padding-top: 2px;
  flex-shrink: 0;
}

.timeline-item .dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  margin-top: 5px;
  margin-right: 15px;
  background: #d1d5db;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px #d1d5db;
  flex-shrink: 0;
  background-color: #fff;
}

.timeline-item .dot.is-success {
  background-color: #10b981;
  box-shadow: 0 0 0 1px #10b981;
}

.timeline-item .dot.is-info {
  background-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6;
}

.timeline-item .content-wrap {
  flex-grow: 1;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 10px 15px;
  position: relative;
}

.timeline-item .content-wrap::before {
  content: '';
  position: absolute;
  top: 10px;
  left: -6px;
  width: 10px;
  height: 10px;
  background: #f9fafb;
  border-left: 1px solid #e5e7eb;
  border-bottom: 1px solid #e5e7eb;
  transform: rotate(45deg);
}

.note-card {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 12px;
}

.detail-notes-list {
  max-height: 300px;
  overflow-y: auto;
  padding-right: 5px;
}

.skeleton-line {
  background: #eee;
  border-radius: 4px;
  animation: pulse 1.5s infinite;
}

:deep(.p-progressbar) {
  background: #f1f5f9;
  border-radius: 999px;
  overflow: hidden;
}

:deep(.p-progressbar-value) {
  background: #f59e0b;
  border-radius: 999px;
  transition: width 0.5s ease;
}

:deep(.p-progressbar-success .p-progressbar-value) {
  background: #10b981 !important;
}

:deep(.p-progressbar-info .p-progressbar-value) {
  background: #00cfdd !important;
}

.custom-multiselect {
  width: 100%;
  border-radius: 4px;
  border: 1px solid #dbdbdb;
  box-shadow: inset 0 0.0625em 0.125em rgba(10, 10, 10, 0.05);
  font-size: 1rem;
}

:deep(.p-multiselect-label) {
  padding: 8px 12px;
  font-size: 0.95rem;
}

:deep(.p-multiselect-token) {
  background: #00cfdd;
  color: #fff;
  padding: 2px 8px;
  font-size: 0.85rem;
}
</style>
