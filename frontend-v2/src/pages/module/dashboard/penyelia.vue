<template>
  <ConfirmDialog />
  <div class="business-dashboard hr-dashboard">
    <div class="columns">
      <div class="column is-8">
        <div class="columns is-multiline">
          <!--Header-->
          <div class="column is-12">
            <div class="illustration-header-2">
              <div class="header-image">
                <img src="/@src/assets/illustrations/dashboards/lifestyle/Picture5.png" alt=""
                  style="max-width:75%; margin-left: 2rem; margin-bottom: 1rem;" />
              </div>
              <div class="header-meta">
                <h3 style="color:white"><i class="fas fa-home"></i> Dashboard Penyelia</h3>
                <p>
                  Selamat Datang , {{ userLogin.pegawai.namaLengkap }}
                </p>
              </div>
            </div>
          </div>
          <div class="column is-12">
            <TabView class="tabview-custom " :scrollable="true" @tab-click="klikTab($event)">
              <TabPanel>
                <template #header>
                  <i class="fas fa-users mr-2" aria-hidden="true"></i>
                  <span>Daftar Alat</span>
                  <Badge :value="dataAlatKalibrasi.length" v-if="dataAlatKalibrasi.length > 0" severity="danger"
                    class="ml-2" />
                </template>
                <div v-if="activeTab == 0">
                  <div class="column is-6 date-filter-container">
                    <VDatePicker v-model="filterTgl" is-range color="pink" trim-weeks
                      @update:modelValue="onDateRangeUserChange">
                      <template #default="{ inputValue, inputEvents }">
                        <VField addons>
                          <VControl icon="feather:calendar">
                            <VInput :value="inputValue.start" v-on="inputEvents.start" />
                          </VControl>
                          <VControl>
                            <VButton static><i class="fas fa-arrow-right" aria-hidden="true"></i></VButton>
                          </VControl>
                          <VControl icon="feather:calendar">
                            <VInput :value="inputValue.end" v-on="inputEvents.end" />
                          </VControl>
                        </VField>
                      </template>
                    </VDatePicker>
                  </div>
                  <div class="list-view list-view-v3">
                    <div class="search-menu-igd mb-2">
                      <div class="search-location-igd" style="width: 100%">
                        <i class="iconify" data-icon="feather:search"></i>
                        <input type="text" placeholder="Cari Nama Alat, No Order Alat, No Pedaftaran"
                          v-model="item.qsearch" v-on:keyup.enter="fetchAlatKalibrasi(order)" />
                      </div>
                      <VButton raised class="search-button-igd" @click="fetchAlatKalibrasi(order)" :loading="isLoading">
                        Cari Data
                      </VButton>
                    </div>
                    <VCard class="text-center pt-0 pb-0 mt-0">
                      <VRadio v-model="order" value="0" label="Belum Verif" name="outlined_radio" color="success" />
                      <VRadio v-model="order" value="1" label="Sudah Verif" name="outlined_radio" color="info" />
                      <VRadio v-model="order" value="2" label="Sertifikat/Laporan Disetujui Penyelia"
                        name="outlined_radio" color="info" />
                    </VCard>
                    <VButton rounded color="info" class="mt-2 amandemen-btn" icon="feather:list" raised bold
                      @click="openPengajuan(item)">
                      <span>Daftar Pengajuan Amandemen</span>
                      <Badge v-if="pendingAmandemenCount > 0" :value="pendingAmandemenCount" severity="danger"
                        class="ml-2" />
                    </VButton>
                    <VPlaceholderPage :class="[dataAlatKalibrasi.length !== 0 && 'is-hidden']"
                      title="Tidak Ada Alat Hari Ini." subtitle="Silakan Pilih Tanggal" larger>
                      <template #image>
                        <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.png" alt="" />
                        <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg"
                          alt="" />
                      </template>
                    </VPlaceholderPage>
                    <div class="list-view-inner mt-2" style="max-height:1000px;overflow: auto;">
                      <div name="list-complete" tag="div">
                        <div v-for="(item, rowIndex) in dataAlatKalibrasi" :key="rowIndex">
                          <div v-if="rowGroupMetadata[item.lingkupkalibrasi].index === rowIndex">
                            <span style="font-weight: bold; font-size: 16px; font-family: var(--font-alt);">{{
                              item.lingkupkalibrasi }}</span>
                            <Badge :value="rowGroupMetadata[item.lingkupkalibrasi].size"
                              v-if="rowGroupMetadata[item.lingkupkalibrasi].size > 0" class="ml-2 mt-2-min" />
                          </div>
                          <div class="list-view-item"
                            :class="{ 'card-tolak-serti': item.istolakserti == true || item.istolaksertiasman == true || item.istolaksertimanager == true || item.istolakrepair == true || item.istolakrepairasman == true || item.istolakrepairmanager == true }">
                            <div class="list-view-item-inner">
                              <VAvatar size="small" :picture="getLingkupAvatar(item.lingkupkalibrasi).picture"
                                :color="getLingkupAvatar(item.lingkupkalibrasi).color" bordered />
                              <div class="meta-left">
                                <h3>
                                  <b>{{ item.namaproduk }}</b>
                                  <VTag class="ml-1 mb-2" v-if="item.pelaksanaveriffk == null"
                                    :label="'Belum Diverifikasi Pelaksana'" :color="'warning'" />
                                  <VTag v-if="item.isamandemen && item.statusamandemen == null" class="ml-1"
                                    :label="'Amandemen Diajukan'" :color="'warning'" />
                                  <VTag v-if="item.isamandemen && item.statusamandemen == 1" class="ml-1"
                                    :label="'Amandemen Disetujui Penyelia'" :color="'success'" />
                                  <VTag v-if="item.isamandemen && item.statusamandemen == 2" class="ml-1"
                                    :label="'Amandemen Ditolak Penyelia'" :color="'danger'" />
                                  <VTag v-if="item.isamandemen && item.statusamandemen == 3" class="ml-1"
                                    :label="'Amandemen Disetujui Asman'" :color="'success'" />
                                  <VTag v-if="item.isamandemen && item.statusamandemen == 4" class="ml-1"
                                    :label="'Amandemen Ditolak Asman'" :color="'danger'" />
                                  <VTag v-if="item.isamandemen && item.statusamandemen == 5" class="ml-1"
                                    :label="'Amandemen Disetujui Manager'" :color="'success'" />
                                  <VTag v-if="item.isamandemen && item.statusamandemen == 6" class="ml-1"
                                    :label="'Amandemen Ditolak Manager'" :color="'danger'" />
                                  <VTag v-if="item.tanda_kalibrasi_ai == 'ADA'" class="ml-1"
                                    :label="'Kalibrasi AI Tersedia'" :color="'success'" />
                                </h3>
                                <h5>Merk/Tipe : {{ item.namamerk }}/{{ item.namatipe }}</h5>
                                <h5>S/N : {{ item.namaserialnumber }}</h5>
                                <span>
                                  <i aria-hidden="true" class="iconify" data-icon="feather:calendar"></i>
                                  <span>{{ item.tglverifasman }}</span>
                                  <i aria-hidden="true" class="fas fa-circle icon-separator"></i>
                                  <i aria-hidden="true" class="iconify" data-icon="feather:check-circle"></i>
                                  <span>{{ item.nopendaftaran }}</span>
                                  <i aria-hidden="true" class="fas fa-circle icon-separator"></i>
                                  <i aria-hidden="true" class="iconify" data-icon="teenyicons:id-outline"></i>
                                  <span>{{ item.noorderalat }}</span>
                                </span>
                                <div>
                                  <VTag v-if="item.isstandarulab" :label="'Rekalibrasi Standar Internal'"
                                    :color="'warning'" />
                                  <VTag v-if="item.iskalibrasiinternal" :label="'Kalibrasi Internal'"
                                    :color="'warning'" />
                                  <VTag
                                    v-if="!item.isstandarulab && !item.iskalibrasiinternal && item.tanggalmulai != null && item.jenisorder == 'kalibrasi'"
                                    :label="'Tanggal Mulai : ' + item.tanggalmulai" :color="'warning'" />
                                  <VTag
                                    v-if="(item.tglisilembarkerjapelaksana == null && item.tglisilembarkerjapenyelia == null) && !item.isstandarulab && !item.iskalibrasiinternal && item.tanggalmulai && item.durasikalbrasi && item.jenisorder == 'kalibrasi'"
                                    :label="'Sisa Waktu: ' + getRemainingKalibrasiTime(item.tanggalmulai, item.durasikalbrasi)"
                                    color="danger" class="ml-2" />
                                  <VTag
                                    v-if="!item.isstandarulab && !item.iskalibrasiinternal && item.jenisorder == 'kalibrasi'"
                                    :label="'Durasi Kalibrasi : ' + item.durasikalbrasi" :color="'warning'"
                                    class="ml-2" />
                                  <VTag v-if="item.durasi_proses != null" :label="item.durasi_proses" color="info"
                                    class="ml-2" />
                                  <VTag
                                    v-if="item.pelaksanaisilembarkerjafk != null && (item.setujuilembarkerjapenyelia == null || item.setujuilembarkerjapenyelia == false) && item.isverifikasi == null"
                                    :label="'Sudah Isi Lembar Kerja'" :color="'info'" class="ml-2" />
                                  <VTag
                                    v-if="item.pelaksanaisilembarkerjafk != null && (item.setujuilembarkerjapenyelia == null || item.setujuilembarkerjapenyelia == false) && item.isverifikasi == true"
                                    :label="'Sudah Isi Laporan Verifikasi'" :color="'success'" class="ml-2" />
                                  <VTag v-if="item.pelaksanaisilaporanrepairfk != null"
                                    :label="'Sudah Isi Laporan Repair'" :color="'success'" class="ml-2" />
                                  <VTag
                                    v-if="item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && item.isverifikasi == null"
                                    :label="'Sertifikat Disetujui Penyelia'" :color="'primary'" class="ml-2" />
                                  <VTag v-if="item.penyeliasetujulaporanrepairfk != null"
                                    :label="'Laporan Repair Disetujui Penyelia'" :color="'success'" class="ml-2" />
                                  <VTag
                                    v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true && item.isverifikasi == null"
                                    :label="'Sertifikat Disetujui Asman'" :color="'primary'" class="ml-2" />
                                  <VTag
                                    v-if="item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && item.isverifikasi == true"
                                    :label="'Laporan Verifikasi Disetujui Penyelia'" :color="'primary'" class="ml-2" />
                                  <VTag
                                    v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true && item.isverifikasi == true"
                                    :label="'Laporan Verifikasi Disetujui Asman'" :color="'primary'" class="ml-2" />
                                  <VTag
                                    v-if="item.setujuilembarkerjamanager != null && item.setujuilembarkerjamanager == true && item.isverifikasi == null"
                                    :label="'Sertifikat Disetujui Manager'" :color="'primary'" class="ml-2" />
                                  <VTag
                                    v-if="item.setujuilembarkerjamanager != null && item.setujuilembarkerjamanager == true && item.isverifikasi == true"
                                    :label="'Laporan Verifikasi Disetujui Manager'" :color="'primary'" class="ml-2" />
                                  <VTag v-if="item.managersetujulaporanrepairfk != null"
                                    :label="'Laporan Repair Disetujui Manager'" :color="'info'" class="ml-2" />
                                </div>
                                <div>
                                  <span style="font-weight: bold;">Penyelia Teknik :
                                    {{ item.penyeliateknik ?? '-' }}
                                  </span>
                                </div>
                                <div>
                                  <span style="font-weight: bold;">Pelaksana Teknik :
                                    {{ item.pelaksanateknik ?? '-' }}
                                  </span>
                                </div>
                                <div v-if="item.jenisorder === 'kalibrasi'">
                                  <WorksheetAttachments :norec="item.norec_detail" role="penyelia" compact />
                                </div>
                                <div v-if="item.statusrepairfk == 2 && item.jenisorder === 'repair'"
                                  class="gagal-repair-anim">
                                  <svg viewBox="0 0 32 32" fill="none">
                                    <circle cx="16" cy="16" r="16" fill="#e57373" />
                                    <path d="M10 10L22 22M22 10L10 22" stroke="#fff" stroke-width="3.2"
                                      stroke-linecap="round" />
                                  </svg>
                                  GAGAL REPAIR
                                </div>
                                <div v-if="item.istolakserti === true" class="tolak-serti-anim"
                                  style="font-weight: bold;">
                                  <svg viewBox="0 0 32 32" fill="none">
                                    <circle cx="16" cy="16" r="16" fill="#e57373" />
                                    <path d="M10 10L22 22M22 10L10 22" stroke="#fff" stroke-width="3.2"
                                      stroke-linecap="round" />
                                  </svg>

                                  <div v-if="item.isverifikasi == true" class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">Laporan Verifikasi Ditolak Penyelia
                                    </div>
                                    <div class="tolak-serti-ket">
                                      Keterangan: {{ item.alasanpenolakanserti ?? '-' }}
                                    </div>
                                  </div>
                                  <div v-else class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">Sertifikat Ditolak Penyelia</div>
                                    <div class="tolak-serti-ket">
                                      Keterangan: {{ item.alasanpenolakanserti ?? '-' }}
                                    </div>
                                  </div>
                                </div>
                                <div v-if="item.tanggalmulaiestimasi != null" class="tolak-serti-anim"
                                  style="font-weight: bold;">
                                  <svg viewBox="0 0 32 32" fill="none">
                                    <circle cx="16" cy="16" r="16" fill="#e57373" />
                                    <path d="M10 10L22 22M22 10L10 22" stroke="#fff" stroke-width="3.2"
                                      stroke-linecap="round" />
                                  </svg>

                                  <div class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">ESTIMASI MULAI MAX : {{
                                      item.tanggalmulaiestimasi ??
                                      '-' }}</div>
                                    <div class="tolak-serti-ket">
                                      Segera Lakukan Verifikasi
                                    </div>
                                  </div>
                                </div>
                                <div v-if="item.istolaksertiasman === true" class="tolak-serti-anim"
                                  style="font-weight: bold;">
                                  <svg viewBox="0 0 32 32" fill="none">
                                    <circle cx="16" cy="16" r="16" fill="#e57373" />
                                    <path d="M10 10L22 22M22 10L10 22" stroke="#fff" stroke-width="3.2"
                                      stroke-linecap="round" />
                                  </svg>
                                  <div v-if="item.isverifikasi == true" class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">Laaporan Verifikasi Ditolak Asman
                                    </div>
                                    <div class="tolak-serti-ket">
                                      Keterangan: {{ item.alasanpenolakansertiasman ?? '-' }}
                                    </div>
                                  </div>
                                  <div v-else class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">Sertifikat Ditolak Asman</div>
                                    <div class="tolak-serti-ket">
                                      Keterangan: {{ item.alasanpenolakansertiasman ?? '-' }}
                                    </div>
                                  </div>
                                </div>
                                <div v-if="item.istolaksertimanager === true" class="tolak-serti-anim"
                                  style="font-weight: bold;">
                                  <svg viewBox="0 0 32 32" fill="none">
                                    <circle cx="16" cy="16" r="16" fill="#e57373" />
                                    <path d="M10 10L22 22M22 10L10 22" stroke="#fff" stroke-width="3.2"
                                      stroke-linecap="round" />
                                  </svg>
                                  <div v-if="item.isverifikasi == true" class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">Laporan Verifikasi Ditolak Manager
                                    </div>
                                    <div class="tolak-serti-ket">
                                      Keterangan: {{ item.alasanpenolakansertimanager ?? '-' }}
                                    </div>
                                  </div>
                                  <div v-else class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">Sertifikat Ditolak Manager</div>
                                    <div class="tolak-serti-ket">
                                      Keterangan: {{ item.alasanpenolakansertimanager ?? '-' }}
                                    </div>
                                  </div>
                                </div>
                                <div v-if="item.istolakrepair === true" class="tolak-serti-anim"
                                  style="font-weight: bold;">
                                  <svg viewBox="0 0 32 32" fill="none">
                                    <circle cx="16" cy="16" r="16" fill="#e57373" />
                                    <path d="M10 10L22 22M22 10L10 22" stroke="#fff" stroke-width="3.2"
                                      stroke-linecap="round" />
                                  </svg>

                                  <div class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">Laporan Repair Ditolak Penyelia</div>
                                    <div class="tolak-serti-ket">
                                      Keterangan: {{ item.alasanpenolakanrepair ?? '-' }}
                                    </div>
                                  </div>
                                </div>
                                <div v-if="item.istolakrepairasman === true" class="tolak-serti-anim"
                                  style="font-weight: bold;">
                                  <svg viewBox="0 0 32 32" fill="none">
                                    <circle cx="16" cy="16" r="16" fill="#e57373" />
                                    <path d="M10 10L22 22M22 10L10 22" stroke="#fff" stroke-width="3.2"
                                      stroke-linecap="round" />
                                  </svg>

                                  <div class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">Laporan Repair Ditolak Asman</div>
                                    <div class="tolak-serti-ket">
                                      Keterangan: {{ item.alasanpenolakanrepairasman ?? '-' }}
                                    </div>
                                  </div>
                                </div>
                                <div v-if="item.istolakrepairmanager === true" class="tolak-serti-anim"
                                  style="font-weight: bold;">
                                  <svg viewBox="0 0 32 32" fill="none">
                                    <circle cx="16" cy="16" r="16" fill="#e57373" />
                                    <path d="M10 10L22 22M22 10L10 22" stroke="#fff" stroke-width="3.2"
                                      stroke-linecap="round" />
                                  </svg>

                                  <div class="tolak-serti-text">
                                    <div class="tolak-serti-title tolak-serti-red">Laporan Repair Ditolak Manager</div>
                                    <div class="tolak-serti-ket">
                                      Keterangan: {{ item.alasanpenolakanrepairmanager ?? '-' }}
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div class="meta-right flex justify-center items-center">
                                <div class="buttons">
                                  <VIconButton v-tooltip.bottom.left="'SPK'" icon="feather:printer"
                                    @click="cetakSpk(item)" color="warning" raised circle class="mr-2">
                                  </VIconButton>
                                  <VIconButton
                                    v-if="item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && item.isverifikasi == null"
                                    v-tooltip.bottom.left="'Cetak Sertifikat'" icon="feather:printer"
                                    @click="cetakSertifikatLembarKerja(item)" color="info" raised circle class="mr-2">
                                  </VIconButton>
                                  <VIconButton
                                    v-if="item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && item.isverifikasi == true"
                                    v-tooltip.bottom.left="'Cetak Laporan Verifikasi'" icon="feather:printer"
                                    @click="cetakLaporanVerfikasi(item)" color="success" raised circle class="mr-2">
                                  </VIconButton>
                                  <VIconButton v-if="item.statusorderpenyelia == 2 && item.jenisorder == 'repair'"
                                    v-tooltip.bottom.left="'Cetak Laporan Repair'" icon="feather:printer"
                                    @click="cetakLaporanRepair(item)" color="success" raised circle class="mr-2">
                                  </VIconButton>
                                  <VIconButton
                                    v-if="item.statusorderpenyelia == 1 && item.jenisorder == 'repair' && item.istolakrepair == null"
                                    color="info" circle icon="fas fa-tools" outlined raised @click="laporanRepair(item)"
                                    v-tooltip.bottom.left="'Laporan Repair'" />
                                  <VIconButton
                                    v-if="item.sublingkupfk != null && item.lingkupfk != 2 && item.statusorderpenyelia == 1 && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                    color="info" circle icon="fas fa-pager" outlined raised
                                    @click="masukLembarKerjaSudahIsi(item)" v-tooltip.bottom.left="'Lembar Kerja'" />
                                  <VIconButton
                                    v-if="item.sublingkupfk == null && item.lingkupfk != 2 && item.statusorderpenyelia == 1 && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                    color="info" circle icon="fas fa-pager" outlined raised
                                    @click="masukLembarKerja(item)" v-tooltip.bottom.left="'Lembar Kerja'" />
                                  <VIconButton
                                    v-if="item.statuskanfk == null && item.lingkupfk == 2 && item.statusorderpenyelia == 1 && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                    color="info" circle icon="fas fa-pager" outlined raised
                                    @click="masukLembarKerjaTekanan(item)" v-tooltip.bottom.left="'Lembar Kerja'" />
                                  <VIconButton v-if="
                                    (item.statusorderpenyelia == 1 || item.statusorderpenyelia == 2) &&
                                    (item.setujuilembarkerjaasman == null || item.setujuilembarkerjaasman == false) &&
                                    item.jenisorder == 'kalibrasi' &&
                                    (
                                      (item.pelaksanaisilembarkerjafk == null && (item.isverifikasi == null || item.isverifikasi == true)) ||
                                      (item.pelaksanaisilembarkerjafk != null && item.isverifikasi == true)
                                    )
                                  " color="success" circle icon="fas fa-file" outlined raised
                                    @click="masukLaporanVerifikasi(item)"
                                    v-tooltip.bottom.left="'Laporan Verifikasi'" />
                                  <VIconButton
                                    v-if="item.statuskanfk != null && item.lingkupfk == 2 && item.statusorderpenyelia == 1 && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                    color="info" circle icon="fas fa-pager" outlined raised
                                    @click="lembarKerjaTekananSudahIsi(item)" v-tooltip.bottom.left="'Lembar Kerja'" />
                                  <VIconButton v-tooltip.bottom.left="'Verifikasi'" label="Bottom Left" color="primary"
                                    circle icon="pi pi-check-circle" v-if="item.statusorderpenyelia == 0"
                                    @click="orderVerify(item)" />
                                  <VIconButton v-tooltip.bottom.left="'Aktivitas'" icon="feather:activity"
                                    v-if="item.statusorderpenyelia == 1" @click="detailOrder(item)" color="info" raised
                                    circle class="mr-2">
                                  </VIconButton>
                                  <VIconButton
                                    v-if="item.tanda_kalibrasi_ai == 'ADA' && item.sublingkupfk == null && (item.statusorderpenyelia == 1 || item.statusorderpenyelia == 2) && (item.setujuilembarkerjaasman == null || item.setujuilembarkerjaasman == false) && item.jenisorder == 'kalibrasi'"
                                    color="info" circle icon="fas fa-robot" outlined raised
                                    @click="masukLembarKerjaAI(item)" v-tooltip.bottom.left="'Lembar Kerja AI'" />
                                  <VIconButton
                                    v-if="item.tanda_kalibrasi_ai == 'ADA' && item.sublingkupfk != null && (item.statusorderpenyelia == 1 || item.statusorderpenyelia == 2) && (item.setujuilembarkerjaasman == null || item.setujuilembarkerjaasman == false) && item.jenisorder == 'kalibrasi'"
                                    color="info" circle icon="fas fa-robot" outlined raised
                                    @click="masukLembarKerjaSudahIsi(item)" v-tooltip.bottom.left="'Lembar Kerja AI'" />
                                  <VIconButton v-tooltip.bottom.left="'Riwayat Amandemen'" icon="feather:repeat"
                                    v-if="item.versisertifikat > 1 || item.versilaporanrepair > 1"
                                    @click="riwayatAmandemen(item)" color="warning" raised circle class="mr-2">
                                  </VIconButton>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <VFlexPagination v-model:current-page="currentPage.page" :item-per-page="currentPage.limit"
                      :total-items="totalData" :max-links-displayed="5">
                      <template #before-pagination>
                      </template>
                      <template #before-navigation>
                        <VFlex class="mr-4 mt-1" column-gap="1rem">
                          <VField>
                          </VField>
                          <VField>
                            <VControl>
                              <div class="select is-rounded">
                                <select v-model="currentPage.limit">
                                  <option :value="1">1 results per page</option>
                                  <option :value="5">5 results per page</option>
                                  <option :value="10">10 results per page</option>
                                  <option :value="15">15 results per page</option>
                                  <option :value="25">25 results per page</option>
                                  <option :value="50">50 results per page</option>
                                </select>
                              </div>
                            </VControl>
                          </VField>
                        </VFlex>
                      </template>
                    </VFlexPagination>
                  </div>
                </div>
              </TabPanel>
            </TabView>
          </div>
        </div>
      </div>
      <div class="column is-4">
        <VCard class="sensor-card">
          <div class="sensor-card__header">
            <div>
              <h4 class="sensor-card__title">
                <i class="fas fa-microchip mr-2"></i> Sensor Ruangan
              </h4>
              <div class="sensor-card__meta">
                <VTag color="primary" rounded>Room #{{ sensor?.room_id ?? '-' }}</VTag>
              </div>
            </div>
            <div class="sensor-card__time">
              <i class="far fa-clock mr-1"></i>
              <span>{{ sensorUpdatedAt }}</span>
            </div>
          </div>

          <div v-if="sensor" class="sensor-grid">
            <div class="sensor-item" v-for="(m, idx) in sensorItems" :key="idx">
              <div class="sensor-item__icon">
                <i :class="m.icon"></i>
              </div>
              <div class="sensor-item__text">
                <div class="sensor-item__label">{{ m.label }}</div>
                <div class="sensor-item__value">
                  {{ m.value }} <span class="unit">{{ m.unit }}</span>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="sensor-empty">
            <VPlaceload class="mb-2" />
            <VPlaceloadText :lines="2" width="90%" last-line-width="40%" />
          </div>
        </VCard>
        <VCard class="mt-4">
          <div class="p-4">
            <div class="is-flex is-justify-content-space-between is-align-items-center">
              <h4 class="sensor-card__title">
                <i class="far fa-chart-line mr-2"></i> Grafik Sensor (≤10 terbaru)
              </h4>
            </div>

            <div v-if="chartLabels.length" class="columns is-multiline mt-3">
              <div class="column is-6" v-for="g in charts" :key="g.key">
                <div class="box" style="padding:12px">
                  <div class="is-flex is-justify-content-space-between is-align-items-center mb-2">
                    <strong>{{ g.title }}</strong>
                    <VTag rounded>{{ g.unit }}</VTag>
                  </div>
                  <ApexChart id="apex-chart-22" type="line" height="160" :options="g.options" :series="g.series" />
                </div>
              </div>
            </div>

            <div v-else class="has-text-centered has-text-grey py-5">
              Tidak ada data chart untuk ditampilkan.
            </div>
          </div>
        </VCard>
        <div class="column border-custom mb-2 mt-5">
          <span style="font-weight: bold; font-size: 16px; font-family: var(--font-alt);">Pelaksana
          </span>
        </div>
        <div class="tile-grid tile-grid-v2">
          <VPlaceholderPage :class="[dataPegawaiPelaksana.length !== 0 && 'is-hidden']" title="Tidak Ada Pelaksana."
            larger>
            <template #image>
              <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.png" alt="" />
              <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
            </template>
          </VPlaceholderPage>
          <div name="list" tag="div" class="columns is-multiline">
            <div class="columns is-multiline p-2" style="max-height:500px;overflow: auto;">
              <div v-for="item in dataPegawaiPelaksana" :key="item.id" class="column is-12 p-0 pb-2 pl-2 pr-2 ">
                <div class="tile-grid-item">
                  <div class="tile-grid-item-inner">
                    <VAvatar size="small" picture="/images/simrs/male.png" color="primary" bordered />
                    <div class="meta">
                      <span class="dark-inverted">{{ item.namalengkap
                      }}</span>
                      <span class="dark-inverted">{{ item.namajabatanulab
                      }}</span>
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
  <VModal :open="modalDetailOrder" title="Verifikasi" noclose size="big" actions="right"
    @close="modalDetailOrder = false" cancelLabel="Tutup">
    <template #content>
      <div class="column is-12">

        <div class="mb-4" v-if="item.tanggalmulai == null && item.jenisorder != 'repair'">
          <div class="p-4" style="
            border: 1px solid #dbeafe;
            background: #eff6ff;
            border-radius: 10px;
          ">
            <div style="font-size: 18px; font-weight: 800; line-height: 1.3;">
              Informasi Verifikasi
            </div>
            <div style="font-size: 15px; margin-top: 6px; line-height: 1.5;">
              Karena belum Diverifikasi Pelaksana, setelah Anda klik <b>Simpan Verif</b>, sistem akan otomatis mengisi
              <b>Tanggal Mulai</b>.
              Setelah itu, <b>lama waktu penyelesaian</b> akan mulai dihitung berdasarkan tanggal mulai tersebut.
            </div>
          </div>
        </div>

        <Fieldset legend="Data Alat" :toggleable="true">
          <div class="column" v-for="(data) in 3" style="text-align:center" v-if="isLoadDataOrder">
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
                <div class="timeline-item is-unread" v-for="(items, index) in detailOrderLayanan" :key="items.norec">
                  <div :class="'dot is-' + listColor[index + 1]"></div>

                  <div class="content-wrap is-grey">
                    <div class="content-box">
                      <div class="status"></div>
                      <VIconBox size="medium" :color="listColor[index + 1]" rounded>
                        <i class="iconify" data-icon="feather:package" aria-hidden="true"></i>
                      </VIconBox>
                      <div class="box-text" style="width:70%">
                        <div class="meta-text">
                          <p><span>{{ items.namaproduk }}</span></p>

                          <table class="tb-order">
                            <tr>
                              <td>Merk/Tipe</td>
                              <td>:</td>
                              <td>{{ items.namamerk ?? '' }}/{{ items.namatipe }}</td>
                            </tr>
                            <tr>
                              <td>S/N</td>
                              <td>:</td>
                              <td>{{ items.namaserialnumber ?? '' }}</td>
                            </tr>
                            <tr>
                              <td>Lingkup</td>
                              <td>:</td>
                              <td>{{ items.lingkupkalibrasi }}</td>
                            </tr>
                            <tr>
                              <td>Lokasi</td>
                              <td>:</td>
                              <td v-if="items.jenisorder == 'kalibrasi'">{{ items.lokasi }}</td>
                              <td v-if="items.jenisorder == 'repair'">{{ items.lokasirepair }}</td>
                            </tr>
                            <tr>
                              <td v-if="items.jenisorder == 'kalibrasi'">Penyelia Teknik</td>
                              <td v-if="items.jenisorder == 'repair'">Penyelia Teknik Repair</td>
                              <td>:</td>
                              <td class="font-values">{{ items.penyeliateknik }}</td>
                            </tr>
                            <tr>
                              <td v-if="items.jenisorder == 'kalibrasi'">Pelaksana Teknik</td>
                              <td v-if="items.jenisorder == 'repair'">Pelaksana Teknik Repair</td>
                              <td>:</td>
                              <td>{{ items.pelaksanateknik }}</td>
                            </tr>
                            <tr v-if="items.jenisorder == 'kalibrasi'">
                              <td>Durasi</td>
                              <td>:</td>
                              <td>
                                <VTag v-if="items.durasikalbrasi" color="warning" rounded>
                                  {{ items.durasikalbrasi }}
                                </VTag>
                              </td>
                            </tr>
                          </table>

                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </Fieldset>
      </div>
    </template>

    <template #action>
      <VButton icon="feather:save" @click="save(item)" color="info" :loading="isLoadingSave" raised>
        Simpan Verif
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalRiwayat" title="" noclose size="big" actions="right" @close="modalRiwayat = false"
    cancelLabel="Tutup">
    <template #content>
      <div class="column">
        <div class="business-dashboard hr-dashboard">
          <div class="columns is-multiline">
            <div class="column is-12 p-0">
              <div class="block-header">
                <div class="left column is-6 p-0">
                  <div class="current-user">
                    <h3>{{ item.namaproduk }}</h3>
                  </div>
                </div>
                <div class="center column is-6 p-0">
                  <div>
                    <div>
                      <h4 class="block-heading">Merk/Tipe</h4>
                      <p class="block-hext">{{ item.namamerk }}/{{ item.namatipe }}</p>
                      <h4 class="block-heading">S/N</h4>
                      <p class="block-hext">{{ item.namaserialnumber }}</p>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
      <div class="column is-12">
        <Fieldset legend="Data Alat" :toggleable="true">
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
                    <span>{{ H.formatDateIndo(item.date) }}</span>
                  </div>
                  <div :class="'dot is-' + listColor[index + 1]"></div>
                  <div class="content-wrap is-grey">
                    <div class="content-box">
                      <div class="status"></div>
                      <div class="box-text" style="width:70%">
                        <div class="meta-text">
                          <p>
                            <span>
                              {{ item.type }} : {{ item.nama }}
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
        </Fieldset>
      </div>
    </template>
    <template #action>
    </template>
  </VModal>
  <VModal :open="modalBukaLembarKerja" title="Pilih Sub Lingkup" noclose size="medium" actions="right"
    @close="modalBukaLembarKerja = false" cancelLabel="Tutup">
    <template #content>

      <div class="colum is-12">
        <div class="columns is-multiline">
          <div class="column is-6">
            <span class="mb-4"><b>Sub Lingkup</b></span>
            <VField>
              <VControl>
                <AutoComplete v-model="item.daftarsublingkup" :suggestions="d_sublingkup"
                  @complete="fetchSubKalibrasi($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                  class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                  placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
          <div class="column is-6">
            <span class="mb-4"><b>Status KAN</b></span>
            <VField>
              <VControl>
                <AutoComplete v-model="item.statuskan" :suggestions="d_StatusKan" @complete="fetchStatusKan($event)"
                  :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                  :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
        </div>
      </div>

    </template>
    <template #action>
      <VButton icon="feather:save" @click="lembarKerja(item)" color="info" :loading="isLoading" raised>
        Lanjut Ke Lembar Kerja
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalBukaLembarKerjaTekanan" title="Pilih Sub Lingkup" noclose size="medium" actions="right"
    @close="modalBukaLembarKerjaTekanan = false" cancelLabel="Tutup">
    <template #content>

      <div class="colum is-12">
        <div class="columns is-multiline">
          <div class="column is-12">
            <span class="mb-4"><b>Status KAN</b></span>
            <VField>
              <VControl>
                <AutoComplete v-model="item.statuskan" :suggestions="d_StatusKan" @complete="fetchStatusKan($event)"
                  :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                  :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
        </div>
      </div>

    </template>
    <template #action>
      <VButton icon="feather:save" @click="lembarKerjaTekanan(item)" color="info" :loading="isLoading" raised>
        Lanjut Ke Lembar Kerja
      </VButton>
    </template>
  </VModal>
  <WelcomeBannerModalV v-model="openWelcome" :images="[
    '/welcome1.png',
    '/welcome2.png'
  ]" title="Seputar Layanan" subtitle="Info terbaru sebelum menggunakan web"
    storage-key="ulab_welcome_banner_customer_v1" />
  <VModal :open="modalPengajuanAmandemen" title="Pengajuan Amandemen" noclose size="big" actions="right"
    @close="modalPengajuanAmandemen = false" cancelLabel="Tutup">
    <template #content>

      <VCard>
        <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
          <h3 class="title is-5 mb-2">Riwayat Pengajuan Amandemen</h3>
          <div class="is-flex is-align-items-center" style="gap:.5rem">
            <VTag :color="'warning'" rounded>Diajukan</VTag>
            <VTag :color="'success'" rounded>Disetujui</VTag>
            <VTag :color="'danger'" rounded>Ditolak</VTag>
          </div>
        </div>
        <div>
          <DataTable :value="dataPengajuanAmandemen" class="p-datatable-sm" :loading="isLoading" :paginator="true"
            :rows="10" :rowsPerPageOptions="[5, 10, 25]" scrollable
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

            <Column field="no" header="#" style="width:70px;min-width:70px"></Column>
            <Column field="Aksi" header="Aksi" style="min-width: 120px; text-align: center;">
              <template #body="slotProps">
                <VIconButton v-if="(slotProps.data.statusamandemen == null)" v-tooltip.top.left="'Setujui'"
                  color="success" outlined circle icon="fas fa-check" @click="confirmApproval(slotProps.data, 1)" />
                <VIconButton v-if="(slotProps.data.statusamandemen == null)" v-tooltip.top.left="'Tolak'" class="ml-3"
                  color="warning" outlined circle icon="feather:x" @click="confirmApproval(slotProps.data, 2)" />
              </template>
            </Column>
            <Column field="status" header="Status" :sortable="true" style="min-width:140px">
              <template #body="slotProps">
                <VTag class="ml-2" :color="slotProps.data.color" rounded>{{ slotProps.data.status }}</VTag>
              </template>
            </Column>
            <Column field="alasanpengajuan" header="Alasan Pengajuan" :sortable="true" style="min-width:240px">
            </Column>
            <Column field="namaproduk" header="Nama Alat" :sortable="true" style="min-width:100px"></Column>
            <Column field="namamerk" header="Merk Alat" :sortable="true" style="min-width:100px"></Column>
            <Column field="namatipe" header="Tipe Alat" :sortable="true" style="min-width:100px"></Column>
            <Column field="namaserialnumber" header="SN" :sortable="true" style="min-width:100px"></Column>
            <Column field="tglpengajuanamandemen" header="Tanggal Pengajuan" :sortable="false" style="min-width:240px">
              <template #body="slotProps">
                <span>
                  {{ H.formatDateToLocalString(slotProps.data.tglpengajuanamandemen) }}
                </span>
              </template>
            </Column>
          </DataTable>
        </div>
      </VCard>

    </template>
  </VModal>
  <VModal :open="modalBukaLembarKerjaAI" title="Status KAN" noclose size="medium" actions="right"
    @close="modalBukaLembarKerjaAI = false" cancelLabel="Tutup">
    <template #content>

      <div class="colum is-12">
        <div class="columns is-multiline">
          <div class="column is-12">
            <span class="mb-4"><b>Status KAN</b></span>
            <VField>
              <VControl>
                <AutoComplete v-model="item.statuskan" :suggestions="d_StatusKan" @complete="fetchStatusKan($event)"
                  :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                  :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
        </div>
      </div>

    </template>
    <template #action>
      <VButton icon="feather:save" @click="lembarKerjaAI(item)" color="info" :loading="isLoading" raised>
        Lanjut Ke Lembar Kerja
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalRiwayatAmandemen" title="" size="medium" actions="right" cancelLabel="Tutup"
    @close="modalRiwayatAmandemen = false">
    <template #content>
      <div class="amandemen-wrap">
        <div class="amandemen-card">
          <div class="amandemen-title">Informasi Alat</div>

          <div class="amandemen-grid">
            <div class="row">
              <div class="label">Nama Alat</div>
              <div class="value">{{ amandemenForm.namaalat }}</div>
            </div>

            <div class="row">
              <div class="label">Merk / Tipe</div>
              <div class="value">{{ amandemenForm.namamerk }} / {{ amandemenForm.namatipe }}</div>
            </div>

            <div class="row">
              <div class="label">Serial Number</div>
              <div class="value">{{ amandemenForm.namaserialnumber }}</div>
            </div>

            <div class="row">
              <div class="label">No Order Alat</div>
              <div class="value">{{ amandemenForm.noorderalat }}</div>
            </div>
          </div>
        </div>
        <div class="amandemen-card">
          <div class="amandemen-title">Riwayat Amandemen</div>

          <div v-if="isLoadingRiwayat" class="p-3">Memuat riwayat...</div>

          <div v-else>
            <div v-if="riwayatSorted.length === 0" class="p-3">Tidak ada riwayat.</div>

            <div v-else class="riwayat-list">
              <div v-for="item in riwayatSorted" :key="item.id" class="riwayat-row"
                :class="{ 'is-latest': isLatest(item) }">
                <div class="riwayat-left">
                  <div class="riwayat-head">
                    <div class="riwayat-version">
                      <span class="ver">v{{ item.version }}</span>
                      <span class="chip">{{ item.jenisorder }}</span>

                      <span v-if="isLatest(item)" class="chip chip-latest">
                        Terbaru
                      </span>
                    </div>

                    <div class="riwayat-date">
                      {{ item.created_at }}
                    </div>
                  </div>
                </div>

                <div class="riwayat-right">
                  <VButton color="primary" outlined @click="cetakRiwayatAmandemen(item)">
                    Cetak v{{ item.version }}
                  </VButton>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </template>
  </VModal>
</template>
<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { ref, computed, reactive, watch, inject, onMounted, onUnmounted } from 'vue'
import { useThemeColors } from '/@src/composable/useThemeColors'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import { useHead } from '@vueuse/head'
import moment, { isDate } from 'moment'
import ApexChart from 'vue3-apexcharts'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import TabView from 'primevue/tabview';
import MultiSelect from 'primevue/multiselect';
import AutoComplete from 'primevue/autocomplete';
import Dialog from 'primevue/dialog';
import TabPanel from 'primevue/tabpanel';
import Badge from 'primevue/badge';
import RadioButton from 'primevue/radiobutton';
import OverlayPanel from 'primevue/overlaypanel';
import { state, socket } from "/@src/socket.js";
import * as qzService from '/@src/utils/qzTrayService'
import dayjs from 'dayjs'
import durationPlugin from 'dayjs/plugin/duration'
import isBetween from 'dayjs/plugin/isBetween'
import FileUpload from 'primevue/fileupload';
import WelcomeBannerModalV from '/@src/components/WelcomeBannerModalV.vue';
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from "primevue/useconfirm"
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

useHead({
  title: 'Dashboard Penyelia - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

dayjs.extend(durationPlugin)
dayjs.extend(isBetween)
const op = ref();
const activeTab = ref(0)
const date = new Date();
let listColor: any = ref(Object.keys(useThemeColors()))
const dateNow = date.toLocaleString('id-ID', { year: "numeric", month: "long", day: "numeric" });
const themeColors = useThemeColors()
const userLogin = useUserSession().getUser()
const router = useRouter()
const rowGroupMetadata = ref({})
const datasensor = ref([])
const datasensorChart = ref<any[]>([])
const order: any = ref(0)
let totalData: any = ref(0)
let modalDetailOrder: any = ref(false)
let isLoadDataOrder: any = ref(false)
let isLoadingSave: any = ref(false)
let detailOrderLayanan: any = ref(0)
let modalRiwayat: any = ref(false)
let isLoadDataDeatilOrder: any = ref(false)
let modalPengajuanAmandemen: any = ref(false)
const confirm = useConfirm();
const dataPengajuanAmandemen = ref<any[]>([])
const timelineItems = ref([])
const openWelcome = ref(false)
const approvalTargetRow: any = ref(null)
const approvalStatusBaru = ref<1 | 2 | ''>('')
const approvalError = ref('')
const approvalSubmitting = ref(false)
const pendingAmandemenCount = ref(0)
let modalBukaLembarKerjaAI: any = ref(false)
const item: any = ref({
  aktif: true,
  fStatusOrder: 0,
})
type DateRange = { start: Date | null; end: Date | null }
const filterTgl = ref<DateRange>({ start: null, end: null })
type AvatarColor = 'primary' | 'info' | 'success' | 'warning' | 'danger'
type LingkupAvatar = { picture: string; color: AvatarColor }
const DEFAULT_AVATAR: LingkupAvatar = {
  picture: '/images/avatars/svg/propinsi.svg',
  color: 'primary',
}
let modalRiwayatAmandemen: any = ref(false)
const isLoadingRiwayat = ref(false)
const listRiwayatAmandemen = ref<any[]>([])

const riwayatSorted = computed(() => {
  const arr = [...(listRiwayatAmandemen.value ?? [])]
  return arr.sort((a: any, b: any) => {
    const va = Number(a?.version ?? 0)
    const vb = Number(b?.version ?? 0)
    if (vb !== va) return vb - va

    const ta = new Date(a?.created_at ?? 0).getTime()
    const tb = new Date(b?.created_at ?? 0).getTime()
    return tb - ta
  })
})

const latestItem = computed(() => riwayatSorted.value?.[0] ?? null)

const isLatest = (item: any) => {
  if (!latestItem.value) return false
  return item?.id === latestItem.value?.id
}

const amandemenForm = reactive({
  namaalat: '',
  namatipe: '',
  namamerk: '',
  namaserialnumber: '',
  noorderalat: '',
  alasanpengajuan: '',
  norec_detail: '',
})

const riwayatAmandemen = async (e: any) => {
  amandemenForm.namaalat = e?.namaproduk ?? '-'
  amandemenForm.namatipe = e?.namatipe ?? '-'
  amandemenForm.namamerk = e?.namamerk ?? '-'
  amandemenForm.namaserialnumber = e?.namaserialnumber ?? '-'
  amandemenForm.noorderalat = e?.noorderalat ?? '-'
  amandemenForm.norec_detail = e?.norec_detail ?? '-'

  try {
    isLoadingRiwayat.value = true
    const response = await useApi().get(`/registrasi/riwayat-amandemen?norec_detail=${e.norec_detail}`)
    listRiwayatAmandemen.value = response ?? []
  } catch (err) {
    listRiwayatAmandemen.value = []
    H.alert('error', 'Gagal mengambil riwayat amandemen')
  } finally {
    isLoadingRiwayat.value = false
  }

  modalRiwayatAmandemen.value = true
}

const cetakRiwayatAmandemen = (item: any) => {
  if ((item?.jenisorder ?? '').toLowerCase() === 'repair') {
    H.printBlade(`registrasi/cetak-laporan-repair-pdf?norec_detail=${item.norec_detail}&version=${item.version}`)
    return
  }
  H.printBlade(`registrasi/cetak-sertif-customer-pdf?norec_detail=${item.norec_detail}&version=${item.version}`)
}

const normalizeLingkupKey = (s?: string) =>
  (s ?? '')
    .toLowerCase()
    .replace(/&/g, 'dan')
    .replace(/[^a-z0-9]/g, '')
    .trim()

const LINGKUP_AVATAR_MAP: Record<string, LingkupAvatar> = {
  kelistrikan: { picture: '/images/avatars/lingkup/kelistrikan.png', color: 'warning' },
  tekanan: { picture: '/images/avatars/lingkup/tekanan.png', color: 'info' },
  suhudankelembaban: { picture: '/images/avatars/lingkup/suhu.png', color: 'success' },
  vibrasi: { picture: '/images/avatars/lingkup/vibrasi.png', color: 'danger' },
  dimensi: { picture: '/images/avatars/lingkup/dimensi.png', color: 'primary' },
  repair: { picture: '/images/avatars/lingkup/repair.png', color: 'info' },
  vendor: { picture: '/images/avatars/lingkup/vendor.png', color: 'success' },
}

const getLingkupAvatar = (lingkup: string): LingkupAvatar => {
  const key = normalizeLingkupKey(lingkup)
  const alias: Record<string, string> = {
    emisi: 'emisigas',
    instrument: 'instrumentanalitik',
  }
  const finalKey = alias[key] ?? key
  return LINGKUP_AVATAR_MAP[finalKey] ?? DEFAULT_AVATAR
}

const STORAGE_KEYS = {
  dateRange: 'pelaksana:filterTgl',
  order: 'pelaksana:order',
  q: 'pelaksana:qsearch',
};
const allowPersistDateRange = ref(false)
const yearDefaultRange = () => {
  const start = dayjs().startOf('year').toDate()
  const end = dayjs().endOf('year').toDate()
  return { start, end }
}

const now = ref(dayjs());
let interval: any;

const chart: any = ref({
  aktif: true
})
const currentPage = ref({
  page: 1,
  limit: 5,
  rows: 50,
})
let dataPegawaiPelaksana: any = ref([])
let dataAlatKalibrasi: any = ref([])
let isLoading: any = ref(false)
let chartStatus: any = ref({
  series: [],
})
const filters = ref('')
let modalBukaLembarKerja: any = ref(false)
let modalBukaLembarKerjaTekanan: any = ref(false)
const d_sublingkup = ref([])
const d_StatusKan = ref([])

const openPengajuan = async (e: any) => {
  modalPengajuanAmandemen.value = true
  await fetchDataRiwayat()
}

const confirmApproval = (row: any, statusBaru: 1 | 2) => {
  const isApprove = statusBaru === 1
  confirm.require({
    message: `Yakin ingin ${isApprove ? 'MENYETUJUI' : 'MENOLAK'} pengajuan ini?`,
    header: isApprove ? 'Konfirmasi Persetujuan' : 'Konfirmasi Penolakan',
    icon: 'pi pi-question-circle',
    acceptLabel: isApprove ? 'Setujui' : 'Tolak',
    rejectLabel: 'Batal',
    acceptClass: isApprove ? 'p-button-success' : 'p-button-danger',
    rejectClass: 'p-button-text',
    accept: () => submitApprovalWithNote(row, statusBaru),
    reject: () => { },
  })
};

const submitApprovalWithNote = async (row: any, statusBaru: 1 | 2) => {
  approvalError.value = ''

  approvalSubmitting.value = true
  try {
    await useApi().post('penyelia/approval-pengajuan-amandemen', {
      norec: row.norec_detail,
      status: statusBaru,
      noorderalat: row.noorderalat
    })
    await fetchDataRiwayat()
  } catch (e) {
    approvalError.value = 'Gagal menyimpan approval. Coba lagi.'
  } finally {
    approvalSubmitting.value = false
  }
}

const fetchDataRiwayat = async () => {
  isLoading.value = true
  try {
    const res = await useApi().get('penyelia/get-pengajuan-amandemen')
    const rows = res?.data ?? []
    pendingAmandemenCount.value = rows.filter((x: any) => x.statusamandemen == null).length

    dataPengajuanAmandemen.value = rows.map((it: any, idx: number) => {
      const statusText =
        it?.statusamandemen === 1 ? 'Disetujui Penyelia' :
          it?.statusamandemen === 2 ? 'Ditolak Penyelia' :
            it?.statusamandemen === 3 ? 'Disetujui Asman' :
              it?.statusamandemen === 4 ? 'Ditolak Asman' :
                it?.statusamandemen === 5 ? 'Disetujui Manager' :
                  it?.statusamandemen === 6 ? 'Ditolak Manager' : 'Diajukan'

      const color =
        it?.statusamandemen === 1 ? 'success' :
          it?.statusamandemen === 2 ? 'danger' :
            it?.statusamandemen === 3 ? 'success' :
              it?.statusamandemen === 4 ? 'danger' :
                it?.statusamandemen === 5 ? 'success' :
                  it?.statusamandemen === 6 ? 'danger' : 'warning'

      return {
        ...it,
        no: idx + 1,
        status: statusText,
        color,
      }
    })
  } catch (e) {
    console.error('Error fetching data riwayat:', e)
  } finally {
    isLoading.value = false
  }
}

const loadPersistedFilters = () => {
  const raw = localStorage.getItem(STORAGE_KEYS.dateRange)
  if (raw) {
    try {
      const obj = JSON.parse(raw)
      const s = dayjs(obj.start).toDate()
      const e = dayjs(obj.end).toDate()
      if (!isNaN(s.getTime()) && !isNaN(e.getTime())) {
        filterTgl.value = { start: s, end: e }
        allowPersistDateRange.value = true
      }
    } catch { }
  }
  const savedOrder = localStorage.getItem(STORAGE_KEYS.order)
  if (savedOrder !== null) order.value = Number(savedOrder)

  const savedQ = localStorage.getItem(STORAGE_KEYS.q)
  if (savedQ !== null) item.value.qsearch = savedQ
};

const persistDateRange = () => {
  if (!allowPersistDateRange.value) return
  if (!filterTgl.value.start || !filterTgl.value.end) return
  const payload = {
    start: dayjs(filterTgl.value.start).format('YYYY-MM-DD'),
    end: dayjs(filterTgl.value.end).format('YYYY-MM-DD'),
  }
  localStorage.setItem(STORAGE_KEYS.dateRange, JSON.stringify(payload))
}

const clearPersistedFilters = () => {
  localStorage.removeItem(STORAGE_KEYS.dateRange);
  localStorage.removeItem(STORAGE_KEYS.order);
  localStorage.removeItem(STORAGE_KEYS.q);
};

const onDateRangeUserChange = (val: any) => {
  filterTgl.value = val
  allowPersistDateRange.value = true
  persistDateRange()
  fetchAlatKalibrasi(order.value)
}

const WORK_START_H = 7
const WORK_START_M = 30
const WORK_END_H = 16
const WORK_END_M = 0
const WORK_MIN_PER_DAY = (WORK_END_H * 60 + WORK_END_M) - (WORK_START_H * 60 + WORK_START_M) // 510 menit

const isWeekend = (d: dayjs.Dayjs) => d.day() === 0 || d.day() === 6
const setTime = (d: dayjs.Dayjs, h: number, m: number) => d.hour(h).minute(m).second(0).millisecond(0)
const workStartOf = (d: dayjs.Dayjs) => setTime(d, WORK_START_H, WORK_START_M)
const workEndOf = (d: dayjs.Dayjs) => setTime(d, WORK_END_H, WORK_END_M)
const nextWorkStart = (d: dayjs.Dayjs) => {
  let x = d.add(1, 'day')
  while (isWeekend(x)) x = x.add(1, 'day')
  return workStartOf(x)
}
const clampToWorkWindow = (dt: dayjs.Dayjs) => {
  let d = dt
  if (isWeekend(d)) return nextWorkStart(workEndOf(d))
  const start = workStartOf(d)
  const end = workEndOf(d)
  if (d.isBefore(start)) return start
  if (d.isSame(end) || d.isAfter(end)) return nextWorkStart(d)
  return d
}
const addBusinessMinutes = (start: dayjs.Dayjs, minutes: number) => {
  let cursor = clampToWorkWindow(start)
  let remain = minutes
  while (remain > 0) {
    const endWindow = workEndOf(cursor)
    const slot = endWindow.diff(cursor, 'minute')
    if (remain <= slot) return cursor.add(remain, 'minute')
    remain -= slot
    cursor = nextWorkStart(cursor)
  }
  return cursor
}
const businessMinutesBetween = (from: dayjs.Dayjs, to: dayjs.Dayjs) => {
  if (from.isSame(to)) return 0
  let forward = true
  let start = from
  let end = to
  if (from.isAfter(to)) {
    forward = false
    start = to
    end = from
  }
  let cursor = clampToWorkWindow(start)
  let total = 0
  while (cursor.isBefore(end)) {
    const endWindow = workEndOf(cursor)
    const stop = end.isBefore(endWindow) ? clampToWorkWindow(end) : endWindow
    if (!isWeekend(cursor)) {
      const add = Math.max(0, stop.diff(cursor, 'minute'))
      total += add
    }
    if (stop.isSame(end)) break
    cursor = nextWorkStart(cursor)
  }
  return forward ? total : -total
}

const getRemainingKalibrasiTime = (tanggalmulai: string, durasi: number) => {
  if (!tanggalmulai || !durasi) return null;

  const startRaw = dayjs(tanggalmulai);
  const start = startRaw.isValid() ? startRaw : dayjs();
  const startInWindow = clampToWorkWindow(start);
  const target = addBusinessMinutes(startInWindow, durasi * WORK_MIN_PER_DAY);
  const nowTime = now.value;
  const diffMin = businessMinutesBetween(nowTime, target);
  const absMin = Math.abs(diffMin);
  const days = Math.floor(absMin / WORK_MIN_PER_DAY);
  const remAfterDays = absMin % WORK_MIN_PER_DAY;
  const hours = Math.floor(remAfterDays / 60);
  const minutes = remAfterDays % 60;

  if (diffMin <= 0) {
    return `Terlambat ${days} hari ${hours} jam ${minutes} menit`;
  }
  return `${days} hari ${hours} jam ${minutes} menit`;
};

watch(filterTgl, persistDateRange, { deep: true })
watch(order, v => localStorage.setItem(STORAGE_KEYS.order, String(v)))
watch(() => item.value.qsearch, v => localStorage.setItem(STORAGE_KEYS.q, v ?? ''))

const fetchAlatKalibrasi = async (q: any) => {
  let dari = '', sampai = ''
  const range = (!filterTgl.value.start || !filterTgl.value.end)
    ? yearDefaultRange()
    : { start: filterTgl.value.start!, end: filterTgl.value.end! }

  dari = H.formatDate(range.start, 'YYYY-MM-DD')
  sampai = H.formatDate(range.end, 'YYYY-MM-DD')
  let status = ''
  let statusorderpenyelia = ''
    , search = ''
  let limit: any = currentPage.value.limit
  let page = currentPage.value.page
  let offset = (page - 1) * limit
  item.value.statusorderpenyelia = q
  if (order) statusorderpenyelia = '&statusorderpenyelia=' + q
  if (item.value.qsearch) search = item.value.qsearch
  isLoading.value = true
  dataAlatKalibrasi.value = []
  const response = await useApi().get(
    '/penyelia/get-alat-penyelia?dari=' + dari
    + '&page=' + page
    + '&offset=' + offset
    + '&limit=' + limit
    + '&rows=' + currentPage.value.rows
    + '&sampai=' + sampai
    + '&search=' + search
    + statusorderpenyelia
  )
  isLoading.value = false
  response.data.data.sort(compare);
  dataAlatKalibrasi.value = response.data.data
  totalData.value = response.data.total
  updateRowGroupMetaData();
}

const fetchDataSensor = async () => {
  await useApi().get(
    `pelaksana/get-sensor`).then((response) => {
      datasensor.value = response.data
      datasensorChart.value = response.dataChart
    })
}

const chartLabels = computed<string[]>(() => {
  const rows = (datasensorChart.value || []).slice().reverse()
  const pad = (n: number) => String(n).padStart(2, '0')
  return rows.map((r: any) => {
    const d = new Date(r.created_at)
    return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
  })
})

const chartTooltipLabels = computed<string[]>(() => {
  const rows = (datasensorChart.value || []).slice().reverse()
  const pad = (n: number) => String(n).padStart(2, '0')
  return rows.map((r: any) => {
    const d = new Date(r.created_at)
    const tanggal = d.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
    const waktu = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
    return `${tanggal} ${waktu}`
  })
})

const seriesFrom = (key: string) => {
  const rows = (datasensorChart.value || []).slice().reverse()
  return rows.map((r: any) => Number(r?.[key] ?? 0))
}

const makeSpark = (title: string, unit: string, dataKey: string) => {
  const data = seriesFrom(dataKey)
  return {
    key: dataKey,
    title,
    unit,
    series: [{ name: title, data }],
    options: {
      chart: { sparkline: { enabled: true }, animations: { enabled: true } },
      stroke: { width: 2, curve: 'smooth' },
      tooltip: {
        x: {
          formatter: (_: any, { dataPointIndex }: any) =>
            chartTooltipLabels.value[dataPointIndex] || ''
        },
        y: {
          formatter: (val: number) => `${val} ${unit}`
        }
      },
      xaxis: { categories: chartLabels.value },
      markers: { size: 0 },
      grid: { padding: { left: 0, right: 0 } }
    }
  }
}

const charts = computed(() => [
  makeSpark('Temperature', '°C', 'temperature'),
  makeSpark('Humidity', '%RH', 'humidity'),
  makeSpark('Pressure', 'Pa', 'pressure'),
  makeSpark('Light', 'lx', 'light'),
  makeSpark('UV', 'index', 'uv'),
])

const sensor = computed(() => {
  return (datasensor.value && Object.keys(datasensor.value).length) ? datasensor.value as any : null
})

const sensorUpdatedAt = computed(() => {
  if (!sensor.value?.created_at) return '-'
  return dayjs(sensor.value.created_at).format('DD MMM YYYY HH:mm')
})

const toNum = (v: any, d = 0) => {
  const n = Number(v)
  return Number.isFinite(n) ? n.toFixed(d) : '-'
}

const sensorItems = computed(() => {
  return [
    { label: 'Suhu', icon: 'fas fa-thermometer-half', value: toNum(sensor.value?.temperature, 1), unit: '°C' },
    { label: 'Kelembapan', icon: 'fas fa-tint', value: toNum(sensor.value?.humidity, 1), unit: '%RH' },
    { label: 'Tekanan', icon: 'fas fa-tachometer-alt', value: toNum(sensor.value?.pressure, 0), unit: 'hPa' },
    { label: 'Cahaya', icon: 'fas fa-sun', value: toNum(sensor.value?.light, 0), unit: 'lux' },
    { label: 'UV Index', icon: 'fas fa-radiation', value: toNum(sensor.value?.uv, 2), unit: '' },
  ]
})

const orderVerify = async (e: any) => {
  detailOrderLayanan.value = []
  modalDetailOrder.value = true
  item.value.norec = e.norec
  item.value.norec_detail = e.norec_detail
  item.value.tanggalmulai = e.tanggalmulai
  item.value.jenisorder = e.jenisorder
  isLoadDataOrder.value = true
  const response = await useApi().get(`/penyelia/layanan-verif-penyelia?norec_pd=${e.norec_detail}`)
  response.detail.forEach((element: any, i: any) => {
    element.no = i + 1
  });
  isLoadDataOrder.value = false
  detailOrderLayanan.value = response.detail
}

const detailOrder = async (e) => {
  modalRiwayat.value = true
  item.value.namaproduk = e.namaproduk
  item.value.namamerk = e.namamerk
  item.value.namatipe = e.namatipe
  item.value.namaserialnumber = e.namaserialnumber
  item.value.durasikalbrasi = e.durasikalbrasi
  isLoadDataDeatilOrder.value = true
  const response = await useApi().get(`/penyelia/detail-produk?norec_pd=${e.norec_detail}`)
  timelineItems.value = response.timeline
  isLoadDataDeatilOrder.value = false
}

const masukLembarKerjaAI = async (e: any) => {
  modalBukaLembarKerjaAI.value = true
  item.value.norec = e.norec
  item.value.norec_detail = e.norec_detail
  item.value.lingkupfk = e.lingkupfk
  item.value.lingkupkalibrasi = e.lingkupkalibrasi
}

const isLingkupSuhuAi = (e: any) => {
  return Number(e.lingkupfk) === 4
    || String(e.lingkupkalibrasi ?? '').trim().toUpperCase() === 'SUHU & KELEMBABAN'
}

const lembarKerjaAI = async (e: any) => {
  let json = {
    'status': {
      'norec_detail': e.norec_detail,
      'statuskanfk': e.statuskan.value ?? 1,
    }
  }
  isLoading.value = true
  try {
    let sublingkup = 17
    if (isLingkupSuhuAi(e)) {
      const response = await useApi().get(
        `pelaksana/get-sublingkup?lingkup=${e.lingkupfk}&param_search=namasublingkup&query=${encodeURIComponent('KALIBRASI AI SUHU')}`
      )
      const thermalAi = response.data.find((sub: any) => sub.namasublingkup === 'KALIBRASI AI SUHU')
      if (!thermalAi) {
        H.alert('warning', 'Sub lingkup Kalibrasi AI Suhu belum tersedia')
        return
      }
      sublingkup = thermalAi.id
    }

    await useApi().post('/penyelia/save-status-kan', json)
    isLoading.value = false
    router.push({
      name: 'module-penyelia-lembar-kerja-yolo',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup,
      }
    })
  } catch (error: any) {
    isLoading.value = false
    console.error('Error saat menyimpan berkas mitra:', error);
  } finally {
    isLoading.value = false
  }
}


const fetchSubKalibrasi = async (filter: any) => {
  let lingkup = item.value.lingkupfk
  await useApi().get(
    `pelaksana/get-sublingkup?lingkup=${lingkup}&param_search=namasublingkup&query=${filter.query}`).then((response) => {
      d_sublingkup.value = response.data.map((e: any) => {
        return { label: e.namasublingkup, value: e.id }
      })
    })
}

const masukLembarKerja = async (e: any) => {
  modalBukaLembarKerja.value = true
  item.value.norec = e.norec
  item.value.norec_detail = e.norec_detail
  item.value.lingkupfk = e.lingkupfk
}

const masukLembarKerjaTekanan = async (e: any) => {
  modalBukaLembarKerjaTekanan.value = true
  item.value.norec = e.norec
  item.value.norec_detail = e.norec_detail
  item.value.lingkupfk = e.lingkupfk
}

const lembarKerja = (e: any) => {
  let json = {
    'status': {
      'norec_detail': e.norec_detail,
      'statuskanfk': e.statuskan.value ?? 1,
    }
  }
  isLoading.value = true
  useApi().post('/pelaksana/save-status-kan', json).then((r) => {
    isLoading.value = false
    if (e.daftarsublingkup.label == 'METER') {
      router.push({
        name: 'module-penyelia-lembar-kerja',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'SUMBER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-sumber',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'CLAMP') {
      router.push({
        name: 'module-penyelia-lembar-kerja-clamp',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'VIBRATION METER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-vibration-meter',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'ACCELLEROMETER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-accellerometer',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'CLAMP & METER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-clamp-meter',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'METER & SUMBER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-meter-sumber',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'THERMOMETER INFRARED') {
      router.push({
        name: 'module-penyelia-lembar-kerja-termometer-infrared',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'THERMAL IMAGER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-thermal-imager',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'THERMOHYGROMETER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-thermohygrometer',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'DRYBLOCK') {
      router.push({
        name: 'module-penyelia-lembar-kerja-dryblock',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'VIBRATION CALIBRATOR') {
      router.push({
        name: 'module-penyelia-lembar-kerja-vibration-calibrator',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'CALIPER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-caliper-micrometer',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'DIAL INDICATOR') {
      router.push({
        name: 'module-penyelia-lembar-kerja-dial-indicator',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'BORE GAUGE') {
      router.push({
        name: 'module-penyelia-lembar-kerja-bore-gauge',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'CLAMP & METER & SUMBER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-clamp-meter-sumber',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'CONTINUITY +SOURCE') {
      router.push({
        name: 'module-penyelia-lembar-kerja-continuity-+source',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'TACHOMETER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-tachometer',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'INSULATION + METER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-insulation-meter-resistansi',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'TORQUE WRENCH') {
      router.push({
        name: 'module-penyelia-lembar-kerja-torque-wrench',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'OSCILLOSCOPE') {
      router.push({
        name: 'module-penyelia-lembar-kerja-oscilloscope',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'TEMPERATURE INDICATOR WITH SENSOR') {
      router.push({
        name: 'module-penyelia-lembar-kerja-temperature-indicator-with-sensor',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'ULTRASONIC THICKNESS') {
      router.push({
        name: 'module-penyelia-lembar-kerja-ultrasonic-thickness',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'THICKNESS GAUGE') {
      router.push({
        name: 'module-penyelia-lembar-kerja-thickness-gauge',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
} else if (e.daftarsublingkup.label == 'FEELER GAUGE') {
      router.push({
        name: 'module-penyelia-lembar-kerja-feeler-gauge',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'INSULATION TESTER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-insulation-tester',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'INSULATION SOURCE') {
      router.push({
        name: 'module-penyelia-lembar-kerja-insulation-source',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'THERMOCOUPLE/RTD SIMULATOR') {
      router.push({
        name: 'module-penyelia-lembar-kerja-thermocouple-rtd-simulator',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'MICROMETER HEAD') {
      router.push({
        name: 'module-penyelia-lembar-kerja-micrometer-head',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    } else if (e.daftarsublingkup.label == 'OUTSIDE MICROMETER') {
      router.push({
        name: 'module-penyelia-lembar-kerja-outside-micrometer',
        query: {
          norec: e.norec,
          norec_detail: e.norec_detail,
          sublingkup: e.daftarsublingkup.value,
        }
      })
    }
  }).catch((error: any) => {
    isLoading.value = false
    console.error('Error saat menyimpan berkas mitra:', error);
  })
}

const masukLembarKerjaSudahIsi = (e: any) => {
  if (e.namasublingkup == 'METER') {
    router.push({
      name: 'module-penyelia-lembar-kerja',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'SUMBER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-sumber',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'CLAMP') {
    router.push({
      name: 'module-penyelia-lembar-kerja-clamp',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'VIBRATION METER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-vibration-meter',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'ACCELLEROMETER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-accellerometer',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'CLAMP & METER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-clamp-meter',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'METER & SUMBER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-meter-sumber',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'THERMOMETER INFRARED') {
    router.push({
      name: 'module-penyelia-lembar-kerja-termometer-infrared',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'THERMAL IMAGER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-thermal-imager',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'THERMOHYGROMETER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-thermohygrometer',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'DRYBLOCK') {
    router.push({
      name: 'module-penyelia-lembar-kerja-dryblock',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'VIBRATION CALIBRATOR') {
    router.push({
      name: 'module-penyelia-lembar-kerja-vibration-calibrator',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'CALIPER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-caliper-micrometer',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'DIAL INDICATOR') {
    router.push({
      name: 'module-penyelia-lembar-kerja-dial-indicator',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'BORE GAUGE') {
    router.push({
      name: 'module-penyelia-lembar-kerja-bore-gauge',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'CLAMP & METER & SUMBER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-clamp-meter-sumber',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (['KALIBRASI AI', 'KALIBRASI AI SUHU'].includes(e.namasublingkup)) {
    router.push({
      name: 'module-penyelia-lembar-kerja-yolo',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'CONTINUITY +SOURCE') {
    router.push({
      name: 'module-penyelia-lembar-kerja-continuity-+source',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'TACHOMETER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-tachometer',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'INSULATION + METER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-insulation-meter-resistansi',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'TORQUE WRENCH') {
    router.push({
      name: 'module-penyelia-lembar-kerja-torque-wrench',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'OSCILLOSCOPE') {
    router.push({
      name: 'module-penyelia-lembar-kerja-oscilloscope',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'TEMPERATURE INDICATOR WITH SENSOR') {
    router.push({
      name: 'module-penyelia-lembar-kerja-temperature-indicator-with-sensor',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'ULTRASONIC THICKNESS') {
    router.push({
      name: 'module-penyelia-lembar-kerja-ultrasonic-thickness',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'THICKNESS GAUGE') {
    router.push({
      name: 'module-penyelia-lembar-kerja-thickness-gauge',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
} else if (e.namasublingkup == 'FEELER GAUGE') {
    router.push({
      name: 'module-penyelia-lembar-kerja-feeler-gauge',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'INSULATION TESTER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-insulation-tester',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'INSULATION SOURCE') {
    router.push({
      name: 'module-penyelia-lembar-kerja-insulation-source',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'THERMOCOUPLE/RTD SIMULATOR') {
    router.push({
      name: 'module-penyelia-lembar-kerja-thermocouple-rtd-simulator',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'MICROMETER HEAD') {
    router.push({
      name: 'module-penyelia-lembar-kerja-micrometer-head',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  } else if (e.namasublingkup == 'OUTSIDE MICROMETER') {
    router.push({
      name: 'module-penyelia-lembar-kerja-outside-micrometer',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail,
        sublingkup: e.sublingkupfk,
      }
    })
  }
}

const masukLaporanVerifikasi = async (e: any) => {
  router.push({
    name: 'module-penyelia-laporan-verifikasi',
    query: {
      norec: e.norec,
      norec_detail: e.norec_detail
    }
  })
}

const lembarKerjaTekanan = (e: any) => {
  let json = {
    'status': {
      'norec_detail': e.norec_detail,
      'statuskanfk': e.statuskan.value ?? 1,
    }
  }
  isLoading.value = true
  useApi().post('/penyelia/save-status-kan', json).then((r) => {
    isLoading.value = false
    router.push({
      name: 'module-penyelia-lembar-kerja-tekanan',
      query: {
        norec: e.norec,
        norec_detail: e.norec_detail
      }
    })
  }).catch((error: any) => {
    isLoading.value = false
    console.error('Error saat menyimpan berkas mitra:', error);
  })
}

const lembarKerjaTekananSudahIsi = (e: any) => {
  router.push({
    name: 'module-penyelia-lembar-kerja-tekanan',
    query: {
      norec: e.norec,
      norec_detail: e.norec_detail
    }
  })
}

const laporanRepair = (e: any) => {
  router.push({
    name: 'module-penyelia-laporan-repair',
    query: {
      norec: e.norec,
      norec_detail: e.norec_detail,
    }
  })
}

const fetchStatusKan = async (filter: any) => {
  await useApi().get(
    `general/dropdown/statuskan_m?select=id,statuskan&param_search=statuskan&query=${filter.query}&limit=10`
  ).then((response) => {
    d_StatusKan.value = response
  })
}

const save = async (e: any) => {
  let json = {
    'verif': {
      'norec': e.norec ?? '',
      'norec_detail': e.norec_detail ?? '',
    }
  }
  isLoadingSave.value = true
  await useApi().post('/penyelia/save-verif', json).then((r) => {
    isLoadingSave.value = false
    modalDetailOrder.value = false
    fetchAlatKalibrasi(0)
  }).catch((error: any) => {
    isLoadingSave.value = false
    console.error('Error saat menyimpan berkas mitra:', error);

    if (error.response) {

      H.alert('error', `Kesalahan: ${error.response.status} - ${error.response.data.message || 'Gagal menyimpan berkas mitra'}`);
    } else if (error.request) {

      H.alert('error', 'Tidak ada respons dari server. Silakan coba lagi.');
    } else {

      H.alert('error', `Terjadi kesalahan: ${error.message}`);
    }
  })
}


const compare = (a: any, b: any) => {
  if (a.lingkupkalibrasi > b.lingkupkalibrasi) {
    return -1;
  }
  if (a.lingkupkalibrasi < b.lingkupkalibrasi) {
    return 1;
  }
  return 0;
}

const fetchDetail = async () => {
  dataPegawaiPelaksana.value = []
  const response = await useApi().get(
    'penyelia/get-pegawai-pelaksana'
  )
  dataPegawaiPelaksana.value = response.pegawaiJakarta
}

const updateRowGroupMetaData = () => {
  rowGroupMetadata.value = {};

  if (dataAlatKalibrasi.value) {
    for (let i = 0; i < dataAlatKalibrasi.value.length; i++) {
      let rowData = dataAlatKalibrasi.value[i];
      let lingkupkalibrasi = rowData.lingkupkalibrasi;

      if (i == 0) {
        rowGroupMetadata.value[lingkupkalibrasi] = { index: 0, size: 1 };
      }
      else {
        let previousRowData = dataAlatKalibrasi.value[i - 1];
        let previousRowGroup = previousRowData.lingkupkalibrasi;
        if (lingkupkalibrasi === previousRowGroup) {
          rowGroupMetadata.value[lingkupkalibrasi].size++;
        }
        else {
          rowGroupMetadata.value[lingkupkalibrasi] = { index: i, size: 1 };
        }
      }
    }
  }
}

const cetakSpk = (e) => {
  console.log(e)

  H.printBlade(`penyelia/cetak-spk?pdf=true&norec=${e.norec}&penyeliateknikfk=${e.penyeliateknikfk}`);
}

const cetakSertifikatLembarKerja = (e) => {
  H.printBlade(`penyelia/cetak-sertifikat-lembar-kerja?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
}

const cetakLaporanVerfikasi = (e: any) => {
  H.printBlade(`penyelia/cetak-laporan-verifikasi?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`)
}

const cetakLaporanRepair = (e) => {
  H.printBlade(`penyelia/cetak-laporan-repair?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
}

const downloadFileTerunggah = (e: any) => {
  const token = useUserSession().token
  const url = `/service/penyelia/download-file-terunggah?norec=${e.norec_detail}&token=${token}`
  window.open(url, '_blank')
}


const changeSwitch = (e: any) => {
  fetchAlatKalibrasi(e)
}

watch(
  () => currentPage.value.page,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchAlatKalibrasi(item.value.statusorderpenyelia)
    }
  }
)
watch(
  () => currentPage.value.limit,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchAlatKalibrasi(item.value.statusorderpenyelia)
    }
  }
)

watch(
  () => [
    order.value
  ], () => {
    changeSwitch(order.value)
  }
)

onUnmounted(() => {
  clearInterval(interval);
});

onMounted(() => {
  interval = setInterval(() => { now.value = dayjs() }, 60000)
  loadPersistedFilters()
  if (!filterTgl.value.start || !filterTgl.value.end) {
    filterTgl.value = yearDefaultRange()
  }
  const key = 'ulab_welcome_banner_customer_v1'
  if (localStorage.getItem(key) !== '1') {
    openWelcome.value = true
  }

  fetchAlatKalibrasi(order.value)
  fetchDataRiwayat()
})
fetchDataSensor()
fetchDetail()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/penyelia.scss';
@import '/@src/scss/module/dashboard/bedah.scss';

.hide {
  display: hidden !important;
}


@keyframes shake-loop {
  0% {
    transform: translateX(0);
  }

  10% {
    transform: translateX(-10px);
  }

  20% {
    transform: translateX(10px);
  }

  30% {
    transform: translateX(-7px);
  }

  40% {
    transform: translateX(7px);
  }

  50% {
    transform: translateX(-4px);
  }

  60% {
    transform: translateX(4px);
  }

  70% {
    transform: translateX(-2px);
  }

  80% {
    transform: translateX(2px);
  }

  100% {
    transform: translateX(0);
  }
}

.gagal-repair-anim {
  animation: shake-loop 1.3s cubic-bezier(.36, .07, .19, .97) infinite;
  display: flex;
  align-items: center;
  gap: 18px;
  color: #e53935;
  font-weight: 900;
  font-size: 2rem;
  /* Ukuran lebih besar */
  letter-spacing: 1px;
  margin-top: 16px;
  text-shadow: 0 2px 10px #fff6;
  user-select: none;
}

.gagal-repair-anim svg {
  width: 38px !important;
  height: 38px !important;
  flex-shrink: 0;
}

.tolak-serti-anim {
  animation: shake-loop 1.3s cubic-bezier(.36, .07, .19, .97) infinite;
  display: flex;
  align-items: center;
  gap: 9px;
  color: #e53935;
  font-weight: bold;
  font-size: 1rem;
  /* Ukuran lebih besar */
  letter-spacing: 1px;
  margin-top: 10px;
  text-shadow: 0 2px 10px #fff6;
  user-select: none;
}

.tolak-serti-anim svg {
  width: 18px !important;
  height: 18px !important;
  flex-shrink: 0;
}

.tolak-serti-text {
  display: inline-block;
  vertical-align: middle;
  margin-left: 8px;
}

.tolak-serti-title {
  line-height: 1.2;
}

.tolak-serti-ket {
  margin-top: 4px;
  /* enter */
  line-height: 1.2;
}

.tolak-serti-red {
  color: #e53935;
  /* merah */
}

/* Style untuk layar besar (desktop) */
.date-filter-container {
  margin-left: 23rem;
  margin-top: -4.25rem;
  margin-bottom: 20px;
  padding: 0px;
}

/* Media Query untuk layar mobile (lebar maksimal 768px) */
@media (max-width: 768px) {
  .date-filter-container {
    /* Atur ulang margin untuk mobile */
    margin-left: 0;
    margin-top: 1rem;
    /* Beri sedikit jarak dari atas */
    margin-bottom: 1rem;
    width: 100%;
    /* Pastikan lebarnya penuh */
  }
}

.sensor-card {
  padding: 1rem 1rem 0.75rem;

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: .75rem;
  }

  &__title {
    font-weight: 700;
    font-size: 1.05rem;
    display: flex;
    align-items: center;
  }

  &__meta {
    margin-top: .25rem;
  }

  &__time {
    font-size: .85rem;
    opacity: .75;
    display: flex;
    align-items: center;
  }
}

.sensor-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: .65rem;

  @media (min-width: 420px) {
    grid-template-columns: 1fr 1fr;
  }
}

.sensor-item {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, .03);
  border-radius: 14px;
  padding: .6rem .75rem;

  &__icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: rgba(0, 0, 0, .06);
    display: grid;
    place-items: center;
    margin-right: .6rem;

    i {
      font-size: 1rem;
    }
  }

  &__text {
    line-height: 1.1;
  }

  &__label {
    font-size: .8rem;
    opacity: .75;
    margin-bottom: .15rem;
  }

  &__value {
    font-weight: 800;
    font-size: 1.05rem;

    .unit {
      font-weight: 600;
      font-size: .85rem;
      opacity: .8;
      margin-left: .2rem;
    }
  }
}

.sensor-empty {
  padding: .25rem 0 .75rem;
}

/* efek merah tipis untuk seluruh card */
.card-tolak-serti {
  background: rgba(237, 15, 11, 0.06) !important;
  border: 1px solid rgba(229, 57, 53, 0.18);
  box-shadow: 0 6px 18px rgba(229, 57, 53, 0.08);
}

/* biar sudutnya konsisten dengan card Anda (opsional) */
.card-tolak-serti .list-view-item-inner {
  border-radius: 12px !important;
}

/* transisi halus (opsional) */
.list-view-item {
  transition: background-color .2s ease, box-shadow .2s ease, border-color .2s ease !important;
}

.amandemen-btn {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
}


.amandemen-wrap {
  display: grid;
  gap: 14px;
}

.amandemen-card {
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 14px;
  background: var(--card-bg, #fff);
}

.amandemen-title {
  font-weight: 700;
  font-size: 13px;
  font-family: var(--font-alt);
  margin-bottom: 10px;
}

.amandemen-grid {
  display: grid;
  gap: 10px;
}


.row {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 12px;
  align-items: start;
}

.label {
  font-size: 12px;
  color: var(--light-text, #6b7280);
}

.value {
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-text, #111827);
  word-break: break-word;
}

.hint {
  margin-top: 8px;
  font-size: 12px;
  color: var(--light-text, #6b7280);
}

.amandemen-btn {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
}

// Riwayat Styles
.riwayat-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 12px;
}

.riwayat-row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 14px;
  border: 1px solid #e7e9ef;
  border-radius: 12px;
  background: #fff;
  transition: 0.15s ease;
}

.riwayat-row:hover {
  border-color: #d7dbe6;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}

.riwayat-row.is-latest {
  border-color: #b9e7cc;
  background: #f3fff7;
}

.riwayat-left {
  flex: 1;
  min-width: 0;
}

.riwayat-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.riwayat-version {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
}

.riwayat-version .ver {
  font-size: 16px;
}

.riwayat-date {
  font-size: 12px;
  color: #6b7280;
  white-space: nowrap;
}

.riwayat-sub {
  margin-top: 6px;
  font-size: 12px;
  color: #6b7280;
  display: flex;
  gap: 6px;
  align-items: baseline;
}

.riwayat-sub .path {
  color: #111827;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 520px;
}

.chip {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  background: #eef2ff;
  color: #374151;
  border: 1px solid #e5e7eb;
  text-transform: lowercase;
}

.chip-latest {
  background: #16a34a;
  color: #fff;
  border-color: #16a34a;
}

.riwayat-right {
  display: flex;
  align-items: center;
}
</style>
