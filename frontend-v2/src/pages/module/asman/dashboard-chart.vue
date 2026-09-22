<template>
  <div>
    <div class="personal-dashboard personal-dashboard-v1">
      <div class="dashboard-body">
        <div class="columns is-multiline">
          <div class="column is-12">
            <div class="columns is-multiline">
              <div class="column is-3">
                <div class="dashboard-card" @click="handleJumlahUnit">
                  <VBlock :title="String(jumlahMitra)" subtitle="JUMLAH UNIT" center>
                    <template #icon>
                      <VIconBox color="success" rounded>
                        <i class="lnil lnil-checkmark" aria-hidden="true"></i>
                      </VIconBox>
                    </template>
                  </VBlock>
                </div>
              </div>
              <div class="column is-3">
                <div class="dashboard-card" @click="handlePaguUnitProgres">
                  <VBlock :title="String(jumlahProgresAlat)" subtitle="UNIT SEDANG DAFTAR ALAT" center>
                    <template #icon>
                      <VIconBox color="info" rounded>
                        <i class="lnil lnil-wheelbarrow" aria-hidden="true"></i>
                      </VIconBox>
                    </template>
                  </VBlock>
                </div>
              </div>
              <div class="column is-3">
                <div class="dashboard-card" @click="handleRegistrasiKalibrasi">
                  <VBlock :title="String(jumlahAlatKalibrasi)" subtitle="UNIT DAFTAR KALIBRASI" center>
                    <template #icon>
                      <VIconBox color="info" rounded>
                        <i class="lnil lnil-cogs" aria-hidden="true"></i>
                      </VIconBox>
                    </template>
                  </VBlock>
                </div>
              </div>
              <div class="column is-3">
                <div class="dashboard-card" @click="handleRegistrasiReapir">
                  <VBlock :title="String(jumlahAlatRepair)" subtitle="UNIT DAFTAR REPAIR" center>
                    <template #icon>
                      <VIconBox color="warning" rounded>
                        <i class="lnil lnil-wrench" aria-hidden="true"></i>
                      </VIconBox>
                    </template>
                  </VBlock>
                </div>
              </div>
            </div>
          </div>

          <div class="columns is-multiline" style="display: contents;">
            <div class="column is-12">
              <VCard>
                <div id="map" style="height: 500px; z-index: 6;"></div>
              </VCard>
            </div>

            <div class="column is-12">
              <div class="columns is-multiline is-vcentered">
                <div class="column is-3">
                  <VField class="is-autocomplete-select" label="Cari Unit">
                    <VControl icon="feather:search">
                      <AutoComplete v-model="item.unitfk" :suggestions="d_unit" @complete="fetchUnit($event)"
                        :optionLabel="'label'" :dropdown="true" :minLength="3" :appendTo="'body'"
                        :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="Ketik Nama Unit" />
                    </VControl>
                  </VField>
                </div>

                <div class="column is-3">
                  <VField label="Pilih Tahun">
                    <MultiSelect v-model="item.filterYears" :options="yearOptions" optionLabel="label"
                      optionValue="value" placeholder="Pilih Tahun" :maxSelectedLabels="3" class="w-full"
                      style="width: 100%;" display="chip" />
                  </VField>
                </div>

                <div class="column is-2">
                  <VField label="Wilayah ULAB">
                    <Dropdown v-model="item.filterUlab" :options="ulabOptions" optionLabel="label" optionValue="value"
                      placeholder="Semua Wilayah" class="w-full" style="width: 100%;" showClear />
                  </VField>
                </div>

                <div class="column is-3">
                  <VField label="Ruang Lingkup">
                    <Dropdown v-model="item.filterLingkup" :options="scopeOptions" optionLabel="label" optionValue="value"
                      placeholder="Semua Ruang Lingkup" class="w-full" style="width: 100%;" showClear />
                  </VField>
                </div>

                <div class="column is-1 has-text-centered">
                  <VIconButton type="button" color="success" class="is-rounded mt-4" rounded raised icon="fas fa-search"
                    @click="fetchData()" :loading="isLoadingCharts">
                  </VIconButton>
                </div>
              </div>

              <div v-if="selectedUnitId" class="notification is-success is-light unit-filter-notice">
                <span>
                  Filter unit aktif: <strong>{{ selectedMitraId }}</strong>. Semua ringkasan dan grafik di bawah
                  sudah difilter untuk unit ini.
                </span>
                <VButton color="success" outlined @click="clearUnitFilter">Tampilkan Semua Unit</VButton>
              </div>
            </div>

            <div class="column is-12" v-if="!isLoadingCharts">
              <div class="columns is-multiline">

                <div class="column is-12-mobile is-6-tablet region-divider"
                  v-if="!item.filterUlab || item.filterUlab === 'JKT'">
                  <h3 class="title is-4 has-text-centered has-text-primary mb-5"
                    style="border-bottom: 2px solid #ddd; padding-bottom: 10px;">ULAB JAKARTA</h3>

                  <div class="mb-5">
                    <VCard>
                      <div class="dashboard-card">
                        <ApexChart :options="UnitOptionsLingkup" :series="UnitOptionsLingkup.series" type="donut"
                          height="340" @dataPointSelection="handleChartPointSelection" />
                        <div class="scope-detail-grid">
                          <button v-for="scope in scopeDetailItemsJKT" :key="scope.label" type="button"
                            class="scope-detail-button" @click="openScopeDetail('JKT', scope)">
                            <span class="scope-detail-color" :style="{ backgroundColor: scope.color }"></span>
                            <span class="scope-detail-label">{{ scope.label }}</span>
                            <strong>{{ scope.value }} Alat</strong>
                          </button>
                        </div>
                      </div>
                    </VCard>
                  </div>

                  <div class="mb-5">
                    <VCard>
                      <div class="dashboard-card status-donut-card">
                        <ApexChart :options="optionsStatusPenyelesaianJKT"
                          :series="optionsStatusPenyelesaianJKT.series" type="donut" height="340"
                          @dataPointSelection="handleChartPointSelection" />
                      </div>
                    </VCard>
                  </div>

                  <div class="mb-5">
                    <VCard>
                      <div class="dashboard-card status-donut-card">
                        <ApexChart :options="optionsStatusPengambilanJKT"
                          :series="optionsStatusPengambilanJKT.series" type="donut" height="340"
                          @dataPointSelection="handleChartPointSelection" />
                      </div>
                    </VCard>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsKalibrasiJakarta)"
                        :series="optionsKalibrasiJakarta.series" :options="optionsKalibrasiJakarta"
                        @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsRepairJakarta)" :series="optionsRepairJakarta.series"
                        :options="optionsRepairJakarta" @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsLingkupJKT)" :series="optionsLingkupJKT.series"
                        :options="optionsLingkupJKT" @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsPelaksanaJKT)" :series="optionsPelaksanaJKT.series"
                        :options="optionsPelaksanaJKT" @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsPelaksanaRapairJKT)"
                        :series="optionsPelaksanaRapairJKT.series" :options="optionsPelaksanaRapairJKT"
                        @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>
                </div>

                <div class="column is-12-mobile is-6-tablet"
                  v-if="!item.filterUlab || item.filterUlab === 'GRK'">
                  <h3 class="title is-4 has-text-centered has-text-info mb-5"
                    style="border-bottom: 2px solid #ddd; padding-bottom: 10px;">ULAB GRESIK</h3>

                  <div class="mb-5">
                    <VCard>
                      <div class="dashboard-card">
                        <ApexChart :options="UnitOptionsLingkupGresik" :series="UnitOptionsLingkupGresik.series"
                          type="donut" height="340" @dataPointSelection="handleChartPointSelection" />
                        <div class="scope-detail-grid">
                          <button v-for="scope in scopeDetailItemsGRK" :key="scope.label" type="button"
                            class="scope-detail-button" @click="openScopeDetail('GRK', scope)">
                            <span class="scope-detail-color" :style="{ backgroundColor: scope.color }"></span>
                            <span class="scope-detail-label">{{ scope.label }}</span>
                            <strong>{{ scope.value }} Alat</strong>
                          </button>
                        </div>
                      </div>
                    </VCard>
                  </div>

                  <div class="mb-5">
                    <VCard>
                      <div class="dashboard-card status-donut-card">
                        <ApexChart :options="optionsStatusPenyelesaianGRK"
                          :series="optionsStatusPenyelesaianGRK.series" type="donut" height="340"
                          @dataPointSelection="handleChartPointSelection" />
                      </div>
                    </VCard>
                  </div>

                  <div class="mb-5">
                    <VCard>
                      <div class="dashboard-card status-donut-card">
                        <ApexChart :options="optionsStatusPengambilanGRK"
                          :series="optionsStatusPengambilanGRK.series" type="donut" height="340"
                          @dataPointSelection="handleChartPointSelection" />
                      </div>
                    </VCard>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsKalibrasiGresik)"
                        :series="optionsKalibrasiGresik.series" :options="optionsKalibrasiGresik"
                        @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsRepairGresik)" :series="optionsRepairGresik.series"
                        :options="optionsRepairGresik" @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsLingkupGRK)" :series="optionsLingkupGRK.series"
                        :options="optionsLingkupGRK" @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsPelaksanaGRK)" :series="optionsPelaksanaGRK.series"
                        :options="optionsPelaksanaGRK" @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>

                  <div class="dashboard-card mb-5">
                    <div class="chart-scroll-container">
                      <ApexChart :height="getBaseChartHeight(optionsPelaksanaRapairGRK)"
                        :series="optionsPelaksanaRapairGRK.series" :options="optionsPelaksanaRapairGRK"
                        @dataPointSelection="handleChartPointSelection" />
                    </div>
                  </div>
                </div>

                <div class="column is-12 mt-6">
                  <h3 class="title is-4 has-text-centered mb-5"
                    style="border-bottom: 2px solid #ddd; padding-bottom: 10px;">
                    ANALISIS DURASI PROSES KALIBRASI & REPAIR</h3>
                  <p class="has-text-centered is-size-7 calculation-note mb-4">
                    Durasi hanya menghitung jam kerja 07:30-16:00 serta tidak menghitung Sabtu, Minggu, hari libur,
                    dan cuti yang terdaftar.
                  </p>
                </div>

                <div class="column is-6 region-divider" v-if="!item.filterUlab || item.filterUlab === 'JKT'">
                  <div class="dashboard-card" style="min-height: 450px;">
                    <div class="card-header">
                      <h4 class="card-title is-size-5">Analisis Waktu - ULAB JAKARTA</h4>
                    </div>
                    <ApexChart :options="optionsAnalisisJKT" :series="optionsAnalisisJKT.series" type="bar"
                      height="380" @dataPointSelection="handleChartPointSelection" />
                  </div>
                </div>

                <div class="column is-6" v-if="!item.filterUlab || item.filterUlab === 'GRK'">
                  <div class="dashboard-card" style="min-height: 450px;">
                    <div class="card-header">
                      <h4 class="card-title is-size-5">Analisis Waktu - ULAB GRESIK</h4>
                    </div>
                    <ApexChart :options="optionsAnalisisGRK" :series="optionsAnalisisGRK.series" type="bar"
                      height="380" @dataPointSelection="handleChartPointSelection" />
                  </div>
                </div>
                <div class="column is-12 mt-4">
                  <div class="message is-info is-light">
                    <div class="message-header">
                      <p>📝 Simulasi & Contoh Perhitungan Rata-rata</p>
                    </div>
                    <div class="message-body">
                      <div class="content">
                        <div class="columns">
                          <div class="column is-6">
                            <p><strong>Rumus:</strong></p>
                            <blockquote style="font-size: 16px; background: #fff; border-left: 5px solid #2196f3;">
                              Rata-rata = (Total Jam Pengerjaan Semua Alat) ÷ (Jumlah Alat)
                            </blockquote>
                          </div>
                          <div class="column is-6">
                            <p><strong>Contoh Kasus Nyata:</strong></p>
                            <ul>
                              <li>Alat A: Dikerjakan <strong>10 Jam</strong></li>
                              <li>Alat B: Dikerjakan <strong>48 Jam</strong></li>
                              <li>Alat C: Dikerjakan <strong>113.6 Jam</strong></li>
                            </ul>
                            <p><strong>Hasil:</strong> (171.6) ÷ 3 = <strong style="color: #d32f2f;">57.2 Jam</strong>
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="column is-12 mt-6">
                  <h3 class="title is-4 has-text-centered mb-5"
                    style="border-bottom: 2px solid #ddd; padding-bottom: 10px; color: #d32f2f;">
                    DURASI VERIFIKASI KALIBRASI / REPAIR PELAKSANA</h3>
                </div>

                <div class="column is-12">
                  <div class="dashboard-card">
                    <div class="columns">
                      <div class="column is-8">
                        <ApexChart :options="optionsVerifikasiPelaksana" :series="optionsVerifikasiPelaksana.series"
                          type="bar" :height="Math.max(400, verificationPerformanceDetailItems.length * 38)"
                          @dataPointSelection="handleChartPointSelection" />
                      </div>
                      <div class="column is-4">
                        <div class="message is-info is-light mt-4">
                          <div class="message-header">Indikator Durasi Verifikasi Pelaksana</div>
                          <div class="message-body">
                            <p class="mb-2">
                              Grafik ini menampilkan rata-rata waktu pelaksana melakukan verifikasi setelah alat
                              diterbitkan SPK oleh Asman.
                            </p>
                            <br>
                            <p class="is-size-7"><strong>Durasi dihitung dari:</strong><br>
                              <span class="tag is-light">Alat Terbit SPK / Verifikasi Asman</span> &rarr;
                              <span class="tag is-light">Alat Diverifikasi Pelaksana</span>
                            </p>
                            <p class="is-size-7 mt-3 calculation-note">
                              Hanya jam kerja <strong>07:30-16:00</strong> yang dihitung. Sabtu, Minggu, hari libur,
                              dan cuti yang terdaftar tidak dihitung.
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="column is-12 mt-6">
                  <h3 class="title is-4 has-text-centered mb-5"
                    style="border-bottom: 2px solid #ddd; padding-bottom: 10px; color: #d32f2f;">
                    DURASI PENGERJAAN KALIBRASI / REPAIR PELAKSANA</h3>
                </div>

                <div class="column is-12">
                  <div class="dashboard-card">
                    <div class="columns">
                      <div class="column is-8">
                        <ApexChart :options="optionsPelaksanaLambat" :series="optionsPelaksanaLambat.series" type="bar"
                          :height="Math.max(400, performanceDetailItems.length * 38)"
                          @dataPointSelection="handleChartPointSelection" />
                      </div>
                      <div class="column is-4">
                        <div class="message is-warning is-light mt-4">
                          <div class="message-header">ℹ️ Indikator Kinerja</div>
                          <div class="message-body">
                            <p class="mb-2">Grafik ini menampilkan personil yang membutuhkan waktu <strong>paling
                                lama</strong>
                              dalam menyelesaikan aspek teknis alat.</p>
                            <hr style="background-color: #dbdbdb; height: 1px; margin: 10px 0;">
                            <p class="is-size-7"><strong>Informasi:</strong><br>
                              Pada label ditampilkan <strong>[Jumlah Alat]</strong> yang dikerjakan. <br>
                              <em>Semakin banyak alat, durasi tinggi mungkin wajar. Jika alat sedikit tapi durasi
                                tinggi, perlu
                                evaluasi.</em>
                            </p>
                            <br>
                            <p class="is-size-7"><strong>Durasi dihitung dari:</strong><br>
                              <span class="tag is-light">Verifikasi Pelaksana</span> &rarr; <span
                                class="tag is-light">Isi Lembar Kerja / Laporan Repair</span>
                            </p>
                            <p class="is-size-7 mt-3 calculation-note">
                              Hanya jam kerja <strong>07:30-16:00</strong> yang dihitung. Sabtu, Minggu, hari libur,
                              dan cuti yang terdaftar tidak dihitung.
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
        </div>
      </div>
    </div>

    <Dialog v-model:visible="detailJumlahUnit" maximizable modal header="JUMLAH UNIT" :style="{ width: '100vw' }">
      <DataTable :value="dataJumlahUnit" :rows="10" :rowsPerPageOptions="[5, 10, 15, 50, 100, 1000]"
        class="p-datatable-sm" breakpoint="960px" selectionMode="single" sortMode="multiple"
        v-model:expanded-rows="expandedRows" showGridlines scrollable scrollHeight="500px" tableStyle="min-width: 30rem"
        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        paginator currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
        <template #header>
          <div class="column pt-0 pb-0">
            <VButtons style="justify-content: space-between;">
              <VButton color="primary" @click="exportExcelJumlahUnit()" outlined icon="fas fa-file-excel">
                Export To Excel
              </VButton>
            </VButtons>
          </div>
        </template>
        <Column field="no" header="No"></Column>
        <Column field="namaperusahaan" header="Nama Unit"></Column>
        <Column field="alamatktr" header="Alamat"></Column>
      </DataTable>
      <template #footer>
        <VButton color="danger" icon="pi pi-times" outlined raised @click="detailJumlahUnit = false"> Tutup
        </VButton>
      </template>
    </Dialog>
    <Dialog v-model:visible="detailOpenUnitProgres" maximizable modal header="DETAIL UNIT REGISTRASI"
      :style="{ width: '100vw' }">
      <DataTable :value="dataDetailUnitProgres" :rows="10" :rowsPerPageOptions="[5, 10, 15, 50, 1000]"
        class="p-datatable-sm" breakpoint="960px" selectionMode="single" sortMode="multiple"
        v-model:expanded-rows="expandedRows" showGridlines scrollable scrollHeight="500px" tableStyle="min-width: 30rem"
        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        paginator currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
        <template #header>
          <div class="column pt-0 pb-0">
            <VButtons style="justify-content: space-between;">
              <VButton color="primary" @click="exportExcelDetailUnitProgres()" outlined icon="fas fa-file-excel">
                Export To Excel
              </VButton>
            </VButtons>
          </div>
        </template>
        <Column field="no" header="No"></Column>
        <Column field="namaperusahaan" header="Nama Unit"></Column>
        <Column field="tglregistrasi" header="Tgl Registrasi"></Column>
        <Column field="jenisorder" header="Jenis Order"></Column>
      </DataTable>
      <template #footer>
        <VButton color="danger" icon="pi pi-times" outlined raised @click="detailOpenUnitProgres = false">
          Tutup
        </VButton>
      </template>
    </Dialog>
    <Dialog v-model:visible="detailOpenDaftarRepair" maximizable modal header="DETAIL REGISTRASI REPAIR"
      :style="{ width: '100vw' }">
      <DataTable :value="dataDetailDaftarRepair" :rows="10" :rowsPerPageOptions="[5, 10, 15, 50, 1000]"
        class="p-datatable-sm" breakpoint="960px" selectionMode="single" sortMode="multiple"
        v-model:expanded-rows="expandedRows" showGridlines scrollable scrollHeight="500px" tableStyle="min-width: 30rem"
        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        paginator currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
        <template #header>
          <div class="column pt-0 pb-0">
            <VButtons style="justify-content: space-between;">
              <VButton color="primary" @click="exportExcelDetailRegisRepair()" outlined icon="fas fa-file-excel">
                Export To Excel
              </VButton>
            </VButtons>
          </div>
        </template>
        <Column field="no" header="No"></Column>
        <Column field="namaperusahaan" header="Nama Unit"></Column>
        <Column field="tglregistrasi" header="Tanggal Registrasi"></Column>
      </DataTable>
      <template #footer>
        <VButton color="danger" icon="pi pi-times" outlined raised @click="detailOpenDaftarRepair = false">
          Tutup
        </VButton>
      </template>
    </Dialog>
    <Dialog v-model:visible="detailRegisKalibrasi" maximizable modal header="DETAIL REGISTRASI KALIBRASI"
      :style="{ width: '100vw' }">
      <DataTable :value="dataRegisKalibrasi" :rows="10" :rowsPerPageOptions="[5, 10, 15, 50, 1000]"
        class="p-datatable-sm" breakpoint="960px" selectionMode="single" sortMode="multiple"
        v-model:expanded-rows="expandedRows" showGridlines scrollable scrollHeight="500px" tableStyle="min-width: 30rem"
        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        paginator currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
        <template #header>
          <div class="column pt-0 pb-0">
            <VButtons style="justify-content: space-between;">
              <VButton color="primary" @click="exportDetailKalibrasi()" outlined icon="fas fa-file-excel">
                Export To Excel
              </VButton>
            </VButtons>
          </div>
        </template>
        <Column field="no" header="No"></Column>
        <Column field="namaperusahaan" header="Nama Unit"></Column>
        <Column field="tglregistrasi" header="Tanggal Registrasi"></Column>
      </DataTable>
      <template #footer>
        <VButton color="danger" icon="pi pi-times" outlined raised @click="detailRegisKalibrasi = false">
          Tutup
        </VButton>
      </template>
    </Dialog>

    <Dialog v-model:visible="detailChartVisible" maximizable modal :header="detailChartTitle"
      :style="{ width: '96vw' }">
      <div class="detail-chart-summary">
        <div>
          <strong>Daftar alat sesuai bagian chart yang diklik</strong>
          <p>Data di bawah sudah mengikuti warna, kategori, status, dan filter dashboard yang dipilih.</p>
        </div>
        <span class="detail-chart-count">
          {{ detailChartLoading ? 'Memuat data...' : `${detailChartData.length} alat ditemukan` }}
        </span>
      </div>
      <DataTable :value="detailChartData" :loading="detailChartLoading" :rows="15"
        :rowsPerPageOptions="[10, 15, 25, 50, 100, 500]" class="p-datatable-sm" breakpoint="960px"
        showGridlines scrollable scrollHeight="65vh" tableStyle="min-width: 115rem" paginator
        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        currentPageReportTemplate="Menampilkan {first} sampai {last} dari {totalRecords} alat">
        <template #empty>
          Tidak ada alat yang sesuai dengan bagian chart yang diklik.
        </template>
        <Column field="no" header="No" style="width: 4rem"></Column>
        <Column field="namaalat" header="Nama Alat"></Column>
        <Column field="namaserialnumber" header="Serial Number"></Column>
        <Column field="noorderalat" header="No. Order Alat"></Column>
        <Column field="namamerk" header="Merk"></Column>
        <Column field="namatipe" header="Tipe"></Column>
        <Column field="namaperusahaan" header="Unit"></Column>
        <Column field="jenisorder" header="Jenis Order"></Column>
        <Column field="lingkupkalibrasi" header="Ruang Lingkup"></Column>
        <Column field="pelaksana" header="Pelaksana"></Column>
        <Column field="lokasi" header="ULAB"></Column>
        <Column field="status_penyelesaian" header="Status"></Column>
        <Column field="status_terakhir" header="Posisi / Status Terakhir" style="min-width: 18rem">
          <template #body="{ data }">
            <span :class="[
              'last-status-badge',
              data.status_penyelesaian === 'Selesai' ? 'is-complete' : 'is-pending',
            ]">
              {{ data.status_terakhir || '-' }}
            </span>
          </template>
        </Column>
        <Column field="status_pengambilan" header="Pengambilan" style="min-width: 10rem">
          <template #body="{ data }">
            <span :class="[
              'pickup-detail-badge',
              data.status_pengambilan === 'Sudah Diambil' ? 'is-picked-up' : 'is-waiting',
            ]">
              {{ data.status_pengambilan || '-' }}
            </span>
          </template>
        </Column>
        <Column field="durasi_jam" header="Durasi Jam Kerja"></Column>
      </DataTable>
      <template #footer>
        <VButton color="danger" icon="pi pi-times" outlined raised @click="detailChartVisible = false">
          Tutup
        </VButton>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import ApexChart from 'vue3-apexcharts';
import * as H from '/@src/utils/appHelper'
import { useThemeColors } from '/@src/composable/useThemeColors';
import { reactive, ref, shallowRef, onMounted, nextTick, computed } from 'vue';
import { useApi } from '/@src/composable/useApi';
import { useHead } from '@vueuse/head';
import { useViewWrapper } from '/@src/stores/viewWrapper';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import * as XLSX from "xlsx";
import * as XLSXStyle from 'xlsx-js-style';
import Dialog from 'primevue/dialog';
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { useRoute, useRouter } from 'vue-router'
import MultiSelect from 'primevue/multiselect';
import Dropdown from 'primevue/dropdown';
import AutoComplete from 'primevue/autocomplete';

useHead({
  title: 'Dashboard Integrasi - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const router = useRouter();
const themeColors = useThemeColors();
const modalFilter = ref(false);
const detailJumlahUnit = ref(false);
const detailOpenUnitProgres = ref(false);
const detailOpenDaftarRepair = ref(false);
const detailRegisKalibrasi = ref(false);
const detailOpenGlobalJrs = ref(false);
const detailChartVisible = ref(false);
const detailChartLoading = ref(false);
const detailChartTitle = ref('DETAIL DATA CHART');
const detailChartData = shallowRef<any[]>([]);
const performanceDetailItems = ref<any[]>([]);
const verificationPerformanceDetailItems = ref<any[]>([]);
const isLoadingCharts = ref(false);
const detailCardUnit = ref(false);
const expandedRows = ref();
const selectedMitraId = ref<string | null>(null);
const selectedMitraUnits = shallowRef<any[]>([]);
const d_unit = ref([]);
const noop = () => { };
const dataJumlahUnit = shallowRef([]);
const dataDetailUnitProgres = shallowRef([]);
const dataDetailDaftarRepair = shallowRef([]);
const dataRegisKalibrasi = shallowRef([]);
let jumlahMitra = ref(0);
let jumlahProgresAlat = ref(0);
let jumlahAlatKalibrasi = ref(0);
let jumlahAlatRepair = ref(0);
const totalOrderUnit = ref(0);
const alatProgres = ref(0);
const alatSelesai = ref(0);
let leafletMap: any = null;

// -- Option Definitions --
const lingkupLabels = ['Kelistrikan', 'Tekanan', 'Waktu & Frekuensi', 'Suhu & Kelembaban', 'Vibrasi', 'Dimensi', 'Gaya', 'Repair', 'Vendor'];
const scopeColorMap: Record<string, string> = {
  Kelistrikan: '#ff8a65',
  Tekanan: '#00b894',
  'Waktu & Frekuensi': '#f6c85f',
  'Suhu & Kelembaban': '#5f6caf',
  Vibrasi: '#8e67c7',
  Dimensi: '#0984c6',
  Gaya: '#f39c12',
  Repair: '#e84393',
  Vendor: '#64748b',
};
const scopeColors = lingkupLabels.map(label => scopeColorMap[label]);
const scopeDetailItemsJKT = shallowRef<any[]>([]);
const scopeDetailItemsGRK = shallowRef<any[]>([]);
const analysisStageKeys = ['admin_review', 'asman_verification', 'executor_verification', 'technical_work', 'supervisor_approval', 'asman_approval', 'manager_approval'];

const currentYear = new Date().getFullYear();
const yearOptions = computed(() => {
  const years = [];
  for (let y = currentYear + 1; y >= 2020; y--) {
    years.push({ label: String(y), value: y });
  }
  return years;
});

const ulabOptions = ref([
  { label: 'Jakarta', value: 'JKT' },
  { label: 'Gresik', value: 'GRK' }
]);

const scopeOptions = computed(() => {
  return lingkupLabels.map(l => ({ label: l, value: l }));
});

const item: any = reactive({
  periode: {
    start: new Date(new Date().setDate(new Date().getDate() - 5)),
    end: new Date(),
  },
  filterTgl: new Date(),
  filterYears: [currentYear],
  filterUlab: null,
  filterLingkup: null,
  unitfk: null,
  tarif: 0,
});

const getDatesBetween = (start: any, end: any) => {
  const dates = [];
  let curr = new Date(start);
  while (curr <= end) {
    dates.push(new Date(curr));
    curr.setDate(curr.getDate() + 1);
  }
  return dates;
};
const cat = getDatesBetween(item.periode.start, item.periode.end).map(d => H.formatDate(d, 'YYYY-MM-DD'));

// --- Chart Configurations ---

const getChartPointValue = (config: any) => {
  const configuredSeries = config.w?.config?.series;
  const selectedSeries = configuredSeries?.[config.seriesIndex];
  const rawValue = typeof selectedSeries === 'number'
    ? (configuredSeries?.[config.dataPointIndex] ?? selectedSeries)
    : selectedSeries?.data?.[config.dataPointIndex];

  if (typeof rawValue === 'object' && rawValue !== null) {
    return rawValue.y ?? rawValue.value ?? null;
  }
  return rawValue ?? null;
};

const getDurationPerformanceColors = (values: number[]) => {
  if (values.length === 0) return [];

  const min = Math.min(...values);
  const max = Math.max(...values);

  if (min === max) {
    return values.map(() => '#42a5f5');
  }

  return values.map((value) => {
    const position = (value - min) / (max - min);
    if (position <= 0.25) return '#43a047';
    if (position <= 0.5) return '#42a5f5';
    if (position <= 0.75) return '#ffa726';
    return '#ef5350';
  });
};

const getBaseChartHeight = (chartOptions: any) => {
  const categoryCount = chartOptions?.xaxis?.categories?.length || 0;
  return Math.max(340, categoryCount * 58 + 160);
};

const createBaseChart = (title: string, detailConfig: any, type = 'bar', stacked = false, colors = ['#f44336', '#4caf50', '#2196f3']) => ({
  series: [
    { name: 'BELUM SELESAI', type: 'bar', data: [] as any[] },
    { name: 'SELESAI', type: 'bar', data: [] as any[] },
    { name: 'TOTAL', type: 'bar', data: [] as any[] },
  ],
  chart: {
    id: `${detailConfig.chart}_${detailConfig.chart_ulab || 'ALL'}`,
    type,
    stacked,
    animations: { enabled: true, speed: 450 },
    toolbar: {
      show: true,
      tools: { download: true, customIcons: [{ icon: '<span style="padding:0 6px;font-weight:600;">DETAIL</span>', index: 0, title: 'Lihat Detail', class: 'custom-icon', click: () => openChartDetail(detailConfig) }] }
    },
  },
  colors,
  title: { text: title, align: 'left' },
  subtitle: { text: 'Klik bar untuk melihat daftar alat. Gulir ke bawah jika kategori banyak.', align: 'left' },
  legend: { position: 'top' },
  dataLabels: {
    enabled: true,
    formatter: (val: number) => val > 0 ? new Intl.NumberFormat('id-ID').format(val) : '',
    style: { fontSize: '11px', fontWeight: 700, colors: ['#fff'] },
    dropShadow: { enabled: true, opacity: 0.35 },
  },
  stroke: { width: 1, colors: ['#fff'] },
  grid: { borderColor: '#e9edf2', strokeDashArray: 4 },
  xaxis: {
    categories: [] as any[],
    title: { text: 'Jumlah Alat' },
    labels: { formatter: (val: any) => new Intl.NumberFormat('id-ID').format(Number(val)) },
  },
  yaxis: {
    labels: {
      maxWidth: 230,
      style: { fontSize: '11px', fontWeight: 600 },
    },
  },
  tooltip: {
    shared: false,
    intersect: true,
    y: { formatter: (val: any) => `${val} Alat` }
  },
  states: {
    hover: { filter: { type: 'lighten', value: 0.1 } },
    active: { filter: { type: 'darken', value: 0.15 } },
  },
  plotOptions: {
    bar: {
      horizontal: true,
      barHeight: '76%',
      borderRadius: 4,
      dataLabels: { position: 'center' },
    },
  },
});

const optionsKalibrasiJakarta = ref(createBaseChart('TOTAL KALIBRASI PER UNIT ULAB JAKARTA', { chart: 'kalibrasi_unit', chart_ulab: 'JKT', title: 'Total Kalibrasi per Unit ULAB Jakarta' }));
const optionsKalibrasiGresik = ref(createBaseChart('TOTAL KALIBRASI PER UNIT ULAB GRESIK', { chart: 'kalibrasi_unit', chart_ulab: 'GRK', title: 'Total Kalibrasi per Unit ULAB Gresik' }));
const optionsRepairJakarta = ref(createBaseChart('TOTAL REPAIR PER UNIT ULAB JAKARTA', { chart: 'repair_unit', chart_ulab: 'JKT', title: 'Total Repair per Unit ULAB Jakarta' }));
const optionsRepairGresik = ref(createBaseChart('TOTAL REPAIR PER UNIT ULAB GRESIK', { chart: 'repair_unit', chart_ulab: 'GRK', title: 'Total Repair per Unit ULAB Gresik' }));
const optionsLingkupJKT = ref(createBaseChart('TOTAL ORDER PER RUANG LINGKUP ULAB JAKARTA', { chart: 'ruang_lingkup', chart_ulab: 'JKT', title: 'Total Order per Ruang Lingkup ULAB Jakarta' }));
const optionsLingkupGRK = ref(createBaseChart('TOTAL ORDER PER RUANG LINGKUP ULAB GRESIK', { chart: 'ruang_lingkup', chart_ulab: 'GRK', title: 'Total Order per Ruang Lingkup ULAB Gresik' }));
const optionsPelaksanaJKT = ref(createBaseChart('TOTAL KALIBRASI PER PELAKSANA ULAB JAKARTA', { chart: 'kalibrasi_pelaksana', chart_ulab: 'JKT', title: 'Total Kalibrasi per Pelaksana ULAB Jakarta' }));
const optionsPelaksanaRapairJKT = ref(createBaseChart('TOTAL REPAIR PER PELAKSANA ULAB JAKARTA', { chart: 'repair_pelaksana', chart_ulab: 'JKT', title: 'Total Repair per Pelaksana ULAB Jakarta' }));
const optionsPelaksanaGRK = ref(createBaseChart('TOTAL KALIBRASI PER PELAKSANA ULAB GRESIK', { chart: 'kalibrasi_pelaksana', chart_ulab: 'GRK', title: 'Total Kalibrasi per Pelaksana ULAB Gresik' }));
const optionsPelaksanaRapairGRK = ref(createBaseChart('TOTAL REPAIR PER PELAKSANA ULAB GRESIK', { chart: 'repair_pelaksana', chart_ulab: 'GRK', title: 'Total Repair per Pelaksana ULAB Gresik' }));

// --- New Chart Options for Analysis (Horizontal Bar) ---
const createAnalysisChart = (title: string, color: string, chartUlab: string) => ({
  series: [],
  chart: {
    id: `analisis_tahap_${chartUlab}`,
    type: 'bar',
    height: 380,
    toolbar: {
      show: true,
      tools: {
        download: true,
        customIcons: [{ icon: ' DETAIL', index: 0, title: 'Lihat Detail', class: 'custom-icon', click: () => openChartDetail({ chart: 'analisis_tahap', chart_ulab: chartUlab, title }) }]
      }
    }
  },
  colors: [color],
  title: { text: title, align: 'left' },
  subtitle: { text: 'Klik bar tahapan untuk melihat daftar alat', align: 'left' },
  plotOptions: {
    bar: {
      borderRadius: 4,
      horizontal: true,
      barHeight: '60%',
      dataLabels: {
        position: 'center',
      },
    }
  },
  dataLabels: {
    enabled: true,
    textAnchor: 'start',
    formatter: function (val: any) {
      return val + " Jam";
    },
    style: {
      fontSize: '12px',
      colors: ["#fff"],
      fontWeight: 'bold',
    },
    dropShadow: {
      enabled: true,
      top: 1,
      left: 1,
      blur: 1,
      opacity: 0.45
    }
  },
  stroke: {
    show: true,
    width: 1,
    colors: ['#fff']
  },
  xaxis: {
    categories: [],
    title: { text: 'Rata-rata Durasi (Jam)' },
    labels: { style: { fontSize: '11px' } }
  },
  yaxis: {
    labels: {
      style: { fontSize: '12px', fontWeight: 500 },
      maxWidth: 250
    }
  },
  tooltip: {
    y: {
      formatter: function (val: any) {
        return val + " Jam";
      }
    }
  }
});

const createPerformanceChart = (chartId: string, title: string) => ({
  series: [{ name: '', data: [] as number[] }],
  chart: {
    id: `${chartId}_ALL`,
    type: 'bar',
    height: 400,
    toolbar: {
      show: true,
      tools: {
        download: true,
        customIcons: [{ icon: ' DETAIL', index: 0, title: 'Lihat Detail', class: 'custom-icon', click: () => openChartDetail({ chart: chartId, title }) }]
      }
    }
  },
  colors: [] as string[],
  title: { text: title, align: 'left' },
  subtitle: { text: 'Klik bar untuk melihat daftar alat.', align: 'left' },
  plotOptions: {
    bar: {
      borderRadius: 4,
      horizontal: true,
      barHeight: '70%',
      distributed: true,
      dataLabels: { position: 'bottom' },
    }
  },
  dataLabels: {
    enabled: true,
    textAnchor: 'start',
    formatter: function (val: any) {
      return val + " Jam";
    },
    offsetX: 0,
    style: {
      fontSize: '12px',
      colors: ["#304758"],
      fontWeight: 'bold',
    }
  },
  xaxis: {
    categories: [] as string[],
    title: { text: 'Jam' }
  },
  yaxis: {
    labels: {
      style: { fontSize: '12px', fontWeight: 600 }
    }
  },
  tooltip: {
    y: {
      formatter: function (val: any) { return val + " Jam"; }
    }
  }
});

const optionsAnalisisJKT = ref(createAnalysisChart('Rata-rata Durasi per Tahap ULAB Jakarta', '#5d87ff', 'JKT'));
const optionsAnalisisGRK = ref(createAnalysisChart('Rata-rata Durasi per Tahap ULAB Gresik', '#13deb9', 'GRK'));
const optionsVerifikasiPelaksana = ref(createPerformanceChart('durasi_verifikasi_pelaksana', 'Rata-rata Waktu Verifikasi per Alat'));
const optionsPelaksanaLambat = ref(createPerformanceChart('durasi_pelaksana', 'Rata-rata Waktu Pengerjaan per Alat'));

const UnitOptions = ref<any>({
  series: [{ name: 'SEDANG PROGRES', type: 'area', data: [] }, { name: 'TERBIT SERTIFIKAT', type: 'area', data: [] }],
  chart: { toolbar: { show: true, tools: { download: true, customIcons: [{ icon: ' DETAIL', index: 0, title: 'tooltip', class: 'custom-icon', click: () => { detailOpenGlobalJrs.value = true; } }] } } },
  colors: [themeColors.orange, themeColors.success],
  title: { text: 'PROGRES ALAT UNIT', align: 'left' },
  legend: { position: 'top' },
  dataLabels: { enabled: false },
  stroke: { width: [2, 2, 2], curve: 'smooth' },
  fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 2.0, stops: [0, 90, 100] } },
  xaxis: { type: 'datetime', categories: cat },
  yaxis: { labels: { formatter: (v: any) => new Intl.NumberFormat('id-ID').format(v) } },
  tooltip: { x: { format: 'dd/MM/yy HH:mm' }, y: { formatter: (v: any) => new Intl.NumberFormat('id-ID').format(v) } }
});

const createPieChart = (title: string, labels: string[], chartUlab: string) => ({
  series: [],
  chart: {
    id: `lingkup_pie_${chartUlab}`,
    type: 'donut',
    toolbar: { show: true, tools: { download: true, customIcons: [{ icon: 'DETAIL', index: 0, title: 'Detail per Lingkup', class: 'custom-icon', click: () => openChartDetail({ chart: 'lingkup_pie', chart_ulab: chartUlab, title }) }] } }
  },
  labels,
  colors: scopeColors,
  title: { text: title, align: 'left' },
  subtitle: { text: 'Klik irisan atau tombol kategori di bawah untuk melihat daftar alat', align: 'left' },
  stroke: { width: 2, colors: ['#f3f5f7'] },
  dataLabels: {
    enabled: true,
    formatter: (percentage: number) => percentage >= 5 ? `${percentage.toFixed(1)}%` : '',
  },
  plotOptions: {
    pie: {
      expandOnClick: true,
      dataLabels: { minAngleToShowLabel: 12 },
      donut: {
        size: '62%',
        labels: {
          show: true,
          name: { show: true, fontSize: '13px' },
          value: {
            show: true,
            fontSize: '20px',
            fontWeight: 700,
            formatter: (value: string) => `${new Intl.NumberFormat('id-ID').format(Number(value))} Alat`,
          },
          total: {
            show: true,
            showAlways: true,
            label: 'Total Alat',
            fontSize: '13px',
            fontWeight: 600,
            formatter: (chart: any) => new Intl.NumberFormat('id-ID').format(
              chart.globals.seriesTotals.reduce((sum: number, value: number) => sum + value, 0)
            ),
          },
        },
      },
    },
  },
  legend: {
    position: 'right',
    fontSize: '11px',
    formatter: (seriesName: string, opts: any) => {
      const value = opts.w.globals.series[opts.seriesIndex] || 0;
      return `${seriesName}: ${new Intl.NumberFormat('id-ID').format(value)}`;
    },
  },
  tooltip: { y: { formatter: (val: number) => `${new Intl.NumberFormat('id-ID').format(val)} Alat` } }
});

const UnitOptionsLingkup = ref(createPieChart('Persentase Total Jumlah Per Ruang Lingkup ULAB Jakarta', lingkupLabels, 'JKT'));
const UnitOptionsLingkupGresik = ref(createPieChart('Persentase Total Jumlah Per Ruang Lingkup ULAB Gresik', lingkupLabels, 'GRK'));

const createStatusDonutChart = (
  title: string,
  labels: string[],
  colors: string[],
  chart: 'status_penyelesaian' | 'status_pengambilan',
  chartUlab: 'JKT' | 'GRK',
) => ({
  series: [0, 0],
  chart: {
    id: `${chart}_${chartUlab}`,
    type: 'donut',
    toolbar: {
      show: true,
      tools: {
        download: true,
        customIcons: [{
          icon: 'DETAIL',
          index: 0,
          title: 'Lihat seluruh rincian',
          class: 'custom-icon',
          click: () => openChartDetail({ chart, chart_ulab: chartUlab, title }),
        }],
      },
    },
  },
  labels,
  colors,
  title: { text: title, align: 'left' },
  subtitle: {
    text: chart === 'status_pengambilan'
      ? 'Data hanya menghitung alat yang sudah selesai layanan. Klik irisan untuk melihat rinciannya.'
      : 'Klik irisan untuk melihat alat pembentuk status dan posisi proses terakhirnya',
    align: 'left',
  },
  stroke: { width: 3, colors: ['#fff'] },
  dataLabels: {
    enabled: true,
    formatter: (percentage: number) => `${percentage.toFixed(1)}%`,
    style: { fontSize: '13px', fontWeight: 800, colors: ['#fff'] },
    dropShadow: { enabled: true, opacity: 0.35 },
  },
  plotOptions: {
    pie: {
      expandOnClick: true,
      donut: {
        size: '62%',
        labels: {
          show: true,
          name: { show: true, fontSize: '13px' },
          value: {
            show: true,
            fontSize: '20px',
            fontWeight: 800,
            formatter: (value: string) => `${new Intl.NumberFormat('id-ID').format(Number(value))} Alat`,
          },
          total: {
            show: true,
            showAlways: true,
            label: chart === 'status_pengambilan' ? 'Total Alat Selesai' : 'Total Alat',
            fontSize: '13px',
            fontWeight: 700,
            formatter: (chartContext: any) => new Intl.NumberFormat('id-ID').format(
              chartContext.globals.seriesTotals.reduce((sum: number, value: number) => sum + value, 0)
            ),
          },
        },
      },
    },
  },
  legend: {
    position: 'right',
    fontSize: '12px',
    formatter: (seriesName: string, opts: any) => {
      const value = Number(opts.w.globals.series[opts.seriesIndex] || 0);
      const total = opts.w.globals.seriesTotals.reduce((sum: number, item: number) => sum + item, 0);
      const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
      return `${seriesName}: ${new Intl.NumberFormat('id-ID').format(value)} (${percentage}%)`;
    },
  },
  responsive: [{
    breakpoint: 768,
    options: {
      legend: { position: 'bottom' },
      chart: { height: 370 },
    },
  }],
  tooltip: {
    y: {
      formatter: (value: number) => `${new Intl.NumberFormat('id-ID').format(value)} Alat`,
    },
  },
});

const optionsStatusPenyelesaianJKT = ref(createStatusDonutChart(
  'Status Penyelesaian Alat ULAB Jakarta',
  ['Selesai', 'Belum Selesai'],
  ['#10b981', '#f59e0b'],
  'status_penyelesaian',
  'JKT',
));
const optionsStatusPenyelesaianGRK = ref(createStatusDonutChart(
  'Status Penyelesaian Alat ULAB Gresik',
  ['Selesai', 'Belum Selesai'],
  ['#10b981', '#f59e0b'],
  'status_penyelesaian',
  'GRK',
));
const optionsStatusPengambilanJKT = ref(createStatusDonutChart(
  'Status Pengambilan Alat Selesai Layanan ULAB Jakarta',
  ['Sudah Diambil', 'Belum Diambil'],
  ['#2563eb', '#ef4444'],
  'status_pengambilan',
  'JKT',
));
const optionsStatusPengambilanGRK = ref(createStatusDonutChart(
  'Status Pengambilan Alat Selesai Layanan ULAB Gresik',
  ['Sudah Diambil', 'Belum Diambil'],
  ['#2563eb', '#ef4444'],
  'status_pengambilan',
  'GRK',
));

const fetchUnit = async (filter: any) => {
  const response = await useApi().get(`general/dropdown/mitra_m?select=id,namaperusahaan&param_search=namaperusahaan&query=${filter.query}&limit=10`);
  d_unit.value = response;
};

const getSelectedUnitId = () => item.unitfk?.value ?? item.unitfk?.id ?? null;
const selectedUnitId = computed(() => getSelectedUnitId());

const getTglAwalParam = () => {
  if (Array.isArray(item.filterYears) && item.filterYears.length > 0) {
    return item.filterYears.join(',');
  }
  return new Date().getFullYear().toString();
};

const buildDashboardUrl = () => {
  const params = new URLSearchParams();
  params.set('tglAwal', getTglAwalParam());

  if (getSelectedUnitId()) params.set('unitfk', String(getSelectedUnitId()));
  if (item.filterLingkup) params.set('lingkup', String(item.filterLingkup));
  if (item.filterUlab) params.set('ulab', String(item.filterUlab));

  return `asman/get-data-chart-integrasi?${params.toString()}`;
};

const handleChartPointSelection = (_event: any, _chart: any, config: any) => {
  const seriesIndex = Number(config?.seriesIndex);
  const dataPointIndex = Number(config?.dataPointIndex);
  const chartId = String(config?.w?.config?.chart?.id || '');
  const locationMatch = chartId.match(/_(JKT|GRK|ALL)$/);
  const chartUlab = locationMatch?.[1] === 'ALL' ? '' : (locationMatch?.[1] || '');
  const chart = locationMatch ? chartId.slice(0, -locationMatch[0].length) : chartId;
  const title = config?.w?.config?.title?.text || 'Detail Chart';

  if (!chart || seriesIndex < 0 || dataPointIndex < 0) return;

  if (chart === 'status_penyelesaian') {
    const statuses = ['selesai', 'belum_selesai'];
    const labels = ['Selesai', 'Belum Selesai'];
    const clickedValue = Number(config.w?.config?.series?.[dataPointIndex] || 0);
    openChartDetail(
      { chart, chart_ulab: chartUlab, title },
      labels[dataPointIndex] || '',
      statuses[dataPointIndex] || 'total',
      clickedValue,
      'Alat',
    );
    return;
  }

  if (chart === 'status_pengambilan') {
    const statuses = ['sudah_diambil', 'belum_diambil'];
    const labels = ['Sudah Diambil', 'Belum Diambil'];
    const clickedValue = Number(config.w?.config?.series?.[dataPointIndex] || 0);
    openChartDetail(
      { chart, chart_ulab: chartUlab, title },
      labels[dataPointIndex] || '',
      statuses[dataPointIndex] || 'total',
      clickedValue,
      'Alat',
    );
    return;
  }

  if (['kalibrasi_unit', 'repair_unit', 'ruang_lingkup', 'kalibrasi_pelaksana', 'repair_pelaksana'].includes(chart)) {
    const status = ['belum_selesai', 'selesai', 'total'][seriesIndex] || 'total';
    const category = config.w?.config?.xaxis?.categories?.[dataPointIndex]
      || config.w?.globals?.labels?.[dataPointIndex]
      || '';

    openChartDetail({ chart, chart_ulab: chartUlab, title }, category, status, getChartPointValue(config), 'Alat');
    return;
  }

  if (chart === 'lingkup_pie') {
    const pieIndex = dataPointIndex >= 0 ? dataPointIndex : seriesIndex;
    const category = config.w?.globals?.labels?.[pieIndex]
      || config.w?.config?.labels?.[pieIndex]
      || '';
    const clickedValue = config.w?.config?.series?.[pieIndex] ?? null;

    openChartDetail({ chart, chart_ulab: chartUlab, title }, category, 'total', clickedValue, 'Alat');
    return;
  }

  if (chart === 'analisis_tahap') {
    const stageLabel = config.w?.config?.xaxis?.categories?.[dataPointIndex] || '';
    openChartDetail({
      chart,
      chart_ulab: chartUlab,
      stage: analysisStageKeys[dataPointIndex],
      title,
    }, stageLabel, 'total', getChartPointValue(config), 'Jam rata-rata');
    return;
  }

  if (['durasi_pelaksana', 'durasi_verifikasi_pelaksana'].includes(chart)) {
    const isVerification = chart === 'durasi_verifikasi_pelaksana';
    const detailItems = isVerification ? verificationPerformanceDetailItems.value : performanceDetailItems.value;
    const performer = detailItems[dataPointIndex];
    if (!performer) return;

    openChartDetail({
      chart,
      pelaksana_id: performer.pelaksana_id,
      title: isVerification ? 'Durasi Verifikasi Pelaksana' : 'Durasi Pengerjaan Pelaksana',
    }, performer.nama, 'total', getChartPointValue(config), 'Jam rata-rata');
  }
};

const openChartDetail = async (context: any, category = '', status = 'total', clickedValue: number | null = null, valueUnit = 'Alat') => {
  detailChartVisible.value = true;
  detailChartLoading.value = true;
  detailChartData.value = [];

  const statusLabel: Record<string, string> = {
    belum_selesai: 'Belum Selesai',
    selesai: 'Selesai',
    sudah_diambil: 'Sudah Diambil',
    belum_diambil: 'Belum Diambil',
    total: 'Total',
  };
  const titleParts = [context.title || 'Detail Chart'];
  if (category) titleParts.push(category);
  if (statusLabel[status]) {
    const valueLabel = clickedValue === null || clickedValue === undefined
      ? statusLabel[status]
      : `${statusLabel[status]} (${clickedValue} ${valueUnit})`;
    titleParts.push(valueLabel);
  }
  detailChartTitle.value = titleParts.join(' - ');

  try {
    const params = new URLSearchParams();
    params.set('tglAwal', getTglAwalParam());
    params.set('chart', context.chart);
    params.set('status', status);

    if (getSelectedUnitId()) params.set('unitfk', String(getSelectedUnitId()));
    if (item.filterLingkup) params.set('lingkup', String(item.filterLingkup));
    if (item.filterUlab) params.set('ulab', String(item.filterUlab));
    if (context.chart_ulab) params.set('chart_ulab', String(context.chart_ulab));
    if (context.pelaksana_id) params.set('pelaksana_id', String(context.pelaksana_id));
    if (context.stage) params.set('stage', String(context.stage));
    if (category) params.set('category', category);

    const response = await useApi().get(`asman/get-detail-chart-integrasi?${params.toString()}`);
    detailChartData.value = (response.data || []).map((row: any, index: number) => ({
      ...row,
      no: index + 1,
      durasi_jam: row.durasi_jam === null || row.durasi_jam === undefined ? '-' : `${row.durasi_jam} Jam`,
    }));
  } catch (error) {
    console.error('Gagal mengambil detail chart:', error);
  } finally {
    detailChartLoading.value = false;
  }
};

const openScopeDetail = (chartUlab: string, scope: any) => {
  const ulabName = chartUlab === 'JKT' ? 'Jakarta' : 'Gresik';
  openChartDetail({
    chart: 'lingkup_pie',
    chart_ulab: chartUlab,
    title: `Persentase Ruang Lingkup ULAB ${ulabName}`,
  }, scope.label, 'total', scope.value, 'Alat');
};

const clearUnitFilter = async () => {
  item.unitfk = null;
  selectedMitraId.value = null;
  selectedMitraUnits.value = [];
  await fetchData();
};

const handleDetailFetch = async (targetRef: any, stateRef: any, mapKey: string) => {
  try {
    detailCardUnit.value = true;
    const tarif = await useApi().get(buildDashboardUrl());
    targetRef.value = tarif[mapKey].map((el: any, i: any) => ({ ...el, no: i + 1 }));
    detailCardUnit.value = false;
    stateRef.value = true;
  } catch (e) { console.error("API Error:", e); }
};

const handleJumlahUnit = () => handleDetailFetch(dataJumlahUnit, detailJumlahUnit, 'jumlahMitraDetail');
const handlePaguUnitProgres = () => handleDetailFetch(dataDetailUnitProgres, detailOpenUnitProgres, 'jumlahProgresAlatDetail');
const handleRegistrasiReapir = () => handleDetailFetch(dataDetailDaftarRepair, detailOpenDaftarRepair, 'jumlahAlatRepairDetail');
const handleRegistrasiKalibrasi = () => handleDetailFetch(dataRegisKalibrasi, detailRegisKalibrasi, 'jumlahAlatKalibrasiDetail');

const mapChartSeries = (chartRef: any, data: any[], keyName: string) => {
  const cats: string[] = [], s1: number[] = [], s2: number[] = [], s3: number[] = [];
  (data || []).forEach((d: any) => {
    cats.push(d[keyName]);
    s1.push(d.jumlahbelumselesai || 0);
    s2.push(d.jumlahselesai || 0);
    s3.push(d.jumlah || 0);
  });
  chartRef.value.xaxis.categories = cats;
  chartRef.value.series[0].data = s1;
  chartRef.value.series[1].data = s2;
  chartRef.value.series[2].data = s3;
};

const mapStatusDonut = (
  chartRef: any,
  summary: any,
  keys: ['selesai', 'belum_selesai'] | ['sudah_diambil', 'belum_diambil'],
) => {
  chartRef.value.series = keys.map((key) => Number(summary?.[key] || 0));
};

const mapPerformanceChart = (data: any[], detailItemsRef: any, chartRef: any) => {
  const performanceData = Array.isArray(data) ? data : [];
  const categories = performanceData.map((entry: any) =>
    `${entry.nama} (${entry.lokasi}) - [${entry.jumlah_alat} Alat]`
  );
  const seriesData = performanceData.map((entry: any) => entry.rata_rata_jam);

  detailItemsRef.value = performanceData;
  chartRef.value = {
    ...chartRef.value,
    colors: getDurationPerformanceColors(seriesData),
    xaxis: {
      ...chartRef.value.xaxis,
      categories,
    },
    series: [{
      name: 'Rata-rata Durasi',
      data: seriesData,
    }],
  };
};

const fetchData = async (preserveMap = false) => {
  if (!preserveMap) {
    if (leafletMap?.remove) leafletMap.remove();
    await nextTick();

    leafletMap = L.map('map').setView([-2.5, 117.5], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(leafletMap);
  }

  isLoadingCharts.value = true;
  modalFilter.value = false;

  selectedMitraId.value = getSelectedUnitId()
    ? (item.unitfk?.label ?? selectedMitraId.value)
    : null;

  const tarif = await useApi().get(buildDashboardUrl());

  if (!preserveMap) {
    const orderLookup = new Map();
    [...(tarif.jumlahOrderUnitJakarta || []), ...(tarif.jumlahOrderUnitGresik || []), ...(tarif.jumlahRepairUnitJakarta || []), ...(tarif.jumlahRepairUnitGresik || [])]
      .forEach((d: any) => orderLookup.set(d.namaperusahaan, (orderLookup.get(d.namaperusahaan) || 0) + (d.jumlah || 0)));

    const createIcon = (cls: string) => L.divIcon({ html: '<div class="inner-circle"></div>', className: `custom-div-icon ${cls}`, iconSize: [30, 30], iconAnchor: [15, 30] });
    const iconHas = createIcon('marker-has-orders'), iconNo = createIcon('marker-no-orders');

  tarif.data.forEach((mitra: any) => {
    const marker = L.marker([mitra.lat, mitra.lng], { icon: (orderLookup.get(mitra.namaperusahaan) || 0) > 0 ? iconHas : iconNo }).addTo(leafletMap);
    marker.bindTooltip(mitra.namaperusahaan, { permanent: true, direction: 'right', offset: [10, 0], className: 'map-label' }).openTooltip();
    marker.bindPopup(`
      <div class="popup-header"><p class="popup-title">${mitra.namaperusahaan}</p><p class="popup-subtitle">${mitra.alamatktr}</p></div>
      <div class="popup-body"><div class="popup-details">
        <div class="detail-item"><svg class="detail-icon" style="color:#f39c12;" viewBox="0 0 24 24" fill="currentColor"><path d="M13 3h-2v10h2V3m4.83 2.17L16.41 6.59A7.93 7.93 0 0 1 19 12a8 8 0 1 1-8-8c1.45 0 2.8.38 4 .95l1.42-1.42C15.03 2.56 13.57 2 12 2a10 10 0 1 0 10 10c0-2.12-.64-4.07-1.76-5.64l.01-.01Z"/></svg><span class="detail-text">${mitra.kapasitaspembangkit ?? '-'}</span></div>
        <div class="detail-item"><svg class="detail-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.4-2.4c.4-.4.4-1 0-1.4z"/></svg><span class="detail-text" id="unit-count-${mitra.id}">...</span></div>
        <div class="detail-item"><svg class="detail-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2H6v6l4 4-4 4v6h12V16l-4-4 4-4V2zm-2 14.5V19H8v-2.5l4-4 4 4zm-4-5l-4-4V5h8v3.5l-4 4z"/></svg><span class="detail-text" id="unit-progres-${mitra.id}">...</span></div>
        <div class="detail-item"><svg class="detail-icon" style="color:#27ae60;" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg><span class="detail-text" id="unit-complete-${mitra.id}">...</span></div>
      </div><div class="popup-percent"><span class="percent-value" id="unit-percent-${mitra.id}">0%</span></div></div>
      <div class="popup-footer"><a href="#" id="see-detail-${mitra.id}" class="popup-link">Lihat Selengkapnya</a></div>
    `, { className: 'custom-popup' });

    marker.on('popupopen', () => {
      const link = document.getElementById(`see-detail-${mitra.id}`);
      if (link) link.onclick = (e) => { e.preventDefault(); masukDetailCustomer(mitra); };
    });

    marker.on('click', async () => {
      selectedMitraId.value = mitra.namaperusahaan;
      item.unitfk = { label: mitra.namaperusahaan, value: mitra.id };
      await fetchUnitDetailByMitra(mitra.id);
      await fetchData(true);

      ['count', 'progres', 'complete'].forEach((key, index) => {
        const element = document.getElementById(`unit-${key}-${mitra.id}`);
        if (element) {
          element.textContent = String([totalOrderUnit.value, alatProgres.value, alatSelesai.value][index]);
        }
      });

      const percentElement = document.getElementById(`unit-percent-${mitra.id}`);
      if (percentElement) {
        const percent = totalOrderUnit.value > 0
          ? Math.round((Number(alatSelesai.value) / Number(totalOrderUnit.value)) * 100)
          : 0;
        percentElement.textContent = `${percent}%`;
      }
    });
  });
  }
  jumlahMitra.value = tarif.jumlahMitra;
  jumlahProgresAlat.value = tarif.jumlahProgresAlat;
  jumlahAlatKalibrasi.value = tarif.jumlahAlatKalibrasi;
  jumlahAlatRepair.value = tarif.jumlahAlatRepair;
  isLoadingCharts.value = false;

  mapChartSeries(optionsKalibrasiJakarta, tarif.jumlahOrderUnitJakarta, 'namaperusahaan');
  mapChartSeries(optionsKalibrasiGresik, tarif.jumlahOrderUnitGresik, 'namaperusahaan');
  mapChartSeries(optionsRepairJakarta, tarif.jumlahRepairUnitJakarta, 'namaperusahaan');
  mapChartSeries(optionsRepairGresik, tarif.jumlahRepairUnitGresik, 'namaperusahaan');
  mapChartSeries(optionsLingkupJKT, tarif.jumlahKalibrasiLingkupJakarta, 'lingkup');
  mapChartSeries(optionsLingkupGRK, tarif.jumlahKalibrasiLingkupGresik, 'lingkup');
  mapChartSeries(optionsPelaksanaJKT, tarif.jumlahPerPelaksanaJKT, 'pelaksana');
  mapChartSeries(optionsPelaksanaGRK, tarif.jumlahPerPelaksanaGRK, 'pelaksana');
  mapChartSeries(optionsPelaksanaRapairJKT, tarif.jumlahPerPelaksanaRepairJKT, 'pelaksana');
  mapChartSeries(optionsPelaksanaRapairGRK, tarif.jumlahPerPelaksanaRepairGRK, 'pelaksana');

  // --- Map data for Analysis Charts (Bottleneck) ---
  if (tarif.dataAnalisiJKT) {
    optionsAnalisisJKT.value.series = tarif.dataAnalisiJKT.series;
    optionsAnalisisJKT.value.xaxis.categories = tarif.dataAnalisiJKT.categories;
  }
  if (tarif.dataAnalisiGRK) {
    optionsAnalisisGRK.value.series = tarif.dataAnalisiGRK.series;
    optionsAnalisisGRK.value.xaxis.categories = tarif.dataAnalisiGRK.categories;
  }

  mapPerformanceChart(tarif.analisisVerifikasiPelaksana, verificationPerformanceDetailItems, optionsVerifikasiPelaksana);
  mapPerformanceChart(tarif.analisisPelaksanaLambat, performanceDetailItems, optionsPelaksanaLambat);

  const processLingkup = (dataObj: any, chartRef: any, detailItemsRef: any) => {
    const raw = lingkupLabels.map(label => dataObj?.[label.toLowerCase()] ?? 0);
    const fSeries: number[] = [], fLabels: string[] = [];
    raw.forEach((v, i) => { if (v > 0) { fSeries.push(v); fLabels.push(lingkupLabels[i]); } });
    chartRef.value.series = fSeries;
    chartRef.value.labels = fLabels;
    chartRef.value.colors = fLabels.map(label => scopeColorMap[label]);
    detailItemsRef.value = fLabels.map((label, index) => ({
      label,
      value: fSeries[index],
      color: scopeColorMap[label],
    }));
  };
  processLingkup(tarif.jumlahAlatLingkup?.[0], UnitOptionsLingkup, scopeDetailItemsJKT);
  processLingkup(tarif.jumlahAlatLingkupGresik?.[0], UnitOptionsLingkupGresik, scopeDetailItemsGRK);
  mapStatusDonut(optionsStatusPenyelesaianJKT, tarif.statusRingkasJakarta, ['selesai', 'belum_selesai']);
  mapStatusDonut(optionsStatusPenyelesaianGRK, tarif.statusRingkasGresik, ['selesai', 'belum_selesai']);
  mapStatusDonut(optionsStatusPengambilanJKT, tarif.statusRingkasJakarta, ['sudah_diambil', 'belum_diambil']);
  mapStatusDonut(optionsStatusPengambilanGRK, tarif.statusRingkasGresik, ['sudah_diambil', 'belum_diambil']);
};

const fetchUnitDetailByMitra = async (id: number) => {
  try {
    const res = await useApi().get(`/asman/unit-by-idmitra?id=${id}`);
    selectedMitraUnits.value = res.data;
    totalOrderUnit.value = res.data.length;
    alatProgres.value = res.alatprogres;
    alatSelesai.value = res.alatselesai;

    const sProgres = Array(cat.length).fill(0), sSuccess = Array(cat.length).fill(0);
    res.dataAlatUnit.forEach((item: any) => {
      const idx = cat.indexOf(item.tanggal);
      if (idx !== -1) {
        sProgres[idx] += parseFloat(item.belum);
        sSuccess[idx] += parseFloat(item.disetujui);
      }
    });
    UnitOptions.value.series[0].data = sProgres;
    UnitOptions.value.series[1].data = sSuccess;
  } catch (err) { console.error('Gagal ambil unit mitra', err); selectedMitraUnits.value = []; }
};

const masukDetailCustomer = (e: any) => {
  router.push({ name: 'module-asman-customer-viewer', query: { idunit: e.id } });
};

const writeExcel = (fileName: string, sheetTitle: string, mainTitle: string, header: string[], data: any[][], mergeCols: number, colWidths: number[]) => {
  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.aoa_to_sheet([[mainTitle], [], header, ...data]);
  const headerStyle = { alignment: { horizontal: 'center', vertical: 'center' }, font: { color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: '807C7C' } } };

  const range = XLSX.utils.decode_range(ws['!ref']!);
  for (let c = range.s.c; c <= range.e.c; c++) {
    const cell = XLSX.utils.encode_cell({ r: 2, c });
    if (!ws[cell]) ws[cell] = { t: 's', v: '' };
    ws[cell].s = headerStyle;
  }

  ws['!cols'] = colWidths.map(w => ({ wch: w }));
  const titleCell = XLSX.utils.encode_cell({ r: 0, c: 0 });
  if (!ws[titleCell]) ws[titleCell] = { t: 's', v: mainTitle };
  ws[titleCell].s = { alignment: { horizontal: 'center', vertical: 'center' }, font: { bold: true, sz: 18 } };
  ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 1, c: mergeCols } }];

  XLSX.utils.book_append_sheet(wb, ws, sheetTitle, true);
  XLSXStyle.writeFile(wb, fileName);
};

const exportExcelJumlahUnit = () => writeExcel('jumlah_unit.xlsx', 'Jumlah Unit', 'Jumlah Unit', ['NO', 'NAMA UNIT', 'ALAMAT UNIT'], dataJumlahUnit.value.map((e: any) => [e.no, e.namaperusahaan, e.alamatktr]), 3, [5, 18, 18]);
const exportExcelDetailUnitProgres = () => writeExcel('Detail Unit Daftar Alat.xlsx', 'Deatil Daftar Alat Unit', 'DETAIL UNIT DAFTAR ALAT', ['NO', 'NAMA UNIT', 'TGL REGISTRASI', 'JENIS ORDER'], dataDetailUnitProgres.value.map((e: any) => [e.no, e.namaperusahaan, e.tglregistrasi, e.jenisorder]), 4, [5, 18, 18, 18]);
const exportDetailKalibrasi = () => writeExcel('Detail Daftar Kalibrasi.xlsx', 'Detail Daftar Kalibrasi', 'Deatil Daftar Kalibrasi', ['NO', 'NAMA UNIT', 'TGL REGISTRASI'], dataRegisKalibrasi.value.map((e: any) => [e.no, e.namaperusahaan, e.tglregistrasi]), 3, [5, 18, 18]);
const exportExcelDetailRegisRepair = () => writeExcel('Detail Daftar Repair.xlsx', 'Detail Daftar Repair', 'Detail Daftar Repair', ['NO', 'NAMA UNIT', 'TGL REGISTRASI'], dataDetailDaftarRepair.value.map((e: any) => [e.no, e.namaperusahaan, e.tglregistrasi]), 3, [5, 18, 18]);
const exportExcel = () => writeExcel('Detail Alat Unit.xlsx', 'Detail Alat Unit', `Detail Alat UNIT ${selectedMitraId.value}`, ['NAMA ALAT', 'MERK', 'TIPE', 'SERIAL NUMBER', 'TGL REGISTRASI', 'LINGKUP KALIBRASI', 'LOKASI', 'STATUS ALAT'], selectedMitraUnits.value.map((e: any) => [e.namaproduk, e.namamerk, e.namatipe, e.namaserialnumber, e.tglregistrasi, e.lingkupkalibrasi, e.lokasi, e.statusAlat]), 8, [20, 18, 18, 18, 18, 18, 18, 18]);

onMounted(async () => {
  delete (L.Icon.Default.prototype as any)._getIconUrl;
  L.Icon.Default.mergeOptions({ iconRetinaUrl: '/marker-icon-2x.png', iconUrl: '/marker-icon.png', shadowUrl: '/marker-shadow.png' });
  isLoadingCharts.value = false;
  await nextTick();
  await fetchData();
});
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/integrasi-sistem/dashboard-vclaim';
@import '/@src/scss/module/dashboard/customer.scss';

.region-divider {
  border-right: 2px solid #e0e0e0;
}

@media screen and (max-width: 768px) {
  .region-divider {
    border-right: none;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 20px;
    padding-bottom: 20px;
  }
}

.map-label {
  background-color: rgba(255, 255, 255, 0.85);
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 4px 8px;
  font-size: 11px;
  font-weight: bold;
  color: #333;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  white-space: nowrap;
}

.custom-popup .leaflet-popup-content-wrapper {
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.custom-popup .leaflet-popup-content {
  margin: 0;
  padding: 12px;
  width: 280px !important;
}

.popup-header {
  border-bottom: 1px solid #eee;
  padding-bottom: 8px;
  margin-bottom: 10px;
}

.popup-title {
  font-weight: bold;
  font-size: 16px;
  color: #2c3e50;
  margin: 0;
}

.popup-subtitle {
  font-size: 11px;
  color: #7f8c8d;
  margin: 2px 0 0 0;
}

.popup-body {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.popup-details {
  flex-grow: 1;
}

.unit-filter-notice {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 8px;
}

.calculation-note {
  color: #d32f2f !important;
  font-weight: 700;
}

.chart-scroll-container {
  max-height: 640px;
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 6px;
  scrollbar-color: #9fb3c8 #edf2f7;
  scrollbar-width: thin;
}

.scope-detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  margin-top: 8px;
}

.scope-detail-button {
  display: grid;
  grid-template-columns: 12px minmax(0, 1fr) auto;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #dce4ec;
  border-radius: 8px;
  background: #f7f9fc;
  color: #25364a;
  cursor: pointer;
  font-size: 11px;
  text-align: left;
  transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}

.scope-detail-button:hover {
  transform: translateY(-1px);
  border-color: #2196f3;
  box-shadow: 0 4px 12px rgb(33 150 243 / 14%);
}

.scope-detail-color {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.scope-detail-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.detail-chart-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
  padding: 12px 16px;
  border-left: 4px solid #2196f3;
  border-radius: 6px;
  background: #eef7ff;
}

.detail-chart-summary p {
  margin: 3px 0 0;
  color: #536271;
  font-size: 13px;
}

.detail-chart-count {
  flex-shrink: 0;
  padding: 7px 12px;
  border-radius: 999px;
  background: #2196f3;
  color: #fff;
  font-weight: 700;
}

.status-donut-card {
  min-height: 390px;
  padding: 16px;
}

.last-status-badge,
.pickup-detail-badge {
  display: inline-flex;
  align-items: center;
  max-width: 280px;
  padding: 7px 10px;
  border: 1px solid transparent;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.35;
  white-space: normal;
}

.last-status-badge.is-complete {
  border-color: #86efac;
  background: #dcfce7;
  color: #15803d;
}

.last-status-badge.is-pending {
  border-color: #fdba74;
  background: #fff7ed;
  color: #c2410c;
}

.pickup-detail-badge.is-picked-up {
  border-color: #1d4ed8;
  background: #2563eb;
  color: #fff;
}

.pickup-detail-badge.is-waiting {
  border-color: #dc2626;
  background: #ef4444;
  color: #fff;
}

:deep(.apexcharts-bar-area),
:deep(.apexcharts-pie-area) {
  cursor: pointer;
}

@media (max-width: 768px) {
  .scope-detail-grid {
    grid-template-columns: 1fr;
  }

  .status-donut-card {
    min-height: 410px;
    padding: 10px;
  }
}

.detail-item {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
}

.detail-icon {
  width: 24px;
  height: 24px;
  margin-right: 10px;
  color: #3498db;
}

.detail-text {
  font-size: 14px;
  color: #34495e;
}

.popup-percent {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding-left: 15px;
}

.percent-value {
  font-size: 52px;
  font-weight: 700;
  color: #27ae60;
  line-height: 1;
}

.popup-footer {
  margin-top: 8px;
  text-align: left;
}

.popup-link {
  font-size: 12px;
  color: #1976d2;
  text-decoration: underline;
  cursor: pointer;
}

.custom-div-icon {
  border-radius: 50% 50% 50% 0;
  border: 1px solid #fff;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.4);
  transform: rotate(-45deg);
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.custom-div-icon .inner-circle {
  width: 12px;
  height: 12px;
  background: #fff;
  border-radius: 50%;
  transform: rotate(45deg);
}

.marker-has-orders {
  background-color: #1976d2;
}

.marker-no-orders {
  background-color: #9E9E9E;
}
</style>
