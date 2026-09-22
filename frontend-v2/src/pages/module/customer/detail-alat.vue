<template>
  <ConfirmDialog />
  <div class="form-layout is-stacked">
    <div class="form-outer" style="margin-top: 15px; align-items: center;">
      <div class="form-body p-2">
        <div v-if="!isOwner" class="not-owner-container">
          <div class="not-owner-wrapper">
            <div class="not-owner-icon">
              <i class="fas fa-lock"></i>
            </div>
            <h3 class="not-owner-title">Akses Dibatasi</h3>
            <p class="not-owner-text">
              Alat dengan ID ini tidak terdaftar sebagai milik akun Anda.
              Silakan periksa kembali daftar alat milik Unit Anda
              atau hubungi admin ULAB jika merasa ini adalah kesalahan.
            </p>
            <div class="not-owner-meta">
              <span>ID Alat:</span>
              <code>{{ ID_ALAT }}</code>
            </div>
            <!-- <div class="not-owner-actions">
              <VButton color="primary" icon="feather:arrow-left" @click="goBack">
                Kembali ke Daftar Alat
              </VButton>
            </div> -->
          </div>
        </div>


        <div v-else class="business-dashboard hr-dashboard">
          <div class="column is-12">
            <div class="block-header">
              <div class="left">
                <div class="current-user" style="text-align: center;">
                  <h3>{{ alat.namaproduk }}</h3>
                  <VTag v-if="alat.versisertifikat > 1 || alat.versilaporanrepair > 1" :label="'Amandemen'"
                    :color="'info'" class="ml-2" />
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
                              <img :src="items.fotoproduk
                                ? '/produk/' + items.fotoproduk
                                : '/images/other/no_image.jpg'
                                " :data-fallback="'/images/other/no_image.jpg'" class="item-photo" />
                            </div>

                            <div class="column is-6-desktop is-8-tablet is-12-mobile">
                              <div class="box-text" style="width:70%">
                                <div class="meta-text">
                                  <p>
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
                                    <tr>
                                      <td>Tanggal Registrasi</td>
                                      <td>:</td>
                                      <td>{{ items.tglregistrasi }} </td>
                                    </tr>
                                    <tr v-if="items.tglkalibrasilembarkerja != null">
                                      <td>Tanggal Kalibrasi</td>
                                      <td>:</td>
                                      <td>{{ fmtNoMs(items.tglkalibrasilembarkerja) }}</td>
                                    </tr>
                                    <tr>
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
                                      <td>Durasi Penyelesaian Kalibrasi Sampai Terbit Sertifikat</td>
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
  <VModal :open="modalPenilaianPelanggan" title="Survey Kepuasan Pelanggan" size="small" actions="right"
    @close="modalPenilaianPelanggan = false" cancelLabel="Tutup">
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
import { ref, computed, reactive } from 'vue'
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
  title: 'Detail Registrasi Alat - ' + import.meta.env.VITE_PROJECT,
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
const isOwner = ref<boolean>(true)

const getQRCodeValue = computed(
  () => `https://ulabumro.id/module/customer/detail-alat?id_alat=${ID_ALAT}`
)

const fmtNoMs = (v: any) => {
  if (!v) return '-'
  const s = String(v).trim()

  return s.split('.')[0]
}

const isTrueFlag = (value: any) => {
  if (typeof value === 'boolean') return value
  if (typeof value === 'number') return value === 1

  return ['true', '1', 't', 'yes', 'y', 'on'].includes(
    String(value ?? '').trim().toLowerCase()
  )
}

const perluRatingAlat = (data: any) =>
  !isTrueFlag(data?.iskalibrasiinternal) && !isTrueFlag(data?.isireviewalat)

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

  if (perluRatingAlat(e)) {
    modalPenilaianPelanggan.value = true
    item.value.norec = e.norec
    item.value.norec_detail = e.norec_detail
    item.value.noorderalat = e.noorderalat
  } else {
    modalRiwayatAmandemen.value = true
  }
}

const cetakRiwayatAmandemen = async (item: any) => {
  const isRepair = (item?.jenisorder ?? '').toLowerCase() === 'repair'
  const bolehCetak = await pastikanSurveySebelumCetak(
    { norec: amandemenForm.norec, norec_detail: item.norec_detail },
    isRepair ? 'amendmentRepair' : 'amendmentCert',
    item.version
  )
  if (!bolehCetak) return

  if (isRepair) {
    H.printBlade(`registrasi/cetak-laporan-repair-pdf?norec_detail=${item.norec_detail}&version=${item.version}`)
  } else {
    H.printBlade(`registrasi/cetak-sertif-customer-pdf?norec_detail=${item.norec_detail}&version=${item.version}`)
  }
}

const goBack = () => {
  router.push({ name: 'module-customer-dashboard' }).catch(() => { })
}

const copyToClipboard = (text: string) => {
  if (!text) return
  navigator.clipboard.writeText(text).then(() => {
    useToaster().info(text, 'Copied to clipboard')
  })
}

const savePenilaianPelanggan = async () => {
  if (!item.value.bintang) {
    useToaster().error('Berikan Bintang terlebih dahulu')
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
  await useApi()
    .post(`/registrasi/save-penilaian-pelanggan`, json)
    .then(() => {
      isLoadDataOrder.value = false
      modalPenilaianPelanggan.value = false
      orderVerify()
    })
    .catch(() => {
      isLoadDataOrder.value = false
    })
}

const orderVerify = async () => {
  detailOrderLayanan.value = []
  isLoadDataOrder.value = true
  try {
    const response: any = await useApi().get(
      `/customer/history-alat?id_alat=${ID_ALAT}`
    )
    isOwner.value = response?.is_owner ?? true

    if (!isOwner.value) {
      isLoadDataOrder.value = false
      detailOrderLayanan.value = []
      useToaster().warning(
        'Alat ini tidak terdaftar sebagai milik akun Anda.',
        'Akses dibatasi'
      )
      return
    }
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
  // double protection
  if (!isOwner.value) return

  const response: any = await useApi().get(
    `/customer/get-alat?id_alat=${ID_ALAT}`
  )
  alat.value = response.data
}

type PrintJenis = 'lembarKerja' | 'vendor' | 'lapVerifikasi' | 'repair' | 'amendmentCert' | 'amendmentRepair'

const pastikanSurveySebelumCetak = async (e: any, jenis: PrintJenis, version?: any) => {
  if (isTrueFlag(e?.iskalibrasiinternal)) return true

  const norecPd = String(e?.norec ?? '').trim()
  if (!norecPd) {
    useToaster().error('Nomor pendaftaran alat tidak ditemukan.')
    return false
  }

  try {
    const response = await useApi().get(`/registrasi/get-survey-pelanggan?norec_pd=${norecPd}`)
    if (response.survey_applicable === false) return true

    const sudahDiisi = response.survey_sudah_diisi ?? !!(response.data && response.data.length)
    const hasCompleted = response.has_completed_tool ?? true
    if (hasCompleted && !sudahDiisi) {
      await router.push({
        name: 'module-customer-detail-registrasi',
        query: {
          norec_pd: norecPd,
          print_jenis: jenis,
          print_norec_detail: e.norec_detail,
          ...(version != null ? { print_version: version } : {}),
        },
      })
      return false
    }
    return true
  } catch (error) {
    useToaster().error('Status survey belum dapat diperiksa. Silakan coba kembali.')
    return false
  }
}

const siapkanRating = (e: any) => {
  modalPenilaianPelanggan.value = true
  item.value.norec = e.norec
  item.value.norec_detail = e.norec_detail
  item.value.noorderalat = e.noorderalat
}

const cetakSertifikatLembarKerja = async (e: any) => {
  if (!await pastikanSurveySebelumCetak(e, 'lembarKerja')) return
  if (perluRatingAlat(e)) {
    siapkanRating(e)
  } else {
    if (e.versisertifikat == null) {
      H.printBlade(
        `asman/cetak-sertifikat-lembar-kerja?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`
      )
    } else {
      H.printBlade(`registrasi/cetak-sertif-customer-pdf?norec_detail=${e.norec_detail}`)
    }
  }
}

const cetakSertiVendor = async (e: any) => {
  if (!await pastikanSurveySebelumCetak(e, 'vendor')) return
  if (perluRatingAlat(e)) {
    siapkanRating(e)
  } else {
    H.printBlade(`registrasi/cetak-sertifikat-vendor?norec=${e.norec_detail}`)
  }
}

const cetakLaporanRepair = async (e: any) => {
  if (!await pastikanSurveySebelumCetak(e, 'repair')) return
  if (perluRatingAlat(e)) {
    siapkanRating(e)
  } else {
    if (e.versilaporanrepair == null) {
      H.printBlade(
        `asman/cetak-laporan-repair?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`
      )
    } else {
      H.printBlade(`registrasi/cetak-laporan-repair-pdf?norec_detail=${e.norec_detail}`)
    }
  }
}

const cetakLaporanVerfikasi = async (e: any) => {
  if (!await pastikanSurveySebelumCetak(e, 'lapVerifikasi')) return
  if (perluRatingAlat(e)) {
    siapkanRating(e)
  } else {
    H.printBlade(
      `asman/cetak-laporan-verifikasi?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`
    )
  }
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
