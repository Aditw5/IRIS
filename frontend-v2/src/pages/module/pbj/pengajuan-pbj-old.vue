<template>
  <div class="column">
    <VCard class="kp-header-card">
      <div class="kp-hero">
        <div class="kp-hero-left">
          <img src="/@src/assets/illustrations/dashboards/personal/UMRO.png" alt="UMRO Laboratory"
            class="kp-hero-logo" />
          <div class="kp-hero-text">
            <h3 class="kp-hero-title">Pengajuan PBJ</h3>
            <p class="kp-hero-sub">Permintaan Barang dan Jasa</p>
          </div>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <div class="columns is-variable is-5">
      <div class="column is-12">
        <VCard>
          <div class="is-flex is-align-items-center is-justify-content-space-between">
            <div class="is-flex is-align-items-center" style="gap:.75rem">
              <h3 class="title is-6">Pengajuan</h3>

              <VTag v-if="editMode" :color="editingStatusColor" rounded>
                {{ editingStatusText }} · No Surat: {{ editingNoSurat || '-' }}
              </VTag>
            </div>

            <div class="is-flex is-align-items-center" style="gap: 0.5rem">
              <VButton v-if="editMode" size="medium" outlined color="danger" icon="feather:x" @click="cancelEdit()">
                Batal Edit
              </VButton>

              <VButton v-if="canDeleteDraft" size="medium" outlined color="danger" icon="feather:trash-2"
                :loading="isLoading" @click="deleteDraft()">
                Hapus Draft
              </VButton>

              <VButton v-if="canSubmitDraft" size="medium" :loading="isLoading" color="warning" icon="feather:send"
                @click="submitToAsman()">
                {{ submitButtonText }}
              </VButton>

              <VButton size="medium" :loading="isLoading" outlined color="primary" icon="feather:save"
                @click="savePengajuanPbj()">
                {{ !editMode ? 'Simpan Draft' : (draftEditingFlag ? 'Update Draft' : 'Update Revisi') }}
              </VButton>
            </div>
          </div>

          <div class="list-view list-view-v3 mt-3">
            <div class="columns is-multiline">
              <div class="column is-3">
                <VField>
                  <template #label>
                    Judul permintaan <span class="required-star">*</span>
                  </template>
                  <VControl>
                    <VInput v-model="item.judulpermintaan" placeholder="Judul Permintaan" />
                  </VControl>
                </VField>
              </div>

              <div class="column is-3">
                <VField class="is-rounded-select_Z is-autocomplete-select">
                  <template #label>
                    Material/Jasa <span class="required-star">*</span>
                  </template>
                  <VControl icon="fa:search" fullwidth class="prime-auto">
                    <AutoComplete v-model="item.jenispbj" :suggestions="d_jenispbj" @complete="fetchJenisPBJ($event)"
                      :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                      :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
                  </VControl>
                </VField>
              </div>

              <div class="column is-3">
                <VField>
                  <template #label>
                    Dasar Anggaran <span class="required-star">*</span>
                  </template>
                  <div class="toggle-row">
                    <label class="radio">
                      <input type="radio" value="dropdown" v-model="item.kebutuhanMode" /> Dropdown
                    </label>
                    <label class="radio">
                      <input type="radio" value="manual" v-model="item.kebutuhanMode" /> Manual
                    </label>
                  </div>

                  <div v-if="item.kebutuhanMode === 'dropdown'" class="mt-2">
                    <VField class="is-rounded-select_Z is-autocomplete-select">
                      <VControl icon="fa:search" fullwidth class="prime-auto">
                        <AutoComplete v-model="item.kebutuhan" :suggestions="d_dasaranggaran"
                          @complete="fetchDasarAnggaran($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                          class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                          placeholder="ketik untuk mencari..." />
                      </VControl>
                    </VField>
                    <small class="help mt-1 help-hint">Pilih dari master (sementara)</small>
                  </div>

                  <div v-else class="mt-2">
                    <VControl>
                      <VInput v-model="item.kebutuhanmanual" placeholder="Isi dasar anggaran manual..." />
                    </VControl>
                    <small class="help mt-1 help-hint">Isi manual jika tidak ada di dropdown</small>
                  </div>
                </VField>
              </div>

              <div class="column is-3">
                <VField>
                  <template #label>
                    Bidang Pemohon <span class="required-star">*</span>
                  </template>
                  <div v-if="item.pemohonMode === 'dropdown'" class="mt-2">
                    <VField class="is-rounded-select_Z is-autocomplete-select">
                      <VControl icon="fa:search" fullwidth class="prime-auto">
                        <AutoComplete v-model="item.pemohon" :suggestions="d_pemohonpbj"
                          @complete="fetchPemohonPBJ($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                          class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                          placeholder="ketik untuk mencari..." />
                      </VControl>
                    </VField>
                  </div>
                </VField>
              </div>

              <div class="column is-3">
                <VField class="is-rounded-select_Z is-autocomplete-select">
                  <template #label>
                    Prioritas<span class="required-star">*</span>
                  </template>
                  <VControl icon="fa:search" fullwidth class="prime-auto">
                    <AutoComplete v-model="item.prioritas" :suggestions="d_prioritaspbj"
                      @complete="fetchPrioritasPBJ($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                      class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                      placeholder="ketik untuk mencari..." />
                  </VControl>
                </VField>
              </div>

              <div class="column is-3">
                <VField>
                  <template #label>
                    User Unit <span class="required-star">*</span>
                  </template>
                  <div class="toggle-row">
                    <label class="radio">
                      <input type="radio" value="dropdown" v-model="item.userMode" /> Dropdown
                    </label>
                    <label class="radio">
                      <input type="radio" value="manual" v-model="item.userMode" /> Manual
                    </label>
                  </div>

                  <div v-if="item.userMode === 'dropdown'" class="mt-2">
                    <VField class="is-rounded-select_Z is-autocomplete-select">
                      <VControl icon="fa:search" fullwidth class="prime-auto">
                        <AutoComplete v-model="item.userDropdown" :suggestions="d_unit" @complete="fetchUnit($event)"
                          :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                          :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
                      </VControl>
                    </VField>
                    <small class="help mt-1 help-hint">Dropdown sementara pakai master unit surkes</small>
                  </div>

                  <div v-else class="mt-2">
                    <VControl>
                      <VInput v-model="item.userManual" placeholder="Isi user manual..." />
                    </VControl>
                    <small class="help mt-1 help-hint">Isi manual</small>
                  </div>
                </VField>
              </div>

              <div class="column is-3">
                <VField label="Nomer Proyek">
                  <VControl>
                    <VInput v-model="item.noproyek" placeholder="Nomor Proyek" />
                  </VControl>
                </VField>
              </div>

              <div class="column is-3">
                <VField>
                  <template #label>
                    Manager Bidang <span class="required-star">*</span>
                  </template>
                  <div class="toggle-row">
                    <label class="radio">
                      <input type="radio" value="dropdown" v-model="item.bidangMode" /> Dropdown
                    </label>
                    <label class="radio">
                      <input type="radio" value="manual" v-model="item.bidangMode" /> Manual
                    </label>
                  </div>

                  <div v-if="item.bidangMode === 'dropdown'" class="mt-2">
                    <VField class="is-rounded-select_Z is-autocomplete-select">
                      <VControl icon="fa:search" fullwidth class="prime-auto">
                        <AutoComplete v-model="item.bidangDropdown" :suggestions="d_managerbidang"
                          @complete="fetchManagerbidang($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                          class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                          placeholder="ketik untuk mencari..." />
                      </VControl>
                    </VField>
                    <small class="help mt-1 help-hint">Dropdown sementara pakai master manager bidang</small>
                  </div>

                  <div v-else class="mt-2">
                    <VControl>
                      <VInput v-model="item.bidangManual" placeholder="Isi bidang manual..." />
                    </VControl>
                    <small class="help mt-1 help-hint">Isi manual</small>
                  </div>
                </VField>
              </div>

              <div class="column is-3">
                <VField>
                  <template #label>
                    Kepada <span class="required-star">*</span>
                  </template>
                  <div class="toggle-row">
                    <label class="radio">
                      <input type="radio" value="dropdown" v-model="item.kepadaMode" /> Dropdown
                    </label>
                    <label class="radio">
                      <input type="radio" value="manual" v-model="item.kepadaMode" /> Manual
                    </label>
                  </div>

                  <div v-if="item.kepadaMode === 'dropdown'" class="mt-2">
                    <VField class="is-rounded-select_Z is-autocomplete-select">
                      <VControl icon="fa:search" fullwidth class="prime-auto">
                        <AutoComplete v-model="item.kepadaDropdown" :suggestions="d_kepadaPBJ"
                          @complete="fetchKepadaPBJ($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                          class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                          placeholder="ketik untuk mencari..." />
                      </VControl>
                    </VField>
                    <small class="help mt-1 help-hint">Dropdown sementara pakai master kepada PBJ</small>
                  </div>

                  <div v-else class="mt-2">
                    <VControl>
                      <VInput v-model="item.kepadaManual" placeholder="Isi kepada manual..." />
                    </VControl>
                    <small class="help mt-1 help-hint">Isi manual</small>
                  </div>
                </VField>
              </div>

              <div class="column is-3">
                <VField>
                  <template #label>
                    PRK <span class="required-star">*</span>
                  </template>

                  <VField v-if="isAoSelected" class="is-rounded-select_Z is-autocomplete-select">
                    <VControl icon="fa:search" fullwidth class="prime-auto">
                      <AutoComplete v-model="item.prkMaster" :suggestions="d_prk" @complete="fetchPrk($event)"
                        :optionLabel="'label'" :dropdown="true" :minLength="0" :forceSelection="true"
                        class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                        placeholder="Pilih PRK AO..." />
                    </VControl>
                    <small class="help mt-1 help-hint">PRK untuk AO wajib dipilih dari master</small>
                  </VField>

                  <VControl v-else>
                    <VInput v-model="item.prk" placeholder="PRK" />
                  </VControl>
                </VField>
              </div>

              <div class="column is-3">
                <VField label="WO">
                  <VControl>
                    <VInput v-model="item.wo" placeholder="WO" />
                  </VControl>
                </VField>
              </div>

              <div class="column is-3">
                <VField class="is-rounded-select_Z is-autocomplete-select">
                  <template #label>
                    Pengadaan <span class="required-star">*</span>
                  </template>
                  <VControl icon="fa:search" fullwidth class="prime-auto">
                    <AutoComplete v-model="item.pengadaan" :suggestions="d_pengadaanpbj"
                      @complete="fetchPengadaanPBJ($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                      class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                      placeholder="ketik untuk mencari..." />
                  </VControl>
                </VField>
              </div>

              <div class="column is-3">
                <VField label="Cost Code">
                  <VControl>
                    <VInput v-model="item.costcode" placeholder="Cost Code" />
                  </VControl>
                </VField>
              </div>

              <div class="column is-1">
                <VField label="PPN">
                  <VControl>
                    <VInput type="number" inputmode="numeric" v-model="item.ppn" placeholder="PPN" />
                  </VControl>
                </VField>
              </div>

              <div class="column is-3">
                <VField class="is-rounded-select_Z is-autocomplete-select">
                  <template #label>
                    Lokasi PBJ <span class="required-star">*</span>
                  </template>
                  <VControl icon="fa:search" fullwidth class="prime-auto">
                    <AutoComplete v-model="item.lokasipbj" :suggestions="d_lokasipbj" @complete="fetchLokasiPBJ($event)"
                      :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                      :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
                  </VControl>
                </VField>
              </div>

              <div class="column is-6">
                <VField>
                  <template #label>
                    Catatan
                    <span class="optional-label">Opsional</span>
                  </template>
                  <VControl>
                    <VTextarea v-model="item.notes" rows="3" placeholder="Isi catatan jika diperlukan..."
                      autocomplete="off" autocapitalize="off" spellcheck="true" />
                  </VControl>
                  <small class="help mt-1 help-hint">Catatan tidak wajib diisi</small>
                </VField>
              </div>

              <div class="column is-12 mt-4">
                <input ref="replaceIhtInput" type="file" accept="application/pdf,.pdf" class="is-hidden"
                  @change="replaceIhtFile" />

                <FileUpload :key="uploaderKeyIht" ref="uploaderIht" mode="advanced" name="fileIht[]" :multiple="true"
                  accept="application/pdf" :maxFileSize="10000000" :auto="false" :customUpload="true"
                  :showUploadButton="false" :showCancelButton="false" @select="onSelectFilesIht($event)" outlined
                  style="background-color: transparent; color: var(--primary); border: 1px solid"
                  class="is-rounded w-100" :chooseLabel="'Upload IH (PDF, Bisa Banyak)'"
                  @remove="onSelectFilesIht($event)" @clear="filesIht = []" />
                <small class="help mt-1 help-hint">Lampiran IH dapat lebih dari satu file</small>

                <small v-if="editMode" class="help mt-1 help-hint">
                  *Catatan: Kelola file IH lama melalui daftar IH tersimpan di bawah. Ganti dan hapus tersedia saat
                  PBJ masih draft atau sedang direvisi karena ditolak.
                </small>

                <div v-if="editMode" class="saved-iht-panel mt-4">
                  <div class="is-flex is-align-items-center is-justify-content-space-between mb-3">
                    <div>
                      <p class="saved-iht-title">IH Tersimpan</p>
                      <small class="help-hint">{{ existingIhtFiles.length }} file tersimpan</small>
                    </div>
                    <VTag :color="canManageEditingIht ? 'success' : 'danger'" rounded>
                      {{ canManageEditingIht ? editingIhtAccessText : 'Terkunci' }}
                    </VTag>
                  </div>

                  <DataTable v-if="existingIhtFiles.length" :value="existingIhtFiles" class="p-datatable-sm">
                    <Column header="No" style="width:70px; text-align:center">
                      <template #body="slotProps">
                        {{ (slotProps.index ?? 0) + 1 }}
                      </template>
                    </Column>
                    <Column field="namafileiht" header="Nama File" />
                    <Column header="Aksi" style="width:220px; text-align:center">
                      <template #body="slotProps">
                        <VIconButton v-tooltip.bottom="'Cetak IH'" class="mr-2" color="danger" outlined circle
                          icon="feather:printer" @click="cetakIHT(slotProps.data)" />
                        <VIconButton v-if="canManageEditingIht" v-tooltip.bottom="'Ganti IH'" class="mr-2"
                          color="primary" outlined circle icon="feather:upload"
                          :loading="isIhtActionLoading && activeIhtAction === 'replace' && activeIhtNorec === slotProps.data.norec"
                          :disabled="isIhtActionLoading" @click="chooseReplacementIht(slotProps.data)" />
                        <VIconButton v-if="canManageEditingIht" v-tooltip.bottom="'Hapus IH'" color="danger" outlined
                          circle icon="feather:trash-2"
                          :loading="isIhtActionLoading && activeIhtAction === 'delete' && activeIhtNorec === slotProps.data.norec"
                          :disabled="isIhtActionLoading" @click="deleteIht(slotProps.data)" />
                      </template>
                    </Column>
                  </DataTable>

                  <div v-else class="saved-iht-empty">
                    Belum ada file IH tersimpan. Gunakan tombol Upload IH di atas untuk menambahkan file.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <Fieldset class="mt-5" legend="Detail PBJ" :toggleable="true">
            <div class="mt-5 form-section-inner is-horizontal">
              <div class="table-responsive">
                <table class="table-po"
                  style="width: 100%; table-layout: fixed; border-collapse: collapse; border: 1px solid #e5e7eb">
                  <colgroup>
                    <col style="width: 14%" />
                    <col style="width: 24%" />
                    <col style="width: 8%" />
                    <col style="width: 8%" />
                    <col style="width: 8%" />
                    <col style="width: 12%" />
                    <col style="width: 20%" />
                    <col style="width: 6%" />
                  </colgroup>

                  <thead>
                    <tr class="tr-po">
                      <th class="th-po" style="text-align:center; padding: 10px 8px; border: 1px solid #e5e7eb">
                        Nama Item
                      </th>
                      <th class="th-po" style="text-align:center; padding: 10px 8px; border: 1px solid #e5e7eb">
                        Uraian Item
                      </th>
                      <th class="th-po" style="text-align:center; padding: 10px 8px; border: 1px solid #e5e7eb">
                        Stock Code
                      </th>
                      <th class="th-po" style="text-align:center; padding: 10px 8px; border: 1px solid #e5e7eb">
                        Banyak
                      </th>
                      <th class="th-po" style="text-align:center; padding: 10px 8px; border: 1px solid #e5e7eb">
                        Satuan
                      </th>
                      <th class="th-po" style="text-align:center; padding: 10px 8px; border: 1px solid #e5e7eb">
                        Harga Satuan
                      </th>
                      <th class="th-po" style="text-align:center; padding: 10px 8px; border: 1px solid #e5e7eb">
                        Keterangan
                      </th>
                      <th class="th-po" style="text-align:center; padding: 10px 8px; border: 1px solid #e5e7eb">
                        Aksi
                      </th>
                    </tr>
                  </thead>

                  <tbody v-for="(items, index) in input.detailOrderPBJ" :key="items._uid || index">
                    <tr class="tr-po">
                      <td style="vertical-align: top; padding: 8px; border: 1px solid #e5e7eb">
                        <VField>
                          <VControl>
                            <VInput v-model="items.namaitem" placeholder="Nama Item"
                              style="width: 100%; height: 38px; font-size: 14px; box-sizing: border-box" />
                          </VControl>
                        </VField>
                      </td>

                      <td style="vertical-align: top; padding: 8px; border: 1px solid #e5e7eb">
                        <VField>
                          <VControl>
                            <VTextarea v-model="items.uraianitem" rows="4" placeholder="Uraian Item ..."
                              autocomplete="off" autocapitalize="off" spellcheck="true"
                              style="width: 100%; min-height: 100px; font-size: 14px; box-sizing: border-box" />
                          </VControl>
                        </VField>
                      </td>

                      <td style="vertical-align: top; padding: 8px; border: 1px solid #e5e7eb">
                        <VField>
                          <VControl>
                            <VInput v-model="items.stockcode" placeholder="Stock Code"
                              style="width: 100%; height: 34px; font-size: 13px; box-sizing: border-box" />
                          </VControl>
                        </VField>
                      </td>

                      <td style="vertical-align: top; padding: 8px; border: 1px solid #e5e7eb">
                        <VField>
                          <VControl>
                            <VInput type="number" v-model.number="items.banyak" placeholder="Banyak" min="0" step="1"
                              inputmode="numeric"
                              style="width: 100%; height: 34px; font-size: 13px; text-align: right; box-sizing: border-box" />
                          </VControl>
                        </VField>
                      </td>

                      <td style="vertical-align: top; padding: 8px; border: 1px solid #e5e7eb">
                        <VField>
                          <VControl>
                            <VInput v-model="items.satuan" placeholder="Satuan"
                              style="width: 100%; height: 34px; font-size: 13px; text-transform: uppercase; box-sizing: border-box" />
                          </VControl>
                        </VField>
                      </td>

                      <td style="vertical-align: top; padding: 8px; border: 1px solid #e5e7eb">
                        <VField>
                          <VControl>
                            <VInput v-model="items._hargaStr" @input="onHargaInput(items)"
                              @keydown.enter.prevent="commitHarga(items)" @blur="commitHarga(items)"
                              placeholder="Harga Satuan" inputmode="numeric" autocomplete="off" autocorrect="off"
                              spellcheck="false"
                              style="width: 100%; height: 34px; font-size: 13px; text-align: right; box-sizing: border-box" />
                          </VControl>
                        </VField>
                      </td>

                      <td style="vertical-align: top; padding: 8px; border: 1px solid #e5e7eb">
                        <VField>
                          <VControl>
                            <VTextarea v-model="items.keterangan" rows="4" placeholder="Keterangan ..."
                              autocomplete="off" autocapitalize="off" spellcheck="true"
                              style="width: 100%; min-height: 100px; font-size: 14px; box-sizing: border-box" />
                          </VControl>
                        </VField>
                      </td>

                      <td style="vertical-align: middle; padding: 8px; border: 1px solid #e5e7eb">
                        <div style="display:flex; gap: 8px; justify-content:center">
                          <VIconButton type="button" raised circle icon="feather:plus"
                            v-tooltip-prime.bottom="'Tambah di bawah baris ini'" @click="addNewDetailPBJ(index)"
                            outlined color="info" />
                          <VIconButton type="button" raised circle icon="feather:trash" v-tooltip-prime.bottom="'Hapus'"
                            @click="removeDetailPBJ(index)" outlined color="danger" />
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </Fieldset>
        </VCard>
      </div>
    </div>
  </div>

  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
        <h3 class="title is-5 mb-2">Riwayat Pengajuan PBJ</h3>
        <div class="is-flex is-align-items-center" style="gap:.5rem">
          <VTag :color="'info'" rounded>Draft</VTag>
          <VTag :color="'warning'" rounded>Diajukan</VTag>
          <VTag :color="'success'" rounded>Disetujui</VTag>
          <VTag :color="'danger'" rounded>Ditolak</VTag>
        </div>
      </div>

      <div class="legend-steps mb-4">
        <div class="legend-title">Legenda Progress</div>
        <div class="legend-scroller">
          <div v-for="s in stepsLegend" :key="s.step" class="legend-item">
            <span class="legend-num">{{ s.step }}</span>
            <span class="legend-label">{{ s.label }}</span>
          </div>
        </div>
      </div>

      <div class="column" v-if="isPlaceLoad">
        <VPlaceloadWrap v-for="data in 12" :key="data">
          <VPlaceload class="mx-2 mb-3" />
          <VPlaceload class="mx-2" />
        </VPlaceloadWrap>
      </div>

      <div class="column" v-else>
        <VPlaceholderPage v-if="dataSource.length == 0" title="Belum ada pengajuan PBJ"
          subtitle="Silakan lakukan pengajuan di atas" larger>
          <template #image>
            <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
            <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
          </template>
        </VPlaceholderPage>

        <div v-else>
          <DataTable v-model:expandedRows="expandedRows" dataKey="norec" :value="dataSource" class="p-datatable-sm"
            :loading="isLoading" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]" scrollable
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
            <ColumnGroup type="header">
              <Row>
                <Column header="Info" :rowspan="2" :frozen="true" style="background-color: #f8fafc" />
                <Column header="Detail" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Status" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Progress" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Cetak PBJ" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Cetak IH" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Aktivitas" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Edit" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Ajukan" :rowspan="2" style="background-color: #f8fafc" />
                <Column header="Data Pengajuan PBJ" :colspan="12"
                  style="background-color: #e0f2fe; color: #0369a1; font-weight: bold; text-align: center" />
                <Column header="Rendal" :colspan="2"
                  style="background-color: #fef9c3; color: #a16207; font-weight: bold; text-align: center" />
                <Column header="Inventory" :colspan="4"
                  style="background-color: #dcfce7; color: #15803d; font-weight: bold; text-align: center" />
                <Column header="Pengadaan" :colspan="9"
                  style="background-color: #ffedd5; color: #c2410c; font-weight: bold; text-align: center" />
                <Column header="BA1" :colspan="4"
                  style="background-color: #f3e8ff; color: #7e22ce; font-weight: bold; text-align: center" />
                <Column header="BA2" :colspan="3"
                  style="background-color: #fae8ff; color: #a21caf; font-weight: bold; text-align: center" />
                <Column header="Pembayaran" :colspan="4"
                  style="background-color: #e0e7ff; color: #4338ca; font-weight: bold; text-align: center" />
                <Column header="Pembebanan" :colspan="3"
                  style="background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: center" />
                <Column header="Limit" :colspan="2"
                  style="background-color: #fee2e2; color: #991b1b; font-weight: bold; text-align: center" />
              </Row>
              <Row>
                <Column header="No Surat PBJ" :sortable="true" style="background-color: #f0f9ff" />
                <Column header="Tanggal Pengajuan" style="background-color: #f0f9ff" />
                <Column header="Nama Pegawai" style="background-color: #f0f9ff" />
                <Column header="Judul" style="background-color: #f0f9ff" />
                <Column header="Pemohon" style="background-color: #f0f9ff" />
                <Column header="Prioritas" style="background-color: #f0f9ff" />
                <Column header="Pengadaan" style="background-color: #f0f9ff" />
                <Column header="Dasar Anggaran" style="background-color: #f0f9ff" />
                <Column header="User" style="background-color: #f0f9ff" />
                <Column header="Bidang" style="background-color: #f0f9ff" />
                <Column header="Kepada" style="background-color: #f0f9ff" />
                
                <Column header="Catatan" style="background-color: #f0f9ff" />
                <Column header="No PR" style="background-color: #fefce8" />
                <Column header="Tgl PR" style="background-color: #fefce8" />
                <Column header="Tgl RO" style="background-color: #f0fdf4" />
                <Column header="Tgl HPE" style="background-color: #f0fdf4" />
                <Column header="Tgl RKS" style="background-color: #f0fdf4" />
                <Column header="Tgl Penyerahan" style="background-color: #f0fdf4" />
                <Column header="Aanwijzing" style="background-color: #fff7ed" />
                <Column header="Klartek" style="background-color: #fff7ed" />
                <Column header="Pembukaan" style="background-color: #fff7ed" />
                <Column header="Tgl PP" style="background-color: #fff7ed" />
                <Column header="No PO" style="background-color: #fff7ed" />
                <Column header="Nilai PO" style="background-color: #fff7ed" />
                <Column header="Tgl PO" style="background-color: #fff7ed" />
                <Column header="Pelaksana" style="background-color: #fff7ed" />
                <Column header="HPS" style="background-color: #fff7ed" />
                <Column header="SPPP" style="background-color: #faf5ff" />
                <Column header="Mulai" style="background-color: #faf5ff" />
                <Column header="Selesai" style="background-color: #faf5ff" />
                <Column header="Denda" style="background-color: #faf5ff" />
                <Column header="No BA" style="background-color: #fdf4ff" />
                <Column header="Posisi" style="background-color: #fdf4ff" />
                <Column header="Status" style="background-color: #fdf4ff" />
                <Column header="Verif Invoice" style="background-color: #eef2ff" />
                <Column header="No BKK" style="background-color: #eef2ff" />
                <Column header="Status" style="background-color: #eef2ff" />
                <Column header="Ket" style="background-color: #eef2ff" />
                <Column header="No Jurnal" style="background-color: #f8fafc" />
                <Column header="Periode" style="background-color: #f8fafc" />
                <Column header="Ket" style="background-color: #f8fafc" />
                <Column header="Periode Limit" style="background-color: #fef2f2" />
                <Column header="Ket" style="background-color: #fef2f2" />
              </Row>
            </ColumnGroup>

            <template #header>
              <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="is-flex is-align-items-center" style="gap:.5rem">
                  <VButton color="warning" class="mr-2 mb-3" icon="fas fa-file-excel" raised @click="exportExcel()">
                    Export Excel
                  </VButton>
                  <VButton color="info" class="mb-3" icon="feather:refresh-ccw" outlined @click="fetchDataRiwayat()">
                    Refresh
                  </VButton>
                </div>
              </div>
            </template>

            <Column field="no" frozen></Column>
            <Column expander style="width:3rem" />

            <Column field="statusText" style="min-width: 140px">
              <template #body="slotProps">
                <VTag class="ml-2" :color="slotProps.data.color" rounded>
                  {{ slotProps.data.statusText }}
                </VTag>
              </template>
            </Column>

            <Column style="min-width: 400px">
              <template #body="{ data: row }">
                <div class="progress-cell">
                  <div class="progress-text">
                    {{ row.progress || '-' }}
                  </div>
                  <div class="progress-stepper">
                    <div class="bar-wrap">
                      <ProgressBar :value="stepPercent(row.progress_step)" :showValue="false" style="height:10px" />
                      <div class="step-dots">
                        <span v-for="n in 12" :key="'dot-' + n" class="step-dot"
                          :class="{ active: n <= (Number(row.progress_step || 1)) }"></span>
                      </div>
                    </div>
                    <div class="step-caption">
                      Step {{ Number(row.progress_step || 1) }}/12
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <Column style="min-width: 100px">
              <template #body="slotProps">
                <VIconButton class="mr-3" color="danger" outlined circle icon="feather:printer"
                  @click="cetakPBJ(slotProps.data)" />
              </template>
            </Column>

            <Column style="text-align:center; min-width: 90px">
              <template #body="slotProps">
                <VIconButton v-if="slotProps.data.dataListIht?.length" color="warning" outlined circle
                  icon="feather:printer" @click="lihatDetailIht(slotProps.data)" />
              </template>
            </Column>

            <Column style="min-width: 100px">
              <template #body="slotProps">
                <VIconButton v-tooltip.bottom.left="'Aktivitas'" icon="feather:activity"
                  @click="detailOrder(slotProps.data)" color="info" raised circle class="mr-2">
                </VIconButton>
              </template>
            </Column>

            <Column header="Edit" style="min-width: 120px; text-align:center">
              <template #body="slotProps">
                <VIconButton v-if="isEditableRow(slotProps.data)" v-tooltip.bottom="'Edit / Revisi'"
                  icon="feather:edit-3" color="primary" outlined circle @click="startEdit(slotProps.data)" />
                <VTag v-else color="danger" rounded>Locked</VTag>
              </template>
            </Column>

            <Column header="Ajukan" style="min-width: 130px; text-align:center">
              <template #body="slotProps">
                <VButton v-if="isEditableRow(slotProps.data)" color="warning" outlined icon="feather:send"
                  :loading="isLoading" @click="submitToAsman(slotProps.data)">
                  Ajukan
                </VButton>
              </template>
            </Column>

            <Column field="nosuratpbj" style="min-width: 160px" />

            <Column field="tglpengajuan" style="min-width: 140px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpengajuan">{{ H.formatDateIndo(slotProps.data.tglpengajuan) }}</span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="namalengkap" style="min-width: 200px" />
            <Column field="judulpermintaan" style="min-width: 100px" />
            <Column field="pemohonpbj" style="min-width: 100px" />
            <Column field="prioritaspbj" style="min-width: 120px" />
            <Column field="pengadaanpbj" style="min-width: 100px" />

            <Column style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ kebutuhanText(data) }}</span>
              </template>
            </Column>

            <Column style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ userText(data) }}</span>
              </template>
            </Column>

            <Column style="min-width: 220px">
              <template #body="{ data }">
                <span>{{ bidangText(data) }}</span>
              </template>
            </Column>

            <Column style="min-width: 240px">
              <template #body="{ data }">
                <span>{{ kepadaText(data) }}</span>
              </template>
            </Column>

            <Column field="notes" style="min-width: 240px">
              <template #body="slotProps">
                <span>{{ slotProps.data.notes || '-' }}</span>
              </template>
            </Column>

            <Column field="nopr" style="min-width: 240px" />

            <Column field="tglpembuatanpr" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpembuatanpr">
                  {{ H.formatDateToLocalString(slotProps.data.tglpembuatanpr) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglpenerimaanro" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanro">
                  {{ H.formatDateToLocalString(slotProps.data.tglpenerimaanro) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglpenerimaanhpe" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanhpe">
                  {{ H.formatDateToLocalString(slotProps.data.tglpenerimaanhpe) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglpenerimaanrks" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenerimaanrks">
                  {{ H.formatDateToLocalString(slotProps.data.tglpenerimaanrks) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglpenyerahandokumen" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpenyerahandokumen">
                  {{ H.formatDateToLocalString(slotProps.data.tglpenyerahandokumen) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglaanwijzing" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglaanwijzing">
                  {{ H.formatDateToLocalString(slotProps.data.tglaanwijzing) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglklartek" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglklartek">
                  {{ H.formatDateToLocalString(slotProps.data.tglklartek) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglpembukaanpenawaran" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpembukaanpenawaran">
                  {{ H.formatDateToLocalString(slotProps.data.tglpembukaanpenawaran) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglpp" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpp">
                  {{ H.formatDateToLocalString(slotProps.data.tglpp) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="nopo" style="min-width: 100px" />
            <Column field="nilaipoppn" style="min-width: 100px" />

            <Column field="tglpo" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglpo">
                  {{ H.formatDateToLocalString(slotProps.data.tglpo) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="pelaksana" style="min-width: 100px" />
            <Column field="hps" style="min-width: 100px" />
            <Column field="sppp" style="min-width: 200px" />

            <Column field="tglmulairealisasi" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglmulairealisasi">
                  {{ H.formatDateToLocalString(slotProps.data.tglmulairealisasi) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="tglselesairealisasi" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.tglselesairealisasi">
                  {{ H.formatDateToLocalString(slotProps.data.tglselesairealisasi) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="dendahari" style="min-width: 100px" />
            <Column field="noba" style="min-width: 100px" />
            <Column field="posisiba" style="min-width: 100px" />
            <Column field="statusba" style="min-width: 100px" />

            <Column field="verifikasiinvoice" style="min-width: 240px">
              <template #body="slotProps">
                <span v-if="slotProps.data.verifikasiinvoice">
                  {{ H.formatDateToLocalString(slotProps.data.verifikasiinvoice) }}
                </span>
                <span v-else>-</span>
              </template>
            </Column>

            <Column field="nobkk" style="min-width: 100px" />
            <Column field="statuspaid" style="min-width: 100px" />
            <Column field="ketverifpembayaran" style="min-width: 100px" />
            <Column field="nojurnal" style="min-width: 100px" />
            <Column field="periode" style="min-width: 100px" />
            <Column field="ketverifpembebanan" style="min-width: 100px" />
            <Column field="periodepermintaanlimit" style="min-width: 100px" />
            <Column field="ketverifpermitaanlimit" style="min-width: 100px" />

            <template #expansion="{ data }">
              <div class="p-4 bg-white border-round-md shadow-1">
                <h5 class="mb-2">Detail PBJ untuk No Surat {{ data.nosuratpbj }}</h5>
                <DataTable :value="data.detailList" class="p-datatable-sm" tableStyle="min-width:40rem" scrollable
                  responsiveLayout="stack" breakpoint="960px" showGridlines>
                  <Column field="no" header="No" style="width:60px; min-width:60px" />
                  <Column field="namaitem" header="Nama Item" style="min-width: 160px" />
                  <Column field="uraianitem" header="Uraian" style="min-width: 220px" />
                  <Column field="stockcode" header="Stock Code" style="min-width: 120px" />
                  <Column field="banyak" header="Banyak" style="min-width: 100px" />
                  <Column field="satuan" header="Satuan" style="min-width: 100px" />
                  <Column field="hargasatuan" header="Harga Satuan" style="min-width: 140px">
                    <template #body="sp">{{ formatRupiah(sp.data.hargasatuan) }}</template>
                  </Column>
                  <Column field="keterangan" header="Keterangan" style="min-width: 220px" />
                </DataTable>
              </div>
            </template>
          </DataTable>
        </div>
      </div>
    </VCard>
  </div>

  <VModal title="" :open="modalRiwayat" noclose size="big" actions="right" @close="modalRiwayat = false, clear()"
    cancelLabel="Tutup">
    <template #content>
      <div class="column is-12">
        <div class="column" v-for="(data) in 3" style="text-align:center" v-if="isLoadDataDeatilOrder">
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
              <div class="timeline-item is-unread" v-for="(it, idx) in timelineItems" :key="idx">
                <div class="date">
                  <span>{{ it.date ? H.formatDateIndo(it.date) : '-' }}</span>
                </div>
                <div :class="'dot is-' + listColor[idx + 1]"></div>
                <div class="content-wrap is-grey">
                  <div class="content-box">
                    <div class="box-text" style="width:70%">
                      <div class="meta-text">
                        <p>
                          <span>
                            {{ it.type }}
                            <span v-if="it.nama"> : {{ it.nama }}</span>
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
      </div>
    </template>
    <template #action></template>
  </VModal>

  <VModal title="" :open="detaiIhtModalOpen" size="medium" actions="right" @close="closeDetailIhtModal()">
    <template #content>
      <div class="mb-3">
        <VTag color="info" rounded>
          User upload sebanyak: {{ (detailIsiIht?.length || 0) }} file
        </VTag>
        <VTag v-if="detailIhtCanEdit" color="success" rounded class="ml-2">
          Bisa diganti / dihapus karena draft atau ditolak
        </VTag>
      </div>

      <DataTable :value="detailIsiIht" :rows="10" :rowsPerPageOptions="[5, 10, 15]" class="p-datatable-sm">
        <Column header="No" style="width:70px; text-align:center">
          <template #body="slotProps">
            {{ (slotProps.index ?? 0) + 1 }}
          </template>
        </Column>
        <Column field="namafileiht" header="Nama File" />
        <Column header="Aksi" style="text-align:center; width:220px">
          <template #body="slotProps">
            <VIconButton v-tooltip.bottom="'Cetak IH'" class="mr-2" color="danger" outlined circle icon="feather:printer"
              @click="cetakIHT(slotProps.data)" />
            <VIconButton v-if="detailIhtCanEdit" v-tooltip.bottom="'Ganti IH'" class="mr-2" color="primary" outlined
              circle icon="feather:upload"
              :loading="isIhtActionLoading && activeIhtAction === 'replace' && activeIhtNorec === slotProps.data.norec"
              :disabled="isIhtActionLoading" @click="chooseReplacementIht(slotProps.data)" />
            <VIconButton v-if="detailIhtCanEdit" v-tooltip.bottom="'Hapus IH'" color="danger" outlined circle
              icon="feather:trash-2"
              :loading="isIhtActionLoading && activeIhtAction === 'delete' && activeIhtNorec === slotProps.data.norec"
              :disabled="isIhtActionLoading" @click="deleteIht(slotProps.data)" />
          </template>
        </Column>
      </DataTable>
    </template>
  </VModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import AutoComplete from 'primevue/autocomplete'
import Fieldset from 'primevue/fieldset'
import ProgressBar from 'primevue/progressbar'
import FileUpload from 'primevue/fileupload'
import * as XLSX from 'xlsx'
import ColumnGroup from 'primevue/columngroup'
import Row from 'primevue/row'
import { useThemeColors } from '/@src/composable/useThemeColors'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import * as H from '/@src/utils/appHelper'

useHead({ title: 'Pengajuan PBJ - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const listColor: any = ref(Object.keys(useThemeColors()))
const userLogin = useUserSession().getUser()

const isLoading: any = ref(false)
const isPlaceLoad: any = ref(false)
const dataSource: any = ref([])
const isLoadDataDeatilOrder: any = ref(false)

interface TimelineItem {
  date: string | null
  type: string
  nama: string
}

const timelineItems = ref<TimelineItem[]>([])

const editMode = ref(false)
const editingNorec = ref<string | null>(null)
const editingNoSurat = ref<string | null>(null)
const draftEditingFlag = ref(true)
const editingRejectedStep = ref<any>(null)

const item: any = ref({
  search: '',
  judulpermintaan: '',
  jenispbj: {},
  project: '',
  noproyek: '',
  prk: '',
  prkMaster: {},
  wo: '',
  pengadaan: {},
  lokasipbj: {},
  costcode: '',
  ppn: '',
  notes: '',
  kebutuhanMode: 'dropdown',
  kebutuhan: {},
  kebutuhanmanual: '',
  pemohonMode: 'dropdown',
  pemohon: {},
  prioritas: {},
  pemohonmanual: '',
  userMode: 'dropdown',
  userDropdown: {},
  userManual: '',
  bidangMode: 'dropdown',
  bidangDropdown: {},
  bidangManual: '',
  kepadaMode: 'dropdown',
  kepadaDropdown: {},
  kepadaManual: '',
})

const d_jenispbj = ref([])
const d_pemohonpbj = ref([])
const d_prioritaspbj = ref([])
const d_dasaranggaran = ref([])
const d_prk = ref([])
const d_pengadaanpbj = ref([])
const d_lokasipbj = ref([])
const d_managerbidang = ref([])
const d_kepadaPBJ = ref([])
const d_unit = ref([])

const isAoSelected = computed(() => {
  if (item.value.kebutuhanMode !== 'dropdown') return false
  return String(item.value.kebutuhan?.label || '').trim().toUpperCase() === 'AO'
})

watch(
  isAoSelected,
  (isAo, wasAo) => {
    if (isAo === wasAo) return
    item.value.prk = ''
    item.value.prkMaster = {}
  },
  { flush: 'sync' }
)

const makeUid = () => {
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`
}

const makeEmptyDetail = (no = 1) => ({
  _uid: makeUid(),
  no,
  namaitem: '',
  uraianitem: '',
  stockcode: '',
  banyak: '',
  satuan: '',
  hargasatuan: '',
  keterangan: '',
  _hargaStr: '',
})

const input: any = ref({
  detailOrderPBJ: [makeEmptyDetail(1)],
})

const expandedRows = ref(null)

type StepLegend = { step: number; label: string }

const stepsLegend: StepLegend[] = [
  { step: 1, label: 'Diajukan' },
  { step: 2, label: 'Diverifikasi Asman' },
  { step: 3, label: 'Diverifikasi Manager' },
  { step: 4, label: 'Diverifikasi Rendal' },
  { step: 5, label: 'Diverifikasi Inventory' },
  { step: 6, label: 'Diverifikasi Pengadaan' },
  { step: 7, label: 'Diverifikasi BA 1' },
  { step: 8, label: 'Diverifikasi Asman untuk BA 1' },
  { step: 9, label: 'Diverifikasi BA 2' },
  { step: 10, label: 'Diverifikasi Pembayaran' },
  { step: 11, label: 'Diverifikasi Pembebanan' },
  { step: 12, label: 'Diverifikasi Permintaan Limit' },
]

const detailIsiIht: any = ref([])
const existingIhtFiles = ref<any[]>([])
const detaiIhtModalOpen = ref(false)
const detailIhtCanEdit = ref(false)
const replaceIhtInput = ref<HTMLInputElement | null>(null)
const replacementIhtTarget = ref<any>(null)
const isIhtActionLoading = ref(false)
const activeIhtNorec = ref<string | null>(null)
const activeIhtAction = ref<'replace' | 'delete' | null>(null)
const modalRiwayat: any = ref(false)

const isDraftRow = (row: any) => {
  const tglNull = row?.tglpengajuan === null || row?.tglpengajuan === undefined || row?.tglpengajuan === ''
  const stNull = row?.statusorder === null || row?.statusorder === undefined
  const asNull = row?.statusorderasman === null || row?.statusorderasman === undefined

  return tglNull && stNull && asNull
}

const rejectedStages: Record<number, { label: string; step: number }> = {
  2: { label: 'Asman', step: 2 },
  4: { label: 'Manager', step: 3 },
  6: { label: 'Rendal', step: 4 },
  8: { label: 'Inventory 1', step: 5 },
  10: { label: 'Pengadaan', step: 6 },
  12: { label: 'BA 1', step: 7 },
  14: { label: 'Asman untuk BA 1', step: 8 },
  16: { label: 'BA 2', step: 9 },
  18: { label: 'Pembayaran', step: 10 },
  20: { label: 'Pembebanan', step: 11 },
  22: { label: 'Permintaan Limit', step: 12 },
}

const getRejectedStep = (row: any) => {
  const submitted = !(row?.tglpengajuan === null || row?.tglpengajuan === undefined || row?.tglpengajuan === '')
  if (!submitted) return null

  return rejectedStages[Number(row?.statusorder)] || null
}

const isRejectedRow = (row: any) => getRejectedStep(row) !== null

const isEditableRow = (row: any) => {
  return isDraftRow(row) || isRejectedRow(row)
}

const pickLabel = (row: any, modeKey: string, manualKey: string, labelKey: string) => {
  const mode = (row?.[modeKey] || '').toString().toLowerCase()

  if (mode === 'manual') {
    const v = row?.[manualKey]
    return v && String(v).trim() ? String(v) : '-'
  }

  const lb = row?.[labelKey]
  return lb && String(lb).trim() ? String(lb) : '-'
}

const kebutuhanText = (row: any) => pickLabel(row, 'kebutuhan_mode', 'kebutuhanmanual', 'kebutuhan_label')
const userText = (row: any) => pickLabel(row, 'user_mode', 'usermanual', 'user_label')
const bidangText = (row: any) => pickLabel(row, 'bidang_mode', 'bidangmanual', 'bidang_label')
const kepadaText = (row: any) => pickLabel(row, 'kepada_mode', 'kepadamanual', 'kepada_label')

const canSubmitDraft = computed(() => editMode.value && !!editingNorec.value)
const canDeleteDraft = computed(() => editMode.value && !!editingNorec.value && draftEditingFlag.value)
const canManageEditingIht = computed(
  () => editMode.value && !!editingNorec.value && (draftEditingFlag.value || !!editingRejectedStep.value)
)
const editingIhtAccessText = computed(() =>
  draftEditingFlag.value ? 'Bisa diedit (Draft)' : `Bisa diedit (Ditolak ${editingRejectedStep.value?.label || '-'})`
)
const submitButtonText = computed(() =>
  editingRejectedStep.value ? `Ajukan Ulang ke ${editingRejectedStep.value.label}` : 'Ajukan ke Asman'
)

const editingStatusText = computed(() => {
  if (!editMode.value) return ''
  return draftEditingFlag.value
    ? 'Draft'
    : `Revisi Penolakan ${editingRejectedStep.value?.label || '-'}`
})

const editingStatusColor = computed(() => {
  if (!editMode.value) return 'info'
  return draftEditingFlag.value ? 'info' : 'danger'
})

const detailOrder = async (row: any) => {
  modalRiwayat.value = true
  isLoadDataDeatilOrder.value = true
  timelineItems.value = []

  try {
    const res = await useApi().get(`/pbj/get-pengajuan-pbj-detail?norec=${row.norec}`)
    timelineItems.value = res.timeline || []
  } catch (e) {
    console.log(e)
    timelineItems.value = []
  } finally {
    isLoadDataDeatilOrder.value = false
  }
}

const uploaderKeyIht = ref(0)
const uploaderIht = ref()
const filesIht = ref<File[]>([])

const uniqFiles = (arr: File[]) => {
  const m = new Map<string, File>()

  for (const f of arr || []) {
    const key = `${f.name}|${f.size}|${f.lastModified}`
    if (!m.has(key)) m.set(key, f)
  }

  return Array.from(m.values())
}

const onSelectFilesIht = (e: any) => {
  const selected: File[] = e?.files || []
  filesIht.value = uniqFiles(selected)
}

const cetakIHT = (it: any) => H.printBlade(`pbj/cetak-pbj-iht?norec=${it.norec}`)

const lihatDetailIht = (row: any) => {
  detailIsiIht.value = normalizeIhtFiles(row?.dataListIht)
  detailIhtCanEdit.value = isEditableRow(row)
  detaiIhtModalOpen.value = true
}

const normalizeIhtFiles = (files: any): any[] => {
  if (Array.isArray(files)) return [...files]
  if (files && typeof files === 'object') return Object.values(files)
  return []
}

const canManageIhtAction = () => detailIhtCanEdit.value || canManageEditingIht.value

const replaceIhtInState = (oldNorec: string, newIht: any) => {
  detailIsiIht.value = detailIsiIht.value.map((iht: any) =>
    iht.norec === oldNorec ? newIht : iht
  )
  existingIhtFiles.value = existingIhtFiles.value.map((iht: any) =>
    iht.norec === oldNorec ? newIht : iht
  )
}

const removeIhtFromState = (norec: string) => {
  detailIsiIht.value = detailIsiIht.value.filter((iht: any) => iht.norec !== norec)
  existingIhtFiles.value = existingIhtFiles.value.filter((iht: any) => iht.norec !== norec)
}

const closeDetailIhtModal = () => {
  detaiIhtModalOpen.value = false
  detailIhtCanEdit.value = false
  replacementIhtTarget.value = null
  activeIhtNorec.value = null
  activeIhtAction.value = null
  if (replaceIhtInput.value) replaceIhtInput.value.value = ''
}

const chooseReplacementIht = (iht: any) => {
  if (!canManageIhtAction() || isIhtActionLoading.value) return

  replacementIhtTarget.value = iht
  if (replaceIhtInput.value) {
    replaceIhtInput.value.value = ''
    replaceIhtInput.value.click()
  }
}

const replaceIhtFile = async (event: Event) => {
  const inputFile = event.target as HTMLInputElement
  const file = inputFile.files?.[0]
  const target = replacementIhtTarget.value

  if (!file || !target?.norec) return

  if (!file.name.toLowerCase().endsWith('.pdf')) {
    H.alert('warning', 'File pengganti IH harus berformat PDF')
    inputFile.value = ''
    return
  }

  if (file.size > 10000000) {
    H.alert('warning', 'Ukuran file pengganti IH maksimal 10 MB')
    inputFile.value = ''
    return
  }

  const formData = new FormData()
  formData.append('norec', target.norec)
  formData.append('fileIht', file)

  isIhtActionLoading.value = true
  activeIhtNorec.value = target.norec
  activeIhtAction.value = 'replace'
  try {
    const newIht: any = await useApi().post('/pbj/replace-draft-iht-pbj', formData)
    replaceIhtInState(target.norec, newIht)
    H.alert('success', 'Lampiran IH berhasil diganti')
    await fetchDataRiwayat()
  } catch (e: any) {
    H.alert('error', typeof e === 'string' ? e : 'Gagal mengganti lampiran IH')
  } finally {
    isIhtActionLoading.value = false
    activeIhtNorec.value = null
    activeIhtAction.value = null
    replacementIhtTarget.value = null
    inputFile.value = ''
  }
}

const deleteIht = async (iht: any) => {
  if (!canManageIhtAction() || !iht?.norec || isIhtActionLoading.value) return

  const ok = window.confirm(`Hapus lampiran IH "${iht.namafileiht || ''}"?`)
  if (!ok) return

  isIhtActionLoading.value = true
  activeIhtNorec.value = iht.norec
  activeIhtAction.value = 'delete'
  try {
    await useApi().post('/pbj/delete-draft-iht-pbj', { norec: iht.norec })
    removeIhtFromState(iht.norec)
    H.alert('success', 'Lampiran IH berhasil dihapus')
    await fetchDataRiwayat()
  } catch (e: any) {
    H.alert('error', typeof e === 'string' ? e : 'Gagal menghapus lampiran IH')
  } finally {
    isIhtActionLoading.value = false
    activeIhtNorec.value = null
    activeIhtAction.value = null
  }
}

const fetchJenisPBJ = async (filter: any) => {
  const res = await useApi().get(
    `general/dropdown/jenispbj_m?select=id,jenispbj&param_search=jenispbj&query=${filter.query}&limit=10`
  )
  d_jenispbj.value = res
}

const fetchPemohonPBJ = async (filter: any) => {
  const res = await useApi().get(
    `general/dropdown/pemohonpbj_m?select=id,pemohonpbj&param_search=pemohonpbj&query=${filter.query}&limit=10`
  )
  d_pemohonpbj.value = res
}

const fetchPrioritasPBJ = async (filter: any) => {
  const res = await useApi().get(
    `general/dropdown/prioritaspbj_m?select=id,prioritaspbj&param_search=prioritaspbj&query=${filter.query}&limit=10`
  )
  d_prioritaspbj.value = res
}

const fetchDasarAnggaran = async (filter: any) => {
  const res = await useApi().get(
    `general/dropdown/dasaranggaranpbj_m?select=id,dasar&param_search=dasar&query=${filter.query}&limit=10`
  )
  d_dasaranggaran.value = res
}

const fetchPrk = async (filter: any) => {
  const query = encodeURIComponent(filter?.query || '')
  const res = await useApi().get(
    `general/dropdown/prk_m?select=id,prk&param_search=prk&query=${query}&orderby=prk&limit=50`
  )
  d_prk.value = res
  return res
}

const fetchManagerbidang = async (filter: any) => {
  const res = await useApi().get(
    `general/dropdown/managerbidangpbj_m?select=id,managerbidang&param_search=managerbidang&query=${filter.query}&limit=10`
  )
  d_managerbidang.value = res
}

const fetchKepadaPBJ = async (filter: any) => {
  const res = await useApi().get(
    `general/dropdown/kepadapbj_m?select=id,kepada&param_search=kepada&query=${filter.query}&limit=10`
  )
  d_kepadaPBJ.value = res
}

const fetchUnit = async (filter: any) => {
  const response = await useApi().get(`pbj/fetch-unit-surkes?param_search=namaperusahaan&query=${filter.query}`)

  d_unit.value = response.data.map((e: any) => ({
    label: `${e.namaperusahaan}`,
    value: e.id,
  }))
}

const fetchPengadaanPBJ = async (filter: any) => {
  const res = await useApi().get(
    `general/dropdown/pengadaanpbj_m?select=id,pengadaanpbj&param_search=pengadaanpbj&query=${filter.query}&limit=10`
  )
  d_pengadaanpbj.value = res
}

const fetchLokasiPBJ = async (filter: any) => {
  const res = await useApi().get(
    `general/dropdown/lokasikalibrasi_m?select=id,lokasi&param_search=lokasi&query=${filter.query}&limit=10`
  )
  d_lokasipbj.value = res
}

const renumberDetailPBJ = () => {
  input.value.detailOrderPBJ = input.value.detailOrderPBJ.map((row: any, idx: number) => ({
    ...row,
    no: idx + 1,
  }))
}

const addNewDetailPBJ = (index: number) => {
  const insertAt = Number(index) + 1

  input.value.detailOrderPBJ.splice(insertAt, 0, makeEmptyDetail(insertAt + 1))

  renumberDetailPBJ()
}

const removeDetailPBJ = (index: number) => {
  if (input.value.detailOrderPBJ.length <= 1) {
    input.value.detailOrderPBJ.splice(0, input.value.detailOrderPBJ.length, makeEmptyDetail(1))
    return
  }

  input.value.detailOrderPBJ.splice(index, 1)

  renumberDetailPBJ()
}

const stepPercent = (step: any): number => {
  const s = Math.max(1, Math.min(12, Number(step || 1)))
  return Math.round((s / 12) * 100)
}

const rupiahFmt = new Intl.NumberFormat('id-ID')

const formatRupiah = (val: any) => {
  if (val === null || val === undefined || val === '') return ''

  const n = Number(val)

  return isNaN(n) ? '' : rupiahFmt.format(n)
}

const onlyDigits = (s: string) => (s || '').replace(/[^\d]/g, '')

const formatRupiahFast = (digits: string) => (!digits ? '' : digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.'))

const onHargaInput = (row: any) => {
  row._hargaStr = formatRupiahFast(onlyDigits(row._hargaStr))
}

const commitHarga = (row: any) => {
  const digits = onlyDigits(row._hargaStr)
  row.hargasatuan = digits ? parseInt(digits, 10) : null
  row._hargaStr = formatRupiahFast(digits)
}

const validateModeField = (mode: string, dd: any, manual: string, label: string) => {
  if (mode === 'dropdown') {
    if (!dd?.value) {
      H.alert('warning', `${label} (dropdown) harus dipilih`)
      return false
    }
  } else {
    if (!manual || !String(manual).trim()) {
      H.alert('warning', `${label} (manual) harus di isi`)
      return false
    }
  }

  return true
}

const normalizeOption = (value: any, label: any) => {
  if (value === null || value === undefined || value === '') return {}
  return { label: String(label ?? value), value: value }
}

const applyModeField = (
  modeFromApi: any,
  fkFromApi: any,
  labelFromApi: any,
  manualFromApi: any,
  stateModeKey: string,
  stateDdKey: string,
  stateManualKey: string,
  fallbackText: any
) => {
  const mode = modeFromApi === 'manual' || modeFromApi === 'dropdown' ? modeFromApi : null

  if (mode) {
    item.value[stateModeKey] = mode

    if (mode === 'dropdown') {
      item.value[stateDdKey] = normalizeOption(fkFromApi, labelFromApi)
      item.value[stateManualKey] = ''
    } else {
      item.value[stateDdKey] = {}
      item.value[stateManualKey] = manualFromApi || ''
    }

    return
  }

  item.value[stateModeKey] = 'manual'
  item.value[stateDdKey] = {}
  item.value[stateManualKey] = fallbackText || ''
}

const startEdit = async (row: any) => {
  if (!isEditableRow(row)) {
    H.alert('warning', 'PBJ ini tidak bisa diedit.')
    return
  }

  editMode.value = true
  editingNorec.value = row?.norec || null
  editingNoSurat.value = row?.nosuratpbj || null
  draftEditingFlag.value = isDraftRow(row)
  editingRejectedStep.value = getRejectedStep(row)
  existingIhtFiles.value = normalizeIhtFiles(row?.dataListIht)

  isLoading.value = true

  try {
    let hdr: any = row || {}
    const resDetail = await useApi().get(`/pbj/get-pengajuan-pbj-detail?norec=${row.norec}`)
    hdr = resDetail?.header ? resDetail.header : hdr

    if (resDetail?.detailList) hdr.detailList = resDetail.detailList

    item.value.judulpermintaan = hdr.judulpermintaan || ''
    item.value.project = hdr.project || ''
    item.value.noproyek = hdr.noproyek || ''
    item.value.prk = ''
    item.value.prkMaster = {}
    item.value.wo = hdr.wo || ''
    item.value.costcode = hdr.costcode || ''
    item.value.ppn = hdr.ppn || ''
    item.value.notes = hdr.notes || ''

    item.value.jenispbj = normalizeOption(hdr.jenispbjfk, hdr.jenispbj)
    item.value.pemohonMode = 'dropdown'
    item.value.pemohon = normalizeOption(hdr.pemohonpbjfk, hdr.pemohonpbj)
    item.value.pemohonmanual = ''
    item.value.pengadaan = normalizeOption(hdr.pengadaanpbjfk, hdr.pengadaanpbj)
    item.value.prioritas = normalizeOption(hdr.prioritaspbjfk, hdr.prioritaspbj)
    item.value.lokasipbj = normalizeOption(hdr.lokasipbjfk, hdr.lokasipbj)

    applyModeField(
      hdr.kebutuhan_mode,
      hdr.kebutuhan_fk,
      hdr.kebutuhan_label,
      hdr.kebutuhanmanual,
      'kebutuhanMode',
      'kebutuhan',
      'kebutuhanmanual',
      hdr.kebutuhan
    )

    applyModeField(
      hdr.bidang_mode,
      hdr.bidang_fk,
      hdr.bidang_label,
      hdr.bidangmanual,
      'bidangMode',
      'bidangDropdown',
      'bidangManual',
      hdr.bidang
    )

    applyModeField(
      hdr.kepada_mode,
      hdr.kepada_fk,
      hdr.kepada_label,
      hdr.kepadamanual,
      'kepadaMode',
      'kepadaDropdown',
      'kepadaManual',
      hdr.kepada
    )

    applyModeField(
      hdr.user_mode,
      hdr.user_fk,
      hdr.user_label,
      hdr.usermanual,
      'userMode',
      'userDropdown',
      'userManual',
      hdr.user
    )

    if (isAoSelected.value) {
      if (hdr.prkfk) {
        item.value.prkMaster = normalizeOption(hdr.prkfk, hdr.prk_master || hdr.prk)
      } else if (hdr.prk) {
        const options: any[] = await fetchPrk({ query: hdr.prk })
        const legacyMatch = options.find(
          (option: any) => String(option.label || '').trim().toUpperCase() === String(hdr.prk).trim().toUpperCase()
        )
        item.value.prkMaster = legacyMatch || {}
      }
    } else {
      item.value.prk = hdr.prk || ''
    }

    const details = (hdr?.detailList || []) as any[]
    input.value.detailOrderPBJ.splice(0, input.value.detailOrderPBJ.length)

    if (details.length) {
      details.forEach((d: any, idx: number) => {
        const harga = d.hargasatuan ?? null

        input.value.detailOrderPBJ.push({
          _uid: makeUid(),
          no: idx + 1,
          namaitem: d.namaitem ?? '',
          uraianitem: d.uraianitem ?? '',
          stockcode: d.stockcode ?? '',
          banyak: d.banyak ?? '',
          satuan: d.satuan ?? '',
          hargasatuan: harga,
          keterangan: d.keterangan ?? '',
          _hargaStr: harga ? formatRupiahFast(String(harga)) : '',
          norec: d.norec ?? null,
        })
      })

      renumberDetailPBJ()
    } else {
      input.value.detailOrderPBJ.push(makeEmptyDetail(1))
    }

    filesIht.value = []
    uploaderKeyIht.value += 1

    if (draftEditingFlag.value) {
      H.alert('success', 'Draft berhasil dimasukkan ke form (Mode Edit)')
    } else {
      H.alert(
        'success',
        `Data PBJ yang ditolak ${editingRejectedStep.value?.label || ''} berhasil dimasukkan ke form untuk direvisi`
      )
    }
  } catch (e) {
    console.log(e)
    H.alert('error', 'Gagal load data untuk edit')
  } finally {
    isLoading.value = false
  }
}

const cancelEdit = () => {
  editMode.value = false
  editingNorec.value = null
  editingNoSurat.value = null
  draftEditingFlag.value = true
  editingRejectedStep.value = null
  clear()
  H.alert('info', 'Mode edit dibatalkan')
}

const submitToAsman = async (row?: any) => {
  const norec = row?.norec || editingNorec.value

  if (!norec) {
    H.alert('warning', 'Norec PBJ belum ada. Simpan draft dulu.')
    return
  }

  const rejectedStep = row ? getRejectedStep(row) : editingRejectedStep.value
  const isResubmit = !!rejectedStep
  const targetLabel = rejectedStep?.label || 'Asman'

  // Saat mengajukan dari form edit, simpan seluruh revisi (termasuk IH baru)
  // terlebih dahulu. Pengajuan dibatalkan bila validasi/simpan revisi gagal.
  if (!row && editMode.value) {
    const revisionSaved = await savePengajuanPbj({ silentSuccess: true })
    if (!revisionSaved) return
  }

  isLoading.value = true

  try {
    await useApi().post('/pbj/submit-pengajuan-pbj-asman', { norec })

    H.alert(
      'success',
      isResubmit ? `PBJ berhasil diajukan ulang ke ${targetLabel}` : 'PBJ berhasil diajukan ke Asman'
    )

    editMode.value = false
    editingNorec.value = null
    editingNoSurat.value = null
    draftEditingFlag.value = true
    editingRejectedStep.value = null

    clear()
    fetchDataRiwayat()
  } catch (e: any) {
    console.log(e)
    H.alert('error', e?.response?.data?.message || 'Gagal ajukan PBJ')
  } finally {
    isLoading.value = false
  }
}

const deleteDraft = async (row?: any) => {
  const norec = row?.norec || editingNorec.value

  if (!norec) {
    H.alert('warning', 'norec tidak ditemukan')
    return
  }

  const ok = window.confirm('Hapus draft PBJ ini?\nAksi ini tidak bisa dibatalkan.')

  if (!ok) return

  isLoading.value = true

  try {
    await useApi().post('/pbj/delete-draft-pengajuan-pbj', { norec })
    H.alert('success', 'Draft berhasil dihapus')

    if (editingNorec.value === norec) {
      cancelEdit()
    }

    fetchDataRiwayat()
  } catch (e: any) {
    console.log(e)
    H.alert('error', e?.response?.data?.message || 'Gagal menghapus draft')
  } finally {
    isLoading.value = false
  }
}

const savePengajuanPbj = async (
  options: { silentSuccess?: boolean } = {}
): Promise<boolean> => {
  if (!item.value.judulpermintaan) {
    H.alert('warning', 'Judul Permintaan harus di isi')
    return false
  }

  if (!item.value.jenispbj?.value) {
    H.alert('warning', 'Material/Jasa harus di isi')
    return false
  }

  if (!validateModeField(item.value.kebutuhanMode, item.value.kebutuhan, item.value.kebutuhanmanual, 'Dasar Anggaran')) return false
  if (!validateModeField(item.value.pemohonMode, item.value.pemohon, item.value.pemohonmanual, 'Bidang Pemohon')) return false

  if (!item.value.prioritas?.value) {
    H.alert('warning', 'Prioritas harus di isi')
    return false
  }

  if (!validateModeField(item.value.userMode, item.value.userDropdown, item.value.userManual, 'User Unit')) return false
  if (!validateModeField(item.value.bidangMode, item.value.bidangDropdown, item.value.bidangManual, 'Manager Bidang')) return false
  if (!validateModeField(item.value.kepadaMode, item.value.kepadaDropdown, item.value.kepadaManual, 'Kepada')) return false

  if (isAoSelected.value) {
    if (!item.value.prkMaster?.value) {
      H.alert('warning', 'PRK AO harus dipilih dari dropdown')
      return false
    }
  } else if (!item.value.prk) {
    H.alert('warning', 'PRK harus di isi')
    return false
  }

  if (!item.value.pengadaan?.value) {
    H.alert('warning', 'Pengadaan harus di isi')
    return false
  }

  if (!item.value.lokasipbj?.value) {
    H.alert('warning', 'Lokasi PBJ harus di isi')
    return false
  }

  renumberDetailPBJ()

  const mappedOrderPbj = input.value.detailOrderPBJ.map((d: any, index: number) => ({
    norec: d.norec ?? null,
    urutitem: index + 1,
    namaitem: d.namaitem ?? null,
    uraianitem: d.uraianitem ?? null,
    stockcode: d.stockcode ?? null,
    banyak: d.banyak ?? null,
    satuan: d.satuan ?? null,
    hargasatuan: d.hargasatuan ?? null,
    keterangan: d.keterangan ?? null,
  }))

  const formData = new FormData()

  const fdAppend = (fd: FormData, key: string, val: any) => {
    if (val === null || val === undefined) return

    const s = String(val)

    if (s.trim() === '' || s === 'null' || s === 'undefined') return

    fd.append(key, s)
  }

  if (editMode.value && editingNorec.value) {
    fdAppend(formData, 'norec', editingNorec.value)
  }

  fdAppend(formData, 'judulpermintaan', item.value.judulpermintaan)
  fdAppend(formData, 'jenispbj', item.value.jenispbj?.value)
  fdAppend(formData, 'project', item.value.project)
  fdAppend(formData, 'noproyek', item.value.noproyek)
  if (isAoSelected.value) {
    fdAppend(formData, 'prkfk', item.value.prkMaster?.value)
  } else {
    fdAppend(formData, 'prk', item.value.prk)
  }
  fdAppend(formData, 'wo', item.value.wo)
  fdAppend(formData, 'pengadaan', item.value.pengadaan?.value)
  fdAppend(formData, 'prioritas', item.value.prioritas?.value)
  fdAppend(formData, 'lokasipbj', item.value.lokasipbj?.value)
  fdAppend(formData, 'costcode', item.value.costcode)
  fdAppend(formData, 'ppn', item.value.ppn)
  fdAppend(formData, 'notes', item.value.notes)
  fdAppend(formData, 'mappedOrderPbj', JSON.stringify(mappedOrderPbj))

  fdAppend(formData, 'kebutuhan_mode', item.value.kebutuhanMode)
  if (item.value.kebutuhanMode === 'dropdown') {
    fdAppend(formData, 'kebutuhan', item.value.kebutuhan?.value)
  } else {
    fdAppend(formData, 'kebutuhanmanual', item.value.kebutuhanmanual)
  }

  fdAppend(formData, 'pemohon_mode', item.value.pemohonMode)
  if (item.value.pemohonMode === 'dropdown') {
    fdAppend(formData, 'pemohon', item.value.pemohon?.value)
  } else {
    fdAppend(formData, 'pemohonmanual', item.value.pemohonmanual)
  }

  fdAppend(formData, 'user_mode', item.value.userMode)
  if (item.value.userMode === 'dropdown') {
    fdAppend(formData, 'user_dd', item.value.userDropdown?.value)
  } else {
    fdAppend(formData, 'user_manual', item.value.userManual)
  }

  fdAppend(formData, 'bidang_mode', item.value.bidangMode)
  if (item.value.bidangMode === 'dropdown') {
    fdAppend(formData, 'bidang_dd', item.value.bidangDropdown?.value)
  } else {
    fdAppend(formData, 'bidang_manual', item.value.bidangManual)
  }

  fdAppend(formData, 'kepada_mode', item.value.kepadaMode)
  if (item.value.kepadaMode === 'dropdown') {
    fdAppend(formData, 'kepada_dd', item.value.kepadaDropdown?.value)
  } else {
    fdAppend(formData, 'kepada_manual', item.value.kepadaManual)
  }

  uniqFiles(filesIht.value || []).forEach((f: File) => {
    formData.append('fileIht[]', f)
  })

  isLoading.value = true

  try {
    const res: any = await useApi().post(`/pbj/save-pengajuan-pbj`, formData)

    const norecRes = res?.norec || res?.result?.norec
    const noSuratRes = res?.nosuratpbj || res?.result?.nosuratpbj || null

    if (!editMode.value) {
      editMode.value = true
      editingNorec.value = norecRes || null
      editingNoSurat.value = noSuratRes
      draftEditingFlag.value = true
      if (!options.silentSuccess) {
        H.alert('success', `Draft tersimpan. No Surat: ${editingNoSurat.value || '-'}`)
      }
    } else {
      if (!options.silentSuccess) {
        if (draftEditingFlag.value) {
          H.alert('success', 'Draft berhasil diupdate')
        } else {
          H.alert('success', 'Revisi PBJ berhasil disimpan')
        }
      }
    }

    filesIht.value = []
    uploaderIht.value?.clear?.()
    uploaderKeyIht.value += 1

    fetchDataRiwayat()
    return true
  } catch (e: any) {
    console.clear()
    console.log(e)
    H.alert('error', e?.response?.data?.message || 'Gagal simpan PBJ')
    return false
  } finally {
    isLoading.value = false
  }
}

const clear = () => {
  editingRejectedStep.value = null
  item.value.judulpermintaan = ''
  item.value.jenispbj = {}
  item.value.project = ''
  item.value.noproyek = ''
  item.value.prk = ''
  item.value.prkMaster = {}
  item.value.wo = ''
  item.value.pengadaan = {}
  item.value.lokasipbj = {}
  item.value.costcode = ''
  item.value.ppn = ''
  item.value.notes = ''

  item.value.kebutuhanMode = 'dropdown'
  item.value.kebutuhan = {}
  item.value.kebutuhanmanual = ''

  item.value.pemohonMode = 'dropdown'
  item.value.pemohon = {}
  item.value.pemohonmanual = ''

  item.value.prioritas = {}

  item.value.userMode = 'manual'
  item.value.userDropdown = {}
  item.value.userManual = ''

  item.value.bidangMode = 'manual'
  item.value.bidangDropdown = {}
  item.value.bidangManual = ''

  item.value.kepadaMode = 'manual'
  item.value.kepadaDropdown = {}
  item.value.kepadaManual = ''

  input.value.detailOrderPBJ.splice(0, input.value.detailOrderPBJ.length)
  input.value.detailOrderPBJ.push(makeEmptyDetail(1))

  filesIht.value = []
  existingIhtFiles.value = []
  uploaderKeyIht.value += 1
}

const statusColor = (status: string) => {
  if (!status) return 'warning'

  const s = (status || '').toLowerCase()

  if (s.includes('draft')) return 'info'
  if (s.includes('setuju')) return 'success'
  if (s.includes('tolak')) return 'danger'

  return 'warning'
}

const fetchDataRiwayat = async () => {
  isPlaceLoad.value = true

  const search = item.value.search ? `&search=${encodeURIComponent(item.value.search)}` : ''
  const id = `id=${userLogin.pegawai?.id || ''}`

  try {
    const res = await useApi().get(`pbj/get-pengajuan-pbj?${id}${search}`)

    res.forEach((e: any, i: number) => {
      e.no = i + 1

      if (isDraftRow(e)) {
        e.statusText = 'Draft'
        e.color = 'info'
        e.progress = 'Draft'
        e.progress_step = 1
      } else if (isRejectedRow(e)) {
        const rejectedStep = getRejectedStep(e)
        e.statusText = `Ditolak ${rejectedStep?.label || '-'}`
        e.color = 'danger'
        e.progress = `Ditolak ${rejectedStep?.label || '-'} - menunggu revisi pengaju`
        e.progress_step = rejectedStep?.step || e.progress_step
      } else {
        e.color = e.color || statusColor(e.statusText)
      }

      e.detailList = (e.detailList || []).map((d: any, idx: number) => ({
        ...d,
        no: idx + 1,
      }))
    })

    dataSource.value = res
    if (editMode.value && editingNorec.value) {
      const editingRow = res.find((row: any) => row.norec === editingNorec.value)
      if (editingRow) existingIhtFiles.value = normalizeIhtFiles(editingRow.dataListIht)
    }
  } catch (e) {
    dataSource.value = []
  } finally {
    isPlaceLoad.value = false
  }
}

const remakeData: any = ref([])

const exportExcel = () => {
  remakeData.value = (dataSource.value || []).map((e: any) => ({
    No: e.no,
    'No Surat PBJ': e.nosuratpbj || '-',
    'Judul Permintaan': e.judulpermintaan,
    'Pemohon PBJ': e.pemohonpbj,
    'Pengadaan PBJ': e.pengadaanpbj,
    'Tanggal Pengajuan': e.tglpengajuan ? H.formatDateToLocalString(e.tglpengajuan) : '-',
    Status: e.statusText,
    Progress: e.progress,
    Catatan: e.notes || '-',
  }))

  const ws = XLSX.utils.json_to_sheet(remakeData.value)
  const wb = { Sheets: { data: ws }, SheetNames: ['data'] }
  const buff: any = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })

  const blob = new Blob([buff], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8',
  })

  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')

  a.href = url
  a.download = 'pengajuan-pbj.xlsx'

  document.body.appendChild(a)
  a.click()
  a.remove()

  window.URL.revokeObjectURL(url)
}

const cetakPBJ = (e: any) => H.printBlade(`pbj/cetak-pbj?pdf=true&norec=${e.norec}`)

fetchDataRiwayat()
</script>

<style lang="scss">
.input-calendar {
  min-width: 140px;
}

.icon-separator {
  font-size: .45rem;
  margin: 0 .5rem;
  vertical-align: middle;
}

@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/customer.scss';

.ellipsis-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

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
}

.toggle-row {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-top: 6px;
}

.help-hint {
  color: #6b7280;
}

.saved-iht-panel {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fafafa;
}

.saved-iht-title {
  font-weight: 700;
  color: var(--dark-text);
}

.saved-iht-empty {
  padding: 1rem;
  border: 1px dashed #d1d5db;
  border-radius: 8px;
  color: #6b7280;
  text-align: center;
}

.progress-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.progress-text {
  font-weight: 600;
  color: var(--dark-text);
  margin-bottom: 2px;
}

.progress-stepper .bar-wrap {
  position: relative;
}

.progress-stepper .step-dots {
  position: absolute;
  left: 0;
  right: 0;
  top: -6px;
  display: flex;
  justify-content: space-between;
  padding: 0 2px;
  pointer-events: none;
}

.progress-stepper .step-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid #d5d7da;
  background: #f4f5f7;
  box-sizing: border-box;
}

.progress-stepper .step-dot.active {
  border-color: #22c55e;
  background: #22c55e;
}

.progress-stepper .step-caption {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 6px;
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

  .progress-stepper .step-dot {
    border-color: #394150;
    background: #111827;
  }

  .progress-stepper .step-dot.active {
    border-color: #22c55e;
    background: #22c55e;
  }
}

.legend-steps {
  padding: 10px 12px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.03), rgba(16, 185, 129, 0.03));
}

.legend-title {
  font-weight: 700;
  margin-bottom: 10px;
  font-size: 0.95rem;
  color: var(--dark-text);
}

.legend-scroller {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: max-content;
  gap: 10px 14px;
  overflow-x: auto;
  padding-bottom: 6px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.6);
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 999px;
  padding: 6px 10px;
  white-space: nowrap;
}

.legend-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  background: #f8fafc;
  font-weight: 700;
  font-size: 0.8rem;
}

.legend-label {
  font-size: 0.85rem;
  color: #374151;
}

.is-dark {
  .progress-stepper .step-dot {
    border-color: #394150;
    background: #111827;
  }

  .progress-stepper .step-dot.active {
    border-color: #22c55e;
    background: #22c55e;
  }
}

.mode-chip {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 0.75rem;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: rgba(255, 255, 255, 0.6);
  color: #374151;
}

.mode-chip.is-manual {
  background: rgba(245, 158, 11, 0.10);
}

.mode-chip.is-dd {
  background: rgba(59, 130, 246, 0.10);
}

.is-dark .mode-chip {
  border-color: rgba(255, 255, 255, 0.12);
  background: rgba(17, 24, 39, 0.5);
  color: #e5e7eb;
}

.required-star {
  color: #ff3860;
  margin-left: 3px;
  font-weight: bold;
}

.optional-label {
  display: inline-flex;
  align-items: center;
  margin-left: 8px;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}

.is-dark .optional-label {
  color: #cbd5e1;
  background: rgba(15, 23, 42, 0.7);
  border-color: rgba(148, 163, 184, 0.24);
}
</style>
