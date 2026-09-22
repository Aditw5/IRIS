<template>
  <WorksheetHeader :item="item" />

  <div class="column is-12">
    <TabView class="tabview-custom " :scrollable="true" @tab-click="klikTab($event)">
      <TabPanel>
        <template #header>
          <i class="fas fa-users mr-2" aria-hidden="true"></i>
          <span>Lembar Kerja Sub Lingkup Dryblock</span>
          <Badge :value="totalData" v-if="totalData > 0" severity="danger" class="ml-2" />
        </template>
        <VCard>
          <div class="colum is-12">
            <div class="columns is-multiline">
              <div class="column">
                <div class="column is-12 mt-4-min">
                  <VButton rounded color="warning" class="" icon="feather:download" raised bold
                    @click="downloadTemplate()">
                    Download Template
                  </VButton>
                  <div class="dataTable-bottom mt-2">
                    <div class="dataTable-info" style="font-style:italic"> *Note : Template
                      digunakan untuk menyamakan data, agar saat di upload tidak terjadi
                      kesalahan memasukkan data.
                    </div>
                  </div>
                </div>
              </div>
              <div class="column is-12">
                <FileUpload name="demo[]" :multiple="false" @upload="onTemplatedUpload($event)" mode="advanced"
                  :showUploadButton="false" :showCancelButton="true" @select="onSelectedFiles" chooseLabel="Pilih"
                  cancelLabel="Batal" :maxFileSize="50000000">
                  <template #header="{ chooseCallback, uploadCallback, clearCallback, files }">
                    <div class="flex flex-wrap justify-content-between align-items-center flex-1 gap-2">
                      <div class="flex gap-2">
                        <Button @click="chooseCallback()" icon="pi pi-upload" rounded severity="info" class="mr-1"
                          outlined></Button>
                        <Button @click="clearCallback()" icon="pi pi-times" rounded outlined severity="danger"
                          :disabled="!files || files.length === 0"></Button>
                      </div>
                      <ProgressBar :value="totalSizePercent" :showValue="false"
                        :class="['md:w-20rem h-1rem w-full md:ml-auto', { 'exceeded-progress-bar': totalSizePercent > 100 }]">
                        <span class="white-space-nowrap">{{ totalSize
                        }}B / 50Mb</span>
                      </ProgressBar>
                    </div>
                  </template>
                  <template #content="{ files, uploadedFiles, removeUploadedFileCallback, removeFileCallback }">
                    <div v-if="files.length > 0">

                      <div class="flex flex-wrap p-0 sm:p-5 gap-5">
                        <div :key="files[0].name + files[0].type + files[0].size"
                          class="card m-0 px-6 flex flex-column border-1 surface-border align-items-center gap-3">
                          <div>
                            <i class="fas fa-file-excel shadow-2 mr-2" aria-hidden="true"></i>
                          </div>
                          <span class="font-semibold">{{ files[0].name
                          }}</span>
                          <div class="ml-2">{{
                            formatSize(files[0].size)
                          }}
                            <Badge :value="valueProgress >= 99 ? 'Uploaded' : 'Pending'"
                              :severity="valueProgress >= 99 ? 'success' : 'warning'" class="ml-2 mr-2" />
                          </div>

                          <Button icon="pi pi-times" @click="onRemoveTemplatingFile(files[0], removeFileCallback, 0)"
                            outlined rounded severity="danger" />
                        </div>
                      </div>
                    </div>
                  </template>
                  <template #empty>
                    <p>Drag atau drop files untuk mengupload.</p>
                  </template>
                </FileUpload>
              </div>
            </div>
          </div>
          <div class="column" v-if="isLoading">
            <VPlaceloadWrap v-for="data in 10">
              <VPlaceload class="mx-2 mb-3" />
            </VPlaceloadWrap>
          </div>
          <div v-else-if="dataSourcefilter.length == 0">
            <VPlaceholderSection :title="H.assets().notFound" :subtitle="H.assets().notFoundSubtitle" class="my-6">
              <template #image>
                <img class="light-image" :src="H.assets().iconNotFound_rev" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
              </template>
            </VPlaceholderSection>
          </div>
          <div class="column is-12" v-else>
            <div class="columns is-multiline" style="align-items:right">
              <div class="column is-3">
                <h3 class="title is-5 mb-2 mr-1">Upload Lembar Kerja </h3>
              </div>
            </div>
            <div class="columns is-multiline">
              <div class="column is-3">
                <VField label=" Tanggal Kalibrasi">
                  <VDatePicker v-model="item.tglkalibrasi" mode="dateTime" style="width: 100%;">
                    <template #default="{ inputValue, inputEvents }">
                      <VField>
                        <VControl icon="feather:calendar" fullwidth>
                          <VInput :value="inputValue" v-on="inputEvents" />
                        </VControl>
                      </VField>
                    </template>
                  </VDatePicker>
                </VField>
              </div>
              <div class="column is-3">
                <VField label="Tempat Kalibrasi">
                  <AutoComplete v-model="item.tempatKalibrasi" :suggestions="d_ruangan" @complete="fetchRuangan($event)"
                    :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                    :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
                </VField>
              </div>
            </div>
            <div class="columns is-multiline">
              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Resolusi </span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.resolusi" placeholder="Resolusi" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>°C</VButton>
                  </VControl>
                </VField>
              </div>
              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Rentang Ukur Suhu </span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.rentangukursuhu" placeholder="Rentang Ukur Suhu" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>°C</VButton>
                  </VControl>
                </VField>
              </div>
              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Suhu </span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.suhu" placeholder="Suhu" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>°C</VButton>
                  </VControl>
                </VField>
              </div>
              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Kelembaban Relatif </span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.kelembabanRelatif" placeholder="Kelembaban Relatif" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>% RH</VButton>
                  </VControl>
                </VField>
              </div>
            </div>
            <div class="columns is-multiline">
              <div class="column is-12">
                <VField label="Catatan/Notes">
                  <VControl>
                    <VTextarea v-model="item.notes" rows="4" placeholder="Catatan/Notes"> </VTextarea>
                  </VControl>
                </VField>
              </div>
            </div>
            <div class="columns is-multiline">
              <div class="column is-6">
                <Fieldset legend="- Instruksi Kerja" :toggleable="true">
                  <div style="overflow-y:auto;" class="mt-5 form-section-inner is-horizontal">
                    <table width="100%">
                      <thead>
                        <tr class="tr-po">
                          <th class="th-po" width="25%" style="vertical-align:inherit;text-align: center;">Nama
                            Instruksi
                            Kerja
                            <WorksheetMasterButton kind="ik" />
                          </th>
                          <th class="th-po" width="8%" style="vertical-align:inherit;text-align: center;">Aksi</th>
                        </tr>
                      </thead>
                      <tbody v-for="(items, index) in item.detailInstruksiKerja" :key="index">
                        <tr class="tr-po">
                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <WorksheetInstructionPicker v-model="items.daftarInstruksiKerja" />
                                </VControl>
                              </VField>
                            </div>
                          </td>
                          <td class="td-po" style="vertical-align: inherit;">
                            <div class="column is-12 pl-0 pr-0">
                              <VButtons style="justify-content: space-around;">
                                <VIconButton type="button" raised circle icon="feather:plus"
                                  v-tooltip-prime.bottom="'Tambah'" @click="addNewAlat(items)" outlined color="info">
                                </VIconButton>
                                <VIconButton type="button" raised circle v-tooltip-prime.bottom="'Hapus'" outlined
                                  icon="feather:trash" @click="removeAlat(items)" color="danger">
                                </VIconButton>
                              </VButtons>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </Fieldset>
              </div>
              <div class="column is-6">
                <Fieldset legend="- Daftar Peralatan Standar" :toggleable="true">
                  <div style="overflow-y:auto;" class="mt-5 form-section-inner is-horizontal">
                    <table width="100%">
                      <thead>
                        <tr class="tr-po">
                          <th class="th-po" width="25%" style="vertical-align:inherit;text-align: center;">Nama Alat
                            Standar
                            <WorksheetMasterButton kind="standar" />
                          </th>
                          <th class="th-po" width="8%" style="vertical-align:inherit;text-align: center;">Aksi</th>
                        </tr>
                      </thead>
                      <tbody v-for="(items, index) in item.detailPeralatanStandar" :key="index">
                        <tr class="tr-po">
                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <div style="display:flex; align-items:center">
                                    <AutoComplete v-model="items.daftaralatstandar" :suggestions="d_alatstandar"
                                      @complete="fetchAlatStandar($event)" :optionLabel="'label'" :dropdown="true"
                                      :minLength="3" class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                                      :field="'label'" placeholder="ketik untuk mencari..." />
                                    <VIconButton class="ml-2" icon="feather:camera" rounded @click="openScanner(items)"
                                      v-tooltip.bubble="'Scan QR Alat'" />
                                  </div>
                                </VControl>
                              </VField>
                            </div>
                          </td>
                          <td class="td-po" style="vertical-align: inherit;">
                            <div class="column is-12 pl-0 pr-0">
                              <VButtons style="justify-content: space-around;">
                                <VIconButton type="button" raised circle icon="feather:plus"
                                  v-tooltip-prime.bottom="'Tambah'" @click="addNewAlatStandar(items)" outlined
                                  color="info">
                                </VIconButton>
                                <VIconButton type="button" raised circle v-tooltip-prime.bottom="'Hapus'" outlined
                                  icon="feather:trash" @click="removeAlatStandar(items)" color="danger">
                                </VIconButton>
                              </VButtons>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </Fieldset>
              </div>
              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Kedalaman Lubang </span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.kedalamalubang" placeholder="Kedalaman Lubang" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>mm</VButton>
                  </VControl>
                </VField>
              </div>
              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Lubang Acuan</span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.lubangacuan" placeholder="Lubang Acuan" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>mm</VButton>
                  </VControl>
                </VField>
              </div>
              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Lubang 1</span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.lubang1" placeholder="Lubang 1" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>mm</VButton>
                  </VControl>
                </VField>
              </div>
              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Lubang 2</span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.lubang2" placeholder="Lubang 2" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>mm</VButton>
                  </VControl>
                </VField>
              </div>
            </div>
            <div class="column is-12">
              <FileUpload v-model="fileSuhu" mode="advanced" name="gambar" accept="image/jpeg,image/jpg,image/png"
                :maxFileSize="10000000"
                :invalidFileTypeMessage="'{0}: File yang diupload harus berupa JPG, JPEG, atau PNG.'"
                :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                :chooseLabel="isFile(fileSuhu) ? fileSuhu.name : item.gambarsuhu || 'Unggah Gambar'"
                @select="onSelectSuhu($event)" class="is-rounded w-100" />
            </div>
            <div class="columns is-multiline">
              <div class="column is-3 mt-4">
                <VCardCustom :style="'padding:5px 25px'">
                  <div class="label-status success">
                    <i aria-hidden="true" class="fas fa-circle"></i>
                    <span class="ml-1">TOTAL DATA DRYBLOCK</span>
                  </div>
                  <small class="text-bold-custom h-100">{{ dataSourcefilter.length }}</small>
                </VCardCustom>
              </div>
              <div class="column is-3 mt-4">
                <VButton icon="feather:save" @click="Save()" :loading="isLoadingSave" color="info">Simpan</VButton>
              </div>
            </div>
            <h4>Hasil Kalibrasi</h4>
            <DataTable :value="dataSourceKalibrasi" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
              :loading="isLoading" showGridlines responsiveLayout="stack" breakpoint="960px"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
              <ColumnGroup type="header">
                <Row>
                  <Column header="No" />
                  <Column header="Set Poin" :colspan="2" />
                  <Column header="Penunjukan Standar" :colspan="2" />
                  <Column header="Pembacaan Alat" :colspan="2" />
                  <Column header="Koreksi" :colspan="2" />
                  <Column header="Ketidakpastian" :colspan="2" />
                </Row>
              </ColumnGroup>
              <Column field="no" header="No" />
              <Column field="set_poin" />
              <Column field="set_poin_satuan" />
              <Column field="penunjukan_standar" />
              <Column field="penunjukan_standar_satuan" />
              <Column field="pembacaan_alat" />
              <Column field="pembacaan_alat_satuan" />
              <Column field="koreksi" />
              <Column field="koreksi_satuan" />
              <Column field="ketidakpastian" />
              <Column field="ketidakpastian_satuan" />
            </DataTable>

            <h4>Hasil Karakteristik</h4>
            <DataTable :value="dataSourceKarakteristik" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
              :loading="isLoading" showGridlines responsiveLayout="stack" breakpoint="960px"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
              <ColumnGroup type="header">
                <Row>
                  <Column header="No" />
                  <Column header="Set Poin" :colspan="2" />
                  <Column header="Penunjukan Standar" :colspan="2" />
                  <Column header="Kedalaman Pencelupan" :colspan="2" />
                  <Column header="Perbedaan Suhu Axial" :colspan="2" />
                  <Column header="Keseragaman Suhu" :colspan="2" />
                  <Column header="Kestabilan Suhu" :colspan="2" />
                </Row>
              </ColumnGroup>
              <Column field="no" header="No" />
              <Column field="set_poin" />
              <Column field="set_poin_satuan" />
              <Column field="penunjukan_standar" />
              <Column field="penunjukan_standar_satuan" />
              <Column field="kedalaman_pencelupan" />
              <Column field="kedalaman_pencelupan_satuan" />
              <Column field="perbedaan_suhu" />
              <Column field="perbedaan_suhu_satuan" />
              <Column field="keseragaman_suhu" />
              <Column field="keseragaman_suhu_satuan" />
              <Column field="kestabilan_suhu" />
              <Column field="kestabilan_suhu_satuan" />
            </DataTable>
          </div>
        </VCard>
        <hr>
        <VCard>
          <div class="column is-12">
            <WorksheetMetadataCard :item="item" :norec="NOREC_DETAIL" role="pelaksana" editable />
            <WorksheetAttachments :norec="NOREC_DETAIL" role="pelaksana" />
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Upload Lembar Kerja Sub Lingkup Dryblock </h3>
              </div>
            </div>
            <h4>Hasil Kalibrasi</h4>
            <DataTable v-model:editingRows="editingRows" :value="dataSourceHasilKalibrasi" editMode="row" dataKey="no"
              @row-edit-save="onRowEditSave" rowGroupMode="rowspan" groupRowsBy="group" :paginator="true" :rows="10"
              :rowsPerPageOptions="[5, 10, 25]" class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="column pt-0 pb-0" v-if="dataSourceHasilKalibrasi.length > 0">
                  <VButtons style="justify-content: space-between;">
                    <VButton color="primary" @click="cetakSertifikatLembarKerja()" outlined icon="feather:printer">
                      Cetak Sertifikat
                    </VButton>
                  </VButtons>
                </div>
              </template>
              <ColumnGroup type="header">
                <Row>
                  <Column header="No" />
                  <Column header="Set Poin" :colspan="2" />
                  <Column header="Penunjukan Standar" :colspan="2" />
                  <Column header="Pembacaan Alat" :colspan="2" />
                  <Column header="Koreksi" :colspan="2" />
                  <Column header="Ketidakpastian" :colspan="2" />
                  <Column header="Edit" />
                </Row>
              </ColumnGroup>
              <Column field="no" header="No" />
              <Column field="set_poin">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="set_poin_satuan" />
              <Column field="penunjukan_standar">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="penunjukan_standar_satuan" />
              <Column field="pembacaan_alat">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="pembacaan_alat_satuan" />
              <Column field="koreksi">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="koreksi_satuan" />
              <Column field="ketidakpastian">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="ketidakpastian_satuan" />
              <Column :rowEditor="true" style="width: 10%; min-width: 8rem" bodyStyle="text-align:center" />
            </DataTable>

            <h4>Hasil Karakteristik</h4>
            <DataTable v-model:editingRows="editingRowsKarakter" :value="dataSourceHasilKarakteristik" editMode="row"
              dataKey="no" @row-edit-save="onRowEditSaveKarakter" rowGroupMode="rowspan" groupRowsBy="group"
              :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
              class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="column pt-0 pb-0" v-if="dataSourceHasilKarakteristik.length > 0">
                  <VButtons style="justify-content: space-between;">
                    <VButton color="primary" @click="cetakSertifikatLembarKerja()" outlined icon="feather:printer">
                      Cetak Sertifikat
                    </VButton>
                  </VButtons>
                </div>
              </template>
              <ColumnGroup type="header">
                <Row>
                  <Column header="No" />
                  <Column header="Set Poin" :colspan="2" />
                  <Column header="Penunjukan Standar" :colspan="2" />
                  <Column header="Kedalaman Pencelupan" :colspan="2" />
                  <Column header="Perbedaan Suhu Axial" :colspan="2" />
                  <Column header="Keseragaman Suhu" :colspan="2" />
                  <Column header="Kestabilan Suhu" :colspan="2" />
                  <Column header="Edit" />
                </Row>
              </ColumnGroup>
              <Column field="no" header="No" />
              <Column field="set_poin">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="set_poin_satuan" />
              <Column field="penunjukan_standar">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="penunjukan_standar_satuan" />
              <Column field="kedalaman_pencelupan">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="kedalaman_pencelupan_satuan" />
              <Column field="perbedaan_suhu">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="perbedaan_suhu_satuan" />
              <Column field="keseragaman_suhu">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="keseragaman_suhu_satuan" />
              <Column field="kestabilan_suhu">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="kestabilan_suhu_satuan" />
              <Column :rowEditor="true" style="width: 10%; min-width: 8rem" bodyStyle="text-align:center" />
            </DataTable>
          </div>
        </VCard>
      </TabPanel>
      <TabPanel>
        <template #header>
          <i class="fas fa-upload mr-2" aria-hidden="true"></i>
          <span>Upload FIle</span>
          <Badge :value="totalData" v-if="totalData > 0" severity="danger" class="ml-2" />
        </template>
        <VCard>
          <WorksheetAttachments :norec="NOREC_DETAIL" role="pelaksana" upload-enabled />
          <div v-if="false" class="colum is-12">
            <div class="columns is-multiline">
              <div class="column is-12 mt-4" style="text-align: right;">
                <VButton icon="feather:save" @click="simpanExcelLembarKerja()" :loading="isLoadingSave" color="info">
                  Simpan
                </VButton>
              </div>
              <div class="column is-12">
                <FileUpload v-model="fileMitraExcel" mode="advanced" name="demo"
                  accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                  :maxFileSize="30000000" @upload="onUpload" outlined
                  :invalidFileTypeMessage="'{0}: File yang diupload harus CSV atau Excel (XLS/XLSX).'"
                  :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                  style="background-color: transparent; color: var(--danger); border: 1px solid;"
                  :chooseLabel="fileMitraExcel ? fileMitraExcel.name : 'Unggah'" @select="onSelect($event)"
                  class="is-rounded w-100" />
              </div>
              <div class="column" v-if="dataExcel != null">
                <div class="column is-12 mt-4-min">
                  <div class="dataTable-bottom mt-2">
                    <div class="dataTable-info" style="font-style:italic">
                      File Excel Terunggah {{ dataExcel }}
                    </div>
                  </div>
                  <div class="mt-2">
                  </div>
                </div>
              </div>

            </div>
          </div>
        </VCard>
      </TabPanel>
    </TabView>
  </div>
  <Dialog v-model:visible="showScanner" header="Scan QR Code" :modal="true" :closable="false" style="width:520px">
    <div id="qr-reader" style="width:100%;"></div>
    <div class="p-dialog-footer">
      <VButton @click="cancelScanner" type="button" icon="feather:trash" class="mr-3 mt-4" color="info" outlined raised>
        Batal
      </VButton>
    </div>
  </Dialog>
</template>
<script setup lang="ts">
import WorksheetInstructionPicker from '/@src/components/worksheet/WorksheetInstructionPicker.vue'
import WorksheetMasterButton from '/@src/components/worksheet/WorksheetMasterButton.vue'
import { useRoute, useRouter } from 'vue-router';
import { ref, computed, watch, reactive, nextTick } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useViewWrapper } from '/@src/stores/viewWrapper';
import { useHead } from '@vueuse/head';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';
import * as H from '/@src/utils/appHelper';
import { useApi } from '/@src/composable/useApi';
import moment from 'moment';
import { useUserSession } from '/@src/stores/userSession'
import * as XLSX from "xlsx";
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import AutoComplete from 'primevue/autocomplete';
import Fieldset from 'primevue/fieldset';
import Dialog from 'primevue/dialog'
import { Html5Qrcode } from 'html5-qrcode'

useHead({
  title: 'Lembar Kerja Dryblock - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

let NOREC_DETAIL = useRoute().query.norec_detail as string
let SUBLINGKUP = useRoute().query.sublingkup as string
let NOREC = useRoute().query.norec as string
const isLoading: Boolean = ref(false)
const isLoadingUpload: Boolean = ref(false)
const isLoadingSave: Boolean = ref(false)
const totalSizePercent: Number = ref(0)
const totalSize: Number = ref(0)
const valueProgress: Number = ref(0)
let loadSearch: any = ref(false)
const dataSourceKarakteristik: any = ref([])
const dataSourceKalibrasi: any = ref([])
const dataSourceHasilKalibrasi: any = ref([])
const dataSourceHasilKarakteristik: any = ref([])
const filterd = ref('')
const arr3 = ref([])
const router = useRouter()
const fileMitraExcel: any = ref()
const dataExcel: any = ref()
const editingRows = ref([]);
const editingRowsKarakter = ref([]);
const item: any = ref({
  filterTgl: reactive({
    start: new Date(),
    end: new Date(),
  }),
  tglkalibrasi: new Date(),
  detailInstruksiKerja: [{
    no: 1
  }],
  detailPeralatanStandar: [{
    no: 1
  }]
})

const d_alatstandar = ref([])
const d_ruangan = ref([])
const fileSuhu: any = ref()
const onUpload = () => {

}





const onRowEditSave = async (event: any) => {
  const { newData, index } = event
  dataSourceHasilKalibrasi.value[index] = newData
  const payload = {
    data: [newData],
    sublingkupfk: SUBLINGKUP,
    norec_detail: NOREC_DETAIL
  }
  isLoadingSave.value = true
  try {
    await useApi().post(
      `/pelaksana/edit-lembar-kerja`,
      payload
    )
  }
  catch (err: any) {
    console.error(err)
  }
  finally {
    isLoadingSave.value = false
    fetchData()
  }
}

const onRowEditSaveKarakter = async (event: any) => {
  const { newData, index } = event
  dataSourceHasilKarakteristik.value[index] = newData
  const payload = {
    data: [newData],
    sublingkupfk: SUBLINGKUP,
    norec_detail: NOREC_DETAIL
  }
  isLoadingSave.value = true
  try {
    await useApi().post(
      `/pelaksana/edit-lembar-kerja`,
      payload
    )
  }
  catch (err: any) {
    console.error(err)
  }
  finally {
    isLoadingSave.value = false
    fetchData()
  }
}

const onSelectSuhu = async (filez: any) => {
  const file = filez.files[0];
  if (!file) return;

  if (file.size > 10000000) {
    H.alert('error', 'Maksimal ukuran file adalah 10 MB');
    return;
  }

  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
  if (!allowedTypes.includes(file.type)) {
    H.alert('error', 'File yang diizinkan hanya gambar JPG, JPEG, atau PNG');
    return;
  }
  fileSuhu.value = file;
};


const addNewAlat = () => {
  item.value.detailInstruksiKerja.push({
    no: item.value.detailInstruksiKerja[item.value.detailInstruksiKerja.length - 1].no + 1
  });
}

const removeAlat = (index: any) => {
  item.value.detailInstruksiKerja.splice(index, 1)
  if (item.value.detailInstruksiKerja.length == 0) {
    item.value.detailInstruksiKerja.push({
      no: 1
    });
  }
}

const addNewAlatStandar = () => {
  item.value.detailPeralatanStandar.push({
    no: item.value.detailPeralatanStandar[item.value.detailPeralatanStandar.length - 1].no + 1
  });
}

const removeAlatStandar = (index: any) => {
  item.value.detailPeralatanStandar.splice(index, 1)
  if (item.value.detailPeralatanStandar.length == 0) {
    item.value.detailPeralatanStandar.push({
      no: 1
    });
  }
}


const showScanner = ref(false)
const scannerRowIdx = ref<number | null>(null)
let html5QrCode: Html5Qrcode | null = null
const scannerItem = ref<any>(null)

async function openScanner(row: any) {
  scannerItem.value = row
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
      console.error("Failed to start QR scanner", err)
      cancelScanner()
    })
}

async function cancelScanner() {
  showScanner.value = false
  if (html5QrCode) {
    await html5QrCode.stop().catch(() => { })
    html5QrCode = null
  }
  scannerRowIdx.value = null
}

async function onScanSuccess(decodedText: string) {
  await html5QrCode?.stop().catch(() => { })
  showScanner.value = false
  let id_alat: string | null = null
  try {
    const url = new URL(decodedText)
    id_alat = url.searchParams.get("id_alat")
  } catch {
    console.error("QR does not contain a valid URL:", decodedText)
  }
  if (!id_alat || !scannerItem.value) {
    scannerItem.value = null
    return
  }

  const { data } = await useApi().get(
    `pelaksana/alat-standar?id_alat=${id_alat}`
  )
  if (!data.length) {
    scannerItem.value = null
    return
  }
  const e = data[0]
  const option = {
    label: `${e.namaalatstandar} - ${e.namamerk} ${e.namatipe} (${e.namaserialnumber})`,
    value: e.id
  }

  d_alatstandar.value = [option, ...d_alatstandar.value.filter(o => o.value !== option.value)]
  scannerItem.value.alat = option
  scannerItem.value = null
}


const fetchAlatStandar = async (filter: any) => {
  await useApi().get(
    `pelaksana/alat-standar?param_search=namaalatstandar,namamerk,namatipe,namaserialnumber&query=${filter.query}`
  ).then((response) => {
    d_alatstandar.value = response.data.map((e: any) => {
      return {
        label: `${e.namaalatstandar} - ${e.namamerk} ${e.namatipe} (${e.namaserialnumber})`,
        value: e.id
      };
    });
  });
}

const fetchRuangan = async (filter: any) => {
  await useApi().get(
    `general/dropdown/ruangan_m?select=id,namaruangan&param_search=namaruangan&query=${filter.query}&limit=10`
  ).then((response) => {
    d_ruangan.value = response
  })
}

const downloadTemplate = () => {
  window.open('/service/pelaksana/download-template-lembar-kerja-dryblock?token=' + useUserSession().token, '_blank');
}

const downloadFileTerunggah = () => {
  const norec = NOREC_DETAIL
  const token = useUserSession().token
  const url = `/service/pelaksana/download-file-terunggah?norec=${norec}&token=${token}`
  window.open(url, '_blank')
}

const fetchData = async () => {
  loadSearch.value = true
  try {
    const response = await useApi().get(`pelaksana/get-lembar-kerja-dryblock?norecdetail=${NOREC_DETAIL}`);
    response.forEach((element: any, i: any) => {
      element.no = i + 1
    });
    dataSourceHasilKalibrasi.value = response.filter((item: any) => item.jenis === 'kalibrasi')
    dataSourceHasilKarakteristik.value = response.filter((item: any) => item.jenis === 'karakteristik')
  } catch (err) {
    dataSourceHasilKalibrasi.value = []
    dataSourceHasilKarakteristik.value = []
  }
  loadSearch.value = false
}

const onSelectedFiles = async (event: any) => {
  const files = event.files;
  if (!files || files.length === 0) return;

  const file = files[files.length - 1];
  if (!file) return;

  item.fileName = file.name;

  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target!.result as ArrayBuffer);
    const workbook = XLSX.read(data, { type: 'array' });
    const worksheet = workbook.Sheets[workbook.SheetNames[0]];
    const rows: any[][] = XLSX.utils.sheet_to_json(worksheet, { header: 1, raw: false });

    const kalibrasiData: any[] = [];
    const karakteristikData: any[] = [];

    let nomor = 1;                    // kalau mau Karakteristik mulai dari 1 lagi, reset nomor=1 saat switch ke Karakteristik
    let isKalibrasi = false;
    let isKarakteristik = false;
    let headerRowCount = 0;

    const isBlankRow = (r: any[]) => r.every(c => c == null || String(c).trim() === '');

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i] ?? [];
      const joined = row.map(v => (v ?? '')).join(' ').toLowerCase();

      // MULAI TABEL KALIBRASI -> baris match adalah header level-2 (ada "Instrument Reading")
      if (!isKalibrasi && /instrument\s*reading/.test(joined)) {
        isKalibrasi = true;
        isKarakteristik = false;
        headerRowCount = 2; // baris ini = header ke-2, jadi setelah ini langsung data
        continue;
      }

      // MULAI TABEL KARAKTERISTIK -> baris match adalah header level-1 (ada "Kedalaman Pencelupan")
      if (!isKarakteristik && /kedalaman\s*pencelupan/.test(joined)) {
        isKalibrasi = false;
        isKarakteristik = true;
        headerRowCount = 1; // masih ada sub-header 1 baris lagi yang harus di-skip
        // nomor = 1; // <-- uncomment jika ingin numbering Karakteristik mulai dari 1 lagi
        continue;
      }

      // belum di dalam tabel apa pun
      if (!isKalibrasi && !isKarakteristik) continue;

      // skip sisa header sesuai tabel aktif
      if (headerRowCount < 2) {
        headerRowCount++;
        continue;
      }

      // skip row kosong / pemisah
      if (isBlankRow(row)) continue;

      // baris data minimal kolom 0 & 2 harus ada (Set Poin dan Penunjukan Standar)
      if (row[0] == null || row[2] == null || String(row[0]).trim() === '') continue;

      const commonFields = {
        no: nomor++,
        set_poin: row[0] ?? '',
        set_poin_satuan: row[1] ?? '',
        penunjukan_standar: row[2] ?? '',
        penunjukan_standar_satuan: row[3] ?? '',
      };

      if (isKalibrasi) {
        kalibrasiData.push({
          ...commonFields,
          pembacaan_alat: row[4] ?? '',
          pembacaan_alat_satuan: row[5] ?? '',
          koreksi: row[6] ?? '',
          koreksi_satuan: row[7] ?? '',
          ketidakpastian: row[8] ?? '',
          ketidakpastian_satuan: row[9] ?? '',
        });
      } else if (isKarakteristik) {
        karakteristikData.push({
          ...commonFields,
          kedalaman_pencelupan: row[4] ?? '',
          kedalaman_pencelupan_satuan: row[5] ?? '',
          perbedaan_suhu: row[6] ?? '',
          perbedaan_suhu_satuan: row[7] ?? '',
          keseragaman_suhu: row[8] ?? '',
          keseragaman_suhu_satuan: row[9] ?? '',
          kestabilan_suhu: row[10] ?? '',
          kestabilan_suhu_satuan: row[11] ?? '',
        });
      }
    }

    dataSourceKalibrasi.value = kalibrasiData;
    dataSourceKarakteristik.value = karakteristikData;
  };

  reader.readAsArrayBuffer(file);
};

const onTemplatedUpload = (e: any) => {
}

const formatSize = (bytes: any) => {
  if (bytes === 0) return "0 B";
  const k = 1024;
  const sizes = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const uploadEvent = (callback: any) => {
  totalSizePercent.value = totalSize.value / 10;
  isLoadingUpload.value = true
};

const onRemoveTemplatingFile = (file: any, removeFileCallback: any, index: any) => {
  removeFileCallback(index);
  totalSize.value = parseInt(formatSize(file.size));
  totalSizePercent.value = totalSize.value / 10;
  dataSourceKalibrasi.value = [];
  dataSourceKarakteristik.value = [];
  valueProgress.value = 0;
};
const dataSourcefilter = computed(() => {
  return [...dataSourceKalibrasi.value, ...dataSourceKarakteristik.value];
});

const onSelect = async (filez: any) => {
  const file = filez.files[0];

  console.log(file.size);
  if (file.size > 30000000) {
    H.alert('error', 'Maksimal file size adalah 30 MB');
    return;
  }

  fileMitraExcel.value = file;
};


const simpanExcelLembarKerja = async () => {
  if (!fileMitraExcel.value) {
    H.alert('error', 'File harus diunggah')
    return
  }

  const formData = new FormData()
  formData.append('fileMitraExcel', fileMitraExcel.value)
  formData.append('norec', NOREC_DETAIL)

  isLoadingSave.value = true

  try {
    const r = await useApi().post('/pelaksana/save-excel-lembar-kerja', formData)
    fileMitraExcel.value = null

    H.alert('success', 'File berhasil diunggah')
  } catch (error: any) {
    console.error('Error saat menyimpan berkas excel:', error)
    if (error.response) {
      H.alert('error', `Kesalahan: ${error.response.status} - ${error.response.data.message || 'Gagal menyimpan berkas mitra'}`)
    } else if (error.request) {
      H.alert('error', 'Tidak ada respons dari server. Silakan coba lagi.')
    } else {
      H.alert('error', `Terjadi kesalahan: ${error.message}`)
    }
  } finally {
    isLoadingSave.value = false
  }
}

const isFile = (obj: any): obj is File => {
  return obj instanceof File || (obj && typeof obj === 'object' && 'name' in obj && 'size' in obj && 'type' in obj);
};

const Save = async () => {
  if (!item.value.tglkalibrasi) {
    H.alert('warning', 'Tgl Kalibrasi harus di isi'); return;
  }
  if (!item.value.tempatKalibrasi) {
    H.alert('warning', 'Tempat Kalibrasi harus di isi'); return;
  }
  if (!item.value.resolusi) {
    H.alert('warning', 'Resolusi harus di isi'); return;
  }
  if (!item.value.rentangukursuhu) {
    H.alert('warning', 'Rentang Ukur Suhu harus di isi'); return;
  }
  if (!item.value.suhu) {
    H.alert('warning', 'Suhu harus di isi'); return;
  }
  if (!item.value.kelembabanRelatif) {
    H.alert('warning', 'Kelembaban Relatif harus di isi'); return;
  }
  if (!item.value.notes) { H.alert('warning', 'Catatan/Notes harus di isi'); return }

  const mappInstruksiKinerja = item.value.detailInstruksiKerja.map((items: any) => ({
    instruksikerja: items.daftarInstruksiKerja?.value || null
  }));

  const mappPeralatanStandar = item.value.detailPeralatanStandar.map((items: any) => ({
    peralatanstandar: items.daftaralatstandar?.value || null
  }));

  const formData = new FormData()
  if (isFile(fileSuhu.value)) {
    formData.append('fileSuhu', fileSuhu.value);
  } else if (item.value.gambarsuhu) {
    formData.append('namaFileLama', item.value.gambarsuhu);
  }
  formData.append('fileSuhu', fileSuhu.value)
  formData.append('data', JSON.stringify(dataSourcefilter.value))
  formData.append('sublingkupfk', SUBLINGKUP)
  formData.append('fileName', item.fileName)
  formData.append('norec_detail', NOREC_DETAIL)
  formData.append('tglkalibrasi', H.formatDate(item.value.tglkalibrasi, 'YYYY-MM-DD'))
  formData.append('tempatKalibrasi', item.value.tempatKalibrasi.value)
  formData.append('resolusi', item.value.resolusi)
  formData.append('rentangukursuhu', item.value.rentangukursuhu)
  formData.append('suhu', item.value.suhu)
  formData.append('kelembabanRelatif', item.value.kelembabanRelatif)
  formData.append('kedalamalubang', item.value.kedalamalubang)
  formData.append('lubangacuan', item.value.lubangacuan)
  formData.append('lubang1', item.value.lubang1)
  formData.append('lubang2', item.value.lubang2)
  formData.append('notes', item.value.notes)
  formData.append('daftarinstruksikerja', JSON.stringify(mappInstruksiKinerja))
  formData.append('daftarperalatanstandar', JSON.stringify(mappPeralatanStandar))

  isLoadingSave.value = true;
  await useApi().post(
    `/pelaksana/save-data-upload-lembar-kerja-dryblock`, formData
  ).then((response: any) => {
    isLoadingSave.value = false;
    totalSizePercent.value = 0;
    dataSourceKalibrasi.value = [];
    dataSourceKarakteristik.value = [];
    valueProgress.value = 0;
    fetchData();
  }).catch((e: any) => {
    isLoadingSave.value = false;
  });
};

const kembali = () => {
  window.history.back()
}

const detailOrder = async () => {
  const response = await useApi().get(`/pelaksana/detail-produk-lembar-kerja?norec_pd=${NOREC_DETAIL}`)
  const data = response.data[0]

  item.value.namaproduk = data.namaproduk
  item.value.namamerk = data.namamerk
  item.value.namatipe = data.namatipe
  item.value.namaserialnumber = data.namaserialnumber

  item.value.fotoproduk = data.fotoproduk
  item.value.durasikalbrasi = data.durasikalbrasi
  item.value.tglkalibrasi = data.tglkalibrasilembarkerja ?? new Date(),
    item.value.tempatKalibrasi = {
      value: data?.idruangan ?? '',
      label: data?.tempatKalibrasilembarkerja ?? ''
    };
  item.value.resolusi = data.resolusi
  item.value.rentangukursuhu = data.rentangukursuhu
  item.value.suhu = data.suhulembarkerja
  item.value.kelembabanRelatif = data.kelembabanRelatiflembarkerja
  item.value.kedalamalubang = data.kedalamalubang
  item.value.lubangacuan = data.lubangacuan
  item.value.lubang1 = data.lubang1
  item.value.lubang2 = data.lubang2
  item.value.gambarsuhu = data.gambarsuhu;
  item.value.notes = data.noteslembarkerja
  fileSuhu.value = null;
  item.value.detailInstruksiKerja = (data.daftarinstruksikerja?.length > 0)
    ? data.daftarinstruksikerja.map(i => ({
      daftarInstruksiKerja: {
        value: i.value ?? '',
        label: i.label ?? ''
      }
    }))
    : [{ daftarInstruksiKerja: { value: '', label: '' } }]
  item.value.detailPeralatanStandar = (data.daftaralatstandar?.length > 0)
    ? data.daftaralatstandar.map(a => ({
      daftaralatstandar: {
        value: a.value ?? '',
        label: `${a.namaalatstandar || ''} - ${a.namamerk || ''} ${a.namatipe || ''} (${a.namaserialnumber || ''})`
      }
    }))
    : [{
      daftaralatstandar: { value: '', label: '' }
    }];


  const norec = NOREC_DETAIL
  const r = await useApi().get(`/pelaksana/excel-length?norec=${norec}`)
  dataExcel.value = r.data.namafileexcel

}

const cetakSertifikatLembarKerja = () => {
  console.log(dataSourceHasilKalibrasi.value)

  H.printBlade(`pelaksana/cetak-sertifikat-lembar-kerja?pdf=true&norec=${dataSourceHasilKalibrasi.value[0].norecregis}&norec_detail=${dataSourceHasilKalibrasi.value[0].detailregistraifk}`);
}

detailOrder()
fetchData()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/bedah.scss';
</style>
