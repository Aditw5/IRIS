<template>
  <VCard>
    <div class="columns is-multiline">
      <!-- KOLOM 1 : DOKUMEN -->
      <div class="column is-4">
        <VCard>
          <div class="columns is-multiline">
            <h3 class="title is-5 mb-2 mr-1">Dokumen</h3>
            <div class="column is-12">
              <div class="user-grid-toolbar">
                <VControl icon="feather:search">
                  <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
                </VControl>
                <div class="buttons">
                  <VControl class="is-pulled-right">
                    <VSwitchBlock v-model="item.aktif" label="Aktif" color="danger"
                      @change="changeSwitch(item.aktif)" />
                  </VControl>
                </div>
              </div>

              <!-- LIST VIEW -->
              <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
                <DataTable :value="dataSourcefiltered" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
                  paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                  responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                  currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :class="`p-datatable-small`">
                  <template #header>
                    <div class="flex">
                      <span class="p-input-icon-left">
                        <VButton @click="tambah('Dokumen')" type="button" icon="feather:plus" class="is-fullwidth mr-3"
                          color="success" raised rounded>
                          Tambah
                        </VButton>
                      </span>
                    </div>
                  </template>

                  <!-- <Column field="id" header="Kode"></Column> -->
                  <Column field="namadokumen" header="Nama Dokumen" :sortable="true"></Column>
                  <Column :exportable="false" header="Action">
                    <template #body="slotProps">
                      <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                        v-tooltip.top="'Edit'" @click="edit(slotProps.data)" />
                      <VIconButton type="button" icon="fas fa-trash" class="mr-3" color="danger" circle outlined raised
                        v-tooltip.top="'Hapus'" @click="deleterow(slotProps.data)" />
                      <VIconButton type="button" icon="fas fa-arrow-right" class="mr-3" color="success" circle outlined
                        raised v-tooltip.top="'Pilih data'" @click="selectedModul(slotProps.data)" />
                    </template>
                  </Column>
                </DataTable>
              </div>

              <!-- GRID VIEW (kalau dipakai) -->
              <div class="list-view list-view-v3" v-else-if="selectView == 'grid'">
                <TransitionGroup name="list-complete" tag="div">
                  <div v-for="item in dataSourcefiltered" :key="item.id" class="list-view-item">
                    <div class="list-view-item-inner">
                      <VAvatar size="medium" picture="/images/avatars/svg/modul.svg" color="primary" squared bordered />
                      <div class="meta-left">
                        <h3>{{ item.namadokumen }}</h3>
                        <span>{{ item.reportdisplay }}</span>
                      </div>
                      <div class="meta-right">
                        <div class="buttons">
                          <VButton color="warning" outlined raised @click="edit(item)"> Edit </VButton>
                          <VIconButton icon="feather:trash" class="hint--bubble hint--primary hint--top"
                            data-hint="Delete" circle @click="deleterow(item)" />
                        </div>
                      </div>
                    </div>
                  </div>
                </TransitionGroup>
              </div>
            </div>
          </div>
        </VCard>
      </div>

      <!-- KOLOM 2 : RINCIAN DOKUMEN -->
      <div class="column is-4">
        <VCard>
          <div class="columns is-multiline">
            <h3 class="title is-5 mb-2 mr-1"> Rincian Dokumen</h3>
            <div class="column is-12">
              <VControl icon="feather:search">
                <input v-model="filters2" class="input custom-text-filter" placeholder="Search..." />
              </VControl>
            </div>
            <div class="column is-12">
              <div class="user-grid user-grid-v2">
                <DataTable :value="dataSourcefilteredSub" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
                  paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                  responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                  currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :class="`p-datatable-small`">
                  <template #header>
                    <div class="flex">
                      <span class="p-input-icon-left">
                        <VButton @click="tambah('Rincian')" type="button" icon="feather:plus" class="is-fullwidth mr-3"
                          color="success" raised rounded :disabled="selectedHead == undefined">
                          Tambah
                        </VButton>
                      </span>
                    </div>
                  </template>

                  <!-- <Column field="id" header="Kode"></Column> -->
                  <Column field="namadokumen" header="Rincian Dokumen" :sortable="true"></Column>
                  <Column :exportable="false" header="Action">
                    <template #body="slotProps">
                      <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                        v-tooltip.top="'Edit'" @click="edit(slotProps.data)" />
                      <VIconButton type="button" icon="fas fa-trash" class="mr-3" color="danger" circle outlined raised
                        v-tooltip.top="'Hapus'" @click="deleterow(slotProps.data)" />
                      <VIconButton type="button" icon="fas fa-arrow-right" class="mr-3" color="success" circle outlined
                        raised v-tooltip.top="'Pilih data'" @click="selectedSub(slotProps.data)" />
                    </template>
                  </Column>
                </DataTable>
              </div>
            </div>
          </div>
        </VCard>
      </div>

      <!-- KOLOM 3 : TREE ISI DOKUMEN -->
      <div class="column is-4">
        <VCard>
          <h3 class="title is-5 mb-2 mr-1"> Isi Dokumen</h3>
          <VButton @click="add()" type="button" icon="feather:plus" color="success" raised outlined rounded
            :disabled="selectedIDModul == ''">
            Tambah
          </VButton>

          <Tree v-model:expandedKeys="expandedKeys" v-model:selectionKeys="selectedKey" :value="dataSourceMenu"
            class="w-full md:w-30rem mt-5 custom-tree" :filter="true" filterMode="lenient" @nodeSelect="onNodeSelect"
            selectionMode="single" :metaKeySelection="false">
            <template #default="{ node }">
              <div class="tree-node-label" :class="{
                'is-root-head': node.children && node.children.length,  // punya anak -> head/folder
                'is-child-node': !node.children || !node.children.length // leaf -> isi/file
              }">
                <!-- ikon manual -->
                <i v-if="node.children && node.children.length" class="mr-2 head-icon" aria-hidden="true"></i>
                <i v-else class="mr-2 child-icon" aria-hidden="true"></i>

                <span v-tooltip-prime.bottom="node.nourut?.toString()">
                  {{ node.label }}
                </span>
              </div>
            </template>
          </Tree>

        </VCard>
      </div>
    </div>
  </VCard>

  <!-- MONITORING ORDER (TETAP) -->
  <VCard class="mt-5">
    <div class="personal-dashboard personal-dashboard-v2">
      <div class="columns is-multiline">
        <div class="column">
          <VCard>
            <div class="column is-12">
              <div class="search-widget">
                <div class="field">
                  <div class="columns is-multiline">
                    <div class="column is-2">
                      <VField class="is-autocomplete-select" label="Lokasi">
                        <VControl icon="feather:search">
                          <AutoComplete v-model="item.lokasifk" :suggestions="d_lokasikalibrasi"
                            @complete="fetchLokasi($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                            :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                            placeholder="ketik Lokasi" />
                        </VControl>
                      </VField>
                    </div>

                    <div class="column is-3">
                      <VField class="is-autocomplete-select" label="Unit">
                        <VControl icon="feather:search">
                          <AutoComplete v-model="item.unitfk" :suggestions="d_unit" @complete="fetchUnit($event)"
                            :optionLabel="'label'" :dropdown="true" :minLength="3" :appendTo="'body'"
                            :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik Unit" />
                        </VControl>
                      </VField>
                    </div>

                    <div class="column is-3">
                      <VField class="is-autocomplete-select" label="Jenis Order">
                        <VControl icon="feather:search">
                          <Multiselect v-model="item.jenisorder" :attrs="{ id }" :options="optionsKelompokLayanan"
                            placeholder="Pilih Jenis Order" :searchable="true" />
                        </VControl>
                      </VField>
                    </div>

                    <div class="column is-3 mt-5">
                      <input type="text" v-model="item.search" v-on:keyup.enter="fetchMonitoring()" class="input"
                        placeholder="Search..." />
                    </div>

                    <div class="column mt-5" style="margin-left: auto:!important;">
                      <VIconButton type="button" color="success" class="searcv-button" raised icon="fas fa-search"
                        @click="fetchMonitoring()" :loading="isPlaceLoad" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </VCard>
        </div>

        <div class="column is-12">
          <div class="column" v-if="isPlaceLoad">
            <VPlaceloadWrap v-for="data in 25" :key="data">
              <VPlaceload class="mx-2 mb-3" />
              <VPlaceload class="mx-2" />
            </VPlaceloadWrap>
          </div>

          <div class="column" v-else>
            <div class="dashboard-card has-margin-bottom">
              <DataTable v-model:expandedRows="expandedRows" :value="ulab" dataKey="norec" class="p-datatable-sm"
                :loading="isLoading" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]" scrollable
                paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
                <Column expander style="width:3rem" />
                <Column field="nopendaftaran" header="No Pendaftaran" sortable frozen />
                <Column field="namapenanggungjawab" header="Penanggung Jawab" sortable />
                <Column field="namaperusahaan" header="Unit" sortable />
                <Column field="alamatktr" header="Alamat" sortable />
                <Column field="lokasi" header="Lokasi" sortable />
                <Column field="jenisorder" header="Jenis Order" sortable />
                <Column field="tanggalmulai" header="Tanggal Mulai Kalibrasi" sortable />

                <Column header="Progress Order" style="text-align:center; min-width:8rem">
                  <template #body="{ data }">
                    <div v-if="data.jumlahdetail && data.jumlahselesai != null"
                      style="font-weight: 600; font-size: 0.98em; color: #2abb4a; margin-top: 5px;">
                      Selesai {{ data.jumlahselesai }}/{{ data.jumlahdetail }}
                      <div style="
                          background:#e9ecef;
                          border-radius:8px;
                          width:85%;
                          height:7px;
                          margin: 6px 0 0 0;
                        ">
                        <div :style="{
                          width: ((data.jumlahselesai / data.jumlahdetail) * 100) + '%',
                          background: '#53dd6c',
                          height: '100%',
                          borderRadius: '8px',
                          transition: 'width 0.5s'
                        }"></div>
                      </div>
                    </div>
                  </template>
                </Column>

                <Column field="rata2Bintang" header="Rating Pelanggan" sortable>
                  <template #body="slotProps">
                    <div v-if="slotProps.data.rata2Bintang !== null" class="stars-summary"
                      :aria-label="`Rating ${slotProps.data.rata2Bintang} dari 5`"
                      style="display:flex; align-items:center;">
                      <div class="stars-outer" style="font-size:1rem;">
                        <div class="stars-inner" :style="{
                          width: ((parseFloat(slotProps.data.rata2Bintang) / 5) * 100) + '%'
                        }"></div>
                      </div>
                      <span class="has-text-grey-dark" style="margin-left:0.5rem; font-size:0.9rem;">
                        ({{ slotProps.data.rata2Bintang.toString().replace('.', ',') }} dari 5)
                      </span>
                    </div>
                    <span v-else class="text-500">-</span>
                  </template>
                </Column>

                <Column field="tglregistrasi" header="Tgl Registrasi" sortable />

                <!-- EXPANSION DETAIL -->
                <template #expansion="{ data }">
                  <div class="p-4 bg-white border-round-md shadow-1">
                    <h5 class="mb-2">Detail Orders untuk {{ data.namaperusahaan }}</h5>

                    <DataTable :value="data.detail" class="p-datatable-sm" tableStyle="min-width:40rem" scrollable
                      paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                      responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                      currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
                      <Column field="namaproduk" header="Produk" style="min-width: 250px" />
                      <Column field="namamerk" header="Merk" style="min-width: 100px" />
                      <Column field="namatipe" header="Tipe" style="min-width: 100px" />
                      <Column field="namaserialnumber" header="SN" style="min-width: 200px" />
                      <Column field="noorderalat" header="No Order" style="min-width: 100px" />
                      <Column field="lingkupkalibrasi" header="Lingkup" style="min-width: 100px" />
                      <Column field="pelaksanateknik" header="Pelaksana" style="min-width: 200px" />
                      <Column field="penyeliateknik" header="Penyelia" style="min-width: 200px" />
                      <Column field="durasikalbrasi" header="Durasi Kalbrasi" style="min-width: 50px" />
                      <Column field="nosertifikat" header="No Sertifikat Kalbrasi" style="min-width: 150px" />

                      <!-- PROGRESS + STEPPER -->
                      <Column header="Progress" style="min-width: 300px">
                        <template #body="{ data: row }">
                          <div class="progress-cell">
                            <div class="progress-text">
                              {{ row.progress || '-' }}
                            </div>

                            <div class="progress-stepper">
                              <div class="bar-wrap">
                                <ProgressBar :value="stepPercent(row.progress_step)" :showValue="false"
                                  style="height:10px" />
                                <div class="step-dots">
                                  <span v-for="n in 5" :key="'dot-' + n" class="step-dot"
                                    :class="{ active: n <= (Number(row.progress_step || 1)) }"></span>
                                </div>
                              </div>

                              <div class="step-caption">
                                Step {{ Number(row.progress_step || 1) }}/5
                              </div>
                            </div>
                          </div>
                        </template>
                      </Column>

                      <Column field="bintangpenilaian" header="Penilaian" style="min-width: 200px">
                        <template #body="{ data: row }">
                          <div v-if="row.bintangpenilaian !== null" class="stars-summary"
                            :aria-label="`Rating ${row.bintangpenilaian} dari 5`"
                            style="display:flex; align-items:center;">
                            <div class="stars-outer" style="font-size:1rem;">
                              <div class="stars-inner" :style="{
                                width: ((parseFloat(row.bintangpenilaian) / 5) * 100) + '%'
                              }"></div>
                            </div>
                            <span class="has-text-grey-dark" style="margin-left:0.5rem; font-size:0.9rem;">
                              ({{ row.bintangpenilaian.toString().replace('.', ',') }} dari 5)
                            </span>
                          </div>
                          <span v-else class="text-500">-</span>
                        </template>
                      </Column>

                      <Column field="ulasanpenilaian" header="Ulasan Penilaian" style="min-width: 300px" />

                      <Column header="Sertifikat Kalibrasi" style="min-width: 100px">
                        <template #body="slotProps">
                          <VIconButton class="mr-3" v-if="
                            slotProps.data.pelaksanaisilembarkerjafk != null &&
                            slotProps.data.isverifikasi == null &&
                            slotProps.data.jenisorder == 'kalibrasi'
                          " color="info" outlined circle icon="feather:printer"
                            @click="cetakSertifikatLembarKerja(slotProps.data)" />
                        </template>
                      </Column>

                      <Column header="Sertifikat Verifikasi" style="min-width: 100px">
                        <template #body="slotProps">
                          <VIconButton class="mr-3" v-if="
                            slotProps.data.pelaksanaisilembarkerjafk != null &&
                            slotProps.data.isverifikasi == true &&
                            slotProps.data.jenisorder == 'kalibrasi'
                          " color="success" outlined circle icon="feather:printer"
                            @click="cetakLaporanVerfikasi(slotProps.data)" />
                        </template>
                      </Column>

                      <Column header="Laporan Repair" style="min-width: 100px">
                        <template #body="slotProps">
                          <VIconButton class="mr-3" v-if="
                            slotProps.data.pelaksanaisilaporanrepairfk != null &&
                            slotProps.data.jenisorder == 'repair'
                          " color="warning" outlined circle icon="feather:printer"
                            @click="cetakLaporanRepair(slotProps.data)" />
                        </template>
                      </Column>

                      <Column header="Tanda Terima" style="min-width: 100px">
                        <template #body="slotProps">
                          <VIconButton class="mr-3" v-if="slotProps.data.iskaji !== null" color="danger" outlined circle
                            icon="feather:printer" @click="cetakTandaTerima(slotProps.data)" />
                        </template>
                      </Column>

                      <Column header="Permintaan Kalibrasi dan Kontrak" style="min-width: 200px">
                        <template #body="slotProps">
                          <VIconButton class="mr-3" v-if="
                            slotProps.data.iskaji !== null &&
                            slotProps.data.statusorder == 1
                          " color="danger" outlined circle icon="feather:printer"
                            @click="cetakPermintaanKalibrasi(slotProps.data)" />
                        </template>
                      </Column>

                      <Column header="AMS" style="min-width: 100px">
                        <template #body="slotProps">
                          <VIconButton class="mr-3" v-if="slotProps.data.iskaji !== null" color="danger" outlined circle
                            icon="feather:printer" @click="cetakAmsDashboard(slotProps.data)" />
                        </template>
                      </Column>

                      <Column header="Tanda Selesai Terima" style="min-width: 160px">
                        <template #body="slotProps">
                          <VIconButton class="mr-3" v-if="slotProps.data.isSimpanTerima == true" color="danger" outlined
                            circle icon="feather:printer" @click="cetakSelesaaiTerima(slotProps.data)" />
                        </template>
                      </Column>
                      <Column header="Surat Perintah Kerja" style="min-width: 160px">
                        <template #body="slotProps">
                          <VIconButton class="mr-3" v-if="slotProps.data.iskaji !== null" color="danger" outlined circle
                            icon="feather:printer" @click="cetakSpk(slotProps.data)" />
                        </template>
                      </Column>
                    </DataTable>
                  </div>
                </template>
              </DataTable>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VCard>

  <!-- MODAL DOKUMEN -->
  <Dialog v-model:visible="modalDokumen" modal :header="item.id ? 'Ubah ' : 'Tambah'" :style="{ width: '30vw' }">
    <div class="columns is-multiline">
      <div class="column is-12">
        <VField label="Dokumen">
          <VControl icon="feather:bookmark">
            <input v-model="item.namadokumen" type="text" class="input is-rounded" placeholder="Dokumen" />
          </VControl>
        </VField>
      </div>
      <div class="column is-4">
        <VField class="is-rounded-select is-autocomplete-select">
          <VLabel>Status</VLabel>
          <VControl>
            <VSwitchBlock v-model="item.statusenabled" :options="d_status" label="Aktif" color="danger" />
          </VControl>
        </VField>
      </div>
    </div>
    <template #footer>
      <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="tutup()">
        Tutup
      </VButton>
      <VButton type="button" rounded outlined color="primary" raised icon="feather:save" :loading="isLoading"
        @click="simpan()">
        {{ item.id ? 'Ubah' : 'Simpan' }}
      </VButton>
    </template>
  </Dialog>

  <!-- MODAL ISI DOKUMEN -->
  <Dialog v-model:visible="modalIsiDokumen" modal :header="item.id ? 'Ubah ' + item.namaisidokumen : 'Tambah'"
    :style="{ width: '30vw' }">
    <div class="columns is-multiline">
      <div class="column is-12">
        <VField label="Head" class="is-rounded-select is-autocomplete-select mt-0 pt-0" v-slot="{ id }">
          <VControl icon="fas fa-archway" fullwidth class="prime-auto-select">
            <Dropdown v-model="item.kdrinciandokumenhead" :options="d_ObjectModul" :optionLabel="'label'"
              class="is-rounded" placeholder="Head" style="width: 100%;" :filter="true" showClear
              @change="changeHead($event)" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Nama Isi Dokumen">
          <VControl icon="feather:bookmark">
            <input v-model="item.namaisidokumen" type="text" class="input is-rounded" placeholder="Nama Isi Dokumen" />
          </VControl>
        </VField>
      </div>

      <!-- RIWAYAT DOKUMEN -->
      <div class="column is-12" v-if="riwayat.length && item.fileLama != null">
        <VField label="Riwayat Dokumen">
          <div class="buttons is-multiline">
            <VButton v-for="r in riwayat" :key="r.id" class="mr-2 mb-2" :color="r.id === item.id ? 'info' : 'primary'"
              outlined rounded icon="feather:eye" :title="formatDateID(r.tglrevisi)" @click="cetakDokumenById(r.id)">
              Revisi {{ pad2(r.revisike) }}
              <span class="is-size-7 has-text-grey ml-2">
                ({{ formatDateID(r.tglrevisi) }})
              </span>
            </VButton>
          </div>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Upload Isi Dokumen">
          <FileUpload v-model="fileDokumenMutu" mode="advanced" name="demo"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" :maxFileSize="10000000" outlined
            :invalidFileTypeMessage="'{0}: Format file tidak diizinkan.'"
            :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
            style="background-color: transparent; color: var(--danger); border: 1px solid;"
            :chooseLabel="isFile(fileDokumenMutu) ? fileDokumenMutu.name : item.fileLama || 'Unggah File'"
            @select="onSelect($event)" class="is-rounded w-100" />
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Keterangan">
          <VControl icon="feather:bookmark">
            <input v-model="item.keterangan" type="text" class="input is-rounded" placeholder="Keterangan " />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="No Urut">
          <VControl icon="feather:bookmark">
            <input v-model="item.nourut" type="number" class="input is-rounded" placeholder="No Urut" />
          </VControl>
        </VField>
      </div>
    </div>

    <template #footer>
      <VButton v-if="item.id" type="button" rounded outlined color="danger" raised icon="feather:trash"
        :loading="isLoading" @click="deleteIsiDokumen(item)">
        Hapus
      </VButton>
      <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="tutup()">
        Tutup
      </VButton>
      <VButton type="button" rounded outlined color="primary" raised icon="feather:save" :loading="isLoading"
        @click="saveIsiDokumen()">
        {{ item.id ? 'Ubah' : 'Simpan' }}
      </VButton>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import Dialog from 'primevue/dialog'
import { useToaster } from '/@src/composable/toaster'
import Tree from 'primevue/tree'
import Dropdown from 'primevue/dropdown'
import * as H from '/@src/utils/appHelper'
import FileUpload from 'primevue/fileupload'
import ProgressBar from 'primevue/progressbar'
import AutoComplete from 'primevue/autocomplete'

useHead({ title: 'Dokumen Mutu - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const toast = useToaster()
const item: any = ref({ statusenabled: true, aktif: true })

const modalIsiDokumen = ref(false)
const modalInput = ref(false)
const modalDokumen = ref(false)
const modalDetail = ref(false)

const expandedKeys: any = ref({})
const selectedKey = ref(null)
const selectedIDModul = ref('')
const selectedHead = ref('')
let dataSource: any = ref([])
let dataSourceSub: any = ref([])
let dataSourceMenu: any = ref([])
let d_ObjectModul: any = ref([])
const fileDokumenMutu: any = ref()
const d_status = [
  { value: 't', label: 'True' },
  { value: 'f', label: 'False' },
]
const selectView: any = ref('list')
let isLoading: any = ref(false)
const currentPage: any = ref({ limit: 5, rows: 50 })
const d_lokasikalibrasi = ref([])
const d_unit = ref([])
const ulab = ref<any[]>([])
let isPlaceLoad: any = ref(false)
const expandedRows = ref(null)

const cetakSertifikatLembarKerja = (e: any) => {
  H.printBlade(
    `asman/cetak-sertifikat-lembar-kerja?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`,
  )
}

const cetakLaporanVerfikasi = (e: any) => {
  H.printBlade(
    `asman/cetak-laporan-verifikasi?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`,
  )
}

const cetakLaporanRepair = (e: any) => {
  H.printBlade(
    `asman/cetak-laporan-repair?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`,
  )
}

const cetakTandaTerima = (e: any) => {
  H.printBlade(`registrasi/cetak-tanda-terima?pdf=true&norec=${e.norec}`)
}

const cetakAmsDashboard = (e: any) => {
  H.printBlade(`registrasi/cetak-ams?norecregis=${e.norec}`)
}

const cetakSelesaaiTerima = (e: any) => {
  H.printBlade(`registrasi/cetak-selesai-terima?pdf=true&norec=${e.norec}`)
}

const cetakSpk = (e: any) => {
  console.log(e)
  H.printBlade(`asman/cetak-spk?pdf=true&norec=${e.norec}&penyeliateknikfk=${e.penyeliateknikfk}`);
}

const cetakPermintaanKalibrasi = (e: any) => {
  H.printBlade(`registrasi/cetak-permintaan-kalibrasi?pdf=true&norec=${e.norec}`)
}

const optionsKelompokLayanan: any = [
  { value: 'kalibrasi', label: 'Kalibrasi' },
  { value: 'repair', label: 'Repair' },
]

const fetchLokasi = async (filter: any) => {
  await useApi()
    .get(
      `general/dropdown/lokasikalibrasi_m?select=id,lokasi&param_search=lokasi&query=${filter.query}&limit=10`,
    )
    .then((response) => {
      d_lokasikalibrasi.value = response
    })
}

const fetchUnit = async (filter: any) => {
  await useApi()
    .get(
      `general/dropdown/mitra_m?select=id,namaperusahaan&param_search=namaperusahaan&query=${filter.query}&limit=10`,
    )
    .then((response) => {
      d_unit.value = response
    })
}

const fetchMonitoring = async () => {
  let search = item.value.search ? `search=${item.value.search}` : ''
  // FIX: lokasifk typo
  let lokasikalibrasi = item.value.lokasifk ? `&lokasifk=${item.value.lokasifk.value}` : ''
  let unitfk = item.value.unitfk ? `&unitfk=${item.value.unitfk.value}` : ''
  // FIX: jenisorder ambil value dari object Multiselect
  let jenisorder = item.value.jenisorder ? `&jenisorder=${item.value.jenisorder.value}` : ''

  try {
    isPlaceLoad.value = true
    const res = await useApi().get(
      `/laporan/get-laporan-monitoring?${search}${lokasikalibrasi}${unitfk}${jenisorder}`,
    )
    ulab.value = (res.detail || []).map((r: any) => ({
      ...r,
      lokasi:
        String(r.jenisorder || '').toLowerCase() === 'repair'
          ? r.lokasirepair || ''
          : r.lokasikalibrasi || '',
    }))
    isPlaceLoad.value = false
  } catch (e) {
    isPlaceLoad.value = false
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data.' })
  }
}

const stepPercent = (step: any): number => {
  const s = Math.min(5, Math.max(1, Number(step || 1)))
  return Math.round(((s - 1) / 4) * 100)
}

const riwayat: any = ref([])

const cetakDokumen = (e: any) => {
  if (!e.id) {
    H.alert('warning', 'Data tidak valid')
    return
  }
  H.printBlade(`sysadmin/cetak-dokumen-mutu?id=${e.id}`)
}

// cetak by id (untuk tombol di riwayat)
const cetakDokumenById = (id: any) => {
  if (!id) {
    H.alert('warning', 'Data tidak valid')
    return
  }
  H.printBlade(`sysadmin/cetak-dokumen-mutu?id=${id}`)
}

const isFile = (obj: any): obj is File => {
  return (
    obj instanceof File ||
    (obj && typeof obj === 'object' && 'name' in obj && 'size' in obj && 'type' in obj)
  )
}

const onSelect = async (filez: any) => {
  const file = filez.files[0]
  if (!file) return

  const allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png',
    'image/webp',
  ]
  if (file.size > 10000000) {
    H.alert('error', 'Maksimal ukuran file adalah 10 MB')
    return
  }
  if (!allowedTypes.includes(file.type)) {
    H.alert('error', 'File yang diizinkan: PDF, Word, Excel, JPG, PNG, atau WebP')
    return
  }
  fileDokumenMutu.value = file
}

const filters = ref('')
const filters2 = ref('') // FIX: filter terpisah untuk rincian

const dataSourcefiltered = computed(() => {
  if (!filters.value) return dataSource.value
  return dataSource.value.filter((items: any) =>
    items.namadokumen.match(new RegExp(filters.value, 'i')),
  )
})
const dataSourcefilteredSub = computed(() => {
  if (!filters2.value) return dataSourceSub.value
  return dataSourceSub.value.filter((items: any) =>
    items.namadokumen.match(new RegExp(filters2.value, 'i')),
  )
})

const expandAll = () => {
  for (let node of dataSourceMenu.value) expandNode(node)
  expandedKeys.value = { ...expandedKeys.value }
}
const collapseAll = () => {
  expandedKeys.value = {}
}
const expandNode = (node: any) => {
  if (node.children && node.children.length) {
    expandedKeys.value[node.key] = true
    for (let child of node.children) expandNode(child)
  }
}

const selectedModul = async (e: any) => {
  selectedHead.value = e.id
  loadSub()
}
const loadSub = async () => {
  const response = await useApi().get('/sysadmin/master-dokumen-by-head?id=' + selectedHead.value)
  dataSourceSub.value = response
}
const selectedSub = (e: any) => {
  selectedIDModul.value = e.id
  loadTree()
}
const loadTree = async () => {
  const response = await useApi().get('/sysadmin/master-isi-dokumen?id=' + selectedIDModul.value)
  dataSourceMenu.value = response.tree
  d_ObjectModul.value = response.data
}

const formatDateID = (v: any) => {
  if (!v) return '-'
  const d = new Date(v)
  if (isNaN(d as any)) return v
  return d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
}

// load riwayat revisi untuk sebuah item id
const loadRiwayat = async (id: any) => {
  try {
    const res = await useApi().get('/sysadmin/riwayat-isi-dokumen?id=' + id)
    riwayat.value = res.riwayat || []
  } catch (e) {
    riwayat.value = []
  }
}

const onNodeSelect = async (node: any) => {
  d_ObjectModul.value.forEach((element: any) => {
    if (element.key == node.parent_id) {
      item.value.kdrinciandokumenhead = element
    }
  })

  item.value.id = node.key
  item.value.namaisidokumen = node.label
  item.value.fungsi = node.data.fungsi
  item.value.nourut = node.nourut
  item.value.keterangan = node.data.keterangan
  item.value.alamaturlform = node.data.alamaturlform
  item.value.fileLama = node.data.isidokumen

  await loadRiwayat(node.key)

  modalIsiDokumen.value = true
  toast.success(node.label)
}

const tutup = () => {
  clear()
  modalIsiDokumen.value = false
}

const saveIsiDokumen = async () => {
  if (!item.value.namaisidokumen) {
    H.alert('error', 'Nama Isi Dokumen harus di isi')
    return
  }
  if (!item.value.nourut) {
    H.alert('error', 'No Urut harus di isi')
    return
  }

  const formData = new FormData()
  if (isFile(fileDokumenMutu.value)) {
    formData.append('fileDokumenMutu', fileDokumenMutu.value)
  } else if (item.value.fileLama) {
    formData.append('namaFileLama', item.value.fileLama)
  }
  formData.append('id', item.value.id ? item.value.id : '')
  formData.append('keterangan', item.value.keterangan ? item.value.keterangan : '')
  formData.append('namaisidokumen', item.value.namaisidokumen ? item.value.namaisidokumen : null)
  formData.append('nourut', item.value.nourut ? item.value.nourut : null)
  formData.append('kdrinciandokumenhead', item.value.kdrinciandokumenhead?.key ?? '')
  formData.append('rincianfk', selectedIDModul.value)

  isLoading.value = true
  await useApi().post(`/sysadmin/save-isi-dokumen-map`, formData).then(
    async () => {
      await loadTree()
      clear()
      setNoUrut()
      isLoading.value = false
      modalIsiDokumen.value = false
    },
    () => {
      isLoading.value = false
    },
  )
}

const deleteIsiDokumen = async (itemx: any) => {
  isLoading.value = true
  await useApi()
    .post(`/sysadmin/hapus-isi-dokumen`, { id: itemx.id })
    .then(
      async () => {
        await loadTree()
        clear()
        setNoUrut()
        isLoading.value = false
        modalIsiDokumen.value = false
      },
      () => {
        isLoading.value = false
      },
    )
}

function add() {
  clear()
  setNoUrut()
  modalIsiDokumen.value = true
  fileDokumenMutu.value = null
}

const changeHead = async () => {
  setNoUrut()
}
const setNoUrut = async () => {
  if (d_ObjectModul.value.length == 0) {
    const response = await useApi().get('/sysadmin/master-modul-aplikasi-nourut')
    item.value.nourut = response.nourut + 1
  } else {
    item.value.nourut = d_ObjectModul.value.length
      ? d_ObjectModul.value[d_ObjectModul.value.length - 1].nourut + 1
      : 0
  }
}

const route = useRoute()
async function fetchData() {
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = offset * limit - limit
  let rows: any = currentPage.value.rows
  let namadokumen = ''
  let ReportDisplay = ''
  let kdModulAplikasi = ''
  let StatusEnabled = ''

  if (item.namadokumen) namadokumen = '&namadokumen' + item.namadokumen
  if (item.reportdisplay) ReportDisplay = '&reportdisplay=' + item.kdreportdisplay
  if (item.kdmodulaplikasi) kdModulAplikasi = '&kdmodulaplikasi=' + item.kdmodulaplikasi
  if (item.value.aktif) StatusEnabled = '&statusenabled=' + item.value.aktif

  isLoading.value = true
  const response = await useApi().get(
    '/sysadmin/master-dokumen?offset=' +
    offset +
    '&limit=' +
    limit +
    '&rows=' +
    rows +
    kdModulAplikasi +
    namadokumen +
    ReportDisplay +
    StatusEnabled,
  )
  isLoading.value = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x]
    element.no = x + 1
  }
  dataSource.value = response.data
}
function loadData() {
  fetchData()
}

const edit = (e: any) => {
  item.value.id = e.id
  item.value.namadokumen = e.namadokumen
  item.value.reportdisplay = e.reportdisplay
  item.value.statusenabled = e.statusenabled
  modalDokumen.value = true
}
const tambah = (jenis: any) => {
  clear()
  item.value.reportdisplay = jenis
  modalDokumen.value = true
}

async function simpan() {
  if (!item.value.namadokumen) {
    useToaster().error('Nama Dokumen harus di isi')
    return
  }
  const objSave = {
    namadokumen: {
      id: item.value.id ? item.value.id : '',
      namadokumen: item.value.namadokumen,
      reportdisplay: item.value.reportdisplay ? item.value.reportdisplay : null,
      statusenabled: item.value.statusenabled ? item.value.statusenabled : true,
      kddokumenhead: selectedHead.value != '' ? selectedHead.value : null,
    },
  }
  isLoading.value = true
  await useApi().post(`/sysadmin/save-dokumen`, objSave).then(
    (response: any) => {
      isLoading.value = false
      if (item.value.reportdisplay == 'Rincian') {
        loadSub()
      }
      if (item.value.reportdisplay == 'Dokumen') {
        fetchData()
      }
      clear()
      modalDokumen.value = false
    },
    () => {
      isLoading.value = false
    },
  )
}

async function deleterow(e: any) {
  await useApi().post(`/sysadmin/delete-rincian-dokumen`, { id: e.id }).then(() => {
    loadData()
    loadSub()
  })
}

function clear() {
  item.value = { statusenabled: true, aktif: true }
  modalInput.value = false
  fileDokumenMutu.value = null
  riwayat.value = []
}
function changeView(e: any) {
  selectView.value = e
}
function changeSwitch() {
  loadData()
}
function filter() {
  fetchData()
}
function clearFilter() {
  fetchData()
}
filter()
fetchData()
fetchMonitoring()

const pad2 = (n: any) => String(n ?? 1).padStart(2, '0')
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';

.tile-grid {
  .columns {
    margin-left: -0.5rem !important;
    margin-right: -0.5rem !important;
    margin-top: -0.5rem !important;
  }

  .column {
    padding: 0.5rem !important;
  }
}

.is-dark {
  .tile-grid {
    .tile-grid-item {
      @include vuero-card--dark;
    }
  }
}

.list-view-v3 {
  .list-view-item {
    @include vuero-r-card;
    margin-bottom: 16px;
    padding: 16px;

    .list-view-item-inner {
      display: flex;
      align-items: center;

      >img {
        width: 100%;
        max-width: 60px;
        min-width: 60px;
        max-height: 60px;
        min-height: 60px;
        border-radius: var(--radius-rounded);
        border: 1px solid var(--fade-grey);
      }

      .meta-left {
        margin-left: 16px;

        h3 {
          font-family: var(--font-alt);
          color: var(--dark-text);
          font-weight: 500;
          font-size: 1.1rem;
          line-height: 1;
        }

        >span:not(.tag) {
          font-size: 0.9rem;
          color: var(--light-text);

          svg {
            position: relative;
            top: 1px;
            height: 12px;
            width: 12px;
          }

          .icon-separator {
            position: relative;
            top: -3px;
            font-size: 5px;
            color: var(--light-text);
            padding: 0 8px;
          }

          .iconify {
            margin-right: 0.25rem;
          }
        }
      }

      .meta-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        justify-content: flex-end;

        .buttons {
          margin-bottom: 0;
          margin-right: 10px;
        }
      }
    }
  }
}

.fs-075 {
  font-size: 0.9rem;
}

.is-navbar {
  .form-layout {
    margin-top: 30px;
  }
}

.form-layout {
  margin: 0 auto;

  &.is-separate {
    .form-outer {
      background: none;
      border: none;

      .form-body {
        display: flex;

        .form-section {
          flex-grow: 2;
          padding: 10px;
          width: 50%;

          .form-section-inner {
            @include vuero-s-card;
            padding: 40px;

            &.has-padding-bottom {
              padding-bottom: 60px;
              height: 100%;
            }

            >h3 {
              font-family: var(--font-alt);
              font-size: 1.2rem;
              font-weight: 600;
              color: var(--dark-text);
              margin-bottom: 30px;
            }

            .columns {
              .column {
                padding-top: 0.25rem;
                padding-bottom: 0.25rem;
              }
            }

            .radio-boxes {
              display: flex;
              justify-content: space-between;
              margin-left: -8px;
              margin-right: -8px;

              .radio-box {
                position: relative;
                width: calc(50% - 16px);
                margin: 8px;

                &:focus-within {
                  border-radius: 3px;
                  outline-offset: var(--accessibility-focus-outline-offset);
                  outline-width: var(--accessibility-focus-outline-width);
                  outline-style: var(--accessibility-focus-outline-style);
                  outline-color: var(--primary);
                }

                input {
                  position: absolute;
                  top: 0;
                  left: 0;
                  height: 100%;
                  width: 100%;
                  opacity: 0;
                  cursor: pointer;

                  &:checked {
                    +.radio-box-inner {
                      background: var(--primary);
                      border-color: var(--primary);
                      box-shadow: var(--primary-box-shadow);

                      .fee,
                      p {
                        color: var(--smoke-white);
                      }
                    }
                  }
                }

                .radio-box-inner {
                  background: var(--white);
                  border: 1px solid var(--fade-grey-dark-3);
                  text-align: center;
                  border-radius: var(--radius);
                  font-family: var(--font);
                  font-weight: 600;
                  font-size: 0.9rem;
                  transition: color .3s, background-color .3s, border-color .3s, height .3s, width .3s;
                  padding: 30px 20px;

                  .fee {
                    font-family: var(--font);
                    font-weight: 700;
                    color: var(--dark-text);
                    font-size: 2.4rem;
                    line-height: 1;

                    span {
                      &::after {
                        content: '$';
                        position: relative;
                        top: -10px;
                        font-size: 1.5rem;
                      }
                    }
                  }

                  p {
                    font-family: var(--font-alt);
                  }
                }
              }
            }

            .control {
              >p {
                padding-top: 12px;

                >span {
                  display: block;
                  font-size: 0.9rem;

                  span {
                    font-weight: 500;
                    color: var(--dark-text);
                  }
                }
              }
            }
          }

          .form-section-outer {
            .checkboxes {
              padding: 16px 0;

              .checkbox {
                padding: 0;
                font-size: 0.9rem;
              }
            }

            .button-wrap {
              .button {
                min-height: 60px;
                font-size: 1.05rem;
                font-weight: 600;
                font-family: var(--font-alt);
              }
            }
          }
        }
      }
    }
  }
}

.is-dark {
  .form-layout {
    &.is-separate {
      .form-outer {
        background: none !important;

        .form-body {
          .form-section {
            .form-section-inner {
              @include vuero-card--dark;

              >h3 {
                color: var(--dark-dark-text);
              }

              .radio-boxes {
                .radio-box {
                  input:checked+.radio-box-inner {
                    background: var(--primary);
                    border-color: var(--primary);
                    box-shadow: var(--primary-box-shadow);

                    .fee,
                    p {
                      color: var(--smoke-white);
                    }
                  }

                  .radio-box-inner {
                    background: var(--dark-sidebar-light-2);
                    border-color: var(--dark-sidebar-light-12);

                    .fee {
                      color: var(--dark-dark-text);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}

@media only screen and (max-width: 767px) {
  .form-layout {
    &.is-separate {
      .form-outer {
        .form-body {
          padding-left: 0;
          padding-right: 0;
          flex-direction: column;

          .form-section {
            width: 100%;

            .form-section-inner {
              padding: 30px;
            }
          }
        }
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
  .form-layout {
    &.is-separate {
      .form-outer {
        .form-body {
          padding-left: 0;
          padding-right: 0;

          .form-section {
            .form-section-inner {
              padding: 30px;
            }
          }
        }
      }
    }
  }
}

.all-projects {
  .all-projects-header {
    display: flex;
    padding: 20px;
    background: var(--white);
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: var(--radius-large);
    margin-bottom: 1.5rem;

    .header-item {
      width: 25%;
      border-right: 1px solid var(--fade-grey-dark-3);

      &:last-child {
        border-right: none;
      }

      .item-inner {
        text-align: center;

        .lnil,
        .lnir {
          font-size: 2.2rem;
          margin-bottom: 6px;
          color: var(--primary);
        }

        span {
          display: block;
          font-family: var(--font);
          font-weight: 600;
          font-size: 1.4rem;
          color: var(--dark-text);
        }

        p {
          font-family: var(--font-alt);
        }
      }
    }
  }

  .projects-card-grid {
    .grid-item {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 220px;
      padding: 20px;
      background: var(--white);
      border: 1px solid var(--fade-grey-dark-3);
      border-radius: var(--radius-large);

      .top-section {
        .head {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 8px;

          h3 {
            font-size: 1rem;
            font-family: var(--font-alt);
            color: var(--dark-text);
            font-weight: 600;
          }
        }

        .body {
          p {
            font-family: var(--font);
            color: var(--light-text);
          }
        }
      }

      .bottom-section {
        display: flex;

        .foot-block {
          margin-right: 30px;

          .heading {
            font-family: var(--font-alt);
            font-size: 0.75rem;
            color: var(--light-text-dark-22);
          }

          >p {
            padding-top: 5px;
          }

          .developers {
            display: flex;

            .v-avatar {
              margin-right: 6px;
            }
          }
        }
      }
    }
  }
}

.heading {
  font-family: var(--font-alt);
  font-size: 0.75rem;
  color: var(--light-text-dark-22);
}

.is-dark {
  .all-projects {
    .all-projects-header {
      background: var(--dark-sidebar-light-6);
      border-color: var(--dark-sidebar-light-12);

      .header-item {
        border-color: var(--dark-sidebar-light-18);

        span {
          color: var(--dark-dark-text);
        }

        i {
          color: var(--primary) !important;
        }
      }
    }

    .projects-card-grid {
      .grid-item {
        background: var(--dark-sidebar-light-6);
        border-color: var(--dark-sidebar-light-12);

        .top-section {
          .head {
            h3 {
              color: var(--dark-dark-text);
            }
          }
        }

        .bottom-section {
          .foot-block {
            .heading {
              color: var(--light-text-dark-12);
            }
          }
        }
      }
    }
  }
}

.custom-tree {
  .p-tree-container {
    padding-left: 0 !important;
  }

  /* Hilangkan ikon bawaan PrimeVue supaya tidak dobel */
  .p-tree-node-icon {
    display: none !important;
  }

  /* Indent anak + garis bantu vertikal */
  .p-treenode-children {
    margin-left: 1.25rem;
    padding-left: 0.25rem;
    border-left: 1px dashed var(--fade-grey, #dde2eb);
  }

  .p-treenode-content {
    padding: 0.15rem 0.25rem;
  }

  .tree-node-label {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
  }

  .tree-node-label.is-root-head {
    font-weight: 600;
    color: var(--dark-text);
  }

  .tree-node-label.is-child-node {
    font-weight: 400;
    color: var(--light-text);
    margin-left: 0.1rem;
  }

  .head-icon {
    font-size: 0.9rem;
  }

  .child-icon {
    font-size: 0.85rem;
  }
}
</style>
