<template>
  <ConfirmDialog />

  <div class="column">
    <VCard class="kp-header-card">
      <div class="kp-hero">
        <div class="kp-hero-left">
          <img src="/@src/assets/illustrations/dashboards/personal/UMRO.png" alt="UMRO Laboratory"
            class="kp-hero-logo" />
          <div class="kp-hero-text">
            <h3 class="kp-hero-title">Kendali Surat Jalan</h3>
            <p class="kp-hero-sub">
              Pembuatan surat jalan untuk alat yang sudah selesai dan belum pernah dibuatkan surat jalan
            </p>
          </div>
        </div>

        <div class="is-flex is-align-items-center" style="gap:.5rem">
          <VButton color="primary" icon="feather:plus" raised @click="openManual()">
            Surat Jalan Manual
          </VButton>

          <VButton color="info" icon="feather:refresh-ccw" outlined @click="fetchRegistrasi(); fetchRiwayatAll()">
            Refresh
          </VButton>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="columns is-multiline">
        <div class="column is-4">
          <VDatePicker v-model="item.filterTgl" is-range color="pink" trim-weeks>
            <template #default="{ inputValue, inputEvents }">
              <VField addons>
                <VControl icon="feather:calendar">
                  <VInput :value="inputValue.start" v-on="inputEvents.start" />
                </VControl>

                <VControl>
                  <VButton static>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                  </VButton>
                </VControl>

                <VControl icon="feather:calendar">
                  <VInput :value="inputValue.end" v-on="inputEvents.end" />
                </VControl>
              </VField>
            </template>
          </VDatePicker>
        </div>

        <div class="column is-5">
          <VField label="Cari No Pendaftaran / Unit / Jenis Order">
            <VControl icon="feather:search">
              <VInput v-model="item.search" class="is-rounded" placeholder="Cari data registrasi..."
                @keyup.enter="fetchRegistrasi()" />
            </VControl>
          </VField>
        </div>

        <div class="column is-3 mt-5">
          <VButton color="success" icon="feather:search" raised :loading="isLoading" @click="fetchRegistrasi()">
            Cari
          </VButton>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-3">
        <h3 class="title is-5 mb-0">Daftar Pendaftaran</h3>

        <div class="is-flex is-align-items-center" style="gap:.5rem; flex-wrap: wrap;">
          <VTag color="info" rounded>Alat Selesai</VTag>
          <VTag color="primary" rounded>Surat Jalan</VTag>
          <VTag color="success" rounded>Tersedia</VTag>
        </div>
      </div>

      <div v-if="isPlaceLoad && dataRegistrasi.length === 0">
        <VPlaceloadWrap v-for="n in 6" :key="'sk-reg-' + n">
          <VPlaceload class="mx-2 mb-3" />
          <VPlaceload class="mx-2" />
        </VPlaceloadWrap>
      </div>

      <VPlaceholderPage v-else-if="dataRegistrasi.length === 0" title="Tidak ada alat yang tersedia"
        subtitle="Data yang tampil hanya pendaftaran yang memiliki alat selesai dan belum dibuatkan surat jalan" larger>
        <template #image>
          <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
          <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
        </template>
      </VPlaceholderPage>

      <div v-else class="columns is-multiline">
        <div v-for="row in dataRegistrasi" :key="row.norec" class="column is-6">
          <div class="sj-card">
            <div class="sj-card-head">
              <div>
                <div class="sj-card-title">{{ row.nopendaftaran || '-' }}</div>
                <div class="sj-card-sub">{{ H.formatDateToLocalString(row.tglregistrasi) }}</div>
              </div>

              <VTag :color="row.jenisorder === 'repair' ? 'warning' : 'info'" rounded>
                {{ row.jenisorder || '-' }}
              </VTag>
            </div>

            <div class="sj-card-body">
              <div class="sj-unit">{{ row.namaperusahaan }}</div>

              <div class="sj-stat-grid">
                <div class="sj-stat is-done">
                  <span class="num">{{ row.jumlahdetail || 0 }}</span>
                  <span class="txt">Alat Selesai</span>
                </div>

                <div class="sj-stat is-made">
                  <span class="num">{{ row.jumlahsuratjalan || 0 }}</span>
                  <span class="txt">Surat Jalan</span>
                </div>

                <div class="sj-stat is-ready">
                  <span class="num">{{ row.jumlahtersedia || 0 }}</span>
                  <span class="txt">Tersedia</span>
                </div>
              </div>

              <div class="sj-actions">
                <VButton color="primary" icon="feather:file-plus" raised
                  :disabled="Number(row.jumlahtersedia || 0) <= 0" @click="openFromRegistrasi(row)">
                  Buat
                </VButton>

                <VButton color="info" icon="feather:clock" outlined @click="openRiwayat(row)">
                  Riwayat
                </VButton>
              </div>
            </div>
          </div>
        </div>
      </div>

      <VFlexPagination v-model:current-page="pageRegistrasi.page" :item-per-page="pageRegistrasi.limit"
        :total-items="totalRegistrasi" :max-links-displayed="5" class="mt-4">
        <template #before-navigation>
          <VFlex class="mr-4 mt-1" column-gap="1rem">
            <VField>
              <VControl>
                <div class="select is-rounded">
                  <select v-model="pageRegistrasi.limit">
                    <option :value="2">2 results per page</option>
                    <option :value="4">4 results per page</option>
                    <option :value="6">6 results per page</option>
                    <option :value="8">8 results per page</option>
                    <option :value="12">12 results per page</option>
                  </select>
                </div>
              </VControl>
            </VField>
          </VFlex>
        </template>
      </VFlexPagination>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
        <h3 class="title is-5 mb-0">Riwayat Surat Jalan</h3>

        <VButton color="info" icon="feather:refresh-ccw" outlined @click="fetchRiwayatAll()">
          Refresh
        </VButton>
      </div>

      <DataTable :value="dataRiwayat" class="p-datatable-sm" :loading="isLoadingRiwayat" :paginator="true" :rows="10"
        :rowsPerPageOptions="[5, 10, 25, 50]" scrollable responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
        showGridlines>
        <Column field="no" header="#" frozen style="min-width:70px"></Column>

        <Column field="status" header="Status" style="min-width:160px">
          <template #body="slotProps">
            <VTag :color="slotProps.data.color" rounded>
              {{ slotProps.data.status }}
            </VTag>
          </template>
        </Column>

        <Column field="nosuratjalan" header="No Surat Jalan" style="min-width:210px"></Column>
        <Column field="sumber" header="Sumber" style="min-width:130px"></Column>
        <Column field="nopendaftaran" header="No Pendaftaran" style="min-width:220px"></Column>
        <Column field="diberikankepada" header="Diberikan Kepada" style="min-width:260px"></Column>
        <Column field="tujuan" header="Tujuan" style="min-width:260px"></Column>
        <Column field="namapegawaibawa" header="Yang Membawa" style="min-width:220px"></Column>
        <Column field="jumlahbarang" header="Jumlah Barang" style="min-width:140px"></Column>

        <Column field="tanggalsurat" header="Tanggal" style="min-width:180px">
          <template #body="slotProps">
            <span>{{ H.formatDateToLocalString(slotProps.data.tanggalsurat) }}</span>
          </template>
        </Column>

        <Column header="Aksi" frozen alignFrozen="right" style="min-width:230px">
          <template #body="slotProps">
            <div class="is-flex" style="gap:.35rem">
              <VIconButton color="info" icon="feather:eye" circle raised v-tooltip.bubble="'Detail'"
                @click="openDetail(slotProps.data)" />

              <VIconButton color="success" icon="feather:printer" circle raised v-tooltip.bubble="'Cetak Surat Jalan'"
                @click="cetakSuratJalan(slotProps.data)" />

              <VIconButton color="danger" icon="feather:trash" circle raised v-tooltip.bubble="'Batalkan'"
                @click="batalSuratJalan(slotProps.data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </VCard>
  </div>

  <VModal :open="modalForm" title="Form Surat Jalan" size="big" actions="right" cancelLabel="Tutup"
    @close="closeForm()">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-12">
          <div class="sj-form-alert">
            <b>Sumber:</b>
            {{ form.sumber === 'registrasi' ? 'Dari Nomor Pendaftaran' : 'Manual / Eksternal' }}
            <span v-if="form.nopendaftaran"> — {{ form.nopendaftaran }}</span>
          </div>
        </div>

        <div class="column is-4">
          <VField>
            <VLabel class="required-field">Diberikan Kepada</VLabel>
            <VControl icon="feather:user">
              <VInput v-model="form.diberikankepada" placeholder="Nama penerima / perusahaan" />
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField>
            <VLabel>Berdasarkan</VLabel>
            <VControl icon="feather:file-text">
              <VInput v-model="form.berdasarkan" placeholder="Dasar surat / keterangan" />
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField>
            <VLabel class="required-field">Tanggal</VLabel>
            <VDatePicker v-model="form.tanggalsurat" mode="date" style="width:100%" trim-weeks>
              <template #default="{ inputValue, inputEvents }">
                <VField>
                  <VControl icon="feather:calendar" fullwidth>
                    <VInput :value="inputValue" placeholder="Tanggal" v-on="inputEvents" />
                  </VControl>
                </VField>
              </template>
            </VDatePicker>
          </VField>
        </div>

        <div class="column is-6">
          <VField>
            <VLabel class="required-field">Tujuan</VLabel>
            <VControl icon="feather:map-pin">
              <VInput v-model="form.tujuan" placeholder="Tujuan barang dikirim" />
            </VControl>
          </VField>
        </div>

        <div class="column is-6">
          <VField>
            <VLabel class="required-field">Barang-barang dari</VLabel>
            <VControl icon="feather:home">
              <VInput v-model="form.barangbarangdari" placeholder="Asal barang" />
            </VControl>
          </VField>
        </div>

        <div class="column is-3">
          <VField>
            <VLabel>Kendaraan</VLabel>
            <VControl icon="feather:truck">
              <VInput v-model="form.kendaraan" placeholder="Kendaraan" />
            </VControl>
          </VField>
        </div>

        <div class="column is-3">
          <VField>
            <VLabel>Nomor Polisi</VLabel>
            <VControl icon="feather:hash">
              <VInput v-model="form.nomorpolisi" placeholder="Nomor polisi" />
            </VControl>
          </VField>
        </div>

        <div class="column is-3">
          <VField>
            <VLabel>Pengemudi</VLabel>
            <VControl icon="feather:user-check">
              <VInput v-model="form.pengemudi" placeholder="Nama pengemudi" />
            </VControl>
          </VField>
        </div>

        <div class="column is-3">
          <VField>
            <VLabel class="required-field">Yang Membawa</VLabel>
            <VControl icon="feather:user">
              <AutoComplete v-model="form.pegawaibawa" :suggestions="d_pegawaibawa" @complete="fetchPegawai($event)"
                :optionLabel="'label'" :dropdown="true" :minLength="3" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                :field="'label'" placeholder="Ketik pegawai" />
            </VControl>
          </VField>
        </div>

        <div class="column is-12">
          <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
            <h4 class="title is-6 mb-0">Daftar Barang</h4>

            <div class="is-flex" style="gap:.5rem">
              <VButton v-if="form.sumber === 'registrasi'" color="info" outlined icon="feather:check-square"
                @click="toggleAllRows()">
                {{ isAllChecked ? 'Batal Semua' : 'Pilih Semua' }}
              </VButton>

              <VButton color="primary" outlined icon="feather:plus" @click="addManualRow()">
                Tambah Barang
              </VButton>
            </div>
          </div>

          <div v-if="isLoadDetail" class="p-4">
            <VPlaceloadWrap v-for="n in 3" :key="'sk-detail-' + n">
              <VPlaceload class="mx-2 mb-3" />
              <VPlaceload class="mx-2" />
            </VPlaceloadWrap>
          </div>

          <div v-else>
            <div v-if="detailRows.length === 0" class="notification is-light">
              Belum ada barang yang tersedia. Jika dari pendaftaran, kemungkinan semua alat selesai sudah pernah
              dibuatkan
              surat jalan.
            </div>

            <div v-for="(row, index) in detailRows" :key="row._id" class="sj-item-card"
              :class="{ 'is-selected': row._checked }">
              <div class="sj-item-top">
                <label class="sj-check" v-if="form.sumber === 'registrasi'">
                  <input type="checkbox" v-model="row._checked" />
                  <span>Pilih</span>
                </label>

                <div class="sj-item-no">
                  Barang {{ index + 1 }}
                </div>

                <VIconButton v-if="form.sumber === 'manual' || !row.norec_detail" color="danger" icon="feather:x" circle
                  @click="removeRow(index)" />
              </div>

              <div class="columns is-multiline">
                <div class="column is-3">
                  <VField>
                    <VLabel class="required-field">Nama Barang</VLabel>
                    <VControl>
                      <VInput v-model="row.namabarang" />
                    </VControl>
                  </VField>
                </div>

                <div class="column is-2">
                  <VField>
                    <VLabel>Merk</VLabel>
                    <VControl>
                      <VInput v-model="row.namamerk" />
                    </VControl>
                  </VField>
                </div>

                <div class="column is-2">
                  <VField>
                    <VLabel>Tipe</VLabel>
                    <VControl>
                      <VInput v-model="row.namatipe" />
                    </VControl>
                  </VField>
                </div>

                <div class="column is-2">
                  <VField>
                    <VLabel>S/N</VLabel>
                    <VControl>
                      <VInput v-model="row.namaserialnumber" />
                    </VControl>
                  </VField>
                </div>

                <div class="column is-1">
                  <VField>
                    <VLabel>Jumlah</VLabel>
                    <VControl>
                      <VInput v-model="row.jumlah" />
                    </VControl>
                  </VField>
                </div>

                <div class="column is-1">
                  <VField>
                    <VLabel>Satuan</VLabel>
                    <VControl>
                      <VInput v-model="row.satuan" @input="row.satuan = String(row.satuan || '').toUpperCase()" />
                    </VControl>
                  </VField>
                </div>

                <div class="column is-1">
                  <VField>
                    <VLabel>Ket</VLabel>
                    <VControl>
                      <VInput v-model="row.ket" />
                    </VControl>
                  </VField>
                </div>

                <div class="column is-12">
                  <div class="sj-photo-area">
                    <div class="sj-photo-list">
                      <div v-if="row.fotoproduk" class="sj-photo" @click="previewImage(getImageUrl(row.fotoproduk))">
                        <img :src="getImageUrl(row.fotoproduk)" alt="Foto Produk" />
                        <span>Foto Produk</span>
                      </div>

                      <div v-for="(p, i) in row._previews" :key="p.id" class="sj-photo" @click="previewImage(p.url)">
                        <img :src="p.url" alt="Foto Tambahan" />
                        <button class="delete is-small" @click.stop="removeImageRow(row, i)"></button>
                        <span>Tambahan</span>
                      </div>
                    </div>

                    <div class="sj-photo-actions">
                      <div class="file is-small is-info is-boxed">
                        <label class="file-label">
                          <input class="file-input" type="file" accept="image/*" multiple
                            @change="onFileRowChange($event, row)" />
                          <span class="file-cta">
                            <span class="file-icon">
                              <i class="fas fa-upload"></i>
                            </span>
                            <span class="file-label">Upload</span>
                          </span>
                        </label>
                      </div>

                      <VButton color="success" outlined icon="feather:camera" @click="openRowCamera(row)">
                        Kamera
                      </VButton>

                      <VButton v-if="row._previews.length" color="danger" outlined icon="feather:trash"
                        @click="clearRowImages(row)">
                        Hapus Foto Tambahan
                      </VButton>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="isRowCameraActive" class="box camera-overlay">
              <h5 class="title is-6 has-text-centered">
                Ambil Foto: {{ activeCameraRow?.namabarang }}
              </h5>

              <div class="has-text-centered">
                <video ref="videoRow" autoplay muted playsinline
                  style="width:500px; max-width:100%; height:auto; border-radius:10px; border:1px solid #ddd;"></video>
              </div>

              <div class="buttons is-centered mt-4">
                <button type="button" @click="takePhotoRow" class="button is-success">
                  <span class="icon">
                    <i class="fas fa-camera"></i>
                  </span>
                  <span>Jepret Tambah</span>
                </button>

                <button type="button" @click="closeRowCamera" class="button is-warning">
                  Selesai
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #action>
      <VButton color="primary" icon="feather:save" :loading="isSaving" raised @click="saveSuratJalan()">
        Simpan Surat Jalan
      </VButton>
    </template>
  </VModal>

  <VModal :open="modalRiwayat" title="Riwayat Surat Jalan" size="big" actions="right" cancelLabel="Tutup"
    @close="modalRiwayat = false">
    <template #content>
      <div v-if="selectedRegistrasi" class="notification is-info is-light">
        <b>{{ selectedRegistrasi.nopendaftaran }}</b> — {{ selectedRegistrasi.namaperusahaan }}
      </div>

      <DataTable :value="dataRiwayatRegistrasi" class="p-datatable-sm" :paginator="true" :rows="10" scrollable
        responsiveLayout="stack" breakpoint="960px" showGridlines>
        <Column field="no" header="#" style="min-width:70px"></Column>

        <Column field="status" header="Status" style="min-width:150px">
          <template #body="slotProps">
            <VTag :color="slotProps.data.color" rounded>
              {{ slotProps.data.status }}
            </VTag>
          </template>
        </Column>

        <Column field="nosuratjalan" header="No Surat Jalan" style="min-width:220px"></Column>
        <Column field="diberikankepada" header="Diberikan Kepada" style="min-width:260px"></Column>
        <Column field="tujuan" header="Tujuan" style="min-width:260px"></Column>
        <Column field="namapegawaibawa" header="Yang Membawa" style="min-width:220px"></Column>
        <Column field="jumlahbarang" header="Jumlah Barang" style="min-width:150px"></Column>

        <Column field="tanggalsurat" header="Tanggal" style="min-width:180px">
          <template #body="slotProps">
            <span>{{ H.formatDateToLocalString(slotProps.data.tanggalsurat) }}</span>
          </template>
        </Column>

        <Column header="Aksi" style="min-width:160px">
          <template #body="slotProps">
            <div class="is-flex" style="gap:.35rem">
              <VIconButton color="info" icon="feather:eye" circle raised v-tooltip.bubble="'Detail'"
                @click="openDetail(slotProps.data)" />

              <VIconButton color="success" icon="feather:printer" circle raised v-tooltip.bubble="'Cetak Surat Jalan'"
                @click="cetakSuratJalan(slotProps.data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </template>
  </VModal>

  <VModal :open="modalDetail" title="Detail Surat Jalan" size="big" actions="right" cancelLabel="Tutup"
    @close="modalDetail = false">
    <template #content>
      <div v-if="detailSurat.head">
        <div class="sj-detail-head">
          <div>
            <h3>{{ detailSurat.head.nosuratjalan }}</h3>
            <p>{{ detailSurat.head.diberikankepada }}</p>
          </div>

          <VTag :color="detailSurat.head.color" rounded>
            {{ detailSurat.head.status }}
          </VTag>
        </div>

        <div class="columns is-multiline mt-3">
          <div class="column is-4">
            <b>No Pendaftaran</b>
            <p>{{ detailSurat.head.nopendaftaran || '-' }}</p>
          </div>

          <div class="column is-4">
            <b>Tujuan</b>
            <p>{{ detailSurat.head.tujuan || '-' }}</p>
          </div>

          <div class="column is-4">
            <b>Yang Membawa</b>
            <p>{{ detailSurat.head.namapegawaibawa || '-' }}</p>
          </div>

          <div class="column is-4">
            <b>Kendaraan</b>
            <p>{{ detailSurat.head.kendaraan || '-' }}</p>
          </div>

          <div class="column is-4">
            <b>Nomor Polisi</b>
            <p>{{ detailSurat.head.nomorpolisi || '-' }}</p>
          </div>

          <div class="column is-4">
            <b>Pengemudi</b>
            <p>{{ detailSurat.head.pengemudi || '-' }}</p>
          </div>
        </div>

        <DataTable :value="detailSurat.detail" class="p-datatable-sm mt-3" scrollable responsiveLayout="stack"
          breakpoint="960px" showGridlines>
          <Column field="nourut" header="No" style="min-width:70px"></Column>
          <Column field="namabarang" header="Nama Barang" style="min-width:220px"></Column>

          <Column field="merk_tipe" header="Merk/Tipe" style="min-width:220px">
            <template #body="slotProps">
              <span>{{ slotProps.data.namamerk || '-' }} / {{ slotProps.data.namatipe || '-' }}</span>
            </template>
          </Column>

          <Column field="namaserialnumber" header="S/N" style="min-width:180px"></Column>

          <Column field="jumlah" header="Jumlah" style="min-width:100px">
            <template #body="slotProps">
              <span>{{ slotProps.data.jumlah }} {{ slotProps.data.satuan }}</span>
            </template>
          </Column>

          <Column field="ket" header="Ket" style="min-width:160px"></Column>

          <Column header="Foto" style="min-width:250px">
            <template #body="slotProps">
              <div class="sj-photo-list mini">
                <div v-for="foto in slotProps.data.foto" :key="foto.norec" class="sj-photo"
                  @click="previewImage(getImageUrl(foto.filepath))">
                  <img :src="getImageUrl(foto.filepath)" alt="Foto" />
                </div>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </template>
  </VModal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import AutoComplete from 'primevue/autocomplete'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import * as H from '/@src/utils/appHelper'

useHead({ title: 'Kendali Surat Jalan - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const confirmDialog = useConfirm()
const userLogin = useUserSession().getUser()

const isLoading = ref(false)
const isPlaceLoad = ref(false)
const isLoadingRiwayat = ref(false)
const isLoadDetail = ref(false)
const isSaving = ref(false)

const modalForm = ref(false)
const modalRiwayat = ref(false)
const modalDetail = ref(false)

const dataRegistrasi: any = ref([])
const dataRiwayat: any = ref([])
const dataRiwayatRegistrasi: any = ref([])
const detailRows: any = ref([])
const d_pegawaibawa: any = ref([])

const totalRegistrasi = ref(0)
const pageRegistrasi: any = reactive({
  page: 1,
  limit: 4
})

const selectedRegistrasi: any = ref(null)

const detailSurat: any = reactive({
  head: null,
  detail: []
})

const item: any = reactive({
  search: '',
  filterTgl: {
    start: new Date(new Date().getFullYear(), 0, 1),
    end: new Date()
  }
})

const form: any = reactive({
  sumber: 'registrasi',
  noregistrasifk: null,
  nopendaftaran: '',
  diberikankepada: '',
  berdasarkan: '',
  tanggalsurat: new Date(),
  tujuan: '',
  barangbarangdari: 'PT PLN NP UMRO ULAB Jakarta',
  kendaraan: '',
  nomorpolisi: '',
  pengemudi: '',
  pegawaibawa: null
})

const videoRow = ref<HTMLVideoElement | null>(null)
let rowCameraStream: MediaStream | null = null
const isRowCameraActive = ref(false)
const activeCameraRow: any = ref(null)

const isAllChecked = computed(() => {
  const rows = detailRows.value || []
  if (!rows.length) return false
  return rows.every((x: any) => x._checked)
})

const makeId = () => `${Date.now()}_${Math.random().toString(16).slice(2)}`

const getImageUrl = (path: any) => {
  if (!path) return '/images/other/no_image.jpg'

  const value = String(path)

  if (value.startsWith('http') || value.startsWith('blob:') || value.startsWith('/')) {
    return value
  }

  return `/produk/${value}`
}

const previewImage = (url: string) => {
  if (url) window.open(url, '_blank')
}

const statusColor = (status: any) => {
  return 'info'
}

const statusText = (status: any) => {
  return 'Dibuat'
}

const resetForm = () => {
  form.sumber = 'registrasi'
  form.noregistrasifk = null
  form.nopendaftaran = ''
  form.diberikankepada = ''
  form.berdasarkan = ''
  form.tanggalsurat = new Date()
  form.tujuan = ''
  form.barangbarangdari = 'PT PLN NP UMRO ULAB Jakarta'
  form.kendaraan = ''
  form.nomorpolisi = ''
  form.pengemudi = ''
  form.pegawaibawa = null

  detailRows.value.forEach((row: any) => clearRowImages(row))
  detailRows.value = []
}

const closeForm = () => {
  closeRowCamera()
  resetForm()
  modalForm.value = false
}

const fetchPegawai = async (filter: any) => {
  await useApi().get(
    `general/dropdown/pegawai_m?select=id,namalengkap&param_search=namalengkap&query=${filter.query}&limit=10`
  ).then((response) => {
    d_pegawaibawa.value = response
  })
}

const cetakSuratJalan = (row: any) => {
  if (!row?.norec) {
    H.alert('warning', 'Data surat jalan tidak valid.')
    return
  }

  H.printBlade(`registrasi/cetak-surat-jalan?pdf=true&norec=${row.norec}`)
}

const fetchRegistrasi = async () => {
  let dari = ''
  let sampai = ''

  if (item.filterTgl.start) {
    dari = H.formatDate(item.filterTgl.start, 'YYYY-MM-DD 00:00')
  }

  if (item.filterTgl.end) {
    sampai = H.formatDate(item.filterTgl.end, 'YYYY-MM-DD 23:59')
  }

  const search = item.search ? `&search=${encodeURIComponent(item.search)}` : ''

  isPlaceLoad.value = true
  isLoading.value = true

  try {
    const res = await useApi().get(
      `registrasi/list-registrasi-surat-jalan?page=${pageRegistrasi.page}&limit=${pageRegistrasi.limit}&dari=${dari}&sampai=${sampai}${search}`
    )

    dataRegistrasi.value = res?.data ?? []
    totalRegistrasi.value = res?.total ?? 0
  } catch (e) {
    console.error(e)
    dataRegistrasi.value = []
    totalRegistrasi.value = 0
  } finally {
    isPlaceLoad.value = false
    isLoading.value = false
  }
}

const fetchRiwayatAll = async () => {
  isLoadingRiwayat.value = true

  try {
    const res = await useApi().get(`registrasi/riwayat-surat-jalan`)
    const list = Array.isArray(res) ? res : []

    list.forEach((x: any, i: number) => {
      x.no = i + 1
      x.status = x.status || statusText(x.statussuratjalan)
      x.color = x.color || statusColor(x.statussuratjalan)
    })

    dataRiwayat.value = list
  } catch (e) {
    console.error(e)
    dataRiwayat.value = []
  } finally {
    isLoadingRiwayat.value = false
  }
}

const fetchRiwayatByRegistrasi = async (norecPd: string) => {
  try {
    const res = await useApi().get(`registrasi/riwayat-surat-jalan?norec_pd=${norecPd}`)
    const list = Array.isArray(res) ? res : []

    list.forEach((x: any, i: number) => {
      x.no = i + 1
      x.status = x.status || statusText(x.statussuratjalan)
      x.color = x.color || statusColor(x.statussuratjalan)
    })

    dataRiwayatRegistrasi.value = list
  } catch (e) {
    console.error(e)
    dataRiwayatRegistrasi.value = []
  }
}

const makeRow = (data: any = {}) => {
  return {
    _id: makeId(),
    _checked: data._checked ?? true,
    _files: [] as File[],
    _previews: [] as Array<{ id: string; url: string; name: string }>,

    norec_detail: data.norec_detail ?? null,
    namabarang: data.namabarang ?? data.namaproduk ?? '',
    namamerk: data.namamerk ?? '',
    namatipe: data.namatipe ?? '',
    namaserialnumber: data.namaserialnumber ?? '',
    jumlah: data.jumlah ?? '1',
    satuan: data.satuan ?? 'SET',
    ket: data.ket ?? data.jenisorder ?? 'Kalibrasi',
    fotoproduk: data.fotoproduk ?? null
  }
}

const openFromRegistrasi = async (row: any) => {
  if (Number(row.jumlahtersedia || 0) <= 0) {
    H.alert('warning', 'Semua alat selesai pada pendaftaran ini sudah dibuatkan surat jalan.')
    return
  }

  resetForm()

  selectedRegistrasi.value = row
  form.sumber = 'registrasi'
  form.noregistrasifk = row.norec
  form.nopendaftaran = row.nopendaftaran
  form.diberikankepada = row.namaperusahaan
  form.berdasarkan = row.jenisorder === 'repair' ? 'Repair' : 'Kalibrasi'
  form.tujuan = row.namaperusahaan
  form.barangbarangdari = 'PT PLN NP UMRO ULAB Jakarta'

  modalForm.value = true
  isLoadDetail.value = true

  try {
    const res = await useApi().get(`registrasi/layanan-surat-jalan?norec_pd=${row.norec}`)
    const rows = res?.detail ?? []

    if (!rows.length) {
      H.alert('warning', 'Semua alat selesai pada pendaftaran ini sudah dibuatkan surat jalan.')
    }

    detailRows.value = rows.map((x: any) => makeRow({
      ...x,
      _checked: false,
      namabarang: x.namaproduk,
      jumlah: '1',
      satuan: 'SET',
      ket: x.jenisorder === 'repair' ? 'Repair' : 'Kalibrasi'
    }))
  } catch (e) {
    console.error(e)
    detailRows.value = []
  } finally {
    isLoadDetail.value = false
  }
}

const openManual = () => {
  resetForm()
  selectedRegistrasi.value = null
  form.sumber = 'manual'
  form.tanggalsurat = new Date()
  addManualRow()
  modalForm.value = true
}

const openRiwayat = async (row: any) => {
  selectedRegistrasi.value = row
  dataRiwayatRegistrasi.value = []
  modalRiwayat.value = true
  await fetchRiwayatByRegistrasi(row.norec)
}

const addManualRow = () => {
  detailRows.value.push(makeRow({
    _checked: true,
    jumlah: '1',
    satuan: 'SET',
    ket: 'Kalibrasi'
  }))
}

const removeRow = (index: number) => {
  const row = detailRows.value[index]
  if (row) {
    clearRowImages(row)
  }

  detailRows.value.splice(index, 1)
}

const toggleAllRows = () => {
  const target = !isAllChecked.value

  detailRows.value.forEach((row: any) => {
    row._checked = target
  })
}

const addFilesToRow = (row: any, files: File[], renamePrefix = '') => {
  if (!files?.length) return

  files.forEach((file) => {
    if (!file) return

    if (file.size > 10000000) {
      H.alert('error', 'Maksimal ukuran foto 10MB.')
      return
    }

    const ext = (file.name.split('.').pop() || 'jpeg').toLowerCase()
    const safePrefix = renamePrefix ? renamePrefix.replace(/\s+/g, '_') : 'barang'
    const fname = `${safePrefix}_${row._id}_${Date.now()}_${Math.random().toString(16).slice(2)}.${ext}`
    const renamed = new File([file], fname, { type: file.type || 'image/jpeg' })

    row._files.push(renamed)
    row._previews.push({
      id: makeId(),
      url: URL.createObjectURL(renamed),
      name: renamed.name
    })
  })
}

const onFileRowChange = (event: any, row: any) => {
  const files: File[] = Array.from(event.target.files || [])
  if (!files.length) return

  addFilesToRow(row, files, row.namabarang || 'barang')
  event.target.value = ''
}

const removeImageRow = (row: any, index: number) => {
  const p = row._previews?.[index]
  if (p?.url) URL.revokeObjectURL(p.url)

  row._previews.splice(index, 1)
  row._files.splice(index, 1)
}

const clearRowImages = (row: any) => {
  if (Array.isArray(row._previews)) {
    row._previews.forEach((p: any) => p?.url && URL.revokeObjectURL(p.url))
  }

  row._previews = []
  row._files = []
}

const isMobileDevice = () => {
  return /Mobi|Android/i.test(navigator.userAgent)
}

const openRowCamera = async (row: any) => {
  activeCameraRow.value = row

  if (isMobileDevice()) {
    const videoInput = document.createElement('input')
    videoInput.type = 'file'
    videoInput.accept = 'image/*'
      ; (videoInput as any).capture = 'environment'
    videoInput.multiple = true

    videoInput.onchange = (event: any) => {
      const files: File[] = Array.from((event.target as HTMLInputElement).files || [])
      if (!files.length) return

      addFilesToRow(row, files, row.namabarang || 'barang')
    }

    videoInput.click()
    return
  }

  isRowCameraActive.value = true
  await nextTick()

  try {
    if (!navigator.mediaDevices?.getUserMedia) {
      H.alert('error', 'Browser tidak mendukung kamera.')
      isRowCameraActive.value = false
      return
    }

    rowCameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
      audio: false
    })

    if (videoRow.value) {
      videoRow.value.srcObject = rowCameraStream
      videoRow.value.muted = true
      videoRow.value.playsInline = true
      await videoRow.value.play()
    }
  } catch (err) {
    console.error(err)
    H.alert('error', 'Gagal mengakses kamera.')
    closeRowCamera()
  }
}

const takePhotoRow = async () => {
  if (!videoRow.value || !activeCameraRow.value) return

  const v = videoRow.value

  if (!v.videoWidth || !v.videoHeight) {
    H.alert('error', 'Kamera belum siap, coba lagi.')
    return
  }

  const canvas = document.createElement('canvas')
  canvas.width = v.videoWidth
  canvas.height = v.videoHeight

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  ctx.drawImage(v, 0, 0, canvas.width, canvas.height)

  const blob: Blob | null = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92))
  if (!blob) return

  const file = new File([blob], `camera_${activeCameraRow.value._id}_${Date.now()}.jpeg`, {
    type: 'image/jpeg'
  })

  addFilesToRow(activeCameraRow.value, [file], activeCameraRow.value.namabarang || 'barang')
}

const closeRowCamera = () => {
  try {
    if (rowCameraStream) {
      rowCameraStream.getTracks().forEach((t) => t.stop())
      rowCameraStream = null
    }

    if (videoRow.value) {
      videoRow.value.pause?.()
      videoRow.value.srcObject = null
    }
  } finally {
    isRowCameraActive.value = false
    activeCameraRow.value = null
  }
}

const validateForm = () => {
  if (!form.tanggalsurat) {
    H.alert('warning', 'Tanggal surat wajib diisi.')
    return false
  }

  if (!form.diberikankepada || String(form.diberikankepada).trim() === '') {
    H.alert('warning', 'Diberikan kepada wajib diisi.')
    return false
  }

  if (!form.tujuan || String(form.tujuan).trim() === '') {
    H.alert('warning', 'Tujuan wajib diisi.')
    return false
  }

  if (!form.barangbarangdari || String(form.barangbarangdari).trim() === '') {
    H.alert('warning', 'Barang-barang dari wajib diisi.')
    return false
  }

  if (!form.pegawaibawa?.value && !form.pegawaibawa?.label && !form.pegawaibawa) {
    H.alert('warning', 'Yang membawa wajib dipilih.')
    return false
  }

  const selectedRows = detailRows.value.filter((x: any) => form.sumber === 'manual' || x._checked)

  if (!selectedRows.length) {
    H.alert('warning', 'Pilih minimal satu barang.')
    return false
  }

  for (const row of selectedRows) {
    if (!row.namabarang || String(row.namabarang).trim() === '') {
      H.alert('warning', 'Nama barang wajib diisi.')
      return false
    }
  }

  return true
}

const saveSuratJalan = async () => {
  if (!validateForm()) return

  const selectedRows = detailRows.value.filter((x: any) => form.sumber === 'manual' || x._checked)

  const detailbarang = selectedRows.map((row: any) => ({
    noregistrasidetailfk: row.norec_detail || null,
    namabarang: row.namabarang || '',
    namamerk: row.namamerk || '',
    namatipe: row.namatipe || '',
    namaserialnumber: row.namaserialnumber || '',
    jumlah: row.jumlah || '1',
    satuan: String(row.satuan || 'SET').toUpperCase(),
    ket: row.ket || '',
    fotoproduk: row.fotoproduk || null
  }))

  const formData = new FormData()
  formData.append('sumber', form.sumber)
  formData.append('noregistrasifk', form.noregistrasifk || '')
  formData.append('nopendaftaran', form.nopendaftaran || '')

  formData.append('diberikankepada', form.diberikankepada || '')
  formData.append('berdasarkan', form.berdasarkan || '')
  formData.append('tanggalsurat', H.formatDate(form.tanggalsurat, 'YYYY-MM-DD'))
  formData.append('tujuan', form.tujuan || '')
  formData.append('barangbarangdari', form.barangbarangdari || '')

  formData.append('kendaraan', form.kendaraan || '')
  formData.append('nomorpolisi', form.nomorpolisi || '')
  formData.append('pengemudi', form.pengemudi || '')

  formData.append('pegawaibawafk', form.pegawaibawa?.value || '')
  formData.append('namapegawaibawa', form.pegawaibawa?.label || form.pegawaibawa || '')

  formData.append('petugasfk', userLogin?.pegawai?.id || '')
  formData.append('namapetugas', userLogin?.pegawai?.namaLengkap || userLogin?.pegawai?.namalengkap || '')

  formData.append('detailbarang', JSON.stringify(detailbarang))

  selectedRows.forEach((row: any, index: number) => {
    if (Array.isArray(row._files) && row._files.length) {
      row._files.forEach((file: File) => {
        formData.append(`files[${index}][]`, file)
      })
    }
  })

  isSaving.value = true

  try {
    const res = await useApi().post('registrasi/save-surat-jalan', formData)

    H.alert('success', res?.message || 'Surat jalan berhasil dibuat.')

    closeForm()
    await fetchRegistrasi()
    await fetchRiwayatAll()
  } catch (e: any) {
    console.error(e)
    H.alert('error', e?.response?.data?.message || 'Gagal menyimpan surat jalan.')
  } finally {
    isSaving.value = false
  }
}

const openDetail = async (row: any) => {
  detailSurat.head = null
  detailSurat.detail = []
  modalDetail.value = true

  try {
    const res = await useApi().get(`registrasi/detail-surat-jalan?norec=${row.norec}`)
    detailSurat.head = res?.head ?? null
    detailSurat.detail = res?.detail ?? []

    if (detailSurat.head) {
      detailSurat.head.status = detailSurat.head.status || statusText(detailSurat.head.statussuratjalan)
      detailSurat.head.color = detailSurat.head.color || statusColor(detailSurat.head.statussuratjalan)
    }
  } catch (e) {
    console.error(e)
    detailSurat.head = null
    detailSurat.detail = []
  }
}

const batalSuratJalan = async (row: any) => {
  confirmDialog.require({
    message: `Batalkan surat jalan ${row.nosuratjalan}?`,
    header: 'Konfirmasi Pembatalan',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Batalkan',
    rejectLabel: 'Tidak',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-secondary p-button-outlined',
    accept: async () => {
      try {
        await useApi().post('registrasi/batal-surat-jalan', {
          norec: row.norec,
          alasanbatal: 'Dibatalkan dari halaman kendali surat jalan'
        })

        H.alert('success', 'Surat jalan berhasil dibatalkan.')
        await fetchRegistrasi()
        await fetchRiwayatAll()

        if (selectedRegistrasi.value?.norec) {
          await fetchRiwayatByRegistrasi(selectedRegistrasi.value.norec)
        }
      } catch (e: any) {
        console.error(e)
        H.alert('error', e?.response?.data?.message || 'Gagal membatalkan surat jalan.')
      }
    }
  })
}

watch(
  () => [pageRegistrasi.page, pageRegistrasi.limit],
  () => {
    fetchRegistrasi()
  },
  { immediate: true }
)

fetchRiwayatAll()

onBeforeUnmount(() => {
  closeRowCamera()
  detailRows.value.forEach((row: any) => clearRowImages(row))
})
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/customer.scss';

.kp-header-card {
  padding: 0;
  overflow: hidden;
  border-radius: 14px;
}

.kp-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 18px 22px;
  background: linear-gradient(135deg, rgba(16, 185, 129, .08), rgba(59, 130, 246, .08));
  border: 1px solid rgba(0, 0, 0, .06);
}

.kp-hero-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.kp-hero-logo {
  height: 56px;
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, .08));
}

.kp-hero-text {
  line-height: 1.2;
}

.kp-hero-title {
  margin: 0;
  font-weight: 700;
  font-size: 1.35rem;
  letter-spacing: .2px;
}

.kp-hero-sub {
  margin: .2rem 0 0;
  font-size: .95rem;
  color: #6b7280;
}

.sj-card {
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
  overflow: hidden;
  transition: .18s ease;
  height: 100%;
}

.sj-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, .08);
}

.sj-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid #eef0f4;
  background: #fafafa;
}

.sj-card-title {
  font-size: 15px;
  font-weight: 800;
  color: #111827;
}

.sj-card-sub {
  font-size: 12px;
  color: #6b7280;
  margin-top: 3px;
}

.sj-card-body {
  padding: 14px 16px 16px;
}

.sj-unit {
  min-height: 42px;
  font-size: 14px;
  font-weight: 700;
  color: #111827;
  line-height: 1.35;
}

.sj-stat-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-top: 14px;
}

.sj-stat {
  border: 1px solid #eef0f4;
  border-radius: 12px;
  padding: 10px 8px;
  text-align: center;
  background: #fcfcfd;
}

.sj-stat .num {
  display: block;
  font-size: 20px;
  font-weight: 800;
  color: #111827;
  line-height: 1;
}

.sj-stat .txt {
  display: block;
  font-size: 11px;
  color: #6b7280;
  margin-top: 4px;
}

.sj-stat.is-done {
  border-color: #bfdbfe;
  background: #eff6ff;
}

.sj-stat.is-done .num {
  color: #1d4ed8;
}

.sj-stat.is-done .txt {
  color: #1e40af;
}

.sj-stat.is-made {
  border-color: #ddd6fe;
  background: #f5f3ff;
}

.sj-stat.is-made .num {
  color: #6d28d9;
}

.sj-stat.is-made .txt {
  color: #5b21b6;
}

.sj-stat.is-ready {
  border-color: #bbf7d0;
  background: #f0fdf4;
}

.sj-stat.is-ready .num {
  color: #15803d;
}

.sj-stat.is-ready .txt {
  color: #166534;
}

.sj-actions {
  display: flex;
  justify-content: flex-end;
  gap: .5rem;
  margin-top: 16px;
}

.sj-form-alert {
  border: 1px solid #dbeafe;
  background: #eff6ff;
  color: #1e3a8a;
  border-radius: 12px;
  padding: 10px 12px;
  font-size: 13px;
}

.sj-item-card {
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 12px;
  margin-bottom: 12px;
  background: #fff;
}

.sj-item-card.is-selected {
  border-color: #93c5fd;
  background: #eff6ff;
}

.sj-item-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  margin-bottom: 10px;
}

.sj-check {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
}

.sj-check input {
  width: 17px;
  height: 17px;
}

.sj-item-no {
  flex: 1;
  font-weight: 800;
  color: #111827;
}

.sj-photo-area {
  border: 1px dashed #d1d5db;
  border-radius: 12px;
  padding: 10px;
  background: #fafafa;
}

.sj-photo-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.sj-photo-list.mini {
  gap: 6px;
}

.sj-photo {
  position: relative;
  width: 88px;
  height: 88px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
  overflow: hidden;
  cursor: pointer;
}

.sj-photo-list.mini .sj-photo {
  width: 48px;
  height: 48px;
}

.sj-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.sj-photo span {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 2px 4px;
  font-size: 10px;
  color: #fff;
  text-align: center;
  background: rgba(0, 0, 0, .55);
}

.sj-photo .delete {
  position: absolute;
  top: 3px;
  right: 3px;
}

.sj-photo-actions {
  display: flex;
  align-items: center;
  gap: .5rem;
  flex-wrap: wrap;
  margin-top: 10px;
}

.camera-overlay {
  margin-top: 1rem;
  border: 2px solid var(--primary);
  background: #f8fafc;
}

.sj-detail-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 14px;
  background: #fafafa;
}

.sj-detail-head h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: #111827;
}

.sj-detail-head p {
  margin-top: 4px;
  color: #6b7280;
}

@media (max-width: 768px) {
  .kp-hero {
    flex-direction: column;
    align-items: flex-start;
    padding: 16px;
    gap: .75rem;
  }

  .kp-hero-logo {
    height: 48px;
  }

  .sj-stat-grid {
    grid-template-columns: 1fr;
  }

  .sj-actions {
    justify-content: flex-start;
    flex-wrap: wrap;
  }
}
</style>