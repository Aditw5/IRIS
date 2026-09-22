<template>
  <div class="columns is-multiline ">
    <div class="column is-12">
      <div class="form-layout is-stacked p-0">
        <RegistrationReviewHeader context-label="Pengkajian Ulang Repair" :company-name="mitra.namaperusahaan"
          :registration-number="mitra.nopendaftaran" :registration-date="mitra.tglregistrasi"
          :total="kajiStats.total" :reviewed="kajiStats.reviewed" :pending="kajiStats.pending" />

        <div class="personal-dashboard personal-dashboard-v2">
          <div class="columns is-multiline">
            <div class="column is-12" style="padding: 18px;">
              <div class="dashboard-card has-margin-bottom">
                <div class="card-head">
                  <h3 class="dark-inverted"> PENGKAJIAN ULANG REPAIR </h3>
                </div>
                <div class="active-projects">
                  <div class="columns is-multiline">
                    <div class="column is-4">
                    </div>
                    <div class="column is-2">
                    </div>
                    <div class="column is-6">
                      <div class="columns is-multiline is-pulled-right">
                        <div :class="['column', canSaveKaji ? 'is-6' : 'is-12']">
                          <VButton icon="feather:plus-circle" raised bold class="w-100" @click="openInputAlat"
                            :loading="isLoadingBill" color="info">
                            Input Alat
                          </VButton>
                        </div>
                        <div v-if="canSaveKaji" class="column is-6">
                          <VButton icon="feather:save" raised bold class="w-100" @click="simpanKaji(item)"
                            :loading="isLoadingBill" color="primary">
                            Simpan Kaji
                          </VButton>
                        </div>
                      </div>
                    </div>

                    <div class="column is-12">
                      <div class="column is-12">
                        <table class="tb-custom mt-3">
                          <thead>
                            <tr>
                              <th width="10%" class="text-center">
                              </th>
                              <th width="25%">Alat</th>
                              <th width="20%">Merk/Tipe</th>
                              <th>S/N</th>
                              <th>Jumlah</th>
                              <th>OPSI</th>
                            </tr>
                          </thead>
                          <tbody v-if="isLoadingBill">
                            <tr>
                              <td colspan="5">
                                <div class="list-view list-view-v1 is-fullwidth">
                                  <div class="list-view-inner">
                                    <div v-for="key in 6" :key="key" class="list-view-item mt-2">
                                      <VPlaceloadWrap>
                                        <VPlaceloadAvatar size="medium" />
                                        <VPlaceloadText last-line-width="60%" class="mx-2" />
                                        <VPlaceload class="mx-2" disabled />
                                        <VPlaceload class="mx-2 h-hidden-tablet-p" />
                                        <VPlaceload class="mx-2 h-hidden-tablet-p" />
                                        <VPlaceload class="mx-2" />
                                      </VPlaceloadWrap>
                                    </div>
                                  </div>
                                </div>
                              </td>
                            </tr>
                          </tbody>
                          <div style="max-height:500px;min-height: 300px; overflow-y: scroll;display: block;">
                            <tbody>
                              <tr>
                                <td colspan="5" class="koneng">
                                </td>
                              </tr>
                              <tr v-if="!isLoadingBill" v-for="(itemsDet, index) in dataSourcefiltered" :key="index"
                                style="border-bottom: 1px solid #ccc;">
                                <td width="5%">
                                </td>
                                <td width="30%">
                                  <div class="columns is-multiline">
                                    <div class="column is-12">
                                      <div class="title-ruangan">{{ itemsDet.nopendaftaran }}
                                        <VTag v-if="itemsDet.tanggalpenolakanregis != null" color="danger"
                                          label="Alat Ditolak" />
                                      </div>
                                      <div class="title-layan">{{ itemsDet.namaproduk }} </div>
                                      <div>
                                        <VTag color="info" :label="itemsDet.namaperusahaan" class="mr-2" />
                                        <VTag :color="isReviewed(itemsDet) ? 'primary' : 'danger'"
                                          :label="reviewStatusLabel(itemsDet)" />
                                      </div>
                                    </div>
                                  </div>
                                </td>
                                <td class="center">
                                  <div class="columns is-multiline">
                                    <div class="column is-12">
                                      <div class="title-layan">{{ itemsDet.namamerk }} - {{ itemsDet.namatipe }}</div>

                                    </div>
                                  </div>
                                </td>
                                <td class="center" style="text-align:center">
                                  <div class="columns is-multiline">
                                    <div class="column is-12">
                                      <div class="title-layan">{{ itemsDet.namaserialnumber }}</div>
                                    </div>
                                  </div>
                                </td>
                                <td class="center">
                                  <div class="columns is-multiline">
                                    <div class="column is-12">
                                      <div class="title-layan">
                                        <p class="title-layan"
                                          style="text-align: center; font-weight: bold; color: black;">1</p>
                                      </div>
                                    </div>
                                  </div>

                                </td>
                                <td class="center">
                                  <VIconButton v-if="canUseRegularReview(itemsDet)" color="info" light raised circle
                                    icon="feather:edit" class="mr-1" v-tooltip.bubble="'Kaji ulang'"
                                    @click="KajiUlang(itemsDet)" />
                                  <VIconButton v-if="canUseVendorReview(itemsDet)" color="warning" light raised circle
                                    icon="feather:send" class="mr-1" v-tooltip.bubble="'Repair Vendor'"
                                    @click="KalibrasiVendor(itemsDet)" />
                                  <VIconButton v-if="canRemoveReviewTool(itemsDet)" color="danger" light raised circle
                                    icon="feather:trash" class="mr-1"
                                    v-tooltip.bubble="'Keluarkan dari pendaftaran'" @click="batalRegis(itemsDet)" />
                                </td>
                              </tr>
                            </tbody>
                            <div class="search-results-wrapper"
                              v-if="dataSourcefiltered.length == 0 && isLoadingBill == false">
                              <div class="search-results-body ">
                                <div class="page-placeholder">
                                  <div class="placeholder-content">
                                    <img class="light-image" style=" max-width: 340px;"
                                      :src="H.assets().iconNotFound_rev" alt="" />
                                    <img class="dark-image" style=" max-width: 340px;"
                                      :src="H.assets().iconNotFound_rev" alt="" />
                                    <h3>{{ H.assets().notFound }}</h3>
                                    <p class="is-larger">
                                      {{ H.assets().notFoundSubtitle }}
                                    </p>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </table>
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

  <VModal :open="modalBatalRegis" title="Keluarkan Alat dari Pendaftaran" size="medium" actions="right" @close="modalBatalRegis = false"
    cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-12">
          <VField>
            <VLabel class="required-field">Tanggal Penolakan</VLabel>
            <VDatePicker v-model="item.tanggalpenolakan" mode="dateTime" style="width: 100%" trim-weeks
              :max-date="new Date()">
              <template #default="{ inputValue, inputEvents }">
                <VField>
                  <VControl icon="feather:calendar" fullwidth>
                    <VInput :value="inputValue" placeholder="Tanggal" v-on="inputEvents" disabled />
                  </VControl>
                </VField>
              </template>
            </VDatePicker>
          </VField>
        </div>
        <div class="column is-12">
          <VField>
            <VLabel class="required-field">Nama Alat</VLabel>
            <VControl icon="feather:tool">
              <VInput type="text" v-model="item.namaproduk" class="is-rounded_Z" disabled />
            </VControl>
          </VField>
        </div>
        <div class="column is-12">
          <span style="margin-bottom:1rem;font-weight: bold; font-size: 12px; font-family: var(--font-alt);">Alasan
            dikeluarkan
          </span>

          <VField>
            <VControl>
              <VTextarea class="textarea is-rounded" v-model="item.alasanpenolakan" rows="4"
                placeholder="Alasan alat dikeluarkan" autocomplete="off" autocapitalize="off" spellcheck="true" />
            </VControl>
          </VField>
        </div>

      </div>
    </template>
    <template #action>
      <VButton icon="feather:trash" color="danger" @click="saveBatalRegis" :loading="isLoadingBill" raised>Keluarkan Alat
      </VButton>
    </template>
  </VModal>

  <VModal :open="modalKajiUlang" title="Masukan Pengkajian Ulang" :noclose="false" size="big" actions="right"
    @close="modalKajiUlang = false, clear()">
    <template #content>
      <form class="modal-form">
        <div class="columns is-multiline">
          <div class="column is-4">
            <VField>
              <VLabel>Nama Alat</VLabel>
              <VControl icon="feather:box">
                <VInput type="text" v-model="input.namaproduk" placeholder="Nama Pelayanan" class="is-rounded"
                  disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Merk/Tipe</VLabel>
              <VControl icon="feather:box">
                <VInput type="text" v-model="input.merktipe" placeholder="Merk/Tipe" class="is-rounded" disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>SN</VLabel>
              <VControl icon="feather:box">
                <VInput type="text" v-model="input.namaserialnumber" placeholder="SN" class="is-rounded" disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField label="Tanggal Kaji Ulang">
              <VDatePicker v-model="input.tanggalKajian" mode="dateTime" style="width: 100%">
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VControl icon="feather:calendar" fullwidth>
                      <VInput :value="inputValue" placeholder="Tanggal" v-on="inputEvents" class="is-rounded" />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Lokasi Repair</VLabel>
              <VControl>
                <AutoComplete v-model="input.lokasirepairfk" :suggestions="d_lokasikalibrasi"
                  @complete="fetchLokasi($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                  class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                  placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Manager</VLabel>
              <VControl icon="feather:user">
                <VInput type="text" v-model="input.namamanager" placeholder="Nama Manager" class="is-rounded"
                  disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Asman</VLabel>
              <VControl icon="feather:user">
                <VInput type="text" v-model="input.namaasman" placeholder="Nama Asman" class="is-rounded" disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Penyelia Teknik Repair</VLabel>
              <VControl>
                <AutoComplete v-model="input.penyeliateknik" :suggestions="d_penyelia" @complete="fetchPenyelia($event)"
                  :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                  :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />

              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Pelaksana Teknik Repair</VLabel>
              <VControl>
                <AutoComplete v-model="input.pelaksana" :suggestions="d_pelaksana" @complete="fetchPelaksana($event)"
                  :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                  :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Status Surkes</VLabel>
              <VControl>
                <AutoComplete v-model="input.statussurkes" :suggestions="d_statussurkes"
                  @complete="fetchStatusSurkes($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                  class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                  placeholder="ketik untuk mencari..." :disabled="isStatusSurkesLocked" />
                <small v-if="isStatusSurkesLocked" class="help is-success">
                  Alat sudah masuk rencana alat surkes, status otomatis SURKES.
                </small>
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField>
              <VLabel>Keterangan</VLabel>
              <VControl>
                <VTextarea v-model="input.keterangan" rows="3" placeholder="Keterangan">
                </VTextarea>
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <div class="columns is-multiline">
              <div class="column is-12">
                <FileUpload :key="uploaderKeyKaji" ref="uploaderKaji" mode="advanced" name="fileMitra[]"
                  :multiple="true" accept="image/jpeg,image/jpg,image/png,image/webp" :maxFileSize="10000000"
                  :auto="false" :customUpload="true" :showUploadButton="false" :showCancelButton="false"
                  @select="onSelectFiles($event)" outlined
                  style="background-color: transparent; color: var(--danger); border: 1px solid;"
                  class="is-rounded w-100" :chooseLabel="'Pilih Foto (Bisa Banyak)'" />

              </div>

              <div class="column is-12">
                <div class="buttons">
                  <button type="button" @click="openCamera" class="button is-primary">
                    Buka Kamera
                  </button>

                  <button type="button" v-if="isCameraActive" @click="takePhoto" class="button is-success">
                    Ambil Foto
                  </button>

                  <button type="button" v-if="isCameraActive" @click="stopCamera" class="button is-warning">
                    Tutup Kamera
                  </button>

                  <button type="button" v-if="hasFiles" @click="clearFiles" class="button is-danger is-light">
                    Hapus Semua Foto
                  </button>
                </div>
                <div v-show="isCameraActive" class="mt-2">
                  <video ref="videoKaji" autoplay muted playsinline
                    style="width: 100%; max-width: 520px; border-radius: 10px; border: 1px solid #ddd;"></video>
                </div>
                <div v-if="hasFiles" class="mt-3">
                  <div class="columns is-multiline">
                    <div class="column is-12">
                      <p style="font-weight: 700; margin-bottom: 6px;">
                        Foto terpilih: {{ filePreviews.length }}
                      </p>
                    </div>

                    <div v-for="(p, idx) in filePreviews" :key="p.id" class="column is-3">
                      <div style="border:1px solid #e5e5e5; border-radius: 12px; padding: 8px;">
                        <img :src="p.url" alt="preview" @error="onPreviewImageError(idx)"
                          style="width:100%; height:140px; object-fit: cover; border-radius: 10px;" />
                        <div class="mt-2" style="font-size: 12px; word-break: break-all;">
                          {{ p.name }}
                        </div>
                        <div class="mt-2">
                          <button type="button" class="button is-small is-danger is-light w-100"
                            @click="removeFile(idx)">
                            Hapus
                          </button>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>

                <p class="mt-2" style="font-size: 12px; color: #888;">
                  Catatan: kamu bisa mix upload biasa + foto kamera. Untuk nambah foto kamera lagi, klik “Ambil Foto”
                  lagi.
                </p>
              </div>
            </div>
          </div>

        </div>
      </form>
    </template>
    <template #action>
      <VButton icon="feather:save" @click="simpan()" :loading="isLoadingPop" color="primary" raised>Simpan
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalKalibrasiVendor" title="Repair Vendor" :noclose="false" size="big" actions="right"
    @close="modalKalibrasiVendor = false, clear()">
    <template #content>
      <form class="modal-form">
        <div class="columns is-multiline">
          <div class="column is-4">
            <VField>
              <VLabel>Nama Alat</VLabel>
              <VControl icon="feather:box">
                <VInput type="text" v-model="input.namaproduk" placeholder="Nama Pelayanan" class="is-rounded"
                  disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Merk/Tipe</VLabel>
              <VControl icon="feather:box">
                <VInput type="text" v-model="input.merktipe" placeholder="Merk/Tipe" class="is-rounded" disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>SN</VLabel>
              <VControl icon="feather:box">
                <VInput type="text" v-model="input.namaserialnumber" placeholder="SN" class="is-rounded" disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField label="Tanggal Kaji Ulang">
              <VDatePicker v-model="input.tanggalKajian" mode="dateTime" style="width: 100%">
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VControl icon="feather:calendar" fullwidth>
                      <VInput :value="inputValue" placeholder="Tanggal" v-on="inputEvents" class="is-rounded" />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Lokasi Repair</VLabel>
              <VControl>
                <AutoComplete v-model="input.lokasirepairfk" :suggestions="d_lokasikalibrasi"
                  @complete="fetchLokasi($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                  class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                  placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Manager</VLabel>
              <VControl icon="feather:user">
                <VInput type="text" v-model="input.namamanager" placeholder="Nama Manager" class="is-rounded"
                  disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Asman</VLabel>
              <VControl icon="feather:user">
                <VInput type="text" v-model="input.namaasman" placeholder="Nama Asman" class="is-rounded" disabled />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField>
              <VLabel>Vendor Repair</VLabel>
              <VControl>
                <AutoComplete v-model="input.vendorrepair" :suggestions="d_vendor" @complete="fetchvendor($event)"
                  :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                  :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />

              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField>
              <VLabel>Keterangan</VLabel>
              <VControl>
                <VTextarea v-model="input.keterangan" rows="3" placeholder="Keterangan">
                </VTextarea>
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <div class="columns is-multiline">
              <div class="column is-12">
                <FileUpload :key="uploaderKeyVendor" ref="uploaderVendor" mode="advanced" name="fileMitra[]"
                  :multiple="true" accept="image/jpeg,image/jpg,image/png,image/webp" :maxFileSize="10000000"
                  :auto="false" :customUpload="true" :showUploadButton="false" :showCancelButton="false"
                  @select="onSelectFiles($event)" outlined
                  style="background-color: transparent; color: var(--danger); border: 1px solid;"
                  class="is-rounded w-100" :chooseLabel="'Pilih Foto (Bisa Banyak)'" />
              </div>

              <div class="column is-12">
                <div class="buttons">
                  <button type="button" @click="openCamera" class="button is-primary">
                    Buka Kamera
                  </button>

                  <button type="button" v-if="isCameraActive" @click="takePhoto" class="button is-success">
                    Ambil Foto
                  </button>

                  <button type="button" v-if="isCameraActive" @click="stopCamera" class="button is-warning">
                    Tutup Kamera
                  </button>

                  <button type="button" v-if="hasFiles" @click="clearFiles" class="button is-danger is-light">
                    Hapus Semua Foto
                  </button>
                </div>

                <div v-show="isCameraActive" class="mt-2">
                  <video ref="videoVendor" autoplay muted playsinline
                    style="width: 100%; max-width: 520px; border-radius: 10px; border: 1px solid #ddd;"></video>
                </div>

                <div v-if="hasFiles" class="mt-3">
                  <div class="columns is-multiline">
                    <div class="column is-12">
                      <p style="font-weight: 700; margin-bottom: 6px;">
                        Foto terpilih: {{ filePreviews.length }}
                      </p>
                    </div>

                    <div v-for="(p, idx) in filePreviews" :key="p.id" class="column is-3">
                      <div style="border:1px solid #e5e5e5; border-radius: 12px; padding: 8px;">
                        <img :src="p.url" alt="preview" @error="onPreviewImageError(idx)"
                          style="width:100%; height:140px; object-fit: cover; border-radius: 10px;" />
                        <div class="mt-2" style="font-size: 12px; word-break: break-all;">
                          {{ p.name }}
                        </div>
                        <div class="mt-2">
                          <button type="button" class="button is-small is-danger is-light w-100"
                            @click="removeFile(idx)">
                            Hapus
                          </button>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>

                <p class="mt-2" style="font-size: 12px; color: #888;">
                  Catatan: kamu bisa mix upload biasa + foto kamera. Untuk nambah foto kamera lagi, klik “Ambil Foto”
                  lagi.
                </p>
              </div>
            </div>
          </div>
        </div>
      </form>
    </template>
    <template #action>
      <VButton icon="feather:save" @click="simpanKalibrasiVendor()" :loading="isLoadingPop" color="primary" raised>
        Simpan
      </VButton>
    </template>
  </VModal>

  <AddRegistrationToolsModal :open="modalInputAlat" :registration-norec="NOREC_PD"
    :registration-number="mitra.nopendaftaran" :unit-name="mitra.namaperusahaan" @close="modalInputAlat = false"
    @saved="handleAlatAdded" />
</template>
<script setup lang="ts">
import { ref, reactive, computed, watch, onBeforeUnmount, nextTick } from 'vue'
import { useWindowScroll } from '@vueuse/core'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useRoute, useRouter } from 'vue-router'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import FileUpload from 'primevue/fileupload';
import AutoComplete from 'primevue/autocomplete';
import jsPDF from 'jspdf';
import AddRegistrationToolsModal from '/@src/components/partials/registrasi/AddRegistrationToolsModal.vue'
import RegistrationReviewHeader from '/@src/components/partials/registrasi/RegistrationReviewHeader.vue'

useHead({
  title: 'Kajian Ulang Repair- ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
let NOREC_PD = useRoute().query.norec_mitra_daftar as string
let TGLREGISTRASI = useRoute().query.tglregistrasi as string
let ID_MITRA = useRoute().query.nocmfk as string
const isLoadingBill: any = ref(false)
const isLoadingPop: any = ref(false)
const mitra: any = ref({})
const dataSource: any = ref([])
const router = useRouter()
const filters = ref('')
const filtersHide = ref('')
const isLoadHeader = ref(true)
const modalKajiUlang: any = ref(false)
const modalBatalRegis: any = ref(false)
const modalCatatan: any = ref(false)
const modalKalibrasiVendor: any = ref(false)
const modalInputAlat = ref(false)
const kajiUlangData = ref<any>(null);
const item: any = reactive({
  NOREC_PD: NOREC_PD != undefined ? NOREC_PD : '',
  TGLREGISTRASI: TGLREGISTRASI != undefined ? TGLREGISTRASI : '',
  registrasi: {},
  tglorder: new Date(),
  tanggalpenolakan: new Date(),
  tanggal: new Date(),
  produkCeklis: [],
  pegawaiOrder: useUserSession().getUser().id,
  periode: reactive({
    start: new Date(),
    end: new Date(),
  }),
  isExpertise: false,
})
const { y } = useWindowScroll()
const isStuck = computed(() => {
  return y.value > 30
})

const isDatabaseTrue = (value: any) => {
  return value === true || value === 1 || value === '1' || value === 'true' || value === 't'
}

const isReviewed = (alat: any) => isDatabaseTrue(alat?.iskaji)
const isVendorReview = (alat: any) => isDatabaseTrue(alat?.isVendor)
const canUseRegularReview = (alat: any) => !isReviewed(alat) || !isVendorReview(alat)
const canUseVendorReview = (alat: any) => !isReviewed(alat) || isVendorReview(alat)
const canRemoveReviewTool = (alat: any) => !isReviewed(alat)
const reviewStatusLabel = (alat: any) => {
  if (!isReviewed(alat)) return 'Belum Kaji'
  return isVendorReview(alat) ? 'Sudah Kaji Vendor' : 'Sudah Kaji'
}

const kajiStats = computed(() => {
  const total = dataSource.value.length
  const reviewed = dataSource.value.filter((alat: any) => isReviewed(alat)).length

  return {
    total,
    reviewed,
    pending: total - reviewed,
  }
})

const canSaveKaji = computed(() => {
  return dataSource.value.length > 0 && dataSource.value.every((alat: any) => isReviewed(alat))
})

const input: any = ref({})
const d_lokasikalibrasi = ref([])
const d_lingkup = ref([])
const d_penyelia = ref([])
const d_pelaksana = ref([])
const d_vendor = ref([])
const uploaderKaji = ref<any>(null)
const uploaderVendor = ref<any>(null)
const uploaderKeyKaji = ref(1)
const uploaderKeyVendor = ref(1)
const videoKaji = ref<HTMLVideoElement | null>(null)
const videoVendor = ref<HTMLVideoElement | null>(null)
const isCameraActive = ref(false)
let cameraStream: MediaStream | null = null
type PreviewItem = {
  id: string
  url: string
  name: string
  file?: File
  existingFile?: string
  isObjectUrl?: boolean
  candidates?: string[]
  candidateIndex?: number
}
const filesMitra = ref<File[]>([])
const filePreviews = ref<PreviewItem[]>([])
const existingMitraFiles = ref<string[]>([])
const hasFiles = computed(() => filePreviews.value.length > 0)
const makeId = () => `${Date.now()}_${Math.random().toString(16).slice(2)}`
const WEB_BASE_URL = String(import.meta.env.VITE_API_BASE_URL || '').replace(/\/service\/?$/, '').replace(/\/$/, '')

const buildExistingFileCandidates = (file: string) => {
  const value = String(file || '').trim()
  if (!value) return []

  if (value.startsWith('http://') || value.startsWith('https://')) {
    return [value]
  }

  const filename = value.split('/').pop()
  if (!filename) return []

  return [
    `${WEB_BASE_URL}/berkas-mitra/${filename}`,
    `${WEB_BASE_URL}/storage/berkas-mitra/${filename}`,
    `${WEB_BASE_URL}/storage/${filename}`,
  ]
}

const normalizeFotoFiles = (value: any): string[] => {
  if (!value) return []

  if (typeof value === 'string') {
    const trimmed = value.trim()
    if (!trimmed) return []

    if (trimmed.startsWith('[')) {
      try {
        return normalizeFotoFiles(JSON.parse(trimmed))
      } catch (e) {
        return []
      }
    }

    return [trimmed]
  }

  if (Array.isArray(value)) {
    return value.flatMap((item) => normalizeFotoFiles(item))
  }

  if (typeof value === 'object') {
    return normalizeFotoFiles(value.namafile ?? value.file ?? value.filename ?? '')
  }

  return []
}

const setExistingFilePreviews = (detail: any) => {
  const files = [
    ...normalizeFotoFiles(detail?.foto_files),
    ...normalizeFotoFiles(detail?.foto_detail),
    ...normalizeFotoFiles(detail?.namafile),
  ].filter(Boolean)

  const uniqueFiles = [...new Set(files)]
  existingMitraFiles.value = uniqueFiles

  for (const file of uniqueFiles) {
    const candidates = buildExistingFileCandidates(file)
    if (!candidates.length) continue

    filePreviews.value.push({
      id: `existing_${file}_${makeId()}`,
      url: candidates[0],
      name: file.split('/').pop() || file,
      existingFile: file,
      candidates,
      candidateIndex: 0,
    })
  }
}

const isAllowedType = (file: File) => {
  const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
  return allowed.includes(file.type)
}
const isAllowedSize = (file: File) => file.size <= 10000000
const d_statussurkes = ref([])
const isStatusSurkesLocked = ref(false)
const SURKES_OPTION = { value: 1, label: 'SURKES' }
const resetUploader = () => {
  clearFiles()
  stopCamera()
  try {
    if (modalKalibrasiVendor.value) {
      uploaderVendor.value?.clear?.()
      uploaderKeyVendor.value++
    } else {
      uploaderKaji.value?.clear?.()
      uploaderKeyKaji.value++
    }
  } catch (e) {
    if (modalKalibrasiVendor.value) uploaderKeyVendor.value++
    else uploaderKeyKaji.value++
  }
}

const addFiles = (files: File[]) => {
  for (const f of files) {
    if (!isAllowedSize(f)) {
      H.alert('error', 'Maksimal file size adalah 10 MB')
      continue
    }
    if (!isAllowedType(f)) {
      H.alert('error', 'File harus berupa JPG, JPEG, PNG, atau WEBP')
      continue
    }
    const exists = filesMitra.value.some(x => x.name === f.name && x.size === f.size && x.lastModified === f.lastModified)
    if (exists) continue

    filesMitra.value.push(f)
    filePreviews.value.push({
      id: makeId(),
      url: URL.createObjectURL(f),
      name: f.name,
      file: f,
      isObjectUrl: true,
    })
  }
}

const onSelectFiles = (event: any) => {
  const files: File[] = event?.files ?? []
  if (!files.length) return
  addFiles(files)
}

const removeFile = (index: number) => {
  const p = filePreviews.value[index]
  if (p?.isObjectUrl && p.url) URL.revokeObjectURL(p.url)
  if (p?.file) {
    const fileIndex = filesMitra.value.findIndex((file) => file === p.file)
    if (fileIndex > -1) {
      filesMitra.value.splice(fileIndex, 1)
    }
  }
  if (p?.existingFile) {
    existingMitraFiles.value = existingMitraFiles.value.filter((file) => file !== p.existingFile)
  }
  filePreviews.value.splice(index, 1)
}

const clearFiles = () => {
  for (const p of filePreviews.value) {
    if (p.isObjectUrl && p.url) URL.revokeObjectURL(p.url)
  }
  filePreviews.value = []
  filesMitra.value = []
  existingMitraFiles.value = []
}

const onPreviewImageError = (index: number) => {
  const preview = filePreviews.value[index]
  if (!preview?.candidates?.length) return

  const nextIndex = (preview.candidateIndex ?? 0) + 1
  if (nextIndex >= preview.candidates.length) return

  preview.candidateIndex = nextIndex
  preview.url = preview.candidates[nextIndex]
}

const getActiveVideoEl = () => {
  if (modalKalibrasiVendor.value) return videoVendor.value
  if (modalKajiUlang.value) return videoKaji.value
  return null
}

const isMobileDevice = () => /Mobi|Android/i.test(navigator.userAgent)

const openCamera = async () => {
  if (isMobileDevice()) {
    const videoInput = document.createElement('input')
    videoInput.type = 'file'
    videoInput.accept = 'image/*'
      ; (videoInput as any).capture = 'environment'

    videoInput.onchange = async (event) => {
      const file = (event.target as HTMLInputElement).files?.[0]
      if (!file) return

      const norec_detail = kajiUlangData.value?.norec_detail ?? 'foto'
      const nopendaftaran = kajiUlangData.value?.nopendaftaran ?? 'mitra'
      const ext = file.type === 'image/png' ? 'png' : (file.type === 'image/webp' ? 'webp' : 'jpeg')
      const imageFileName = `${norec_detail}_${nopendaftaran}_${Date.now()}.${ext}`
      const renamedFile = new File([file], imageFileName, { type: file.type })
      addFiles([renamedFile])
    }

    videoInput.click()
    return
  }
  try {
    if (!navigator.mediaDevices?.getUserMedia) {
      H.alert('error', 'Browser tidak mendukung kamera.')
      return
    }
    isCameraActive.value = true
    await nextTick()

    const v = getActiveVideoEl()
    if (!v) {
      H.alert('error', 'Video preview belum siap (ref null).')
      return
    }

    cameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
      audio: false,
    })

    v.srcObject = cameraStream
    v.muted = true
    v.playsInline = true

    await v.play()
  } catch (err: any) {
    console.error('Gagal mengakses kamera:', err)
    H.alert('error', `Gagal mengakses kamera: ${err?.name || ''} ${err?.message || ''}`)
    stopCamera()
  }
}

const takePhoto = async () => {
  const v = getActiveVideoEl()
  if (!v) return
  const w = v.videoWidth
  const h = v.videoHeight
  if (!w || !h) {
    H.alert('warning', 'Kamera belum siap. Tunggu 1-2 detik lalu klik lagi.')
    return
  }
  const canvas = document.createElement('canvas')
  canvas.width = w
  canvas.height = h
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  ctx.drawImage(v, 0, 0, w, h)
  const blob: Blob | null = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92))
  if (!blob) return
  const norec_detail = kajiUlangData.value?.norec_detail ?? 'foto'
  const nopendaftaran = kajiUlangData.value?.nopendaftaran ?? 'mitra'
  const imageFileName = `${norec_detail}_${nopendaftaran}_${Date.now()}.jpeg`
  const imageFile = new File([blob], imageFileName, { type: 'image/jpeg' })
  addFiles([imageFile])
}

const stopCamera = () => {
  try {
    if (cameraStream) {
      cameraStream.getTracks().forEach(t => t.stop())
      cameraStream = null
    }

    const v = getActiveVideoEl()
    if (v) {
      v.pause?.()
      v.srcObject = null
    }
  } finally {
    isCameraActive.value = false
  }
}


onBeforeUnmount(() => {
  stopCamera()
  clearFiles()
})

const simpan = async () => {
  if (!input.value.tanggalKajian) {
    H.alert('error', 'Tanggal Kajian harus di isi')
    return
  }
  if (!input.value.lokasirepairfk.value) {
    H.alert('error', 'Lokasi Kalibrasi harus di isi')
    return
  }
  if (!input.value.penyeliateknik.value) {
    H.alert('error', 'Penyelia Teknik harus di isi')
    return
  }
  if (!input.value.pelaksana.value) {
    H.alert('error', 'Pelaksana Teknik harus di isi')
    return
  }
  if (!input.value.statussurkes?.value) { H.alert('error', 'Status Surkes harus di isi'); return }
  if (!hasFiles.value) { H.alert('error', 'Minimal 1 foto harus di unggah / diambil dari kamera'); return }
  const formData = new FormData()
  filesMitra.value.forEach((f) => formData.append('fileMitra[]', f))
  existingMitraFiles.value.forEach((f) => formData.append('existing_files[]', f))
  formData.append('noregistrasifk', NOREC_PD)
  formData.append('norec', kajiUlangData.value.norec_detail)
  formData.append('keterangan', input.value.keterangan)
  formData.append('tanggalKajian', input.value.tanggalKajian)
  formData.append('lokasirepairfk', input.value.lokasirepairfk.value)
  formData.append('lingkupkalibrasi', 11);
  formData.append('penyeliateknik', input.value.penyeliateknik.value)
  formData.append('pelaksana', input.value.pelaksana.value)
  formData.append('statussurkes', input.value.statussurkes.value)
  formData.append('manager', input.value.namamanager)
  formData.append('namaasman', input.value.namaasman)
  isLoadingPop.value = true
  await useApi().post('/registrasi/save-kajian-ulang-item', formData).then((r) => {
    isLoadingPop.value = false
    fetchLayanan()
    resetUploader()
    modalKajiUlang.value = false
    clear()
  }).catch((error: any) => {
    isLoadingPop.value = false
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

const fetchvendor = async (filter: any) => {
  await useApi().get(
    `general/dropdown/vendor_m?select=id,namavendor&param_search=namavendor&query=${filter.query}&limit=10`
  ).then((response) => {
    d_vendor.value = response
  })
}

const fetchStatusSurkes = async (filter: any) => {
  if (isStatusSurkesLocked.value) {
    d_statussurkes.value = [SURKES_OPTION] as any
    return
  }

  await useApi().get(
    `general/dropdown/jenissurkes_m?select=id,jenissurkes&param_search=jenissurkes&query=${filter.query}&limit=10`
  ).then((response) => {
    d_statussurkes.value = response
  })
}

const applyStatusSurkesLock = (detail: any) => {
  const isMapped = !!detail.mapping_surkes_id
  isStatusSurkesLocked.value = isMapped

  if (isMapped) {
    input.value.statussurkes = { ...SURKES_OPTION }
    d_statussurkes.value = [SURKES_OPTION] as any
    return
  }

  input.value.statussurkes = {
    value: detail.jenissurkesfk ?? '',
    label: detail.jenissurkes ?? ''
  }
}

const simpanKalibrasiVendor = async () => {
  if (!input.value.tanggalKajian) {
    H.alert('error', 'Tanggal Kajian harus di isi')
    return
  }
  if (!input.value.lokasirepairfk.value) {
    H.alert('error', 'Lokasi Repair harus di isi')
    return
  }
  if (!input.value.vendorrepair.value) {
    H.alert('error', 'Vendor Repair harus di isi')
    return
  }
  if (!hasFiles.value) { H.alert('error', 'Minimal 1 foto harus di unggah / diambil dari kamera'); return }
  const formData = new FormData()
  filesMitra.value.forEach((f) => formData.append('fileMitra[]', f))
  existingMitraFiles.value.forEach((f) => formData.append('existing_files[]', f))
  formData.append('noregistrasifk', NOREC_PD)
  formData.append('norec', kajiUlangData.value.norec_detail)
  formData.append('keterangan', input.value.keterangan)
  formData.append('tanggalKajian', input.value.tanggalKajian)
  formData.append('lokasirepairfk', input.value.lokasirepairfk.value)
  formData.append('lingkupkalibrasi', 14);
  formData.append('vendorkalibrasi', input.value.vendorrepair.value)
  formData.append('manager', input.value.namamanager)
  formData.append('namaasman', input.value.namaasman)
  isLoadingPop.value = true
  await useApi().post('/registrasi/save-kajian-ulang-item-vendor', formData).then((r) => {
    isLoadingPop.value = false
    fetchLayanan()
    resetUploader()
    modalKalibrasiVendor.value = false
    clear()
  }).catch((error: any) => {
    isLoadingPop.value = false
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

const batalRegis = async (e: any) => {
  if (!canRemoveReviewTool(e)) {
    H.alert('warning', 'Alat yang sudah dikaji tidak dapat dikeluarkan dari pendaftaran.')
    return
  }

  console.log(e)
  item.namaproduk = e.namaproduk
  item.norecregis = e.norec_detail

  modalBatalRegis.value = true
}

const saveBatalRegis = async () => {
  if (!item.alasanpenolakan) { H.alert('warning', 'Alasan alat dikeluarkan harus diisi'); return }
  let json = {
    mitraregis: {
      'norecregis': item.norecregis,
      'tanggalpenolakanregis': item.tanggalpenolakan,
      'alasanpenolakanregis': item.alasanpenolakan,
    }
  }
  isLoadingBill.value = true
  await useApi()
    .post(`/registrasi/save-penolakan-alat`, json)
    .then((response: any) => {
      isLoadingBill.value = false
      // clear()
      modalBatalRegis.value = false
      fetchLayanan()
    })
    .catch((e: any) => {
      isLoadingBill.value = false
    })
}


const simpanKaji = async (e: any) => {
  if (!canSaveKaji.value) {
    H.alert('warning', 'Semua alat harus sudah dikaji. Kaji alat yang tersisa atau keluarkan dari pendaftaran.')
    return
  }

  console.log(e)
  let json = {
    'kajian': {
      'norec': item.NOREC_PD ? item.NOREC_PD : '',
      'nomitrafk': ID_MITRA,
      'namaperusahaan': mitra.value.namaperusahaan,
    }
  }
  isLoadingBill.value = true
  await useApi().post(`/registrasi/save-kaji-ulang`, json).then(async (response: any) => {
    isLoadingBill.value = false
    toDashboard()
  }).catch((e: any) => {
    isLoadingBill.value = false
    H.alert('error', (typeof e === 'string' ? e : e?.error) || 'Gagal menyimpan kaji ulang.')
  })
  isLoadingBill.value = false
}

const toDashboard = () => {
  router.push({
    name: 'module-dashboard-registrasi',
  })
}

const dataSourcefiltered: any = computed(() => {

  if (!filters.value && !filtersHide.value) {
    return dataSource.value
  }
  var filtered: any = [];
  for (let x = 0; x < dataSource.value.length; x++) {
    const element = dataSource.value[x];
    var filteredD = [];
    for (let z = 0; z < element.details.length; z++) {
      const element2 = element.details[z];
      if (filters.value) {
        if (element2.namaproduk.match(new RegExp(filters.value, 'i'))
        ) {
          filteredD.push(element2);
          filtered.push({
            details: filteredD
          })
          break
        }
      } else if (filtersHide.value) {
        if (element2.jenis.match(new RegExp(filtersHide.value, 'i'))
        ) {
          for (let xxx = 0; xxx < filtered.length; xxx++) {
            const elementxxx = filtered[xxx];
          }
          filteredD.push(element2);
          filtered.push({
            details: filteredD
          })
          // break
        }
      }

    }
  }
  return filtered;
})

const headerUnit = async (id: any, norec_pd: any, tglregistrasi: any) => {
  await useApi().get(`/registrasi/header-mitra?nocmfk=${id}&norec_pd=${norec_pd}&tglregistrasi=${tglregistrasi}`)
    .then((response: any) => {
      mitra.value = response.mitra
      fetchLayanan()
    })
  isLoadHeader.value = false
}

const fetchLayanan = async () => {
  isLoadingBill.value = true
  dataSource.value = []
  await useApi().get(
    `/registrasi/layana-mitra?norec_pd=${NOREC_PD}&tglregistrasi=${TGLREGISTRASI}`).then(async (response: any) => {
      dataSource.value = response.detail
      item.TOTAL = response.total
      item.length = response.length
      isLoadingBill.value = false

    })
}

const openInputAlat = () => {
  modalInputAlat.value = true
}

const handleAlatAdded = async () => {
  modalInputAlat.value = false
  await fetchLayanan()
}

const KajiUlang = async (e: any) => {
  if (!canUseRegularReview(e)) {
    H.alert('warning', 'Alat sudah dikaji melalui vendor dan tidak dapat dialihkan ke kaji biasa.')
    return
  }

  input.value.tanggalKajian = new Date();
  clearFiles()
  stopCamera()

  await useApi().get(
    `/registrasi/layana-mitra?norec_pd=${NOREC_PD}&norecdetail=${e.norec_detail}`
  ).then(async (response: any) => {
    const detail = response.detail?.[0] ?? {};

    input.value.lokasikalibrasi = {
      value: detail.lokasikalibrasifk ?? '',
      label: detail.lokasi ?? ''
    };

    input.value.lingkupkalibrasi = {
      value: detail.lingkupfk ?? '',
      label: detail.lingkupkalibrasi ?? ''
    };

    input.value.penyeliateknik = {
      value: detail.penyeliateknikfk ?? '',
      label: detail.penyeliateknik ?? ''
    };

    input.value.pelaksana = {
      value: detail.pelaksanateknikfk ?? '',
      label: detail.pelaksanateknik ?? ''
    };

    applyStatusSurkesLock(detail);

    input.value.keterangan = detail.keterangan ?? '';
    setExistingFilePreviews(detail)
  });

  input.value.namaproduk = e.namaproduk ?? '';
  input.value.namaserialnumber = e.namaserialnumber ?? ''
  input.value.merktipe = [e?.namamerk, e?.namatipe].filter(Boolean).join(' / ')
  kajiUlangData.value = e;
  modalKajiUlang.value = true;
  setAutoFill();
};

const KalibrasiVendor = async (e: any) => {
  if (!canUseVendorReview(e)) {
    H.alert('warning', 'Alat sudah dikaji biasa dan tidak dapat dialihkan ke repair vendor.')
    return
  }

  input.value.tanggalKajian = new Date();
  clearFiles()
  stopCamera()

  await useApi().get(
    `/registrasi/layana-mitra?norec_pd=${NOREC_PD}&norecdetail=${e.norec_detail}`
  ).then(async (response: any) => {
    const detail = response.detail?.[0] ?? {};

    input.value.lokasikalibrasi = {
      value: detail.lokasikalibrasifk ?? '',
      label: detail.lokasi ?? ''
    };

    input.value.lingkupkalibrasi = {
      value: detail.lingkupfk ?? '',
      label: detail.lingkupkalibrasi ?? ''
    };

    input.value.penyeliateknik = {
      value: detail.penyeliateknikfk ?? '',
      label: detail.penyeliateknik ?? ''
    };

    input.value.pelaksana = {
      value: detail.pelaksanateknikfk ?? '',
      label: detail.pelaksanateknik ?? ''
    };

    input.value.keterangan = detail.keterangan ?? '';
    setExistingFilePreviews(detail)
  });

  input.value.namaproduk = e.namaproduk ?? '';
  input.value.namaserialnumber = e.namaserialnumber ?? ''
  input.value.merktipe = [e?.namamerk, e?.namatipe].filter(Boolean).join(' / ')
  input.value.lingkupkalibrasiFill = e.lingkupkalibrasi ?? '';
  input.value.durasikalbrasi = e.durasikalbrasi ?? '';
  kajiUlangData.value = e;
  item.isregiscustomer = e.isregiscustomer;
  modalKalibrasiVendor.value = true;
  setAutoFill();
};

const fetchLokasi = async (filter: any) => {
  await useApi().get(
    `general/dropdown/lokasikalibrasi_m?select=id,lokasi&param_search=lokasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lokasikalibrasi.value = response
  })
}

const fetchLingkup = async (filter: any) => {
  await useApi().get(
    `general/dropdown/lingkupkalibrasi_m?select=id,lingkupkalibrasi&param_search=lingkupkalibrasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lingkup.value = response
  })
}

const fetchPenyelia = async (filter: any) => {
  let lokasi = input.value.lokasirepairfk.value
  await useApi().get(
    `registrasi/pegawai-lokasi-kalibrasi?lokasi=${lokasi}&param_search=namalengkap&query=${filter.query}&jenispegawai=${1}`).then((response) => {
      d_penyelia.value = response.data.map((e: any) => {
        return { label: e.namalengkap, value: e.id }
      })
    })
}

const fetchPelaksana = async (filter: any) => {
  let lokasi = input.value.lokasirepairfk.value
  await useApi().get(
    `registrasi/pegawai-lokasi-kalibrasi?lokasi=${lokasi}&param_search=namalengkap&query=${filter.query}&jenispegawai=${2}`).then((response) => {
      d_pelaksana.value = response.data.map((e: any) => {
        return { label: e.namalengkap, value: e.id }
      })
    })
}

const setAutoFill = async () => {
  input.value.tglPembuatan = new Date()
  let manager = 2;
  let asman = 3;
  await useApi().get(
    `registrasi/pegawai-kalibrasi?jabatan=${manager}`).then((response) => {
      input.value.namamanager = response.data.namalengkap
    })
  await useApi().get(
    `registrasi/pegawai-kalibrasi?jabatan=${asman}`).then((response) => {
      input.value.namaasman = response.data.namalengkap
    })
}


const clear = () => {
  stopCamera()
  clearFiles()

  item.keterangan = ''
  input.value.keterangan = ''
  isStatusSurkesLocked.value = false
  modalKajiUlang.value = false
  modalKalibrasiVendor.value = false
  modalCatatan.value = false
}

watch(
  () => ID_MITRA,
  (newValue, oldValue) => {
    if (newValue != oldValue) {

    }
  }
)

headerUnit(ID_MITRA, NOREC_PD, TGLREGISTRASI)

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/bedah.scss';

tabs-wrapper.is-slider .tabs,
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
          color: var(--primary);
          box-shadow: var(--light-box-shadow);
        }
      }
    }
  }

  .grid-item-wrap {
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: var(--radius-large);
    transition: all 0.3s; // transition-all test

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
            color: var(--primary);
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
  background: var(--success) !important
}

.user-grid-v2 .grid-item-wrap .grid-item-head {
  padding: 10px;
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
      color: var(--primary);
      flex-shrink: 0;
    }
  }

  .search-button-rad {
    background-color: var(--primary);
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
</style>
