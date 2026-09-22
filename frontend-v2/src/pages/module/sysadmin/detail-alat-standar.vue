<template>
  <ConfirmDialog />
  <div class="form-layout is-stacked">
    <div class="form-outer" style="margin-top: 15px; align-items: center;">
      <div class="form-body p-2">
        <div class="business-dashboard hr-dashboard">
          <div class="column is-12">
            <div class="block-header">
              <div class="left">
                <div class="current-user" style="text-align: center;">
                  <h3>{{ alat.namaproduk }}</h3>
                </div>
              </div>
              <div class="center">
                <div class="columns">
                  <div class="column">
                    <h4 class="block-heading">Merk/Tipe</h4>
                    <p class="block-text">{{ alat.namamerk }}/{{ alat.namatipe }}</p>
                    <h4 class="block-heading">S/N</h4>
                    <p class="block-text">{{ alat.namaserialnumber }}</p>
                  </div>
                </div>
              </div>
              <div class="right">
                <div class="columns">
                  <div class="column">
                    <QrcodeVue :value="getQRCodeValue" :size="120" />
                    <VIconButton style="margin-left: 30px; margin-top: 30px;" v-tooltip.bottom.right="'Cetak Barcode'"
                      icon="feather:printer" @click="cetakQRALat()" color="warning" raised circle class="mr-2" />
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
              <div class="column is-12">
                <div class="timeline-wrapper">
                  <div class="timeline-wrapper-inner">
                    <div class="timeline-container">
                      <div class="timeline-item is-unread" v-for="(items, index) in detailOrderLayanan"
                        :key="items.norec">
                        <div class="content-wrap is-grey">
                          <div class="content-box columns is-variable is-3 is-multiline is-mobile">
                            <div class="column is-3-desktop is-4-tablet is-12-mobile">
                              <div v-if="items.is_external" class="external-certificate-visual">
                                <i class="iconify" data-icon="feather:file-text" aria-hidden="true"></i>
                                <span>Sertifikat PDF</span>
                              </div>
                              <img v-else :src="items.fotoproduk
                                ? '/produk/' + items.fotoproduk
                                : '/images/other/no_image.jpg'
                                " :data-fallback="'/images/other/no_image.jpg'" class="item-photo" />
                            </div>

                            <div class="column is-6-desktop is-8-tablet is-12-mobile">
                              <div class="box-text" style="width:70%">
                                <div class="meta-text">
                                  <p v-if="items.is_external" class="external-history-heading">
                                    <span class="external-history-badge">Rekalibrasi Eksternal</span>
                                    <span>{{ items.certificate_file_name }}</span>
                                  </p>
                                  <p v-else>
                                    <span>
                                      No Pendaftaran : {{ items.nopendaftaran ?? '-' }}
                                      <button @click="copyToClipboard(items.nopendaftaran)" v-if="items.nopendaftaran"
                                        class="copy-button">
                                        <i class="iconify" data-icon="mdi:content-copy"
                                          style="font-size: 14px; margin-left: 4px;"></i>
                                      </button>
                                    </span>
                                    -
                                    <span>
                                      (No Order : {{ items.noorderalat ?? '-' }})
                                      <button @click="copyToClipboard(items.noorderalat)" v-if="items.noorderalat"
                                        class="copy-button">
                                        <i class="iconify" data-icon="mdi:content-copy"
                                          style="font-size: 14px; margin-left: 4px;"></i>
                                      </button>
                                    </span>
                                  </p>

                                  <table class="tb-order">
                                    <tr v-if="items.is_external">
                                      <td>Cal Date</td>
                                      <td>:</td>
                                      <td>{{ H.formatDate(items.calldate, 'DD-MM-YYYY HH:mm') }}</td>
                                    </tr>
                                    <tr v-if="items.is_external">
                                      <td>Due Date</td>
                                      <td>:</td>
                                      <td>{{ H.formatDate(items.duedate, 'DD-MM-YYYY HH:mm') }}</td>
                                    </tr>
                                    <tr v-if="items.is_external">
                                      <td>Diunggah</td>
                                      <td>:</td>
                                      <td>{{ H.formatDate(items.uploaded_at, 'DD-MM-YYYY HH:mm') }}</td>
                                    </tr>
                                    <tr v-if="items.is_external">
                                      <td>Petugas</td>
                                      <td>:</td>
                                      <td>{{ items.uploaded_by || '-' }}</td>
                                    </tr>
                                    <tr v-if="!items.is_external">
                                      <td>Tanggal Registrasi</td>
                                      <td>:</td>
                                      <td>{{ items.tglregistrasi }} </td>
                                    </tr>
                                    <tr v-if="!items.is_external">
                                      <td>Lingkup</td>
                                      <td>:</td>
                                      <td>{{ items.lingkupkalibrasi ?? '-' }} </td>
                                    </tr>
                                    <tr v-if="items.jenisorder == 'kalibrasi'">
                                      <td>Lokasi</td>
                                      <td>:</td>
                                      <td>{{ items.lokasi ?? '-' }} </td>
                                    </tr>
                                    <tr v-if="items.jenisorder == 'repair'">
                                      <td>Lokasi Repair</td>
                                      <td>:</td>
                                      <td>{{ items.lokasirepair ?? '-' }} </td>
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
                                        <span class="has-text-grey ml-2">
                                          ({{ items.bintangpenilaian }} dari 5)
                                        </span>
                                      </td>
                                    </tr>
                                  </table>
                                </div>
                              </div>
                            </div>

                            <!-- Aksi cetak -->
                            <div class="column is-3-desktop is-12-mobile">
                              <div class="actions is-flex is-justify-content-flex-end is-align-items-start">
                                <VIconButton v-if="items.is_external" v-tooltip.bottom.left="'Lihat Sertifikat'"
                                  icon="feather:eye" @click="lihatSertifikatEksternal(items)" color="success" outlined
                                  circle class="mr-2" />
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
                              </div>
                            </div>
                          </div>

                          <!-- Progress tracker KALIBRASI -->
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

                          <!-- Progress tracker REPAIR -->
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
                      </div> <!-- end timeline-item -->
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
  <VModal title="" :open="modalDetailOrder" noclose size="big" actions="right" @close="modalDetailOrder = false"
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
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
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
import QrcodeVue from 'qrcode.vue'

useHead({
  title: 'Detail Rekalibrasi Alat Standar- ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const route = useRoute()
const router = useRouter()
const themeColors = useThemeColors()
const { y } = useWindowScroll()
const isStuck = computed(() => y.value > 30)

const ID_ALAT = route.query.id_alat as string

const alat: any = ref({})
const timelineItems = ref<any[]>([])
const detailOrderLayanan: any = ref([])
const isLoadDataOrder = ref(false)
const isLoadDataDeatilOrder = ref(false)
const listColor: any = ref(Object.keys(themeColors))
const modalDetailOrder = ref(false)
const modalPenilaianPelanggan = ref(false)
const item: any = ref({})

const getQRCodeValue = computed(
  () => `https://ulabumro.id/module/customer/detail-alat?id_alat=${ID_ALAT}`
)

const copyToClipboard = (text: string) => {
  if (!text) return
  navigator.clipboard.writeText(text).then(() => {
    useToaster().info(text, 'Copied to clipboard')
  })
}

const orderVerify = async () => {
  detailOrderLayanan.value = []
  isLoadDataOrder.value = true
  try {
    const response: any = await useApi().get(
      `sysadmin/history-alat?id_alat=${ID_ALAT}`
    )

    response.detail.forEach((element: any, i: number) => {
      element.no = i + 1
    })
    detailOrderLayanan.value = response.detail
    await fetchAlat()
  } catch (e) {
    // console.error(e)
    // useToaster().error('Gagal memuat data riwayat alat.')
  } finally {
    isLoadDataOrder.value = false
  }
}

const fetchAlat = async () => {

  const response: any = await useApi().get(
    `sysadmin/get-alat?id_alat=${ID_ALAT}`
  )
  alat.value = response.data
}

const cetakSertifikatLembarKerja = (e: any) => {
  H.printBlade(
    `asman/cetak-sertifikat-lembar-kerja?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`
  )
}

const cetakSertiVendor = (e: any) => {
  H.printBlade(`registrasi/cetak-sertifikat-vendor?norec=${e.norec_detail}`)
}

const lihatSertifikatEksternal = (e: any) => {
  H.printBlade(`sysadmin/sertifikat-standar?id=${e.certificate_history_id}`)
}

const cetakLaporanRepair = (e: any) => {
  H.printBlade(
    `asman/cetak-laporan-repair?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`
  )
}

const cetakLaporanVerfikasi = (e: any) => {
  H.printBlade(
    `asman/cetak-laporan-verifikasi?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`
  )
}

const cetakQRALat = () => {
  H.printBlade(`customer/cetak-qr-alat?pdf=true&idalat=${ID_ALAT}`)
}

// INIT
orderVerify()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/detail-registrasi-customer.scss';

/* === WARNING BUKAN ALAT MILIK USER === */
.not-owner-wrapper {
  @include vuero-s-card;
  border: 1px dashed #fb923c;
  background: #fff7ed;
  padding: 24px 20px;
  text-align: center;
  max-width: 640px;
  margin: 0 auto 20px auto;
}

.not-owner-icon {
  width: 54px;
  height: 54px;
  border-radius: 999px;
  margin: 0 auto 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f97316;
  color: #fff;
  box-shadow: 0 8px 18px rgba(249, 115, 22, 0.35);

  i {
    font-size: 24px;
  }
}

.not-owner-title {
  font-family: var(--font-alt);
  font-size: 1.15rem;
  font-weight: 700;
  color: #9a3412;
  margin-bottom: 6px;
}

.not-owner-text {
  font-size: 0.9rem;
  color: #7c2d12;
  margin-bottom: 10px;
}

.not-owner-meta {
  font-size: 0.8rem;
  color: #9a3412;
  margin-bottom: 16px;
  display: flex;
  justify-content: center;
  gap: 6px;
  align-items: center;

  code {
    background: #fed7aa;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #7c2d12;
  }
}

.not-owner-actions {
  display: flex;
  justify-content: center;
}

/* tombol copy */
.copy-button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  vertical-align: middle;
  color: #555;

  &:hover {
    color: #000;
  }
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

/* Foto rapi & tidak melebar */
.item-photo {
  width: 100%;
  height: auto;
  max-height: 220px;
  object-fit: contain;
  border-radius: 8px;
  background: #f3f3f3;
}

.external-certificate-visual {
  display: flex;
  min-height: 150px;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 0.55rem;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  color: #15803d;
  background: #f0fdf4;

  i {
    font-size: 2.5rem;
  }

  span {
    font-size: 0.8rem;
    font-weight: 400;
  }
}

.external-history-heading {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.55rem;
  margin-bottom: 0.45rem;
}

.external-history-badge {
  display: inline-flex;
  padding: 0.25rem 0.55rem;
  border: 1px solid #86efac;
  border-radius: 999px;
  color: #166534;
  background: #dcfce7;
  font-size: 0.72rem;
  font-weight: 400;
}

.is-dark {
  .external-certificate-visual {
    border-color: #285b42;
    color: #86efac;
    background: #152d24;
  }

  .external-history-badge {
    border-color: #285b42;
    color: #bbf7d0;
    background: #173b2b;
  }
}

.actions {
  gap: 0.5rem;
}

/* Progress tracker, dsb (dari kode lama, dipertahankan) */
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

/* RESPONSIVE: progress tracker */
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

  .progress-tracker-wrapper {
    gap: 4px !important;
    white-space: normal !important;
    overflow: visible !important;
  }

  .progress-tracker-wrapper .step {
    width: 22px !important;
    height: 22px !important;
    font-size: 12px !important;
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

/* === CONTAINER: bikin card benar-benar di tengah layar === */
.not-owner-container {
  min-height: calc(100vh - 140px); // sesuaikan kalau header/footer beda
  display: flex;
  justify-content: center;
  align-items: center;
}

/* === CARD WARNING: BUKAN ALAT MILIK USER === */
.not-owner-wrapper {
  @include vuero-s-card;
  border: 1px dashed #fb923c;
  background: #fff7ed;
  padding: 24px 20px;
  text-align: center;
  max-width: 640px;
  width: 100%;
  margin: 0; // sudah di-center oleh .not-owner-container
}

.not-owner-icon {
  width: 54px;
  height: 54px;
  border-radius: 999px;
  margin: 0 auto 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f97316;
  color: #fff;
  box-shadow: 0 8px 18px rgba(249, 115, 22, 0.35);

  i {
    font-size: 24px;
  }
}

.not-owner-title {
  font-family: var(--font-alt);
  font-size: 1.15rem;
  font-weight: 700;
  color: #9a3412;
  margin-bottom: 6px;
}

.not-owner-text {
  font-size: 0.9rem;
  color: #7c2d12;
  margin-bottom: 10px;
}

.not-owner-meta {
  font-size: 0.8rem;
  color: #9a3412;
  margin-bottom: 16px;
  display: flex;
  justify-content: center;
  gap: 6px;
  align-items: center;

  code {
    background: #fed7aa;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #7c2d12;
  }
}

.not-owner-actions {
  display: flex;
  justify-content: center;
}
</style>
