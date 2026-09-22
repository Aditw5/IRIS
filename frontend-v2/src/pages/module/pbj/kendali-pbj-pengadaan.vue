<template>
  <div class="pengadaan-page">
    <VCard class="page-heading mb-5">
      <div>
        <h3 class="title is-4 mb-1">Kendali Pengadaan PBJ Fungsi Pengadaan</h3>
        <!-- <p class="subtitle is-6 has-text-grey mb-0">
          Penyusunan dokumen pengadaan untuk PBJ yang belum memiliki PO
        </p> -->
      </div>
    </VCard>

    <VCard class="tabs-card">
      <div class="document-tabs-heading">
        <div class="document-tabs-icon">
          <VIcon icon="feather:layers" />
        </div>
        <div>
          <h4>Tahapan Dokumen Pengadaan</h4>
          <p>Pilih tab dokumen yang akan dikerjakan.</p>
        </div>
      </div>

      <VTabs type="boxed" v-model:selected="activeTab" :tabs="documentTabs">
        <template #tab="{ activeValue }">
          <div v-if="activeValue === 'hps'" class="hps-tab">
            <div class="columns workspace-columns">
              <div class="column is-12-mobile is-8-desktop detail-column">
                <VCard class="detail-panel">
                  <template v-if="selectedPbj">
                    <div class="detail-heading">
                      <div class="detail-heading-copy">
                        <div class="eyebrow">Detail PBJ</div>
                        <h4 class="detail-title">{{ selectedPbj.judulpermintaan || '-' }}</h4>
                        <div class="detail-tags">
                          <VTag color="info" rounded>{{ selectedPbj.lokasipbj || 'Lokasi belum diisi' }}</VTag>
                          <VTag rounded>{{ selectedPbj.jenispbj || 'Jenis PBJ belum diisi' }}</VTag>
                          <VTag v-if="selectedPbj.nomorhps" color="success" rounded>HPS sudah bernomor</VTag>
                          <VTag v-else color="warning" rounded>HPS belum bernomor</VTag>
                        </div>
                      </div>

                      <div class="print-actions">
                        <VButton color="info" outlined icon="feather:printer" @click="cetakPbj(selectedPbj)">
                          Cetak PBJ
                        </VButton>
                        <VButton color="warning" outlined icon="feather:file-text" @click="openIht(selectedPbj)">
                          Cetak IH
                        </VButton>
                        <VButton color="success" outlined icon="feather:hash" :loading="hpsNumberLoading"
                          @click="openHpsNumberModal(selectedPbj)">
                          {{ selectedPbj.nomorhps ? 'Ubah Nomor HPS' : 'Buat Nomor HPS' }}
                        </VButton>
                        <VButton color="primary" icon="feather:printer" @click="cetakHps(selectedPbj)">
                          Cetak HPS
                        </VButton>
                      </div>
                    </div>

                    <div v-if="selectedPbj.nomorhps" class="hps-number-strip">
                      <span>Nomor HPS</span>
                      <span>{{ selectedPbj.nomorhps }}</span>
                      <span>Tanggal {{ formatTanggal(selectedPbj.tanggalhps) }}</span>
                    </div>

                    <div class="section-title">Informasi pengajuan</div>
                    <div class="info-grid">
                      <div class="info-item info-item-primary">
                        <div class="info-icon"><VIcon icon="feather:file-text" /></div>
                        <div class="info-copy">
                          <span class="info-label">No. PBJ</span>
                          <span class="info-value no-pbj">{{ selectedPbj.nosuratpbj || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:user" /></div>
                        <div class="info-copy">
                          <span class="info-label">Nama pengaju</span>
                          <span class="info-value">{{ selectedPbj.namalengkap || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:calendar" /></div>
                        <div class="info-copy">
                          <span class="info-label">Tanggal pengajuan</span>
                          <span class="info-value">{{ formatTanggal(selectedPbj.tglpengajuan) }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:clipboard" /></div>
                        <div class="info-copy">
                          <span class="info-label">No. PR</span>
                          <span class="info-value">{{ selectedPbj.nopr || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:calendar" /></div>
                        <div class="info-copy">
                          <span class="info-label">Tanggal PR</span>
                          <span class="info-value">{{ formatTanggal(selectedPbj.tglpr) }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:users" /></div>
                        <div class="info-copy">
                          <span class="info-label">Pemohon</span>
                          <span class="info-value">{{ selectedPbj.pemohonpbj || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:shopping-bag" /></div>
                        <div class="info-copy">
                          <span class="info-label">Pengadaan</span>
                          <span class="info-value">{{ selectedPbj.pengadaanpbj || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:credit-card" /></div>
                        <div class="info-copy">
                          <span class="info-label">Dasar anggaran</span>
                          <span class="info-value">{{ selectedPbj.kebutuhan || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:user-check" /></div>
                        <div class="info-copy">
                          <span class="info-label">User</span>
                          <span class="info-value">{{ selectedPbj.pengguna || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:briefcase" /></div>
                        <div class="info-copy">
                          <span class="info-label">Bidang</span>
                          <span class="info-value">{{ selectedPbj.bidang || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:send" /></div>
                        <div class="info-copy">
                          <span class="info-label">Kepada</span>
                          <span class="info-value">{{ selectedPbj.kepada || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:bookmark" /></div>
                        <div class="info-copy">
                          <span class="info-label">PRK</span>
                          <span class="info-value">{{ selectedPbj.prk || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:tool" /></div>
                        <div class="info-copy">
                          <span class="info-label">WO</span>
                          <span class="info-value">{{ selectedPbj.wo || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:folder" /></div>
                        <div class="info-copy">
                          <span class="info-label">Project</span>
                          <span class="info-value">{{ selectedPbj.project || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:hash" /></div>
                        <div class="info-copy">
                          <span class="info-label">No. proyek</span>
                          <span class="info-value">{{ selectedPbj.noproyek || '-' }}</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <div class="info-icon"><VIcon icon="feather:tag" /></div>
                        <div class="info-copy">
                          <span class="info-label">Cost code</span>
                          <span class="info-value">{{ selectedPbj.costcode || '-' }}</span>
                        </div>
                      </div>
                    </div>

                    <div class="items-heading">
                      <div>
                        <div class="section-title mb-0">Item PBJ</div>
                        <div class="section-note">{{ selectedPbj.detailList?.length || 0 }} item pada pengajuan</div>
                      </div>
                      <div class="estimated-total">
                        <span>Total estimasi</span>
                        <span>{{ formatRupiah(totalTerpilih) }}</span>
                      </div>
                    </div>

                    <DataTable :value="selectedPbj.detailList || []" class="p-datatable-sm item-table" scrollable
                      responsiveLayout="scroll" stripedRows rowHover>
                      <Column header="No" style="width: 58px; text-align: center">
                        <template #body="slotProps">{{ slotProps.index + 1 }}</template>
                      </Column>
                      <Column field="stockcode" header="Stock Code" style="min-width: 120px" />
                      <Column field="namaitem" header="Nama Item" style="min-width: 180px" />
                      <Column field="uraianitem" header="Spesifikasi / Uraian" style="min-width: 240px" />
                      <Column field="banyak" header="Qty" style="min-width: 80px; text-align: right" />
                      <Column field="satuan" header="Satuan" style="min-width: 90px" />
                      <Column header="Harga Satuan" style="min-width: 140px; text-align: right">
                        <template #body="slotProps">{{ formatRupiah(nilaiAngka(slotProps.data.hargasatuan)) }}</template>
                      </Column>
                      <Column header="Total" style="min-width: 140px; text-align: right">
                        <template #body="slotProps">{{ formatRupiah(totalItem(slotProps.data)) }}</template>
                      </Column>
                      <Column field="keterangan" header="Keterangan" style="min-width: 200px" />
                      <template #empty>
                        <div class="table-empty">Belum ada item pada PBJ ini.</div>
                      </template>
                    </DataTable>
                  </template>

                  <div v-else class="empty-detail">
                    <VIcon icon="feather:mouse-pointer" />
                    <h4>Pilih PBJ dari daftar</h4>
                    <p>Detail pengajuan dan itemnya akan tampil di area ini.</p>
                  </div>
                </VCard>
              </div>

              <div class="column is-12-mobile is-4-desktop queue-column">
                <VCard class="queue-panel">
                  <div class="queue-heading">
                    <div>
                      <div class="eyebrow">Daftar PBJ</div>
                      <h4>Menunggu proses HPS</h4>
                    </div>
                    <VButton icon="feather:refresh-cw" color="info" outlined :loading="loading" @click="loadHps">
                      Refresh
                    </VButton>
                  </div>

                  <div class="queue-search">
                    <VControl icon="feather:search">
                      <input v-model="search" class="input" type="search" placeholder="Cari no. PBJ, judul, pengaju..." />
                    </VControl>
                  </div>

                  <div class="filter-grid">
                    <VControl>
                      <div class="select is-fullwidth">
                        <select v-model="filterLokasi" aria-label="Filter lokasi">
                          <option value="">Semua lokasi</option>
                          <option v-for="lokasi in pilihanLokasi" :key="lokasi.value" :value="lokasi.value">
                            {{ lokasi.label }}
                          </option>
                        </select>
                      </div>
                    </VControl>
                    <VControl>
                      <div class="select is-fullwidth">
                        <select v-model="filterJenis" aria-label="Filter jenis PBJ">
                          <option value="">Semua jenis</option>
                          <option v-for="jenis in pilihanJenis" :key="jenis.value" :value="jenis.value">
                            {{ jenis.label }}
                          </option>
                        </select>
                      </div>
                    </VControl>
                    <VControl class="filter-status">
                      <div class="select is-fullwidth">
                        <select v-model="filterHps" aria-label="Filter status HPS">
                          <option value="">Semua status HPS</option>
                          <option value="belum">Belum bernomor</option>
                          <option value="sudah">Sudah bernomor</option>
                        </select>
                      </div>
                    </VControl>
                  </div>

                  <div class="queue-summary">
                    <span>{{ filteredPbj.length }} PBJ ditemukan</span>
                    <button v-if="hasFilter" type="button" class="clear-filter" @click="resetFilter">
                      Bersihkan filter
                    </button>
                  </div>

                  <div v-if="loading" class="queue-list">
                    <div v-for="index in 5" :key="index" class="queue-skeleton">
                      <VPlaceload class="mb-2" />
                      <VPlaceloadText :lines="2" width="70%" last-line-width="45%" />
                    </div>
                  </div>

                  <div v-else-if="filteredPbj.length" class="queue-list">
                    <button v-for="row in filteredPbj" :key="row.norec" type="button" class="queue-item"
                      :class="{ selected: selectedPbj?.norec === row.norec }" @click="selectPbj(row)">
                      <div class="queue-number">{{ row.nosuratpbj || 'Nomor PBJ belum tersedia' }}</div>
                      <div class="queue-title">{{ row.judulpermintaan || '-' }}</div>
                      <div class="queue-meta">
                        <span><i class="iconify" data-icon="feather:calendar"></i>{{ formatTanggal(row.tglpengajuan) }}</span>
                        <span><i class="iconify" data-icon="feather:user"></i>{{ row.namalengkap || '-' }}</span>
                      </div>
                      <div class="queue-foot">
                        <VTag color="info" rounded>{{ row.lokasipbj || 'Tanpa lokasi' }}</VTag>
                        <VTag v-if="row.nomorhps" color="success" rounded>{{ row.nomorhps }}</VTag>
                        <VTag v-else color="warning" rounded>Belum ada nomor HPS</VTag>
                      </div>
                    </button>
                  </div>

                  <div v-else class="empty-queue">
                    <VIcon icon="feather:inbox" />
                    <h4>Tidak ada PBJ</h4>
                    <p>Tidak ada data yang sesuai pencarian dan filter.</p>
                  </div>
                </VCard>
              </div>
            </div>
          </div>

          <div v-else-if="activeValue === 'pp'" class="hps-tab pp-tab">
            <div class="columns workspace-columns">
              <div class="column is-12-mobile is-8-desktop detail-column">
                <VCard class="detail-panel pp-detail-panel">
                  <template v-if="selectedPp">
                    <div class="detail-heading">
                      <div class="detail-heading-copy">
                        <div class="eyebrow">Proses Permintaan Penawaran</div>
                        <h4 class="detail-title">{{ selectedPp.judulpermintaan || '-' }}</h4>
                        <div class="detail-tags">
                          <VTag color="info" rounded>{{ selectedPp.lokasipbj || 'Lokasi belum diisi' }}</VTag>
                          <VTag color="success" rounded>{{ selectedPp.nomorhps }}</VTag>
                          <VTag :color="selectedPp.siapbapp ? 'primary' : 'warning'" rounded>
                            {{ selectedPp.siapbapp ? 'Siap masuk BAPP' : 'Proses PP' }}
                          </VTag>
                        </div>
                      </div>
                      <div class="print-actions">
                        <VButton color="primary" icon="feather:printer" @click="cetakPp(selectedPp)">
                          Cetak PP
                        </VButton>
                      </div>
                    </div>

                    <div class="pp-document-strip">
                      <div>
                        <span>Nomor PP</span>
                        <strong>{{ selectedPp.nomorpp }}</strong>
                      </div>
                      <div>
                        <span>No. PBJ</span>
                        <strong>{{ selectedPp.nosuratpbj || '-' }}</strong>
                      </div>
                      <div>
                        <span>No. PR</span>
                        <strong>{{ selectedPp.nopr || '-' }}</strong>
                      </div>
                    </div>

                    <div class="pp-timeline">
                      <section class="pp-step" :class="{ completed: ppStepOneComplete }">
                        <div class="pp-step-rail">
                          <span class="pp-step-dot">1</span>
                          <span class="pp-step-line"></span>
                        </div>
                        <div class="pp-step-content">
                          <VCard class="pp-step-card">
                            <div class="pp-step-heading">
                              <div>
                                <span class="pp-step-kicker">Tahap 1</span>
                                <h5>Tanggal Permintaan Penawaran</h5>
                                <p>Isi tanggal dokumen PP dan batas akhir pemasukan penawaran.</p>
                              </div>
                              <VTag :color="ppStepOneComplete ? 'success' : 'warning'" rounded>
                                {{ ppStepOneComplete ? 'Tanggal tersimpan' : 'Belum lengkap' }}
                              </VTag>
                            </div>

                            <div class="pp-date-grid">
                              <VField>
                                <template #label>
                                  <span class="pp-date-label">Tanggal Permintaan Penawaran</span>
                                </template>
                                <VControl icon="feather:calendar">
                                  <input v-model="ppForm.tanggalpp" class="input" type="date" />
                                </VControl>
                              </VField>
                              <VField>
                                <template #label>
                                  <span class="pp-date-label">Batas Pemasukan Penawaran</span>
                                </template>
                                <VControl icon="feather:clock">
                                  <input v-model="ppForm.bataspemasukan" class="input" type="date"
                                    :min="ppForm.tanggalpp || undefined" />
                                </VControl>
                              </VField>
                            </div>

                            <div class="pp-step-actions">
                              <p>Cetakan PP tetap dapat dibuka meskipun tanggal belum diisi.</p>
                              <div>
                                <VButton color="info" outlined icon="feather:printer" @click="cetakPp(selectedPp)">
                                  Preview Cetakan
                                </VButton>
                                <VButton color="primary" icon="feather:save" :loading="ppSaving" @click="savePpDates">
                                  Simpan Tanggal
                                </VButton>
                              </div>
                            </div>
                          </VCard>
                        </div>
                      </section>

                      <section class="pp-step" :class="{ completed: selectedPp.siapbapp, disabled: !ppStepOneComplete }">
                        <div class="pp-step-rail">
                          <span class="pp-step-dot">2</span>
                        </div>
                        <div class="pp-step-content">
                          <VCard class="pp-step-card">
                            <div class="pp-step-heading">
                              <div>
                                <span class="pp-step-kicker">Tahap 2</span>
                                <h5>Upload File Permintaan Penawaran</h5>
                                <p>File baru akan ditambahkan ke riwayat. Upload sebelumnya tidak akan tertimpa.</p>
                              </div>
                              <VTag :color="selectedPp.siapbapp ? 'success' : 'warning'" rounded>
                                {{ selectedPp.jumlahfilepp || 0 }} file terupload
                              </VTag>
                            </div>

                            <div v-if="!ppStepOneComplete" class="pp-step-lock">
                              <VIcon icon="feather:lock" />
                              <span>Simpan kedua tanggal pada tahap 1 untuk membuka upload file.</span>
                            </div>

                            <template v-else>
                              <FileUpload :key="ppUploaderKey" ref="ppUploader" name="files[]" mode="advanced"
                                :multiple="true" :maxFileSize="15728640" :showUploadButton="false"
                                :showCancelButton="true" :customUpload="true"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                chooseLabel="Pilih File PP" cancelLabel="Bersihkan"
                                invalidFileTypeMessage="{0}: format file tidak didukung."
                                invalidFileSizeMessage="{0}: ukuran maksimal 15 MB."
                                @select="onPpFileSelect" @remove="onPpFileRemove" @clear="ppSelectedFiles = []" />

                              <div class="pp-upload-action">
                                <span>{{ ppSelectedFiles.length }} file dipilih</span>
                                <VButton color="primary" icon="feather:upload" :loading="ppUploading"
                                  :disabled="!ppSelectedFiles.length" @click="uploadPpFiles">
                                  Upload File
                                </VButton>
                              </div>
                            </template>

                            <div class="pp-file-history">
                              <div class="pp-file-history-title">
                                <div>
                                  <h6>Riwayat Upload</h6>
                                  <p>Urutan terbaru ditampilkan paling atas.</p>
                                </div>
                              </div>
                              <div v-if="selectedPp.fileList?.length" class="pp-file-list">
                                <div v-for="(file, index) in selectedPp.fileList" :key="file.norec" class="pp-file-row">
                                  <div class="pp-file-icon"><VIcon icon="feather:file" /></div>
                                  <div class="pp-file-copy">
                                    <strong>{{ file.namaasli }}</strong>
                                    <span>Upload ke-{{ selectedPp.fileList.length - index }} · {{ formatTanggalWaktu(file.created_at) }} · {{ formatFileSize(file.ukuran) }}</span>
                                  </div>
                                  <div class="pp-file-actions">
                                    <VIconButton color="primary" outlined circle icon="feather:eye"
                                      v-tooltip.top="'Lihat dan cetak file'" @click="viewPpFile(file)" />
                                    <VIconButton color="info" outlined circle icon="feather:download"
                                      v-tooltip.top="'Download file'" @click="downloadPpFile(file)" />
                                    <VIconButton color="danger" outlined circle icon="feather:trash-2"
                                      v-tooltip.top="'Hapus file'" @click="confirmDeletePpFile(file)" />
                                  </div>
                                </div>
                              </div>
                              <div v-else class="pp-file-empty">
                                <VIcon icon="feather:archive" />
                                <span>Belum ada file Permintaan Penawaran yang diupload.</span>
                              </div>
                            </div>
                          </VCard>
                        </div>
                      </section>
                    </div>
                  </template>

                  <div v-else class="empty-detail">
                    <VIcon icon="feather:mouse-pointer" />
                    <h4>Pilih PBJ dari daftar PP</h4>
                    <p>Timeline pengisian tanggal dan upload file akan tampil di sini.</p>
                  </div>
                </VCard>
              </div>

              <div class="column is-12-mobile is-4-desktop queue-column">
                <VCard class="queue-panel pp-queue-panel">
                  <div class="queue-heading">
                    <div>
                      <div class="eyebrow">Daftar PBJ</div>
                      <h4>HPS yang Sudah Bernomor</h4>
                    </div>
                    <VButton icon="feather:refresh-cw" color="info" outlined :loading="ppLoading" @click="loadPp">
                      Refresh
                    </VButton>
                  </div>

                  <div class="queue-search">
                    <VControl icon="feather:search">
                      <input v-model="ppSearch" class="input" type="search"
                        placeholder="Cari no. PBJ, no. HPS, judul..." />
                    </VControl>
                  </div>

                  <div class="filter-grid">
                    <VControl>
                      <div class="select is-fullwidth">
                        <select v-model="ppFilterLokasi" aria-label="Filter lokasi PP">
                          <option value="">Semua lokasi</option>
                          <option v-for="lokasi in pilihanLokasiPp" :key="lokasi.value" :value="lokasi.value">
                            {{ lokasi.label }}
                          </option>
                        </select>
                      </div>
                    </VControl>
                    <VControl>
                      <div class="select is-fullwidth">
                        <select v-model="ppFilterStatus" aria-label="Filter status PP">
                          <option value="">Semua status</option>
                          <option value="tanggal">Tanggal tersimpan</option>
                          <option value="upload">Sudah upload</option>
                          <option value="belum">Belum diproses</option>
                        </select>
                      </div>
                    </VControl>
                  </div>

                  <div class="queue-summary">
                    <span>{{ filteredPp.length }} PBJ masuk tahap PP</span>
                    <button v-if="ppSearch || ppFilterLokasi || ppFilterStatus" type="button" class="clear-filter"
                      @click="resetPpFilter">Bersihkan filter</button>
                  </div>

                  <div v-if="ppLoading" class="queue-list">
                    <div v-for="index in 5" :key="index" class="queue-skeleton">
                      <VPlaceload class="mb-2" />
                      <VPlaceloadText :lines="3" width="75%" last-line-width="50%" />
                    </div>
                  </div>
                  <div v-else-if="filteredPp.length" class="queue-list">
                    <button v-for="row in filteredPp" :key="row.norec" type="button" class="queue-item pp-queue-item"
                      :class="{ selected: selectedPp?.norec === row.norec }" @click="selectPp(row)">
                      <div class="pp-queue-identities">
                        <div><span>No. PBJ</span><strong>{{ row.nosuratpbj || '-' }}</strong></div>
                        <div><span>No. HPS</span><strong>{{ row.nomorhps }}</strong></div>
                      </div>
                      <div class="queue-title">{{ row.judulpermintaan || '-' }}</div>
                      <div class="queue-meta">
                        <span><i class="iconify" data-icon="feather:user"></i>{{ row.namalengkap || '-' }}</span>
                        <span><i class="iconify" data-icon="feather:map-pin"></i>{{ row.lokasipbj || '-' }}</span>
                      </div>
                      <div class="queue-foot">
                        <VTag :color="row.tanggalpp && row.bataspemasukan ? 'info' : 'warning'" rounded>
                          {{ row.tanggalpp && row.bataspemasukan ? 'Tanggal tersimpan' : 'Tanggal belum lengkap' }}
                        </VTag>
                        <VTag :color="row.siapbapp ? 'success' : 'light'" rounded>
                          {{ row.jumlahfilepp || 0 }} file PP
                        </VTag>
                      </div>
                    </button>
                  </div>
                  <div v-else class="empty-queue">
                    <VIcon icon="feather:inbox" />
                    <h4>Belum ada data PP</h4>
                    <p>PBJ akan tampil setelah nomor HPS disimpan.</p>
                  </div>
                </VCard>
              </div>
            </div>
          </div>

          <div v-else-if="activeValue === 'bapp'" class="hps-tab pp-tab bapp-tab">
            <div class="columns workspace-columns">
              <div class="column is-12-mobile is-8-desktop detail-column">
                <VCard class="detail-panel pp-detail-panel bapp-detail-panel">
                  <template v-if="selectedBapp">
                    <div class="detail-heading">
                      <div class="detail-heading-copy">
                        <div class="eyebrow">Berita Acara Pembukaan Penawaran</div>
                        <h4 class="detail-title">{{ selectedBapp.judulpermintaan || '-' }}</h4>
                        <div class="detail-tags">
                          <VTag color="info" rounded>{{ selectedBapp.lokasipbj || 'Lokasi belum diisi' }}</VTag>
                          <VTag color="success" rounded>{{ selectedBapp.nomorpp }}</VTag>
                          <VTag :color="selectedBapp.jumlahpenyedia ? 'primary' : 'warning'" rounded>
                            {{ selectedBapp.jumlahpenyedia || 0 }} penyedia
                          </VTag>
                        </div>
                      </div>
                      <div class="print-actions">
                        <VButton color="primary" icon="feather:printer" @click="cetakBapp(selectedBapp)">
                          Cetak BAPP
                        </VButton>
                      </div>
                    </div>

                    <div class="pp-document-strip bapp-document-strip">
                      <div><span>Nomor BAPP</span><strong>{{ selectedBapp.nomorbapp }}</strong></div>
                      <div><span>No. PBJ</span><strong>{{ selectedBapp.nosuratpbj || '-' }}</strong></div>
                      <div><span>No. PP</span><strong>{{ selectedBapp.nomorpp }}</strong></div>
                      <div><span>File PP</span><strong>{{ selectedBapp.jumlahfilepp || 0 }} file</strong></div>
                    </div>

                    <div class="pp-timeline bapp-timeline">
                      <section class="pp-step" :class="{ completed: Boolean(selectedBapp.tanggalbapp) }">
                        <div class="pp-step-rail">
                          <span class="pp-step-dot">1</span>
                          <span class="pp-step-line"></span>
                        </div>
                        <div class="pp-step-content">
                          <VCard class="pp-step-card">
                            <div class="pp-step-heading">
                              <div>
                                <span class="pp-step-kicker">Tahap 1</span>
                                <h5>Tanggal Berita Acara</h5>
                                <p>Tanggal Permintaan Penawaran otomatis diambil dari tahap PP.</p>
                              </div>
                              <VTag :color="selectedBapp.tanggalbapp ? 'success' : 'warning'" rounded>
                                {{ selectedBapp.tanggalbapp ? 'Tanggal tersimpan' : 'Belum disimpan' }}
                              </VTag>
                            </div>

                            <div class="pp-date-grid">
                              <VField>
                                <template #label>
                                  <span class="pp-date-label">Tanggal Permintaan Penawaran</span>
                                </template>
                                <VControl icon="feather:calendar">
                                  <input class="input bapp-readonly-input" type="text"
                                    :value="formatTanggal(selectedBapp.tanggalpp)" readonly />
                                </VControl>
                              </VField>
                              <VField>
                                <template #label><span class="pp-date-label">Tanggal BAPP</span></template>
                                <VControl icon="feather:calendar">
                                  <input v-model="bappForm.tanggalbapp" class="input" type="date" />
                                </VControl>
                              </VField>
                            </div>

                            <div class="pp-step-actions">
                              <p>Jika belum disimpan, tanggal BAPP disarankan mengikuti batas pemasukan penawaran.</p>
                              <div>
                                <VButton color="info" outlined icon="feather:printer" @click="cetakBapp(selectedBapp)">
                                  Preview Cetakan
                                </VButton>
                                <VButton color="primary" icon="feather:save" :loading="bappSaving"
                                  @click="saveBappDate">
                                  Simpan Tanggal
                                </VButton>
                              </div>
                            </div>
                          </VCard>
                        </div>
                      </section>

                      <section class="pp-step" :class="{ completed: Boolean(selectedBapp.jumlahpenyedia) }">
                        <div class="pp-step-rail"><span class="pp-step-dot">2</span></div>
                        <div class="pp-step-content">
                          <VCard class="pp-step-card">
                            <div class="pp-step-heading">
                              <div>
                                <span class="pp-step-kicker">Tahap 2</span>
                                <h5>Daftar Penyedia Barang/Jasa</h5>
                                <p>Tambahkan seluruh penyedia yang memasukkan penawaran.</p>
                              </div>
                              <div class="bapp-provider-heading-actions">
                                <VTag :color="selectedBapp.jumlahpenyedia ? 'success' : 'warning'" rounded>
                                  {{ selectedBapp.jumlahpenyedia || 0 }} penyedia
                                </VTag>
                                <VButton color="primary" icon="feather:plus" @click="openBappProviderModal()">
                                  Tambah Penyedia
                                </VButton>
                              </div>
                            </div>

                            <DataTable :value="selectedBapp.penyediaList || []"
                              class="p-datatable-sm item-table bapp-provider-table" responsiveLayout="scroll"
                              stripedRows rowHover>
                              <Column header="No" style="width: 44px; text-align: center">
                                <template #body="slotProps">{{ slotProps.index + 1 }}</template>
                              </Column>
                              <Column field="namapenyedia" header="Nama Penyedia" style="min-width: 210px" />
                              <Column header="Total Harga" style="min-width: 150px; text-align: right">
                                <template #body="slotProps">{{ formatRupiah(slotProps.data.totalharga) }}</template>
                              </Column>
                              <Column field="keterangan" header="Keterangan" style="min-width: 220px" />
                              <Column header="Aksi" style="width: 112px; text-align: center">
                                <template #body="slotProps">
                                  <div class="bapp-table-actions">
                                    <VIconButton color="info" outlined circle icon="feather:edit-2"
                                      v-tooltip.top="'Edit penyedia'" @click="openBappProviderModal(slotProps.data)" />
                                    <VIconButton color="danger" outlined circle icon="feather:trash-2"
                                      v-tooltip.top="'Hapus penyedia'" @click="confirmDeleteBappProvider(slotProps.data)" />
                                  </div>
                                </template>
                              </Column>
                              <template #empty><div class="table-empty">Belum ada penyedia pada BAPP ini.</div></template>
                            </DataTable>
                          </VCard>
                        </div>
                      </section>
                    </div>
                  </template>

                  <div v-else class="empty-detail">
                    <VIcon icon="feather:mouse-pointer" />
                    <h4>Pilih PBJ dari daftar BAPP</h4>
                    <p>Tanggal dan daftar penyedia akan tampil di area ini.</p>
                  </div>
                </VCard>
              </div>

              <div class="column is-12-mobile is-4-desktop queue-column">
                <VCard class="queue-panel pp-queue-panel bapp-queue-panel">
                  <div class="queue-heading">
                    <div>
                      <div class="eyebrow">Daftar PBJ</div>
                      <h4>PP dengan File Terupload</h4>
                    </div>
                    <VButton icon="feather:refresh-cw" color="info" outlined :loading="bappLoading" @click="loadBapp">
                      Refresh
                    </VButton>
                  </div>

                  <div class="queue-search">
                    <VControl icon="feather:search">
                      <input v-model="bappSearch" class="input" type="search"
                        placeholder="Cari no. PBJ, no. PP, judul..." />
                    </VControl>
                  </div>

                  <div class="filter-grid">
                    <VControl>
                      <div class="select is-fullwidth">
                        <select v-model="bappFilterLokasi" aria-label="Filter lokasi BAPP">
                          <option value="">Semua lokasi</option>
                          <option v-for="lokasi in pilihanLokasiBapp" :key="lokasi.value" :value="lokasi.value">
                            {{ lokasi.label }}
                          </option>
                        </select>
                      </div>
                    </VControl>
                    <VControl>
                      <div class="select is-fullwidth">
                        <select v-model="bappFilterStatus" aria-label="Filter status BAPP">
                          <option value="">Semua status</option>
                          <option value="tanggal">Tanggal tersimpan</option>
                          <option value="penyedia">Sudah ada penyedia</option>
                          <option value="belum">Belum diproses</option>
                        </select>
                      </div>
                    </VControl>
                  </div>

                  <div class="queue-summary">
                    <span>{{ filteredBapp.length }} PBJ masuk tahap BAPP</span>
                    <button v-if="bappSearch || bappFilterLokasi || bappFilterStatus" type="button"
                      class="clear-filter" @click="resetBappFilter">Bersihkan filter</button>
                  </div>

                  <div v-if="bappLoading" class="queue-list">
                    <div v-for="index in 5" :key="index" class="queue-skeleton">
                      <VPlaceload class="mb-2" />
                      <VPlaceloadText :lines="3" width="75%" last-line-width="50%" />
                    </div>
                  </div>
                  <div v-else-if="filteredBapp.length" class="queue-list">
                    <button v-for="row in filteredBapp" :key="row.norec" type="button"
                      class="queue-item pp-queue-item" :class="{ selected: selectedBapp?.norec === row.norec }"
                      @click="selectBapp(row)">
                      <div class="pp-queue-identities">
                        <div><span>No. PBJ</span><strong>{{ row.nosuratpbj || '-' }}</strong></div>
                        <div><span>No. PP</span><strong>{{ row.nomorpp }}</strong></div>
                      </div>
                      <div class="queue-title">{{ row.judulpermintaan || '-' }}</div>
                      <div class="queue-meta">
                        <span><i class="iconify" data-icon="feather:user"></i>{{ row.namalengkap || '-' }}</span>
                        <span><i class="iconify" data-icon="feather:map-pin"></i>{{ row.lokasipbj || '-' }}</span>
                      </div>
                      <div class="queue-foot">
                        <VTag :color="row.tanggalbapp ? 'info' : 'warning'" rounded>
                          {{ row.tanggalbapp ? formatTanggal(row.tanggalbapp) : 'Tanggal belum disimpan' }}
                        </VTag>
                        <VTag :color="row.jumlahpenyedia ? 'success' : 'light'" rounded>
                          {{ row.jumlahpenyedia || 0 }} penyedia
                        </VTag>
                      </div>
                    </button>
                  </div>
                  <div v-else class="empty-queue">
                    <VIcon icon="feather:inbox" />
                    <h4>Belum ada data BAPP</h4>
                    <p>PBJ akan tampil setelah minimal satu file PP diupload.</p>
                  </div>
                </VCard>
              </div>
            </div>
          </div>

          <div v-else class="coming-soon">
            <VIcon icon="feather:file-plus" />
            <h4>{{ placeholderTab(activeValue).title }}</h4>
            <p>{{ placeholderTab(activeValue).description }}</p>
          </div>
        </template>
      </VTabs>
    </VCard>

    <VModal :open="ihtModalOpen" title="Cetakan Informasi Harga" size="medium" actions="right"
      @close="ihtModalOpen = false">
      <template #content>
        <p class="mb-4 has-text-grey">Pilih file IH yang akan dibuka.</p>
        <DataTable :value="selectedIht" class="p-datatable-sm iht-table" stripedRows rowHover>
          <Column header="No" style="width: 64px; text-align: center">
            <template #body="slotProps">{{ slotProps.index + 1 }}</template>
          </Column>
          <Column field="namafileiht" header="Nama file" />
          <Column header="Cetak" style="width: 92px; text-align: center">
            <template #body="slotProps">
              <VIconButton color="danger" outlined circle icon="feather:printer" @click="cetakIht(slotProps.data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </VModal>

    <VModal :open="hpsNumberModalOpen" title="Buat Nomor HPS" size="medium" actions="right"
      @close="hpsNumberModalOpen = false">
      <template #content>
        <div class="hps-number-modal">
          <div class="hps-number-modal-intro">
            <div class="hps-number-modal-icon">
              <VIcon icon="feather:hash" />
            </div>
            <div>
              <h4>Nomor HPS PBJ</h4>
              <p>Nomor berikut mengikuti urutan HPS terakhir dan masih dapat diedit sebelum disimpan.</p>
            </div>
          </div>

          <div v-if="hpsNumberLoading" class="hps-number-loading">
            <VPlaceload class="mb-3" />
            <VPlaceloadText :lines="2" width="80%" last-line-width="55%" />
          </div>

          <template v-else>
            <div v-if="hpsNumberDraft.existing" class="hps-number-current">
              <span>Nomor saat ini</span>
              <strong>{{ hpsNumberDraft.nomorhps }}</strong>
            </div>

            <VField label="Urutan nomor HPS" class="hps-number-field">
              <VControl>
                <input v-model.number="hpsNumberDraft.nourut" class="input hps-number-input" type="number" min="1"
                  step="1" :disabled="hpsNumberSaving" aria-label="Urutan nomor HPS" />
              </VControl>
            </VField>

            <div class="hps-number-preview">
              <span>Nomor yang akan disimpan</span>
              <strong>{{ nomorHpsPreview }}</strong>
            </div>
            <p class="hps-number-help">
              Tahun dan tanggal HPS mengikuti tanggal PR: {{ formatTanggal(hpsNumberDraft.tanggalhps) }}.
            </p>
          </template>
        </div>
      </template>
      <template #action="{ close }">
        <VButton light @click="close(); hpsNumberModalOpen = false">Batal</VButton>
        <VButton color="primary" :loading="hpsNumberSaving" :disabled="hpsNumberLoading" @click="saveHpsNumber">
          Simpan Nomor HPS
        </VButton>
      </template>
    </VModal>

    <VModal :open="ppFileDeleteModalOpen" title="Hapus File Permintaan Penawaran" size="small" actions="right"
      @close="ppFileDeleteModalOpen = false">
      <template #content>
        <div class="bapp-delete-confirm">
          <div class="bapp-delete-icon"><VIcon icon="feather:trash-2" /></div>
          <div>
            <h4>Hapus file dari riwayat PP?</h4>
            <p>
              File <strong>{{ ppFileDeleteTarget?.namaasli || '-' }}</strong> tidak lagi dihitung sebagai kelengkapan
              tahap PP setelah dihapus.
            </p>
          </div>
        </div>
      </template>
      <template #action="{ close }">
        <VButton light @click="close(); ppFileDeleteModalOpen = false">Batal</VButton>
        <VButton color="danger" icon="feather:trash-2" :loading="ppFileDeleting" @click="deletePpFile">
          Hapus File
        </VButton>
      </template>
    </VModal>

    <VModal :open="bappProviderModalOpen" :title="bappProviderEditing ? 'Edit Penyedia' : 'Tambah Penyedia'"
      size="medium" actions="right" @close="closeBappProviderModal">
      <template #content>
        <div class="bapp-provider-modal-intro">
          <div class="bapp-provider-modal-icon"><VIcon icon="feather:briefcase" /></div>
          <div>
            <h4>{{ bappProviderEditing ? 'Perbarui data penyedia' : 'Tambahkan penyedia penawaran' }}</h4>
            <p>Isi nama penyedia, total harga penawaran, dan keterangan yang akan tercetak pada BAPP.</p>
          </div>
        </div>

        <div class="bapp-provider-modal-form">
          <VField>
            <template #label><span class="pp-date-label">Nama Penyedia Barang/Jasa</span></template>
            <VControl icon="feather:briefcase">
              <input v-model="bappProviderForm.namapenyedia" class="input" type="text"
                placeholder="Masukkan nama penyedia" maxlength="255" />
            </VControl>
          </VField>
          <VField>
            <template #label><span class="pp-date-label">Total Harga Penawaran (Rp)</span></template>
            <VControl icon="feather:credit-card">
              <input :value="bappProviderForm.totalharga" class="input" type="text" inputmode="numeric"
                autocomplete="off" placeholder="0" @input="formatBappProviderPrice" />
            </VControl>
            <p class="bapp-money-preview">Nilai: {{ formatRupiah(parseBappProviderPrice(bappProviderForm.totalharga)) }}</p>
          </VField>
          <VField class="bapp-provider-note">
            <template #label><span class="pp-date-label">Keterangan</span></template>
            <VControl>
              <textarea v-model="bappProviderForm.keterangan" class="textarea" rows="3" maxlength="2000"
                placeholder="Masukkan keterangan penawaran"></textarea>
            </VControl>
          </VField>
        </div>
      </template>
      <template #action="{ close }">
        <VButton light @click="close(); closeBappProviderModal()">Batal</VButton>
        <VButton color="primary" icon="feather:save" :loading="bappProviderSaving" @click="saveBappProvider">
          {{ bappProviderEditing ? 'Simpan Perubahan' : 'Tambah Penyedia' }}
        </VButton>
      </template>
    </VModal>

    <VModal :open="bappDeleteModalOpen" title="Hapus Penyedia" size="small" actions="right"
      @close="bappDeleteModalOpen = false">
      <template #content>
        <div class="bapp-delete-confirm">
          <div class="bapp-delete-icon"><VIcon icon="feather:trash-2" /></div>
          <div>
            <h4>Hapus penyedia dari BAPP?</h4>
            <p>
              Penyedia <strong>{{ bappDeleteTarget?.namapenyedia || '-' }}</strong> akan dihapus dari daftar
              pembukaan penawaran.
            </p>
          </div>
        </div>
      </template>
      <template #action="{ close }">
        <VButton light @click="close(); bappDeleteModalOpen = false">Batal</VButton>
        <VButton color="danger" icon="feather:trash-2" :loading="bappProviderDeleting"
          @click="deleteBappProvider">
          Hapus Penyedia
        </VButton>
      </template>
    </VModal>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useHead } from '@vueuse/head'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import FileUpload from 'primevue/fileupload'
import { useApi } from '/@src/composable/useApi'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import * as H from '/@src/utils/appHelper'

type FilterOption = {
  label: string
  value: string
}

const activeTabStorageKey = 'kendali-pbj-pengadaan.active-tab'
const allowedTabs = new Set(['hps', 'pp', 'bapp', 'nego', 'lspk', 'penunjukan'])
const cachedActiveTab = (() => {
  if (typeof window === 'undefined') return 'hps'
  const stored = window.localStorage.getItem(activeTabStorageKey) || ''
  return allowedTabs.has(stored) ? stored : 'hps'
})()
const activeTab = ref(cachedActiveTab)
const loading = ref(false)
const rows = ref<any[]>([])
const selectedPbj = ref<any>(null)
const search = ref('')
const filterLokasi = ref('')
const filterJenis = ref('')
const filterHps = ref('')
const ihtModalOpen = ref(false)
const selectedIht = ref<any[]>([])
const hpsNumberModalOpen = ref(false)
const hpsNumberLoading = ref(false)
const hpsNumberSaving = ref(false)
const hpsNumberDraft = ref<any>({
  norec: '',
  nourut: 0,
  tahun: new Date().getFullYear(),
  nomorhps: '',
  tanggalhps: '',
  existing: false,
})
const ppRows = ref<any[]>([])
const selectedPp = ref<any>(null)
const ppLoading = ref(false)
const ppSaving = ref(false)
const ppUploading = ref(false)
const ppFileDeleting = ref(false)
const ppFileDeleteModalOpen = ref(false)
const ppFileDeleteTarget = ref<any>(null)
const ppLoaded = ref(false)
const ppSearch = ref('')
const ppFilterLokasi = ref('')
const ppFilterStatus = ref('')
const ppSelectedFiles = ref<File[]>([])
const ppUploaderKey = ref(0)
const ppUploader = ref<any>(null)
const ppForm = ref({
  tanggalpp: '',
  bataspemasukan: '',
})
const bappRows = ref<any[]>([])
const selectedBapp = ref<any>(null)
const bappLoading = ref(false)
const bappLoaded = ref(false)
const bappSaving = ref(false)
const bappProviderSaving = ref(false)
const bappProviderDeleting = ref(false)
const bappProviderModalOpen = ref(false)
const bappProviderEditing = ref(false)
const bappSearch = ref('')
const bappFilterLokasi = ref('')
const bappFilterStatus = ref('')
const bappDeleteModalOpen = ref(false)
const bappDeleteTarget = ref<any>(null)
const bappForm = ref({ tanggalbapp: '' })
const bappProviderForm = ref({
  norec: '',
  namapenyedia: '',
  totalharga: '',
  keterangan: 'Harga tertera termasuk pajak berlaku',
})

useHead({ title: 'Kendali Pengadaan PBJ - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const documentTabs = computed(() => [
  { label: `1. HPS - Harga Perkiraan Sendiri (${rows.value.length})`, value: 'hps', icon: 'feather:file-text' },
  { label: `2. PP - Permintaan Penawaran (${ppRows.value.length})`, value: 'pp', icon: 'feather:send' },
  { label: `3. BAPP - Pembukaan Penawaran (${bappRows.value.length})`, value: 'bapp', icon: 'feather:clipboard' },
  { label: '4. Negosiasi', value: 'nego', icon: 'feather:message-square' },
  { label: '5. LSPK', value: 'lspk', icon: 'feather:file' },
  { label: '6. Penunjukan', value: 'penunjukan', icon: 'feather:check-square' },
])

const placeholderTabs: Record<string, { title: string; description: string }> = {
  nego: {
    title: '4. Negosiasi',
    description: 'Tahap negosiasi akan disiapkan pada instruksi berikutnya.',
  },
  lspk: {
    title: '5. LSPK',
    description: 'Tahap LSPK akan disiapkan pada instruksi berikutnya.',
  },
  penunjukan: {
    title: '6. Penunjukan',
    description: 'Tahap penunjukan akan disiapkan pada instruksi berikutnya.',
  },
}

const placeholderTab = (value: string) => placeholderTabs[value] || {
  title: 'Tahap Pengadaan',
  description: 'Tahap ini belum tersedia.',
}

const uniqueOptions = (items: any[], valueKey: string, labelKey: string): FilterOption[] => {
  const found = new Map<string, string>()
  items.forEach((row) => {
    const value = row?.[valueKey]
    const label = row?.[labelKey]
    if (value !== null && value !== undefined && String(value) !== '' && label) {
      found.set(String(value), String(label))
    }
  })

  return Array.from(found, ([value, label]) => ({ value, label }))
    .sort((a, b) => a.label.localeCompare(b.label, 'id'))
}

const pilihanLokasi = computed(() => uniqueOptions(rows.value, 'lokasipbjfk', 'lokasipbj'))
const pilihanJenis = computed(() => uniqueOptions(rows.value, 'jenispbjfk', 'jenispbj'))

const filteredPbj = computed(() => {
  const keyword = search.value.trim().toLocaleLowerCase('id')

  return rows.value.filter((row) => {
    const searchable = [row.nosuratpbj, row.judulpermintaan, row.namalengkap, row.nopr]
      .filter(Boolean)
      .join(' ')
      .toLocaleLowerCase('id')
    const matchesSearch = !keyword || searchable.includes(keyword)
    const matchesLokasi = !filterLokasi.value || String(row.lokasipbjfk) === filterLokasi.value
    const matchesJenis = !filterJenis.value || String(row.jenispbjfk) === filterJenis.value
    const matchesHps = !filterHps.value
      || (filterHps.value === 'sudah' && Boolean(row.nomorhps))
      || (filterHps.value === 'belum' && !row.nomorhps)

    return matchesSearch && matchesLokasi && matchesJenis && matchesHps
  })
})

const hasFilter = computed(() => Boolean(search.value || filterLokasi.value || filterJenis.value || filterHps.value))
const pilihanLokasiPp = computed(() => uniqueOptions(ppRows.value, 'lokasipbjfk', 'lokasipbj'))
const filteredPp = computed(() => {
  const keyword = ppSearch.value.trim().toLocaleLowerCase('id')
  return ppRows.value.filter((row) => {
    const searchable = [row.nosuratpbj, row.nomorhps, row.nomorpp, row.judulpermintaan, row.namalengkap, row.nopr]
      .filter(Boolean)
      .join(' ')
      .toLocaleLowerCase('id')
    const matchesSearch = !keyword || searchable.includes(keyword)
    const matchesLocation = !ppFilterLokasi.value || String(row.lokasipbjfk) === ppFilterLokasi.value
    const datesComplete = Boolean(row.tanggalpp && row.bataspemasukan)
    const matchesStatus = !ppFilterStatus.value
      || (ppFilterStatus.value === 'tanggal' && datesComplete)
      || (ppFilterStatus.value === 'upload' && Boolean(row.siapbapp))
      || (ppFilterStatus.value === 'belum' && !datesComplete && !row.siapbapp)
    return matchesSearch && matchesLocation && matchesStatus
  })
})
const ppStepOneComplete = computed(() => Boolean(selectedPp.value?.tanggalpp && selectedPp.value?.bataspemasukan))
const pilihanLokasiBapp = computed(() => uniqueOptions(bappRows.value, 'lokasipbjfk', 'lokasipbj'))
const filteredBapp = computed(() => {
  const keyword = bappSearch.value.trim().toLocaleLowerCase('id')
  return bappRows.value.filter((row) => {
    const searchable = [row.nosuratpbj, row.nomorpp, row.nomorbapp, row.judulpermintaan, row.namalengkap]
      .filter(Boolean)
      .join(' ')
      .toLocaleLowerCase('id')
    const matchesSearch = !keyword || searchable.includes(keyword)
    const matchesLocation = !bappFilterLokasi.value
      || String(row.lokasipbjfk) === bappFilterLokasi.value
    const matchesStatus = !bappFilterStatus.value
      || (bappFilterStatus.value === 'tanggal' && Boolean(row.tanggalbapp))
      || (bappFilterStatus.value === 'penyedia' && Number(row.jumlahpenyedia || 0) > 0)
      || (bappFilterStatus.value === 'belum' && !row.tanggalbapp && !Number(row.jumlahpenyedia || 0))
    return matchesSearch && matchesLocation && matchesStatus
  })
})

const nilaiAngka = (value: any): number => {
  if (typeof value === 'number') return Number.isFinite(value) ? value : 0
  const raw = String(value ?? '').trim()
  if (/^-?\d+\.\d{1,2}$/.test(raw)) {
    const decimal = Number(raw)
    return Number.isFinite(decimal) ? decimal : 0
  }
  const digits = raw.replace(/[^0-9-]/g, '')
  const number = Number(digits)
  return Number.isFinite(number) ? number : 0
}

const totalItem = (row: any) => nilaiAngka(row?.banyak) * nilaiAngka(row?.hargasatuan)

const totalTerpilih = computed(() => (selectedPbj.value?.detailList || [])
  .reduce((total: number, row: any) => total + totalItem(row), 0))

const formatRupiah = (value: any) => `Rp ${new Intl.NumberFormat('id-ID').format(nilaiAngka(value))}`

const parseBappProviderPrice = (value: any) => {
  return Math.round(Math.max(0, nilaiAngka(value)))
}

const formatPlainRupiah = (value: any) => {
  const amount = parseBappProviderPrice(value)
  return amount ? new Intl.NumberFormat('id-ID').format(amount) : ''
}

const formatBappProviderPrice = (event: Event) => {
  const target = event.target as HTMLInputElement
  bappProviderForm.value.totalharga = formatPlainRupiah(target.value)
}

const formatTanggal = (value: any) => {
  if (!value) return '-'
  const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/)
  if (!match) return String(value)
  const date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])))
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
  }).format(date)
}

const formatTanggalWaktu = (value: any) => {
  if (!value) return '-'
  const normalized = String(value).replace(' ', 'T')
  const date = new Date(normalized)
  if (Number.isNaN(date.getTime())) return String(value)
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const formatFileSize = (value: any) => {
  const bytes = Number(value || 0)
  if (!bytes) return '0 KB'
  if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

const dateInputValue = (value: any) => value ? String(value).slice(0, 10) : ''

const nomorHpsPreview = computed(() => {
  const nourut = Number(hpsNumberDraft.value.nourut)
  const tahun = Number(hpsNumberDraft.value.tahun) || new Date().getFullYear()
  if (!Number.isInteger(nourut) || nourut < 1) return `HTD.HPS/-/UMRO/${tahun}`
  return `HTD.HPS/${String(nourut).padStart(3, '0')}/UMRO/${tahun}`
})

const selectPbj = (row: any) => {
  selectedPbj.value = row
}

const resetFilter = () => {
  search.value = ''
  filterLokasi.value = ''
  filterJenis.value = ''
  filterHps.value = ''
}

const resetPpFilter = () => {
  ppSearch.value = ''
  ppFilterLokasi.value = ''
  ppFilterStatus.value = ''
}

const resetBappFilter = () => {
  bappSearch.value = ''
  bappFilterLokasi.value = ''
  bappFilterStatus.value = ''
}

const resetBappProviderForm = () => {
  bappProviderForm.value = {
    norec: '',
    namapenyedia: '',
    totalharga: '',
    keterangan: 'Harga tertera termasuk pajak berlaku',
  }
}

const syncBappForm = (row: any) => {
  bappForm.value = {
    tanggalbapp: dateInputValue(row?.tanggalbapp || row?.bataspemasukan),
  }
  resetBappProviderForm()
}

const selectBapp = (row: any) => {
  selectedBapp.value = row
  syncBappForm(row)
}

const syncPpForm = (row: any) => {
  ppForm.value = {
    tanggalpp: dateInputValue(row?.tanggalpp),
    bataspemasukan: dateInputValue(row?.bataspemasukan),
  }
  ppSelectedFiles.value = []
  ppUploader.value?.clear?.()
  ppUploaderKey.value += 1
}

const selectPp = (row: any) => {
  selectedPp.value = row
  syncPpForm(row)
}

const loadHps = async () => {
  loading.value = true
  const selectedNorec = selectedPbj.value?.norec
  try {
    const response = await useApi().get('pbj/pengadaan/hps')
    rows.value = (Array.isArray(response) ? response : []).map((row: any) => ({
      ...row,
      detailList: Array.isArray(row.detailList) ? row.detailList : [],
      dataListIht: Array.isArray(row.dataListIht) ? row.dataListIht : [],
    }))
    selectedPbj.value = rows.value.find((row) => row.norec === selectedNorec) || rows.value[0] || null
  } catch (error: any) {
    rows.value = []
    selectedPbj.value = null
    H.alert('error', error?.response?.data?.metaData?.message || 'Data HPS Pengadaan gagal dimuat.')
  } finally {
    loading.value = false
  }
}

const loadPp = async () => {
  ppLoading.value = true
  const selectedNorec = selectedPp.value?.norec
  try {
    const response = await useApi().get('pbj/pengadaan/pp')
    ppRows.value = (Array.isArray(response) ? response : []).map((row: any) => ({
      ...row,
      detailList: Array.isArray(row.detailList) ? row.detailList : [],
      fileList: Array.isArray(row.fileList) ? row.fileList : [],
      jumlahfilepp: Number(row.jumlahfilepp || 0),
      siapbapp: Boolean(row.siapbapp),
    }))
    selectedPp.value = ppRows.value.find((row) => row.norec === selectedNorec) || ppRows.value[0] || null
    syncPpForm(selectedPp.value)
    ppLoaded.value = true
  } catch (error: any) {
    ppRows.value = []
    selectedPp.value = null
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'Data PP gagal dimuat.')
  } finally {
    ppLoading.value = false
  }
}

const loadBapp = async () => {
  bappLoading.value = true
  const selectedNorec = selectedBapp.value?.norec
  try {
    const response = await useApi().get('pbj/pengadaan/bapp')
    bappRows.value = (Array.isArray(response) ? response : []).map((row: any) => ({
      ...row,
      penyediaList: Array.isArray(row.penyediaList) ? row.penyediaList : [],
      jumlahfilepp: Number(row.jumlahfilepp || 0),
      jumlahpenyedia: Number(row.jumlahpenyedia || 0),
    }))
    selectedBapp.value = bappRows.value.find((row) => row.norec === selectedNorec)
      || bappRows.value[0]
      || null
    syncBappForm(selectedBapp.value)
    bappLoaded.value = true
  } catch (error: any) {
    bappRows.value = []
    selectedBapp.value = null
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'Data BAPP gagal dimuat.')
  } finally {
    bappLoading.value = false
  }
}

const cetakPbj = (row: any) => {
  H.printBlade(`pbj/cetak-pbj?pdf=true&norec=${row.norec}`)
}

const cetakIht = (row: any) => {
  H.printBlade(`pbj/cetak-pbj-iht?norec=${row.norec}`)
}

const openIht = (row: any) => {
  const iht = Array.isArray(row.dataListIht) ? row.dataListIht : []
  if (!iht.length) {
    H.alert('warning', 'PBJ ini belum memiliki file Informasi Harga.')
    return
  }
  if (iht.length === 1) {
    cetakIht(iht[0])
    return
  }
  selectedIht.value = iht
  ihtModalOpen.value = true
}

const cetakHps = (row: any) => {
  if (!row?.norec) return
  H.printBlade(`pbj/pengadaan/hps/cetak?pdf=true&norec=${row.norec}&_=${Date.now()}`)
}

const cetakPp = (row: any) => {
  if (!row?.norec) return
  H.printBlade(`pbj/pengadaan/pp/cetak?pdf=true&norec=${row.norec}&_=${Date.now()}`)
}

const cetakBapp = (row: any) => {
  if (!row?.norec) return
  H.printBlade(`pbj/pengadaan/bapp/cetak?pdf=true&norec=${row.norec}&_=${Date.now()}`)
}

const savePpDates = async () => {
  if (!selectedPp.value?.norec) return
  if (!ppForm.value.tanggalpp || !ppForm.value.bataspemasukan) {
    H.alert('warning', 'Tanggal Permintaan Penawaran dan batas pemasukan wajib diisi untuk menyelesaikan tahap 1.')
    return
  }
  if (ppForm.value.bataspemasukan < ppForm.value.tanggalpp) {
    H.alert('warning', 'Batas pemasukan penawaran tidak boleh lebih awal dari tanggal PP.')
    return
  }

  ppSaving.value = true
  try {
    const response = await useApi().post('pbj/pengadaan/pp', {
      norec: selectedPp.value.norec,
      tanggalpp: ppForm.value.tanggalpp,
      bataspemasukan: ppForm.value.bataspemasukan,
    })
    Object.assign(selectedPp.value, {
      norecpp: response?.norec,
      nomorpp: response?.nomorpp || selectedPp.value.nomorpp,
      tanggalpp: response?.tanggalpp,
      bataspemasukan: response?.bataspemasukan,
    })
    syncPpForm(selectedPp.value)
    H.alert('success', 'Tanggal Permintaan Penawaran berhasil disimpan.')
  } catch (error: any) {
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'Tanggal PP gagal disimpan.')
  } finally {
    ppSaving.value = false
  }
}

const onPpFileSelect = (event: any) => {
  ppSelectedFiles.value = Array.from(event?.files || [])
}

const onPpFileRemove = (event: any) => {
  ppSelectedFiles.value = ppSelectedFiles.value.filter((file) => file !== event?.file)
}

const uploadPpFiles = async () => {
  if (!selectedPp.value?.norec || !ppSelectedFiles.value.length) return
  const formData = new FormData()
  formData.append('norec', selectedPp.value.norec)
  ppSelectedFiles.value.forEach((file) => formData.append('files[]', file))

  ppUploading.value = true
  try {
    await useApi().post('pbj/pengadaan/pp/upload', formData)
    ppSelectedFiles.value = []
    ppUploader.value?.clear?.()
    ppUploaderKey.value += 1
    await loadPp()
    if (bappLoaded.value) await loadBapp()
  } catch (error: any) {
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'File PP gagal diupload.')
  } finally {
    ppUploading.value = false
  }
}

const downloadPpFile = (file: any) => {
  if (!file?.norec) return
  H.printBlade(`pbj/pengadaan/pp/file?norec=${encodeURIComponent(file.norec)}`)
}

const viewPpFile = (file: any) => {
  if (!file?.norec) return
  H.printBlade(`pbj/pengadaan/pp/file/view?norec=${encodeURIComponent(file.norec)}`)
}

const confirmDeletePpFile = (file: any) => {
  ppFileDeleteTarget.value = file
  ppFileDeleteModalOpen.value = true
}

const deletePpFile = async () => {
  if (!ppFileDeleteTarget.value?.norec) return
  ppFileDeleting.value = true
  try {
    await useApi().post('pbj/pengadaan/pp/file/hapus', {
      norec: ppFileDeleteTarget.value.norec,
    })
    ppFileDeleteModalOpen.value = false
    ppFileDeleteTarget.value = null
    await loadPp()
    if (bappLoaded.value) await loadBapp()
    H.alert('success', 'File PP berhasil dihapus dari riwayat upload.')
  } catch (error: any) {
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'File PP gagal dihapus.')
  } finally {
    ppFileDeleting.value = false
  }
}

const saveBappDate = async () => {
  if (!selectedBapp.value?.norec) return
  if (!bappForm.value.tanggalbapp) {
    H.alert('warning', 'Tanggal BAPP wajib diisi sebelum disimpan.')
    return
  }

  bappSaving.value = true
  try {
    const response = await useApi().post('pbj/pengadaan/bapp', {
      norec: selectedBapp.value.norec,
      tanggalbapp: bappForm.value.tanggalbapp,
    })
    Object.assign(selectedBapp.value, {
      norecbapp: response?.norec,
      nomorbapp: response?.nomorbapp || selectedBapp.value.nomorbapp,
      tanggalbapp: response?.tanggalbapp,
    })
    syncBappForm(selectedBapp.value)
    H.alert('success', 'Tanggal BAPP berhasil disimpan.')
  } catch (error: any) {
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'Tanggal BAPP gagal disimpan.')
  } finally {
    bappSaving.value = false
  }
}

const openBappProviderModal = (row?: any) => {
  resetBappProviderForm()
  bappProviderEditing.value = Boolean(row?.norec)
  if (row?.norec) {
    bappProviderForm.value = {
      norec: row.norec,
      namapenyedia: String(row.namapenyedia || ''),
      totalharga: formatPlainRupiah(row.totalharga),
      keterangan: String(row.keterangan || ''),
    }
  }
  bappProviderModalOpen.value = true
}

const closeBappProviderModal = () => {
  bappProviderModalOpen.value = false
  bappProviderEditing.value = false
  resetBappProviderForm()
}

const saveBappProvider = async () => {
  if (!selectedBapp.value?.norec) return
  const nama = bappProviderForm.value.namapenyedia.trim()
  const total = parseBappProviderPrice(bappProviderForm.value.totalharga)
  if (!nama) {
    H.alert('warning', 'Nama penyedia barang/jasa wajib diisi.')
    return
  }
  if (!Number.isFinite(total) || total < 0) {
    H.alert('warning', 'Total harga penawaran harus berupa angka minimal 0.')
    return
  }

  bappProviderSaving.value = true
  try {
    await useApi().post('pbj/pengadaan/bapp/penyedia', {
      norec: selectedBapp.value.norec,
      norecpenyedia: bappProviderForm.value.norec || null,
      namapenyedia: nama,
      totalharga: total,
      keterangan: bappProviderForm.value.keterangan.trim() || null,
    })
    const message = bappProviderEditing.value
      ? 'Data penyedia berhasil diperbarui.'
      : 'Penyedia berhasil ditambahkan ke daftar BAPP.'
    closeBappProviderModal()
    await loadBapp()
    H.alert('success', message)
  } catch (error: any) {
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'Data penyedia gagal disimpan.')
  } finally {
    bappProviderSaving.value = false
  }
}

const confirmDeleteBappProvider = (row: any) => {
  bappDeleteTarget.value = row
  bappDeleteModalOpen.value = true
}

const deleteBappProvider = async () => {
  if (!bappDeleteTarget.value?.norec) return
  bappProviderDeleting.value = true
  try {
    await useApi().post('pbj/pengadaan/bapp/penyedia/hapus', {
      norec: bappDeleteTarget.value.norec,
    })
    bappDeleteModalOpen.value = false
    bappDeleteTarget.value = null
    await loadBapp()
    H.alert('success', 'Penyedia berhasil dihapus dari daftar BAPP.')
  } catch (error: any) {
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'Penyedia gagal dihapus.')
  } finally {
    bappProviderDeleting.value = false
  }
}

const openHpsNumberModal = async (row: any) => {
  if (!row?.norec) return
  hpsNumberModalOpen.value = true
  hpsNumberLoading.value = true
  try {
    const response = await useApi().get(`pbj/pengadaan/hps/nomor-next?norec=${encodeURIComponent(row.norec)}`)
    hpsNumberDraft.value = {
      norec: row.norec,
      nourut: Number(response?.nourut || 0),
      tahun: Number(response?.tahun || new Date().getFullYear()),
      nomorhps: response?.nomorhps || '',
      tanggalhps: response?.tanggalhps || row.tglpr || '',
      existing: Boolean(response?.existing),
    }
  } catch (error: any) {
    hpsNumberModalOpen.value = false
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'Nomor HPS berikutnya gagal dimuat.')
  } finally {
    hpsNumberLoading.value = false
  }
}

const saveHpsNumber = async () => {
  const nourut = Number(hpsNumberDraft.value.nourut)
  if (!Number.isInteger(nourut) || nourut < 1) {
    H.alert('warning', 'Urutan nomor HPS harus berupa bilangan bulat minimal 1.')
    return
  }

  hpsNumberSaving.value = true
  try {
    const response = await useApi().post('pbj/pengadaan/hps/nomor', {
      norec: hpsNumberDraft.value.norec,
      nourut,
    })
    const row = rows.value.find((item) => item.norec === hpsNumberDraft.value.norec)
    if (row) {
      Object.assign(row, {
        nomorhps: response?.nomorhps,
        nomoruruthps: response?.nourut,
        tahunhps: response?.tahun,
        tanggalhps: response?.tanggalhps,
      })
      selectedPbj.value = row
    }
    hpsNumberModalOpen.value = false
    H.alert('success', `Nomor HPS ${response?.nomorhps || nomorHpsPreview.value} berhasil disimpan.`)
    await loadPp()
  } catch (error: any) {
    H.alert('error', error?.response?.data?.metaData?.message || error?.message || error || 'Nomor HPS gagal disimpan.')
  } finally {
    hpsNumberSaving.value = false
  }
}

loadHps()
loadPp()
if (activeTab.value === 'bapp') {
  loadBapp()
}

watch(activeTab, (tab) => {
  if (typeof window !== 'undefined' && allowedTabs.has(tab)) {
    window.localStorage.setItem(activeTabStorageKey, tab)
  }
  if (tab === 'pp' && !ppLoaded.value && !ppLoading.value) {
    loadPp()
  }
  if (tab === 'bapp' && !bappLoaded.value && !bappLoading.value) {
    loadBapp()
  }
})
</script>

<style lang="scss" scoped>
@import '/@src/scss/abstracts/all';

.pengadaan-page {
  --kp-border: #e5e7eb;
  --kp-muted: #6b7280;
  --kp-soft: #f7f8fa;
}

.page-heading {
  padding: 20px 22px;
  border: 1px solid var(--kp-border);
  border-radius: 10px;
  box-shadow: none;

  .title {
    font-weight: 700;
  }

  .subtitle {
    margin-top: 0 !important;
    line-height: 1.4;
  }
}

.tabs-card {
  padding: 0;
  border: 1px solid var(--kp-border);
  border-radius: 10px;
  box-shadow: 0 4px 14px rgb(31 41 55 / 6%);
  overflow: hidden;
}

.document-tabs-heading {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px 13px;

  h4 {
    margin: 0;
    color: var(--dark-text);
    font-size: 0.98rem;
    font-weight: 700;
  }

  p {
    margin: 2px 0 0;
    color: var(--kp-muted);
    font-size: 0.8rem;
  }
}

.document-tabs-icon {
  display: inline-flex;
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: #eaf5f0;
  color: var(--primary);
  font-size: 1.05rem;
}

.tabs-card :deep(.tabs-wrapper > .tabs-inner) {
  padding: 10px 14px 0;
  border-top: 1px solid #e1e6eb;
  border-bottom: 1px solid #ccd5de;
  background: #f4f6f8;
}

.tabs-card :deep(.tabs) {
  margin-bottom: -1px;
}

.tabs-card :deep(.tabs ul) {
  flex-wrap: wrap;
  gap: 8px;
  border-bottom: 0;
}

.tabs-card :deep(.tabs li) {
  min-width: 135px;
  flex: 1 1 135px;
}

.tabs-card :deep(.tabs li a) {
  min-height: 50px;
  justify-content: center;
  gap: 7px;
  border: 1px solid #ccd5de !important;
  border-bottom-color: #b9c4ce !important;
  border-radius: 8px 8px 0 0;
  background: #fff;
  color: #5f6c7b;
  font-size: 0.82rem;
  font-weight: 600;
}

.tabs-card :deep(.tabs li.is-active a) {
  border-color: var(--primary) !important;
  border-bottom-color: #fff !important;
  background: #fff;
  box-shadow: inset 0 3px 0 var(--primary);
  color: var(--primary);
  font-weight: 700;
}

.hps-tab {
  padding: 14px 16px 18px;
  background: #f5f7f9;
}

.workspace-columns {
  margin-top: 0;
  align-items: stretch;
}

.detail-column {
  padding-right: 20px;
}

.queue-column {
  padding-left: 20px;
  border-left: 2px solid #cbd5df;
}

.detail-panel,
.queue-panel {
  height: 100%;
  min-height: 650px;
  border: 1px solid #d7dee5;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 3px 10px rgb(31 41 55 / 7%);
}

.detail-panel {
  padding: 20px;
  border-top: 3px solid #2b6f9f;
}

.queue-panel {
  padding: 18px;
  border-top: 3px solid var(--primary);
}

.detail-heading,
.queue-heading,
.items-heading,
.queue-summary,
.hps-number-strip,
.queue-meta,
.queue-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.detail-heading {
  align-items: flex-start;
  gap: 18px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--kp-border);
}

.detail-heading-copy {
  min-width: 0;
}

.eyebrow {
  margin-bottom: 5px;
  color: var(--kp-muted);
  font-size: 0.75rem;
  font-weight: 500;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.detail-title {
  margin: 0;
  color: var(--dark-text);
  font-size: 1.15rem;
  font-weight: 700;
  line-height: 1.35;
}

.detail-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  margin-top: 10px;
}

.print-actions {
  display: flex;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.hps-number-strip {
  gap: 10px;
  margin-top: 16px;
  padding: 10px 12px;
  border: 1px solid #bfe3d4;
  border-radius: 7px;
  background: #f2faf6;
  color: #246b50;
  font-size: 0.86rem;

  span:nth-child(2) {
    flex: 1;
    font-weight: 600;
  }
}

.hps-number-modal {
  padding: 4px 2px 2px;
}

.hps-number-modal-intro {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  border: 1px solid #cce8db;
  border-radius: 10px;
  background: #f2faf6;
}

.hps-number-modal-icon {
  display: inline-flex;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  background: #d8f1e5;
  color: var(--primary);
  font-size: 1.15rem;
}

.hps-number-modal-intro h4 {
  margin: 0 0 4px;
  color: var(--dark-text);
  font-size: 1.08rem;
  font-weight: 700;
}

.hps-number-modal-intro p,
.hps-number-help {
  margin: 0;
  color: var(--kp-muted);
  font-size: 0.86rem;
  line-height: 1.45;
}

.hps-number-loading {
  padding: 22px 4px 10px;
}

.hps-number-current {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 15px;
  padding: 10px 12px;
  border-radius: 8px;
  background: #f5f7f9;
  color: var(--kp-muted);
  font-size: 0.86rem;
}

.hps-number-current strong {
  color: var(--dark-text);
  font-size: 0.94rem;
}

.hps-number-field {
  margin-top: 18px;
}

.hps-number-input {
  height: 54px;
  font-size: 1.35rem !important;
  font-weight: 700;
  text-align: center;
}

.hps-number-preview {
  margin-top: 14px;
  padding: 13px 14px;
  border: 1px dashed #9ed7bf;
  border-radius: 9px;
  background: #f8fcfa;
}

.hps-number-preview span {
  display: block;
  color: var(--kp-muted);
  font-size: 0.79rem;
}

.hps-number-preview strong {
  display: block;
  margin-top: 4px;
  color: var(--primary);
  font-size: 1.18rem;
  letter-spacing: 0.02em;
}

.hps-number-help {
  margin-top: 10px;
}

.pp-detail-panel {
  border-top-color: #345b99;
}

.pp-queue-panel {
  border-top-color: #345b99;
}

.pp-document-strip {
  display: grid;
  grid-template-columns: 1.3fr 1fr 0.8fr;
  gap: 10px;
  margin-top: 16px;
  padding: 12px;
  border: 1px solid #cbd9e8;
  border-radius: 9px;
  background: #f3f7fb;

  > div {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 3px;
    padding: 0 10px;
    border-right: 1px solid #d7e1eb;

    &:last-child {
      border-right: 0;
    }
  }

  span {
    color: var(--kp-muted);
    font-size: 0.69rem;
  }

  strong {
    color: #234d70;
    font-size: 0.88rem;
    overflow-wrap: anywhere;
  }
}

.pp-timeline {
  margin-top: 22px;
}

.pp-step {
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr);
}

.pp-step-rail {
  display: flex;
  align-items: center;
  flex-direction: column;
}

.pp-step-dot {
  display: inline-flex;
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  align-items: center;
  justify-content: center;
  border: 2px solid #9dafc0;
  border-radius: 50%;
  background: #fff;
  color: #5f7182;
  font-size: 0.82rem;
  font-weight: 700;
}

.pp-step-line {
  width: 2px;
  min-height: 34px;
  flex: 1;
  background: #cbd5df;
}

.pp-step.completed .pp-step-dot {
  border-color: var(--primary);
  background: var(--primary);
  color: #fff;
}

.pp-step.completed .pp-step-line {
  background: #73b99f;
}

.pp-step-content {
  min-width: 0;
  padding: 0 0 18px 4px;
}

.pp-step-card {
  padding: 16px;
  border: 1px solid #dbe2e9;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgb(31 41 55 / 6%);
}

.pp-step.disabled .pp-step-card {
  background: #fafbfc;
}

.pp-step-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;

  h5 {
    margin: 1px 0 3px;
    color: var(--dark-text);
    font-size: 0.98rem;
    font-weight: 700;
  }

  p {
    margin: 0;
    color: var(--kp-muted);
    font-size: 0.78rem;
    line-height: 1.4;
  }
}

.pp-step-kicker {
  color: #345b99;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.pp-date-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 16px;
}

.pp-date-label {
  display: block;
  width: 100%;
  margin-bottom: 7px;
  color: var(--dark-text);
  font-size: 0.82rem;
  font-weight: 700;
  line-height: 1.35;
  white-space: normal;
}

.bapp-detail-panel,
.bapp-queue-panel {
  border-top-color: #6c5b9b;
}

.bapp-document-strip {
  grid-template-columns: 1.25fr 0.8fr 1.1fr 0.55fr;
}

.bapp-readonly-input {
  background: #f5f7f9;
  color: #4b5563;
}

.bapp-provider-modal-form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 16px;
}

.bapp-provider-note {
  grid-column: 1 / -1;
}

.bapp-provider-heading-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 9px;
}

.bapp-provider-table {
  margin-top: 16px;
}

.bapp-table-actions,
.pp-file-actions {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  gap: 7px;
}

.bapp-provider-modal-intro {
  display: flex;
  align-items: flex-start;
  gap: 13px;
  padding: 3px 1px 12px;
  border-bottom: 1px solid var(--kp-border);

  h4 {
    margin: 0 0 5px;
    color: var(--dark-text);
    font-size: 1.05rem;
    font-weight: 700;
  }

  p {
    margin: 0;
    color: var(--kp-muted);
    font-size: 0.84rem;
    line-height: 1.45;
  }
}

.bapp-provider-modal-icon {
  display: inline-flex;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  background: #e7eff7;
  color: #2b6f9f;
  font-size: 1.05rem;
}

.bapp-money-preview {
  margin: 6px 0 0;
  color: var(--kp-muted);
  font-size: 0.74rem;
}

.bapp-delete-confirm {
  display: flex;
  align-items: flex-start;
  gap: 13px;
  padding: 4px 2px;

  h4 {
    margin: 0 0 5px;
    color: var(--dark-text);
    font-size: 1rem;
    font-weight: 700;
  }

  p {
    margin: 0;
    color: var(--kp-muted);
    font-size: 0.84rem;
    line-height: 1.5;
  }
}

.bapp-delete-icon {
  display: inline-flex;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  background: #fce8eb;
  color: var(--danger);
  font-size: 1.05rem;
}

.pp-step-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-top: 6px;

  p {
    max-width: 320px;
    margin: 0;
    color: var(--kp-muted);
    font-size: 0.73rem;
  }

  > div {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
  }
}

.pp-step-lock {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-top: 15px;
  padding: 12px;
  border: 1px solid #ead9a5;
  border-radius: 8px;
  background: #fffaf0;
  color: #8a681f;
  font-size: 0.8rem;
}

.pp-upload-action {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 10px;
  color: var(--kp-muted);
  font-size: 0.78rem;
}

.pp-file-history {
  margin-top: 18px;
  padding-top: 15px;
  border-top: 1px solid var(--kp-border);
}

.pp-file-history-title {
  h6 {
    margin: 0;
    color: var(--dark-text);
    font-size: 0.88rem;
    font-weight: 700;
  }

  p {
    margin: 2px 0 0;
    color: var(--kp-muted);
    font-size: 0.73rem;
  }
}

.pp-file-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 11px;
}

.pp-file-row {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border: 1px solid #dfe5eb;
  border-radius: 8px;
  background: #f8fafb;
}

.pp-file-icon {
  display: inline-flex;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  align-items: center;
  justify-content: center;
  border-radius: 7px;
  background: #e7eff7;
  color: #345b99;
}

.pp-file-copy {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
  gap: 2px;

  strong {
    color: var(--dark-text);
    font-size: 0.8rem;
    overflow-wrap: anywhere;
  }

  span {
    color: var(--kp-muted);
    font-size: 0.69rem;
  }
}

.pp-file-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 11px;
  padding: 13px;
  border-radius: 8px;
  background: #f5f7f9;
  color: var(--kp-muted);
  font-size: 0.78rem;
}

.pp-queue-item {
  padding: 13px;
}

.pp-queue-identities {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;

  > div {
    min-width: 0;
    padding: 9px;
    border-radius: 7px;
    background: #f1f5f8;
  }

  span,
  strong {
    display: block;
  }

  span {
    color: var(--kp-muted);
    font-size: 0.64rem;
  }

  strong {
    margin-top: 2px;
    color: #1f4f74;
    font-size: 0.84rem;
    font-weight: 700;
    overflow-wrap: anywhere;
  }
}

.pp-queue-item.selected .pp-queue-identities > div {
  background: #e7f4ee;
}

.section-title {
  margin: 22px 0 10px;
  color: var(--dark-text);
  font-size: 0.92rem;
  font-weight: 700;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 9px;
}

.info-item {
  display: flex;
  min-width: 0;
  min-height: 54px;
  align-items: center;
  gap: 9px;
  padding: 8px 10px;
  border: 0;
  border-radius: 8px;
  background: #f5f7f9;
}

.info-icon {
  display: inline-flex;
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  align-items: center;
  justify-content: center;
  border-radius: 7px;
  background: #e6eef4;
  color: #2b6f9f;
  font-size: 0.86rem;
}

.info-item-primary {
  background: #edf8f3;

  .info-icon {
    background: #d9f0e5;
    color: #23805d;
  }
}

.info-copy {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 2px;
}

.info-label {
  color: var(--kp-muted);
  font-size: 0.69rem;
}

.info-value {
  color: var(--dark-text);
  font-size: 0.81rem;
  line-height: 1.25;
  overflow-wrap: anywhere;
}

.no-pbj {
  color: var(--primary);
  font-size: 0.86rem;
  font-weight: 700;
}

.items-heading {
  align-items: flex-end;
  gap: 12px;
  margin-top: 4px;
}

.section-note {
  margin-top: 3px;
  color: var(--kp-muted);
  font-size: 0.8rem;
}

.estimated-total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  color: var(--kp-muted);
  font-size: 0.76rem;

  span:last-child {
    color: var(--dark-text);
    font-size: 0.96rem;
    font-weight: 700;
  }
}

.item-table {
  margin-top: 10px;
}

:deep(.item-table .p-datatable-wrapper),
:deep(.iht-table .p-datatable-wrapper) {
  border: 0;
  border-radius: 8px;
}

:deep(.item-table .p-datatable-thead > tr > th) {
  background: #f3f4f6;
  color: #374151;
  font-weight: 700;
}

:deep(.item-table .p-datatable-thead > tr > th),
:deep(.item-table .p-datatable-tbody > tr > td),
:deep(.iht-table .p-datatable-thead > tr > th),
:deep(.iht-table .p-datatable-tbody > tr > td) {
  border: 0 !important;
}

:deep(.item-table .p-datatable-tbody > tr > td) {
  color: #374151;
  vertical-align: top;
}

:deep(.item-table .p-datatable-tbody > tr:nth-child(even) > td),
:deep(.iht-table .p-datatable-tbody > tr:nth-child(even) > td) {
  background: #f8fafb;
}

.table-empty {
  padding: 20px;
  color: var(--kp-muted);
  text-align: center;
}

.queue-heading {
  align-items: flex-start;
  gap: 10px;

  h4 {
    margin: 0;
    color: var(--dark-text);
    font-size: 1rem;
    font-weight: 700;
  }
}

.queue-search {
  margin-top: 18px;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  margin-top: 9px;
}

.filter-status {
  grid-column: 1 / -1;
}

.queue-summary {
  min-height: 42px;
  color: var(--kp-muted);
  font-size: 0.8rem;
}

.clear-filter {
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--primary);
  cursor: pointer;
  font-family: inherit;
  font-size: 0.8rem;
}

.queue-list {
  display: flex;
  max-height: 670px;
  flex-direction: column;
  gap: 13px;
  padding: 2px 7px 5px 2px;
  overflow-y: auto;
}

.queue-item {
  width: 100%;
  padding: 14px 15px;
  border: 1px solid #d3dae2;
  border-left: 4px solid #b7c3ce;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 3px 9px rgb(31 41 55 / 8%);
  color: inherit;
  cursor: pointer;
  font-family: inherit;
  text-align: left;
  transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;

  &:hover {
    border-color: #9eacb9;
    border-left-color: #7f93a4;
    box-shadow: 0 5px 12px rgb(31 41 55 / 12%);
  }

  &.selected {
    border-color: #77bea4;
    border-left-color: var(--primary);
    background: #f4faf7;
    box-shadow: 0 4px 12px rgb(39 142 104 / 15%);
  }
}

.queue-number {
  color: #1f4f74;
  font-size: 0.92rem;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.queue-title {
  display: -webkit-box;
  margin-top: 5px;
  overflow: hidden;
  color: #374151;
  font-size: 0.84rem;
  line-height: 1.35;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.queue-meta {
  flex-wrap: wrap;
  justify-content: flex-start;
  gap: 6px 14px;
  margin-top: 9px;
  color: var(--kp-muted);
  font-size: 0.76rem;

  span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
}

.queue-foot {
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
  color: var(--kp-muted);
  font-size: 0.71rem;

  > :last-child {
    max-width: 76%;
    margin-left: auto;
    text-align: right;
    overflow-wrap: anywhere;
  }
}

.queue-skeleton {
  min-height: 116px;
  padding: 15px;
  border: 1px solid var(--kp-border);
  border-radius: 8px;
}

.empty-detail,
.empty-queue,
.coming-soon {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--kp-muted);
  text-align: center;
}

.empty-detail {
  min-height: 600px;
}

.empty-queue {
  min-height: 360px;
}

.empty-detail .iconify,
.empty-queue .iconify,
.coming-soon .iconify {
  margin-bottom: 12px;
  color: #9ca3af;
  font-size: 2rem;
}

.empty-detail h4,
.empty-queue h4,
.coming-soon h4 {
  margin: 0 0 5px;
  color: var(--dark-text);
  font-size: 1rem;
  font-weight: 700;
}

.empty-detail p,
.empty-queue p,
.coming-soon p {
  margin: 0;
  font-size: 0.86rem;
}

.coming-soon {
  min-height: 540px;
  padding: 32px;
}

.is-dark {
  .pengadaan-page {
    --kp-border: #303643;
    --kp-muted: #9ca3af;
    --kp-soft: #171b23;
  }

  .page-heading,
  .tabs-card,
  .detail-panel,
  .queue-panel,
  .queue-item {
    background: #171b23;
  }

  .document-tabs-icon,
  .info-item,
  .info-item-primary {
    background: #202630;
  }

  .info-icon,
  .info-item-primary .info-icon {
    background: #293442;
    color: #70b99e;
  }

  .hps-tab,
  .tabs-card :deep(.tabs-wrapper > .tabs-inner) {
    background: #11151c;
  }

  .tabs-card :deep(.tabs li a) {
    border-color: #3a4350 !important;
    background: #202630;
    color: #b7c0cc;
  }

  .tabs-card :deep(.tabs li.is-active a) {
    border-color: var(--primary) !important;
    border-bottom-color: #171b23 !important;
    background: #171b23;
    color: #70b99e;
  }

  .queue-item.selected,
  .hps-number-strip {
    background: #17251f;
  }

  .pp-document-strip,
  .pp-step-card,
  .pp-file-row,
  .pp-file-empty,
  .pp-queue-identities > div {
    border-color: #303946;
    background: #202630;
  }

  .pp-step-dot {
    background: #171b23;
  }

  .pp-step-lock {
    border-color: #6f5a2b;
    background: #2a2518;
    color: #e3c778;
  }

  .bapp-readonly-input {
    background: #202630;
    color: #cbd5e1;
  }

  .bapp-delete-icon {
    background: #382127;
  }

  :deep(.item-table .p-datatable-thead > tr > th) {
    background: #20252f;
    color: #d1d5db;
  }

  :deep(.item-table .p-datatable-tbody > tr > td),
  :deep(.iht-table .p-datatable-tbody > tr > td) {
    background: #171b23;
    color: #d1d5db;
  }

  :deep(.item-table .p-datatable-tbody > tr:nth-child(even) > td),
  :deep(.iht-table .p-datatable-tbody > tr:nth-child(even) > td) {
    background: #1d222c;
  }
}

@media (max-width: 1200px) {
  .detail-heading {
    flex-direction: column;
  }

  .print-actions {
    justify-content: flex-start;
  }

  .info-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .pp-step-actions {
    align-items: flex-start;
    flex-direction: column;
  }

  .pp-step-actions > div {
    justify-content: flex-start;
  }
}

@media (max-width: 768px) {
  .page-heading {
    padding: 16px;
  }

  .hps-tab {
    padding: 7px;
  }

  .document-tabs-heading {
    padding: 14px;
  }

  .tabs-card :deep(.tabs-wrapper > .tabs-inner) {
    padding: 9px;
  }

  .tabs-card :deep(.tabs ul) {
    flex-direction: column;
    gap: 6px;
  }

  .tabs-card :deep(.tabs li),
  .tabs-card :deep(.tabs li a) {
    width: 100%;
  }

  .tabs-card :deep(.tabs li a),
  .tabs-card :deep(.tabs li.is-active a) {
    min-height: 42px;
    justify-content: flex-start;
    border-radius: 7px;
    border-bottom-color: #ccd5de !important;
  }

  .detail-panel,
  .queue-panel {
    min-height: 0;
    padding: 14px;
  }

  .queue-panel {
    margin-bottom: 8px;
  }

  .detail-column,
  .queue-column {
    padding-right: 0.75rem;
    padding-left: 0.75rem;
  }

  .queue-column {
    border-left: 0;
    border-top: 2px solid #cbd5df;
  }

  .info-grid,
  .filter-grid,
  .pp-date-grid,
  .bapp-provider-modal-form,
  .pp-document-strip,
  .pp-queue-identities {
    grid-template-columns: 1fr;
  }

  .bapp-provider-note {
    grid-column: auto;
  }

  .pp-document-strip > div {
    padding: 5px 2px;
    border-right: 0;
    border-bottom: 1px solid #d7e1eb;

    &:last-child {
      border-bottom: 0;
    }
  }

  .pp-step {
    grid-template-columns: 34px minmax(0, 1fr);
  }

  .pp-step-heading,
  .pp-upload-action {
    align-items: flex-start;
    flex-direction: column;
  }

  .filter-status {
    grid-column: auto;
  }

  .hps-number-strip,
  .items-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .estimated-total {
    align-items: flex-start;
  }
}
</style>
