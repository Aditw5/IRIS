<template>
  <ConfirmDialog />
  <div class="form-layout is-stacked">
    <div class="form-outer" style="margin-top: 15px">
      <div class="form-body p-2">
        <div class="business-dashboard hr-dashboard">
          <div class="column is-12">
            <div class="block-header">
              <div class="left">
                <div class="current-user" style="text-align: center;">
                  <h3>{{ mitra.namaperusahaan }}</h3>
                </div>
              </div>
              <div class="center">
                <div class="columns">
                  <div class="column">
                    <h4 class="block-heading">Tanggal Registrasi</h4>
                    <p class="block-text">{{ mitra.tglregistrasi }}</p>
                    <h4 class="block-heading">No Pendaftaran</h4>
                    <p class="block-text">{{ mitra.nopendaftaran }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="column is-12" v-if="isLoadDataOrder">
            <VCard>
              <VPlaceloadWrap v-for="data in 25" :key="data">
                <VPlaceload class="mx-2 mb-3" />
                <VPlaceload class="mx-2" />
              </VPlaceloadWrap>
            </VCard>
          </div>

          <div class="column is-12" v-else>
            <VCard>
              <VButton v-if="surveyBerlaku && !isKalibrasiInternal && hasCompletedTool && !surveySudahDiisi"
                @click="isiSurvey()" type="button"
                icon="feather:smile" class="mr-3" color="success" raised>
                Isi Survey Pelanggan
              </VButton>

              <div class="column is-12">
                <div class="timeline-wrapper">
                  <div class="timeline-wrapper-inner">
                    <div class="timeline-container">
                      <div class="timeline-item is-unread" v-for="(items, index) in detailOrderLayanan"
                        :key="items.norec">
                        <div class="content-wrap is-grey">
                          <div class="content-box columns is-variable is-3 is-multiline is-mobile">
                            <div class="column is-3-desktop is-4-tablet is-12-mobile">
                              <img class="item-photo" :src="items.fotoproduk
                                ? '/produk/' + items.fotoproduk
                                : '/images/other/no_image.jpg'
                                " :data-fallback="'/images/other/no_image.jpg'" />
                            </div>

                            <div class="column is-6-desktop is-8-tablet is-12-mobile">
                              <div class="box-text">
                                <div class="meta-text">
                                  <p class="title-line">
                                    <span>
                                      <b>{{ items.namaproduk ?? '-' }}</b>
                                      <VTag v-if="items.versisertifikat > 1 || items.versilaporanrepair > 1"
                                        :label="'Amandemen'" :color="'info'" class="ml-2" />
                                    </span>
                                    -
                                    <span>(Merk/Tipe : {{ items.namamerk ?? '-' }}/{{ items.namatipe ?? '-' }})</span>
                                    <span>(SN : {{ items.namaserialnumber ?? '-' }})</span>
                                  </p>

                                  <table class="tb-order">
                                    <tr>
                                      <td>Lingkup</td>
                                      <td>:</td>
                                      <td>{{ items.lingkupkalibrasi ?? '-' }}</td>
                                    </tr>
                                    <tr v-if="items.jenisorder == 'kalibrasi'">
                                      <td>Lokasi</td>
                                      <td>:</td>
                                      <td>{{ items.lokasi ?? '-' }}</td>
                                    </tr>
                                    <tr v-if="items.jenisorder == 'repair'">
                                      <td>Lokasi Repair</td>
                                      <td>:</td>
                                      <td>{{ items.lokasirepair ?? '-' }}</td>
                                    </tr>
                                    <tr v-if="items.jenisorder == 'kalibrasi' && items.isVendor == null">
                                      <td>Durasi</td>
                                      <td>:</td>
                                      <td>
                                        <VTag v-if="items.durasikalbrasi" color="warning" rounded>
                                          {{ items.durasikalbrasi }}
                                        </VTag>
                                      </td>
                                    </tr>
                                    <tr v-if="
                                      items.jenisorder == 'kalibrasi' &&
                                      items.tglsetujumanagerlembarkerja &&
                                      items.isVendor == null
                                    ">
                                      <td>Durasi Penyelesaian Kalibrasi</td>
                                      <td>:</td>
                                      <td>
                                        <VTag v-if="items.durasikalbrasi" color="info" rounded>
                                          {{ items.durasi_proses }}
                                        </VTag>
                                      </td>
                                    </tr>
                                    <tr v-if="items.bintangpenilaian != null">
                                      <td>Penilaian Pelanggan</td>
                                      <td>:</td>
                                      <td>
                                        <span v-for="n in 5" :key="'star-' + n">
                                          <i class="fa" :class="n <= items.bintangpenilaian
                                            ? 'fa-star has-text-warning'
                                            : 'fa-star has-text-grey-light'
                                            "></i>
                                        </span>
                                        <span class="has-text-grey ml-2">({{ items.bintangpenilaian }} dari 5)</span>
                                      </td>
                                    </tr>
                                  </table>
                                </div>
                              </div>
                            </div>

                            <div class="column is-3-desktop is-12-mobile">
                              <div class="actions is-flex is-justify-content-flex-end is-align-items-start">
                                <VIconButton v-if="
                                  items.jenisorder == 'kalibrasi' &&
                                  items.tglsetujumanagerlembarkerja != null &&
                                  items.isverifikasi == null &&
                                  items.isVendor == true
                                " v-tooltip.bottom.left="'Cetak Sertifikat'" icon="feather:printer"
                                  @click="cetakSertiVendor(items)" color="info" raised circle class="mr-2" />

                                <VIconButton v-if="
                                  items.jenisorder == 'kalibrasi' &&
                                  items.tglsetujumanagerlembarkerja != null &&
                                  items.isverifikasi == null &&
                                  items.isVendor == null
                                " v-tooltip.bottom.left="'Cetak Sertifikat'" icon="feather:printer"
                                  @click="cetakSertifikatLembarKerja(items)" color="info" raised circle class="mr-2" />

                                <VIconButton v-if="
                                  items.jenisorder == 'kalibrasi' &&
                                  items.tglsetujumanagerlembarkerja != null &&
                                  items.isverifikasi == true
                                " v-tooltip.bottom.left="'Cetak Laporan Verifikasi'" icon="feather:printer"
                                  @click="cetakLaporanVerfikasi(items)" color="success" raised circle class="mr-2" />

                                <VIconButton v-if="
                                  items.jenisorder == 'repair' &&
                                  items.tglsetujumanagerlaporanrepair != null &&
                                  items.isVendor == null
                                " v-tooltip.bottom.left="'Cetak Laporan Repair'" icon="feather:printer"
                                  @click="cetakLaporanRepair(items)" color="warning" raised circle class="mr-2" />

                                <VIconButton v-if="
                                  items.jenisorder == 'repair' &&
                                  items.tglsetujumanagerlaporanrepair != null &&
                                  items.isVendor == true
                                " v-tooltip.bottom.left="'Cetak Laporan Repair'" icon="feather:printer"
                                  @click="cetakSertiVendor(items)" color="warning" raised circle class="mr-2" />
                                <VIconButton v-tooltip.bottom.left="'Riwayat Amandemen'" icon="feather:repeat"
                                  v-if="items.versisertifikat > 1 || items.versilaporanrepair > 1"
                                  @click="riwayatAmandemen(items)" color="warning" raised circle class="mr-2">
                                </VIconButton>
                              </div>
                            </div>
                          </div>

                          <div class="progress-tracker-wrapper" v-if="items.jenisorder == 'kalibrasi'">
                            <div class="step" :class="{ active: true }" v-tooltip.top="'Menunggu Verifikasi'">
                              <i class="fas fa-clock"></i>
                            </div>
                            <div class="connector">
                              <ProgressBar v-if="
                                !items.verifregiscustomer &&
                                !items.tglverifasman &&
                                !items.tglsetujumanagerlembarkerja
                              " mode="indeterminate" style="height: 4px" />
                              <div v-else-if="items.verifregiscustomer" class="line-done"></div>
                              <div v-else class="line-default"></div>
                            </div>
                            <div class="step" :class="{ active: items.verifregiscustomer }"
                              v-tooltip.top="`Terverifikasi ${items.tanggalverifregiscustomer ?? ''}`">
                              <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="connector">
                              <ProgressBar v-if="
                                items.verifregiscustomer &&
                                !items.tglverifasman &&
                                !items.tglsetujumanagerlembarkerja
                              " mode="indeterminate" style="height: 4px" />
                              <div v-else-if="items.tglverifasman" class="line-done"></div>
                              <div v-else class="line-default"></div>
                            </div>
                            <div class="step" :class="{ active: items.tglverifasman }"
                              v-tooltip.top="'Sedang Dikerjakan'">
                              <i class="fas fa-tools"></i>
                            </div>
                            <div class="connector">
                              <ProgressBar v-if="items.tglverifasman && !items.tglsetujumanagerlembarkerja"
                                mode="indeterminate" style="height: 4px" />
                              <div v-else-if="items.tglsetujumanagerlembarkerja" class="line-done"></div>
                              <div v-else class="line-default"></div>
                            </div>
                            <div class="step" :class="{ active: items.tglsetujumanagerlembarkerja }"
                              v-tooltip.top="`Kalibrasi Selesai ${items.tglsetujumanagerlembarkerja ?? ''}`">
                              <i class="fas fa-home"></i>
                            </div>
                          </div>

                          <div class="progress-tracker-wrapper" v-if="items.jenisorder == 'repair'">
                            <div class="step" :class="{ active: true }" v-tooltip.top="'Menunggu Verifikasi'">
                              <i class="fas fa-clock"></i>
                            </div>
                            <div class="connector">
                              <ProgressBar v-if="
                                !items.verifregiscustomer &&
                                !items.tglverifasman &&
                                !items.tglsetujumanagerlaporanrepair
                              " mode="indeterminate" style="height: 4px" />
                              <div v-else-if="items.verifregiscustomer" class="line-done"></div>
                              <div v-else class="line-default"></div>
                            </div>
                            <div class="step" :class="{ active: items.verifregiscustomer }"
                              v-tooltip.top="`Terverifikasi ${items.tanggalverifregiscustomer ?? ''}`">
                              <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="connector">
                              <ProgressBar v-if="
                                items.verifregiscustomer &&
                                !items.tglverifasman &&
                                !items.tglsetujumanagerlaporanrepair
                              " mode="indeterminate" style="height: 4px" />
                              <div v-else-if="items.tglverifasman" class="line-done"></div>
                              <div v-else class="line-default"></div>
                            </div>
                            <div class="step" :class="{ active: items.tglverifasman }"
                              v-tooltip.top="'Sedang Dikerjakan'">
                              <i class="fas fa-tools"></i>
                            </div>
                            <div class="connector">
                              <ProgressBar v-if="items.tglverifasman && !items.tglsetujumanagerlaporanrepair"
                                mode="indeterminate" style="height: 4px" />
                              <div v-else-if="items.tglsetujumanagerlaporanrepair" class="line-done"></div>
                              <div v-else class="line-default"></div>
                            </div>
                            <div class="step" :class="{ active: items.tglsetujumanagerlaporanrepair }"
                              v-tooltip.top="`Repair Selesai ${items.tglsetujumanagerlaporanrepair ?? ''}`">
                              <i class="fas fa-home"></i>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </VCard>
          </div>
        </div>
      </div>
    </div>
  </div>
  <VModal :open="modalDetailOrder" title="" noclose size="big" actions="right" @close="modalDetailOrder = false"
    cancelLabel="Tutup">
    <template #content>
      <div class="business-dashboard hr-dashboard">
        <div class="columns is-multiline">
          <div class="column is-12 p-0">
            <div class="block-header">
              <div class="left column is-12 ">
                <div class="current-user">
                  <h3>{{ item.namaproduk }}</h3>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="column is-12">
        <Fieldset legend="Data Alat" :toggleable="true">
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
                <div class="timeline-item is-unread" v-for="(itemTl, indexTl) in timelineItems" :key="indexTl">
                  <div class="date">
                    <span>{{ H.formatDateIndo(itemTl.date) }}</span>
                  </div>
                  <div :class="'dot is-' + listColor[indexTl + 1]"></div>
                  <div class="content-wrap is-grey">
                    <div class="content-box">
                      <div class="status"></div>
                      <div class="box-text" style="width:70%">
                        <div class="meta-text">
                          <p>
                            <span>
                              {{ itemTl.type }} : {{ itemTl.nama }}
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
    <template #action></template>
  </VModal>
  <VModal :open="modalIsiSurvey" title="FMMO-163-14.4.3.b-86.1 Survey Kepuasan Pelanggan" size="big" actions="right"
    noclose hide-close @close="tutupSurvey" cancelLabel="Tutup">
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
                  <span>
                    Berapa lama Bapak/Ibu menjadi Mitra Unit Maintenance, Repair, Overhaul:
                  </span>
                </div>
                <div class="column is-6">
                  <div class="columns is-multiline">
                    <div class="column is-3">
                      <VField>
                        <VControl raw subcontrol>
                          <VCheckbox v-model="item.lamamenjadimitra" true-value="≤1 tahun" label="≤1 tahun" class="p-0"
                            color="primary" square />
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
                  <p style="color: black;">
                    <strong>(B1-1)</strong> Berdasarkan pengalaman Bapak/Ibu, seberapa puaskah
                    Bapak/Ibu terhadap pelayanan Unit Maintenance, Repair, Overhaul?
                  </p>
                </div>
                <div class="column is-12 mt-4-min">
                  <p style="color: black;">
                    <strong>(B1-2)</strong> Berdasarkan pengalaman Bapak/Ibu, bagaimana harapan
                    Bapak/Ibu terhadap pelayanan Unit Maintenance, Repair, Overhaul?
                  </p>
                </div>
                <div class="column is-12 mt-4-min">
                  <p style="color: blue; font-style: italic;">
                    [petunjuk pengisian: Lingkarilah skala yang sesuai dengan
                    kepuasan Bapak/Ibu terhadap setiap atribut ini!]
                  </p>
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
                        <th class="has-text-centered">
                          HARAPAN<br />
                          <small>(Skala 1 - 6)</small>
                        </th>
                        <th class="has-text-centered">
                          KEPUASAN<br />
                          <small>(Skala 1 - 6)</small>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(attr, index) in atributList" :key="attr.no">
                        <td>{{ attr.no }}</td>
                        <td>{{ attr.dimensi }}</td>
                        <td>{{ attr.atribut }}</td>
                        <td>
                          <div class="columns is-multiline is-gapless is-mobile">
                            <div class="column is-2-desktop is-full-mobile has-text-centered" v-for="skala in 6"
                              :key="`harapan-${index}-${skala}`">
                              <VCheckbox v-model="attr.harapan" :true-value="skala" :label="skala.toString()" square
                                color="info" class="p-0" />
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="columns is-multiline is-gapless is-mobile">
                            <div class="column is-2-desktop is-full-mobile has-text-centered" v-for="skala in 6"
                              :key="`kepuasan-${index}-${skala}`">
                              <VCheckbox v-model="attr.kepuasan" :true-value="skala" :label="skala.toString()" square
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
                  <p style="color: black;">
                    <strong>(B2.1)</strong> Secara umum, seberapa puas Bapak/Ibu dengan layanan
                    yang diberikan oleh Unit Maintenance, Repair, Overhaul?
                  </p>
                  <div class="columns is-mobile is-multiline is-centered mt-2">
                    <div v-for="n in 10" :key="'skala-b21-' + n" class="column is-narrow">
                      <VCheckbox v-model="item.b21" :true-value="n" :label="n.toString()" color="primary" square />
                    </div>
                  </div>
                </div>
                <div class="column is-12">
                  <p class="mb-2" style="color: black;">
                    <strong>(B2.2) DENGAN MENGACU PADA JAWABAN PERTANYAAN B1</strong><br />
                    Jika Bapak/Ibu merasa sangat puas terhadap jenis layanan Divisi/Bidang sebagaimana disebutkan di
                    atas, atau merasa puas/ sangat puas terhadap layanan Divisi/Bidang namun informasi jenis layanan
                    tersebut belum tercakup dalam instrument di atas (bagian B), mohon Bapak/Ibu berkenan memberikan
                    ulasan lebih detil tentang layanan tersebut (*sifat : optional)
                  </p>
                  <VField>
                    <VControl>
                      <VTextarea v-model="item.b22" placeholder="Tuliskan ulasan Anda di sini..." rows="5" />
                    </VControl>
                  </VField>
                </div>
                <div class="column is-12">
                  <p class="mb-2" style="color: black;">
                    <strong>(B2.3) INOVASI</strong><br />
                    Sebutkan inovasi/peningkatan layanan dari Unit Maintenance, Repair, Overhaul pada tahun 2023
                    yang diterima oleh Bapak/Ibu (Jika ada)
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
                  komentar secara rinci, serta masukan dan saran untuk peningkatan kinerja Unit/Divisi
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
            <img v-if="item.namaresponden" :src="'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' +
              (item.namaresponden.label ? item.namaresponden.label : item.namaresponden)
              " />
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
    <template #cancel></template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="saveSurveyKepuasan" :loading="isLoadDataOrder" raised>
        Simpan
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalPenilaianPelanggan" title="Rating Kepuasan Pelanggan" size="small" actions="right"
    @close="modalPenilaianPelanggan = false, clear()" cancelLabel="Tutup">
    <template #content>
      <div class="has-text-centered">
        <p class="mb-3 has-text-weight-bold is-size-6">
          Seberapa puas Anda dengan layanan kami?
        </p>
        <div class="is-flex is-justify-content-center mb-4">
          <span v-for="bintang in 5" :key="'bintang-' + bintang" class="icon is-large" @click="item.bintang = bintang"
            style="cursor: pointer;">
            <i :class="[
              'fas',
              'fa-star',
              'fa-2x',
              item.bintang >= bintang ? 'has-text-warning' : 'has-text-grey-light'
            ]"></i>
          </span>
        </div>

        <VField label="Ulasan Anda (opsional)">
          <VTextarea v-model="item.ulasan" placeholder="Tuliskan ulasan Anda di sini..." :rows="4" />
        </VField>
      </div>
    </template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="savePenilaianPelanggan" :loading="isLoadDataOrder" raised>
        Simpan
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
import { ref, computed, onMounted, reactive } from 'vue'
import { useWindowScroll } from '@vueuse/core'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useRoute, useRouter } from 'vue-router'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import AutoComplete from 'primevue/autocomplete'
import Dialog from 'primevue/dialog'
import { useConfirm } from 'primevue/useconfirm'
import { useToaster } from '/@src/composable/toaster'
import ConfirmDialog from 'primevue/confirmdialog'
import { useThemeColors } from '/@src/composable/useThemeColors'
import ProgressBar from 'primevue/progressbar'
import Fieldset from 'primevue/fieldset'

useHead({
  title: 'Detail Registrasi Produk - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const route = useRoute()
const router = useRouter()
const NOREC_PD = route.query.norec_pd as string

const themeColors = useThemeColors()

const mitra: any = ref({})
const modalIsiSurvey = ref(false)
const timelineItems = ref([])
const detailOrderLayanan: any = ref([])
const isLoadDataOrder = ref(false)
const isLoadDataDeatilOrder = ref(false)
const listColor: any = ref(Object.keys(themeColors))
const modalDetailOrder = ref(false)
const modalPenilaianPelanggan = ref(false)
const { y } = useWindowScroll()
const isStuck = computed(() => y.value > 30)
const item: any = ref({})
const surveySudahDiisi = ref(false)
const hasCompletedTool = ref(false)
const surveyBerlaku = ref(true)
const isTrueFlag = (value: unknown) => {
  if (value === true || value === 1) return true
  return ['1', 'true', 't', 'yes', 'y', 'on'].includes(String(value ?? '').toLowerCase())
}
const isKalibrasiInternal = computed(() =>
  detailOrderLayanan.value.some((detail: any) => isTrueFlag(detail.iskalibrasiinternal)),
)
type PrintJenis = 'lembarKerja' | 'vendor' | 'lapVerifikasi' | 'repair' | 'amendmentCert' | 'amendmentRepair'
const printContext = ref<null | {
  jenis: PrintJenis
  norec: string
  norec_detail: string
  versisertifikat?: any
  versilaporanrepair?: any
  version?: any
}>(null)
const surveyWajib = computed(
  () =>
    surveyBerlaku.value &&
    !isKalibrasiInternal.value &&
    hasCompletedTool.value &&
    !surveySudahDiisi.value,
)
const confirm = useConfirm()
const d_jenisKelamin = ref<any[]>([])
const d_pendidikan = ref<any[]>([])
interface AtributKepuasan {
  no: number
  dimensi: string
  atribut: string
  harapan: number | null
  kepuasan: number | null
}
const onChangeD1 = (val: number, e: Event) => {
  const checked = (e.target as HTMLInputElement).checked
  if (checked) {
    item.value.d1_skor = val
  } else if (Number(item.value.d1_skor) === val) {
    item.value.d1_skor = null
  }
}
const onChangeD2 = (val: number, e: Event) => {
  const checked = (e.target as HTMLInputElement).checked
  if (checked) {
    item.value.d2_skor = val
  } else if (Number(item.value.d2_skor) === val) {
    item.value.d2_skor = null
  }
}
const listRiwayatAmandemen = ref<any[]>([])
let modalRiwayatAmandemen: any = ref(false)
const isLoadingRiwayat = ref(false)
const amandemenForm = reactive({
  norec: '',
  namaalat: '',
  namatipe: '',
  namamerk: '',
  namaserialnumber: '',
  noorderalat: '',
  norec_detail: '',
})

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

const riwayatAmandemen = async (e: any) => {
  amandemenForm.norec = e?.norec ?? ''
  amandemenForm.namaalat = e?.namaproduk ?? '-'
  amandemenForm.namatipe = e?.namatipe ?? '-'
  amandemenForm.namamerk = e?.namamerk ?? '-'
  amandemenForm.namaserialnumber = e?.namaserialnumber ?? '-'
  amandemenForm.noorderalat = e?.noorderalat ?? '-'
  amandemenForm.norec_detail = e?.norec_detail ?? '-'

  try {
    isLoadingRiwayat.value = true
    const response = await useApi().get(`/customer/riwayat-amandemen?norec_detail=${e.norec_detail}`)
    listRiwayatAmandemen.value = response ?? []
  } catch (err) {
    listRiwayatAmandemen.value = []
    H.alert('error', 'Gagal mengambil riwayat amandemen')
  } finally {
    isLoadingRiwayat.value = false
  }
  if (!isKalibrasiInternal.value && !isTrueFlag(e.iskalibrasiinternal) && !e.isireviewalat) {
    modalPenilaianPelanggan.value = true
    item.value.norec = e.norec
    item.value.norec_detail = e.norec_detail
    item.value.noorderalat = e.noorderalat
  } else {
    modalRiwayatAmandemen.value = true
  }
}

const cetakRiwayatAmandemen = (item: any) => {
  handleCetak(
    {
      norec: amandemenForm.norec,
      norec_detail: item.norec_detail,
      version: item.version,
    },
    (item?.jenisorder ?? '').toLowerCase() === 'repair'
      ? 'amendmentRepair'
      : 'amendmentCert',
  )
}


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

const cekStatusSurvey = async (showError = false) => {
  try {
    const response = await useApi().get(
      `/registrasi/get-survey-pelanggan?norec_pd=${NOREC_PD}`,
    )
    surveyBerlaku.value = response.survey_applicable !== false && !isKalibrasiInternal.value
    surveySudahDiisi.value = !!(response.data && response.data.length && response.data[0])

    if (!surveyBerlaku.value) {
      hasCompletedTool.value = false
      return true
    }

    const completedFromDetail = detailOrderLayanan.value.some(
      (detail: any) =>
        detail.tglsetujumanagerlembarkerja != null ||
        detail.tglsetujumanagerlaporanrepair != null,
    )
    hasCompletedTool.value = response.has_completed_tool ?? completedFromDetail
    return true
  } catch (err) {
    if (showError) {
      useToaster().error('Status survey belum dapat diperiksa. Silakan coba kembali.')
    }
    return false
  }
}

const doPrintFromContext = () => {
  if (!printContext.value) return

  const { jenis, norec, norec_detail, versisertifikat, versilaporanrepair, version } =
    printContext.value
  printContext.value = null

  if (route.query.print_jenis) {
    const query = { ...route.query }
    delete query.print_jenis
    delete query.print_norec_detail
    delete query.print_version
    router.replace({ query }).catch(() => undefined)
  }

  if (jenis === 'lembarKerja') {
    if (versisertifikat == null) {
      H.printBlade(
        `asman/cetak-sertifikat-lembar-kerja?pdf=true&norec=${norec}&norec_detail=${norec_detail}`,
      )
    } else {
      H.printBlade(`registrasi/cetak-sertif-customer-pdf?norec_detail=${norec_detail}`)
    }
  } else if (jenis === 'vendor') {
    H.printBlade(`registrasi/cetak-sertifikat-vendor?norec=${norec_detail}`)
  } else if (jenis === 'lapVerifikasi') {
    H.printBlade(
      `asman/cetak-laporan-verifikasi?pdf=true&norec=${norec}&norec_detail=${norec_detail}`,
    )
  } else if (jenis === 'repair') {
    if (versilaporanrepair == null) {
      H.printBlade(
        `asman/cetak-laporan-repair?pdf=true&norec=${norec}&norec_detail=${norec_detail}`,
      )
    } else {
      H.printBlade(`registrasi/cetak-laporan-repair-pdf?norec_detail=${norec_detail}`)
    }
  } else if (jenis === 'amendmentRepair') {
    H.printBlade(
      `registrasi/cetak-laporan-repair-pdf?norec_detail=${norec_detail}&version=${version}`,
    )
  } else if (jenis === 'amendmentCert') {
    H.printBlade(
      `registrasi/cetak-sertif-customer-pdf?norec_detail=${norec_detail}&version=${version}`,
    )
  }
}

const lanjutkanCetak = () => {
  if (!printContext.value) return

  const detail = detailOrderLayanan.value.find(
    (row: any) => String(row.norec_detail) === String(printContext.value?.norec_detail),
  )

  if (!detail) {
    printContext.value = null
    useToaster().error('Data alat untuk dicetak tidak ditemukan pada pendaftaran ini.')
    return
  }

  if (isKalibrasiInternal.value || isTrueFlag(detail.iskalibrasiinternal)) {
    doPrintFromContext()
    return
  }

  if (!detail.isireviewalat) {
    modalPenilaianPelanggan.value = true
    item.value.norec = detail.norec
    item.value.norec_detail = detail.norec_detail
    item.value.noorderalat = detail.noorderalat
    return
  }

  doPrintFromContext()
}

const handleCetak = async (e: any, jenis: PrintJenis) => {
  printContext.value = {
    jenis,
    norec: e.norec,
    norec_detail: e.norec_detail,
    versisertifikat: e.versisertifikat,
    versilaporanrepair: e.versilaporanrepair,
    version: e.version,
  }

  if (isKalibrasiInternal.value || isTrueFlag(e.iskalibrasiinternal)) {
    doPrintFromContext()
    return
  }

  const statusChecked = await cekStatusSurvey(true)
  if (!statusChecked) {
    printContext.value = null
    return
  }

  if (surveyWajib.value) {
    await isiSurvey()
    return
  }

  lanjutkanCetak()
}

const isiSurvey = async () => {
  if (!surveyBerlaku.value || isKalibrasiInternal.value) {
    modalIsiSurvey.value = false
    return
  }

  modalIsiSurvey.value = true
  try {
    if (!mitra.value?.name) {
      await headerMitra()
    }
    item.value.namaresponden ||= mitra.value?.name ?? ''
    item.value.jabatanresponden ||= mitra.value?.jabatan ?? ''
    item.value.notelpon ||= mitra.value?.nowa ?? ''
    await getisiSurvey()
  } catch (e) {
    useToaster().error('Data survey belum dapat dimuat. Silakan coba kembali.')
  }
}

const tutupSurvey = () => {
  if (surveyWajib.value) {
    useToaster().warn(
      'Survey wajib disimpan terlebih dahulu karena minimal satu alat sudah selesai.',
    )
    return
  }
  modalIsiSurvey.value = false
}

const getisiSurvey = async () => {
  isLoadDataOrder.value = true
  try {
    const response = await useApi().get(
      `/registrasi/get-survey-pelanggan?norec_pd=${NOREC_PD}`,
    )
    const data = response.data[0]

    if (!data) return
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
    item.value.d1_skor = data.d1_skor != null ? Number(data.d1_skor) : null
    item.value.d1_penjelasan = data.d1_penjelasan
    item.value.d2_skor = data.d2_skor != null ? Number(data.d2_skor) : null
    item.value.d2_penjelasan = data.d2_penjelasan
  } finally {
    isLoadDataOrder.value = false
  }
}

const surveyValueText = (value: any) => {
  if (value && typeof value === 'object') {
    return String(value.label ?? value.value ?? '').trim()
  }

  return String(value ?? '').trim()
}

const validateSurveyKepuasan = () => {
  if (!surveyValueText(item.value.namaresponden)) return 'Nama lengkap wajib diisi.'
  if (!surveyValueText(item.value.unitdivisikerja)) return 'Unit / Divisi Kerja wajib diisi.'
  if (!surveyValueText(item.value.jabatanresponden)) return 'Jabatan / Posisi wajib diisi.'
  if (!surveyValueText(item.value.notelpon)) return 'No. Telp / HP wajib diisi.'

  const incompleteAttribute = atributList.value.some((a) => a.harapan == null || a.kepuasan == null)
  if (incompleteAttribute) return 'Harapan dan kepuasan pada seluruh Atribut Kepuasan wajib diisi.'

  return ''
}

const saveSurveyKepuasan = async () => {
  if (!surveyBerlaku.value || isKalibrasiInternal.value) {
    modalIsiSurvey.value = false
    useToaster().warn('Survey kepuasan tidak berlaku untuk order kalibrasi internal.')
    return
  }

  const validationMessage = validateSurveyKepuasan()
  if (validationMessage) {
    useToaster().warn(validationMessage)
    return
  }

  const json = {
    survey: {
      registrasifk: NOREC_PD,
      namaresponden: item.value.namaresponden,
      unitdivisikerja: item.value.unitdivisikerja,
      jabatanresponden: item.value.jabatanresponden,
      lamabekerja: item.value.lamabekerja,
      jeniskelamin: item.value.jeniskelamin?.value ?? null,
      usia: item.value.usia,
      notelpon: item.value.notelpon,
      pendidikan: item.value.pendidikan?.value ?? null,
      notependidikanlpon: item.value.pendidikan?.value ?? null,
      lamamenjadimitra: item.value.lamamenjadimitra,
      atributList: atributList.value.map((a) => ({
        no: a.no,
        harapan: a.harapan,
        kepuasan: a.kepuasan,
      })),
      b21: item.value.b21 ?? null,
      b22: item.value.b22 ?? '',
      b23: item.value.b23 ?? '',
      ketidakpuasanlayanan: item.value.ketidakpuasanlayanan ?? '',
      masukansaran: item.value.masukansaran ?? '',
      d1_skor: item.value.d1_skor ?? null,
      d1_penjelasan: item.value.d1_penjelasan ?? '',
      d2_skor: item.value.d2_skor ?? null,
      d2_penjelasan: item.value.d2_penjelasan ?? '',
      namapenanggungjawab:
        item.value.namapenanggungjawab?.label || item.value.namapenanggungjawab || '',
    },
  }

  isLoadDataOrder.value = true
  try {
    await useApi().post(`/registrasi/save-survey-pelanggan`, json)
    const statusChecked = await cekStatusSurvey()
    if (!statusChecked || !surveySudahDiisi.value) {
      throw new Error('Survey belum ditemukan setelah proses simpan.')
    }

    modalIsiSurvey.value = false
    await orderVerify()
    lanjutkanCetak()
  } catch (e: any) {
    useToaster().error(
      e?.response?.data?.message ||
        e?.response?.data?.response ||
        e?.message ||
        'Gagal menyimpan survey pelanggan',
    )
  } finally {
    isLoadDataOrder.value = false
  }
}

const savePenilaianPelanggan = async () => {
  if (!item.value.bintang) {
    useToaster().warn('Berikan Bintang terlebih dahulu')
    return
  }

  if (!item.value.ulasan) {
    useToaster().warn('Ulasan tidak boleh kosong')
    return
  }

  const json = {
    penilaian: {
      norec: item.value.norec,
      norec_detail: item.value.norec_detail,
      noorderalat: item.value.noorderalat,
      bintang: item.value.bintang,
      ulasan: item.value.ulasan,
    },
  }

  isLoadDataOrder.value = true
  try {
    await useApi().post(`/registrasi/save-penilaian-pelanggan`, json)
    isLoadDataOrder.value = false
    modalPenilaianPelanggan.value = false
    clear()
    await orderVerify()
    lanjutkanCetak()
  } catch (e: any) {
    isLoadDataOrder.value = false
  }
}

const clear = () => {
  item.value.bintang = ''
  item.value.ulasan = ''
}

const feetchJenisKelamin = async (filter: any) => {
  const response = await useApi().get(
    `general/dropdown/jeniskelamin_m?select=id,jeniskelamin&param_search=jeniskelamin&query=${filter.query}&limit=10`
  )
  d_jenisKelamin.value = response
}

const feetchPendidikan = async (filter: any) => {
  const response = await useApi().get(
    `general/dropdown/pendidikan_m?select=id,pendidikan&param_search=pendidikan&query=${filter.query}&limit=10`
  )
  d_pendidikan.value = response
}

const headerMitra = async () => {
  const response: any = await useApi().get(`/asman/header-mitra?norec_pd=${NOREC_PD}`)
  mitra.value = response.mitra[0]
}

const orderVerify = async () => {
  detailOrderLayanan.value = []
  isLoadDataOrder.value = true
  const response = await useApi().get(`/customer/detail-registrasi-alat?norec_pd=${NOREC_PD}`)
  response.detail.forEach((element: any, i: any) => {
    element.no = i + 1
  })
  detailOrderLayanan.value = response.detail
  if (isKalibrasiInternal.value) {
    surveyBerlaku.value = false
    hasCompletedTool.value = false
    modalIsiSurvey.value = false
    modalPenilaianPelanggan.value = false
  }
  isLoadDataOrder.value = false
}

const cetakSertifikatLembarKerja = (e: any) => {
  handleCetak(e, 'lembarKerja')
}

const cetakSertiVendor = (e: any) => {
  handleCetak(e, 'vendor')
}

const cetakLaporanVerfikasi = (e: any) => {
  handleCetak(e, 'lapVerifikasi')
}

const cetakLaporanRepair = (e: any) => {
  handleCetak(e, 'repair')
}

const siapkanCetakDariScan = () => {
  const jenis = route.query.print_jenis as PrintJenis | undefined
  const norecDetail = route.query.print_norec_detail as string | undefined
  if (!jenis || !norecDetail) return

  const allowed: PrintJenis[] = [
    'lembarKerja',
    'vendor',
    'lapVerifikasi',
    'repair',
    'amendmentCert',
    'amendmentRepair',
  ]
  const detail = detailOrderLayanan.value.find(
    (row: any) => String(row.norec_detail) === String(norecDetail),
  )
  if (!allowed.includes(jenis) || !detail) return

  printContext.value = {
    jenis,
    norec: detail.norec,
    norec_detail: detail.norec_detail,
    versisertifikat: detail.versisertifikat,
    versilaporanrepair: detail.versilaporanrepair,
    version: route.query.print_version,
  }
}

const initializePage = async () => {
  try {
    await Promise.all([headerMitra(), orderVerify()])
    siapkanCetakDariScan()

    if (isKalibrasiInternal.value) {
      lanjutkanCetak()
      return
    }

    const statusChecked = await cekStatusSurvey()
    if (!statusChecked) return

    if (surveyWajib.value) {
      await isiSurvey()
      return
    }

    lanjutkanCetak()
  } catch (e) {
    useToaster().error(
      'Detail pendaftaran belum dapat dimuat. Silakan muat ulang halaman.',
    )
  }
}

onMounted(() => {
  initializePage()
})
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/detail-registrasi-customer.scss';

.item-photo {
  width: 100%;
  height: auto;
  max-height: 220px;
  object-fit: contain;
  border-radius: 8px;
  background: #f3f3f3;
}

.title-line {
  word-break: break-word;
}

.actions {
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .tb-order tr {
    display: flex;
    flex-wrap: wrap;
  }

  .tb-order td {
    padding-right: 0.4rem;
  }

  .actions {
    justify-content: center;
    margin-top: 0.5rem;
  }
}

.progress-tracker-wrapper {
  display: flex;
  align-items: center;
  margin-top: 10px;
  gap: 6px;
}

@media (max-width: 768px) {
  .progress-tracker-wrapper {
    gap: 4px !important;
    white-space: normal !important;
    overflow: visible !important;
  }

  .progress-tracker-wrapper .step {
    width: 22px !important;
    height: 22px !important;
    font-size: 12px !important;
    border-radius: 50%;
  }

  .progress-tracker-wrapper .step i {
    font-size: 11px !important;
    line-height: 1;
  }

  .progress-tracker-wrapper .connector {
    flex: 1 1 auto !important;
    min-width: 0 !important;
  }

  .progress-tracker-wrapper .line-done,
  .progress-tracker-wrapper .line-default {
    height: 2px !important;
    border-radius: 2px;
  }

  .progress-tracker-wrapper .connector .p-progressbar {
    height: 2px !important;
    width: 100% !important;
  }
}

@media (max-width: 380px) {
  .progress-tracker-wrapper {
    gap: 3px !important;
  }

  .progress-tracker-wrapper .step {
    width: 20px !important;
    height: 20px !important;
    font-size: 11px !important;
  }

  .progress-tracker-wrapper .step i {
    font-size: 10px !important;
  }
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

.tabs-wrapper.is-slider .tabs,
.tabs-wrapper-alt.is-slider .tabs {
  position: relative;
  background: var(--fade-grey-light-2);
  border: 1px solid var(--fade-grey);
  max-width: 400px;
  height: 35px;
  border-bottom: none;
}

.tb-order .text-value {
  font-family: var(--font-alt);
  color: var(--dark-text);
  font-weight: 400;
  font-size: 12px;
}

.user-grid-v2 {
  .columns {
    margin-left: -0.5rem !important;
    margin-right: -0.5rem !important;
    margin-top: -0.5rem !important;
  }

  .column {
    padding: 0.5rem !important;
  }

  .grid-item {
    @include vuero-s-card;

    text-align: center;

    >.v-avatar {
      display: block;
      margin: 0 auto 4px;
    }

    h3 {
      font-family: var(--font-alt);
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--dark-text);
    }

    p {
      font-size: 0.85rem;
    }

    .people {
      display: flex;
      justify-content: center;
      padding: 8px 0 30px;

      .v-avatar {
        margin: 0 4px;
      }
    }

    .buttons {
      display: flex;
      justify-content: space-between;

      .button {
        width: calc(50% - 4px);
        color: var(--light-text);

        &:hover,
        &:focus {
          border-color: var(--fade-grey-dark-4);
          color: var(--info);
          box-shadow: var(--light-box-shadow);
        }
      }
    }
  }

  .grid-item-wrap {
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: var(--radius-large);
    transition: all 0.3s;

    .grid-item-head {
      background: #fafafa;
      border-radius: var(--radius-large) 6px 0 0;
      padding: 20px;

      .flex-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;

        .meta {
          span {
            display: flex;

            &:first-child {
              font-family: var(--font-alt);
              font-weight: 600;
              font-size: 0.85rem;
              color: white;
            }

            &:nth-child(2) {
              font-size: 0.8rem;
              color: white;
            }
          }
        }

        .status-icon {
          height: 28px;
          width: 28px;
          min-width: 28px;
          border-radius: var(--radius-rounded);
          border: 1px solid var(--fade-grey-dark-3);
          display: flex;
          align-items: center;
          justify-content: center;

          &.is-success {
            background: var(--success);
            border-color: var(--success);
            color: var(--white);
          }

          &.is-warning {
            background: var(--orange);
            border-color: var(--orange);
            color: var(--white);
          }

          &.is-danger {
            background: var(--danger);
            border-color: var(--danger);
            color: var(--white);
          }

          i {
            font-size: 8px;
          }
        }
      }

      .buttons {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0;

        .button,
        .v-button {
          width: calc(50% - 4px);
          color: var(--light-text);
          margin-bottom: 0;

          &:hover,
          &:focus {
            border-color: var(--fade-grey-dark-4);
            color: var(--info);
            box-shadow: var(--light-box-shadow);
          }
        }
      }
    }

    .grid-item {
      border-top-left-radius: 0;
      border-top-right-radius: 0;
      border: none;
    }
  }
}

.is-dark {
  .user-grid {
    .grid-item {
      @include vuero-card--dark;
    }
  }

  .user-grid-v2 {
    .grid-item-wrap {
      border-color: var(--dark-sidebar-light-12);

      .grid-item-head {
        background: var(--dark-sidebar-light-4);
      }
    }
  }
}

.user-grid-v2 .grid-item-wrap .grid-item-head.is-registrasi {
  background: var(--success) !important;
}

.user-grid-v2 .grid-item-wrap .grid-item-head {
  padding: 10px;
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

.checkbox-scale {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.checkbox-scale input {
  width: 16px;
  height: 16px;
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
