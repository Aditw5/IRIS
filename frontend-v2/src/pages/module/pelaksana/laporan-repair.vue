<template>
  <div class="column">
    <div class="business-dashboard hr-dashboard">
      <div class="columns is-multiline">
        <div class="column is-12 p-0">
          <div class="block-header">
            <div class="left column is-3 p-0">
              <div class="current-user">
                <h3>{{ item.namaproduk }}</h3>
              </div>
            </div>

            <div class="Center column is-3 p-0">
              <div>
                <h4 class="block-heading">Merk</h4>
                <p class="block-hext">{{ item.namamerk }}</p>

                <h4 class="block-heading">Tipe</h4>
                <p class="block-hext">{{ item.namatipe }}</p>
              </div>
            </div>

            <div class="right column is-6 p-0">
              <div>
                <h4 class="block-heading">S/N</h4>
                <p class="block-hext">{{ item.namaserialnumber }}</p>

                <h4 class="block-heading">No Order</h4>
                <p class="block-hext">{{ item.noorderalat }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="column is-12">
    <TabView class="tabview-custom" :scrollable="true" @tab-click="klikTab($event)">
      <TabPanel>
        <template #header>
          <i class="fas fa-tools mr-2" aria-hidden="true"></i>
          <span>FMMO-163-14.4.3.b-71.7 Investigasi Kerusakan Alat Pelanggan</span>
          <Badge :value="totalData" v-if="totalData > 0" severity="danger" class="ml-2" />
        </template>

        <VCard class="repair-form-card">
          <div class="column is-12">
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Laporan Alat</h3>
              </div>
            </div>

            <div class="alat-report-grid">
              <div class="alat-report-copy">
                <span class="section-eyebrow">Keterangan Alat</span>
                <div class="alat-keterangan">
                  {{ item.keterangan || 'Belum ada keterangan dari kajian ulang.' }}
                </div>
              </div>

              <div class="alat-photo-panel">
                <img v-if="MARKINGSITE" :src="MARKINGSITE" alt="Foto alat repair" @error="onMarkingImageError" />
                <div v-else class="alat-photo-placeholder">
                  Foto alat belum tersedia
                </div>
              </div>
            </div>

            <div class="columns is-multiline">
              <div class="column is-12">
                <Fieldset legend="- STATUS KEBERHASILAN REPAIR" :toggleable="true">
                  <div class="column is-3">
                    <VField>
                      <AutoComplete v-model="item.statusrepair" :suggestions="d_status" @complete="fetchStatus($event)"
                        :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                        :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
                    </VField>
                  </div>
                </Fieldset>
              </div>

              <div class="column is-12">
                <Fieldset legend="- STATUS ALAT" :toggleable="true">
                  <div style="overflow-y:auto;" class="mt-5 form-section-inner is-horizontal">
                    <table border="1" width="100%">
                      <thead>
                        <tr class="tr-po">
                          <th class="th-po" width="24%" style="vertical-align:inherit;text-align:center;">
                            Bagian Alat
                          </th>
                          <th class="th-po" width="35%" style="vertical-align:inherit;text-align:center;">
                            Dokumentasi
                          </th>
                          <th class="th-po" width="30%" style="vertical-align:inherit;text-align:center;">
                            Kondisi
                          </th>
                          <th class="th-po action-cell" width="11%" style="vertical-align:inherit;text-align:center;">
                            Aksi
                          </th>
                        </tr>
                      </thead>

                      <tbody v-for="(items, index) in item.detailLaporanStatus" :key="'status-' + index">
                        <tr class="tr-po">
                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <CKEditor v-model="items.bagianalatstatus" :editor="ClassicEditor"
                                    :config="repairEditorConfig" />
                                </VControl>
                              </VField>
                            </div>
                          </td>

                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <FileUpload mode="advanced" name="demo" accept="image/jpeg,image/jpg,image/png"
                                :multiple="true" :maxFileSize="10000000" :auto="false" :customUpload="true"
                                chooseLabel="Unggah Gambar" uploadLabel="Upload" cancelLabel="Cancel"
                                :invalidFileTypeMessage="'{0}: File yang diupload harus JPEG/JPG/PNG.'"
                                :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                                style="background-color: transparent; color: var(--danger); border: 1px solid;"
                                class="is-rounded w-100" @select="onSelectStatus($event, index)" />

                              <p class="upload-info-text">
                                * Dapat mengunggah lebih dari 1 gambar. Format yang diperbolehkan: JPG, JPEG, PNG.
                                Maksimal 10 MB per gambar.
                              </p>

                              <div class="mt-2">
                                <button type="button" class="button is-primary is-small"
                                  @click="openCameraModalStatus(index)">
                                  Buka Kamera
                                </button>
                              </div>

                              <div v-if="fileMitraStatus[index] && fileMitraStatus[index].length > 0"
                                class="repair-photo-list mt-3">
                                <div v-for="(file, fotoIndex) in fileMitraStatus[index]"
                                  :key="'status-file-' + index + '-' + fotoIndex + '-' + getFileKey(file)"
                                  class="repair-photo-item">
                                  <div class="repair-photo-name">
                                    {{ fotoIndex + 1 }}. {{ file.name }}
                                  </div>

                                  <VField>
                                    <VControl>
                                      <VInput v-model="captionMitraStatus[index][fotoIndex]"
                                        placeholder="Contoh: Gambar 1.1 Main Unit" />
                                    </VControl>
                                  </VField>

                                  <div class="buttons mt-1">
                                    <button type="button" class="button is-link is-small"
                                      @click="previewLocalFile(file)">
                                      Lihat
                                    </button>
                                    <button type="button" class="button is-danger is-small"
                                      @click="removeFotoStatus(index, fotoIndex)">
                                      Hapus
                                    </button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </td>

                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <CKEditor v-model="items.kondisistatus" :editor="ClassicEditor"
                                    :config="repairEditorConfig" />
                                </VControl>
                              </VField>
                            </div>
                          </td>

                          <td class="td-po action-cell" style="vertical-align:inherit;">
                            <div class="column is-12 pl-0 pr-0">
                              <VButtons class="repair-row-actions">
                                <VIconButton type="button" raised circle icon="feather:plus"
                                  v-tooltip-prime.bottom="'Tambah'" @click="addNewLaporanStatus()" outlined
                                  color="info" />
                                <VIconButton type="button" raised circle v-tooltip-prime.bottom="'Hapus'" outlined
                                  icon="feather:trash" @click="removeLaporanStatus(index)" color="danger" />
                              </VButtons>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </Fieldset>

                <br />

                <Fieldset legend="- TINDAKAN TEKNIS" :toggleable="true">
                  <div style="overflow-y:auto;" class="mt-5 form-section-inner is-horizontal">
                    <table border="1" width="100%">
                      <thead>
                        <tr class="tr-po">
                          <th class="th-po" width="25%" style="vertical-align:inherit;text-align:center;">
                            Bagian Alat
                          </th>
                          <th class="th-po" width="23%" style="vertical-align:inherit;text-align:center;">
                            Penanganan
                          </th>
                          <th class="th-po" width="18%" style="vertical-align:inherit;text-align:center;">
                            Status
                          </th>
                          <th class="th-po" width="25%" style="vertical-align:inherit;text-align:center;">
                            Sparepart & Material Consumable
                          </th>
                          <th class="th-po action-cell" width="9%" style="vertical-align:inherit;text-align:center;">
                            Aksi
                          </th>
                        </tr>
                      </thead>

                      <tbody v-for="(items, index) in item.detailLaporanRepair" :key="'repair-' + index">
                        <tr class="tr-po">
                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl fullwidth>
                                  <CKEditor v-model="items.bagianalatlaporan" :editor="ClassicEditor"
                                    :config="repairEditorConfig" />
                                </VControl>
                              </VField>

                              <FileUpload mode="advanced" name="demo" accept="image/jpeg,image/jpg,image/png"
                                :multiple="true" :maxFileSize="10000000" :auto="false" :customUpload="true"
                                chooseLabel="Unggah Gambar" uploadLabel="Upload" cancelLabel="Cancel"
                                :invalidFileTypeMessage="'{0}: File yang diupload harus JPEG/JPG/PNG.'"
                                :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                                style="background-color: transparent; color: var(--danger); border: 1px solid;"
                                class="is-rounded w-100" @select="onSelectRepair($event, index)" />

                              <p class="upload-info-text">
                                * Dapat mengunggah lebih dari 1 gambar. Format yang diperbolehkan: JPG, JPEG, PNG.
                                Maksimal 10 MB per gambar.
                              </p>

                              <div class="mt-2">
                                <button type="button" class="button is-primary is-small"
                                  @click="openCameraModalRepair(index)">
                                  Buka Kamera
                                </button>
                              </div>

                              <div v-if="fileMitraRepair[index] && fileMitraRepair[index].length > 0"
                                class="repair-photo-list mt-3">
                                <div v-for="(file, fotoIndex) in fileMitraRepair[index]"
                                  :key="'repair-file-' + index + '-' + fotoIndex + '-' + getFileKey(file)"
                                  class="repair-photo-item">
                                  <div class="repair-photo-name">
                                    {{ fotoIndex + 1 }}. {{ file.name }}
                                  </div>

                                  <VField>
                                    <VControl>
                                      <VInput v-model="captionMitraRepair[index][fotoIndex]"
                                        placeholder="Contoh: Gambar 1.1 Main Unit" />
                                    </VControl>
                                  </VField>

                                  <div class="buttons mt-1">
                                    <button type="button" class="button is-link is-small"
                                      @click="previewLocalFile(file)">
                                      Lihat
                                    </button>
                                    <button type="button" class="button is-danger is-small"
                                      @click="removeFotoRepair(index, fotoIndex)">
                                      Hapus
                                    </button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </td>

                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <CKEditor v-model="items.penanganan" :editor="ClassicEditor"
                                    :config="repairEditorConfig" />
                                </VControl>
                              </VField>
                            </div>
                          </td>

                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <CKEditor v-model="items.status" :editor="ClassicEditor"
                                    :config="repairEditorConfig" />
                                </VControl>
                              </VField>
                            </div>
                          </td>

                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <CKEditor v-model="items.sparepart" :editor="ClassicEditor"
                                    :config="repairEditorConfig" />
                                </VControl>
                              </VField>
                            </div>
                          </td>

                          <td class="td-po action-cell" style="vertical-align:inherit;">
                            <div class="column is-12 pl-0 pr-0">
                              <VButtons class="repair-row-actions">
                                <VIconButton type="button" raised circle icon="feather:plus"
                                  v-tooltip-prime.bottom="'Tambah'" @click="addNewLaporanRepair()" outlined
                                  color="info" />
                                <VIconButton type="button" raised circle v-tooltip-prime.bottom="'Hapus'" outlined
                                  icon="feather:trash" @click="removeLaporanRepair(index)" color="danger" />
                              </VButtons>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </Fieldset>

                <br />

                <Fieldset legend="- HASIL REPAIR" :toggleable="true">
                  <div style="overflow-y:auto;" class="mt-5 form-section-inner is-horizontal">
                    <table border="1" width="100%">
                      <thead>
                        <tr class="tr-po">
                          <th class="th-po" width="38%" style="vertical-align:inherit;text-align:center;">
                            Hasil
                          </th>
                          <th class="th-po" width="36%" style="vertical-align:inherit;text-align:center;">
                            Dokumentasi
                          </th>
                          <th class="th-po" width="16%" style="vertical-align:inherit;text-align:center;">
                            Status
                          </th>
                          <th class="th-po action-cell" width="10%" style="vertical-align:inherit;text-align:center;">
                            Aksi
                          </th>
                        </tr>
                      </thead>

                      <tbody v-for="(items, index) in item.detailHasilRepair" :key="'hasil-' + index">
                        <tr class="tr-po">
                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <CKEditor v-model="items.hasil" :editor="ClassicEditor"
                                    :config="repairEditorConfig" />
                                </VControl>
                              </VField>
                            </div>
                          </td>

                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <FileUpload mode="advanced" name="demo" accept="image/jpeg,image/jpg,image/png"
                                :multiple="true" :maxFileSize="10000000" :auto="false" :customUpload="true"
                                chooseLabel="Unggah Gambar" uploadLabel="Upload" cancelLabel="Cancel"
                                :invalidFileTypeMessage="'{0}: File yang diupload harus JPEG/JPG/PNG.'"
                                :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                                style="background-color: transparent; color: var(--danger); border: 1px solid;"
                                class="is-rounded w-100" @select="onSelectHasil($event, index)" />

                              <p class="upload-info-text">
                                * Dapat mengunggah lebih dari 1 gambar. Format yang diperbolehkan: JPG, JPEG, PNG.
                                Maksimal 10 MB per gambar.
                              </p>

                              <div class="mt-2">
                                <button type="button" class="button is-primary is-small"
                                  @click="openCameraModalHasil(index)">
                                  Buka Kamera
                                </button>
                              </div>

                              <div v-if="fileMitraHasil[index] && fileMitraHasil[index].length > 0"
                                class="repair-photo-list mt-3">
                                <div v-for="(file, fotoIndex) in fileMitraHasil[index]"
                                  :key="'hasil-file-' + index + '-' + fotoIndex + '-' + getFileKey(file)"
                                  class="repair-photo-item">
                                  <div class="repair-photo-name">
                                    {{ fotoIndex + 1 }}. {{ file.name }}
                                  </div>

                                  <VField>
                                    <VControl>
                                      <VInput v-model="captionMitraHasil[index][fotoIndex]"
                                        placeholder="Contoh: Gambar 1.1 Main Unit" />
                                    </VControl>
                                  </VField>

                                  <div class="buttons mt-1">
                                    <button type="button" class="button is-link is-small"
                                      @click="previewLocalFile(file)">
                                      Lihat
                                    </button>
                                    <button type="button" class="button is-danger is-small"
                                      @click="removeFotoHasil(index, fotoIndex)">
                                      Hapus
                                    </button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </td>

                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <CKEditor v-model="items.status" :editor="ClassicEditor"
                                    :config="repairEditorConfig" />
                                </VControl>
                              </VField>
                            </div>
                          </td>

                          <td class="td-po action-cell" style="vertical-align:inherit;">
                            <div class="column is-12 pl-0 pr-0">
                              <VButtons class="repair-row-actions">
                                <VIconButton type="button" raised circle icon="feather:plus"
                                  v-tooltip-prime.bottom="'Tambah'" @click="addNewHasilRepair()" outlined
                                  color="info" />
                                <VIconButton type="button" raised circle v-tooltip-prime.bottom="'Hapus'" outlined
                                  icon="feather:trash" @click="removeHasilRepair(index)" color="danger" />
                              </VButtons>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </Fieldset>
              </div>
            </div>

            <div class="column is-12">
              <Fieldset legend="- KESIMPULAN" :toggleable="true">
                <div class="columns is-multiline">
                  <div class="column is-12">
                    <VField>
                      <VControl>
                        <CKEditor v-model="item.kesimpulan" :editor="ClassicEditor" :config="repairEditorConfig" />
                      </VControl>
                    </VField>
                  </div>
                </div>
              </Fieldset>
            </div>

            <div class="columns is-multiline">
              <div class="column is-12 is-flex is-justify-content-flex-end mt-4 repair-action-buttons">
                <VButton v-if="hasPrintableData" icon="feather:printer" @click="cetakLaporanRepair()"
                  color="primary" outlined>
                  Cetak Laporan
                </VButton>
                <VButton v-if="!item.laporanRepairSudahMasukReview" icon="feather:file-text" @click="Save('draft')"
                  :loading="isLoadingSave" color="warning" outlined>
                  Simpan Draft
                </VButton>
                <VButton icon="feather:send" @click="Save('final')" :loading="isLoadingSave" color="info">
                  Simpan Final
                </VButton>
              </div>
            </div>
          </div>
        </VCard>

        <hr />

        <VCard>
          <div class="column is-12">
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Status Alat Repair</h3>
              </div>
            </div>

            <DataTable :value="dataSourceHasilLaporanRepairStatus" dataKey="norec" :paginator="true" :rows="10"
              :rowsPerPageOptions="[5, 10, 25]" class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="column pt-0 pb-0" v-if="hasPrintableData">
                  <VButtons style="justify-content:space-between;">
                    <VButton color="primary" @click="cetakLaporanRepair()" outlined icon="feather:printer">
                      Cetak Laporan
                    </VButton>
                  </VButtons>
                </div>
              </template>

              <Column field="no" header="No" />
              <Column header="Bagian Alat">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.bagianalat)"></div>
                </template>
              </Column>
              <Column header="Kondisi">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.kondisi)"></div>
                </template>
              </Column>

              <Column header="Dokumentasi" style="text-align:center; min-width:300px">
                <template #body="slotProps">
                  <div v-if="getRowFotos(slotProps.data).length > 0" class="saved-photo-grid">
                    <div v-for="foto in getRowFotos(slotProps.data)" :key="foto.norec || foto.namafile"
                      class="saved-photo-item">
                      <img :src="getFotoUrl(foto.namafile)" class="saved-photo-img" @error="onImageError" />
                      <div class="saved-photo-caption">
                        {{ foto.keterangan_gambar || '-' }}
                      </div>
                    </div>
                  </div>
                  <span v-else class="text-muted">Tidak ada dokumentasi</span>
                </template>
              </Column>

              <Column field="created_at" header="Tanggal Isi Status" />
              <Column field="petugasisistatus" header="Pengisi" />

              <Column header="Aksi" style="text-align:center; width:96px; min-width:96px">
                <template #body="slotProps">
                  <div @click.stop.prevent>
                    <VButtons style="justify-content:center;">
                      <VIconButton color="info" outlined circle icon="fas fa-edit"
                        @click="openEditStatus(slotProps.data)" :loading="isLoadingSave" />
                      <VIconButton color="danger" outlined circle icon="fas fa-trash"
                        @click="hapusStatus(slotProps.data)" :loading="isLoadingSave" />
                    </VButtons>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </VCard>

        <hr />

        <VCard>
          <div class="column is-12">
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Tindakan Teknis Repair</h3>
              </div>
            </div>

            <DataTable :value="dataSourceHasilLaporanRepair" dataKey="norec" :paginator="true" :rows="10"
              :rowsPerPageOptions="[5, 10, 25]" class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="column pt-0 pb-0" v-if="hasPrintableData">
                  <VButtons style="justify-content:space-between;">
                    <VButton color="primary" @click="cetakLaporanRepair()" outlined icon="feather:printer">
                      Cetak Laporan
                    </VButton>
                  </VButtons>
                </div>
              </template>

              <Column field="no" header="No" />
              <Column header="Bagian Alat">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.bagianalatlaporan)"></div>
                </template>
              </Column>
              <Column header="Penanganan">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.penanganan)"></div>
                </template>
              </Column>
              <Column header="Status">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.status)"></div>
                </template>
              </Column>
              <Column header="Sparepart">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.sparepart)"></div>
                </template>
              </Column>

              <Column header="Foto Repair" style="text-align:center; min-width:300px">
                <template #body="slotProps">
                  <div v-if="getRowFotos(slotProps.data).length > 0" class="saved-photo-grid">
                    <div v-for="foto in getRowFotos(slotProps.data)" :key="foto.norec || foto.namafile"
                      class="saved-photo-item">
                      <img :src="getFotoUrl(foto.namafile)" class="saved-photo-img" @error="onImageError" />
                      <div class="saved-photo-caption">
                        {{ foto.keterangan_gambar || '-' }}
                      </div>
                    </div>
                  </div>
                  <span v-else class="text-muted">Tidak ada dokumentasi</span>
                </template>
              </Column>

              <Column field="created_at" header="Tanggal Isi" />
              <Column field="petugasrepair" header="Pengisi" />

              <Column header="Aksi" style="text-align:center; width:96px; min-width:96px">
                <template #body="slotProps">
                  <div @click.stop.prevent>
                    <VButtons style="justify-content:center;">
                      <VIconButton color="info" outlined circle icon="fas fa-edit"
                        @click="openEditLaporan(slotProps.data)" :loading="isLoadingSave" />
                      <VIconButton color="danger" outlined circle icon="fas fa-trash"
                        @click="hapusLaporan(slotProps.data)" :loading="isLoadingSave" />
                    </VButtons>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </VCard>

        <hr />

        <VCard>
          <div class="column is-12">
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Hasil Repair</h3>
              </div>
            </div>

            <DataTable :value="dataSourceHasilRepair" dataKey="norec" :paginator="true" :rows="10"
              :rowsPerPageOptions="[5, 10, 25]" class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="column pt-0 pb-0" v-if="hasPrintableData">
                  <VButtons style="justify-content:space-between;">
                    <VButton color="primary" @click="cetakLaporanRepair()" outlined icon="feather:printer">
                      Cetak Laporan
                    </VButton>
                  </VButtons>
                </div>
              </template>

              <Column field="no" header="No" />
              <Column header="Hasil">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.hasil)"></div>
                </template>
              </Column>
              <Column header="Status">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.status)"></div>
                </template>
              </Column>

              <Column header="Dokumentasi" style="text-align:center; min-width:300px">
                <template #body="slotProps">
                  <div v-if="getRowFotos(slotProps.data).length > 0" class="saved-photo-grid">
                    <div v-for="foto in getRowFotos(slotProps.data)" :key="foto.norec || foto.namafile"
                      class="saved-photo-item">
                      <img :src="getFotoUrl(foto.namafile)" class="saved-photo-img" @error="onImageError" />
                      <div class="saved-photo-caption">
                        {{ foto.keterangan_gambar || '-' }}
                      </div>
                    </div>
                  </div>
                  <span v-else class="text-muted">Tidak ada dokumentasi</span>
                </template>
              </Column>

              <Column field="created_at" header="Tanggal Isi" />
              <Column field="petugashasilrepair" header="Pengisi" />

              <Column header="Aksi" style="text-align:center; width:96px; min-width:96px">
                <template #body="slotProps">
                  <div @click.stop.prevent>
                    <VButtons style="justify-content:center;">
                      <VIconButton color="info" outlined circle icon="fas fa-edit"
                        @click="openEditHasil(slotProps.data)" :loading="isLoadingSave" />
                      <VIconButton color="danger" outlined circle icon="fas fa-trash"
                        @click="hapusHasil(slotProps.data)" :loading="isLoadingSave" />
                    </VButtons>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </VCard>

        <hr />

        <VCard>
          <div class="columns is-multiline" style="text-align:center">
            <div class="column is-12">
              <h3 class="title is-5 mb-2 mr-1">Kesimpulan</h3>
            </div>
          </div>

          <div class="columns is-multiline">
            <div class="column is-12">
              <div class="rich-preview rich-preview-card" v-html="safeDisplayHtml(item.kesimpulan)"></div>
            </div>
          </div>

          <div class="columns is-multiline">
            <div class="column is-12">
              <VButtons style="justify-content:flex-end;">
                <VIconButton color="info" outlined circle icon="fas fa-edit" v-tooltip-prime.bottom="'Edit Kesimpulan'"
                  @click="openEditKesimpulan" :loading="isLoadingSave" />
              </VButtons>
            </div>
          </div>
        </VCard>
      </TabPanel>
    </TabView>
  </div>

  <VModal :open="modalCamera" :title="modalCameraTitle" :noclose="false" size="big" actions="right"
    @close="closeCameraModal">
    <template #content>
      <form class="modal-form">
        <div class="columns is-multiline">
          <div class="column is-12 has-text-centered">
            <div class="is-flex is-flex-direction-column is-align-items-center">
              <video ref="video" width="500" height="300" autoplay style="display:none;"></video>
              <canvas ref="canvas" style="display:none;"></canvas>

              <div class="buttons mt-4">
                <button type="button" class="button is-success" v-if="isCameraActive" @click="takePhoto">
                  Ambil Foto
                </button>
                <button type="button" class="button is-light" @click="closeCameraModal">
                  Tutup Kamera
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </template>

    <template #action></template>
  </VModal>

  <VModal :open="modalEdit" :title="modalEditTitle" :noclose="false" size="big" actions="right" @close="closeEditModal">
    <template #content>
      <form class="modal-form">
        <div class="columns is-multiline">
          <div class="column is-12" v-if="editType === 'status'">
            <VField>
              <VLabel>Bagian Alat</VLabel>
              <VControl>
                <CKEditor v-model="editForm.bagianalatstatus" :editor="ClassicEditor" :config="repairEditorConfig" />
              </VControl>
            </VField>
          </div>

          <div class="column is-12" v-if="editType === 'status'">
            <VField>
              <VLabel>Kondisi</VLabel>
              <VControl>
                <CKEditor v-model="editForm.kondisistatus" :editor="ClassicEditor" :config="repairEditorConfig" />
              </VControl>
            </VField>
          </div>

          <div class="column is-12" v-if="editType === 'laporan'">
            <VField>
              <VLabel>Bagian Alat</VLabel>
              <VControl>
                <CKEditor v-model="editForm.bagianalatlaporan" :editor="ClassicEditor" :config="repairEditorConfig" />
              </VControl>
            </VField>
          </div>

          <div class="column is-6" v-if="editType === 'laporan'">
            <VField>
              <VLabel>Penanganan</VLabel>
              <VControl>
                <CKEditor v-model="editForm.penanganan" :editor="ClassicEditor" :config="repairEditorConfig" />
              </VControl>
            </VField>
          </div>

          <div class="column is-6" v-if="editType === 'laporan'">
            <VField>
              <VLabel>Status</VLabel>
              <VControl>
                <CKEditor v-model="editForm.status" :editor="ClassicEditor" :config="repairEditorConfig" />
              </VControl>
            </VField>
          </div>

          <div class="column is-12" v-if="editType === 'laporan'">
            <VField>
              <VLabel>Sparepart & Material Consumable</VLabel>
              <VControl>
                <CKEditor v-model="editForm.sparepart" :editor="ClassicEditor" :config="repairEditorConfig" />
              </VControl>
            </VField>
          </div>

          <div class="column is-12" v-if="editType === 'hasil'">
            <VField>
              <VLabel>Hasil Repair</VLabel>
              <VControl>
                <CKEditor v-model="editForm.hasil" :editor="ClassicEditor" :config="repairEditorConfig" />
              </VControl>
            </VField>
          </div>

          <div class="column is-12" v-if="editType === 'hasil'">
            <VField>
              <VLabel>Status</VLabel>
              <VControl>
                <CKEditor v-model="editForm.status" :editor="ClassicEditor" :config="repairEditorConfig" />
              </VControl>
            </VField>
          </div>

          <div class="column is-12" v-if="editType === 'kesimpulan'">
            <VField>
              <VLabel>Kesimpulan</VLabel>
              <VControl>
                <CKEditor v-model="editForm.kesimpulan" :editor="ClassicEditor" :config="repairEditorConfig" />
              </VControl>
            </VField>
          </div>

          <div class="column is-12" v-if="editType !== 'kesimpulan'">
            <h4 class="title is-6 mb-2">Foto yang Sudah Diupload</h4>

            <div v-if="editExistingFotos.length > 0" class="saved-photo-grid edit-photo-grid">
              <div v-for="(foto, fotoIndex) in editExistingFotos" :key="foto.norec || foto.namafile || fotoIndex"
                class="saved-photo-item">
                <img :src="getFotoUrl(foto.namafile)" class="saved-photo-img" @error="onImageError" />

                <VField class="mt-2">
                  <VControl>
                    <VInput v-model="foto.keterangan_gambar" placeholder="Contoh: Gambar 1.1 Main Unit" />
                  </VControl>
                </VField>

                <button type="button" class="button is-danger is-small mt-2" @click="hapusFotoExisting(fotoIndex)">
                  Hapus Foto Ini
                </button>
              </div>
            </div>

            <div v-else class="notification is-warning is-light">
              Belum ada foto lama. Silakan tambah foto baru.
            </div>
          </div>

          <div class="column is-12" v-if="editType !== 'kesimpulan'">
            <h4 class="title is-6 mb-2">Tambah Foto Baru</h4>

            <FileUpload mode="advanced" name="demo" accept="image/jpeg,image/jpg,image/png" :multiple="true"
              :maxFileSize="10000000" :auto="false" :customUpload="true" chooseLabel="Tambah Gambar"
              uploadLabel="Upload" cancelLabel="Cancel"
              :invalidFileTypeMessage="'{0}: File yang diupload harus JPEG/JPG/PNG.'"
              :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
              style="background-color: transparent; color: var(--danger); border: 1px solid;" class="is-rounded w-100"
              @select="onSelectEditFoto($event)" />

            <p class="upload-info-text">
              * Dapat menambahkan lebih dari 1 gambar. Format yang diperbolehkan: JPG, JPEG, PNG. Maksimal 10 MB per
              gambar.
            </p>

            <div v-if="editNewFiles.length > 0" class="repair-photo-list mt-3">
              <div v-for="(file, fotoIndex) in editNewFiles"
                :key="'edit-new-file-' + fotoIndex + '-' + getFileKey(file)" class="repair-photo-item">
                <div class="repair-photo-name">
                  {{ fotoIndex + 1 }}. {{ file.name }}
                </div>

                <VField>
                  <VControl>
                    <VInput v-model="editNewCaptions[fotoIndex]" placeholder="Contoh: Gambar 1.1 Main Unit" />
                  </VControl>
                </VField>

                <div class="buttons mt-1">
                  <button type="button" class="button is-link is-small" @click="previewLocalFile(file)">
                    Lihat
                  </button>
                  <button type="button" class="button is-danger is-small" @click="removeEditNewFoto(fotoIndex)">
                    Hapus
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </template>

    <template #action>
      <VButton color="primary" :loading="isLoadingSave" @click="saveEditData">
        Simpan Perubahan
      </VButton>
    </template>
  </VModal>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { ref, computed } from 'vue'
import CKE from '@ckeditor/ckeditor5-vue'
import ClassicEditor from '@ckeditor/ckeditor5-build-classic'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import FileUpload from 'primevue/fileupload'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import AutoComplete from 'primevue/autocomplete'
import Fieldset from 'primevue/fieldset'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useApi } from '/@src/composable/useApi'
import { publicFileUrl, resolvePublicFileUrl } from '/@src/utils/publicFileUrl'

const CKEditor = CKE.component

useHead({
  title: 'Laporan Repair - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const router = useRouter()
const MARKINGSITE: any = ref('')
const MARKING_CANDIDATES = ref<string[]>([])
const markingCandidateIndex = ref(0)
const NOREC_DETAIL = useRoute().query.norec_detail as string
const repairEditorConfig = {
  toolbar: [
    'heading',
    '|',
    'bold',
    'italic',
    'bulletedList',
    'numberedList',
    'blockQuote',
    '|',
    'undo',
    'redo',
  ],
}

const isLoadingSave = ref(false)
const isLoading = ref(false)
const loadSearch: any = ref(false)

const dataSourceHasilLaporanRepair: any = ref([])
const dataSourceHasilLaporanRepairStatus: any = ref([])
const dataSourceHasilRepair: any = ref([])
const hasSavedReport = ref(false)
const printRegistrationNorec = ref('')

const d_status: any = ref([])

const item: any = ref({
  tglkalibrasi: new Date(),
  statusrepair: null,
  kesimpulan: '',
  laporanRepairSudahMasukReview: false,
  detailLaporanStatus: [
    {
      no: 1,
      bagianalatstatus: '',
      kondisistatus: '',
    },
  ],
  detailLaporanRepair: [
    {
      no: 1,
      bagianalatlaporan: '',
      penanganan: '',
      status: '',
      sparepart: '',
    },
  ],
  detailHasilRepair: [
    {
      no: 1,
      hasil: '',
      status: '',
    },
  ],
})

const fileMitraStatus = ref<File[][]>([[]])
const captionMitraStatus = ref<string[][]>([[]])

const fileMitraRepair = ref<File[][]>([[]])
const captionMitraRepair = ref<string[][]>([[]])

const fileMitraHasil = ref<File[][]>([[]])
const captionMitraHasil = ref<string[][]>([[]])

const video: any = ref(null)
const canvas: any = ref(null)

const modalCamera = ref(false)
const isCameraActive = ref(false)
const currentCameraType = ref<'status' | 'repair' | 'hasil' | null>(null)
const currentCameraIndex = ref<number | null>(null)

const modalEdit = ref(false)
const editType = ref<'status' | 'laporan' | 'hasil' | 'kesimpulan' | null>(null)
const editForm: any = ref({})
const editExistingFotos: any = ref([])
const editDeleteFotos: any = ref([])
const editNewFiles = ref<File[]>([])
const editNewCaptions = ref<string[]>([])

const totalData = computed(() => {
  return (
    dataSourceHasilLaporanRepair.value.length +
    dataSourceHasilLaporanRepairStatus.value.length +
    dataSourceHasilRepair.value.length
  )
})

const hasPrintableData = computed(() => {
  return hasSavedReport.value && !!printRegistrationNorec.value
})

const modalCameraTitle = computed(() => {
  if (currentCameraType.value === 'status') return 'Masukan Foto Status Alat'
  if (currentCameraType.value === 'repair') return 'Masukan Foto Tindakan Teknis'
  if (currentCameraType.value === 'hasil') return 'Masukan Foto Hasil Repair'
  return 'Masukan Foto'
})

const modalEditTitle = computed(() => {
  if (editType.value === 'status') return 'Edit Status Alat Repair'
  if (editType.value === 'laporan') return 'Edit Tindakan Teknis Repair'
  if (editType.value === 'hasil') return 'Edit Hasil Repair'
  if (editType.value === 'kesimpulan') return 'Edit Kesimpulan'
  return 'Edit Data Repair'
})

const klikTab = (event: any) => {
  console.log('Tab clicked:', event)
}

const getFileKey = (file: File) => {
  return `${file.name}_${file.size}_${file.lastModified}`
}

const stripHtmlToText = (value: any) => {
  if (value === null || value === undefined) return ''

  const raw = String(value)

  if (typeof window === 'undefined' || !raw.includes('<')) {
    return raw.replace(/&nbsp;/gi, ' ').trim()
  }

  const doc = new DOMParser().parseFromString(raw, 'text/html')
  return (doc.body.textContent || '').replace(/\u00a0/g, ' ').trim()
}

const escapeHtml = (value: any) => {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

const sanitizeRichTextHtml = (value: any) => {
  const raw = String(value ?? '').trim()

  if (!raw) {
    return '<span class="rich-empty">-</span>'
  }

  if (typeof window === 'undefined' || !raw.includes('<')) {
    const escaped = escapeHtml(raw).replace(/\n/g, '<br>')
    return escaped || '<span class="rich-empty">-</span>'
  }

  const allowedTags = ['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'UL', 'OL', 'LI', 'BLOCKQUOTE', 'H2', 'H3', 'H4']
  const doc = new DOMParser().parseFromString(raw, 'text/html')

  const walk = (node: Node) => {
    Array.from(node.childNodes).forEach((child) => {
      if (child.nodeType === Node.ELEMENT_NODE) {
        const element = child as HTMLElement

        if (!allowedTags.includes(element.tagName)) {
          element.replaceWith(...Array.from(element.childNodes))
          return
        }

        Array.from(element.attributes).forEach((attribute) => element.removeAttribute(attribute.name))
        walk(element)
      }
    })
  }

  walk(doc.body)

  const html = doc.body.innerHTML.trim()
  return stripHtmlToText(html) ? html : '<span class="rich-empty">-</span>'
}

const safeDisplayHtml = (value: any) => sanitizeRichTextHtml(value)

const isRichTextFilled = (value: any) => stripHtmlToText(value) !== ''

const buildMitraFileCandidates = (files: any) => {
  const list = Array.isArray(files) ? files : [files]
  const candidates: string[] = []

  list
    .filter((file) => file !== null && file !== undefined && String(file).trim() !== '')
    .forEach((file) => {
      const value = String(file).trim()

      if (value.startsWith('http://') || value.startsWith('https://')) {
        candidates.push(value)
        return
      }

      const filename = value.split('/').pop()
      if (!filename) return

      candidates.push(
        publicFileUrl('berkas-mitra', filename),
        publicFileUrl('storage/berkas-mitra', filename),
        publicFileUrl('storage', filename),
        publicFileUrl('berkas-laporan-repair', filename)
      )
    })

  return [...new Set(candidates)]
}

const setMarkingCandidates = (files: any) => {
  MARKING_CANDIDATES.value = buildMitraFileCandidates(files)
  markingCandidateIndex.value = 0
  MARKINGSITE.value = MARKING_CANDIDATES.value[0] || ''
}

const onMarkingImageError = () => {
  markingCandidateIndex.value += 1
  MARKINGSITE.value = MARKING_CANDIDATES.value[markingCandidateIndex.value] || ''
}

const ensureFileArray = (target: any, captionTarget: any, index: number) => {
  if (!target.value[index]) target.value[index] = []
  if (!captionTarget.value[index]) captionTarget.value[index] = []
}

const validateImageFile = (file: File) => {
  if (file.size > 10000000) {
    H.alert('warning', 'Maksimal file size adalah 10 MB')
    return false
  }

  if (!file.type.startsWith('image/')) {
    H.alert('warning', 'File harus berupa gambar JPEG/JPG/PNG')
    return false
  }

  return true
}

const isFileAlreadyExists = (files: File[], file: File) => {
  return files.some((oldFile: File) => getFileKey(oldFile) === getFileKey(file))
}

const fetchStatus = async (filter: any) => {
  await useApi()
    .get(`general/dropdown/statusrepair_m?select=id,statusrepair&param_search=statusrepair&query=${filter.query}&limit=10`)
    .then((response) => {
      d_status.value = response
    })
}

const isMobileDevice = () => {
  return /Mobi|Android/i.test(navigator.userAgent)
}

const pushFotoToTarget = (
  type: 'status' | 'repair' | 'hasil',
  index: number,
  file: File,
  defaultCaption = ''
) => {
  if (!validateImageFile(file)) return

  if (type === 'status') {
    ensureFileArray(fileMitraStatus, captionMitraStatus, index)

    if (isFileAlreadyExists(fileMitraStatus.value[index], file)) {
      return
    }

    fileMitraStatus.value[index].push(file)
    captionMitraStatus.value[index].push(defaultCaption)
  }

  if (type === 'repair') {
    ensureFileArray(fileMitraRepair, captionMitraRepair, index)

    if (isFileAlreadyExists(fileMitraRepair.value[index], file)) {
      return
    }

    fileMitraRepair.value[index].push(file)
    captionMitraRepair.value[index].push(defaultCaption)
  }

  if (type === 'hasil') {
    ensureFileArray(fileMitraHasil, captionMitraHasil, index)

    if (isFileAlreadyExists(fileMitraHasil.value[index], file)) {
      return
    }

    fileMitraHasil.value[index].push(file)
    captionMitraHasil.value[index].push(defaultCaption)
  }
}

const openMobileCamera = (type: 'status' | 'repair' | 'hasil', index: number) => {
  const videoInput = document.createElement('input')
  videoInput.type = 'file'
  videoInput.accept = 'image/*'
  videoInput.capture = 'environment'

  videoInput.onchange = async (event) => {
    const file = (event.target as HTMLInputElement).files?.[0]

    if (file) {
      const imageFileName = `${type}_repair_foto-row${index}_${Date.now()}_${NOREC_DETAIL}.jpeg`
      const renamedFile = new File([file], imageFileName, { type: file.type || 'image/jpeg' })
      pushFotoToTarget(type, index, renamedFile, '')
    }
  }

  videoInput.click()
}

const openCameraModalByType = (type: 'status' | 'repair' | 'hasil', index: number) => {
  currentCameraType.value = type
  currentCameraIndex.value = index

  if (isMobileDevice()) {
    openMobileCamera(type, index)
    return
  }

  modalCamera.value = true

  if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices
      .getUserMedia({
        video: { facingMode: { ideal: 'environment' } },
      })
      .then((stream) => {
        if (video.value) {
          video.value.srcObject = stream
          video.value.style.display = 'block'
          isCameraActive.value = true
        }
      })
      .catch((err) => {
        console.error('Gagal mengakses kamera:', err)
        H.alert('error', 'Gagal mengakses kamera')
      })
  }
}

const openCameraModalStatus = (index: number) => {
  openCameraModalByType('status', index)
}

const openCameraModalRepair = (index: number) => {
  openCameraModalByType('repair', index)
}

const openCameraModalHasil = (index: number) => {
  openCameraModalByType('hasil', index)
}

const takePhoto = () => {
  if (!video.value || currentCameraIndex.value === null || currentCameraType.value === null) return

  const canvasEl = document.createElement('canvas')
  canvasEl.width = video.value.videoWidth
  canvasEl.height = video.value.videoHeight

  const context = canvasEl.getContext('2d')

  if (context) {
    context.drawImage(video.value, 0, 0, canvasEl.width, canvasEl.height)

    canvasEl.toBlob((blob) => {
      if (blob && currentCameraType.value && currentCameraIndex.value !== null) {
        const imageFileName = `${currentCameraType.value}_repair_foto-${currentCameraIndex.value}_${Date.now()}_${NOREC_DETAIL}.jpeg`
        const imageFile = new File([blob], imageFileName, { type: 'image/jpeg' })
        pushFotoToTarget(currentCameraType.value, currentCameraIndex.value, imageFile, '')
      }

      closeCameraModal()
    }, 'image/jpeg')
  }
}

const closeCameraModal = () => {
  if (video.value && video.value.srcObject) {
    const stream = video.value.srcObject as MediaStream
    stream.getTracks().forEach((track) => track.stop())
    video.value.srcObject = null
    video.value.style.display = 'none'
  }

  isCameraActive.value = false
  modalCamera.value = false
  currentCameraType.value = null
  currentCameraIndex.value = null
}

const onSelectStatus = async (event: any, index: number) => {
  ensureFileArray(fileMitraStatus, captionMitraStatus, index)

  const files = event.files || []

  files.forEach((file: File) => {
    pushFotoToTarget('status', index, file, '')
  })
}

const onSelectRepair = async (event: any, index: number) => {
  ensureFileArray(fileMitraRepair, captionMitraRepair, index)

  const files = event.files || []

  files.forEach((file: File) => {
    pushFotoToTarget('repair', index, file, '')
  })
}

const onSelectHasil = async (event: any, index: number) => {
  ensureFileArray(fileMitraHasil, captionMitraHasil, index)

  const files = event.files || []

  files.forEach((file: File) => {
    pushFotoToTarget('hasil', index, file, '')
  })
}

const previewLocalFile = (file: File) => {
  if (file) {
    const url = URL.createObjectURL(file)
    window.open(url, '_blank')
  }
}

const removeFotoStatus = (rowIndex: number, fotoIndex: number) => {
  fileMitraStatus.value[rowIndex].splice(fotoIndex, 1)
  captionMitraStatus.value[rowIndex].splice(fotoIndex, 1)
}

const removeFotoRepair = (rowIndex: number, fotoIndex: number) => {
  fileMitraRepair.value[rowIndex].splice(fotoIndex, 1)
  captionMitraRepair.value[rowIndex].splice(fotoIndex, 1)
}

const removeFotoHasil = (rowIndex: number, fotoIndex: number) => {
  fileMitraHasil.value[rowIndex].splice(fotoIndex, 1)
  captionMitraHasil.value[rowIndex].splice(fotoIndex, 1)
}

const addNewLaporanStatus = () => {
  const lastNo = item.value.detailLaporanStatus.at(-1)?.no ?? 0

  item.value.detailLaporanStatus.push({
    no: lastNo + 1,
    bagianalatstatus: '',
    kondisistatus: '',
  })

  fileMitraStatus.value.push([])
  captionMitraStatus.value.push([])
}

const removeLaporanStatus = (index: number) => {
  item.value.detailLaporanStatus.splice(index, 1)
  fileMitraStatus.value.splice(index, 1)
  captionMitraStatus.value.splice(index, 1)

  if (item.value.detailLaporanStatus.length === 0) {
    item.value.detailLaporanStatus.push({
      no: 1,
      bagianalatstatus: '',
      kondisistatus: '',
    })

    fileMitraStatus.value.push([])
    captionMitraStatus.value.push([])
  }
}

const addNewLaporanRepair = () => {
  const lastNo = item.value.detailLaporanRepair.at(-1)?.no ?? 0

  item.value.detailLaporanRepair.push({
    no: lastNo + 1,
    bagianalatlaporan: '',
    penanganan: '',
    status: '',
    sparepart: '',
  })

  fileMitraRepair.value.push([])
  captionMitraRepair.value.push([])
}

const removeLaporanRepair = (index: number) => {
  item.value.detailLaporanRepair.splice(index, 1)
  fileMitraRepair.value.splice(index, 1)
  captionMitraRepair.value.splice(index, 1)

  if (item.value.detailLaporanRepair.length === 0) {
    item.value.detailLaporanRepair.push({
      no: 1,
      bagianalatlaporan: '',
      penanganan: '',
      status: '',
      sparepart: '',
    })

    fileMitraRepair.value.push([])
    captionMitraRepair.value.push([])
  }
}

const addNewHasilRepair = () => {
  const lastNo = item.value.detailHasilRepair.at(-1)?.no ?? 0

  item.value.detailHasilRepair.push({
    no: lastNo + 1,
    hasil: '',
    status: '',
  })

  fileMitraHasil.value.push([])
  captionMitraHasil.value.push([])
}

const removeHasilRepair = (index: number) => {
  item.value.detailHasilRepair.splice(index, 1)
  fileMitraHasil.value.splice(index, 1)
  captionMitraHasil.value.splice(index, 1)

  if (item.value.detailHasilRepair.length === 0) {
    item.value.detailHasilRepair.push({
      no: 1,
      hasil: '',
      status: '',
    })

    fileMitraHasil.value.push([])
    captionMitraHasil.value.push([])
  }
}

const clear = () => {
  fileMitraStatus.value = [[]]
  captionMitraStatus.value = [[]]

  fileMitraRepair.value = [[]]
  captionMitraRepair.value = [[]]

  fileMitraHasil.value = [[]]
  captionMitraHasil.value = [[]]

  item.value.kesimpulan = ''

  item.value.detailLaporanRepair = [
    {
      no: 1,
      bagianalatlaporan: '',
      penanganan: '',
      status: '',
      sparepart: '',
    },
  ]

  item.value.detailLaporanStatus = [
    {
      no: 1,
      bagianalatstatus: '',
      kondisistatus: '',
    },
  ]

  item.value.detailHasilRepair = [
    {
      no: 1,
      hasil: '',
      status: '',
    },
  ]
}

const fetchData = async () => {
  loadSearch.value = true
  isLoading.value = true

  await useApi()
    .get(`pelaksana/get-laporan-repair?norecdetail=${NOREC_DETAIL}`)
    .then((response: any) => {
      const repair = response.data || []
      const status = response.datastatus || []
      const hasil = response.datahasil || []

      repair.forEach((element: any, i: any) => {
        element.no = i + 1
      })

      status.forEach((element: any, i: any) => {
        element.no = i + 1
      })

      hasil.forEach((element: any, i: any) => {
        element.no = i + 1
      })

      dataSourceHasilLaporanRepair.value = repair
      dataSourceHasilLaporanRepairStatus.value = status
      dataSourceHasilRepair.value = hasil

      const detail = response.detail || {}

      printRegistrationNorec.value =
        detail.noregistrasifk ||
        repair[0]?.norecregis ||
        status[0]?.norecregis ||
        hasil[0]?.norecregis ||
        printRegistrationNorec.value

      hasSavedReport.value =
        repair.length > 0 ||
        status.length > 0 ||
        hasil.length > 0 ||
        !!detail.statusrepairfk ||
        isRichTextFilled(detail.kesimpulanrepair)

      if (detail?.statusrepairfk || detail?.statusrepair) {
        item.value.statusrepair = {
          value: detail.statusrepairfk ?? '',
          label: detail.statusrepair ?? '',
        }
      } else if (repair.length > 0) {
        item.value.statusrepair = {
          value: repair[0].statusrepairfk ?? '',
          label: repair[0].statusrepair ?? '',
        }
      }

      if (detail?.kesimpulanrepair) {
        item.value.kesimpulan = detail.kesimpulanrepair
      }
    })
    .catch((err) => {
      console.error(err)
      dataSourceHasilLaporanRepair.value = []
      dataSourceHasilLaporanRepairStatus.value = []
      dataSourceHasilRepair.value = []
      hasSavedReport.value = false
    })
    .finally(() => {
      loadSearch.value = false
      isLoading.value = false
    })
}

const detailOrder = async () => {
  const response = await useApi().get(`/pelaksana/detail-alat-repair?norec_pd=${NOREC_DETAIL}`)
  const data = response.data

  if (!data) {
    setMarkingCandidates([])
    return
  }

  item.value.namaproduk = data.namaproduk
  item.value.namamerk = data.namamerk
  item.value.namatipe = data.namatipe
  item.value.namaserialnumber = data.namaserialnumber
  item.value.noorderalat = data.noorderalat
  printRegistrationNorec.value = data.norec || printRegistrationNorec.value
  item.value.keterangan = data.keterangan || ''
  item.value.namafile = data.namafile
  item.value.kesimpulan = data.kesimpulanrepair
  item.value.laporanRepairSudahMasukReview = !!(
    data.tglisilaporanrepairpelaksana ||
    data.pelaksanaisilaporanrepairfk ||
    data.tglisilaporanrepairpenyelia ||
    data.tglsetujupenyelialaporanrepair ||
    data.tglsetujuasmanlaporanrepair ||
    data.tglsetujumanagerlaporanrepair
  )

  const fotoFiles = Array.isArray(data.foto_files) && data.foto_files.length > 0
    ? data.foto_files
    : [data.namafile]

  setMarkingCandidates(fotoFiles)
}

const appendFotoRowsToFormData = (
  formData: FormData,
  baseName: string,
  rowIndex: number,
  files: File[],
  captions: string[]
) => {
  files.forEach((file, fotoIndex) => {
    formData.append(`${baseName}[${rowIndex}][fotos][${fotoIndex}][file]`, file)
    formData.append(`${baseName}[${rowIndex}][fotos][${fotoIndex}][keterangan_gambar]`, captions[fotoIndex] || '')
  })
}

const isTextFilled = (value: any) => {
  return isRichTextFilled(value)
}

const hasFileInRow = (files: any, index: number) => {
  return files.value[index] && files.value[index].length > 0
}

const isStatusRowFilled = (row: any, index: number) => {
  return (
    isTextFilled(row.bagianalatstatus) ||
    isTextFilled(row.kondisistatus) ||
    hasFileInRow(fileMitraStatus, index)
  )
}

const isRepairRowFilled = (row: any, index: number) => {
  return (
    isTextFilled(row.bagianalatlaporan) ||
    isTextFilled(row.penanganan) ||
    isTextFilled(row.status) ||
    isTextFilled(row.sparepart) ||
    hasFileInRow(fileMitraRepair, index)
  )
}

const isHasilRowFilled = (row: any, index: number) => {
  return (
    isTextFilled(row.hasil) ||
    isTextFilled(row.status) ||
    hasFileInRow(fileMitraHasil, index)
  )
}

const getFilledStatusRows = () => {
  return item.value.detailLaporanStatus
    .map((row: any, index: number) => ({
      row,
      index,
    }))
    .filter((data: any) => isStatusRowFilled(data.row, data.index))
}

const getFilledRepairRows = () => {
  return item.value.detailLaporanRepair
    .map((row: any, index: number) => ({
      row,
      index,
    }))
    .filter((data: any) => isRepairRowFilled(data.row, data.index))
}

const getFilledHasilRows = () => {
  return item.value.detailHasilRepair
    .map((row: any, index: number) => ({
      row,
      index,
    }))
    .filter((data: any) => isHasilRowFilled(data.row, data.index))
}

const validateStatusAlatFilledRows = (filledRows: any[]) => {
  for (const data of filledRows) {
    const row = data.row
    const index = data.index
    const nomor = index + 1

    if (!isTextFilled(row.bagianalatstatus)) {
      H.alert('warning', `Status Alat baris ${nomor}: Bagian Alat wajib diisi`)
      return false
    }

    if (!isTextFilled(row.kondisistatus)) {
      H.alert('warning', `Status Alat baris ${nomor}: Kondisi wajib diisi`)
      return false
    }

    if (!hasFileInRow(fileMitraStatus, index)) {
      H.alert('warning', `Status Alat baris ${nomor}: Dokumentasi gambar wajib diunggah minimal 1 gambar`)
      return false
    }
  }

  return true
}

const validateTindakanTeknisFilledRows = (filledRows: any[]) => {
  for (const data of filledRows) {
    const row = data.row
    const index = data.index
    const nomor = index + 1

    if (!isTextFilled(row.bagianalatlaporan)) {
      H.alert('warning', `Tindakan Teknis baris ${nomor}: Bagian Alat wajib diisi`)
      return false
    }

    if (!isTextFilled(row.penanganan)) {
      H.alert('warning', `Tindakan Teknis baris ${nomor}: Penanganan wajib diisi`)
      return false
    }

    if (!isTextFilled(row.status)) {
      H.alert('warning', `Tindakan Teknis baris ${nomor}: Status wajib diisi`)
      return false
    }

    if (!isTextFilled(row.sparepart)) {
      H.alert('warning', `Tindakan Teknis baris ${nomor}: Sparepart & Material Consumable wajib diisi`)
      return false
    }

    if (!hasFileInRow(fileMitraRepair, index)) {
      H.alert('warning', `Tindakan Teknis baris ${nomor}: Dokumentasi gambar wajib diunggah minimal 1 gambar`)
      return false
    }
  }

  return true
}

const validateHasilRepairFilledRows = (filledRows: any[]) => {
  for (const data of filledRows) {
    const row = data.row
    const index = data.index
    const nomor = index + 1

    if (!isTextFilled(row.hasil)) {
      H.alert('warning', `Hasil Repair baris ${nomor}: Hasil wajib diisi`)
      return false
    }

    if (!isTextFilled(row.status)) {
      H.alert('warning', `Hasil Repair baris ${nomor}: Status wajib diisi`)
      return false
    }

    if (!hasFileInRow(fileMitraHasil, index)) {
      H.alert('warning', `Hasil Repair baris ${nomor}: Dokumentasi gambar wajib diunggah minimal 1 gambar`)
      return false
    }
  }

  return true
}

const validateExistingRowsForFinal = () => {
  for (const row of dataSourceHasilLaporanRepairStatus.value) {
    const nomor = row.no || '-'

    if (!isTextFilled(row.bagianalat) || !isTextFilled(row.kondisi) || getRowFotos(row).length === 0) {
      H.alert('warning', `Draft Status Alat baris ${nomor} belum lengkap`)
      return false
    }
  }

  for (const row of dataSourceHasilLaporanRepair.value) {
    const nomor = row.no || '-'

    if (
      !isTextFilled(row.bagianalatlaporan) ||
      !isTextFilled(row.penanganan) ||
      !isTextFilled(row.status) ||
      !isTextFilled(row.sparepart) ||
      getRowFotos(row).length === 0
    ) {
      H.alert('warning', `Draft Tindakan Teknis baris ${nomor} belum lengkap`)
      return false
    }
  }

  for (const row of dataSourceHasilRepair.value) {
    const nomor = row.no || '-'

    if (!isTextFilled(row.hasil) || !isTextFilled(row.status) || getRowFotos(row).length === 0) {
      H.alert('warning', `Draft Hasil Repair baris ${nomor} belum lengkap`)
      return false
    }
  }

  return true
}

const Save = async (mode: 'draft' | 'final' = 'final') => {
  const filledStatusRows = getFilledStatusRows()
  const filledRepairRows = getFilledRepairRows()
  const filledHasilRows = getFilledHasilRows()

  const totalFilledRows =
    filledStatusRows.length +
    filledRepairRows.length +
    filledHasilRows.length

  const hasExistingReportRows = totalData.value > 0
  const hasAnyDraftContent =
    !!item.value.statusrepair ||
    isTextFilled(item.value.kesimpulan) ||
    totalFilledRows > 0 ||
    hasExistingReportRows

  if (mode === 'draft' && !hasAnyDraftContent) {
    H.alert('warning', 'Isi minimal salah satu data laporan sebelum menyimpan draft')
    return
  }

  if (mode === 'final') {
    if (!item.value.statusrepair) {
      H.alert('warning', 'Status Keberhasilan Repair harus diisi sebelum simpan final')
      return
    }

    if (totalFilledRows === 0 && !hasExistingReportRows) {
      H.alert('warning', 'Minimal isi salah satu bagian: Status Alat, Tindakan Teknis, atau Hasil Repair')
      return
    }

    if (!validateStatusAlatFilledRows(filledStatusRows)) {
      return
    }

    if (!validateTindakanTeknisFilledRows(filledRepairRows)) {
      return
    }

    if (!validateHasilRepairFilledRows(filledHasilRows)) {
      return
    }

    if (!validateExistingRowsForFinal()) {
      return
    }
  }

  const formData = new FormData()
  formData.append('norec_detail', NOREC_DETAIL)
  formData.append('mode_simpan', mode)
  formData.append('kesimpulan', item.value.kesimpulan || '')
  formData.append('statusrepairfk', item.value.statusrepair?.value || '')

  filledStatusRows.forEach((data: any, newIndex: number) => {
    const row = data.row
    const oldIndex = data.index

    formData.append(`daftarstatusrepair[${newIndex}][bagianalatstatus]`, row.bagianalatstatus || '')
    formData.append(`daftarstatusrepair[${newIndex}][kondisistatus]`, row.kondisistatus || '')

    appendFotoRowsToFormData(
      formData,
      'daftarstatusrepair',
      newIndex,
      fileMitraStatus.value[oldIndex] || [],
      captionMitraStatus.value[oldIndex] || []
    )
  })

  filledRepairRows.forEach((data: any, newIndex: number) => {
    const row = data.row
    const oldIndex = data.index

    formData.append(`daftarlaporanrepair[${newIndex}][bagianalatlaporan]`, row.bagianalatlaporan || '')
    formData.append(`daftarlaporanrepair[${newIndex}][penanganan]`, row.penanganan || '')
    formData.append(`daftarlaporanrepair[${newIndex}][status]`, row.status || '')
    formData.append(`daftarlaporanrepair[${newIndex}][sparepart]`, row.sparepart || '')

    appendFotoRowsToFormData(
      formData,
      'daftarlaporanrepair',
      newIndex,
      fileMitraRepair.value[oldIndex] || [],
      captionMitraRepair.value[oldIndex] || []
    )
  })

  filledHasilRows.forEach((data: any, newIndex: number) => {
    const row = data.row
    const oldIndex = data.index

    formData.append(`daftarhasilrepair[${newIndex}][hasil]`, row.hasil || '')
    formData.append(`daftarhasilrepair[${newIndex}][status]`, row.status || '')

    appendFotoRowsToFormData(
      formData,
      'daftarhasilrepair',
      newIndex,
      fileMitraHasil.value[oldIndex] || [],
      captionMitraHasil.value[oldIndex] || []
    )
  })

  isLoadingSave.value = true

  try {
    await useApi().post('/pelaksana/save-data-laporan-repair', formData)
    H.alert('success', mode === 'draft' ? 'Draft laporan repair berhasil disimpan' : 'Laporan repair final berhasil dikirim ke penyelia')
    clear()
    await fetchData()
    await detailOrder()
  } catch (e: any) {
    console.error('Gagal menyimpan:', e)
    H.alert('error', 'Gagal menyimpan laporan repair')
  } finally {
    isLoadingSave.value = false
  }
}

const openEditStatus = (row: any) => {
  editType.value = 'status'
  editForm.value = {
    norec: row.norec,
    bagianalatstatus: row.bagianalat || '',
    kondisistatus: row.kondisi || '',
  }

  editExistingFotos.value = JSON.parse(JSON.stringify(getRowFotos(row)))
  editDeleteFotos.value = []
  editNewFiles.value = []
  editNewCaptions.value = []
  modalEdit.value = true
}

const openEditLaporan = (row: any) => {
  editType.value = 'laporan'
  editForm.value = {
    norec: row.norec,
    bagianalatlaporan: row.bagianalatlaporan || '',
    penanganan: row.penanganan || '',
    status: row.status || '',
    sparepart: row.sparepart || '',
  }

  editExistingFotos.value = JSON.parse(JSON.stringify(getRowFotos(row)))
  editDeleteFotos.value = []
  editNewFiles.value = []
  editNewCaptions.value = []
  modalEdit.value = true
}

const openEditHasil = (row: any) => {
  editType.value = 'hasil'
  editForm.value = {
    norec: row.norec,
    hasil: row.hasil || '',
    status: row.status || '',
  }

  editExistingFotos.value = JSON.parse(JSON.stringify(getRowFotos(row)))
  editDeleteFotos.value = []
  editNewFiles.value = []
  editNewCaptions.value = []
  modalEdit.value = true
}

const openEditKesimpulan = () => {
  editType.value = 'kesimpulan'
  editForm.value = {
    norec: NOREC_DETAIL,
    kesimpulan: item.value.kesimpulan || '',
  }
  editExistingFotos.value = []
  editDeleteFotos.value = []
  editNewFiles.value = []
  editNewCaptions.value = []
  modalEdit.value = true
}

const closeEditModal = () => {
  modalEdit.value = false
  editType.value = null
  editForm.value = {}
  editExistingFotos.value = []
  editDeleteFotos.value = []
  editNewFiles.value = []
  editNewCaptions.value = []
}

const hapusFotoExisting = (fotoIndex: number) => {
  const foto = editExistingFotos.value[fotoIndex]

  if (foto?.norec) {
    editDeleteFotos.value.push(foto.norec)
  }

  editExistingFotos.value.splice(fotoIndex, 1)
}

const onSelectEditFoto = async (event: any) => {
  const files = event.files || []

  files.forEach((file: File) => {
    if (!validateImageFile(file)) {
      return
    }

    const exists = editNewFiles.value.some((oldFile: File) => {
      return getFileKey(oldFile) === getFileKey(file)
    })

    if (!exists) {
      editNewFiles.value.push(file)
      editNewCaptions.value.push('')
    }
  })
}

const removeEditNewFoto = (fotoIndex: number) => {
  editNewFiles.value.splice(fotoIndex, 1)
  editNewCaptions.value.splice(fotoIndex, 1)
}

const appendEditFormDataFoto = (formData: FormData) => {
  editExistingFotos.value.forEach((foto: any, index: number) => {
    if (foto.norec) {
      formData.append(`existing_fotos[${index}][norec]`, foto.norec)
      formData.append(`existing_fotos[${index}][keterangan_gambar]`, foto.keterangan_gambar || '')
    }
  })

  editDeleteFotos.value.forEach((fotoNorec: string, index: number) => {
    formData.append(`hapus_fotos[${index}]`, fotoNorec)
  })

  editNewFiles.value.forEach((file: File, index: number) => {
    formData.append(`fotos[${index}][file]`, file)
    formData.append(`fotos[${index}][keterangan_gambar]`, editNewCaptions.value[index] || '')
  })
}

const validateEditData = () => {
  if (!editType.value || !editForm.value?.norec) {
    H.alert('warning', 'Data edit tidak valid')
    return false
  }

  if (editType.value === 'status') {
    if (!isTextFilled(editForm.value.bagianalatstatus)) {
      H.alert('warning', 'Bagian Alat wajib diisi')
      return false
    }

    if (!isTextFilled(editForm.value.kondisistatus)) {
      H.alert('warning', 'Kondisi wajib diisi')
      return false
    }
  }

  if (editType.value === 'laporan') {
    if (!isTextFilled(editForm.value.bagianalatlaporan)) {
      H.alert('warning', 'Bagian Alat wajib diisi')
      return false
    }

    if (!isTextFilled(editForm.value.penanganan)) {
      H.alert('warning', 'Penanganan wajib diisi')
      return false
    }

    if (!isTextFilled(editForm.value.status)) {
      H.alert('warning', 'Status wajib diisi')
      return false
    }

    if (!isTextFilled(editForm.value.sparepart)) {
      H.alert('warning', 'Sparepart & Material Consumable wajib diisi')
      return false
    }
  }

  if (editType.value === 'hasil') {
    if (!isTextFilled(editForm.value.hasil)) {
      H.alert('warning', 'Hasil Repair wajib diisi')
      return false
    }

    if (!isTextFilled(editForm.value.status)) {
      H.alert('warning', 'Status wajib diisi')
      return false
    }
  }

  if (editType.value === 'kesimpulan' && !isTextFilled(editForm.value.kesimpulan)) {
    H.alert('warning', 'Kesimpulan wajib diisi')
    return false
  }

  if (editType.value !== 'kesimpulan') {
    const totalFotoTersisa = editExistingFotos.value.length + editNewFiles.value.length

    if (totalFotoTersisa === 0) {
      H.alert('warning', 'Dokumentasi gambar wajib ada minimal 1 gambar')
      return false
    }
  }

  return true
}

const saveEditData = async () => {
  if (!validateEditData()) {
    return
  }

  const formData = new FormData()
  formData.append('norec', editForm.value.norec)

  if (editType.value === 'status') {
    formData.append('bagianalatstatus', editForm.value.bagianalatstatus || '')
    formData.append('kondisistatus', editForm.value.kondisistatus || '')
  }

  if (editType.value === 'laporan') {
    formData.append('bagianalatlaporan', editForm.value.bagianalatlaporan || '')
    formData.append('penanganan', editForm.value.penanganan || '')
    formData.append('status', editForm.value.status || '')
    formData.append('sparepart', editForm.value.sparepart || '')
  }

  if (editType.value === 'hasil') {
    formData.append('hasil', editForm.value.hasil || '')
    formData.append('status', editForm.value.status || '')
  }

  if (editType.value === 'kesimpulan') {
    formData.append('kesimpulan', editForm.value.kesimpulan || '')
  } else {
    appendEditFormDataFoto(formData)
  }

  let endpoint = ''

  if (editType.value === 'status') {
    endpoint = '/pelaksana/update-status-repair'
  }

  if (editType.value === 'laporan') {
    endpoint = '/pelaksana/update-laporan-repair'
  }

  if (editType.value === 'hasil') {
    endpoint = '/pelaksana/update-hasil-repair'
  }

  if (editType.value === 'kesimpulan') {
    endpoint = '/pelaksana/update-kesimpulan-repair'
  }

  if (!endpoint) {
    H.alert('error', 'Endpoint update tidak valid')
    return
  }

  isLoadingSave.value = true

  try {
    await useApi().post(endpoint, formData)
    H.alert('success', 'Data berhasil diperbarui')
    closeEditModal()
    await fetchData()
    await detailOrder()
  } catch (error) {
    console.error('Gagal update data:', error)
    H.alert('error', 'Gagal memperbarui data')
  } finally {
    isLoadingSave.value = false
  }
}

const hapusLaporan = async (e: any) => {
  isLoadingSave.value = true

  try {
    await useApi().post(`pelaksana/hapus-laporan-repair`, { norec: e.norec })
    H.alert('success', 'Tindakan teknis berhasil dihapus')
    clear()
    await fetchData()
    await detailOrder()
  } catch (error) {
    console.error('Error hapus laporan:', error)
    H.alert('error', 'Gagal menghapus tindakan teknis')
  } finally {
    isLoadingSave.value = false
  }
}

const hapusStatus = async (e: any) => {
  isLoadingSave.value = true

  try {
    await useApi().post(`pelaksana/hapus-status-repair`, { norec: e.norec })
    H.alert('success', 'Status alat berhasil dihapus')
    clear()
    await fetchData()
    await detailOrder()
  } catch (error) {
    console.error('Error hapus status:', error)
    H.alert('error', 'Gagal menghapus status alat')
  } finally {
    isLoadingSave.value = false
  }
}

const hapusHasil = async (e: any) => {
  isLoadingSave.value = true

  try {
    await useApi().post(`pelaksana/hapus-hasil-repair`, { norec: e.norec })
    H.alert('success', 'Hasil repair berhasil dihapus')
    clear()
    await fetchData()
    await detailOrder()
  } catch (error) {
    console.error('Error hapus hasil:', error)
    H.alert('error', 'Gagal menghapus hasil repair')
  } finally {
    isLoadingSave.value = false
  }
}

const getFotoUrl = (filename: string) => {
  return resolvePublicFileUrl(filename, 'berkas-laporan-repair')
}

const onImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'

  const parent = img.parentElement

  if (parent && !parent.querySelector('.image-error-text')) {
    const errorText = document.createElement('div')
    errorText.className = 'image-error-text'
    errorText.innerText = 'Gambar tidak ditemukan'
    parent.insertBefore(errorText, parent.firstChild)
  }
}

const getRowFotos = (row: any) => {
  if (row.fotos && Array.isArray(row.fotos) && row.fotos.length > 0) {
    return row.fotos
  }

  if (row.fotoalatstatus) {
    return [
      {
        namafile: row.fotoalatstatus,
        keterangan_gambar: '',
      },
    ]
  }

  if (row.fotoalatrepair) {
    return [
      {
        namafile: row.fotoalatrepair,
        keterangan_gambar: '',
      },
    ]
  }

  if (row.fotohasilrepair) {
    return [
      {
        namafile: row.fotohasilrepair,
        keterangan_gambar: '',
      },
    ]
  }

  return []
}

const cetakLaporanRepair = () => {
  const norecReg =
    printRegistrationNorec.value ||
    dataSourceHasilLaporanRepair.value?.[0]?.norecregis ||
    dataSourceHasilLaporanRepairStatus.value?.[0]?.norecregis ||
    dataSourceHasilRepair.value?.[0]?.norecregis

  if (!norecReg) {
    H.alert('warning', 'Data laporan belum tersedia untuk dicetak')
    return
  }

  H.printBlade(`pelaksana/cetak-laporan-repair?pdf=true&norec=${norecReg}&norec_detail=${NOREC_DETAIL}`)
}

const toDashboard = () => {
  router.push({
    name: 'module-dashboard-pelaksana',
  })
}

const kembali = () => {
  window.history.back()
}

detailOrder()
fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/bedah.scss';

.tabs-wrapper.is-slider .tabs,
.tabs-wrapper-alt.is-slider .tabs {
  position: relative;
  background: var(--fade-grey-light-2);
  border: 1px solid var(--fade-grey);
  max-width: 400px;
  height: 35px;
  border-bottom: none;
}

.repair-form-card {
  border: 1px solid var(--fade-grey-dark-3);
}

.alat-report-grid {
  display: grid;
  grid-template-columns: minmax(280px, 0.85fr) minmax(320px, 1.15fr);
  gap: 22px;
  align-items: stretch;
  margin-bottom: 18px;
}

.alat-report-copy,
.alat-photo-panel {
  min-height: 260px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 12px;
  background: var(--white);
  box-shadow: var(--light-box-shadow);
}

.alat-report-copy {
  padding: 22px;
}

.section-eyebrow {
  display: block;
  margin-bottom: 10px;
  color: var(--primary);
  font-family: var(--font-alt);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.alat-keterangan {
  color: var(--dark-text);
  font-family: var(--font);
  font-size: 1rem;
  line-height: 1.7;
  white-space: pre-wrap;
}

.alat-photo-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 14px;
  background: #f8fbff;
}

.alat-photo-panel img {
  width: 100%;
  max-height: 380px;
  object-fit: contain;
}

.alat-photo-placeholder {
  display: flex;
  width: 100%;
  min-height: 220px;
  align-items: center;
  justify-content: center;
  border: 1px dashed var(--fade-grey-dark-4);
  border-radius: 10px;
  color: var(--light-text);
  font-family: var(--font-alt);
  font-weight: 600;
}

.repair-action-buttons {
  gap: 10px;
  flex-wrap: wrap;
}

.ck-editor {
  color: var(--dark-text);
  max-width: 100%;
}

.ck-editor__editable {
  min-height: 115px;
  max-width: 100%;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.form-section-inner.is-horizontal {
  max-width: 100%;
  overflow-x: auto !important;
  overflow-y: visible !important;
}

.form-section-inner.is-horizontal > table {
  width: 100%;
  min-width: 1040px;
  table-layout: fixed;
}

.td-po {
  overflow: hidden;
  vertical-align: top;
}

.th-po.action-cell,
.td-po.action-cell {
  width: 96px;
  min-width: 96px;
  max-width: 108px;
  text-align: center;
}

.repair-row-actions {
  display: flex;
  flex-wrap: nowrap;
  gap: 8px;
  justify-content: center;
  align-items: center;
}

.td-po .ck,
.td-po .ck-editor,
.td-po .ck-editor__main,
.td-po .ck-editor__editable {
  max-width: 100%;
}

.td-po .ck-toolbar,
.td-po .ck-toolbar__items {
  max-width: 100%;
  white-space: normal;
}

.td-po .ck-toolbar__items {
  flex-wrap: wrap !important;
}

.rich-preview {
  color: var(--dark-text);
  font-size: 0.9rem;
  line-height: 1.5;
  max-width: 100%;
  min-width: 0;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.rich-preview p {
  margin: 0 0 0.45rem;
}

.rich-preview ul,
.rich-preview ol {
  margin: 0 0 0.45rem 1.1rem;
}

.rich-preview blockquote {
  margin: 0 0 0.45rem;
  padding-left: 0.75rem;
  border-left: 3px solid var(--primary);
  color: var(--light-text);
}

.rich-preview-card {
  padding: 16px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 10px;
  background: var(--white);
}

.rich-empty,
.text-muted {
  color: var(--light-text);
  font-style: italic;
}

@media (max-width: 960px) {
  .alat-report-grid {
    grid-template-columns: 1fr;
  }

  .repair-action-buttons {
    justify-content: stretch !important;
  }

  .repair-action-buttons .button {
    width: 100%;
  }
}

.tb-order .text-value {
  font-family: var(--font-alt);
  color: var(--dark-text);
  font-weight: 400;
  font-size: 12px;
}

.upload-info-text {
  margin-top: 6px;
  margin-bottom: 8px;
  font-size: 12px;
  color: var(--light-text);
  line-height: 1.4;
  font-style: italic;
}

.repair-photo-list {
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 8px;
  padding: 8px;
  background: var(--white);
}

.repair-photo-item {
  border-bottom: 1px solid var(--fade-grey-dark-3);
  padding: 8px 0;
}

.repair-photo-item:last-child {
  border-bottom: none;
}

.repair-photo-name {
  font-size: 12px;
  font-weight: 600;
  color: var(--dark-text);
  margin-bottom: 6px;
  word-break: break-all;
}

.saved-photo-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
}

.saved-photo-item {
  width: 180px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 8px;
  padding: 6px;
  background: var(--white);
}

.saved-photo-img {
  width: 100%;
  height: 130px;
  object-fit: contain;
  border-radius: 6px;
  background: #f8f8f8;
}

.saved-photo-caption {
  font-size: 12px;
  margin-top: 5px;
  line-height: 1.3;
  text-align: center;
}

.image-error-text {
  min-height: 130px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: var(--light-text);
  font-style: italic;
  background: #f8f8f8;
  border-radius: 6px;
  text-align: center;
  padding: 8px;
}

.edit-photo-grid {
  justify-content: flex-start;
}

.edit-photo-grid .saved-photo-item {
  width: 200px;
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

  .upload-info-text {
    color: var(--light-text);
  }

  .repair-photo-list,
  .saved-photo-item {
    background: var(--dark-sidebar-light-4);
    border-color: var(--dark-sidebar-light-12);
  }

  .saved-photo-img,
  .image-error-text {
    background: var(--dark-sidebar-light-2);
  }
}

.user-grid-v2 .grid-item-wrap .grid-item-head.is-registrasi {
  background: var(--success) !important;
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
