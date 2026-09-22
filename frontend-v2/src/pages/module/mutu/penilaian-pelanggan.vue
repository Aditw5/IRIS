<template>
  <div class="personal-dashboard personal-dashboard-v2">
    <!--Personal Dashboard V2-->
    <div class="columns is-multiline">
      <div class="column is-12">
        <div class="dashboard-header">
          <img src="/@src/assets/illustrations/dashboards/personal/UMRO.png" alt=""
            style="max-width:10%; margin-left: 2rem; margin-bottom: 1rem;" />
          <div class="user-meta is-dark-bordered-12">
            <h3 class="title is-4 is-narrow is-bold">Skor Kepuasan Pelanggan</h3>
            <p class="light-text"> Terakhir diperbarui: {{ lastUpdate }}</p>
          </div>
          <div class="user-action">
            <h3 class="title is-2 is-narrow" style="display: flex; align-items: center;">
              <div class="stars-outer" :aria-label="`Rating ${ulabBintang} dari 5`">
                <div class="stars-inner" :style="{ width: (ulabBintang / 5 * 100) + '%' }"></div>
              </div>
              <span class="has-text-grey-dark" style="font-size: 15pt ;margin-left: 0.5rem;">
                ({{ ulabBintang.toString().replace('.', ',') }} dari 5)
              </span>
            </h3>
            <p class="rating-caption">Rata-rata ulasan sesuai filter aktif. Order standar U-LAB dan kalibrasi internal tidak dihitung.</p>
          </div>
        </div>
      </div>

      <div class="column is-12">
        <div class="customer-location-tabs" role="tablist" aria-label="Filter lokasi laboratorium">
          <button v-for="tab in locationTabs" :key="tab.value" type="button" role="tab"
            :aria-selected="activeLocation === tab.value" :class="{ 'is-active': activeLocation === tab.value }"
            @click="setLocationTab(tab.value)">
            <i class="iconify" :data-icon="tab.icon"></i>
            <span>{{ tab.label }}</span>
          </button>
        </div>

        <div class="dashboard-card customer-filter-card">
          <div class="filter-grid">
            <VField label="Tahun">
              <VControl>
                <Calendar v-model="item.filterTgl" view="year" dateFormat="yy" class="is-fullwidth" />
              </VControl>
            </VField>

            <VField label="Unit Pelanggan">
              <VControl>
                <Dropdown v-model="filters.unitId" :options="unitOptions" optionLabel="label" optionValue="value"
                  placeholder="Semua unit" :filter="true" :showClear="true" class="is-fullwidth" />
              </VControl>
            </VField>

            <VField label="Status Survey">
              <VControl>
                <Dropdown v-model="filters.surveyStatus" :options="surveyStatusOptions" optionLabel="label"
                  optionValue="value" class="is-fullwidth" />
              </VControl>
            </VField>

            <VField label="Jenis Order">
              <VControl>
                <Dropdown v-model="filters.jenisOrder" :options="orderTypeOptions" optionLabel="label"
                  optionValue="value" class="is-fullwidth" />
              </VControl>
            </VField>

            <VField label="Progress Order">
              <VControl>
                <Dropdown v-model="filters.progressStatus" :options="progressStatusOptions" optionLabel="label"
                  optionValue="value" class="is-fullwidth" />
              </VControl>
            </VField>

            <VField label="Pencarian">
              <VControl icon="feather:search">
                <input v-model="item.search" class="input" placeholder="No pendaftaran, unit, PIC, no. HP..."
                  @keyup.enter="loadCustomerAssessment" />
              </VControl>
            </VField>
          </div>

          <div class="filter-actions">
            <VButton icon="feather:search" color="primary" :loading="isLoadDataOrder" @click="loadCustomerAssessment">
              Terapkan Filter
            </VButton>
            <VButton icon="feather:rotate-ccw" outlined @click="resetFilters">Reset</VButton>
            <VButton icon="feather:file-text" color="danger" outlined :loading="exportingPdf" @click="exportPdf">
              Export PDF
            </VButton>
            <VButton icon="feather:file" color="success" outlined :loading="exportingExcel" @click="exportExcel">
              Export Excel
            </VButton>
          </div>
        </div>
      </div>

      <div class="column is-12">
        <div class="dashboard-card has-margin-bottom">
          <div class="customer-kpi-grid">
            <div class="customer-kpi is-total">
              <i class="iconify" data-icon="feather:clipboard"></i>
              <div><span>Total Pendaftaran</span><strong>{{ surveyCoverageMeta.totalRegistrasi }}</strong></div>
            </div>
            <div class="customer-kpi is-filled">
              <i class="iconify" data-icon="feather:check-circle"></i>
              <div><span>Sudah Isi Survey</span><strong>{{ surveyCoverageMeta.totalIsiSurvey }}</strong></div>
            </div>
            <div class="customer-kpi is-pending">
              <i class="iconify" data-icon="feather:clock"></i>
              <div><span>Belum Isi Survey</span><strong>{{ surveyCoverageMeta.totalBelumSurvey }}</strong></div>
            </div>
            <div class="customer-kpi is-coverage">
              <i class="iconify" data-icon="feather:pie-chart"></i>
              <div><span>Coverage Survey</span><strong>{{ formatPercent(surveyCoverageMeta.persentase) }}</strong></div>
            </div>
          </div>

          <div class="table-heading">
            <div>
              <h3>Daftar Penilaian Pelanggan</h3>
              <p>{{ activeFilterDescription }}</p>
            </div>
            <span>{{ ulab.length }} data</span>
          </div>

          <DataTable v-model:expandedRows="expandedRows" :value="ulab" dataKey="norec" tableStyle="min-width:110rem"
            :paginator="true" :rows="10" :rowsPerPageOptions="[10, 25, 50]" stripedRows :loading="isLoadDataOrder">
            <Column expander style="width:3rem" />
            <Column field="nopendaftaran" header="No Pendaftaran" sortable />
            <Column field="namaperusahaan" header="Unit" sortable />
            <Column header="Penanggung Jawab" sortable field="namapenanggungjawab" style="min-width:13rem">
              <template #body="{ data }">
                <div class="pic-cell">
                  <strong>{{ data.namapenanggungjawab || '-' }}</strong>
                  <span>{{ data.jabatanpenanggungjawab || 'Jabatan tidak tersedia' }}</span>
                </div>
              </template>
            </Column>
            <Column field="nohppenanggungjawab" header="No. HP" sortable style="min-width:10rem">
              <template #body="{ data }"><span class="phone-value">{{ data.nohppenanggungjawab || '-' }}</span></template>
            </Column>
            <Column field="lokasi" header="Lokasi" sortable>
              <template #body="{ data }"><span class="location-pill">{{ data.lokasi || '-' }}</span></template>
            </Column>
            <Column field="jenisorder" header="Jenis Order" sortable>
              <template #body="{ data }"><span class="order-pill">{{ capitalize(data.jenisorder) }}</span></template>
            </Column>
            <Column header="Progress Order" style="text-align:center; min-width:8rem">
              <template #body="{ data }">
                <div v-if="data.jumlahdetail && data.jumlahselesai != null" class="order-progress">
                  <span>Selesai {{ data.jumlahselesai }}/{{ data.jumlahdetail }}</span>
                  <div class="order-progress-track">
                    <div :style="{ width: getOrderProgress(data) + '%' }"></div>
                  </div>
                </div>
                <span v-else>-</span>
              </template>
            </Column>
            <Column field="rata2Bintang" header="Rating Pelanggan" sortable>
              <template #body="slotProps">
                <div v-if="slotProps.data.rata2Bintang !== null" class="stars-summary"
                  :aria-label="`Rating ${slotProps.data.rata2Bintang} dari 5`"
                  style="display:flex; align-items:center;">
                  <div class="stars-outer" style="font-size:1rem;">
                    <div class="stars-inner"
                      :style="{ width: ((parseFloat(slotProps.data.rata2Bintang) / 5) * 100) + '%' }"></div>
                  </div>
                  <span class="has-text-grey-dark" style="margin-left:0.5rem; font-size:0.9rem;">
                    ({{ slotProps.data.rata2Bintang.toString().replace('.', ',') }} dari 5)
                  </span>
                </div>
                <span v-else class="text-500">-</span>
              </template>
            </Column>
            <Column field="tglregistrasi" header="Tgl Registrasi" sortable>
              <template #body="{ data }">{{ formatDateDisplay(data.tglregistrasi) }}</template>
            </Column>
            <Column field="isikepuasanpelanggan" header="Isi Survey Pelanggan" sortable
              style="min-width: 100px; text-align:center">
              <template #body="slotProps">
                <span class="survey-status"
                  :class="slotProps.data.isikepuasanpelanggan != null ? 'is-filled' : 'is-empty'">
                  {{ slotProps.data.isikepuasanpelanggan != null ? 'Sudah' : 'Belum' }}
                </span>
                <VIconButton v-if="(slotProps.data.isikepuasanpelanggan != null)" color="info"
                  v-tooltip.top="'Lihat Isi Survey'" outlined circle icon="fas fa-eye"
                  @click="isiSurvey(slotProps.data.norec)" />
                <VIconButton
                  v-if="(slotProps.data.isikepuasanpelanggan == null && slotProps.data.jumlahselesai === slotProps.data.jumlahdetail)"
                  color="success" v-tooltip.top="'Peringatkan Isi Survey'" outlined circle icon="fas fa-paper-plane"
                  @click="kirimPeringatan(slotProps.data.norec)" :loading="isLoadDataOrder" />
              </template>
            </Column>
            <template #expansion="{ data }">
              <div class="p-4 survey-detail-panel border-round-md shadow-1">
                <h5 class="mb-2">Detail Orders untuk {{ data.namaperusahaan }}</h5>
                <DataTable :value="data.detail" tableStyle="min-width:40rem">
                  <Column field="namaproduk" header="Produk" />
                  <Column field="noorderalat" header="No Order" />
                  <Column field="bintangpenilaian" header="Penilaian">
                    <template #body="{ data: row }">
                      <div v-if="row.bintangpenilaian !== null" class="stars-summary"
                        :aria-label="`Rating ${row.bintangpenilaian} dari 5`" style="display:flex; align-items:center;">
                        <div class="stars-outer" style="font-size:1rem;">
                          <div class="stars-inner"
                            :style="{ width: ((parseFloat(row.bintangpenilaian) / 5) * 100) + '%' }"></div>
                        </div>
                        <span class="has-text-grey-dark" style="margin-left:0.5rem; font-size:0.9rem;">
                          ({{ row.bintangpenilaian.toString().replace('.', ',') }} dari 5)
                        </span>
                      </div>
                      <span v-else class="text-500">-</span>
                    </template>
                  </Column>

                  <Column field="ulasanpenilaian" header="Ulasan Penilaian" />
                </DataTable>
              </div>
            </template>
          </DataTable>

          <section class="customer-insights">
            <div class="section-heading">
              <div>
                <span>Analitik sesuai filter</span>
                <h3>Ringkasan Kepuasan Pelanggan</h3>
              </div>
              <p>Seluruh chart tidak memasukkan order standar U-LAB maupun kalibrasi internal.</p>
            </div>

            <div class="insight-grid">
              <article class="insight-card is-wide">
                <div class="insight-card-head">
                  <div><span>Mutu layanan</span><h4>Rata-rata Kepuasan per Atribut</h4></div>
                </div>
                <ApexChart v-if="hasAttributeChart" type="bar" height="430" :options="chartOptions"
                  :series="chartSeries" />
                <div v-else class="chart-empty">Belum ada jawaban survey untuk filter ini.</div>
              </article>

              <article class="insight-card">
                <div class="insight-card-head">
                  <div><span>Partisipasi</span><h4>Coverage Pengisian Survey</h4></div>
                </div>
                <ApexChart v-if="surveyCoverageMeta.totalRegistrasi" type="donut" height="320"
                  :options="surveyCoverageOptions" :series="surveyCoverageSeries" />
                <div v-else class="chart-empty">Belum ada pendaftaran.</div>
                <p class="coverage-copy" v-if="surveyCoverageMeta.totalRegistrasi">
                  {{ surveyCoverageMeta.totalIsiSurvey }} dari {{ surveyCoverageMeta.totalRegistrasi }} pendaftaran
                  sudah mengisi survey ({{ formatPercent(surveyCoverageMeta.persentase) }}).
                </p>
              </article>

              <article class="insight-card">
                <div class="insight-card-head">
                  <div><span>Kualitas ulasan</span><h4>Distribusi Rating Pelanggan</h4></div>
                </div>
                <ApexChart v-if="hasRatingDistribution" type="bar" height="320"
                  :options="ratingDistributionOptions" :series="ratingDistributionSeries" />
                <div v-else class="chart-empty">Belum ada rating pelanggan.</div>
              </article>

              <article class="insight-card">
                <div class="insight-card-head">
                  <div><span>Perbandingan unit</span><h4>Unit dengan Rating Tertinggi</h4></div>
                </div>
                <ApexChart v-if="hasUnitRating" type="bar" :height="unitChartHeight"
                  :options="unitRatingOptions" :series="unitRatingSeries" />
                <div v-else class="chart-empty">Belum ada rating per unit.</div>
              </article>

              <article class="insight-card is-wide">
                <div class="insight-card-head">
                  <div><span>Tren tahunan</span><h4>Pengisian Survey per Bulan</h4></div>
                </div>
                <ApexChart v-if="hasMonthlyTrend" type="line" height="300"
                  :options="monthlyTrendOptions" :series="monthlyTrendSeries" />
                <div v-else class="chart-empty">Belum ada tren bulanan.</div>
              </article>
            </div>
          </section>

          <!-- loading analisis AI -->
          <div class="columns is-multiline" v-if="isLoading">
            <div class="column is-6">
              <div class="analisis-gemini mt-4 p-4">
                <VCard>
                  <VPlaceloadWrap style="display: flex; flex-direction: column; align-items: center;">
                    <p style="margin-top: 10px; font-weight: 600; font-size: 18pt; color: #555; text-align: center;">
                      Memproses Analisis AI...
                    </p>
                    <ProgressSpinner style="width: 100%; max-width: 250px; height: 250px;" strokeWidth="3"
                      fill="transparent" animationDuration=".8s" aria-label="Custom ProgressSpinner" />
                  </VPlaceloadWrap>
                </VCard>
              </div>
            </div>
            <div class="column is-6">
              <div class="analisis-gemini mt-4 p-4">
                <VCard>
                  <VPlaceloadWrap style="display: flex; flex-direction: column; align-items: center;">
                    <p style="margin-top: 10px; font-weight: 600; font-size: 18pt; color: #555; text-align: center;">
                      Memproses History Survey...
                    </p>
                    <ProgressSpinner style="width: 100%; max-width: 250px; height: 250px;" strokeWidth="3"
                      fill="transparent" animationDuration=".8s" aria-label="Custom ProgressSpinner" />
                  </VPlaceloadWrap>
                </VCard>
              </div>
            </div>
          </div>

          <!-- hasil analisis AI + tree history -->
          <div class="columns is-multiline" v-if="!isLoading">
            <div v-if="analisisGemini" class="column is-6 analisis-gemini mt-4 p-4"
              style="background:#f9f9f9; border-radius:8px; white-space: pre-wrap;">
              <h3 class="title is-5 mb-2 mr-1">Analisis AI Survey Penilaian Pelanggan</h3>
              <p style="color: #000;">{{ analisisGemini }}</p>
            </div>
            <div class="column is-6">
              <VCard>
                <h3 class="title is-5 mb-2 mr-1">History Survey Penilaian Pelanggan</h3>
                <VButton @click="tambah()" type="button" icon="feather:plus" color="success" raised outlined rounded>
                  Tambah
                </VButton>
                <Tree v-model:expandedKeys="expandedKeys" v-model:selectionKeys="selectedKey" :value="dataSourceMenu"
                  class="w-full md:w-30rem mt-5" :filter="true" filterMode="lenient" @nodeSelect="onNodeSelect"
                  selectionMode="single" :metaKeySelection="false">
                  <template #default="slotProps">
                    <span v-tooltip-prime.bottom="slotProps.node.nourut.toString()">
                      {{ slotProps.node.label }}
                    </span>
                  </template>
                </Tree>
              </VCard>
            </div>
          </div>
        </div>
      </div>
    </div>

    <VModal :open="modalIsiSurvey" title="FMMO-163-14.4.3.b-86.1 Survey Kepuasan Pelanggan" size="big" actions="right"
      @close="modalIsiSurvey = false" cancelLabel="Tutup">
      <template #content>
        <div class="columns is-multiline">
          <div class="column is-12">
            <Fieldset class="p-fieldsets" legend="A.INFORMASI UMUM RESPONDEN" :toggleable="true">
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-3">
                    <span> Nama Lengkap : </span>
                  </div>
                  <div class="column is-9">
                    <VField>
                      <VInput placeholder=" Nama Lengkap....." v-model="item.namaresponden"></VInput>
                    </VField>
                  </div>
                </div>
              </div>
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-3">
                    <span> Unit/ Devisi Kerja : </span>
                  </div>
                  <div class="column is-9">
                    <VField>
                      <VInput placeholder="Unit/ Devisi Kerja....." v-model="item.unitdivisikerja"></VInput>
                    </VField>
                  </div>
                </div>
              </div>
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-3">
                    <span> Jabatan/Posisi : </span>
                  </div>
                  <div class="column is-9">
                    <VField>
                      <VInput placeholder="Unit/ Devisi Kerja....." v-model="item.jabatanresponden"></VInput>
                    </VField>
                  </div>
                </div>
              </div>
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-3">
                    <span> Lama Bekerja : </span>
                  </div>
                  <div class="column is-3">
                    <VField addons>
                      <VControl>
                        <VInput v-model="item.lamabekerja" placeholder="Lama Bekerja" />
                      </VControl>
                      <VControl class="field-addon-body">
                        <VButton static>Tahun</VButton>
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-3">
                    <span> Jenis Kelamin : </span>
                  </div>
                  <div class="column is-3">
                    <VField>
                      <AutoComplete v-model="item.jeniskelamin" :suggestions="d_jenisKelamin"
                        @complete="feetchJenisKelamin($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                        class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                        placeholder="ketik untuk mencari..." />
                    </VField>
                  </div>
                </div>
              </div>
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-3">
                    <span> Usia : </span>
                  </div>
                  <div class="column is-3">
                    <VField addons>
                      <VControl>
                        <VInput v-model="item.usia" placeholder="Usia" />
                      </VControl>
                      <VControl class="field-addon-body">
                        <VButton static>Tahun</VButton>
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-3">
                    <span> No. Telp / Hp : </span>
                  </div>
                  <div class="column is-3">
                    <VField>
                      <VInput placeholder=" No. Telp / Hp....." v-model="item.notelpon"></VInput>
                    </VField>
                  </div>
                </div>
              </div>
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-3">
                    <span> Pendidikan : </span>
                  </div>
                  <div class="column is-3">
                    <VField>
                      <AutoComplete v-model="item.pendidikan" :suggestions="d_pendidikan"
                        @complete="feetchPendidikan($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                        class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                        placeholder="ketik untuk mencari..." />
                    </VField>
                  </div>
                </div>
              </div>
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-12">
                    <span> Berapa lama Bapak/Ibu menjadi Mitra Unit Maintenance, Repair, Overhaul: : </span>
                  </div>
                  <div class="column is-6">
                    <div class="columns is-multiline">
                      <div class="column is-3">
                        <VField>
                          <VControl raw subcontrol>
                            <VCheckbox v-model="item.lamamenjadimitra" true-value="≤1 tahun" label="≤1 tahun"
                              class="p-0" color="primary" square />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-6">
                        <VField>
                          <VControl raw subcontrol>
                            <VCheckbox v-model="item.lamamenjadimitra" true-value="1 tahun < lama menjadi mitra ≤ tahun"
                              label="1 tahun < lama menjadi mitra ≤ tahun" class="p-0" color="primary" square />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-3">
                        <VField>
                          <VControl raw subcontrol>
                            <VCheckbox v-model="item.lamamenjadimitra" true-value=" > 3 tahun" label=" > 3 tahun"
                              class="p-0" color="primary" square />
                          </VControl>
                        </VField>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Fieldset>
            <!-- B – PENILAIAN TERHADAP KUALITAS LAYANAN  -->
            <Fieldset class="p-fieldsets" legend="B. PENILAIAN TERHADAP KUALITAS LAYANAN " :toggleable="true">
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-6">
                    <strong>Skala Kepuasan:</strong>
                    <ol class="ml-6">
                      <li>Sangat Tidak Puas</li>
                      <li>Tidak Puas</li>
                      <li>Kurang Puas</li>
                      <li>Cukup Puas</li>
                      <li>Puas</li>
                      <li>Sangat Puas</li>
                    </ol>
                  </div>
                  <div class="column is-6">
                    <strong>Skala Harapan / Kepentingan:</strong>
                    <ol class="ml-6">
                      <li>Sangat Tidak Penting</li>
                      <li>Tidak Penting</li>
                      <li>Kurang Penting</li>
                      <li>Cukup Penting</li>
                      <li>Penting</li>
                      <li>Sangat Penting</li>
                    </ol>
                  </div>
                  <div class="column is-12">
                    <p style="color: black;"><strong>(B1-1)</strong> Berdasarkan pengalaman Bapak/Ibu, seberapa puaskah
                      Bapak/Ibu terhadap
                      pelayanan Unit Maintenance, Repair, Overhaul?</p>
                  </div>
                  <div class="column is-12 mt-4-min">
                    <p style="color: black;"><strong>(B1-2)</strong> Berdasarkan pengalaman Bapak/Ibu, bagaimana harapan
                      Bapak/Ibu terhadap
                      pelayanan Unit Maintenance, Repair, Overhaul?</p>
                  </div>
                  <div class="column is-12 mt-4-min">
                    <p style="color: blue; font-style: italic;">[petunjuk pengisian: Lingkarilah skala yang sesuai
                      dengan
                      kepuasan Bapak/Ibu terhadap setiap atribut ini!]</p>
                  </div>

                </div>
              </div>

              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="table-container">
                    <table class="table is-bordered is-fullwidth is-striped is-hoverable">
                      <thead>
                        <tr>
                          <th>NO</th>
                          <th>DIMENSI</th>
                          <th>ATRIBUT KEPUASAN</th>
                          <th class="has-text-centered">HARAPAN<br><small>(Skala 1 - 6)</small></th>
                          <th class="has-text-centered">KEPUASAN<br><small>(Skala 1 - 6)</small></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in atributList" :key="item.no">
                          <td>{{ item.no }}</td>
                          <td>{{ item.dimensi }}</td>
                          <td>{{ item.atribut }}</td>
                          <!-- HARAPAN -->
                          <td>
                            <div class="columns is-multiline is-gapless is-mobile">
                              <div class="column is-2-desktop is-full-mobile has-text-centered" v-for="skala in 6"
                                :key="`harapan-${index}-${skala}`">
                                <VCheckbox v-model="item.harapan" :true-value="skala" :label="skala.toString()" square
                                  color="info" class="p-0" />
                              </div>
                            </div>
                          </td>
                          <!-- KEPUASAN -->
                          <td>
                            <div class="columns is-multiline is-gapless is-mobile">
                              <div class="column is-2-desktop is-full-mobile has-text-centered" v-for="skala in 6"
                                :key="`kepuasan-${index}-${skala}`">
                                <VCheckbox v-model="item.kepuasan" :true-value="skala" :label="skala.toString()" square
                                  color="success" class="p-0" />
                              </div>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-12">
                    <p style="color: black;"><strong>(B2.1)</strong> Secara umum, seberapa puas Bapak/Ibu dengan layanan
                      yang diberikan oleh
                      Unit Maintenance, Repair, Overhaul?</p>
                    <div class="columns is-mobile is-multiline is-centered mt-2">
                      <div v-for="n in 6" :key="'skala-b21-' + n" class="column is-narrow">
                        <VCheckbox v-model="item.b21" :true-value="n" :label="n.toString()" color="primary" square />
                      </div>
                    </div>
                  </div>
                  <div class="column is-12">
                    <p class="mb-2" style="color: black;">
                      <strong>(B2.2) DENGAN MENGACU PADA JAWABAN PERTANYAAN B1</strong><br>
                      Jika Bapak/Ibu merasa sangat puas terhadap jenis layanan Divisi/Bidang sebagaimana disebutkan di
                      atas,
                      atau merasa puas/ sangat puas terhadap layanan Divisi/Bidang namun informasi jenis layanan
                      tersebut
                      belum tercakup dalam instrument di atas (bagian B), mohon Bapak/Ibu berkenan memberikan ulasan
                      lebih
                      detil tentang layanan tersebut (*sifat : optional)
                    </p>
                    <VField>
                      <VControl>
                        <VTextarea v-model="item.b22" placeholder="Tuliskan ulasan Anda di sini..." rows="5" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <p class="mb-2" style="color: black;">
                      <strong>(B2.3) INOVASI</strong><br>
                      Sebutkan inovasi/peningkatan layanan dari Unit Maintenance, Repair, Overhaul pada tahun 2023 yang
                      diterima oleh Bapak/Ibu (Jika ada)
                    </p>
                    <VField>
                      <VControl>
                        <VTextarea v-model="item.b23" placeholder="Tuliskan inovasi yang Anda rasakan..." rows="5" />
                      </VControl>
                    </VField>
                  </div>
                </div>
              </div>
            </Fieldset>
            <Fieldset class="p-fieldsets" legend="C. AREAS for IMPROVEMENT" :toggleable="true">
              <div class="column is-12">
                <div class="content mb-3">
                  <p style="color: black;">
                    Apabila terdapat ketidakpuasan terhadap layanan yang diterima, mohon Bapak/Ibu dapat memberikan
                    komentar secara
                    rinci, serta masukan dan saran untuk peningkatan kinerja Unit/Divisi
                  </p>
                </div>
                <div>
                  <p><strong>C1. Ketidakpuasan terhadap Layanan</strong></p>
                  <VField>
                    <VTextarea v-model="item.ketidakpuasanlayanan" placeholder="Tulis ketidakpuasan di sini" rows="4"
                      class="mt-2" />
                  </VField>
                </div>
                <div class="mt-4">
                  <p><strong>C2. Masukan dan Saran</strong></p>
                  <VField>
                    <VTextarea v-model="item.masukansaran" placeholder="Tulis masukan dan saran di sini" rows="4"
                      class="mt-2" />
                  </VField>
                </div>
              </div>
            </Fieldset>
            <Fieldset class="p-fieldsets" legend="D. KETERIKATAN PELANGGAN" :toggleable="true">
              <div class="column is-12">
                <div class="columns is-multiline">
                  <div class="column is-12">
                    <p class="has-text-danger has-text-weight-bold mb-2">
                      *Mohon dibantu isi skala keterikatan pelanggan berikut:
                    </p>
                    <p class="has-text-danger has-text-weight-bold">Skala Kepuasan:</p>
                    <ol class="pl-4 ml-6 has-text-danger">
                      <li>Sangat Tidak Puas</li>
                      <li>Tidak Puas</li>
                      <li>Kurang Puas</li>
                      <li>Cukup Puas</li>
                      <li>Puas</li>
                      <li>Sangat Puas</li>
                    </ol>
                  </div>

                  <div class="column is-12">
                    <p class="mb-2 has-text-black">
                      <strong>1. a.</strong> Seberapa besar kemungkinan Unit/Divisi Bapak/Ibu untuk menjalin kerjasama
                      kembali dengan Unit/Divisi ini?
                    </p>
                    <VField>
                      <div class="columns is-mobile is-gapless is-multiline is-centered">
                        <div class="column is-1 has-text-centered" v-for="skala in 10" :key="`d1-${skala}`">
                          <label class="checkbox-scale">
                            <input type="checkbox" :checked="Number(item.d1_skor) === skala"
                              @change="onChangeD1(skala, $event)" />
                            <span>{{ skala }}</span>
                          </label>
                        </div>
                      </div>
                    </VField>
                  </div>

                  <div class="column is-12 ml-4">
                    <p class="mb-2 has-text-black">
                      <strong>b.</strong> Berikan penjelasan tentang pertimbangan Bapak/Ibu untuk jawaban poin a
                      (*optional)
                    </p>
                    <VField>
                      <VTextarea v-model="item.d1_penjelasan" color="info" placeholder="Tulis penjelasan Anda di sini"
                        :rows="4" />
                    </VField>
                  </div>

                  <div class="column is-12 mt-4">
                    <p class="mb-2 has-text-black">
                      <strong>2. a.</strong> Apakah Bapak/Ibu bersedia merekomendasikan Unit/Divisi ini kepada
                      kolega/divisi lain?
                    </p>
                    <VField>
                      <div class="columns is-mobile is-gapless is-multiline is-centered">
                        <div class="column is-1 has-text-centered" v-for="skala in 10" :key="`d2-${skala}`">
                          <label class="checkbox-scale">
                            <input type="checkbox" :checked="Number(item.d2_skor) === skala"
                              @change="onChangeD2(skala, $event)" />
                            <span>{{ skala }}</span>
                          </label>
                        </div>
                      </div>
                    </VField>
                  </div>

                  <div class="column is-12 ml-4">
                    <p class="m-2 has-text-black">
                      <strong>b.</strong> Berikan penjelasan tentang pertimbangan Bapak/Ibu untuk jawaban poin a
                      (*optional)
                    </p>
                    <VField>
                      <VTextarea v-model="item.d2_penjelasan" color="info" placeholder="Tulis penjelasan Anda di sini"
                        :rows="4" />
                    </VField>
                  </div>
                </div>
              </div>
            </Fieldset>
          </div>
          <div class="column is-12">
            <div class="box has-text-centered" style="border: 1px solid #000; padding: 2rem;">
              <p style="color: black;" class="has-text-weight-bold is-size-5 mb-4">PERNYATAAN</p>
              <p style="color: black;">
                Saya yang bertanda tangan di bawah ini, menyatakan bahwa pengisian jawaban di atas sudah dapat mewakili
                <br />
                Divisi/Bidang/Unit Kerja dan mengisi dengan benar.
              </p>
              <p style="color: black;" class="mt-5">Tanda Tangan</p>
              <img v-if="item.namaresponden"
                :src="'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + (item.namaresponden.label ? item.namaresponden.label : item.namaresponden)">
              <div class="mt-4 is-flex is-justify-content-center">
                <VField style="width: 300px;">
                  <VControl>
                    <VInput type="text" v-model="item.namaresponden" class="has-text-centered"
                      placeholder="Nama Penanggung Jawab" />
                  </VControl>
                </VField>
              </div>
            </div>
          </div>
        </div>
      </template>
    </VModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue'
import ApexChart from 'vue3-apexcharts'
import { useApi } from '/@src/composable/useApi'
import ColumnGroup from 'primevue/columngroup'
import Row from 'primevue/row'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import Dropdown from 'primevue/dropdown'
import FileUpload from 'primevue/fileupload'
import Dialog from 'primevue/dialog'
import * as H from '/@src/utils/appHelper'
import Tree from 'primevue/tree'
import Calendar from 'primevue/calendar';
import { exportCustomerAssessmentExcel } from '/@src/utils/customerAssessmentExcel'

useHead({
  title: 'Penilaian Pelanggan - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const ulab = ref<any[]>([])
const isTrueFlag = (value: unknown) => {
  if (value === true || value === 1) return true
  return ['1', 'true', 't', 'yes', 'y', 'on'].includes(String(value ?? '').toLowerCase())
}
const ulabBintang: any = ref(0)
const expandedRows = ref(null)
const toast = useToast()
const lastUpdate = ref('')
const item: any = ref({
  filterTgl: new Date(),
  search: '',
})
const activeLocation = ref<'all' | '1' | '2'>('all')
const locationTabs = [
  { value: 'all' as const, label: 'Semua Lokasi', icon: 'feather:globe' },
  { value: '1' as const, label: 'Jakarta', icon: 'feather:map-pin' },
  { value: '2' as const, label: 'Gresik', icon: 'feather:map-pin' },
]
const filters = reactive({
  unitId: null as number | null,
  surveyStatus: 'all',
  jenisOrder: 'all',
  progressStatus: 'selesai',
})
const surveyStatusOptions = [
  { value: 'all', label: 'Semua status survey' },
  { value: 'sudah', label: 'Sudah isi survey' },
  { value: 'belum', label: 'Belum isi survey' },
]
const orderTypeOptions = [
  { value: 'all', label: 'Semua jenis order' },
  { value: 'kalibrasi', label: 'Kalibrasi' },
  { value: 'repair', label: 'Repair' },
]
const progressStatusOptions = [
  { value: 'all', label: 'Semua progress' },
  { value: 'selesai', label: 'Order selesai' },
  { value: 'proses', label: 'Masih proses' },
]
const unitOptions = ref<Array<{ value: number; label: string }>>([])
const exportingPdf = ref(false)
const exportingExcel = ref(false)
const ratingDistribution = ref<Array<{ label: string; value: number }>>([])
const unitRatingData = ref<Array<{ unit: string; rating: number; total: number; sudah: number }>>([])
const monthlyTrendData = ref<Array<{ month: string; total: number; sudah: number; belum: number }>>([])
const modalIsiSurvey: any = ref(false)
const modalIsiDokumen = ref(false)
let dataSourceMenu: any = ref([])
const expandedKeys: any = ref({})
const selectedKey = ref(null)
let isLoadDataOrder: any = ref(false)
const selectedIDModul = ref('')
const isLoading = ref(false)
let d_ObjectModul: any = ref([])
const fileDokumenSurvey: any = ref()

const chartSeries = ref<any[]>([
  {
    name: 'Rata-rata Kepuasan',
    data: [] as number[],
  },
])

const chartOptions = ref<any>({
  chart: {
    type: 'bar',
    toolbar: { show: false },
  },
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 4,
      dataLabels: {
        position: 'top',
      },
    },
  },
  dataLabels: {
    enabled: true,
    formatter: (val: number) => val.toFixed(2),
    offsetX: 8,
    style: {
      fontSize: '12px',
    },
  },
  xaxis: {
    categories: [] as string[],
    min: 1,
    max: 6,
    tickAmount: 5,
    title: {
      text: 'Rata-rata Skor Kepuasan (1–6)',
    },
  },
  tooltip: {
    y: {
      formatter: (val: number) => val.toFixed(2),
    },
  },
  grid: {
    borderColor: '#f1f3f5',
  },
  legend: {
    show: false,
  },
})

interface AtributKepuasan {
  no: number
  dimensi: string
  atribut: string
  harapan: number | null
  kepuasan: number | null
}

const surveyCoverageSeries = ref<number[]>([])
const surveyCoverageOptions = ref<any>({
  chart: {
    type: 'donut',
  },
  labels: ['Sudah isi survey', 'Belum isi survey'],
  legend: {
    position: 'bottom',
  },
  dataLabels: {
    enabled: true,
    formatter: (val: number) => val.toFixed(1) + '%',
  },
  tooltip: {
    y: {
      formatter: (val: number) => `${val.toFixed(0)} registrasi`,
    },
  },
})

const surveyCoverageMeta = ref({
  totalRegistrasi: 0,
  totalIsiSurvey: 0,
  totalBelumSurvey: 0,
  persentase: 0,
})

const hasAttributeChart = computed(() => chartSeries.value[0]?.data?.some((value: number) => value > 0))
const hasRatingDistribution = computed(() => ratingDistribution.value.some((item) => item.value > 0))
const hasUnitRating = computed(() => unitRatingData.value.some((item) => item.rating > 0))
const hasMonthlyTrend = computed(() => monthlyTrendData.value.some((item) => item.total > 0))

const ratingDistributionSeries = computed(() => [{
  name: 'Jumlah Penilaian',
  data: ratingDistribution.value.map((item) => item.value),
}])
const ratingDistributionOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  colors: ['#f59e0b'],
  plotOptions: { bar: { borderRadius: 5, columnWidth: '52%', distributed: true } },
  dataLabels: { enabled: true },
  xaxis: { categories: ratingDistribution.value.map((item) => item.label) },
  yaxis: { min: 0, forceNiceScale: true, title: { text: 'Jumlah penilaian' } },
  legend: { show: false },
  grid: { borderColor: '#e5e7eb' },
}))

const topUnitRating = computed(() => unitRatingData.value.filter((item) => item.rating > 0).slice(0, 10))
const unitChartHeight = computed(() => Math.max(300, topUnitRating.value.length * 44 + 80))
const unitRatingSeries = computed(() => [{
  name: 'Rata-rata Rating',
  data: topUnitRating.value.map((item) => item.rating),
}])
const unitRatingOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  colors: ['#2563eb'],
  plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: '58%' } },
  dataLabels: { enabled: true, formatter: (value: number) => value.toFixed(2) },
  xaxis: {
    categories: topUnitRating.value.map((item) => truncateText(item.unit, 34)),
    min: 0,
    max: 5,
    tickAmount: 5,
  },
  tooltip: { y: { formatter: (value: number) => `${value.toFixed(2)} dari 5` } },
  grid: { borderColor: '#e5e7eb' },
}))

const monthlyTrendSeries = computed(() => [
  { name: 'Sudah Isi', data: monthlyTrendData.value.map((item) => item.sudah) },
  { name: 'Belum Isi', data: monthlyTrendData.value.map((item) => item.belum) },
])
const monthlyTrendOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  colors: ['#10b981', '#f59e0b'],
  stroke: { curve: 'smooth', width: 3 },
  markers: { size: 4 },
  dataLabels: { enabled: false },
  xaxis: { categories: monthlyTrendData.value.map((item) => formatMonth(item.month)) },
  yaxis: { min: 0, forceNiceScale: true, title: { text: 'Jumlah pendaftaran' } },
  legend: { position: 'top' },
  grid: { borderColor: '#e5e7eb' },
}))

const activeFilterDescription = computed(() => {
  const location = activeLocation.value === '1' ? 'Jakarta' : activeLocation.value === '2' ? 'Gresik' : 'Semua lokasi'
  const year = H.formatDate(item.value.filterTgl, 'YYYY')
  return `${location} • Tahun ${year} • ${getOptionLabel(surveyStatusOptions, filters.surveyStatus)}`
})

const analisisGemini = ref('')
const atributList = ref<AtributKepuasan[]>([
  {
    no: 1,
    dimensi: 'Assurance',
    atribut: 'Jaminan keamanan data/dokumen, tools, asset dan kerahasiaan informasi pelanggan',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 2,
    dimensi: 'Assurance',
    atribut: 'Kompetensi personel Laboratorium Kalibrasi',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 3,
    dimensi: 'Assurance',
    atribut: 'Peralatan standar yang digunakan sudah sesuai',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 4,
    dimensi: 'Assurance',
    atribut: 'Kejelasan informasi pada sertifikat kalibrasi',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 5,
    dimensi: 'Empathy',
    atribut: 'Kepedulian terhadap handling tools pelanggan',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 6,
    dimensi: 'Empathy',
    atribut: 'Kemampuan komunikasi/koordinasi personel dalam melayani pelanggan',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 7,
    dimensi: 'Reliability',
    atribut: 'Ketersediaan personel Laboratorium',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 8,
    dimensi: 'Reliability',
    atribut: 'Ketepatan penjadwalan kalibrasi',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 9,
    dimensi: 'Responsiveness',
    atribut: 'Kecepatan merespons permintaan dan pengaduan dari pelanggan',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 10,
    dimensi: 'Responsiveness',
    atribut: 'Kemampuan mengakomodasi diluar scope kalibrasi',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 11,
    dimensi: 'Responsiveness',
    atribut: 'Ketepatan penyampaian sertifikat hasil kalibrasi',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 12,
    dimensi: 'Tangible',
    atribut: 'Ketersediaan schedule, dan prosedur administratif lainnya',
    harapan: null,
    kepuasan: null,
  },
  {
    no: 13,
    dimensi: 'Tangible',
    atribut: 'Keterbukaan informasi mengenai proses kalibrasi',
    harapan: null,
    kepuasan: null,
  },
])

const tambah = () => {
  setNoUrut()
  modalIsiDokumen.value = true
}

const loadTree = async () => {
  const response = await useApi().get('/mutu/master-isi-survey')
  dataSourceMenu.value = response.tree
  d_ObjectModul.value = response.data
  expandAll()
}

const expandAll = () => {
  for (let node of dataSourceMenu.value) {
    expandNode(node)
  }
  expandedKeys.value = { ...expandedKeys.value }
}

const expandNode = (node: any) => {
  if (node.children && node.children.length) {
    expandedKeys.value[node.key] = true
    for (let child of node.children) {
      expandNode(child)
    }
  }
}

const tutup = () => {
  clear()
  modalIsiDokumen.value = false
}

const onNodeSelect = async (node: any) => {
  d_ObjectModul.value.forEach((element: any) => {
    if (element.key == node.parent_id) {
      item.value.kdrincianisisurvey = element
    }
  })
  item.value.id = node.key
  item.value.namasurvey = node.label
  item.value.fungsi = node.data.fungsi
  item.value.nourut = node.nourut
  item.value.keterangan = node.data.keterangan
  item.value.fileLama = node.data.isisurvey
  modalIsiDokumen.value = true
}

function clear() {
  const filterTgl = item.value.filterTgl ?? new Date()
  const search = item.value.search ?? ''
  item.value = {
    statusenabled: true,
    aktif: true,
    filterTgl,
    search,
  }
  fileDokumenSurvey.value = null
}


const setNoUrut = async () => {
  if (d_ObjectModul.value.length == 0) {
    const response = await useApi().get('/mutu/master-history-survey-nourut')
    item.value.nourut = response.nourut + 1
  } else {
    item.value.nourut = d_ObjectModul.value.length
      ? d_ObjectModul.value[d_ObjectModul.value.length - 1].nourut + 1
      : 0
  }
}

const changeHead = async () => {
  setNoUrut()
}

const kirimPeringatan = async (e: any) => {
  let json = {
    survey: {
      norec: e,
    },
  }
  isLoadDataOrder.value = true
  await useApi()
    .post(`/mutu/kirim-isi-survey-pelanggan`, json)
    .then((response: any) => {
      isLoadDataOrder.value = false
    })
    .catch((e: any) => {
      isLoadDataOrder.value = false
    })
}

const isiSurvey = async (e: any) => {
  modalIsiSurvey.value = true
  const response = await useApi().get(`/asman/header-mitra?norec_pd=${e}`)
  item.value.namaresponden = response.mitra.name
  item.value.jabatanresponden = response.mitra.jabatan
  item.value.notelpon = response.mitra.nowa
  getisiSurvey(e)
}

const getisiSurvey = async (e: any) => {
  const response = await useApi().get(`/registrasi/get-survey-pelanggan?norec_pd=${e}`)
  const data = response.data[0]
  item.value.namaresponden = data.namaresponden
  item.value.unitdivisikerja = data.unitdivisikerja
  item.value.jabatanresponden = data.jabatanresponden
  item.value.lamabekerja = data.lamabekerja
  item.value.jeniskelamin = {
    value: data.idjeniskelamin ?? '',
    label: data.jeniskelamin ?? '',
  }
  item.value.pendidikan = {
    value: data.idpendidikan ?? '',
    label: data.pendidikan ?? '',
  }
  item.value.usia = data.usia
  item.value.notelpon = data.notelpon
  item.value.lamamenjadimitra = data.lamamenjadimitra
  if (data.detailSurvey && Array.isArray(data.detailSurvey)) {
    data.detailSurvey.forEach((d: any) => {
      const index = atributList.value.findIndex((x) => x.no == d.no)
      if (index !== -1) {
        atributList.value[index].harapan = Number(d.harapan)
        atributList.value[index].kepuasan = Number(d.kepuasan)
      }
    })
  }
  item.value.b21 = data.b21
  item.value.b22 = data.b22
  item.value.b23 = data.b23
  item.value.ketidakpuasanlayanan = data.ketidakpuasanlayanan
  item.value.masukansaran = data.masukansaran
  item.value.d1_skor = data.d1_skor
  item.value.d1_penjelasan = data.d1_penjelasan
  item.value.d2_skor = data.d2_skor
  item.value.d2_penjelasan = data.d2_penjelasan
}

function buildFilterParams() {
  const params = new URLSearchParams({
    year: H.formatDate(item.value.filterTgl, 'YYYY'),
    survey_status: filters.surveyStatus,
    jenis_order: filters.jenisOrder,
    progress_status: filters.progressStatus,
  })
  if (activeLocation.value !== 'all') params.set('lokasi_id', activeLocation.value)
  if (filters.unitId) params.set('unit_id', String(filters.unitId))
  if (item.value.search?.trim()) params.set('search', item.value.search.trim())
  return params
}

function applyAttributeChart(rows: any[]) {
  const valueByNumber = new Map(rows.map((row: any) => [Number(row.no), row]))
  chartOptions.value = {
    ...chartOptions.value,
    xaxis: {
      ...chartOptions.value.xaxis,
      categories: atributList.value.map((attr) => `${attr.no}. ${attr.atribut}`),
    },
  }
  chartSeries.value = [{
    name: 'Rata-rata Kepuasan',
    data: atributList.value.map((attr) => {
      const value = Number(valueByNumber.get(attr.no)?.avg_kepuasan ?? 0)
      return Number(value.toFixed(2))
    }),
  }]
}

const loadCustomerAssessment = async () => {
  isLoadDataOrder.value = true
  try {
    const res = await useApi().get(`/mutu/get-penilaian-pelanggan?${buildFilterParams().toString()}`)
    ulab.value = (res.detail ?? []).filter(
      (row: any) => !isTrueFlag(row.iskalibrasiinternal),
    )
    unitOptions.value = res.filters?.units ?? []
    const summary = res.summary ?? {}
    ulabBintang.value = Number(summary.rata2Bintang ?? 0)
    surveyCoverageMeta.value = {
      totalRegistrasi: Number(summary.totalRegistrasi ?? 0),
      totalIsiSurvey: Number(summary.totalIsiSurvey ?? 0),
      totalBelumSurvey: Number(summary.totalBelumSurvey ?? 0),
      persentase: Number(summary.persentase ?? 0),
    }
    surveyCoverageSeries.value = [
      surveyCoverageMeta.value.totalIsiSurvey,
      surveyCoverageMeta.value.totalBelumSurvey,
    ]
    ratingDistribution.value = res.charts?.rating_distribution ?? []
    unitRatingData.value = res.charts?.by_unit ?? []
    monthlyTrendData.value = res.charts?.by_month ?? []
    applyAttributeChart(res.charts?.attributes ?? [])
    expandedRows.value = null
    lastUpdate.value = new Date().toLocaleString('id-ID', {
      day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
    return true
  } catch (e) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Gagal memuat data.',
    })
    return false
  } finally {
    isLoadDataOrder.value = false
  }
}

function setLocationTab(location: 'all' | '1' | '2') {
  activeLocation.value = location
  filters.unitId = null
  loadCustomerAssessment()
}

function resetFilters() {
  item.value.filterTgl = new Date()
  item.value.search = ''
  filters.unitId = null
  filters.surveyStatus = 'all'
  filters.jenisOrder = 'all'
  filters.progressStatus = 'selesai'
  activeLocation.value = 'all'
  loadCustomerAssessment()
}

function formatPercent(value: unknown) {
  return `${Number(value ?? 0).toFixed(2).replace('.', ',')}%`
}

function formatDateDisplay(value: unknown) {
  if (!value) return '-'
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function getOrderProgress(row: any) {
  const total = Number(row?.jumlahdetail ?? 0)
  if (total <= 0) return 0
  return Math.min(Math.max((Number(row?.jumlahselesai ?? 0) / total) * 100, 0), 100)
}

function capitalize(value: unknown) {
  const text = String(value ?? '').trim()
  return text ? text.charAt(0).toUpperCase() + text.slice(1) : '-'
}

function truncateText(value: unknown, maxLength: number) {
  const text = String(value ?? '-')
  return text.length > maxLength ? `${text.slice(0, maxLength - 1)}…` : text
}

function formatMonth(value: string) {
  const [year, month] = String(value).split('-').map(Number)
  if (!year || !month) return value
  return new Date(year, month - 1, 1).toLocaleDateString('id-ID', { month: 'short' })
}

function getOptionLabel(options: Array<{ value: string; label: string }>, value: string) {
  return options.find((option) => option.value === value)?.label ?? value
}

function escapeHtml(value: unknown) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function getExportFilterLabels() {
  return {
    year: H.formatDate(item.value.filterTgl, 'YYYY'),
    location: activeLocation.value === '1' ? 'Jakarta' : activeLocation.value === '2' ? 'Gresik' : 'Semua Lokasi',
    unit: unitOptions.value.find((option) => option.value === filters.unitId)?.label ?? 'Semua Unit',
    surveyStatus: getOptionLabel(surveyStatusOptions, filters.surveyStatus),
    orderType: getOptionLabel(orderTypeOptions, filters.jenisOrder),
    progress: getOptionLabel(progressStatusOptions, filters.progressStatus),
    search: item.value.search?.trim() || '-',
  }
}

function buildPdfHtml() {
  const filterLabels = getExportFilterLabels()
  const rows = ulab.value.map((row, index) => `
    <tr>
      <td>${index + 1}</td>
      <td>${escapeHtml(row.nopendaftaran || '-')}</td>
      <td>${escapeHtml(row.namaperusahaan || '-')}</td>
      <td>${escapeHtml(row.namapenanggungjawab || '-')}<small>${escapeHtml(row.jabatanpenanggungjawab || '')}</small></td>
      <td>${escapeHtml(row.nohppenanggungjawab || '-')}</td>
      <td>${escapeHtml(row.lokasi || '-')}</td>
      <td>${escapeHtml(capitalize(row.jenisorder))}</td>
      <td>${Number(row.jumlahselesai ?? 0)}/${Number(row.jumlahdetail ?? 0)}</td>
      <td>${row.rata2Bintang == null ? '-' : Number(row.rata2Bintang).toFixed(2)}</td>
      <td>${row.isikepuasanpelanggan != null ? 'Sudah' : 'Belum'}</td>
      <td>${escapeHtml(formatDateDisplay(row.tglregistrasi))}</td>
    </tr>
  `).join('')

  const attributeRows = atributList.value.map((attr, index) => `
    <tr><td>${attr.no}</td><td>${escapeHtml(attr.dimensi)}</td><td>${escapeHtml(attr.atribut)}</td>
    <td>${Number(chartSeries.value[0]?.data?.[index] ?? 0).toFixed(2)}</td></tr>
  `).join('')

  return `<!doctype html><html><head><meta charset="utf-8"><title>Penilaian Pelanggan</title><style>
    @page{size:A4 landscape;margin:10mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#172554;font-size:9px;margin:0}
    .header{display:flex;justify-content:space-between;gap:20px;padding:14px 16px;background:#172554;color:#fff;border-radius:8px}.header h1{font-size:19px;margin:0 0 4px}.header p,.meta p{margin:2px 0}.meta{text-align:right}
    .note{margin:9px 0;padding:7px 9px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:6px;color:#1e3a8a}
    .kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin:10px 0}.kpi{padding:9px;border:1px solid #cbd5e1;border-radius:7px;background:#f8fafc}.kpi span{display:block;color:#64748b;font-size:8px;text-transform:uppercase}.kpi strong{font-size:14px;margin-top:4px;display:block}.kpi.green{background:#ecfdf5;border-color:#a7f3d0}.kpi.amber{background:#fff7ed;border-color:#fed7aa}
    h2{font-size:13px;margin:14px 0 6px}table{width:100%;border-collapse:collapse}th{background:#1e3a8a;color:#fff;padding:6px;border:1px solid #cbd5e1;font-size:8px}td{padding:5px;border:1px solid #cbd5e1;vertical-align:top}td small{display:block;color:#64748b;margin-top:2px}tbody tr:nth-child(even){background:#f8fafc}.footer{margin-top:10px;text-align:right;color:#64748b}
    @media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
  </style></head><body>
    <div class="header"><div><h1>Penilaian Pelanggan</h1><p>Laboratorium Kalibrasi U-LAB</p></div><div class="meta"><p><strong>Tahun:</strong> ${escapeHtml(filterLabels.year)}</p><p><strong>Lokasi:</strong> ${escapeHtml(filterLabels.location)}</p><p><strong>Unit:</strong> ${escapeHtml(filterLabels.unit)}</p></div></div>
    <div class="note">Filter: ${escapeHtml(filterLabels.surveyStatus)} • ${escapeHtml(filterLabels.orderType)} • ${escapeHtml(filterLabels.progress)} • Pencarian: ${escapeHtml(filterLabels.search)}. Order standar U-LAB dan kalibrasi internal tidak disertakan.</div>
    <div class="kpis"><div class="kpi"><span>Total</span><strong>${surveyCoverageMeta.value.totalRegistrasi}</strong></div><div class="kpi green"><span>Sudah Isi</span><strong>${surveyCoverageMeta.value.totalIsiSurvey}</strong></div><div class="kpi amber"><span>Belum Isi</span><strong>${surveyCoverageMeta.value.totalBelumSurvey}</strong></div><div class="kpi"><span>Coverage</span><strong>${formatPercent(surveyCoverageMeta.value.persentase)}</strong></div><div class="kpi"><span>Rating</span><strong>${Number(ulabBintang.value).toFixed(2)} / 5</strong></div></div>
    <h2>Daftar Pendaftaran (${ulab.value.length} data)</h2><table><thead><tr><th>No</th><th>No Pendaftaran</th><th>Unit</th><th>Penanggung Jawab</th><th>No. HP</th><th>Lokasi</th><th>Order</th><th>Progress</th><th>Rating</th><th>Survey</th><th>Tanggal</th></tr></thead><tbody>${rows}</tbody></table>
    <h2>Rata-rata Kepuasan per Atribut</h2><table><thead><tr><th>No</th><th>Dimensi</th><th>Atribut</th><th>Skor</th></tr></thead><tbody>${attributeRows}</tbody></table>
    <div class="footer">Dicetak ${escapeHtml(new Date().toLocaleString('id-ID'))}</div><script>window.onload=()=>setTimeout(()=>window.print(),350)<\/script>
  </body></html>`
}

async function exportPdf() {
  exportingPdf.value = true
  try {
    const loaded = await loadCustomerAssessment()
    if (!loaded || !ulab.value.length) {
      H.alert('warning', 'Tidak ada data sesuai filter untuk diexport.')
      return
    }
    const printWindow = window.open('', '_blank')
    if (!printWindow) {
      H.alert('error', 'Popup diblokir browser. Izinkan popup untuk export PDF.')
      return
    }
    printWindow.document.open()
    printWindow.document.write(buildPdfHtml())
    printWindow.document.close()
  } finally {
    exportingPdf.value = false
  }
}

async function exportExcel() {
  exportingExcel.value = true
  try {
    const loaded = await loadCustomerAssessment()
    if (!loaded || !ulab.value.length) {
      H.alert('warning', 'Tidak ada data sesuai filter untuk diexport.')
      return
    }
    exportCustomerAssessmentExcel({
      filters: getExportFilterLabels(),
      summary: { ...surveyCoverageMeta.value, averageRating: Number(ulabBintang.value) },
      rows: ulab.value,
      attributes: atributList.value.map((attr, index) => ({
        no: attr.no,
        dimension: attr.dimensi,
        attribute: attr.atribut,
        score: Number(chartSeries.value[0]?.data?.[index] ?? 0),
      })),
      ratingDistribution: ratingDistribution.value,
      units: unitRatingData.value,
      monthly: monthlyTrendData.value,
    })
  } catch (error) {
    console.error(error)
    H.alert('error', 'Gagal export Excel penilaian pelanggan.')
  } finally {
    exportingExcel.value = false
  }
}

const getAnalisiAIPelanggan = async () => {
  try {
    isLoading.value = true
    const res = await useApi().get(`/mutu/get-analisis-ai-pelanggan?${buildFilterParams().toString()}`)
    if (res.analisisGemini) {
      try {
        const parsed = JSON.parse(res.analisisGemini)
        analisisGemini.value = parsed.parts?.[0]?.text || ''
      } catch (e) {
        analisisGemini.value = 'Gagal memproses analisis Gemini.'
      }
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data.' })
  } finally {
    isLoading.value = false
  }
}

loadCustomerAssessment()
getAnalisiAIPelanggan()
loadTree()

onMounted(() => {
  lastUpdate.value = new Date().toLocaleString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
})
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

.rating-caption {
  max-width: 430px;
  margin: 4px 0 0;
  color: #64748b;
  font-size: 0.78rem;
  line-height: 1.45;
}

.customer-location-tabs {
  display: inline-flex;
  gap: 5px;
  padding: 5px;
  border: 1px solid #dbe3ef;
  border-radius: 12px;
  background: #f8fafc;

  button {
    display: inline-flex;
    height: 40px;
    padding: 0 16px;
    align-items: center;
    gap: 7px;
    border: 1px solid transparent;
    border-radius: 9px;
    color: #64748b;
    background: transparent;
    font-family: inherit;
    font-size: 0.84rem;
    font-weight: 500;
    cursor: pointer;

    &:hover { color: #1d4ed8; background: #eff6ff; }

    &.is-active {
      border-color: #bfdbfe;
      color: #1d4ed8;
      background: #fff;
      box-shadow: 0 2px 7px rgba(37, 99, 235, 0.1);
    }
  }
}

.customer-filter-card {
  width: min(100%, 1900px);
  margin-top: 12px;
  padding: 18px 20px !important;
}

.filter-grid {
  display: grid;
  grid-template-columns: 150px 260px 260px 230px 230px minmax(320px, 420px);
  gap: 12px;
  align-items: end;
  justify-content: start;

  :deep(.p-dropdown),
  :deep(.p-calendar),
  :deep(.p-inputtext) {
    width: 100%;
  }
}

.filter-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  max-width: 1520px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;

  .button {
    min-height: 40px;
    font-weight: 500;
  }
}

.customer-kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 22px;
}

.customer-kpi {
  display: flex;
  min-width: 0;
  padding: 15px;
  align-items: center;
  gap: 12px;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  background: #f8fbff;

  > .iconify {
    width: 42px;
    height: 42px;
    padding: 10px;
    flex: 0 0 42px;
    border-radius: 10px;
    color: #2563eb;
    background: #dbeafe;
  }

  span,
  strong { display: block; }
  span { color: #64748b; font-size: 0.7rem; }
  strong { margin-top: 2px; color: #172554; font-size: 1.35rem; font-weight: 600; }

  &.is-filled {
    border-color: #a7f3d0;
    background: #f4fdf9;
    > .iconify { color: #047857; background: #d1fae5; }
    strong { color: #047857; }
  }

  &.is-pending {
    border-color: #fed7aa;
    background: #fffbf5;
    > .iconify { color: #c2410c; background: #ffedd5; }
    strong { color: #c2410c; }
  }

  &.is-coverage {
    border-color: #ddd6fe;
    background: #faf8ff;
    > .iconify { color: #7c3aed; background: #ede9fe; }
    strong { color: #6d28d9; }
  }
}

.table-heading,
.section-heading,
.insight-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.table-heading {
  margin-bottom: 12px;

  h3 { margin: 0; color: #172554; font-size: 1rem; font-weight: 600; }
  p { margin: 3px 0 0; color: #64748b; font-size: 0.74rem; }
  > span { padding: 5px 9px; border-radius: 99px; color: #1d4ed8; background: #eff6ff; font-size: 0.72rem; }
}

.pic-cell {
  strong,
  span { display: block; }
  strong { color: #1e293b; font-size: 0.82rem; font-weight: 600; }
  span { margin-top: 2px; color: #94a3b8; font-size: 0.68rem; }
}

.phone-value { color: #334155; font-family: monospace; font-size: 0.8rem; }
.location-pill,
.order-pill,
.survey-status {
  display: inline-flex;
  padding: 4px 8px;
  align-items: center;
  border: 1px solid #bfdbfe;
  border-radius: 99px;
  color: #1d4ed8;
  background: #eff6ff;
  font-size: 0.7rem;
  font-weight: 500;
}
.order-pill { border-color: #ddd6fe; color: #6d28d9; background: #f5f3ff; }
.survey-status.is-filled { border-color: #a7f3d0; color: #047857; background: #ecfdf5; }
.survey-status.is-empty { border-color: #fed7aa; color: #c2410c; background: #fff7ed; }

.order-progress {
  color: #047857;
  font-size: 0.76rem;
  font-weight: 500;
}
.order-progress-track {
  width: 100%;
  height: 6px;
  margin-top: 6px;
  overflow: hidden;
  border-radius: 99px;
  background: #e5e7eb;
  div { height: 100%; border-radius: inherit; background: #10b981; }
}

.customer-insights {
  margin-top: 28px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.section-heading {
  margin-bottom: 14px;
  span { color: #2563eb; font-size: 0.68rem; font-weight: 600; letter-spacing: 0.07em; text-transform: uppercase; }
  h3 { margin: 2px 0 0; color: #172554; font-size: 1.05rem; font-weight: 600; }
  p { max-width: 470px; margin: 0; color: #64748b; font-size: 0.73rem; text-align: right; }
}

.insight-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.insight-card {
  min-width: 0;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;

  &.is-wide { grid-column: 1 / -1; }
}

.insight-card-head {
  margin-bottom: 8px;
  span { color: #64748b; font-size: 0.64rem; text-transform: uppercase; letter-spacing: 0.06em; }
  h4 { margin: 2px 0 0; color: #1e293b; font-size: 0.9rem; font-weight: 600; }
}

.chart-empty {
  display: grid;
  min-height: 230px;
  place-items: center;
  color: #94a3b8;
  border: 1px dashed #cbd5e1;
  border-radius: 9px;
  background: #f8fafc;
  font-size: 0.8rem;
}

.coverage-copy { margin: 2px 0 0; color: #64748b; font-size: 0.75rem; text-align: center; }
.survey-detail-panel { background: #fff; }

@media (max-width: 1650px) {
  .filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
  .customer-location-tabs { display: flex; width: 100%; }
  .customer-location-tabs button { flex: 1; padding: 0 8px; justify-content: center; }
  .filter-grid,
  .customer-kpi-grid,
  .insight-grid { grid-template-columns: 1fr; }
  .insight-card.is-wide { grid-column: auto; }
  .section-heading { flex-direction: column; }
  .section-heading p { text-align: left; }
}

.is-dark {
  .customer-location-tabs { border-color: #334155; background: #111827; }
  .customer-location-tabs button {
    color: #94a3b8;
    &:hover { color: #bfdbfe; background: #172033; }
    &.is-active { border-color: #315685; color: #bfdbfe; background: #172033; box-shadow: none; }
  }
  .rating-caption,
  .table-heading p,
  .section-heading p,
  .insight-card-head span,
  .coverage-copy,
  .customer-kpi span { color: #94a3b8; }
  .filter-actions,
  .customer-insights { border-color: #334155; }
  .customer-kpi,
  .customer-kpi.is-filled,
  .customer-kpi.is-pending,
  .customer-kpi.is-coverage,
  .insight-card { border-color: #334155; background: #111827; }
  .customer-kpi strong,
  .table-heading h3,
  .section-heading h3,
  .insight-card-head h4,
  .pic-cell strong,
  .phone-value { color: #e2e8f0; }
  .pic-cell span { color: #94a3b8; }
  .chart-empty { border-color: #475569; color: #94a3b8; background: #172033; }
  .order-progress-track { background: #334155; }
  .survey-detail-panel { background: #172033; }
}

.stars-outer {
  position: relative;
  display: inline-block;

  &::before {
    content: '\f005\f005\f005\f005\f005';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    color: #ddd;
  }
}

.stars-inner {
  position: absolute;
  top: 0;
  left: 0;
  white-space: nowrap;
  overflow: hidden;

  &::before {
    content: '\f005\f005\f005\f005\f005';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    color: #ffc107;
  }
}

.is-navbar {
  .personal-dashboard {
    margin-top: 30px;
  }
}

.personal-dashboard-v2 {
  .dashboard-header {
    @include vuero-s-card;

    display: flex;
    align-items: center;
    padding: 30px;

    .user-meta {
      padding: 0 3rem;

      border-right: 1px solid var(--fade-grey-dark-3) h3 {
        max-width: 180px;
      }
    }

    .user-action {
      padding: 0 3rem;
    }

    .cta {
      position: relative;
      flex-grow: 2;
      max-width: 275px;
      margin-left: auto;
      background: var(--primary-light-8);
      padding: 20px;
      border-radius: var(--radius-large);
      box-shadow: var(--primary-box-shadow);

      .lnil,
      .lnir {
        position: absolute;
        bottom: 1rem;
        right: 1rem;
        font-size: 4rem;
        opacity: 0.3;
      }

      .link {
        font-family: var(--font-alt);
        display: block;
        font-weight: 500;
        margin-top: 0.5rem;

        &:hover,
        &:focus {
          color: var(--smoke-white);
          opacity: 0.6;
        }
      }
    }
  }

  .dashboard-card {
    @include vuero-s-card;

    padding: 30px;

    &:not(:last-child) {
      margin-bottom: 1.5rem;
    }

    .card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;

      h3 {
        font-family: var(--font-alt);
        font-size: 1rem;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 0;
      }
    }

    .active-projects,
    .active-team,
    .active-list {
      padding: 10px 0;
    }
  }
}

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
}

@media only screen and (max-width: 767px) {
  .personal-dashboard-v2 {
    .dashboard-header {
      flex-direction: column;
      text-align: center;

      .v-avatar {
        margin-bottom: 10px;
      }

      .user-meta {
        padding-top: 10px;
        padding-bottom: 10px;
        border: none;
      }

      .user-action {
        padding-bottom: 30px;
      }

      .cta {
        margin-left: 0;
      }
    }

    .active-projects {
      .media-flex-center {
        .flex-end {
          .avatar-stack {
            display: none;
          }
        }
      }
    }
  }
}
</style>
