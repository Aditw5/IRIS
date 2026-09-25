<template>
  <div class="food-delivery-dashboard customer-dashboard-floating">
    <div class="left">
      <section v-if="activeSection === 'cart'" class="dashboard-home-hero">
        <div class="dashboard-home-visual" aria-hidden="true">
          <img
            src="/@src/assets/illustrations/dashboards/lifestyle/customer-dashboard-instruments.png"
            alt="" />
        </div>

        <div class="dashboard-home-copy">
          <span class="dashboard-home-icon" aria-hidden="true">
            <i class="iconify" data-icon="feather:grid"></i>
          </span>
          <div>
            <span class="dashboard-home-eyebrow">DASHBOARD CUSTOMER</span>
            <h1>Dashboard Customer</h1>
            <p class="dashboard-home-welcome">
              Selamat datang, {{ userLogin.pegawai.namaLengkap }}
            </p>
            <p class="dashboard-home-description">
              Kelola alat dan pantau aktivitas pengujian dengan mudah.
            </p>
          </div>
        </div>
      </section>

      <!-- BERANDA / KATALOG ALAT
        PENTING: activeSection tetap menggunakan nilai lama 'cart' supaya algoritma awal tidak berubah.
        Jadi data alat tetap langsung tampil saat pertama kali halaman dibuka seperti kode lama.
      -->
      <div class="left-body" v-if="activeSection === 'cart' && 'is-active'">
        <div class="restaurants">
          <div class="restaurants-toolbar">
            <div class="left is-flex is-align-items-center">
              <VButton @click="tambahAlat()" type="button" icon="feather:plus" class="mr-3" color="info" outlined raised
                :loading="isLoading">
                Tambah Alat
              </VButton>
              <VButton @click="orderScan()" type="button" icon="feather:camera" class="mr-3" color="info" raised
                :loading="isLoading">
                Order Scan Qr
              </VButton>
            </div>
          </div>
          <div class="search-menu-rad mb-2">
            <div class="search-location-rad" style="width: 100%">
              <i class="iconify" data-icon="feather:search"></i>
              <input type="text" placeholder="Cari Nama Alat" v-model="item.searchDataAlat"
                v-on:keyup.enter="fetchDataAlat()" />
            </div>
            <VButton raised class="search-button-rad" @click="fetchDataAlat()" :loading="isLoading">
              Cari Data
            </VButton>
          </div>
          <VPlaceholderPage :title="H.assets().notFound" :subtitle="H.assets().notFoundSubtitle" class="my-6"
            :class="[(Array.isArray(dataOrder) && dataOrder.length !== 0) && 'is-hidden']">
            <template #image>
              <img class="light-image" :src="H.assets().iconNotFound_rev" alt="" />
              <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
            </template>
          </VPlaceholderPage>

          <div class="restaurants-list">
            <div class="columns is-multiline is-mobile">
              <div v-for="(items, index) in dataOrder" :key="items.norec || index"
                class="column is-12-mobile is-6-tablet is-4-desktop">
                <div class="product-card" :class="{ 'is-kan-accredited': items.statuskanfk === 1 }">
                  <div class="product-card-media">
                    <div v-if="items.statuskanfk === 1" class="kan-ribbon"><span>KAN</span></div>

                    <figure class="product-image-wrapper">
                      <img :src="toolThumbnailUrl(items)" :data-main-fallback="toolMainImageUrl(items)"
                        :data-fallback="'/images/other/no_image.jpg'" class="product-img" alt="Foto Produk"
                        @error="(e) => toolImageErrored(e, '300x200')" />
                    </figure>
                  </div>

                  <div class="product-info-wrapper">
                    <div class="product-info">
                      <h4 class="product-name">{{ items.namaproduk }}</h4>
                      <p class="product-spec">MERK/TIPE: {{ items.namamerk }}/{{ items.namatipe }}</p>
                      <p class="product-sn">S/N: {{ items.namaserialnumber }}</p>
                    </div>

                    <div class="product-actions">
                      <VButton type="button" icon="feather:arrow-right-circle" color="info"
                        @click="masukKeranjang(items)" outlined raised :loading="items.isLoading"
                        class="btn-mobile-full">
                        Masukkan Keranjang
                      </VButton>
                    </div>

                    <div class="product-utility-actions" aria-label="Aksi pengelolaan alat">
                      <button type="button" class="product-utility-action is-history"
                        @click="riwayatAlat(items)">
                        <span class="action-icon">
                          <i class="iconify" data-icon="feather:clock"></i>
                        </span>
                        <span class="action-copy">
                          <strong>Riwayat Alat</strong>
                          <small>Lihat riwayat</small>
                        </span>
                      </button>

                      <button type="button" class="product-utility-action is-edit" @click="edit(items)">
                        <span class="action-icon">
                          <i class="iconify" data-icon="feather:edit-2"></i>
                        </span>
                        <span class="action-copy">
                          <strong>Edit Alat</strong>
                          <small>Ubah data</small>
                        </span>
                      </button>

                      <button type="button" class="product-utility-action is-delete" @click="hapus(items)">
                        <span class="action-icon">
                          <i class="iconify" data-icon="feather:trash-2"></i>
                        </span>
                        <span class="action-copy">
                          <strong>Hapus Alat</strong>
                          <small>Hapus data</small>
                        </span>
                      </button>
                    </div>

                    <!-- <div class="product-measurement-action">
                      <VButton type="button" icon="feather:activity" color="success"
                        @click="openMeasurementAI(items)" outlined raised class="btn-mobile-full">
                        Measurement AI
                      </VButton>
                    </div> -->
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
                        <option :value="5">6 results per page</option>
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

      <!-- KERANJANG BARU DI TENGAH, BUKAN PANEL KANAN -->
      <div class="left-body customer-cart-body" v-if="activeSection === 'keranjang' && 'is-active'">
        <div class="customer-cart-page">
          <div class="cart-hero-modern">
            <div class="cart-hero-left">
              <div class="cart-hero-icon">
                <i aria-hidden="true" class="iconify" data-icon="feather:shopping-cart"></i>
              </div>
              <div>
                <span class="cart-eyebrow">KERANJANG CUSTOMER</span>
                <h3>Keranjang Saya</h3>
                <p>Pilih alat yang akan diproses, lalu lanjutkan ke checkout.</p>
              </div>
            </div>
            <div class="cart-hero-stats">
              <div class="mini-stat">
                <span class="mini-stat-icon">
                  <i aria-hidden="true" class="iconify" data-icon="feather:clipboard"></i>
                </span>
                <span class="mini-stat-copy">
                  <span>Total Item</span>
                  <strong>{{ dataKeranjang.length }}</strong>
                </span>
              </div>
              <div class="mini-stat is-selected">
                <span class="mini-stat-icon">
                  <i aria-hidden="true" class="iconify" data-icon="feather:check-circle"></i>
                </span>
                <span class="mini-stat-copy">
                  <span>Dipilih</span>
                  <strong>{{ selectedItems.length }}</strong>
                </span>
              </div>
            </div>
          </div>

          <div class="cart-modern-panel">
            <div class="cart-modern-toolbar">
              <div class="cart-toolbar-left">
                <VCheckbox v-model="selectAll" label="Pilih semua" color="info" class="m-0 p-0" />
                <span class="cart-selection-count">
                  <strong>{{ selectedItems.length }}</strong> dari {{ dataKeranjang.length }} item dipilih
                </span>
              </div>
              <VButton type="button" icon="feather:trash-2" color="danger" outlined
                class="cart-delete-selected" @click="hapusKeranjangSelected"
                :disabled="selectedItems.length === 0">
                Hapus Dipilih
              </VButton>
            </div>

            <VPlaceholderSection v-if="isLoading" title="Memuat Keranjang"
              subtitle="Mohon tunggu, sistem sedang memuat data keranjang Anda.">
              <template #image>
                <img class="light-image" src="/@src/assets/illustrations/dashboards/food/cart-placeholder.svg" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/dashboards/food/cart-placeholder.svg" alt="" />
              </template>
            </VPlaceholderSection>

            <VPlaceholderSection v-else-if="dataKeranjang.length === 0" title="Keranjang Masih Kosong"
              subtitle="Silakan buka Beranda dan masukkan alat ke keranjang terlebih dahulu.">
              <template #image>
                <img class="light-image" src="/@src/assets/illustrations/dashboards/food/cart-placeholder.svg" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/dashboards/food/cart-placeholder.svg" alt="" />
              </template>
            </VPlaceholderSection>

            <div v-else class="cart-modern-list">
              <template v-for="(items, rowIndex) in dataKeranjang" :key="rowIndex">
                <div v-if="rowGroupMetadata[items.jenisorder]?.index === rowIndex" class="cart-group-title">
                  <span>
                    <i aria-hidden="true" class="iconify" data-icon="feather:tool"></i>
                    {{ items.jenisorder }}
                  </span>
                  <small>{{ rowGroupMetadata[items.jenisorder]?.size || 0 }} item</small>
                </div>

                <div class="cart-modern-item" :class="{ 'is-checked': items.checked }">
                  <div class="cart-check-area">
                    <VCheckbox v-model="items.checked" color="info" class="m-0 p-0" />
                  </div>

                  <div class="cart-avatar-modern">
                    <img :src="toolThumbnailUrl(items)" :data-main-fallback="toolMainImageUrl(items)"
                      :data-fallback="'/images/other/no_image.jpg'" alt="Foto Alat"
                      @error="(e) => toolImageErrored(e, '80x80')" />
                  </div>

                  <div class="cart-item-main">
                    <h4>{{ items.namaproduk }}</h4>
                    <div class="cart-item-meta">
                      <span>
                        <i aria-hidden="true" class="iconify" data-icon="feather:tag"></i>
                        MERK/TIPE : {{ items.namamerk }}/{{ items.namatipe }}
                      </span>
                      <span>
                        <i aria-hidden="true" class="iconify" data-icon="feather:hash"></i>
                        S/N : {{ items.namaserialnumber }}
                      </span>
                    </div>
                  </div>

                  <span class="service-pill" :class="String(items.jenisorder || '').toLowerCase()">
                    <i aria-hidden="true" class="iconify" data-icon="feather:tool"></i>
                    {{ items.jenisorder }}
                  </span>
                </div>
              </template>
            </div>

            <div class="cart-modern-footer">
              <div class="cart-footer-summary">
                <span class="cart-footer-icon">
                  <i aria-hidden="true" class="iconify" data-icon="feather:clipboard"></i>
                </span>
                <div>
                  <span>Total Dipilih</span>
                  <strong>{{ selectedItems.length }} Item</strong>
                </div>
              </div>
              <VButton color="info" raised icon="feather:shopping-cart" class="checkout-cta-button"
                :class="selectedItems.length > 0 ? 'is-ready' : 'is-empty'" @click="simpanCheckout(selectedItems)"
                :disabled="selectedItems.length === 0">
                Checkout
              </VButton>
            </div>
          </div>
        </div>
      </div>

      <div class="left-body" v-if="activeSection === 'activity' && 'is-active'">
        <div class="history-hero-desktop">
          <div class="history-hero-left">
            <div class="history-hero-icon">
              <i aria-hidden="true" class="iconify" data-icon="feather:clock"></i>
            </div>
            <div>
              <span>RIWAYAT</span>
              <h3>History Pendaftaran</h3>
              <p>Pantau riwayat registrasi, status verifikasi, dan progres penyelesaian alat.</p>
            </div>
          </div>

          <div class="history-hero-stats">
            <div class="history-mini-stat">
              <span class="history-stat-icon">
                <i aria-hidden="true" class="iconify" data-icon="feather:clipboard"></i>
              </span>
              <span class="history-stat-copy">
                <span>Total Pendaftaran</span>
                <strong>{{ historyKelompokStats.total }}</strong>
              </span>
            </div>
            <div class="history-mini-stat">
              <span class="history-stat-icon">
                <i aria-hidden="true" class="iconify" data-icon="feather:check-circle"></i>
              </span>
              <span class="history-stat-copy">
                <span>Selesai</span>
                <strong>{{ historyKelompokStats.totalSelesai }}/{{ historyKelompokStats.totalDetail }}</strong>
              </span>
            </div>
          </div>
        </div>

        <div class="restaurants">
          <div class="restaurants-toolbar history-toolbar-title">
            <div class="left">
            </div>
          </div>
          <div class="history-content-grid" v-if="orderAlat == 0">
            <div class="history-left-panel">
              <div class="search-menu-rad mb-2">
                <div class="search-location-rad" style="width: 100%">
                  <i class="iconify" data-icon="feather:search"></i>
                  <input type="text" placeholder="No Pendaftaran" v-model="item.searchHistoryKelompok"
                    v-on:keyup.enter="fetchHistoryOrderKelompok()" />
                </div>
                <VButton raised class="search-button-rad" @click="fetchHistoryOrderKelompok()" :loading="isLoading">
                  Cari Data
                </VButton>
              </div>

              <VPlaceholderPage
                :class="[(Array.isArray(dataHistoryOrderKelompok) && dataHistoryOrderKelompok.length !== 0) && 'is-hidden']"
                title="Tidak Ada Alat Hari Ini." subtitle="Silakan Pilih Tanggal" larger>
                <template #image>
                  <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.png" alt="" />
                  <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
                </template>
              </VPlaceholderPage>

              <div class="list-view list-view-v3">
                <div class="list-view-inner mt-2 history-order-list-scroll">
                  <div name="list-complete" tag="div">
                    <div v-for="(item, iddetail) in paginatedHistoryOrderKelompok"
                      :key="item.iddetail || item.norec || iddetail">
                      <div class="list-view-item history-order-card">
                        <div class="list-view-item-inner">
                          <!-- <VAvatar size="small" picture="/images/avatars/svg/propinsi.svg" color="primary" bordered /> -->
                          <div class="meta-left">
                            <h3>
                              {{ item.namaperusahaan }} <span><b>({{ item.nopendaftaran ?? '-' }}) </b></span>
                            </h3>

                            <span>
                              <i aria-hidden="true" class="iconify" data-icon="feather:home"></i>
                              <span>{{ item.jenisorder }}</span>
                              <i aria-hidden="true" class="fas fa-circle icon-separator"></i>
                              <i aria-hidden="true" class="iconify" data-icon="feather:calendar"></i>
                              <span>{{ item.tglregistrasi }}</span>
                            </span>

                            <br>

                            <VTag v-if="item.jenisorder == 'repair'" color="warning" rounded>Repair</VTag>
                            <VTag v-if="item.jenisorder == 'kalibrasi'" color="info" rounded>Kalibrasi</VTag>
                            <VTag class="ml-1" v-if="item.verifregiscustomer == null" label="Alat Menunggu Diverifikasi"
                              :color="'warning'" />
                            <VTag class="ml-1" v-if="item.verifregiscustomer" label="Alat Sudah Diverifikasi"
                              :color="'success'" />
                            <VTag
                              v-if="Number(item.jumlahselesai || 0) >= 1 && item.isikepuasanpelanggan == null"
                              class="ml-1"
                              label="Survei Kepuasan Belum Diisi"
                              color="danger"
                              rounded
                              title="Minimal satu alat telah selesai dan survei kepuasan pelanggan belum diisi" />

                            <div class="order-progress-info" style="text-align:center; margin-bottom: 0.3rem;">
                              <template v-if="item.jumlahdetail && item.jumlahselesai != null">
                                <span style="font-weight: 600; font-size: 1.03em;">
                                  Selesai {{ item.jumlahselesai }}/{{ item.jumlahdetail }}
                                </span>
                                <div class="progress-bar-wrap"
                                  style="background:#e9ecef; border-radius:8px; width:85%; height:9px; margin: 6px auto 0;">
                                  <div :style="{
                                    width: ((item.jumlahselesai / item.jumlahdetail) * 100) + '%',
                                    background: '#53dd6c',
                                    height: '100%',
                                    borderRadius: '8px',
                                    transition: 'width 0.5s'
                                  }"></div>
                                </div>
                              </template>
                            </div>

                            <div class="order-progress-info" style="text-align:center; margin-bottom: 0.3rem;">
                              <template v-if="item.jumlahdetail && item.jumlahselesai != null">
                                <span style="font-weight: 600; font-size: 1.03em;">
                                  Diambil {{ item.jumlahditerima }}/{{ item.jumlahdetail }}
                                </span>
                                <div class="progress-bar-wrap"
                                  style="background:#e9ecef; border-radius:8px; width:85%; height:9px; margin: 6px auto 0;">
                                  <div :style="{
                                    width: ((item.jumlahditerima / item.jumlahdetail) * 100) + '%',
                                    background: '#1478e3',
                                    height: '100%',
                                    borderRadius: '8px',
                                    transition: 'width 0.5s'
                                  }"></div>
                                </div>
                              </template>
                            </div>
                            <div class="history-action-buttons is-under-progress">
                              <div class="history-action-item">
                                <VIconButton v-tooltip.bottom.left="'Cetak AMS'" icon="feather:printer"
                                  @click="cetakAmsKelompok(item)" color="warning" raised circle />
                                <span>AMS</span>
                              </div>
                              <div v-if="item.filecustomertools != null" class="history-action-item">
                                <VIconButton v-tooltip.bottom.left="'Download List Tools'" icon="feather:download"
                                  @click="downloadToolsKelompok(item)" color="info" raised circle />
                                <span>List Tools</span>
                              </div>
                              <div v-if="item.iskaji !== null" class="history-action-item">
                                <VIconButton v-tooltip.bottom.left="'Cetak Tanda Terima'" icon="feather:printer"
                                  @click="cetakTandaTerima(item)" color="info" raised circle />
                                <span>Tanda Terima</span>
                              </div>
                              <div v-if="item.iskaji !== null && item.statusorder == 1 && item.jumlahditerima > 0"
                                class="history-action-item">
                                <VIconButton v-tooltip.bottom.left="'Cetak Tanda Terima Selesai'" icon="feather:printer"
                                  @click="openSelesaiTerima(item)" color="info" raised circle />
                                <span>Tanda Terima Selesai</span>
                              </div>
                              <div class="history-action-item">
                                <VIconButton v-tooltip.bottom.left="'Detail Alat'" color="primary" circle raised
                                  icon="fas fa-tools" @click="getDetailVerify(item)" />
                                <span>Detail Alat</span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <VFlexPagination v-if="historyKelompokStats.total > 0"
                v-model:current-page="currentPageHistoryKelompok.page"
                class="history-pagination" :item-per-page="currentPageHistoryKelompok.limit"
                :total-items="historyKelompokStats.total" :max-links-displayed="5">
                <template #before-pagination>
                  <span class="history-pagination-summary">
                    Menampilkan {{ historyPaginationStart }}–{{ historyPaginationEnd }}
                    dari {{ historyKelompokStats.total }} pendaftaran
                  </span>
                </template>
                <template #before-navigation>
                  <VField class="history-pagination-limit">
                    <VControl>
                      <div class="select is-rounded">
                        <select v-model="currentPageHistoryKelompok.limit"
                          aria-label="Jumlah history per halaman">
                          <option :value="5">5 per halaman</option>
                          <option :value="10">10 per halaman</option>
                          <option :value="15">15 per halaman</option>
                          <option :value="25">25 per halaman</option>
                        </select>
                      </div>
                    </VControl>
                  </VField>
                </template>
              </VFlexPagination>
            </div>

            <aside class="history-right-panel">
              <div class="history-summary-card is-main">
                <span>Total History Pendaftaran</span>
                <strong>{{ historyKelompokStats.total }}</strong>
                <small>Total seluruh pendaftaran sesuai filter</small>
              </div>

              <div class="history-summary-grid">
                <div class="history-summary-card">
                  <span>Pendaftaran Kalibrasi</span>
                  <strong>{{ historyKelompokStats.kalibrasi }}</strong>
                </div>
                <div class="history-summary-card">
                  <span>Pendaftaran Repair</span>
                  <strong>{{ historyKelompokStats.repair }}</strong>
                </div>
                <div class="history-summary-card">
                  <span>Pendaftaran Terverifikasi</span>
                  <strong>{{ historyKelompokStats.verified }}</strong>
                </div>
                <div class="history-summary-card">
                  <span>Pendaftaran Menunggu</span>
                  <strong>{{ historyKelompokStats.waiting }}</strong>
                </div>
              </div>

              <div class="history-chart-card">
                <div class="history-chart-head">
                  <div>
                    <span>Jenis Pendaftaran</span>
                  </div>
                </div>
                <ApexChart type="donut" height="235" :options="historyJenisOrderChartOptions"
                  :series="historyJenisOrderChartSeries" />
              </div>

              <div class="history-chart-card">
                <div class="history-chart-head">
                  <div>
                    <span>Progress Alat</span>
                    <h4>Selesai vs Belum Selesai</h4>
                  </div>
                  <strong>{{ historyKelompokStats.percentSelesai }}%</strong>
                </div>
                <ApexChart type="bar" height="220" :options="historyProgressChartOptions"
                  :series="historyProgressChartSeries" />
              </div>
            </aside>
          </div>
        </div>
      </div>

      <div v-if="activeSection === 'laporan'" class="left-body customer-report-page">
        <section class="report-hero">
          <div class="report-hero-main">
            <div class="report-hero-icon">
              <i aria-hidden="true" class="iconify" data-icon="feather:bar-chart-2"></i>
            </div>
            <div>
              <span class="report-eyebrow">LAPORAN CUSTOMER</span>
              <h1>Laporan Pendaftaran &amp; Alat</h1>
              <p>Ringkasan pendaftaran, progres alat, distribusi layanan, dan data siap ekspor.</p>
            </div>
          </div>

          <div class="report-hero-actions">
            <div class="report-unit-meta">
              <span>Unit</span>
              <strong>{{ reportUnitName }}</strong>
              <small v-if="customerReport?.generated_at">
                Diperbarui {{ formatReportDateTime(customerReport.generated_at) }}
              </small>
            </div>
            <VButton color="light" icon="feather:refresh-cw" raised :loading="reportLoading"
              @click="loadCustomerReport">
              Muat Ulang
            </VButton>
            <VButton color="success" icon="feather:download" raised :loading="reportExporting"
              :disabled="!customerReport" @click="exportCustomerReportExcel">
              Export Excel
            </VButton>
          </div>
        </section>

        <section class="report-filter-card">
          <div class="report-filter-heading">
            <div>
              <span>FILTER LAPORAN</span>
              <h2>Pilih data yang ingin dianalisis</h2>
            </div>
            <button type="button" class="report-reset" @click="resetReportFilters">Reset filter</button>
          </div>

          <div class="report-filter-grid">
            <label>
              <span>Dari Tanggal</span>
              <input v-model="reportFilters.dari" type="date" />
            </label>
            <label>
              <span>Sampai Tanggal</span>
              <input v-model="reportFilters.sampai" type="date" />
            </label>
            <label>
              <span>Jenis Pendaftaran</span>
              <select v-model="reportFilters.jenis_order">
                <option value="">Semua Jenis</option>
                <option value="kalibrasi">Kalibrasi</option>
                <option value="repair">Repair</option>
              </select>
            </label>
            <label class="is-search">
              <span>Cari Data</span>
              <div class="report-search-control">
                <i aria-hidden="true" class="iconify" data-icon="feather:search"></i>
                <input v-model="reportFilters.search" type="search"
                  placeholder="No pendaftaran, order, alat, merk, atau serial"
                  @keyup.enter="loadCustomerReport" />
              </div>
            </label>
            <VButton color="info" icon="feather:filter" raised :loading="reportLoading"
              @click="loadCustomerReport">
              Terapkan
            </VButton>
          </div>
        </section>

        <div v-if="reportLoading && !customerReport" class="report-state-card">
          <div class="report-spinner"></div>
          <h3>Menyiapkan laporan...</h3>
          <p>Sistem sedang merangkum pendaftaran dan seluruh order alat unit Anda.</p>
        </div>

        <div v-else-if="reportErrorMessage && !customerReport" class="report-state-card is-error">
          <i aria-hidden="true" class="iconify" data-icon="feather:alert-circle"></i>
          <h3>Laporan belum dapat dimuat</h3>
          <p>{{ reportErrorMessage }}</p>
          <VButton color="info" icon="feather:refresh-cw" raised @click="loadCustomerReport">
            Coba Lagi
          </VButton>
        </div>

        <template v-else-if="customerReport">
          <section class="report-kpi-grid" aria-label="Ringkasan laporan">
            <article v-for="card in reportKpiCards" :key="card.label" class="report-kpi-card"
              :style="{ '--accent': card.color }">
              <div class="report-kpi-icon">
                <i aria-hidden="true" class="iconify" :data-icon="card.icon"></i>
              </div>
              <div>
                <span>{{ card.label }}</span>
                <strong>{{ card.value }}</strong>
                <small>{{ card.description }}</small>
              </div>
            </article>
          </section>

          <section class="report-chart-grid">
            <article class="report-panel is-wide">
              <div class="report-panel-heading">
                <div>
                  <span>TREN PENDAFTARAN</span>
                  <h2>Pendaftaran dan alat per bulan</h2>
                </div>
                <div class="report-legend-note">{{ reportMonthlyRows.length }} periode</div>
              </div>
              <ApexChart type="area" height="315" :options="reportMonthlyChartOptions"
                :series="reportMonthlyChartSeries" />
            </article>

            <article class="report-panel">
              <div class="report-panel-heading">
                <div>
                  <span>JENIS PENDAFTARAN</span>
                  <h2>Kalibrasi vs Repair</h2>
                </div>
              </div>
              <ApexChart type="donut" height="315" :options="reportOrderTypeChartOptions"
                :series="reportOrderTypeChartSeries" />
            </article>

            <article class="report-panel">
              <div class="report-panel-heading">
                <div>
                  <span>PROGRESS ALAT</span>
                  <h2>Status penyelesaian order</h2>
                </div>
                <div class="report-progress-value">
                  {{ formatReportPercent(reportSummary.completion_percent) }}
                </div>
              </div>
              <ApexChart type="bar" height="300" :options="reportProgressChartOptions"
                :series="reportProgressChartSeries" />
            </article>

            <article class="report-panel is-wide">
              <div class="report-panel-heading">
                <div>
                  <span>LINGKUP DOMINAN</span>
                  <h2>Sebaran alat berdasarkan lingkup layanan</h2>
                </div>
              </div>
              <ApexChart type="bar" height="300" :options="reportScopeChartOptions"
                :series="reportScopeChartSeries" />
            </article>
          </section>

          <section class="report-panel report-table-panel">
            <div class="report-table-header">
              <div>
                <span>DATA LENGKAP</span>
                <h2>Laporan Pendaftaran</h2>
                <p>Satu baris mewakili satu nomor pendaftaran beserta rekap jumlah alatnya.</p>
              </div>
              <span class="report-table-count">{{ reportRegistrationRows.length }} pendaftaran</span>
            </div>

            <DataTable :value="reportRegistrationRows" data-key="norec" paginator :rows="10"
              :rows-per-page-options="[10, 25, 50]" striped-rows responsive-layout="scroll"
              class="customer-report-table"
              current-page-report-template="Menampilkan {first}–{last} dari {totalRecords} pendaftaran"
              paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport">
              <template #empty>
                <div class="report-table-empty">Belum ada pendaftaran pada filter yang dipilih.</div>
              </template>
              <Column header="No" style="min-width: 4rem">
                <template #body="slotProps">{{ slotProps.index + 1 }}</template>
              </Column>
              <Column field="nopendaftaran" header="No Pendaftaran" sortable style="min-width: 12rem" />
              <Column field="tglregistrasi" header="Tanggal" sortable style="min-width: 10rem">
                <template #body="slotProps">
                  {{ formatReportDate(slotProps.data.tglregistrasi) }}
                </template>
              </Column>
              <Column field="jenisorder" header="Jenis" sortable style="min-width: 8rem">
                <template #body="slotProps">
                  <span class="report-badge" :class="reportBadgeClass(slotProps.data.jenisorder)">
                    {{ reportTitleCase(slotProps.data.jenisorder) }}
                  </span>
                </template>
              </Column>
              <Column field="lokasi" header="Lokasi" sortable style="min-width: 10rem" />
              <Column field="status_verifikasi" header="Verifikasi" sortable style="min-width: 11rem">
                <template #body="slotProps">
                  <span class="report-badge" :class="reportStatusClass(slotProps.data.status_verifikasi)">
                    {{ slotProps.data.status_verifikasi }}
                  </span>
                </template>
              </Column>
              <Column field="jumlah_alat" header="Jumlah Alat" sortable style="min-width: 8rem" />
              <Column field="jumlah_selesai" header="Selesai" sortable style="min-width: 7rem" />
              <Column field="jumlah_diterima" header="Diterima" sortable style="min-width: 7rem" />
              <Column field="statusorder" header="Status Order" style="min-width: 11rem">
                <template #body="slotProps">{{ slotProps.data.statusorder || '-' }}</template>
              </Column>
              <Column field="catatan" header="Catatan" style="min-width: 16rem">
                <template #body="slotProps">{{ slotProps.data.catatan || '-' }}</template>
              </Column>
            </DataTable>
          </section>

          <section class="report-panel report-table-panel">
            <div class="report-table-header">
              <div>
                <span>DATA LENGKAP</span>
                <h2>Laporan Per Alat yang Sudah Diorder</h2>
                <p>Satu baris mewakili satu alat/order lengkap dengan identitas, lingkup, lokasi, dan progres.</p>
              </div>
              <span class="report-table-count">{{ reportToolRows.length }} alat/order</span>
            </div>

            <DataTable :value="reportToolRows" data-key="norec_detail" paginator :rows="10"
              :rows-per-page-options="[10, 25, 50]" striped-rows responsive-layout="scroll"
              class="customer-report-table"
              current-page-report-template="Menampilkan {first}–{last} dari {totalRecords} alat/order"
              paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport">
              <template #empty>
                <div class="report-table-empty">Belum ada alat/order pada filter yang dipilih.</div>
              </template>
              <Column header="No" style="min-width: 4rem">
                <template #body="slotProps">{{ slotProps.index + 1 }}</template>
              </Column>
              <Column field="nopendaftaran" header="No Pendaftaran" sortable style="min-width: 12rem" />
              <Column field="noorderalat" header="No Order Alat" sortable style="min-width: 12rem">
                <template #body="slotProps">{{ slotProps.data.noorderalat || '-' }}</template>
              </Column>
              <Column field="namaproduk" header="Nama Alat" sortable style="min-width: 15rem" />
              <Column field="namamerk" header="Merk" sortable style="min-width: 9rem" />
              <Column field="namatipe" header="Tipe" sortable style="min-width: 9rem" />
              <Column field="namaserialnumber" header="Serial Number" sortable style="min-width: 11rem" />
              <Column field="jenisorder" header="Jenis" sortable style="min-width: 8rem">
                <template #body="slotProps">
                  <span class="report-badge" :class="reportBadgeClass(slotProps.data.jenisorder)">
                    {{ reportTitleCase(slotProps.data.jenisorder) }}
                  </span>
                </template>
              </Column>
              <Column field="lingkupkalibrasi" header="Lingkup" sortable style="min-width: 14rem" />
              <Column field="lokasi" header="Lokasi" sortable style="min-width: 10rem" />
              <Column field="status_proses" header="Status Proses" sortable style="min-width: 13rem">
                <template #body="slotProps">
                  <span class="report-badge" :class="reportStatusClass(slotProps.data.status_proses)">
                    {{ slotProps.data.status_proses }}
                  </span>
                </template>
              </Column>
              <Column field="tanggal_selesai" header="Tanggal Selesai" sortable style="min-width: 11rem">
                <template #body="slotProps">
                  {{ formatReportDate(slotProps.data.tanggal_selesai) }}
                </template>
              </Column>
              <Column field="durasikalbrasi" header="Durasi" style="min-width: 8rem">
                <template #body="slotProps">{{ slotProps.data.durasikalbrasi || '-' }}</template>
              </Column>
              <Column field="rating" header="Rating" sortable style="min-width: 7rem">
                <template #body="slotProps">{{ formatReportRating(slotProps.data.rating) }}</template>
              </Column>
              <Column field="keterangan" header="Keterangan" style="min-width: 16rem">
                <template #body="slotProps">{{ slotProps.data.keterangan || '-' }}</template>
              </Column>
            </DataTable>
          </section>
        </template>
      </div>
    </div>
  </div>

  <nav
    v-if="!openWelcome && !modalCheckout && !modalTambahAlat && !modalKeranjang && !modalOrderScan && !showScanner && !statusCustomer"
    class="customer-floating-nav" aria-label="Customer Navigation">
    <span class="customer-floating-indicator" :class="`is-${activeSection}`" aria-hidden="true"></span>

    <button type="button" class="customer-floating-item" :class="{ 'is-active': activeSection === 'cart' }"
      @click="activeSection = 'cart'">
      <span class="floating-icon">
        <i aria-hidden="true" class="iconify" data-icon="feather:home"></i>
      </span>
      <span>Beranda</span>
    </button>

    <button type="button" class="customer-floating-item" :class="{ 'is-active': activeSection === 'keranjang' }"
      @click="activeSection = 'keranjang'">
      <span class="floating-icon">
        <i aria-hidden="true" class="iconify" data-icon="feather:shopping-cart"></i>
        <em v-if="dataKeranjang.length > 0">{{ dataKeranjang.length }}</em>
      </span>
      <span>Keranjang</span>
    </button>

    <button type="button" class="customer-floating-item" :class="{ 'is-active': activeSection === 'activity' }"
      @click="activeSection = 'activity'; fetchHistoryOrderKelompok()">
      <span class="floating-icon">
        <i aria-hidden="true" class="iconify" data-icon="feather:clock"></i>
        <em v-if="historyKelompokStats.total > 0">{{ historyKelompokStats.total }}</em>
      </span>
      <span>History</span>
    </button>

    <button type="button" class="customer-floating-item" :class="{ 'is-active': activeSection === 'laporan' }"
      @click="activeSection = 'laporan'">
      <span class="floating-icon">
        <i aria-hidden="true" class="iconify" data-icon="feather:bar-chart-2"></i>
      </span>
      <span>Laporan</span>
    </button>
  </nav>
  <VModal :open="modalTambahAlat" title="Tambah Master Alat" noclose size="big" actions="right"
    @close="modalTambahAlat = false, clear()" cancelLabel="Tutup">
    <template #content>
      <div class="column is-12 p-4">
        <Fieldset legend="Edit Tindakan" :toggleable="true">
          <div class="columns pl-3">
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-12">
                  <div class="columns is-multiline p-3">
                    <div class="column is-6">
                      <VField>
                        <VLabel class="required-field">Nama Alat</VLabel>
                        <VControl icon="feather:briefcase">
                          <VInput type="text" v-model="item.namaproduk" placeholder="Nama Alat" class="is-rounded_Z" />
                        </VControl>
                      </VField>
                    </div>
                    <div class="column is-6">
                      <VField>
                        <VLabel class="required-field">Merk</VLabel>
                        <VControl icon="feather:bookmark">
                          <VInput type="text" v-model="item.namamerk" placeholder="Nama Alat" class="is-rounded_Z" />
                        </VControl>
                      </VField>
                    </div>
                    <div class="column is-6">
                      <VField>
                        <VLabel class="required-field">Tipe</VLabel>
                        <VControl icon="feather:bookmark">
                          <VInput type="text" v-model="item.namatipe" placeholder="Nama Alat" class="is-rounded_Z" />
                        </VControl>
                      </VField>
                    </div>
                    <div class="column is-6">
                      <VField>
                        <VLabel class="required-field">Serial Number</VLabel>
                        <VControl icon="feather:bookmark">
                          <VInput type="text" v-model="item.namaserialnumber" placeholder="Nama Alat"
                            class="is-rounded_Z" />
                        </VControl>
                      </VField>
                    </div>
                    <div v-if="item.namaproduk" class="column is-12 fieldset-heading mt-2" style="text-align: center;">
                      <h4 style="text-align: center;">Foto Alat</h4>
                    </div>
                    <div class="column is-12" v-if="item.namaproduk">
                      <FileUpload v-model="fileMitra" mode="advanced" name="demo"
                        accept="image/jpeg,image/jpg,image/png,image/webp" :maxFileSize="10000000" outlined
                        :invalidFileTypeMessage="'{0}: File yang diupload harus JPEG/JPG.'"
                        :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                        style="background-color: transparent; color: var(--danger); border: 1px solid;"
                        :chooseLabel="isFile(fileMitra) ? fileMitra.name : item.gambaralat || 'Unggah Gambar'"
                        @select="onSelectFoto($event)" class="is-rounded w-100" />
                      <button type="button" @click="openCamera" class="button is-info mt-2">Buka
                        Kamera</button>
                      <button type="button" v-if="fileMitra" @click="previewFile" class="button is-link mt-2">Lihat
                        File</button>
                      <video ref="video" width="500" height="300" autoplay style="display: none;"></video>
                      <canvas ref="canvas" style="display: none;"></canvas>
                      <button type="button" v-if="isCameraActive" @click="takePhoto" class="button is-success mt-2">
                        Ambil Foto
                      </button>
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
      <VButton icon="feather:save" @click="saveProduk(item)" color="info" :loading="isLoading" raised>
        Simpan
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalKeranjang" title="Masukkan Keranjang" noclose size="medium" actions="right"
    @close="modalKeranjang = false, clear()" cancelLabel="Tutup">
    <template #content>
      <div class="add-to-cart-modal-content">
        <div class="product-name-header">
          <h2>{{ item.namaproduk }}</h2>
        </div>

        <p class="order-type-label">Pilih jenis layanan yang dibutuhkan:</p>

        <div class="order-type-options">
          <div class="option-card" :class="{ 'is-selected': item.jenisorderAlat === 'kalibrasi' }"
            @click="item.jenisorderAlat = 'kalibrasi'">
            <i class="iconify" data-icon="ph:ruler-bold"></i>
            <div class="option-details">
              <strong>Kalibrasi</strong>
              <span>Pengukuran sesuai standar</span>
            </div>
            <div class="radio-check"></div>
          </div>

          <div class="option-card" :class="{ 'is-selected': item.jenisorderAlat === 'repair' }"
            @click="item.jenisorderAlat = 'repair'">
            <i class="iconify" data-icon="ph:wrench-bold"></i>
            <div class="option-details">
              <strong>Repair</strong>
              <span>Perbaikan alat ukur</span>
            </div>
            <div class="radio-check"></div>
          </div>
        </div>
      </div>
    </template>
    <template #action>
      <VButton icon="feather:arrow-right-circle" @click="saveKeranjang(item)" color="info" :loading="isLoading" raised>
        Masukkan Keranjang
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalOrderScan" title="Pilih Jenis Order" noclose size="medium" actions="right"
    @close="modalOrderScan = false, clear()" cancelLabel="Tutup">
    <template #content>
      <div class="add-to-cart-modal-content">
        <div class="order-type-options">
          <div class="option-card" inputId="r-kalibrasi" value="kalibrasi"
            :class="{ 'is-selected': orderScanData.jenisorderAlat === 'kalibrasi' }"
            @click="orderScanData.jenisorderAlat = 'kalibrasi'">
            <i class="iconify" data-icon="ph:ruler-bold"></i>
            <div class="option-details">
              <strong>Kalibrasi</strong>
              <span>Pengukuran sesuai standar</span>
            </div>
            <div class="radio-check"></div>
          </div>

          <div class="option-card" inputId="r-repair" value="repair"
            :class="{ 'is-selected': orderScanData.jenisorderAlat === 'repair' }"
            @click="orderScanData.jenisorderAlat = 'repair'">
            <i class="iconify" data-icon="ph:wrench-bold"></i>
            <div class="option-details">
              <strong>Repair</strong>
              <span>Perbaikan alat ukur</span>
            </div>
            <div class="radio-check"></div>
          </div>
        </div>
      </div>
    </template>
  </VModal>
  <VModal :open="modalCheckout" title="Checkout" noclose size="big" @close="closeCheckoutModal">
    <template #content>
      <div class="checkout-modal-content checkout-single-modal">
        <div class="checkout-selected-tools">
          <div class="checkout-tools-title">
            <span>Alat yang dipilih</span>
            <strong>{{ selectedItems.length }} Item</strong>
          </div>

          <div class="checkout-tools-scroll">
            <div v-for="(tool, idx) in selectedItems" :key="tool.norec || idx" class="checkout-tool-card">
              <img :src="toolThumbnailUrl(tool)" :data-main-fallback="toolMainImageUrl(tool)"
                :data-fallback="'/images/other/no_image.jpg'" alt="Foto Alat"
                @error="(e) => toolImageErrored(e, '90x90')" />
              <div>
                <h4>{{ tool.namaproduk }}</h4>
                <p>{{ tool.namamerk }}/{{ tool.namatipe }}</p>
                <span>S/N: {{ tool.namaserialnumber }}</span>
              </div>
              <em :class="String(tool.jenisorder || '').toLowerCase()">
                {{ tool.jenisorder }}
              </em>
            </div>
          </div>
        </div>

        <div class="checkout-single-content">
          <div class="checkout-section">
            <div class="checkout-section-head">
              <div>
                <span>Data Checkout</span>
                <h4>Lokasi, paket, catatan, dan rentang ukur</h4>
              </div>
              <i class="iconify" data-icon="feather:clipboard"></i>
            </div>

            <div class="columns is-multiline">
              <div class="column is-6 is-full-mobile" v-if="!hanyaRepair">
                <VField>
                  <VLabel class="required-field">Lokasi Kalibrasi/Repair</VLabel>
                  <VControl>
                    <AutoComplete v-model="item.lokasi" :suggestions="d_lokasikalibrasi"
                      @complete="fetchlokasiKalibrasi($event)" optionLabel="label" :dropdown="true"
                      placeholder="Ketik untuk mencari..." />
                  </VControl>
                </VField>
              </div>

              <div class="column is-12" v-if="hanyaRepair">
                <VField>
                  <VLabel class="required-field">Lokasi Repair</VLabel>
                  <VControl>
                    <AutoComplete v-model="item.lokasi" :suggestions="d_lokasikalibrasi"
                      @complete="fetchlokasiKalibrasi($event)" optionLabel="label" :dropdown="true"
                      placeholder="Ketik untuk mencari..." />
                  </VControl>
                </VField>
              </div>

              <div class="column is-6 is-full-mobile" v-if="!hanyaRepair && isUserEksternal == true">
                <VField>
                  <VLabel>Paket Kalibrasi</VLabel>
                  <VControl>
                    <AutoComplete v-model="item.paketkalibrasi" :suggestions="d_paketkalibrasi"
                      @complete="fetchpaketKalibrasi($event)" optionLabel="label" :dropdown="true"
                      placeholder="Ketik untuk mencari..." />
                  </VControl>
                </VField>
              </div>

              <div class="column is-6 is-full-mobile" v-if="!hanyaRepair && isUserEksternal == null">
                <VField>
                  <VLabel>Paket Kalibrasi</VLabel>
                  <VControl>
                    <AutoComplete v-model="item.paketkalibrasi" class="paket-kalibrasi-readonly" :suggestions="[]"
                      optionLabel="label" :dropdown="false" placeholder="Paket otomatis" readonly @keydown.prevent
                      @paste.prevent @complete.prevent />
                  </VControl>
                </VField>
              </div>

              <div class="column is-12">
                <VField>
                  <VLabel>Catatan</VLabel>
                  <VControl>
                    <VTextarea v-model="item.catatan" rows="3" placeholder="Catatan registrasi (opsional)..." />
                  </VControl>
                </VField>
              </div>
            </div>
          </div>

          <div class="checkout-section" v-if="!hanyaRepair">
            <div class="checkout-section-head">
              <div>
                <span>Rentang Ukur</span>
                <h4>Pilih rentang ukur kalibrasi</h4>
              </div>
              <i class="iconify" data-icon="feather:sliders"></i>
            </div>

            <p class="v-label required-field">Rentang Ukur</p>

            <div class="selection-group checkout-selection-group">
              <div class="selection-card" :class="{ 'is-selected': item.rentangUkur === 'standarLab' }"
                @click="item.rentangUkur = 'standarLab'">
                <i class="iconify" data-icon="feather:sliders"></i>
                <span>Standar Lab</span>
              </div>

              <div class="selection-card" :class="{ 'is-selected': item.rentangUkur === 'permintaanPelanggan' }"
                @click="item.rentangUkur = 'permintaanPelanggan'">
                <i class="iconify" data-icon="feather:user-check"></i>
                <span>Permintaan Pelanggan</span>
              </div>

              <div class="selection-card" :class="{ 'is-selected': item.rentangUkur === 'lainLain' }"
                @click="item.rentangUkur = 'lainLain'">
                <i class="iconify" data-icon="feather:more-horizontal"></i>
                <span>Lain-Lain</span>
              </div>
            </div>

            <Transition name="fade-slow">
              <div v-if="item.rentangUkur === 'permintaanPelanggan'" class="mt-3">
                <VField>
                  <VControl>
                    <VTextarea v-model="item.rentangUkurketPermintaanPelanggan" rows="2"
                      placeholder="Jelaskan permintaan rentang ukur spesifik Anda di sini..."></VTextarea>
                  </VControl>
                </VField>
              </div>
            </Transition>
          </div>

          <div class="checkout-section">
            <div class="checkout-section-head">
              <div>
                <span>Upload Dokumen</span>
                <h4>AMS dan list tools</h4>
              </div>
              <i class="iconify" data-icon="feather:upload-cloud"></i>
            </div>

            <div class="upload-step-grid">
              <div class="upload-step-card">
                <div class="upload-step-card-head">
                  <div>
                    <span>Dokumen Wajib</span>
                    <h4>Upload AMS</h4>
                  </div>
                  <i class="iconify" data-icon="feather:file-text"></i>
                </div>

                <p class="v-label required-field">Upload AMS</p>
                <div class="file-upload-wrapper">
                  <FileUpload v-model="fileCustomer" mode="advanced" name="demo" accept="application/pdf"
                    :maxFileSize="10000000" :invalidFileSizeMessage="'Ukuran maksimal berkas adalah 10MB'"
                    :invalidFileTypeMessage="'File yang diupload harus berupa PDF'"
                    :chooseLabel="fileCustomer ? fileCustomer.name : 'Pilih File PDF'" @select="onSelect($event)"
                    class="w-100 checkout-file-upload" />
                </div>
              </div>

              <div class="upload-step-card">
                <div class="upload-step-card-head">
                  <div>
                    <span>Dokumen Opsional</span>
                    <h4>Upload List Tools</h4>
                  </div>
                  <i class="iconify" data-icon="feather:file-plus"></i>
                </div>

                <p class="v-label">Upload List Tools (Opsional)</p>
                <div class="file-upload-wrapper">
                  <FileUpload v-model="fileCustomerTools" mode="advanced" name="demo"
                    accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                    :maxFileSize="10000000" :invalidFileSizeMessage="'Ukuran maksimal berkas adalah 10MB'"
                    :invalidFileTypeMessage="'File yang diupload harus berupa Excel'"
                    :chooseLabel="fileCustomerTools ? fileCustomerTools.name : 'Pilih File Excel'"
                    @select="onSelectTools($event)" class="w-100 checkout-file-upload" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-actions-internal checkout-fixed-actions">
          <VButton icon="feather:save" @click="checkoutSelected(item)" color="info" :loading="isLoading" raised>
            Simpan
          </VButton>
        </div>
      </div>
    </template>
  </VModal>
  <Dialog v-model:visible="statusCustomer" :modal="true" :closable="false" :draggable="false" :dismissableMask="false"
    :showHeader="false" :transitionOptions="null" :breakpoints="{}" :style="{ padding: '0', margin: '0' }"
    contentStyle="background: transparent; box-shadow: none; padding: 0;"
    class="loading-dialog load-search status-customer-dialog">
    <div class="status-customer-wrap">
      <p class="status-title">
        Lengkapi data Anda terlebih dahulu...
      </p>

      <div class="columns is-multiline status-form">
        <div class="column is-12">
          <p class="status-label">Kategori</p>

          <div class="segmented" role="tablist" aria-label="Kategori">
            <button type="button" class="segmented-btn" :class="{ 'is-active': item.kategori === 'INTERNAL' }"
              @click="item.kategori = 'INTERNAL'">
              Internal PLN NP
            </button>
            <button type="button" class="segmented-btn" :class="{ 'is-active': item.kategori === 'EKSTERNAL' }"
              @click="item.kategori = 'EKSTERNAL'">
              Eksternal
            </button>
            <span class="segmented-indicator" :class="item.kategori === 'EKSTERNAL' ? 'to-right' : 'to-left'" />
          </div>

          <small class="help is-size-7 has-text-grey">
            Pilih kategori untuk menampilkan form yang sesuai.
          </small>
        </div>

        <template v-if="item.kategori === 'INTERNAL'">
          <div class="column is-6 is-12-mobile">
            <p class="status-label">Penanggung Jawab Unit</p>
            <VField>
              <VControl>
                <AutoComplete v-model="item.penanggungjawabunit" :suggestions="d_unit" @complete="fetchUnit($event)"
                  optionLabel="label" :dropdown="true" :minLength="3" class="is-input" appendTo="body"
                  loadingIcon="pi pi-spinner" field="label" placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>

          <div class="column is-6 is-12-mobile">
            <p class="status-label">Jabatan</p>
            <VField>
              <VControl fullwidth>
                <VInput v-model="item.jabatanpenanggungjawab" placeholder="Jabatan Penanggung Jawab" />
              </VControl>
            </VField>
          </div>
        </template>

        <template v-else-if="item.kategori === 'EKSTERNAL'">
          <div class="column is-6 is-12-mobile">
            <p class="status-label">Mewakili Institusi</p>

            <VField>
              <VControl>
                <AutoComplete v-model="item.mitraEksternal" :suggestions="d_unitEskternal"
                  @complete="fetchUnitEksternal($event)" optionLabel="label" :dropdown="true" :minLength="3"
                  class="is-input" appendTo="body" loadingIcon="pi pi-spinner" field="label"
                  placeholder="ketik untuk mencari..." />
              </VControl>

              <p class="status-hint">
                Jika institusi tidak ditemukan di daftar, isi <b>Nama Institusi (manual)</b> di bawah ini.
              </p>
            </VField>

            <VField class="mt-2">
              <VControl fullwidth>
                <VInput v-model="item.institusiManual" placeholder="Nama Institusi" :disabled="!!item.mitraEksternal"
                  @input="item.institusiManual = (item.institusiManual || '').toUpperCase()" />
              </VControl>

              <p v-if="item.mitraEksternal" class="help is-size-7 has-text-grey mt-1">
                Anda memilih dari daftar. Input manual dinonaktifkan.
              </p>
            </VField>
          </div>

          <div class="column is-6 is-12-mobile">
            <p class="status-label">Jabatan</p>
            <VField>
              <VControl fullwidth>
                <VInput v-model="item.jabataneksternal" placeholder="Jabatan"
                  @input="item.jabataneksternal = (item.jabataneksternal || '').toUpperCase()" />
              </VControl>
            </VField>
          </div>
        </template>

        <div class="column is-12 has-text-centered">
          <VButton icon="feather:save" @click="saveStatusCustomer()" color="success" :loading="isLoading" raised>
            Simpan
          </VButton>
        </div>
      </div>
    </div>
  </Dialog>
  <Dialog v-model:visible="showScanner" header="Scan QR Code" :modal="true" :closable="false" style="width:520px">
    <div id="qr-reader" style="width:100%;"></div>
    <div class="p-dialog-footer">
      <VButton @click="cancelScanner" type="button" icon="feather:trash" class="mr-3 mt-4" color="info" outlined raised
        :loading="isLoading">
        Batal
      </VButton>
    </div>
  </Dialog>
  <WelcomeBannerModalV v-model="openWelcome" style="z-index: 999;" :images="[
    '/welcome1.png',
    '/welcome2.png'
  ]" title="Seputar Layanan" subtitle="Info terbaru sebelum menggunakan web"
    storage-key="ulab_welcome_banner_customer_v1" />
  <VModal :open="modalselesaiterima" title="Cetak Tanda Selesai Terima" size="medium"
    @close="modalselesaiterima = false" cancelLabel="Tutup">
    <template #content>
      <div class="column is-12">
        <VField>
          <VLabel>Riwayat Dokumen</VLabel>
          <div class="is-flex is-wrap is-gap-2">
            <template v-if="versiList.length">
              <VButton v-for="v in versiList" :key="v.terimafk" color="info" outlined rounded class="is-small"
                @click="H.printBlade(`registrasi/cetak-selesai-terima?pdf=true&terimafk=${v.terimafk}`)">
                <i class="fas fa-eye mr-2"></i>
                Dokumen {{ v.versi.toString().padStart(2, '0') }}
                <span class="ml-2 has-text-grey is-size-7">
                  ({{ H.formatDate(v.tanggalterima, 'DD MMM YYYY') }})
                </span>
              </VButton>
            </template>
            <template v-else>
              <span class="has-text-grey">Belum ada riwayat dokumen.</span>
            </template>
          </div>
        </VField>
      </div>
    </template>
  </VModal>
</template>

<script setup lang="ts">
import type { TinySliderInstance } from 'tiny-slider/src/tiny-slider'
import { tns } from 'tiny-slider/src/tiny-slider'
import { ref, onMounted, onUnmounted, watch, computed, nextTick } from 'vue'
import { useUserSession } from '/@src/stores/userSession'
import { useRoute, useRouter } from 'vue-router'
import { onceImageErrored } from '/@src/utils/via-placeholder'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import AutoComplete from 'primevue/autocomplete'
import * as H from '/@src/utils/appHelper'
import { publicFileUrl } from '/@src/utils/publicFileUrl'
import Dialog from 'primevue/dialog';
import FileUpload from 'primevue/fileupload';
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ApexChart from 'vue3-apexcharts'
import * as XLSX from 'xlsx'
import * as XLSXStyle from 'xlsx-js-style'
import { useThemeColors } from '/@src/composable/useThemeColors'
import { useToaster } from '/@src/composable/toaster'
import { Html5Qrcode } from 'html5-qrcode'
import WelcomeBannerModalV from '/@src/components/WelcomeBannerModalV.vue'

useHead({
  title: 'Dashboard Customer - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const route = useRoute()
const router = useRouter()
const customerSections = new Set(['cart', 'keranjang', 'activity', 'laporan'])
const requestedSection = typeof route.query.section === 'string' ? route.query.section : ''
const activeSection = ref(customerSections.has(requestedSection) ? requestedSection : 'cart')
const userLogin = useUserSession().getUser()
let slider: TinySliderInstance
const sliderElement = ref<HTMLElement>()
const nextButtonElement = ref<HTMLElement>()
const prevButtonElement = ref<HTMLElement>()
const item: any = ref({})
let isLoading: any = ref(false)
const openWelcome = ref(false)
const currentPage = ref({
  page: 1,
  limit: 6,
  rows: 50,
})
const currentPageHistory = ref({
  page: 1,
  limit: 5,
  rows: 50,
})

const currentPageHistoryKelompok = ref({
  page: 1,
  limit: 5,
  rows: 50,
})
const themeColors = useThemeColors()
const statusCustomer: any = ref()
const dataCustomer: any = ref()
const dataOrder: any = ref(0)
const dataHistoryOrderKelompok: any = ref(0)
const dataHistoryOrder: any = ref(0)
const countDiverifikasi: any = ref(0)
const totalDataOrder: any = ref(0)
const countBelumDiverifikasi: any = ref(0)
const dataKeranjang = ref<any[]>([])
let totalData: any = ref(0)
let totalDataHistory: any = ref(0)
let totalDataHistoryKelompok: any = ref(0)
let modalKeranjang: any = ref(false)
let modalTambahAlat: any = ref(false)
let modalCheckout: any = ref(false)
let modalOrderScan: any = ref(false)
let hanyaRepair: any = ref(false)
const d_paketkalibrasi = ref([])
const d_unit = ref([])
const d_unitEskternal = ref([])
const d_merk = ref([])
const d_tipe = ref([])
const d_sn = ref([])
const d_lokasikalibrasi = ref([])
const fileMitra: any = ref()
const video: any = ref(null);
const isCameraActive = ref(false);
const selectedItems = computed(() =>
  dataKeranjang.value.filter((item: any) => item.checked)
)
const selectAll = ref(false)
const rowGroupMetadata = ref<Record<string, { index: number; size: number }>>({})
const rowGroupMetadataHistory = ref<Record<string, { index: number; size: number }>>({})
const fileCustomer: any = ref()
const fileCustomerTools: any = ref()
const orderAlat: any = ref(0)
const showScanner = ref(false)
const scannerRowIdx = ref<number | null>(null)
let html5QrCode: Html5Qrcode | null = null
const scannerItem = ref<any>(null)
const d_produk = ref([])
const orderScanData = ref<{ jenisorderAlat: string | null, idproduk: number | null }>({
  jenisorderAlat: null,
  idproduk: null
})
const isUserEksternal: any = ref()
let modalselesaiterima: any = ref(false)
const versiList = ref([])

const safeHistoryOrderKelompok = computed(() => {
  return Array.isArray(dataHistoryOrderKelompok.value) ? dataHistoryOrderKelompok.value : []
})

const paginatedHistoryOrderKelompok = computed(() => {
  const page = Math.max(Number(currentPageHistoryKelompok.value.page) || 1, 1)
  const limit = Math.max(Number(currentPageHistoryKelompok.value.limit) || 5, 1)
  const start = (page - 1) * limit
  return safeHistoryOrderKelompok.value.slice(start, start + limit)
})

const historyPaginationStart = computed(() => {
  if (safeHistoryOrderKelompok.value.length === 0) return 0
  return ((currentPageHistoryKelompok.value.page - 1) * currentPageHistoryKelompok.value.limit) + 1
})

const historyPaginationEnd = computed(() => {
  return Math.min(
    currentPageHistoryKelompok.value.page * currentPageHistoryKelompok.value.limit,
    safeHistoryOrderKelompok.value.length,
  )
})

const historyKelompokStats = computed(() => {
  const rows = safeHistoryOrderKelompok.value
  const total = rows.length
  const kalibrasi = rows.filter((row: any) => String(row.jenisorder || '').toLowerCase() === 'kalibrasi').length
  const repair = rows.filter((row: any) => String(row.jenisorder || '').toLowerCase() === 'repair').length
  const verified = rows.filter((row: any) => row.verifregiscustomer != null).length
  const waiting = rows.filter((row: any) => row.verifregiscustomer == null).length
  const totalDetail = rows.reduce((sum: number, row: any) => sum + Number(row.jumlahdetail || 0), 0)
  const totalSelesai = rows.reduce((sum: number, row: any) => sum + Number(row.jumlahselesai || 0), 0)
  const belumSelesai = Math.max(totalDetail - totalSelesai, 0)
  const percentSelesai = totalDetail > 0 ? Math.round((totalSelesai / totalDetail) * 100) : 0

  return {
    total,
    kalibrasi,
    repair,
    verified,
    waiting,
    totalDetail,
    totalSelesai,
    belumSelesai,
    percentSelesai,
  }
})

const historyJenisOrderChartSeries = computed(() => [
  historyKelompokStats.value.kalibrasi,
  historyKelompokStats.value.repair,
])

const historyJenisOrderChartOptions = computed(() => ({
  chart: {
    type: 'donut',
    toolbar: { show: false },
  },
  labels: ['Kalibrasi', 'Repair'],
  colors: ['#0ea5e9', '#f59e0b'],
  legend: {
    position: 'bottom',
    fontSize: '12px',
  },
  dataLabels: {
    enabled: true,
    formatter: function (val: number) {
      return Math.round(val) + '%'
    },
  },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Total',
            formatter: function () {
              return String(historyKelompokStats.value.total)
            },
          },
        },
      },
    },
  },
  noData: {
    text: 'Belum ada data',
  },
}))

const historyProgressChartSeries = computed(() => [{
  name: 'Jumlah Alat',
  data: [
    historyKelompokStats.value.totalSelesai,
    historyKelompokStats.value.belumSelesai,
  ],
}])

const historyProgressChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
  },
  colors: ['#22c55e', '#94a3b8'],
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 8,
      distributed: true,
      barHeight: '56%',
    },
  },
  dataLabels: {
    enabled: true,
  },
  xaxis: {
    categories: ['Selesai', 'Belum Selesai'],
    labels: {
      style: {
        colors: '#64748b',
      },
    },
  },
  yaxis: {
    labels: {
      style: {
        colors: '#64748b',
      },
    },
  },
  legend: {
    show: false,
  },
  grid: {
    borderColor: '#e2e8f0',
  },
  noData: {
    text: 'Belum ada data',
  },
}))

const checkoutRenderKey = ref(0)
const checkoutCloseTimer = ref<number | null>(null)
const isCheckoutClosing = ref(false)


const resetCheckoutModalState = () => {
  item.value.rentangUkurketPermintaanPelanggan = ''
  item.value.rentangUkur = ''
  item.value.paketkalibrasi = ''
  item.value.catatan = ''
  item.value.lokasi = ''
  fileCustomer.value = null
  fileCustomerTools.value = null
  checkoutRenderKey.value++
}

const closeCheckoutModal = () => {
  if (isCheckoutClosing.value) return

  isCheckoutClosing.value = true
  modalCheckout.value = false

  if (checkoutCloseTimer.value) {
    window.clearTimeout(checkoutCloseTimer.value)
    checkoutCloseTimer.value = null
  }

  checkoutCloseTimer.value = window.setTimeout(() => {
    resetCheckoutModalState()
    isCheckoutClosing.value = false
    checkoutCloseTimer.value = null
  }, 450)
}

const getProductImage = (items: any) => {
  if (items?.fotoproduk) {
    return publicFileUrl('produk', items.fotoproduk)
  }

  if (items?.gambaralat) {
    return publicFileUrl('produk', items.gambaralat)
  }

  return '/images/other/no_image.jpg'
}

watch(
  () => orderScanData.value.jenisorderAlat,
  async (newVal) => {
    if (!newVal) return
    modalOrderScan.value = false
    await openScanner()
  }
)

function orderScan() {
  orderScanData.value = { jenisorderAlat: null, idproduk: null }
  modalOrderScan.value = true
}

async function openScanner() {
  if (!orderScanData.value.jenisorderAlat) {
    H.alert('warning', 'Silakan pilih jenis order terlebih dahulu')
    return
  }

  modalOrderScan.value = false
  showScanner.value = true
  await nextTick()

  html5QrCode = new Html5Qrcode("qr-reader")
  html5QrCode
    .start(
      { facingMode: "environment" },
      { fps: 10, qrbox: 250 },
      onScanSuccess,
      err => console.warn(err)
    )
    .catch(err => {
      console.error("Gagal start kamera:", err)
      cancelScanner()
    })
}

async function cancelScanner() {
  showScanner.value = false
  if (html5QrCode) {
    await html5QrCode.stop().catch(() => { })
    html5QrCode = null
  }
}

const toolMainImageUrl = (tool: any) => tool?.fotoproduk
  ? publicFileUrl('produk', tool.fotoproduk)
  : '/images/other/no_image.jpg'

const toolThumbnailUrl = (tool: any) => tool?.fotoproduk_thumbnail
  ? publicFileUrl('produk', tool.fotoproduk_thumbnail)
  : toolMainImageUrl(tool)

const toolImageErrored = (event: Event, size: string) => {
  const target = event.target as HTMLImageElement
  const mainFallback = target.dataset.mainFallback
  if (mainFallback && target.dataset.mainAttempted !== 'true' && target.src !== new URL(mainFallback, window.location.origin).href) {
    target.dataset.mainAttempted = 'true'
    target.src = mainFallback
    return
  }
  if (target.dataset.placeholderAttempted === 'true') return
  target.dataset.placeholderAttempted = 'true'
  onceImageErrored(event, size)
}

const getCartBlockNotice = (error: any) => {
  const envelope = error?.data ?? error?.response?.data ?? {}
  const payload = envelope?.response ?? error ?? {}
  const reason = payload?.block_reason ?? envelope?.block_reason
  const message = payload?.message
    ?? envelope?.message
    ?? envelope?.metaData?.message
    ?? (typeof error === 'string' ? error : 'Gagal menambahkan alat ke keranjang')

  if (reason === 'work_in_progress') {
    return { title: 'Alat Masih Dikerjakan', message }
  }
  if (reason === 'rating_required') {
    return { title: 'Rating Belum Diberikan', message }
  }

  return { title: 'Tidak Bisa Dilanjutkan', message }
}

async function onScanSuccess(decodedText: string) {
  await html5QrCode?.stop().catch(() => { })
  showScanner.value = false

  let id_alat: string | null = null
  try {
    const url = new URL(decodedText)
    id_alat = url.searchParams.get("id_alat")
  } catch {
    console.error("QR bukan URL valid:", decodedText)
  }
  if (!id_alat) {
    H.alert('error', 'QR tidak berisi id alat')
    return
  }

  try {
    await useApi().postNoMessage(`/customer/save-keranjang-customer`, {
      keranjangcustomer: {
        idalat: +id_alat,
        jenisorder: orderScanData.value.jenisorderAlat
      }
    })
    H.alert('success', 'Alat berhasil ditambahkan ke keranjang')
    fetchKeranjangCustomer()
  } catch (e) {
    const notice = getCartBlockNotice(e)
    H.alert('warning', notice.message, notice.title, 5000)
  }
}

const openCamera = () => {
  if (isMobileDevice()) {
    const videoInput = document.createElement('input');
    videoInput.type = 'file';
    videoInput.accept = 'image/*';
    videoInput.capture = 'environment';

    videoInput.onchange = async (event) => {
      const file = (event.target as HTMLInputElement).files?.[0];
      if (file) {
        const nama_detail = item.value.namaproduk;
        const imageFileName = `${nama_detail}.jpeg`;

        const renamedFile = new File([file], imageFileName, { type: "image/jpeg" });
        fileMitra.value = renamedFile;
        console.log("Gambar berhasil diambil dari kamera (mobile):", fileMitra.value);
      }
    };

    videoInput.click();
  } else {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: "environment" } }
      })
        .then((stream) => {
          if (video.value) {
            video.value.srcObject = stream;
            video.value.style.display = 'block';
            isCameraActive.value = true;
          }
        })
        .catch((err) => {
          console.error("Gagal mengakses kamera:", err);
        });
    }
  }
};

const slugifyCameraPart = (value: any, fallback = 'camera') => {
  const slug = String(value ?? '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
  return slug || fallback
}

const createCameraRoomId = (base: any, fallback = 'camera') => {
  const randomPart = typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID().split('-')[0]
    : Math.random().toString(36).slice(2, 10)
  return `${slugifyCameraPart(base, fallback)}-${randomPart}`
}

const openMeasurementAI = (e: any) => {
  const sn = String(e?.namaserialnumber ?? '').trim()
  if (!sn) {
    H.alert('warning', 'SN alat belum tersedia untuk Measurement AI')
    return
  }

  const cameraRoute = router.resolve({
    name: 'camera',
    query: {
      room: createCameraRoomId(sn, 'measurement'),
      role: 'asci',
      sn,
      alat: e?.namaproduk ?? '',
      alat_id: e?.id ?? '',
      mode: 'measurement',
    },
  })

  window.open(cameraRoute.href, '_blank', 'noopener')
}

const takePhoto = () => {
  if (video.value) {
    const canvas = document.createElement('canvas');
    canvas.width = video.value.videoWidth;
    canvas.height = video.value.videoHeight;
    const context = canvas.getContext('2d');
    if (context) {
      context.drawImage(video.value, 0, 0, canvas.width, canvas.height);
      canvas.toBlob((blob) => {
        if (blob) {
          const nama_detail = item.value.namaproduk;
          const imageFileName = `${nama_detail}.jpeg`;

          const imageFile = new File([blob], imageFileName, { type: "image/jpeg" });
          fileMitra.value = imageFile;
          console.log("Foto tersimpan sebagai Gambar:", fileMitra.value);
        }
        stopCamera();
      }, "image/jpeg", 1.0);
    }
  }
};

const stopCamera = () => {
  if (video.value && video.value.srcObject) {
    const stream = video.value.srcObject as MediaStream;
    const tracks = stream.getTracks();
    tracks.forEach((track: MediaStreamTrack) => track.stop());
    video.value.srcObject = null;
    video.value.style.display = 'none';
    isCameraActive.value = false;
  }
};

const previewFile = () => {
  if (fileMitra.value) {
    const url = URL.createObjectURL(fileMitra.value);
    window.open(url, "_blank");
  } else {
    alert("Tidak ada file yang bisa dibuka.");
  }
};

const isMobileDevice = () => {
  return /Mobi|Android/i.test(navigator.userAgent);
};

const onSelectFoto = async (filez: any) => {
  const file = filez.files[0];
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

  if (file.size > 10000000) {
    H.alert('error', 'Maksimal file size adalah 10 MB');
    return;
  }

  if (!allowedTypes.includes(file.type)) {
    H.alert('error', 'File harus berupa JPG, JPEG, PNG, atau WEBP');
    fileMitra.value = null;
    return;
  }

  fileMitra.value = file;
};

const isFile = (obj: any): obj is File => {
  return obj instanceof File || (obj && typeof obj === 'object' && 'name' in obj && 'size' in obj && 'type' in obj);
};

const fetchStatusCustomer = async () => {
  await useApi().get(`customer/get-status-customer`).then((response) => {
    statusCustomer.value = response.data.isuserbaru
    dataCustomer.value = response.data
    isUserEksternal.value = response.data.iseksternal
    if (!item.value.kategori) item.value.kategori = 'INTERNAL'
  }).catch((err) => {
  })
  isLoading.value = false
}

const percentVerif = computed(() => {
  if (totalDataOrder.value === 0) return 0
  return Math.round((countDiverifikasi.value / totalDataOrder.value) * 100)
})

const gaugeOptions = computed(() => ({
  series: [percentVerif.value],
  chart: {
    height: 270,
    type: 'radialBar',
    offsetY: -10,
  },
  colors: [themeColors.accent],
  plotOptions: {
    radialBar: {
      startAngle: -135,
      endAngle: 135,
      dataLabels: {
        name: {
          show: true,
          fontSize: '18px',
          fontWeight: 700,
          offsetY: -10,
          color: themeColors.accent,
          formatter: function () {
            return "Diverifikasi"
          }
        },
        value: {
          show: true,
          fontWeight: 600,
          color: themeColors.lightText,
          fontSize: '24px',
          offsetY: -5,
          formatter: function (val) {
            return val + "%";
          }
        }
      },
      hollow: {
        margin: 15,
        size: '75%',
      },
      track: {
        strokeWidth: '100%',
      },
    },
  },
  stroke: {
    lineCap: 'round',
  },
  labels: ['Diverifikasi'],
}))

const saveProduk = async (item: any) => {

  console.log(item)

  if (!item.namaproduk) {
    useToaster().error('Nama Alat harus di isi')
    return
  }
  if (!item.namamerk) {
    useToaster().error('Merk harus di isi')
    return
  }
  if (!item.namatipe) {
    useToaster().error('Tipe harus di isi')
    return
  }
  if (!item.namaserialnumber) {
    useToaster().error('Serial Number harus di isi')
    return
  }
  const user = dataCustomer.value

  const formData = new FormData()
  if (isFile(fileMitra.value)) {
    formData.append('fileMitra', fileMitra.value);
  } else if (item.gambaralat) {
    formData.append('namaFileLama', item.gambaralat);
  }
  formData.append('fileMitra', fileMitra.value)
  formData.append('id', '')
  formData.append('namaproduk', item.namaproduk)
  formData.append('namamerk', item.namamerk)
  formData.append('namatipe', item.namatipe)
  formData.append('namaserialnumber', item.namaserialnumber.trim().toUpperCase())
  formData.append('mitrafk', user.mitrafk)
  formData.append('statusenabled', item.statusenabled ?? true)
  isLoading.value = true
  await useApi().post(
    `/sysadmin/save-master-produk`, formData).then((response: any) => {
      isLoading.value = false
      modalTambahAlat.value = false
      fetchDataAlat()
    }).catch((e: any) => {
      isLoading.value = false
      console.clear()
      console.log(e)
    })
}

const hapusKeranjangSelected = async () => {
  const itemsToDelete = selectedItems.value
  if (itemsToDelete.length === 0) {
    H.alert('warning', 'Pilih minimal satu item yang akan dihapus dari keranjang')
    return
  }

  isLoading.value = true
  try {
    const norecArray = itemsToDelete.map((item: any) => item.norec)
    await useApi().post(`/customer/hapus-keranjang-customer`, {
      norec: norecArray
    })
    fetchKeranjangCustomer()
  } catch (e: any) {
    console.clear()
    console.log(e)
  } finally {
    isLoading.value = false
  }
}

const saveStatusCustomer = async () => {
  const kategori = (item.value.kategori || 'INTERNAL').toUpperCase()
  const payload: any = {
    kategori,
    mitrafk: null,
    institusi: null,
    jabatan: null,
  }
  if (kategori === 'INTERNAL') {
    if (!item.value.penanggungjawabunit) {
      H.alert('warning', 'Unit harus di isi')
      return
    }
    if (!item.value.jabatanpenanggungjawab) {
      H.alert('warning', 'Jabatan harus di isi')
      return
    }
    payload.mitrafk = item.value.penanggungjawabunit?.value ?? null
    payload.jabatan = (item.value.jabatanpenanggungjawab || '').toUpperCase()
    item.value.mitraEksternal = null
    item.value.institusiManual = ''
    item.value.jabataneksternal = ''
  } else if (kategori === 'EKSTERNAL') {
    const picked = item.value.mitraEksternal
    const manual = (item.value.institusiManual || '').trim()
    if (!item.value.jabataneksternal) {
      H.alert('warning', 'Jabatan harus di isi')
      return
    }
    payload.jabatan = (item.value.jabataneksternal || '').toUpperCase()
    if (picked && (picked.value || picked.id)) {
      payload.mitrafk = picked.value ?? picked.id
      payload.institusi = null
      item.value.institusiManual = ''
    } else {
      if (!manual) {
        H.alert('warning', 'Institusi harus di isi (pilih dari daftar atau isi manual)')
        return
      }
      payload.institusi = manual.toUpperCase()
      payload.mitrafk = null
      item.value.mitraEksternal = null
    }
    item.value.penanggungjawabunit = null
    item.value.jabatanpenanggungjawab = ''
  } else {
    H.alert('warning', 'Kategori belum dipilih')
    return
  }
  const json = { statusCustomer: payload }
  isLoading.value = true
  try {
    await useApi().post(`/customer/save-status-customer`, json)
    await initDashboard()
  } catch (e: any) {
    console.clear()
    console.log(e)
  } finally {
    isLoading.value = false
  }
}

const onSelect = async (filez: any) => {
  const file = filez.files[0];
  if (!file) return;
  if (file.size > 10000000) {
    H.alert('error', 'Maksimal file size adalah 10 MB');
    return;
  }
  if (file.type !== "application/pdf") {
    H.alert('error', 'File yang diizinkan harus berupa PDF');
    return;
  }
  fileCustomer.value = file;
}

const onSelectTools = async (filez: any) => {
  const file = filez.files[0];
  if (!file) return;

  if (file.size > 10000000) {
    H.alert('error', 'Maksimal file size adalah 10 MB');
    return;
  }

  const allowedTypes = [
    "application/vnd.ms-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
  ];

  if (!allowedTypes.includes(file.type)) {
    H.alert('error', 'File harus berupa Excel (.xls atau .xlsx)');
    return;
  }

  fileCustomerTools.value = file;
};

const riwayatAlat = (e: any) => {
  router.push({
    name: 'module-customer-detail-alat',
    query: {
      id_alat: e.id,
    },
  })
}

function edit(e: any) {
  router.push({
    name: 'module-sysadmin-produk-baru',
    query: {
      id: e.id,
    },
  })
}

function hapus(e: any) {
  useApi().post(
    `sysadmin/delete-master-produk`, { 'id': e.id }).then((response: any) => {
      fetchDataAlat()
    }).catch((e: any) => {

    })
}

const fetchDataAlat = async () => {
  isLoading.value = true
  let searchDataAlat = ''
  if (item.value.searchDataAlat) searchDataAlat = '&search=' + item.value.searchDataAlat
  let limit: any = currentPage.value.limit
  let page = currentPage.value.page
  let offset = (page - 1) * limit
  const user = dataCustomer.value


  await useApi().get(`customer/get-alat-customer?page=${page}&offset=${offset}&limit=${limit}&mitrauser=${user.mitrafk}&rows=${currentPage.value.rows}&` + searchDataAlat).then((response) => {
    dataOrder.value = response.data
    totalData.value = response.total
    response.data.forEach((element: any, i: any) => {
      element.no = i + 1
      let ini = element.namaperusahaan.split(' ')
      let init = element.namaperusahaan.substr(0, 2)
      if (ini.length > 1) {
        init = init + ini[1].substr(0, 1)
      }
      element.initials = init
    });
    isLoading.value = false
  }).catch((err) => {
  })
  isLoading.value = false
}

const fetchHistoryOrder = async () => {
  isLoading.value = true
  let searchHistory = ''
  if (item.value.searchHistory) searchHistory = '&search=' + item.value.searchHistory
  let limit: any = currentPageHistory.value.limit
  let page = currentPageHistory.value.page
  let offset = (page - 1) * limit
  const user = dataCustomer.value


  await useApi().get(`customer/get-history-order-customer?page=${page}&offset=${offset}&limit=${limit}&rows=${currentPageHistory.value.rows}&mtrauser=${user.mitrafk}&` + searchHistory).then((response) => {
    dataHistoryOrder.value = response.data.data
    totalDataOrder.value = response.totalData
    countDiverifikasi.value = response.countVerif
    countBelumDiverifikasi.value = response.countBelumVerif
    totalDataHistory.value = response.data.total
    response.data.data.forEach((element: any, i: any) => {
      element.no = i + 1
      let ini = element.namaproduk.split(' ')
      let init = element.namaproduk.substr(0, 2)
      if (ini.length > 1) {
        init = init + ini[1].substr(0, 1)
      }
      element.initials = init
    });
    isLoading.value = false
  }).catch((err) => {
  })
  isLoading.value = false
  updateRowGroupMetaDataHistory()
}

const fetchHistoryOrderKelompok = async () => {
  isLoading.value = true
  currentPageHistoryKelompok.value.page = 1
  let searchHistoryKelompok = ''
  if (item.value.searchHistoryKelompok) searchHistoryKelompok = '&search=' + item.value.searchHistoryKelompok
  const user = dataCustomer.value

  await useApi().get(`customer/get-history-order-customer-kelompok?mtrauser=${user.mitrafk}&rows=${currentPageHistoryKelompok.value.rows}&` + searchHistoryKelompok).then((response) => {
    const rows = Array.isArray(response)
      ? response
      : (Array.isArray(response?.data) ? response.data : [])

    dataHistoryOrderKelompok.value = rows
    totalDataHistoryKelompok.value = rows.length
    rows.forEach((element: any, i: any) => {
      element.no = i + 1
      let ini = element.namaperusahaan.split(' ')
      let init = element.namaperusahaan.substr(0, 2)
      if (ini.length > 1) {
        init = init + ini[1].substr(0, 1)
      }
      element.initials = init
    });
    isLoading.value = false
  }).catch((err) => {
  })
  isLoading.value = false
}

const fetchKeranjangCustomer = async () => {
  isLoading.value = true
  let search = ''
  if (item.value.search) search = '&search=' + item.value.search

  await useApi().get(`customer/get-keranjang-customer?` + search).then((response) => {
    dataKeranjang.value = response.map((element: any, i: any) => {
      let ini = element.namaproduk.split(' ')
      let init = element.namaproduk.substr(0, 2)
      if (ini.length > 1) {
        init = init + ini[1].substr(0, 1)
      }

      return {
        ...element,
        no: i + 1,
        initials: init,
        checked: false,
      }
    })
    isLoading.value = false
  })
  updateRowGroupMetaData();
}

const updateRowGroupMetaData = () => {
  rowGroupMetadata.value = {};
  if (dataKeranjang.value) {
    for (let i = 0; i < dataKeranjang.value.length; i++) {
      let rowData = dataKeranjang.value[i];
      let jenisorder = rowData.jenisorder;

      if (i == 0) {
        rowGroupMetadata.value[jenisorder] = { index: 0, size: 1 };
      }
      else {
        let previousRowData = dataKeranjang.value[i - 1];
        let previousRowGroup = previousRowData.jenisorder;
        if (jenisorder === previousRowGroup) {
          rowGroupMetadata.value[jenisorder].size++;
        }
        else {
          rowGroupMetadata.value[jenisorder] = { index: i, size: 1 };
        }
      }
    }
  }
}

const updateRowGroupMetaDataHistory = () => {
  rowGroupMetadataHistory.value = {};
  if (dataHistoryOrder.value) {
    for (let i = 0; i < dataHistoryOrder.value.length; i++) {
      let rowData = dataHistoryOrder.value[i];
      let jenisorder = rowData.jenisorder;

      if (i == 0) {
        rowGroupMetadataHistory.value[jenisorder] = { index: 0, size: 1 };
      }
      else {
        let previousRowData = dataHistoryOrder.value[i - 1];
        let previousRowGroup = previousRowData.jenisorder;
        if (jenisorder === previousRowGroup) {
          rowGroupMetadataHistory.value[jenisorder].size++;
        }
        else {
          rowGroupMetadataHistory.value[jenisorder] = { index: i, size: 1 };
        }
      }
    }
  }
}

const getDetailVerify = (e: any) => {
  router.push({
    name: 'module-customer-detail-registrasi',
    query: {
      norec_pd: e.iddetail,
    },
  })
}

const getDetailAlat = (e: any) => {
  console.log(e)
  router.push({
    name: 'module-customer-detail-alat',
    query: {
      id_alat: e.idalat,
    },
  })
}

watch(
  () => currentPage.value.page,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchDataAlat()
    }
  }
)
watch(
  () => currentPage.value.limit,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchDataAlat()
    }
  }
)

watch(
  () => currentPageHistory.value.page,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchHistoryOrder()
    }
  }
)
watch(
  () => currentPageHistory.value.limit,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchHistoryOrder()
    }
  }
)

watch(
  () => currentPageHistoryKelompok.value.limit,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      currentPageHistoryKelompok.value.page = 1
    }
  }
)

const masukKeranjang = async (e: any) => {
  modalKeranjang.value = true
  item.value.namaproduk = e.namaproduk
  item.value.idproduk = e.id
}

const tambahAlat = async () => {
  modalTambahAlat.value = true
}

const saveKeranjang = async (e: any) => {
  console.log(item.value)
  if (!item.value.jenisorderAlat) { H.alert('warning', 'Jenis Order harus di isi'); return }

  let json = {
    'keranjangcustomer': {
      'idalat': item.value.idproduk ?? null,
      'jenisorder': item.value.jenisorderAlat ?? null,
    }
  }

  isLoading.value = true
  try {
    await useApi().postNoMessage(`/customer/save-keranjang-customer`, json)
    modalKeranjang.value = false
    fetchKeranjangCustomer()
    clear()
    H.alert('success', 'Alat berhasil ditambahkan ke keranjang')
  } catch (error: any) {
    const notice = getCartBlockNotice(error)
    H.alert('warning', notice.message, notice.title, 5000)
  } finally {
    isLoading.value = false
  }
}

const fetchUnit = async (filter: any) => {
  await useApi().get(
    `general/dropdown/mitra_m?select=id,namaperusahaan&param_search=namaperusahaan&query=${filter.query}&limit=10`
  ).then((response) => {
    d_unit.value = response
  })
}

const fetchUnitEksternal = async (filter: any) => {
  await useApi().get(
    `customer/dropdown-unit-eksternal?query=${filter.query}&limit=10`
  ).then((response) => {
    d_unitEskternal.value = response
  })
}

const fetchpaketKalibrasi = async (filter: any) => {
  await useApi().get(
    `registrasi/dropdown-paket-kalibrasi?query=${filter.query}&limit=10`
  ).then((response) => {
    d_paketkalibrasi.value = response
    console.log(response)
  })
}

const fetchmerk = async (filter: any) => {
  await useApi().get(
    `general/dropdown/merkalat_m?select=id,namamerk&param_search=namamerk&query=${filter.query}&limit=10`
  ).then((response) => {
    d_merk.value = response
  })
}

const fetchtipe = async (filter: any) => {
  await useApi().get(
    `general/dropdown/tipealat_m?select=id,namatipe&param_search=namatipe&query=${filter.query}&limit=10`
  ).then((response) => {
    d_tipe.value = response
  })
}

const fetchserialnumber = async (filter: any) => {
  await useApi().get(
    `general/dropdown/serialnumber_m?select=id,namaserialnumber&param_search=namaserialnumber&query=${filter.query}&limit=10`
  ).then((response) => {
    d_sn.value = response
  })
}

const fetchlokasiKalibrasi = async (filter: any) => {
  await useApi().get(
    `general/dropdown/lokasikalibrasi_m?select=id,lokasi&param_search=lokasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lokasikalibrasi.value = response
  })
}

const simpanCheckout = async (e: any[]) => {
  const itemsToCheckout = selectedItems.value
  if (itemsToCheckout.length === 0) {
    H.alert('warning', 'Pilih minimal satu item untuk checkout')
    return
  }
  hanyaRepair.value = e.every(item => item.jenisorder === 'repair')
  modalCheckout.value = true
  item.value.paketkalibrasi = {
    value: 2,
    label: '5 Hari'
  }
}

watch(
  () => item.value.lokasi,
  (lokasi: any) => {
    if (!lokasi) {
      item.value.paketkalibrasi = null
      return
    }
    const lokasiValue = lokasi?.value ?? lokasi?.id ?? null
    if (Number(lokasiValue) === 1) {
      item.value.paketkalibrasi = {
        value: 2,
        label: '5 Hari'
      }
      return
    }
    if (Number(lokasiValue) === 2) {
      item.value.paketkalibrasi = {
        value: 3,
        label: '10 Hari'
      }
      return
    }

    item.value.paketkalibrasi = null
  },
  {
    deep: true
  }
)

const checkoutSelected = async (e: any) => {
  const user = dataCustomer.value
  const itemsToCheckout = selectedItems.value

  if (!e.lokasi) {
    H.alert('warning', 'Lokasi harus di isi')
    return
  }

  const formData = new FormData()
  formData.append('fileCustomer', fileCustomer.value)
  formData.append('fileCustomerTools', fileCustomerTools.value)
  formData.append('namapenanggungjawab', user.name)
  formData.append('nomitrafk', user.mitrafk)
  formData.append('nohppenanggungjawab', user.nowa)
  formData.append('jabatanpenanggungjawab', user.jabatan)
  formData.append('catatan', e.catatan ?? null)
  formData.append('paketkalibrasi', e.paketkalibrasi?.value ?? null)
  formData.append('lokasi', e.lokasi?.value ?? null)
  formData.append('rentangUkur', e.rentangUkur)
  formData.append('rentangUkurketPermintaanPelanggan', e.rentangUkurketPermintaanPelanggan ?? null)
  formData.append('mitraregistrasidetail', JSON.stringify(itemsToCheckout))

  isLoading.value = true

  await useApi().post(`/customer/save-checkout`, formData)
    .then(async (response: any) => {
      isLoading.value = false

      fetchKeranjangCustomer()
      fetchHistoryOrder()
      fetchHistoryOrderKelompok()
      closeCheckoutModal()

      window.setTimeout(() => {
        clear()
      }, 500)
    })
    .catch((e: any) => {
      isLoading.value = false
      console.clear()
      console.log(e)
    })

  isLoading.value = false
}

watch(orderAlat, (newVal) => {
  if (newVal == '0') {
    fetchHistoryOrderKelompok()
  } else if (newVal == '1') {
    fetchHistoryOrder()
  }
})

watch(selectAll, (newVal) => {
  dataKeranjang.value.forEach((item) => {
    item.checked = newVal
  })
})

watch(dataKeranjang, (newItems) => {
  const allChecked = newItems.length > 0 && newItems.every((item) => item.checked)
  selectAll.value = allChecked
}, { deep: true })

const clear = () => {
  item.value.merkalat = ''
  item.value.tipealat = ''
  item.value.serialnumber = ''
  item.value.rentangUkurketPermintaanPelanggan = ''
  item.value.rentangUkur = ''
  item.value.paketkalibrasi = ''
  item.value.catatan = ''
  item.value.lokasi = ''
  item.value.jenisorderAlat = ''
}

const onIndexChanged = (info: any) => {
  // direct access to info object
  const indexPrev = info.indexCached
  const indexCurrent = info.index

  // update style based on index
  info.slideItems[indexPrev].classList.remove('active')
  info.slideItems[indexCurrent].classList.add('active')
}
onMounted(() => {
  if (sliderElement.value && nextButtonElement.value && prevButtonElement.value) {
    slider = tns({
      container: sliderElement.value,
      controls: true,
      nav: false,
      mouseDrag: true,
      nextButton: nextButtonElement.value,
      prevButton: prevButtonElement.value,
      fixedWidth: 98,
      swipeAngle: false,
      items: 1,
      center: false,
      loop: true,
    })

    slider.events.on('indexChanged', onIndexChanged)
  }
})

const goTo = (index: number) => {
  if (slider) {
    slider.goTo(index)
  }
}

onUnmounted(() => {
  if (slider) {
    slider.events.off('indexChanged', onIndexChanged)
    slider.destroy()
  }

  if (checkoutCloseTimer.value) {
    window.clearTimeout(checkoutCloseTimer.value)
    checkoutCloseTimer.value = null
  }
})

const cetakAmsKelompok = (e: any) => {
  if (!e.iddetail) {
    H.alert('warning', 'Data tidak valid');
    return;
  }

  H.printBlade(`registrasi/cetak-ams?norecregis=${e.iddetail}`);
};

const cetakTandaTerima = (e: any) => {
  H.printBlade(`registrasi/cetak-tanda-terima?pdf=true&norec=${e.iddetail}`);
}

const openSelesaiTerima = async (e: any) => {
  modalselesaiterima.value = true
  const response = await useApi().get(`/asman/header-mitra?norec_pd=${e.iddetail}`)
  item.norec = response?.mitra?.[0]?.norec_pd ?? e.iddetail
  await fetchVersiList(item.norec)
}

const fetchVersiList = async (norecPd: any) => {
  const res = await useApi().get(`/registrasi/list-versi-terima?norec=${norecPd}`)
  versiList.value = Array.isArray(res) ? res : (res?.data ?? [])
}

const cetakAms = (e: any) => {
  if (!e.norec) {
    H.alert('warning', 'Data tidak valid');
    return;
  }

  H.printBlade(`registrasi/cetak-ams?norecregis=${e.norec}`);
};

const downloadTools = (item: any) => {
  const norec = item.norec;
  const token = useUserSession().token;
  // const url = `http://localhost:8000/service/registrasi/download-tools-customer?norecregis=${norec}&token=${token}`;
  const url = `/service/registrasi/download-tools-customer?norecregis=${norec}&token=${token}`;

  window.open(url, '_blank');
};

const downloadToolsKelompok = (item: any) => {
  const norec = item.iddetail || item.norec;
  const token = useUserSession().token;

  if (!norec) {
    H.alert('warning', 'Data tidak valid');
    return;
  }

  const url = `/service/registrasi/download-tools-customer?norecregis=${norec}&token=${token}`;
  window.open(url, '_blank');
};

const reportLoading = ref(false)
const reportExporting = ref(false)
const reportErrorMessage = ref('')
const customerReport = ref<any>(null)
const reportFilters = ref({
  dari: '',
  sampai: '',
  jenis_order: '',
  search: '',
})
let reportLoadedOnce = false

const reportSummary = computed(() => customerReport.value?.summary || {})
const reportRegistrationRows = computed<any[]>(() =>
  Array.isArray(customerReport.value?.registrations) ? customerReport.value.registrations : [],
)
const reportToolRows = computed<any[]>(() =>
  Array.isArray(customerReport.value?.tools) ? customerReport.value.tools : [],
)
const reportMonthlyRows = computed<any[]>(() =>
  Array.isArray(customerReport.value?.monthly) ? customerReport.value.monthly : [],
)
const reportUnitName = computed(() => customerReport.value?.unit?.namaperusahaan || 'Unit Customer')

const reportKpiCards = computed(() => [
  {
    label: 'Total Alat Terdaftar',
    value: formatReportNumber(reportSummary.value.total_alat),
    description: 'Master alat aktif milik unit',
    icon: 'feather:tool',
    color: '#2563eb',
  },
  {
    label: 'Total Pendaftaran',
    value: formatReportNumber(reportSummary.value.total_registrasi),
    description: `${formatReportNumber(reportSummary.value.pendaftaran_kalibrasi)} kalibrasi · ${formatReportNumber(reportSummary.value.pendaftaran_repair)} repair`,
    icon: 'feather:clipboard',
    color: '#0ea5e9',
  },
  {
    label: 'Alat Sudah Diorder',
    value: formatReportNumber(reportSummary.value.total_order_detail),
    description: 'Total baris alat pada seluruh pendaftaran',
    icon: 'feather:package',
    color: '#8b5cf6',
  },
  {
    label: 'Pekerjaan Selesai',
    value: formatReportNumber(reportSummary.value.total_selesai),
    description: `${formatReportPercent(reportSummary.value.completion_percent)} dari seluruh order`,
    icon: 'feather:check-circle',
    color: '#22c55e',
  },
  {
    label: 'Alat Sudah Diterima',
    value: formatReportNumber(reportSummary.value.total_diterima),
    description: `${formatReportPercent(reportSummary.value.acceptance_percent)} tingkat penerimaan`,
    icon: 'feather:inbox',
    color: '#14b8a6',
  },
  {
    label: 'Rating Layanan',
    value: Number(reportSummary.value.average_rating || 0) > 0
      ? Number(reportSummary.value.average_rating).toFixed(1)
      : '-',
    description: `${formatReportNumber(reportSummary.value.pendaftaran_menunggu)} pendaftaran menunggu verifikasi`,
    icon: 'feather:star',
    color: '#f59e0b',
  },
])

const reportChartBase = {
  chart: {
    toolbar: { show: false },
    fontFamily: 'var(--font)',
    animations: { enabled: true },
  },
  dataLabels: { enabled: false },
  grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
  noData: { text: 'Belum ada data pada filter ini' },
}

const reportMonthlyChartSeries = computed(() => [
  {
    name: 'Pendaftaran',
    data: reportMonthlyRows.value.map((row) => Number(row.registrations || 0)),
  },
  {
    name: 'Alat Diorder',
    data: reportMonthlyRows.value.map((row) => Number(row.tools || 0)),
  },
])

const reportMonthlyChartOptions = computed(() => ({
  ...reportChartBase,
  colors: ['#2563eb', '#06b6d4'],
  stroke: { curve: 'smooth', width: 3 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.34,
      opacityTo: 0.04,
      stops: [0, 92, 100],
    },
  },
  legend: { position: 'top', horizontalAlign: 'right' },
  xaxis: {
    categories: reportMonthlyRows.value.map((row) => row.label),
    labels: { style: { colors: '#64748b' } },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: { formatter: (value: number) => Math.round(value).toString() },
  },
  tooltip: { shared: true, intersect: false },
}))

const reportOrderTypeRows = computed<any[]>(() =>
  Array.isArray(customerReport.value?.jenis_order) ? customerReport.value.jenis_order : [],
)
const reportOrderTypeChartSeries = computed(() =>
  reportOrderTypeRows.value.map((row) => Number(row.count || 0)),
)
const reportOrderTypeChartOptions = computed(() => ({
  ...reportChartBase,
  labels: reportOrderTypeRows.value.map((row) => row.label),
  colors: ['#0ea5e9', '#f59e0b', '#8b5cf6'],
  legend: { position: 'bottom' },
  dataLabels: {
    enabled: true,
    formatter: (value: number) => `${Math.round(value)}%`,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Pendaftaran',
            formatter: () => formatReportNumber(reportSummary.value.total_registrasi),
          },
        },
      },
    },
  },
}))

const reportProgressRows = computed<any[]>(() =>
  Array.isArray(customerReport.value?.progress) ? customerReport.value.progress : [],
)
const reportProgressChartSeries = computed(() => [{
  name: 'Jumlah Alat',
  data: reportProgressRows.value.map((row) => Number(row.count || 0)),
}])
const reportProgressChartOptions = computed(() => ({
  ...reportChartBase,
  colors: ['#22c55e', '#0ea5e9', '#f59e0b', '#94a3b8'],
  plotOptions: {
    bar: {
      horizontal: true,
      distributed: true,
      borderRadius: 7,
      barHeight: '58%',
    },
  },
  xaxis: {
    categories: reportProgressRows.value.map((row) => row.label),
    labels: { formatter: (value: number) => Math.round(value).toString() },
  },
  legend: { show: false },
  dataLabels: { enabled: true },
}))

const reportScopeRows = computed<any[]>(() =>
  Array.isArray(customerReport.value?.lingkup) ? customerReport.value.lingkup : [],
)
const reportScopeChartSeries = computed(() => [{
  name: 'Jumlah Alat',
  data: reportScopeRows.value.map((row) => Number(row.count || 0)),
}])
const reportScopeChartOptions = computed(() => ({
  ...reportChartBase,
  colors: ['#2563eb'],
  plotOptions: {
    bar: {
      horizontal: false,
      borderRadius: 7,
      columnWidth: '48%',
    },
  },
  xaxis: {
    categories: reportScopeRows.value.map((row) => shortenReportLabel(row.label, 22)),
    labels: { rotate: -30, trim: true },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: { formatter: (value: number) => Math.round(value).toString() },
  },
  dataLabels: { enabled: true },
  tooltip: {
    x: {
      formatter: (_value: any, options: any) =>
        reportScopeRows.value[options.dataPointIndex]?.label || '-',
    },
  },
}))

async function loadCustomerReport() {
  reportLoading.value = true
  reportErrorMessage.value = ''

  try {
    const params = new URLSearchParams()
    if (reportFilters.value.dari) params.set('dari', reportFilters.value.dari)
    if (reportFilters.value.sampai) params.set('sampai', reportFilters.value.sampai)
    if (reportFilters.value.jenis_order) {
      params.set('jenis_order', reportFilters.value.jenis_order)
    }
    if (reportFilters.value.search.trim()) {
      params.set('search', reportFilters.value.search.trim())
    }

    const query = params.toString()
    customerReport.value = await useApi().get(
      `/customer/laporan-customer${query ? `?${query}` : ''}`,
    )
  } catch (error: any) {
    reportErrorMessage.value =
      error?.response?.data?.metaData?.message ||
      error?.message ||
      'Terjadi kesalahan saat memuat laporan.'
  } finally {
    reportLoading.value = false
  }
}

function resetReportFilters() {
  reportFilters.value = {
    dari: '',
    sampai: '',
    jenis_order: '',
    search: '',
  }
  loadCustomerReport()
}

function formatReportNumber(value: any) {
  return new Intl.NumberFormat('id-ID').format(Number(value || 0))
}

function formatReportPercent(value: any) {
  return `${Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`
}

function formatReportDate(value: any) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)

  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

function formatReportDateTime(value: any) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)

  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

function formatReportRating(value: any) {
  return Number(value || 0) > 0 ? `${Number(value).toFixed(1)} / 5` : '-'
}

function reportTitleCase(value: any) {
  const text = String(value || '-')
  return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase()
}

function shortenReportLabel(value: any, max: number) {
  const text = String(value || '-')
  return text.length > max ? `${text.slice(0, max - 1)}…` : text
}

function reportBadgeClass(value: any) {
  return String(value || '').toLowerCase() === 'repair' ? 'is-warning' : 'is-info'
}

function reportStatusClass(value: any) {
  const text = String(value || '').toLowerCase()
  if (text.includes('diterima') && !text.includes('belum')) return 'is-success'
  if (text.includes('selesai')) return 'is-success-soft'
  if (text.includes('terverifikasi') || text.includes('proses')) return 'is-info'
  return 'is-muted'
}

function safeReportFileName(value: any) {
  return String(value || 'unit')
    .replace(/[^A-Za-z0-9._-]+/g, '_')
    .replace(/_+/g, '_')
    .replace(/^_|_$/g, '')
}

function createReportSheet(title: string, headers: string[], rows: any[][], widths: number[]) {
  const generated = `Dibuat: ${formatReportDateTime(customerReport.value?.generated_at)}`
  const unit = `Unit: ${reportUnitName.value}`
  const ws = XLSX.utils.aoa_to_sheet([[title], [unit], [generated], [], headers, ...rows])
  const lastColumn = Math.max(headers.length - 1, 0)
  const fullRange = XLSX.utils.decode_range(ws['!ref'] || 'A1:A1')
  const thinBorder = {
    top: { style: 'thin', color: { rgb: 'D9E2F0' } },
    bottom: { style: 'thin', color: { rgb: 'D9E2F0' } },
    left: { style: 'thin', color: { rgb: 'D9E2F0' } },
    right: { style: 'thin', color: { rgb: 'D9E2F0' } },
  }

  ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: lastColumn } }]
  ws['!cols'] = widths.map((width) => ({ wch: width }))
  ws['!rows'] = [{ hpt: 30 }, { hpt: 20 }, { hpt: 20 }, { hpt: 8 }, { hpt: 24 }]
  ws['!autofilter'] = {
    ref: XLSX.utils.encode_range({
      s: { r: 4, c: 0 },
      e: { r: Math.max(4 + rows.length, 4), c: lastColumn },
    }),
  }
  ;(ws as any)['!freeze'] = {
    xSplit: 0,
    ySplit: 5,
    topLeftCell: 'A6',
    activePane: 'bottomLeft',
    state: 'frozen',
  }

  if (ws.A1) {
    ws.A1.s = {
      fill: { fgColor: { rgb: '173E70' } },
      font: { color: { rgb: 'FFFFFF' }, bold: true, sz: 18 },
      alignment: { horizontal: 'left', vertical: 'center' },
    }
  }

  for (let column = 0; column <= lastColumn; column++) {
    const headerAddress = XLSX.utils.encode_cell({ r: 4, c: column })
    if (ws[headerAddress]) {
      ws[headerAddress].s = {
        fill: { fgColor: { rgb: '2563EB' } },
        font: { color: { rgb: 'FFFFFF' }, bold: true },
        alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
        border: thinBorder,
      }
    }
  }

  for (let row = 5; row <= fullRange.e.r; row++) {
    for (let column = 0; column <= lastColumn; column++) {
      const address = XLSX.utils.encode_cell({ r: row, c: column })
      if (!ws[address]) ws[address] = { t: 's', v: '' }
      ws[address].s = {
        fill: { fgColor: { rgb: row % 2 === 0 ? 'F5F8FC' : 'FFFFFF' } },
        alignment: { vertical: 'top', wrapText: true },
        border: thinBorder,
      }
    }
  }

  return ws
}

async function exportCustomerReportExcel() {
  if (!customerReport.value || reportExporting.value) return
  reportExporting.value = true

  try {
    const workbook = XLSX.utils.book_new()
    const appliedFilters = customerReport.value.filters || {}
    const selectedPeriod = appliedFilters.dari || appliedFilters.sampai
      ? `${appliedFilters.dari || 'Awal'} s.d. ${appliedFilters.sampai || 'Sekarang'}`
      : 'Seluruh periode'

    const summaryRows = [
      ['Periode laporan', selectedPeriod],
      [
        'Jenis pendaftaran',
        appliedFilters.jenis_order
          ? reportTitleCase(appliedFilters.jenis_order)
          : 'Semua jenis',
      ],
      ['Kata kunci', appliedFilters.search || '-'],
      ['Total alat terdaftar', Number(reportSummary.value.total_alat || 0)],
      ['Total pendaftaran', Number(reportSummary.value.total_registrasi || 0)],
      ['Pendaftaran kalibrasi', Number(reportSummary.value.pendaftaran_kalibrasi || 0)],
      ['Pendaftaran repair', Number(reportSummary.value.pendaftaran_repair || 0)],
      ['Pendaftaran terverifikasi', Number(reportSummary.value.pendaftaran_terverifikasi || 0)],
      ['Pendaftaran menunggu', Number(reportSummary.value.pendaftaran_menunggu || 0)],
      ['Total alat/order', Number(reportSummary.value.total_order_detail || 0)],
      ['Pekerjaan selesai', Number(reportSummary.value.total_selesai || 0)],
      ['Belum selesai', Number(reportSummary.value.total_belum_selesai || 0)],
      ['Sudah diterima', Number(reportSummary.value.total_diterima || 0)],
      ['Belum diterima', Number(reportSummary.value.total_belum_diterima || 0)],
      ['Persentase selesai', formatReportPercent(reportSummary.value.completion_percent)],
      ['Persentase diterima', formatReportPercent(reportSummary.value.acceptance_percent)],
      ['Rata-rata rating', Number(reportSummary.value.average_rating || 0)],
    ]
    XLSX.utils.book_append_sheet(
      workbook,
      createReportSheet(
        'RINGKASAN LAPORAN CUSTOMER',
        ['Metrik', 'Nilai'],
        summaryRows,
        [32, 24],
      ),
      'Ringkasan',
    )

    const registrationData = reportRegistrationRows.value.map((row, index) => [
      index + 1,
      row.nopendaftaran || '-',
      formatReportDateTime(row.tglregistrasi),
      reportTitleCase(row.jenisorder),
      row.lokasi || '-',
      row.status_verifikasi || '-',
      row.statusorder || '-',
      Number(row.jumlah_alat || 0),
      Number(row.jumlah_selesai || 0),
      Number(row.jumlah_belum_selesai || 0),
      Number(row.jumlah_diterima || 0),
      Number(row.jumlah_belum_diterima || 0),
      row.petugas || '-',
      row.catatan || '-',
      row.filecustomertools ? 'Tersedia' : 'Tidak Ada',
      row.iskaji === null || row.iskaji === undefined
        ? 'Belum Dikaji'
        : (row.iskaji ? 'Sudah Dikaji' : 'Belum Dikaji'),
    ])
    XLSX.utils.book_append_sheet(
      workbook,
      createReportSheet(
        'LAPORAN PENDAFTARAN',
        [
          'No',
          'No Pendaftaran',
          'Tanggal Registrasi',
          'Jenis',
          'Lokasi',
          'Verifikasi',
          'Status Order',
          'Jumlah Alat',
          'Selesai',
          'Belum Selesai',
          'Diterima',
          'Belum Diterima',
          'Petugas',
          'Catatan',
          'Dokumen Customer',
          'Status Kaji',
        ],
        registrationData,
        [6, 20, 20, 12, 18, 18, 18, 12, 10, 14, 10, 14, 18, 30, 18, 16],
      ),
      'Pendaftaran',
    )

    const toolData = reportToolRows.value.map((row, index) => [
      index + 1,
      row.nopendaftaran || '-',
      row.noorderalat || '-',
      formatReportDateTime(row.tglregistrasi),
      row.namaproduk || '-',
      row.namamerk || '-',
      row.namatipe || '-',
      row.namaserialnumber || '-',
      reportTitleCase(row.jenisorder),
      row.lingkupkalibrasi || '-',
      row.lokasi || '-',
      row.status_verifikasi || '-',
      row.status_proses || '-',
      row.selesai ? 'Ya' : 'Tidak',
      row.diterima ? 'Ya' : 'Tidak',
      formatReportDateTime(row.tanggal_selesai),
      row.durasikalbrasi || '-',
      row.statusorderpenyelia || '-',
      row.iskaji === null || row.iskaji === undefined
        ? 'Belum Dikaji'
        : (row.iskaji ? 'Sudah Dikaji' : 'Belum Dikaji'),
      row.rating || '-',
      row.keterangan || '-',
    ])
    XLSX.utils.book_append_sheet(
      workbook,
      createReportSheet(
        'LAPORAN PER ALAT YANG SUDAH DIORDER',
        [
          'No',
          'No Pendaftaran',
          'No Order Alat',
          'Tanggal Registrasi',
          'Nama Alat',
          'Merk',
          'Tipe',
          'Serial Number',
          'Jenis',
          'Lingkup',
          'Lokasi',
          'Verifikasi',
          'Status Proses',
          'Selesai',
          'Diterima',
          'Tanggal Selesai',
          'Durasi',
          'Status Penyelia',
          'Status Kaji',
          'Rating',
          'Keterangan',
        ],
        toolData,
        [6, 20, 20, 20, 24, 16, 16, 18, 12, 24, 18, 18, 24, 10, 10, 20, 14, 20, 16, 10, 32],
      ),
      'Detail Alat',
    )

    const monthlyData = reportMonthlyRows.value.map((row, index) => [
      index + 1,
      row.month,
      row.label,
      Number(row.registrations || 0),
      Number(row.tools || 0),
    ])
    XLSX.utils.book_append_sheet(
      workbook,
      createReportSheet(
        'STATISTIK BULANAN',
        ['No', 'Bulan', 'Label', 'Pendaftaran', 'Alat Diorder'],
        monthlyData,
        [6, 14, 16, 16, 16],
      ),
      'Statistik Bulanan',
    )

    const distributionData: any[][] = []
    const appendDistribution = (category: string, rows: any[]) => {
      rows.forEach((row) => {
        distributionData.push([
          category,
          row.label,
          Number(row.count || 0),
          Number(row.percent || 0) / 100,
        ])
      })
    }
    appendDistribution('Jenis Pendaftaran', reportOrderTypeRows.value)
    appendDistribution('Progress Alat', reportProgressRows.value)
    appendDistribution('Lingkup', reportScopeRows.value)
    appendDistribution(
      'Lokasi',
      Array.isArray(customerReport.value.lokasi) ? customerReport.value.lokasi : [],
    )

    const distributionSheet = createReportSheet(
      'DISTRIBUSI DATA',
      ['Kategori', 'Label', 'Jumlah', 'Persentase'],
      distributionData,
      [24, 34, 12, 14],
    )
    distributionData.forEach((_row, index) => {
      const address = XLSX.utils.encode_cell({ r: index + 5, c: 3 })
      if (distributionSheet[address]) {
        distributionSheet[address].z = '0.0%'
        distributionSheet[address].s = {
          ...distributionSheet[address].s,
          numFmt: '0.0%',
        }
      }
    })
    XLSX.utils.book_append_sheet(workbook, distributionSheet, 'Distribusi')

    const topToolsData = (
      Array.isArray(customerReport.value.top_tools) ? customerReport.value.top_tools : []
    ).map((row: any, index: number) => [
      index + 1,
      row.alat || '-',
      row.merk_tipe || '-',
      row.serial || '-',
      Number(row.jumlah_order || 0),
    ])
    XLSX.utils.book_append_sheet(
      workbook,
      createReportSheet(
        'ALAT PALING SERING DIORDER',
        ['No', 'Nama Alat', 'Merk / Tipe', 'Serial Number', 'Jumlah Order'],
        topToolsData,
        [6, 28, 24, 20, 14],
      ),
      'Top Alat',
    )

    XLSXStyle.writeFile(
      workbook,
      `Laporan_Customer_${safeReportFileName(reportUnitName.value)}_${new Date().toISOString().slice(0, 10)}.xlsx`,
    )
  } finally {
    reportExporting.value = false
  }
}

watch(
  activeSection,
  (section) => {
    if (section === 'laporan' && !reportLoadedOnce) {
      reportLoadedOnce = true
      loadCustomerReport()
    }
  },
  { immediate: true },
)

const initDashboard = async () => {
  await fetchStatusCustomer()
  await fetchKeranjangCustomer()
  if (dataCustomer.value.mitrafk != null) {
    await fetchDataAlat()
  }
  await fetchHistoryOrder()
  await fetchHistoryOrderKelompok()
}

onMounted(() => {
  initDashboard()
  const key = 'ulab_welcome_banner_customer_v1'
  if (localStorage.getItem(key) !== '1') {
    openWelcome.value = true
  }
})

</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/customer.scss';

.dashboard-home-hero {
  position: relative;
  overflow: hidden;
  display: grid;
  grid-template-columns: minmax(300px, 0.9fr) minmax(0, 1.1fr);
  align-items: center;
  gap: 24px;
  min-height: 126px;
  margin-bottom: 16px;
  padding: 16px 26px;
  border-radius: 18px;
  color: #fff;
  background:
    radial-gradient(circle at 68% 120%, rgba(255, 255, 255, 0.15), transparent 38%),
    linear-gradient(118deg, #174ed6 0%, #087fee 50%, #08c7d5 100%);
  box-shadow: 0 10px 26px rgba(20, 87, 205, 0.16);
  font-family: var(--font);

  &::before,
  &::after {
    content: '';
    position: absolute;
    width: 280px;
    height: 280px;
    right: 22%;
    bottom: -220px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 50%;
    pointer-events: none;
  }

  &::after {
    width: 360px;
    height: 360px;
    right: 17%;
    bottom: -275px;
  }
}

.dashboard-home-visual {
  position: relative;
  z-index: 1;
  align-self: stretch;
  min-width: 0;
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
}

.dashboard-home-visual::before {
  content: '';
  position: absolute;
  inset: 8px 10% 4px 0;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.18), transparent 68%);
  pointer-events: none;
}

.dashboard-home-visual img {
  position: relative;
  z-index: 1;
  display: block;
  width: min(100%, 520px);
  max-height: 154px;
  object-fit: contain;
  object-position: left bottom;
  transform: translateY(16px);
  filter: drop-shadow(0 9px 12px rgba(5, 37, 96, 0.2));
}

.dashboard-home-copy {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 18px;
  min-width: 0;
}

.dashboard-home-icon {
  width: 64px;
  height: 64px;
  flex: 0 0 64px;
  display: grid;
  place-items: center;
  border-radius: 16px;
  color: #fff;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.28);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
  font-size: 30px;
}

.dashboard-home-icon .iconify {
  color: #fff !important;
}

.dashboard-home-eyebrow {
  display: block;
  margin-bottom: 7px;
  color: rgba(255, 255, 255, 0.82);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.dashboard-home-copy h1 {
  margin: 0;
  color: #fff;
  font-family: var(--font);
  font-size: clamp(1.55rem, 2.2vw, 1.85rem);
  line-height: 1.2;
  font-weight: 600;
  letter-spacing: -0.015em;
}

.dashboard-home-copy p {
  margin: 0;
  color: rgba(255, 255, 255, 0.88);
  font-size: 13px;
  font-weight: 400;
}

.dashboard-home-welcome {
  margin-top: 5px !important;
}

.dashboard-home-description {
  margin-top: 2px !important;
  color: rgba(255, 255, 255, 0.74) !important;
  font-size: 12px !important;
}

@media (max-width: 900px) {
  .dashboard-home-hero {
    grid-template-columns: minmax(210px, 0.8fr) minmax(0, 1.2fr);
    gap: 16px;
    padding-inline: 20px;
  }
}

@media (max-width: 680px) {
  .dashboard-home-hero {
    display: block;
    min-height: 150px;
    padding: 16px;
    border-radius: 16px;
  }

  .dashboard-home-visual {
    position: absolute;
    right: -28px;
    bottom: 0;
    width: 64%;
    height: 100%;
    opacity: 0.24;
  }

  .dashboard-home-visual img {
    width: 100%;
    max-height: 142px;
    transform: translateY(10px);
  }

  .dashboard-home-copy {
    position: relative;
    z-index: 2;
    align-items: flex-start;
    gap: 12px;
  }

  .dashboard-home-icon {
    width: 52px;
    height: 52px;
    flex-basis: 52px;
    border-radius: 13px;
    font-size: 24px;
  }

  .dashboard-home-copy h1 {
    font-size: 1.45rem;
  }

  .dashboard-home-description {
    max-width: 220px;
  }
}

.product-card {
  background: var(--card-bg-color);
  border: 1px solid var(--card-border-color);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  height: 100%;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  position: relative;
  z-index: 1;

  &:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    z-index: 10;
  }

  &.is-kan-accredited {
    border-color: var(--info);
    box-shadow: 0 0 0 2px var(--info-light-20), 0 8px 20px rgba(0, 0, 0, 0.12);
  }
}

.kan-ribbon {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 90px;
  height: 90px;
  overflow: hidden;
  z-index: 1;

  span {
    position: absolute;
    display: block;
    width: 125px;
    padding: 8px 0;
    background: linear-gradient(45deg, #6cf040, #d19a26);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
    color: #167ad2;
    font: 700 13px/1 'Lato', sans-serif;
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
    text-transform: uppercase;
    text-align: center;
    right: -25px;
    top: 22px;
    transform: rotate(45deg);
  }
}

.product-image-wrapper {
  margin: 0;
  width: 100%;
  aspect-ratio: 4 / 3;
  background-color: var(--widget-grey);

  .product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
}

.product-info-wrapper {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.product-info {
  flex-grow: 1;
  margin-bottom: 1rem;

  .product-name {
    font-family: var(--font-alt);
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--dark-text);
    margin-bottom: 0.25rem;
    line-height: 1.3;
  }

  .product-sn,
  .product-spec {
    font-family: var(--font-alt);
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--dark-text);
    margin-bottom: 0.25rem;
    line-height: 1.3;
  }
}

.product-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  margin-top: auto;
  gap: 0.5rem;

  .button {
    flex-grow: 1;
    min-width: 9rem;
    width: 100%;
  }
}

.product-utility-actions {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.5rem;
  margin-top: 0.65rem;
  padding-top: 0.65rem;
  border-top: 1px solid var(--card-border-color);
}

.product-utility-action {
  appearance: none;
  min-width: 0;
  min-height: 78px;
  padding: 0.6rem 0.35rem;
  border: 1px solid var(--card-border-color);
  border-radius: 10px;
  background: var(--card-bg-color);
  color: var(--dark-text);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  font-family: var(--font-alt);
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 12px rgba(15, 23, 42, 0.1);
  }

  &:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
  }

  .action-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
  }

  .action-copy {
    min-width: 0;

    strong,
    small {
      display: block;
      line-height: 1.2;
    }

    strong {
      color: var(--dark-text);
      font-size: 0.74rem;
      font-weight: 700;
      white-space: nowrap;
    }

    small {
      color: var(--light-text);
      font-size: 0.66rem;
      margin-top: 0.15rem;
    }
  }

  &.is-history {
    .action-icon {
      color: var(--info);
      background: rgba(3, 169, 244, 0.1);
    }

    &:hover {
      border-color: var(--info);
    }
  }

  &.is-edit {
    .action-icon {
      color: var(--warning);
      background: rgba(255, 152, 0, 0.1);
    }

    &:hover {
      border-color: var(--warning);
    }
  }

  &.is-delete {
    .action-icon {
      color: var(--danger);
      background: rgba(241, 70, 104, 0.1);
    }

    &:hover {
      border-color: var(--danger);
    }
  }
}

.product-measurement-action {
  margin-top: 0.5rem;

  .button {
    width: 100%;
  }
}

.is-dark {
  .product-utility-actions {
    border-color: var(--dark-sidebar-light-12);
  }

  .product-utility-action {
    background: var(--dark-sidebar-light-4);
    border-color: var(--dark-sidebar-light-12);

    .action-copy strong {
      color: var(--dark-dark-text);
    }
  }
}

.meta-left {
  min-width: 0;
  flex: 1;
}

.meta-info-line {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.meta-block {
  min-width: 80px;
  display: inline-block;
  white-space: nowrap;
}

.icon-separator {
  font-size: 6px;
  color: #aaa;
}

.progress-tracker-wrapper {
  display: flex;
  align-items: center;
  margin-top: 10px;
  gap: 6px;
}

.step {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #d1d5db;
  color: white;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.step.active {
  background-color: #10b981;
}

.connector {
  flex-grow: 1;
  min-width: 40px;
}

.line-done {
  height: 4px;
  background-color: #10b981;
  border-radius: 2px;
}

.line-default {
  height: 4px;
  background-color: #d1d5db;
  border-radius: 2px;
}

.list-view-item-inner {
  min-height: 160px;
  display: flex;
}

.cart-item+.cart-item {
  border-top: 1px solid #f0f0f0;
  padding-top: 8px;
  margin-top: 8px;
}

.p-dialog.loading-dialog {
  border: none !important;
  border-radius: 0 !important;
  max-width: 100vw !important;
  max-height: 120vh !important;
}

.p-dialog.loading-dialog .p-dialog-content {
  background: transparent !important;
  box-shadow: none !important;
  padding: 0 !important;
}

.p-dialog.loading-dialog,
.p-dialog.loading-dialog.p-dialog-enter-active,
.p-dialog.loading-dialog.p-dialog-leave-active {
  animation: none !important;
  transition: none !important;
}

body>.p-dialog-mask:has(.p-dialog.loading-dialog) {
  background-color: rgba(255, 255, 255, 0.5) !important;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  animation: none !important;
}

.search-menu-rad {
  height: 56px !important;
  white-space: nowrap;
  display: flex;
  flex-shrink: 0;
  align-items: center;
  background-color: white;
  border-radius: 8px;
  width: 100%;
  padding-left: 0.75rem;

  >div:not(:last-of-type) {
    border-right: 1px solid var(--search-border-color);
  }

  .search-bar {
    height: 55px;
    width: 100%;
    position: relative;
    display: flex;
    align-items: center;
    padding-right: 1.5rem;

    .field {
      width: 100%;
    }

    .multiselect-tags {
      padding-left: 2.5rem;
    }
  }

  .search-location-rad,
  .search-job,
  .search-salary {
    display: flex;
    align-items: center;
    width: 50%;
    font-size: 14px;
    font-weight: 500;
    padding: 0 25px;
    height: 100%;
    font-family: var(--font);

    input {
      width: 100%;
      height: 90%;
      display: block;
      font-family: var(--font);
      color: var(--input-color);
      background-color: transparent;
      border: none;
    }

    svg {
      margin-right: 0.5rem;
      width: 18px;
      color: var(--info);
      flex-shrink: 0;
    }
  }

  .search-button-rad {
    background-color: var(--info);
    min-width: 100px;
    height: 56px !important;
    border: none;
    font-weight: 500;
    font-family: var(--font);
    padding: 0 1rem;
    border-radius: 0 0.75rem 0.75rem 0;
    color: white;
    cursor: pointer;
    margin-left: auto;
  }
}

.search-widget {
  flex: 1;
  display: inline-block;
  width: 100%;
  padding: 10px;
  background-color: var(--white);
  border-radius: 16px;
  border: 1px solid var(--fade-grey-dark-3);
  transition: all 0.3s;
}

.food-delivery-dashboard {
  display: flex;
  padding-bottom: 115px;

  &.is-navbar {
    margin-top: 30px;
  }

  .left {
    width: 100%;

    .left-header {
      display: flex;
      align-items: center;
      padding: 10px;
      border-radius: 16px;
      background: var(--info-light-20);
      font-family: var(--font);

      .header-image {
        position: relative;
        height: 150px;
        width: 280px;

        img {
          position: absolute;
          top: -40px;
          left: -30px;
          display: block;
        }
      }

      .header-meta {
        margin-left: 0;
        margin-bottom: 20px;

        h3 {
          font-family: var(--font-alt);
          font-weight: 700;
          font-size: 1.6rem;
        }

        p {
          font-weight: 400;
          color: var(--info-dark-14);
          margin-bottom: 8px;
        }
      }
    }

    .left-body {
      .restaurants {
        .restaurants-toolbar {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin: 20px 0;
          font-family: var(--font);

          .left {
            h3 {
              font-family: var(--font-alt);
              color: var(--dark-text);
              font-size: 1.3rem;
              font-weight: 600;
            }
          }
        }
      }

      .restaurants-list {
        padding: 30px 0;
      }
    }
  }
}

.is-dark {
  .food-delivery-dashboard {
    .left {
      .left-header {
        background: var(--dark-sidebar-light-2) !important;

        .header-meta {
          h3 {
            color: var(--dark-dark-text);
          }

          p {
            color: var(--info);
          }
        }
      }

      .left-body {
        .restaurants {
          .restaurants-toolbar {
            .left {
              h3 {
                color: var(--dark-dark-text);
              }
            }
          }
        }
      }
    }
  }
}

.cart-widget {
  @include vuero-l-card;

  &.is-straight {
    @include vuero-s-card;
  }

  .cart-items {
    .cart-item {
      display: flex;
      margin: 8px 0;

      .meta {
        margin-left: 12px;
        display: flex;
        flex-direction: column;

        span {
          display: block;
          font-family: var(--font);

          &:first-child {
            font-size: 0.9rem;
            color: var(--light-text);
          }

          &:nth-child(2) {
            color: var(--dark-text);
            margin-top: auto;
            font-weight: 600;
            font-size: 1.2rem;
          }
        }
      }
    }
  }

  .cart-button {
    padding-top: 16px;

    .button {
      min-height: 50px;
      border-radius: 10px;
    }
  }
}

.is-dark {
  .cart-widget {
    @include vuero-card--dark;

    .cart-items {
      .cart-item {
        .meta {
          span {
            &:nth-child(2) {
              color: var(--info);
            }
          }
        }
      }
    }
  }
}

.radial-wrap {
  display: flex;
  flex-direction: column;
  height: calc(100% - 44px);
}

.add-to-cart-modal-content {
  padding: 0.5rem 1rem 1.5rem 1rem;
  text-align: center;
}

.product-name-header {
  background-color: var(--widget-grey);
  color: var(--dark-text);
  padding: 1.25rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;

  h2 {
    font-family: var(--font-alt);
    font-weight: 700;
    font-size: 1.5rem;
    line-height: 1.2;
  }
}

.is-dark .product-name-header {
  background-color: var(--dark-sidebar-light-4);
  color: var(--dark-dark-text);
}

.order-type-label {
  font-size: 0.95rem;
  color: var(--light-text);
  margin-bottom: 1.25rem;
  font-weight: 500;
}

.order-type-options {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.option-card {
  display: flex;
  align-items: center;
  text-align: left;
  padding: 1rem 1.25rem;
  border: 2px solid var(--card-border-color);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 100%;
  position: relative;

  .iconify {
    font-size: 2rem;
    color: var(--info);
    margin-right: 1rem;
    flex-shrink: 0;
  }

  .option-details {
    line-height: 1.4;

    strong {
      font-weight: 600;
      color: var(--dark-text);
      display: block;
    }

    span {
      font-size: 0.9rem;
      color: var(--light-text);
    }
  }

  .radio-check {
    margin-left: auto;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid var(--card-border-color);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;

    &::after {
      content: '';
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background-color: var(--white);
      transform: scale(0);
      transition: transform 0.3s ease;
    }
  }

  &:hover {
    border-color: var(--info-light-10);
    transform: translateY(-2px);
  }

  &.is-selected {
    border-color: var(--info);
    background-color: var(--primary-light-40);

    .radio-check {
      background-color: var(--info);
      border-color: var(--info);

      &::after {
        transform: scale(1);
      }
    }
  }
}

@media (max-width: 767px) {
  .order-type-options {
    flex-direction: column;
  }
}

:deep(.modal-card) {
  display: flex !important;
  flex-direction: column !important;
  max-height: calc(100vh - 40px);
}

:deep(.modal-card-body) {
  overflow-y: auto !important;
  flex-grow: 1;
  flex-shrink: 1;
}

.checkout-modal-content {
  padding: 0.25rem 0.5rem;
}

.checkout-section {
  margin-bottom: 1.3rem;

  .label,
  .v-label,
  .p.v-label {
    margin-bottom: 0.75rem !important;
    font-size: 0.9rem;
    font-weight: 500;
  }
}

.selection-group {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.selection-card {
  padding: 0.75rem 1rem;
  border: none;
  border-bottom: 1px solid var(--card-border-color);
  border-radius: 8px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  font-weight: 500;

  &:last-child {
    border-bottom: none;
  }

  &:hover {
    background-color: var(--fade-grey-light-3);
    color: var(--info);
  }

  &.is-selected {
    border: none;
    outline: 2px solid var(--info);
    background-color: var(--info-light-40);
    color: var(--info-dark-5);
    font-weight: 600;
  }
}

.file-upload-wrapper {
  .p-fileupload-buttonbar {
    padding: 0.75rem;
  }

  .p-button {
    font-size: 0.9rem !important;
  }

  .p-fileupload-content {
    padding: 1rem;
    border-color: var(--card-border-color);
  }
}

.fade-slow-enter-active,
.fade-slow-leave-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}

.fade-slow-enter-from,
.fade-slow-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@media (max-width: 767px) {
  .is-full-mobile {
    width: 100% !important;
    flex: none;
  }

  .selection-group {
    grid-template-columns: 1fr;
  }
}

.segmented {
  position: relative;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
  padding: 6px;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.78);
  border: 1px solid rgba(15, 23, 42, 0.18);
  box-shadow:
    0 10px 26px rgba(0, 0, 0, 0.10),
    inset 0 0 0 1px rgba(255, 255, 255, 0.55);
  width: min(560px, 92vw);
  margin: 0 auto;
}

.segmented-btn {
  position: relative;
  z-index: 2;
  border: 0;
  background: transparent;
  padding: 12px 14px;
  border-radius: 9999px;
  font-weight: 800;
  font-size: 0.95rem;
  cursor: pointer;
  line-height: 1;
  color: rgba(15, 23, 42, 0.62);
  transition: color 0.15s ease;
  width: 100%;
}

.segmented-btn.is-active {
  color: rgba(15, 23, 42, 0.95);
}

.segmented-indicator {
  position: absolute;
  z-index: 1;
  top: 6px;
  left: 6px;
  width: calc(50% - 6px);
  height: calc(100% - 12px);
  border-radius: 9999px;
  background: rgba(16, 185, 129, 0.32);
  border: 1px solid rgba(16, 185, 129, 0.28);
  box-shadow:
    0 10px 22px rgba(0, 0, 0, 0.12),
    inset 0 0 0 1px rgba(255, 255, 255, 0.55);
  transition: transform 0.18s ease;
}

.segmented-indicator.to-right {
  transform: translateX(calc(100% + 6px));
}

.segmented-indicator.to-left {
  transform: translateX(0);
}

@media (max-width: 480px) {
  .segmented {
    width: 92vw;
  }

  .segmented-btn {
    padding: 12px 10px;
    font-size: 0.9rem;
  }
}

.status-customer-wrap {
  width: 100%;
  height: 100vh;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 24px;
  max-width: 920px;
  margin: 0 auto;
}

.status-title {
  font-size: 20pt;
  font-weight: 800;
  color: #000;
  margin: 0 0 14px 0;
}

.status-label {
  font-size: 9pt;
  font-weight: 700;
  color: #000;
  margin-top: 10px;
}

.status-hint {
  margin-top: 8px;
  font-size: 11px;
  color: #d30000;
}

.status-form.columns {
  margin-left: 0 !important;
  margin-right: 0 !important;
}

.status-form .column {
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

@media (max-width: 768px) {
  .status-customer-wrap {
    padding: 14px;
  }

  .status-title {
    font-size: 18pt;
  }
}

/* ======================================================= */
/* ====== FLOATING NAV DESKTOP + MOBILE CUSTOMER ========= */
/* ======================================================= */
.customer-floating-nav {
  position: fixed;
  isolation: isolate;
  left: 50%;
  bottom: calc(18px + env(safe-area-inset-bottom));
  z-index: 900;
  width: min(680px, calc(100% - 28px));
  transform: translateX(-50%);
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  padding: 9px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(226, 232, 240, 0.96);
  box-shadow:
    0 22px 55px rgba(15, 23, 42, 0.18),
    0 5px 16px rgba(15, 23, 42, 0.08);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}

.customer-floating-indicator {
  position: absolute;
  z-index: 0;
  top: 9px;
  bottom: 9px;
  left: 9px;
  width: calc((100% - 42px) / 4);
  border-radius: 999px;
  background: linear-gradient(135deg, #2563eb, #06b6d4);
  box-shadow: 0 13px 28px rgba(37, 99, 235, 0.26);
  pointer-events: none;
  will-change: transform;
  transition: transform 520ms cubic-bezier(0.22, 1, 0.36, 1);
}

.customer-floating-indicator.is-cart {
  transform: translateX(0);
}

.customer-floating-indicator.is-keranjang {
  transform: translateX(calc(100% + 8px));
}

.customer-floating-indicator.is-activity {
  transform: translateX(calc(200% + 16px));
}

.customer-floating-indicator.is-laporan {
  transform: translateX(calc(300% + 24px));
}

.customer-floating-item {
  position: relative;
  z-index: 1;
  border: 0;
  outline: 0;
  min-height: 62px;
  padding: 8px 12px;
  border-radius: 999px;
  background: transparent;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  cursor: pointer;
  font-family: var(--font);
  transition:
    color 0.24s ease 0.08s,
    transform 0.18s ease,
    text-shadow 0.24s ease;
}

.customer-floating-item:active {
  transform: scale(0.96);
}

.customer-floating-item.is-active {
  background: transparent;
  color: #fff;
  box-shadow: none;
  text-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
}

.customer-floating-item span:last-child {
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
}

.floating-icon {
  position: relative;
  display: inline-grid;
  place-items: center;
  width: 25px;
  height: 25px;
}

.floating-icon svg {
  width: 22px;
  height: 22px;
}

.floating-icon em {
  position: absolute;
  top: -9px;
  right: -15px;
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  background: #f43f5e;
  color: #fff;
  font-size: 10px;
  font-style: normal;
  font-weight: 900;
  border: 2px solid #fff;
  box-shadow: 0 5px 12px rgba(244, 63, 94, 0.28);
}

.customer-floating-item.is-active .floating-icon em {
  background: #fff;
  color: #2563eb;
  border-color: rgba(255, 255, 255, 0.86);
}

.is-dark {
  .customer-floating-nav {
    background: rgba(15, 23, 42, 0.94);
    border-color: rgba(51, 65, 85, 0.95);
    box-shadow:
      0 22px 55px rgba(0, 0, 0, 0.36),
      0 5px 16px rgba(0, 0, 0, 0.18);
  }

  .customer-floating-item {
    color: #94a3b8;
  }

  .customer-floating-item.is-active {
    color: #fff;
  }

  .floating-icon em {
    border-color: rgba(15, 23, 42, 0.95);
  }
}

/* ======================================================= */
/* ============ KERANJANG MODERN DI KONTEN ============== */
/* ======================================================= */
.customer-cart-page {
  display: grid;
  gap: 16px;
  width: 100%;
  font-family: var(--font);
}

.cart-hero-modern {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  min-height: 126px;
  padding: 22px 26px;
  border-radius: 18px;
  background:
    radial-gradient(circle at 68% 120%, rgba(255, 255, 255, 0.15), transparent 38%),
    linear-gradient(118deg, #174ed6 0%, #087fee 50%, #08c7d5 100%);
  color: #fff;
  box-shadow: 0 10px 26px rgba(20, 87, 205, 0.16);

  &::before,
  &::after {
    content: '';
    position: absolute;
    width: 280px;
    height: 280px;
    right: 22%;
    bottom: -220px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 50%;
    pointer-events: none;
  }

  &::after {
    width: 360px;
    height: 360px;
    right: 17%;
    bottom: -275px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .customer-floating-indicator {
    transition: none;
  }
}

.cart-hero-left {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 18px;
  min-width: 0;
}

.cart-hero-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.28);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
  color: #fff;
  font-size: 30px;
  flex-shrink: 0;
}

.cart-hero-icon .iconify {
  color: #fff !important;
}

.cart-eyebrow {
  display: inline-block;
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.82);
  margin-bottom: 7px;
}

.cart-hero-modern h3 {
  margin: 0;
  font-family: var(--font);
  font-size: clamp(1.55rem, 2.2vw, 1.85rem);
  line-height: 1.2;
  font-weight: 600;
  letter-spacing: -0.015em;
  color: #fff;
}

.cart-hero-modern p {
  margin: 5px 0 0;
  color: rgba(255, 255, 255, 0.88);
  font-size: 13px;
  font-weight: 400;
}

.cart-hero-stats {
  position: relative;
  z-index: 1;
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}

.mini-stat {
  min-width: 132px;
  min-height: 78px;
  padding: 13px 15px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.24);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.16);
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.mini-stat-icon {
  width: 30px;
  height: 30px;
  border-radius: 9px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  color: #fff;
  background: rgba(255, 255, 255, 0.14);
  font-size: 16px;
}

.mini-stat-copy {
  min-width: 0;
}

.mini-stat-copy>span {
  display: block;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: rgba(255, 255, 255, 0.82);
  font-weight: 500;
  white-space: nowrap;
}

.mini-stat-copy strong {
  display: block;
  margin-top: 5px;
  font-size: 24px;
  line-height: 1;
  font-weight: 600;
  color: #fff;
}

.cart-modern-panel {
  border-radius: 18px;
  padding: 18px;
  background: #fff;
  border: 1px solid #e4eaf2;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.is-dark .cart-modern-panel {
  background: rgba(30, 41, 59, 0.94);
  border-color: rgba(51, 65, 85, 0.95);
}

.cart-modern-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 2px 2px 15px;
  border-bottom: 1px solid #e5eaf1;
  margin-bottom: 15px;
}

.is-dark .cart-modern-toolbar {
  border-color: rgba(51, 65, 85, 0.95);
}

.cart-toolbar-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.cart-toolbar-left :deep(.checkbox) {
  color: var(--dark-text);
  font-family: var(--font);
  font-size: 13px;
  font-weight: 400;
}

.is-dark .cart-toolbar-left :deep(.checkbox) {
  color: var(--dark-dark-text);
}

.cart-selection-count {
  color: var(--light-text);
  font-size: 13px;

  strong {
    color: var(--info);
    font-weight: 600;
  }
}

.cart-delete-selected {
  min-height: 38px;
  padding-inline: 14px !important;
  border-radius: 9px !important;
  font-family: var(--font) !important;
  font-weight: 500 !important;
}

.cart-modern-list {
  display: grid;
  gap: 10px;
}

.cart-group-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 4px 0 0;
  padding: 0 4px;
}

.cart-group-title span {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #172554;

  .iconify {
    color: var(--info);
    font-size: 16px;
  }
}

.is-dark .cart-group-title span {
  color: var(--dark-dark-text);
}

.cart-group-title small {
  font-size: 12px;
  color: var(--light-text);
  font-weight: 400;
}

.cart-modern-item {
  display: grid;
  grid-template-columns: 30px 68px minmax(0, 1fr) auto;
  gap: 14px;
  align-items: center;
  min-height: 92px;
  padding: 11px 16px;
  border-radius: 13px;
  background: #fff;
  border: 1px solid #e4eaf2;
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
  transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.cart-modern-item:hover {
  transform: translateY(-1px);
  border-color: #c7d7ee;
  box-shadow: 0 7px 18px rgba(15, 23, 42, 0.07);
}

.cart-modern-item.is-checked {
  background: #eff6ff;
  border-color: #93c5fd;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.08);
}

.is-dark .cart-modern-item {
  background: rgba(15, 23, 42, 0.72);
  border-color: rgba(51, 65, 85, 0.95);
}

.is-dark .cart-modern-item.is-checked {
  background: rgba(37, 99, 235, 0.14);
  border-color: rgba(96, 165, 250, 0.62);
}

.cart-check-area {
  display: flex;
  align-items: center;
  justify-content: center;
}

.cart-avatar-modern {
  width: 64px;
  height: 64px;
  border-radius: 11px;
  overflow: hidden;
  background: #eef6ff;
  border: 1px solid #d9e8f8;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cart-avatar-modern img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cart-item-main {
  min-width: 0;
}

.cart-item-main h4 {
  margin: 0;
  font-family: var(--font);
  font-size: 15px;
  line-height: 1.3;
  font-weight: 600;
  letter-spacing: 0;
  color: #172554;
}

.is-dark .cart-item-main h4 {
  color: var(--dark-dark-text);
}

.service-pill {
  flex-shrink: 0;
  min-width: 108px;
  min-height: 34px;
  padding: 8px 13px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 11px;
  line-height: 1;
  font-weight: 500;
  text-transform: uppercase;
  color: #075985;
  background: #e0f2fe;
  border: 1px solid #bae6fd;

  .iconify {
    font-size: 15px;
  }
}

.service-pill.repair {
  color: #92400e;
  background: #fef3c7;
  border-color: #fde68a;
}

.service-pill.kalibrasi {
  color: #075985;
  background: #e0f2fe;
  border-color: #bae6fd;
}

.cart-item-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 16px;
  margin-top: 7px;
}

.cart-item-meta span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: var(--light-text);
  font-size: 12px;
  font-weight: 400;
}

.cart-modern-footer {
  position: sticky;
  bottom: 100px;
  z-index: 2;
  margin-top: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 16px;
  border-radius: 13px;
  background: rgba(248, 251, 255, 0.96);
  border: 1px solid #dce6f2;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
  backdrop-filter: blur(16px);
}

.is-dark .cart-modern-footer {
  background: rgba(15, 23, 42, 0.76);
  border-color: rgba(51, 65, 85, 0.95);
}

.cart-footer-summary {
  display: flex;
  align-items: center;
  gap: 11px;
}

.cart-footer-icon {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  display: grid !important;
  place-items: center;
  flex-shrink: 0;
  color: #1477e9 !important;
  background: #eef6ff;
  border: 1px solid #b9d8fc;
  font-size: 19px !important;
}

.cart-footer-summary>div>span {
  display: block;
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--light-text);
}

.cart-footer-summary strong {
  display: block;
  margin-top: 2px;
  font-size: 18px;
  color: #172554;
  font-weight: 600;
}

.is-dark .cart-footer-summary strong {
  color: var(--dark-dark-text);
}

/* ======================================================= */
/* ================== CHECKOUT BUTTON STATE ============== */
/* ======================================================= */

.checkout-cta-button.is-empty {
  min-width: 165px;
  min-height: 42px;
  border-radius: 10px !important;
  background: #e2e8f0 !important;
  border-color: #e2e8f0 !important;
  color: #94a3b8 !important;
  box-shadow: none !important;
  font-family: var(--font) !important;
  font-weight: 500 !important;
  letter-spacing: 0;
}

.checkout-cta-button.is-ready {
  min-width: 165px;
  min-height: 42px;
  border-radius: 10px !important;
  background: linear-gradient(135deg, #1756db, #08b8dc) !important;
  border-color: transparent !important;
  color: #ffffff !important;
  box-shadow: 0 7px 18px rgba(14, 116, 220, 0.2) !important;
  font-family: var(--font) !important;
  font-weight: 500 !important;
  letter-spacing: 0;
}

.checkout-next-button.is-disabled-soft {
  opacity: 0.72;
  filter: grayscale(0.25);
}

/* ======================================================= */
/* ================== HISTORY DESKTOP HEADER ============= */
/* ======================================================= */

.history-hero-desktop {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  min-height: 126px;
  margin-bottom: 16px;
  padding: 22px 26px;
  border-radius: 18px;
  background:
    radial-gradient(circle at 68% 120%, rgba(255, 255, 255, 0.15), transparent 38%),
    linear-gradient(118deg, #174ed6 0%, #087fee 50%, #08c7d5 100%);
  color: #fff;
  box-shadow: 0 10px 26px rgba(20, 87, 205, 0.16);
  font-family: var(--font);

  &::before,
  &::after {
    content: '';
    position: absolute;
    width: 280px;
    height: 280px;
    right: 22%;
    bottom: -220px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 50%;
    pointer-events: none;
  }

  &::after {
    width: 360px;
    height: 360px;
    right: 17%;
    bottom: -275px;
  }
}

.history-hero-left {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 18px;
  min-width: 0;
}

.history-hero-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  color: #fff;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.28);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
  font-size: 30px;
  flex-shrink: 0;
}

.history-hero-icon .iconify {
  color: #fff !important;
}

.history-hero-left span {
  display: inline-block;
  font-size: 11px;
  letter-spacing: .08em;
  text-transform: uppercase;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.82);
  margin-bottom: 7px;
}

.history-hero-left h3 {
  margin: 0;
  font-family: var(--font);
  font-size: clamp(1.55rem, 2.2vw, 1.85rem);
  line-height: 1.2;
  font-weight: 600;
  letter-spacing: -0.015em;
  color: #fff;
}

.history-hero-left p {
  margin: 5px 0 0;
  color: rgba(255, 255, 255, 0.88);
  font-size: 13px;
  font-weight: 400;
}

.history-hero-stats {
  position: relative;
  z-index: 1;
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}

.history-mini-stat {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  min-width: 132px;
  min-height: 78px;
  padding: 13px 15px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.24);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.16);
}

.history-mini-stat:first-child {
  min-width: 168px;
}

.history-stat-icon {
  width: 30px;
  height: 30px;
  border-radius: 9px;
  display: grid !important;
  place-items: center;
  flex-shrink: 0;
  color: #fff !important;
  background: rgba(255, 255, 255, 0.14);
  font-size: 16px !important;
}

.history-stat-icon .iconify {
  color: #fff !important;
}

.history-stat-copy {
  min-width: 0;
}

.history-stat-copy>span {
  display: block;
  margin: 0;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: rgba(255, 255, 255, 0.82);
  font-weight: 500;
  white-space: nowrap;
}

.history-stat-copy strong {
  display: block;
  margin-top: 5px;
  font-size: 24px;
  line-height: 1;
  font-weight: 600;
  color: #fff;
}

/* ======================================================= */
/* ================== CHECKOUT SINGLE MODAL ============== */
/* ======================================================= */

.checkout-single-modal {
  padding: 4px 8px 0;
}

.checkout-single-content {
  padding-bottom: 4px;
}

.checkout-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
  padding: 12px 14px;
  border-radius: 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.is-dark .checkout-section-head {
  background: rgba(15, 23, 42, 0.56);
  border-color: rgba(51, 65, 85, 0.9);
}

.checkout-section-head span {
  display: block;
  font-size: 10px;
  font-weight: 850;
  letter-spacing: .09em;
  text-transform: uppercase;
  color: var(--light-text);
  margin-bottom: 3px;
}

.checkout-section-head h4 {
  margin: 0;
  font-size: 15px;
  font-weight: 780;
  color: var(--dark-text);
}

.is-dark .checkout-section-head h4 {
  color: var(--dark-dark-text);
}

.checkout-section-head>.iconify {
  width: 38px;
  height: 38px;
  padding: 8px;
  border-radius: 13px;
  background: #eff6ff;
  color: #2563eb;
  flex-shrink: 0;
}

.checkout-fixed-actions {
  position: sticky;
  bottom: 0;
  z-index: 5;
  margin-top: 14px;
  padding: 14px 0 0;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0), #fff 28%);
}

.is-dark .checkout-fixed-actions {
  background: linear-gradient(180deg, rgba(30, 41, 59, 0), rgba(30, 41, 59, 1) 28%);
}

.checkout-selected-tools {
  margin-bottom: 16px;
  border-radius: 18px;
  padding: 12px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
}

.is-dark .checkout-selected-tools {
  background: rgba(30, 41, 59, 0.72);
  border-color: rgba(51, 65, 85, 0.9);
}

.checkout-tools-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}

.checkout-tools-title span {
  color: var(--light-text);
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .08em;
}

.checkout-tools-title strong {
  color: var(--dark-text);
  font-size: 13px;
}

.is-dark .checkout-tools-title strong {
  color: var(--dark-dark-text);
}

.checkout-tools-scroll {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(245px, 1fr));
  gap: 10px;
  max-height: 170px;
  overflow-y: auto;
  padding-right: 4px;
}

.checkout-tool-card {
  position: relative;
  display: grid;
  grid-template-columns: 58px minmax(0, 1fr);
  gap: 10px;
  align-items: center;
  padding: 10px;
  border-radius: 15px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.is-dark .checkout-tool-card {
  background: rgba(15, 23, 42, 0.54);
  border-color: rgba(51, 65, 85, 0.9);
}

.checkout-tool-card img {
  width: 58px !important;
  height: 58px !important;
  border-radius: 14px;
  object-fit: cover;
  background: #e2e8f0;
}

.checkout-tool-card h4 {
  margin: 0;
  padding-right: 70px;
  font-size: 13px;
  font-weight: 780;
  color: var(--dark-text);
  line-height: 1.2;
}

.is-dark .checkout-tool-card h4 {
  color: var(--dark-dark-text);
}

.checkout-tool-card p {
  margin: 3px 0 0;
  font-size: 11px;
  color: var(--light-text);
  line-height: 1.3;
}

.checkout-tool-card span {
  display: block;
  margin-top: 2px;
  font-size: 11px;
  color: var(--light-text);
  line-height: 1.3;
}

.checkout-tool-card em {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 4px 7px;
  border-radius: 999px;
  font-size: 9px;
  line-height: 1;
  font-style: normal;
  font-weight: 850;
  text-transform: uppercase;
  background: #e0f2fe;
  color: #075985;
  border: 1px solid #bae6fd;
}

.checkout-tool-card em.repair {
  background: #fef3c7;
  color: #92400e;
  border-color: #fde68a;
}

.checkout-selection-group {
  gap: 12px;
}

.checkout-selection-group .selection-card {
  min-height: 78px;
  border: 1px solid #e2e8f0;
  border-bottom: 1px solid #e2e8f0;
  display: grid;
  place-items: center;
  gap: 7px;
  background: #fff;
}

.checkout-selection-group .selection-card .iconify {
  font-size: 22px;
  color: #2563eb;
}

.checkout-selection-group .selection-card.is-selected {
  outline: none;
  border-color: #60a5fa;
  background: #eff6ff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
}

.is-dark .checkout-selection-group .selection-card {
  background: rgba(15, 23, 42, 0.56);
  border-color: rgba(51, 65, 85, 0.9);
}

.upload-step-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.upload-step-card {
  padding: 14px;
  border-radius: 18px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.is-dark .upload-step-card {
  background: rgba(15, 23, 42, 0.56);
  border-color: rgba(51, 65, 85, 0.9);
}

.upload-step-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.upload-step-card-head span {
  display: block;
  font-size: 10px;
  font-weight: 850;
  letter-spacing: .09em;
  text-transform: uppercase;
  color: var(--light-text);
  margin-bottom: 3px;
}

.upload-step-card-head h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 780;
  color: var(--dark-text);
}

.is-dark .upload-step-card-head h4 {
  color: var(--dark-dark-text);
}

.upload-step-card-head>.iconify {
  width: 38px;
  height: 38px;
  padding: 8px;
  border-radius: 13px;
  background: #eff6ff;
  color: #2563eb;
}

.checkout-file-upload :deep(.p-fileupload-buttonbar),
.file-upload-wrapper :deep(.p-fileupload-buttonbar) {
  border-radius: 12px 12px 0 0;
}

.checkout-file-upload :deep(.p-fileupload-content),
.file-upload-wrapper :deep(.p-fileupload-content) {
  min-height: 74px;
  border-radius: 0 0 12px 12px;
}

/* ======================================================= */
/* ================== HISTORY SPLIT LAYOUT =============== */
/* ======================================================= */

.history-content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 360px;
  gap: 18px;
  align-items: start;
}

.history-left-panel,
.history-right-panel {
  min-width: 0;
}

.history-order-list-scroll {
  max-height: 1000px;
  overflow: auto;
  padding-right: 4px;
}

.history-pagination {
  margin-top: 14px;
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 5px 16px rgba(15, 23, 42, 0.05);
}

.history-pagination-summary {
  color: var(--light-text);
  font-size: 12px;
  font-weight: 400;
}

.history-pagination-limit {
  margin: 0 !important;
}

.history-pagination-limit .select select {
  min-width: 142px;
  font-family: var(--font);
  font-size: 12px;
  font-weight: 400;
}

.is-dark .history-pagination {
  background: rgba(15, 23, 42, 0.78);
  border-color: rgba(51, 65, 85, 0.95);
}

.history-order-card {
  border-radius: 18px;
  overflow: hidden;
}

.history-right-panel {
  position: sticky;
  top: 88px;
  display: grid;
  gap: 14px;
}

.history-summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.history-summary-card,
.history-chart-card {
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(226, 232, 240, 0.95);
  box-shadow: 0 16px 36px rgba(15, 23, 42, 0.07);
}

.history-summary-card {
  padding: 15px;
}

.history-summary-card.is-main {
  padding: 18px;
  background:
    radial-gradient(circle at 18% 16%, rgba(14, 165, 233, 0.16), transparent 34%),
    linear-gradient(135deg, #ffffff, #f8fafc);
}

.history-summary-card span {
  display: block;
  color: var(--light-text);
  font-size: 10px;
  font-weight: 850;
  letter-spacing: .09em;
  text-transform: uppercase;
  margin-bottom: 5px;
}

.history-summary-card strong {
  display: block;
  color: var(--dark-text);
  font-size: 26px;
  line-height: 1;
  font-weight: 800;
}

.history-summary-card small {
  display: block;
  margin-top: 7px;
  color: var(--light-text);
  font-size: 12px;
  line-height: 1.35;
}

.history-chart-card {
  padding: 14px;
}

.history-chart-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.history-chart-head span {
  display: block;
  color: var(--light-text);
  font-size: 10px;
  font-weight: 850;
  letter-spacing: .09em;
  text-transform: uppercase;
  margin-bottom: 3px;
}

.history-chart-head h4 {
  margin: 0;
  color: var(--dark-text);
  font-size: 15px;
  font-weight: 780;
}

.history-chart-head strong {
  min-width: 54px;
  height: 36px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  background: #dcfce7;
  color: #166534;
  font-weight: 850;
}

.is-dark .history-summary-card,
.is-dark .history-chart-card {
  background: rgba(30, 41, 59, 0.88);
  border-color: rgba(51, 65, 85, 0.95);
}

.is-dark .history-summary-card.is-main {
  background:
    radial-gradient(circle at 18% 16%, rgba(14, 165, 233, 0.16), transparent 34%),
    linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.92));
}

.is-dark .history-summary-card strong,
.is-dark .history-chart-head h4 {
  color: var(--dark-dark-text);
}

@media only screen and (max-width: 767px) {
  .food-delivery-dashboard {
    flex-direction: column;
    padding-bottom: 105px;
  }

  .food-delivery-dashboard .left,
  .food-delivery-dashboard .right {
    width: 100%;
    padding: 0;
  }

  .food-delivery-dashboard .left .left-header {
    display: none !important;
  }

  .food-delivery-dashboard .left .left-body .restaurants .restaurants-toolbar {
    margin-top: 0 !important;
    margin-bottom: 14px !important;
  }

  .food-delivery-dashboard .left .left-body .restaurants .restaurants-toolbar .left {
    width: 100%;
    flex-wrap: wrap;
    gap: 8px;
  }

  .food-delivery-dashboard .left .left-body .restaurants .restaurants-toolbar .left h3 {
    width: 100%;
    margin-bottom: 4px;
    font-size: 1.2rem !important;
  }

  .food-delivery-dashboard .left .left-body .restaurants .restaurants-toolbar .left .button {
    flex: 1;
    min-height: 42px;
    border-radius: 12px;
  }

  .search-menu-rad {
    height: auto !important;
    min-height: 52px !important;
    border-radius: 14px !important;
    padding-left: 0 !important;
    overflow: hidden;
  }

  .search-menu-rad .search-location-rad {
    padding: 0 14px !important;
  }

  .search-menu-rad .search-button-rad {
    height: 52px !important;
    min-width: 92px !important;
    border-radius: 0 14px 14px 0 !important;
  }

  .restaurants-list {
    padding-top: 18px !important;
  }

  .product-card {
    border-radius: 18px !important;
    overflow: hidden;
  }

  .product-actions {
    gap: 8px;
  }

  .product-actions .button {
    min-height: 42px;
    border-radius: 12px;
  }

  .list-view-item-inner {
    min-height: auto !important;
    flex-direction: column;
    gap: 12px;
  }

  .list-view-item-inner .meta-right {
    width: 100%;
    justify-content: flex-start !important;
  }

  .list-view-item-inner .meta-right .buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .history-hero-desktop {
    display: none !important;
  }

  .history-toolbar-title {
    margin-top: 0 !important;
  }

  .history-content-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .history-left-panel {
    order: 1;
    width: 100%;
  }

  .history-right-panel {
    position: static;
    order: 2;
    width: 100%;
    margin-top: 12px;
  }

  .history-summary-grid {
    grid-template-columns: 1fr 1fr;
  }

  .history-order-list-scroll {
    max-height: none;
    overflow: visible;
  }

  .history-pagination {
    padding: 10px;
  }

  .history-pagination-summary {
    width: 100%;
    margin-bottom: 8px;
  }

  .customer-floating-nav {
    width: min(430px, calc(100% - 28px));
    bottom: calc(16px + env(safe-area-inset-bottom));
    padding: 8px;
    gap: 6px;
  }

  .customer-floating-indicator {
    top: 8px;
    bottom: 8px;
    left: 8px;
    width: calc((100% - 34px) / 4);
  }

  .customer-floating-indicator.is-keranjang {
    transform: translateX(calc(100% + 6px));
  }

  .customer-floating-indicator.is-activity {
    transform: translateX(calc(200% + 12px));
  }

  .customer-floating-indicator.is-laporan {
    transform: translateX(calc(300% + 18px));
  }

  .customer-floating-item {
    min-height: 58px;
    padding: 7px 8px;
  }

  .customer-floating-item span:last-child {
    font-size: 11px;
  }

  .cart-hero-modern {
    flex-direction: column;
    align-items: flex-start;
    min-height: auto;
    padding: 22px;
    border-radius: 22px;
  }

  .cart-hero-left {
    gap: 14px;
  }

  .cart-hero-icon {
    width: 62px;
    height: 62px;
    border-radius: 19px;
    font-size: 29px;
  }

  .cart-hero-modern h3 {
    font-size: 1.7rem;
  }

  .cart-hero-modern p {
    font-size: 13px;
  }

  .cart-hero-stats {
    width: 100%;
    gap: 10px;
  }

  .mini-stat {
    flex: 1;
    min-width: 0;
    min-height: 90px;
    padding: 14px;
    border-radius: 17px;
    gap: 8px;
  }

  .mini-stat-icon {
    width: 28px;
    height: 28px;
    font-size: 15px;
  }

  .mini-stat-copy>span {
    font-size: 9px;
  }

  .mini-stat-copy strong {
    margin-top: 9px;
    font-size: 27px;
  }

  .cart-modern-panel {
    padding: 14px;
    border-radius: 20px;
  }

  .cart-modern-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .cart-modern-toolbar .button {
    width: 100%;
  }

  .cart-modern-item {
    grid-template-columns: 30px 64px minmax(0, 1fr);
    gap: 12px;
    min-height: auto;
    padding: 12px;
  }

  .cart-avatar-modern {
    width: 62px;
    height: 62px;
    border-radius: 15px;
  }

  .cart-item-main h4 {
    font-size: 14px;
  }

  .cart-item-meta {
    display: grid;
    gap: 6px;
    margin-top: 7px;
  }

  .cart-item-meta span {
    font-size: 11px;
  }

  .service-pill {
    grid-column: 3;
    justify-self: start;
    min-width: 0;
    min-height: 34px;
    padding: 8px 12px;
    font-size: 9px;
  }

  .cart-modern-footer {
    bottom: 92px;
    flex-direction: column;
    align-items: stretch;
  }

  .cart-modern-footer .button {
    width: 100%;
  }

  .cart-footer-summary {
    width: 100%;
  }

  .checkout-tools-scroll {
    grid-template-columns: 1fr;
    max-height: 150px;
  }

  .upload-step-grid {
    grid-template-columns: 1fr;
  }

  .checkout-fixed-actions {
    flex-direction: column;
  }

  .checkout-fixed-actions .button {
    width: 100%;
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
  .food-delivery-dashboard {
    flex-direction: column;
  }

  .food-delivery-dashboard .left,
  .food-delivery-dashboard .right {
    width: 100%;
    padding: 0;
  }

  .food-delivery-dashboard .left .restaurants-list .columns {
    display: flex;
  }

  .food-delivery-dashboard .left .restaurants-list .columns .column {
    min-width: 50%;
  }
}

.history-action-buttons {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  gap: 18px;
  flex-wrap: wrap;
}

.history-action-buttons.is-under-progress {
  width: 100%;
  margin-top: 18px;
  padding-top: 14px;
  border-top: 1px dashed rgba(148, 163, 184, 0.45);
}

.history-action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  gap: 7px;
  min-width: 78px;
  text-align: center;
  white-space: nowrap;
}

.history-action-item span {
  display: block;
  font-family: var(--font);
  font-size: 12px;
  font-weight: 800;
  line-height: 1.15;
  color: var(--dark-text);
  white-space: nowrap;
}

.is-dark .history-action-buttons.is-under-progress {
  border-top-color: rgba(71, 85, 105, 0.75);
}

.is-dark .history-action-item span {
  color: var(--dark-dark-text);
}

:deep(.paket-kalibrasi-readonly) {
  width: 100%;
  opacity: 1 !important;
}

:deep(.paket-kalibrasi-readonly .p-autocomplete) {
  width: 100%;
  opacity: 1 !important;
}

:deep(.paket-kalibrasi-readonly .p-inputtext) {
  width: 100%;
  opacity: 1 !important;
  color: #1f2a44 !important;
  background-color: #ffffff !important;
  border: 1px solid #d7dce5 !important;
  font-weight: 600 !important;
  -webkit-text-fill-color: #1f2a44 !important;
  cursor: default !important;
}

:deep(.paket-kalibrasi-readonly input) {
  opacity: 1 !important;
  color: #1f2a44 !important;
  background-color: #ffffff !important;
  font-weight: 600 !important;
  -webkit-text-fill-color: #1f2a44 !important;
  cursor: default !important;
}

/* ======================================================= */
/* =========== LAPORAN CUSTOMER DALAM DASHBOARD ========== */
/* ======================================================= */
.customer-report-page {
  --report-text: #14213d;
  --report-muted: #64748b;
  --report-border: #e2e8f0;
  --report-surface: rgba(255, 255, 255, 0.96);
  display: grid;
  gap: 16px;
  padding-bottom: 118px;
  font-family: var(--font);
}

.report-hero {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  min-height: 126px;
  padding: 22px 26px;
  border-radius: 18px;
  color: #fff;
  background:
    radial-gradient(circle at 68% 120%, rgba(255, 255, 255, 0.15), transparent 38%),
    linear-gradient(118deg, #174ed6 0%, #087fee 50%, #08c7d5 100%);
  box-shadow: 0 10px 26px rgba(20, 87, 205, 0.16);

  &::before,
  &::after {
    content: '';
    position: absolute;
    width: 280px;
    height: 280px;
    right: 22%;
    bottom: -220px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 50%;
    pointer-events: none;
  }

  &::after {
    width: 360px;
    height: 360px;
    right: 17%;
    bottom: -275px;
  }
}

.report-hero-main,
.report-hero-actions {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 12px;
}

.report-hero-main {
  min-width: 0;
  gap: 18px;
}

.report-hero-icon {
  width: 64px;
  height: 64px;
  flex: 0 0 64px;
  display: grid;
  place-items: center;
  color: #fff;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 16px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
  font-size: 30px;
}

.report-hero-icon .iconify {
  color: #fff !important;
}

.report-eyebrow,
.report-panel-heading span,
.report-table-header>div>span,
.report-filter-heading span {
  display: block;
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.report-eyebrow {
  margin-bottom: 7px;
  color: rgba(255, 255, 255, 0.82);
}

.report-hero h1 {
  margin: 0;
  color: #fff !important;
  font-family: var(--font);
  font-size: clamp(1.55rem, 2.2vw, 1.85rem);
  line-height: 1.2;
  font-weight: 600;
  letter-spacing: -0.015em;
}

.report-hero p {
  max-width: 610px;
  margin: 5px 0 0;
  color: rgba(255, 255, 255, 0.88);
  font-size: 13px;
  font-weight: 400;
}

.report-hero-actions {
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.report-hero-actions :deep(.button) {
  min-height: 38px;
  border-radius: 9px;
  font-family: var(--font);
  font-weight: 500;
}

.report-unit-meta {
  min-width: 168px;
  min-height: 78px;
  padding: 12px 14px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.24);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.16);
}

.report-unit-meta span,
.report-unit-meta small {
  display: block;
  color: rgba(255, 255, 255, 0.78);
  font-size: 10px;
  font-weight: 400;
}

.report-unit-meta strong {
  display: block;
  margin: 3px 0;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
}

.report-filter-card,
.report-panel,
.report-kpi-card,
.report-state-card {
  background: var(--report-surface);
  border: 1px solid var(--report-border);
  box-shadow: 0 7px 20px rgba(15, 23, 42, 0.055);
}

.report-filter-card {
  padding: 17px;
  border-radius: 16px;
}

.report-filter-heading,
.report-panel-heading,
.report-table-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.report-filter-heading h2,
.report-panel-heading h2,
.report-table-header h2 {
  margin: 3px 0 0;
  color: var(--report-text);
  font-family: var(--font);
  font-size: 16px;
  font-weight: 600;
}

.report-filter-heading span,
.report-panel-heading span,
.report-table-header>div>span {
  color: #94a3b8;
}

.report-reset {
  border: 0;
  background: transparent;
  color: #2563eb;
  font-family: var(--font);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
}

.report-filter-grid {
  display: grid;
  grid-template-columns:
    minmax(145px, 0.8fr)
    minmax(145px, 0.8fr)
    minmax(165px, 0.9fr)
    minmax(240px, 1.6fr)
    auto;
  gap: 12px;
  align-items: end;
  margin-top: 14px;
}

.report-filter-grid label>span {
  display: block;
  margin-bottom: 6px;
  color: var(--report-muted);
  font-size: 11px;
  font-weight: 500;
}

.report-filter-grid input,
.report-filter-grid select {
  width: 100%;
  min-height: 40px;
  padding: 0 11px;
  color: var(--report-text);
  background: #f8fafc;
  border: 1px solid var(--report-border);
  border-radius: 9px;
  outline: none;
  font-family: var(--font);
  font-weight: 400;
}

.report-filter-grid input:focus,
.report-filter-grid select:focus {
  border-color: #60a5fa;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09);
}

.report-filter-grid :deep(.button) {
  min-height: 40px;
  border-radius: 9px;
  font-family: var(--font);
  font-weight: 500;
}

.report-search-control {
  position: relative;
}

.report-search-control .iconify {
  position: absolute;
  left: 12px;
  top: 50%;
  color: #94a3b8;
  transform: translateY(-50%);
}

.report-search-control input {
  padding-left: 38px;
}

.report-state-card {
  min-height: 280px;
  padding: 32px;
  display: grid;
  place-items: center;
  align-content: center;
  gap: 9px;
  text-align: center;
  border-radius: 16px;
}

.report-state-card h3,
.report-state-card p {
  margin: 0;
}

.report-state-card h3 {
  color: var(--report-text);
  font-family: var(--font);
  font-weight: 600;
}

.report-state-card p {
  color: var(--report-muted);
}

.report-state-card.is-error>.iconify {
  color: #ef4444;
  font-size: 38px;
}

.report-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 999px;
  animation: report-spin 0.8s linear infinite;
}

@keyframes report-spin {
  to {
    transform: rotate(360deg);
  }
}

.report-kpi-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
}

.report-kpi-card {
  min-width: 0;
  padding: 14px;
  border-radius: 14px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.report-kpi-icon {
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
  display: grid;
  place-items: center;
  border-radius: 11px;
  color: var(--accent);
  background: color-mix(in srgb, var(--accent) 13%, transparent);
  font-size: 18px;
}

.report-kpi-card span,
.report-kpi-card small {
  display: block;
  color: var(--report-muted);
}

.report-kpi-card span {
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.report-kpi-card strong {
  display: block;
  margin: 4px 0;
  color: var(--report-text);
  font-size: 22px;
  line-height: 1;
  font-weight: 600;
}

.report-kpi-card small {
  font-size: 10.5px;
  line-height: 1.35;
}

.report-chart-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.report-panel {
  min-width: 0;
  padding: 16px;
  border-radius: 16px;
}

.report-panel.is-wide {
  grid-column: span 2;
}

.report-legend-note,
.report-progress-value {
  padding: 6px 9px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 500;
}

.report-table-panel {
  padding: 0;
  overflow: hidden;
}

.report-table-header {
  padding: 17px;
  border-bottom: 1px solid var(--report-border);
}

.report-table-header p {
  margin: 5px 0 0;
  color: var(--report-muted);
  font-size: 12px;
}

.report-table-count {
  flex: 0 0 auto;
  padding: 6px 10px;
  border-radius: 999px;
  color: #1d4ed8;
  background: #eff6ff;
  font-size: 11px;
  font-weight: 500;
}

.customer-report-table :deep(.p-datatable-wrapper) {
  border-radius: 0;
}

.customer-report-table :deep(.p-datatable-thead>tr>th) {
  padding: 12px 11px;
  color: #334155;
  background: #f8fafc;
  border-color: #e2e8f0;
  font-family: var(--font);
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.customer-report-table :deep(.p-datatable-tbody>tr>td) {
  padding: 10px 11px;
  color: #475569;
  border-color: #eef2f7;
  font-family: var(--font);
  font-size: 11.5px;
  font-weight: 400;
}

.customer-report-table :deep(.p-paginator) {
  border: 0;
  border-top: 1px solid #e2e8f0;
  padding: 11px;
  font-family: var(--font);
}

.report-table-empty {
  padding: 32px;
  text-align: center;
  color: #94a3b8;
}

.report-badge {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 4px 9px;
  border-radius: 999px;
  color: #475569;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  font-size: 10px;
  font-weight: 500;
  white-space: nowrap;
}

.report-badge.is-info {
  color: #0369a1;
  background: #e0f2fe;
  border-color: #bae6fd;
}

.report-badge.is-warning {
  color: #92400e;
  background: #fef3c7;
  border-color: #fde68a;
}

.report-badge.is-success {
  color: #166534;
  background: #dcfce7;
  border-color: #bbf7d0;
}

.report-badge.is-success-soft {
  color: #047857;
  background: #ecfdf5;
  border-color: #a7f3d0;
}

.report-badge.is-muted {
  color: #64748b;
  background: #f1f5f9;
  border-color: #e2e8f0;
}

.is-dark .customer-report-page {
  --report-text: #f8fafc;
  --report-muted: #94a3b8;
  --report-border: rgba(71, 85, 105, 0.7);
  --report-surface: rgba(15, 23, 42, 0.92);
}

.is-dark .report-filter-grid input,
.is-dark .report-filter-grid select,
.is-dark .customer-report-table :deep(.p-datatable-thead>tr>th) {
  color: #e2e8f0;
  background: #1e293b;
  border-color: #334155;
}

.is-dark .customer-report-table :deep(.p-datatable-tbody>tr>td) {
  color: #cbd5e1;
  background: #0f172a;
  border-color: #1e293b;
}

@media (max-width: 1280px) {
  .report-kpi-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .report-filter-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .report-filter-grid .is-search {
    grid-column: span 2;
  }
}

@media (max-width: 900px) {
  .report-hero,
  .report-table-header {
    flex-direction: column;
    align-items: stretch;
  }

  .report-hero-actions {
    justify-content: flex-start;
  }

  .report-chart-grid {
    grid-template-columns: 1fr;
  }

  .report-panel.is-wide {
    grid-column: span 1;
  }
}

@media (max-width: 680px) {
  .customer-report-page {
    gap: 13px;
  }

  .report-hero {
    padding: 16px;
    border-radius: 16px;
  }

  .report-hero-main {
    align-items: flex-start;
    gap: 12px;
  }

  .report-hero-icon {
    width: 52px;
    height: 52px;
    flex-basis: 52px;
    border-radius: 13px;
    font-size: 24px;
  }

  .report-hero h1 {
    font-size: 1.45rem;
  }

  .report-hero-actions,
  .report-hero-actions :deep(.button),
  .report-unit-meta {
    width: 100%;
  }

  .report-kpi-grid,
  .report-filter-grid {
    grid-template-columns: 1fr;
  }

  .report-filter-grid .is-search {
    grid-column: span 1;
  }

  .report-filter-heading {
    align-items: center;
  }
}
</style>
