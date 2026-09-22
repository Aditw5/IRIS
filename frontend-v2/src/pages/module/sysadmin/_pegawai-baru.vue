<route lang="yaml">
meta:
  requiresAuth: true
</route>
<template>
  <div class="columns">
    <div class="column is-12 form-layout is-stacked">
      <div class="form-outer">
        <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
          <div class="form-header-inner">
            <div class="left">
              <h3>Rekam Data Pegawai</h3>
            </div>
            <div class="right">
              <div class="buttons">
                <VButton
                  icon="lnir lnir-arrow-left rem-100"
                  @click="back()"
                  light
                  dark-outlined
                >
                  Cancel
                </VButton>
                <VButton
                  icon="feather:save"
                  type="submit"
                  color="primary"
                  raised
                  @click="save()"
                  :loading="isSimpan"
                >
                  Save
                </VButton>
              </div>
            </div>
          </div>
        </div>
        <div class="form-body">
          <div class="columns is-multiline">
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-8">
                  <VCard>
                    <h4 class="title is-5 mb-2">Data Pegawai</h4>
                    <div class="columns is-multiline">
                      <div class="column is-6">
                        <VField>
                          <VLabel>Nama Lengkap</VLabel>
                          <VControl icon="feather:user">
                            <VInput
                              type="text"
                              v-model="item.namalengkap"
                              placeholder="Nama Lengkap"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-3">
                        <VField>
                          <VLabel> NIK </VLabel>
                          <VControl icon="feather:credit-card">
                            <VInput
                              type="text"
                              v-model="item.persendiscount"
                              placeholder="NIK"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-3">
                        <VField>
                          <VLabel> NIP </VLabel>
                          <VControl icon="feather:credit-card">
                            <VInput
                              type="text"
                              v-model="item.nip"
                              placeholder="NIP"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-4">
                        <VField>
                          <VLabel> Nama Panggilan </VLabel>
                          <VControl icon="feather:user">
                            <VInput
                              type="text"
                              v-model="item.namapanggilan"
                              placeholder="Nama Panggilan"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-4">
                        <VField>
                          <VLabel> Gelar Depan </VLabel>
                          <VControl icon="feather:credit-card">
                            <VInput
                              type="text"
                              v-model="item.gelardepan"
                              placeholder="Gelar Depan"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-4">
                        <VField>
                          <VLabel> Gelar Belakang </VLabel>
                          <VControl icon="feather:credit-card">
                            <VInput
                              type="text"
                              v-model="item.gelarbelakang"
                              placeholder="Gelar Belakang"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>

                      <div class="column is-4">
                        <VField>
                          <VLabel> Tempat Lahir </VLabel>
                          <VControl icon="feather:home">
                            <VInput
                              type="text"
                              v-model="item.tempatlahir"
                              placeholder="Tempat Lahir"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-4">
                        <VDatePicker
                          v-model="item.tgllahir"
                          color="green"
                          trim-weeks
                          mode="dateTime"
                        >
                          <template #default="{ inputValue, inputEvents }">
                            <VField>
                              <VLabel>Tanggal Lahir</VLabel>
                              <VControl icon="feather:calendar">
                                <VInput
                                  type="text"
                                  placeholder="Pilih Tanggal"
                                  class="is-rounded"
                                  :value="inputValue"
                                  v-on="inputEvents"
                                  :disabled="disTanggal"
                                />
                              </VControl>
                            </VField>
                          </template>
                        </VDatePicker>
                      </div>
                      <div class="column is-4">
                        <VField class="is-rounded-select is-autocomplete-select">
                          <VLabel>Pendidikan Terakhir</VLabel>
                          <VControl icon="feather:search">
                            <Multiselect
                              mode="single"
                              v-model="item.objectpendidikanterakhirfk"
                              :options="d_pendidikan"
                              placeholder="Pilih Pendidikan"
                              :searchable="true"
                              :disabled="disabledRuangan"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-3">
                        <VField class="is-rounded-select is-autocomplete-select">
                          <VLabel>Status Perkawinan</VLabel>
                          <VControl icon="feather:search">
                            <Multiselect
                              mode="single"
                              v-model="item.objectpendidikanterakhirfk"
                              :options="d_statusperkawinan"
                              placeholder="Pilih"
                              :searchable="true"
                              :disabled="disabledRuangan"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-3">
                        <VField class="is-rounded-select is-autocomplete-select">
                          <VLabel>Jenis Kelamin</VLabel>
                          <VControl icon="feather:search">
                            <Multiselect
                              mode="single"
                              v-model="item.objectjeniskelaminfk"
                              :options="d_jeniskelamin"
                              placeholder="Pilih"
                              :searchable="true"
                              :disabled="disabledRuangan"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-3">
                        <VField class="is-rounded-select is-autocomplete-select">
                          <VLabel>Agama</VLabel>
                          <VControl icon="feather:search">
                            <Multiselect
                              mode="single"
                              v-model="item.objectagamafk"
                              :options="d_agama"
                              placeholder="Pilih"
                              :searchable="true"
                              :disabled="disabledRuangan"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-3">
                        <VField>
                          <VLabel> NPWP </VLabel>
                          <VControl icon="feather:credit-card">
                            <VInput
                              type="text"
                              v-model="item.npwp"
                              placeholder="NPWP"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-6">
                        <VField class="is-rounded-select is-autocomplete-select">
                          <VLabel>Unit Kerja</VLabel>
                          <VControl icon="feather:search">
                            <Multiselect
                              mode="single"
                              v-model="item.objectunitkerjafk"
                              :options="d_departemen"
                              placeholder="Pilih"
                              :searchable="true"
                              :disabled="disabledRuangan"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-6">
                        <VField class="is-rounded-select is-autocomplete-select">
                          <VLabel>Status Pegawai</VLabel>
                          <VControl icon="feather:search">
                            <Multiselect
                              mode="single"
                              v-model="item.objectstatuspegawaifk"
                              :options="d_statuspegawai"
                              placeholder="Pilih"
                              :searchable="true"
                              :disabled="disabledRuangan"
                            />
                          </VControl>
                        </VField>
                      </div>
                    </div>
                  </VCard>
                </div>
                <div class="column is-4">
                  <VCard>
                    <h3 class="title is-5 mb-2">Identitas</h3>
                    <div class="columns is-multiline">
                      <div class="column is-12">
                        <VField>
                          <VLabel> Alamat </VLabel>
                          <VControl icon="feather:home">
                            <VInput
                              type="text"
                              v-model="item.alamat"
                              placeholder="Alamat Domisili"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-6">
                        <VField>
                          <VLabel> Kode Pos </VLabel>
                          <VControl icon="feather:home">
                            <VInput
                              type="text"
                              v-model="item.kodepos"
                              placeholder="Kode Pos"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-6">
                        <VField>
                          <VLabel> Nomor Telpon </VLabel>
                          <VControl icon="feather:phone-call">
                            <VInput
                              type="text"
                              v-model="item.notlp"
                              placeholder="No. Telepon"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-12">
                        <VField>
                          <VLabel> Email </VLabel>
                          <VControl icon="feather:layers">
                            <VInput
                              type="text"
                              v-model="item.email"
                              placeholder="Email"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-6">
                        <VField>
                          <VLabel> Nama Bank </VLabel>
                          <VControl icon="feather:layers">
                            <VInput
                              type="text"
                              v-model="item.bankrekeningnama"
                              placeholder="Nama Bank"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-6">
                        <VField>
                          <VLabel> Nomor Rekening </VLabel>
                          <VControl icon="feather:layers">
                            <VInput
                              type="text"
                              v-model="item.bankrekeningnama"
                              placeholder="Nomor Rekening"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                      <div class="column is-12">
                        <VField>
                          <VLabel> Nama Pemilik Rekening </VLabel>
                          <VControl icon="feather:layers">
                            <VInput
                              type="text"
                              v-model="item.bankrekeningatasnama"
                              placeholder="Nama Rekening"
                              class="is-rounded"
                            />
                          </VControl>
                        </VField>
                      </div>
                    </div>
                  </VCard>
                </div>

                <div class="column is-12">
                  <VCard>
                    <TabView class="tabview-custom" ref="tabview4">
                      <TabPanel>
                        <template #header>
                          <span>Status Kepegawaian</span>
                        </template>
                        <VCard>
                          <div class="column is-12">
                            <div class="columns is-multiline">
                              <div class="column is-4">
                                <VDatePicker
                                  v-model="item.tglmasuk"
                                  color="green"
                                  trim-weeks
                                  mode="dateTime"
                                >
                                  <template #default="{ inputValue, inputEvents }">
                                    <VField>
                                      <VLabel>Tanggal Masuk</VLabel>
                                      <VControl icon="feather:calendar">
                                        <VInput
                                          type="text"
                                          placeholder="Pilih Tanggal Masuk"
                                          class="is-rounded"
                                          :value="inputValue"
                                          v-on="inputEvents"
                                          :disabled="disTanggal"
                                        />
                                      </VControl>
                                    </VField>
                                  </template>
                                </VDatePicker>
                              </div>
                              <div class="column is-4">
                                <VDatePicker
                                  v-model="item.tglkeluar"
                                  color="green"
                                  trim-weeks
                                  mode="dateTime"
                                >
                                  <template #default="{ inputValue, inputEvents }">
                                    <VField>
                                      <VLabel>Tanggal Keluar</VLabel>
                                      <VControl icon="feather:calendar">
                                        <VInput
                                          type="text"
                                          placeholder="Pilih Tanggal Keluar"
                                          class="is-rounded"
                                          :value="inputValue"
                                          v-on="inputEvents"
                                          :disabled="disTanggal"
                                        />
                                      </VControl>
                                    </VField>
                                  </template>
                                </VDatePicker>
                              </div>
                              <div class="column is-4">
                                <VField>
                                  <VLabel> Masa Kerja </VLabel>
                                  <VControl icon="feather:layers">
                                    <VInput
                                      type="text"
                                      v-model="item.masakerja"
                                      placeholder="Masa Kerja"
                                      class="is-rounded"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField>
                                  <VLabel> Usia Pensiun </VLabel>
                                  <VControl icon="feather:layers">
                                    <VInput
                                      type="text"
                                      v-model="item.pensiun"
                                      placeholder="Usia Pensiun"
                                      class="is-rounded"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VDatePicker
                                  v-model="item.tglpensiun"
                                  color="green"
                                  trim-weeks
                                  mode="dateTime"
                                >
                                  <template #default="{ inputValue, inputEvents }">
                                    <VField>
                                      <VLabel>Tanggal Pensiun</VLabel>
                                      <VControl icon="feather:calendar">
                                        <VInput
                                          type="text"
                                          placeholder="Pilih Tanggal Pensiun"
                                          class="is-rounded"
                                          :value="inputValue"
                                          v-on="inputEvents"
                                          :disabled="disTanggal"
                                        />
                                      </VControl>
                                    </VField>
                                  </template>
                                </VDatePicker>
                              </div>
                              <div class="column is-3">
                                <VField class="is-rounded-select is-autocomplete-select">
                                  <VLabel>Kedudukan Pegawai</VLabel>
                                  <VControl icon="feather:search">
                                    <Multiselect
                                      mode="single"
                                      v-model="item.kedudukanfk"
                                      :options="d_kedudukan"
                                      placeholder="Pilih"
                                      :searchable="true"
                                      :disabled="disabledRuangan"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField class="is-rounded-select is-autocomplete-select">
                                  <VLabel>Golongan Pegawai</VLabel>
                                  <VControl icon="feather:search">
                                    <Multiselect
                                      mode="single"
                                      v-model="item.objectgolonganpegawaifk"
                                      :options="d_golonganpegawai"
                                      placeholder="Pilih"
                                      :searchable="true"
                                      :disabled="disabledRuangan"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField class="is-rounded-select is-autocomplete-select">
                                  <VLabel>Jabatan Fungsional</VLabel>
                                  <VControl icon="feather:search">
                                    <Multiselect
                                      mode="single"
                                      v-model="item.objectjabatanstrukturalfk"
                                      :options="d_jabatan"
                                      placeholder="Pilih"
                                      :searchable="true"
                                      :disabled="disabledRuangan"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField class="is-rounded-select is-autocomplete-select">
                                  <VLabel>Kelompok Jabatan</VLabel>
                                  <VControl icon="feather:search">
                                    <Multiselect
                                      mode="single"
                                      v-model="item.objectkelompokjabatanfk"
                                      :options="d_kelompokjabatan"
                                      placeholder="Pilih"
                                      :searchable="true"
                                      :disabled="disabledRuangan"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField class="is-rounded-select is-autocomplete-select">
                                  <VLabel>Eselon</VLabel>
                                  <VControl icon="feather:search">
                                    <Multiselect
                                      mode="single"
                                      v-model="item.objecteselonfk"
                                      :options="d_eselon"
                                      placeholder="Pilih"
                                      :searchable="true"
                                      :disabled="disabledRuangan"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField class="is-rounded-select is-autocomplete-select">
                                  <VLabel>Jenis Pegawai</VLabel>
                                  <VControl icon="feather:search">
                                    <Multiselect
                                      mode="single"
                                      v-model="item.objectjenispegawaifk"
                                      :options="d_jenispegawai"
                                      placeholder="Pilih"
                                      :searchable="true"
                                      :disabled="disabledRuangan"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField class="is-rounded-select is-autocomplete-select">
                                  <VLabel>Pola Shift Kerja</VLabel>
                                  <VControl icon="feather:search">
                                    <Multiselect
                                      mode="single"
                                      v-model="item.objectshiftkerja"
                                      :options="d_shiftkerja"
                                      placeholder="Pilih"
                                      :searchable="true"
                                      :disabled="disabledRuangan"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField>
                                  <VLabel> ID Finger Print </VLabel>
                                  <VControl icon="feather:layers">
                                    <VInput
                                      type="text"
                                      v-model="item.fingerprintid"
                                      placeholder="Finger Print"
                                      class="is-rounded"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField>
                                  <VLabel> Nilai Jabatan </VLabel>
                                  <VControl icon="feather:layers">
                                    <VInput
                                      type="text"
                                      v-model="item.nilaijabatan"
                                      placeholder="Nilai Jabatan"
                                      class="is-rounded"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                              <div class="column is-3">
                                <VField>
                                  <VLabel> Grade </VLabel>
                                  <VControl icon="feather:layers">
                                    <VInput
                                      type="text"
                                      v-model="item.grade"
                                      placeholder="Grade"
                                      class="is-rounded"
                                    />
                                  </VControl>
                                </VField>
                              </div>
                            </div>
                          </div>
                        </VCard>
                      </TabPanel>
                      <TabPanel>
                        <template #header>
                          <span>Riwayat Pendidikan</span>
                        </template>

                        <div class="column is-12">
                          <Toolbar class="mb-4">
                            <template #start>
                              <VButton
                                icon="feather:plus"
                                color="info"
                                raised
                                @click="addPopUp1()"
                              >
                                Tambah Riwayat Pendidikan
                              </VButton>
                            </template>
                          </Toolbar>

                          <DataTable
                            :value="dataSourcePendidikan"
                            :paginator="true"
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 25]"
                            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                            responsiveLayout="stack"
                            breakpoint="960px"
                            sortMode="multiple"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                          >
                            <Column :exportable="false" header="#" style="width: 8rem">
                              <template #body="slotProps">
                                <Button
                                  icon="pi pi-pencil"
                                  class="p-button-rounded p-button-warning mr-2"
                                  @click="editRow(slotProps.data)"
                                />
                                <Button
                                  icon="pi pi-trash"
                                  class="p-button-rounded p-button-danger"
                                  @click="hapusRow(slotProps.data)"
                                />
                              </template>
                            </Column>

                            <Column field="no" header="No"></Column>
                            <Column field="objectpendidikanfk" header="Pendidikan"></Column>
                            <Column
                              field="namatempatpendidikan"
                              header="Institusi Pendidikan"
                            >
                            </Column>
                            <Column
                              field="alamattempatpendidikan"
                              header="Alamat"
                            ></Column>
                            <Column field="tglmasuk" header="Tahun Masuk"></Column>
                            <Column field="tgllulus" header="Tahun Lulus"></Column>
                            <template #paginatorstart>
                              <Button
                                type="button"
                                icon="pi pi-refresh"
                                class="p-button-text"
                              />
                            </template>
                            <template #paginatorend>
                              <Button
                                type="button"
                                icon="pi pi-cloud"
                                class="p-button-text"
                              />
                            </template>
                          </DataTable>
                        </div>
                      </TabPanel>
                      <TabPanel>
                        <template #header>
                          <span>Data Keluarga</span>
                        </template>

                        <div class="column is-12">
                          <Toolbar class="mb-4">
                            <template #start>
                              <VButton
                                icon="feather:plus"
                                color="info"
                                raised
                                @click="addPopUp2()"
                              >
                                Tambah Data Keluarga
                              </VButton>
                            </template>
                          </Toolbar>

                          <DataTable
                            :value="dataSourceKeluarga"
                            :paginator="true"
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 25]"
                            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                            responsiveLayout="stack"
                            breakpoint="960px"
                            sortMode="multiple"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                          >
                            <Column :exportable="false" header="#" style="width: 8rem">
                              <template #body="slotProps">
                                <Button
                                  icon="pi pi-pencil"
                                  class="p-button-rounded p-button-warning mr-2"
                                  @click="editKeluarga(slotProps.data)"
                                />
                                <Button
                                  icon="pi pi-trash"
                                  class="p-button-rounded p-button-danger"
                                  @click="hapusKeluarga(slotProps.data)"
                                />
                              </template>
                            </Column>

                            <Column field="no" header="No"></Column>
                            <Column field="namalengkap" header="Nama"></Column>
                            <Column field="hubungankeluarga" header="Hubungan Keluarga" > </Column>
                            <Column field="tgllahir" header="Tanggal Lahir"></Column>
                            <Column field="pendidikan" header="Pendidikan"></Column>
                            <Column field="pekerjaan" header="Pekerjaan"></Column>
                            <template #paginatorstart>
                              <Button
                                type="button"
                                icon="pi pi-refresh"
                                class="p-button-text"
                              />
                            </template>
                            <template #paginatorend>
                              <Button
                                type="button"
                                icon="pi pi-cloud"
                                class="p-button-text"
                              />
                            </template>
                          </DataTable>
                        </div>
                      </TabPanel>
                      
                      
                    </TabView>

                  </VCard>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- </div> -->
 <!-- Modal Pendidikan -->
    <VModal
      :open="modalInputPendidikan"
      title="Data Riwayat Pendidikan"
      size="medium"
      actions="right"
      @close="modalInputPendidikan = false"
    >
      <template #content>
        <form class="modal-form">
          <div class="columns is-multiline">
            <div class="column is-6">
              <VField class="is-rounded-select is-autocomplete-select">
                <VLabel>Pendidikan</VLabel>
                <VControl icon="feather:search">
                  <Multiselect
                    mode="single"
                    v-model="item.objectpendidikanfk"
                    :options="d_pendidikan"
                    placeholder="Pilih Pendidikan"
                    :searchable="true"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField>
                <VLabel>Nama Institusi</VLabel>
                <VControl icon="feather:home">
                  <VInput
                    type="text"
                    v-model="item.namatempatpendidikan"
                    placeholder="Nama Tempat Pendidikan "
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-4">
              <VField>
                <VLabel>Jurusan</VLabel>
                <VControl icon="feather:bookmark">
                  <VInput
                    type="text"
                    v-model="item.jurusan"
                    placeholder="Jurusan"
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-4">
              <VDatePicker
                v-model="item.tglmasuk"
                color="green"
                trim-weeks
                mode="dateTime"
              >
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VLabel>Tanggal Masuk</VLabel>
                    <VControl icon="feather:calendar">
                      <VInput
                        type="text"
                        placeholder="Pilih Tanggal Masuk"
                        class="is-rounded"
                        :value="inputValue"
                        v-on="inputEvents"
                        :disabled="disTanggal"
                      />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </div>
            <div class="column is-4">
              <VDatePicker
                v-model="item.tgllulus"
                color="green"
                trim-weeks
                mode="dateTime"
              >
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VLabel>Tanggal Lulus</VLabel>
                    <VControl icon="feather:calendar">
                      <VInput
                        type="text"
                        placeholder="Pilih Tanggal Lulus"
                        class="is-rounded"
                        :value="inputValue"
                        v-on="inputEvents"
                        :disabled="disTanggal"
                      />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </div>
            <div class="column is-12">
              <VField>
                <VLabel>Alamat</VLabel>
                <VControl icon="feather:bookmark">
                  <VInput
                    type="text"
                    v-model="item.alamattempatpendidikan"
                    placeholder="Alamat"
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-4">
              <VField>
                <VLabel>IPK</VLabel>
                <VControl icon="feather:bookmark">
                  <VInput
                    type="text"
                    v-model="item.nilaiipk"
                    placeholder="IPK"
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-4">
              <VDatePicker
                v-model="item.tglijazah"
                color="green"
                trim-weeks
                mode="dateTime"
              >
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VLabel>Tanggal Ijasah</VLabel>
                    <VControl icon="feather:calendar">
                      <VInput
                        type="text"
                        placeholder="Pilih Tanggal Ijazah"
                        class="is-rounded"
                        :value="inputValue"
                        v-on="inputEvents"
                        :disabled="disTanggal"
                      />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </div>
            <div class="column is-4">
              <VField>
                <VLabel>Nomor Ijazah</VLabel>
                <VControl icon="feather:bookmark">
                  <VInput
                    type="text"
                    v-model="item.noijazah"
                    placeholder="No.Ijazah"
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>
          </div>
        </form>
      </template>
      <template #action>
        <VButton
          icon="feather:plus"
          @click="addPendidikan()"
          :loading="isLoading"
          color="primary"
          raised
          >Tambah</VButton
        >
      </template>
    </VModal>
    <!-- Modal Data Keluarga-->
    <VModal
      :open="modalInputKeluarga"
      title="Data Keluarga"
      size="medium"
      actions="right"
      @close="modalInputKeluarga = false"
    >
      <template #content>
        <form class="modal-form">
          <div class="columns is-multiline">
            <div class="column is-4">
              <VField>
                <VLabel>Nama Anggota Keluarga</VLabel>
                <VControl icon="feather:user">
                  <VInput
                    type="text"
                    v-model="item.namalengkap"
                    placeholder="Nama Lengkap "
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-4">
              <VField class="is-rounded-select is-autocomplete-select">
                <VLabel>Hubungan Keluarga</VLabel>
                <VControl icon="feather:search">
                  <Multiselect
                    mode="single"
                    v-model="item.objectkdhubunganfk"
                    :options="d_hubungan"
                    placeholder="Pilih"
                    :searchable="true"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-4">
              <VField class="is-rounded-select is-autocomplete-select">
                <VLabel>Jenis Kelamin</VLabel>
                <VControl icon="feather:search">
                  <Multiselect
                    mode="single"
                    v-model="item.objectjeniskelaminfk"
                    :options="d_jeniskelamin"
                    placeholder="Pilih"
                    :searchable="true"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-4">
              <VField>
                <VLabel>Nama Ayah</VLabel>
                <VControl icon="feather:user">
                  <VInput
                    type="text"
                    v-model="item.namaayah"
                    placeholder="Nama Ayah"
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-4">
              <VField>
                <VLabel>Nama Ibu</VLabel>
                <VControl icon="feather:user">
                  <VInput
                    type="text"
                    v-model="item.namaibu"
                    placeholder="Nama Ibu"
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-4">
              <VField class="is-rounded-select is-autocomplete-select">
                <VLabel>Status Perkawinan</VLabel>
                <VControl icon="feather:search">
                  <Multiselect
                    mode="single"
                    v-model="item.objectstatusperkawinanfk"
                    :options="d_statusperkawinan"
                    placeholder="Pilih"
                    :searchable="true"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField class="is-rounded-select is-autocomplete-select">
                <VLabel>Pekerjaan</VLabel>
                <VControl icon="feather:search">
                  <Multiselect
                    mode="single"
                    v-model="item.objectpekerjaanfk"
                    :options="d_pekerjaan"
                    placeholder="Pilih"
                    :searchable="true"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField class="is-rounded-select is-autocomplete-select">
                <VLabel>Pendidikan Terakhir</VLabel>
                <VControl icon="feather:search">
                  <Multiselect
                    mode="single"
                    v-model="item.objectpendidikanfk"
                    :options="d_pendidikan"
                    placeholder="Pilih"
                    :searchable="true"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField>
                <VLabel>Alamat</VLabel>
                <VControl icon="feather:home">
                  <VInput
                    type="text"
                    v-model="item.alamat"
                    placeholder="Alamat"
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>
          </div>
        </form>
      </template>
      <template #action>
        <VButton
          icon="feather:plus"
          @click="addKeluarga()"
          :loading="isLoading"
          color="primary"
          raised
          >Tambah</VButton
        >
      </template>
    </VModal>
  </div>
</template>
<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import {
  ref,
  computed,
  defineComponent,
  watch,
  nextTick,
  onMounted,
  reactive,
  watchEffect,
} from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import { formatRp } from '/@src/utils/appHelper'
import { useToaster } from '/@src/composable/toaster'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import PrimeVue from 'primevue/config'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toolbar from 'primevue/toolbar'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import moment from 'moment'
const TITLE_PAGE = 'Pegawai'

import * as H from '/@src/utils/appHelper'
useHead({
  title: `${TITLE_PAGE} - Transmedic`,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
let ID_HARGANETTO = useRoute().query.id as string
let ID_HARGANETTO_SET = ref()
let NOREC_PD = useRoute().query.norec_pd as string
let NOREC_APD = useRoute().query.norec_apd as string
const modalInputPendidikan = ref(false)
const modalInputKeluarga = ref(false)

let item: any = reactive({
  header: {}
})
const TOTAL: any = ref(0)
const { y } = useWindowScroll()
const isStuck = computed(() => {
  return y.value > 30
})
const d_statusperkawinan: any = ref([])
const d_pendidikan: any = ref([])
const d_jeniskelamin: any = ref([])
const d_agama: any = ref([])
const d_pekerjaan: any = ref([])
const d_golonganpegawai: any = ref([])
const d_departemen: any = ref([])
const d_statuspegawai: any = ref([])
const d_kedudukan: any = ref([])
const d_jabatan: any = ref([])
const d_eselon: any = ref([])
const d_shiftkerja: any = ref([])
const d_jenispegawai: any = ref([])
const d_kelompokjabatan: any = ref([])
const d_hubungan: any = ref([])
const dataSource: any = ref([])
const dataSourcePendidikan: any = ref([])
const dataSourceKeluarga: any = ref([])
const data2: any = ref([])
const data3: any = ref([])

const isLoading: any = ref(false)
const isSimpan: any = ref(false)
let isRegistrasi = ref(false)
const disabledRuangan: any = ref(false)
const dataSelected: any = ref({})
const disTanggal: any = ref(false)

async function onInit() {
  item.loading = false
  const response = await useApi().get(
    `/sysadmin/master-pegawai?noreca_pd=${NOREC_APD}`
  )

  item.loading = true
  item.header = response.data
  loadDrop()
}

async function loadDrop() {
  const response = await useApi().get(`/sysadmin/master-pegawai-dropdown`)

  d_pendidikan.value = response.pendidikan.map((e: any) => {
    return { label: e.pendidikan, value: e.id }
  })
  d_jeniskelamin.value = response.jeniskelamin.map((e: any) => {
    return { label: e.jeniskelamin, value: e.id }
  })
  d_statusperkawinan.value = response.statusperkawinan.map((e: any) => {
    return { label: e.statusperkawinan, value: e.id }
  })
  d_agama.value = response.agama.map((e: any) => {
    return { label: e.agama, value: e.id }
  })
  d_departemen.value = response.namadepartemen.map((e: any) => {
    return { label: e.namadepartemen, value: e.id }
  })
  d_statuspegawai.value = response.statuspegawai.map((e: any) => {
    return { label: e.statuspegawai, value: e.id }
  })
  d_kedudukan.value = response.kedudukan.map((e: any) => {
    return { label: e.kedudukan, value: e.id }
  })
  d_golonganpegawai.value = response.name.map((e: any) => {
    return { label: e.name, value: e.id }
  })
  d_jabatan.value = response.namajabatan.map((e: any) => {
    return { label: e.namajabatan, value: e.id }
  })
  d_kelompokjabatan.value = response.namakelompokjabatan.map((e: any) => {
    return { label: e.namakelompokjabatan, value: e.id }
  })
  d_eselon.value = response.eselon.map((e: any) => {
    return { label: e.eselon, value: e.id }
  })
  d_jenispegawai.value = response.jenispegawai.map((e: any) => {
    return { label: e.jenispegawai, value: e.id }
  })
  d_hubungan.value = response.hubungankeluarga.map((e: any) => {
    return { label: e.hubungankeluarga, value: e.id }
  })
  d_pekerjaan.value = response.pekerjaan.map((e: any) => {
    return { label: e.pekerjaan, value: e.id }
  })

  disabledRuangan.value = false
  if (ID_HARGANETTO) {
    ID_HARGANETTO_SET.value = ID_HARGANETTO
  }
}

function addPopUp1() {
  clearInput()
  modalInputPendidikan.value = true
}
function editRow(data: any) {
  item.no = data.no
  item.objectpendidikanfk = data.objectpendidikanfk
  item.namatempatpendidikan = data.namatempatpendidikan
  item.alamattempatpendidikan = data.alamattempatpendidikan
  item.jurusan = data.jurusan
  item.tglmasuk = data.tglmasuk
  item.tgllulus = data.tgllulus
  item.tglijazah = data.tglijazah
  item.nilaiipk = data.nilaiipk
  item.noijazah = data.noijazah
  modalInputPendidikan.value = true
}
function addPendidikan() {
  if (!item.objectpendidikanfk) {
    useToaster().error('Pendidikan harus di isi')
    return
  }
  let nomor = 0
  if (data2.value.length == 0) {
    nomor = 1
  } else {
    nomor = data2.value.length + 1
  }
  var data: any = {}
  var pendidikan = ''
  for (let x = 0; x < d_pendidikan.value.length; x++) {
    const element = d_pendidikan.value[x]
    if (element.value == item.objectpendidikanfk) {
      pendidikan = element.label
      break
    }
  }
  if (item.no != undefined) {
    for (let x = 0; x < data2.value.length; x++) {
      const element = data2.value[x]
      if (element.no == item.no) {
        data.no = item.no
        data.objectpendidikanfk = item.objectpendidikanfk
        data.pendidikan = pendidikan
        data.alamattempatpendidikan = item.alamattempatpendidikan
        data.namatempatpendidikan = item.namatempatpendidikan
        data.jurusan = item.jurusan ? item.jurusan : null
        data.tglmasuk = item.tglmasuk ? item.tglmasuk : null
        data.tgllulus = item.tgllulus ? item.tgllulus : null
        data.nilaiipk = item.nilaiipk ? item.nilaiipk : null
        data.tglijazah = item.tglijazah ? item.tglijazah : null
        data.noijazah = item.noijazah ? item.noijazah : null

        data2.value[x] = data
      }
    }
  } else {
    data = {
      no: nomor,
      objectpendidikanfk: item.objectpendidikanfk,
      pendidikan : pendidikan,
      namatempatpendidikan: item.namatempatpendidikan,
      alamattempatpendidikan : item.alamattempatpendidikan,
      jurusan : item.jurusan ? item.jurusan : null,
      tglmasuk : item.tglmasuk ? H.formatDate(item.tglmasuk, 'YYYY-MM-DD') : null,
      tgllulus : item.tgllulus ? H.formatDate(item.tgllulus, 'YYYY-MM-DD') : null,
      nilaiipk : item.nilaiipk ? item.nilaiipk : null,
      tglijazah : item.tglijazah ? H.formatDate(item.tglijazah, 'YYYY-MM-DD') : null,
      noijazah : item.noijazah ? item.noijazah : null,
   
    }
    data2.value.push(data)
  }
  dataSourcePendidikan.value = data2.value
  clearInput()
}

function hapusRow(e: any) {
  for (var i = data2.value.length - 1; i >= 0; i--) {
    if (data2.value[i].no == e.no) {
      data2.value.splice(i, 1)
    }
  }
  dataSourcePendidikan.value = data2.value
  clearInput()
}
function addPopUp2() {
  clearInput()
  modalInputKeluarga.value = true
}
function addKeluarga() {
  if (!item.namalengkap) {
    useToaster().error('Nama Anggota Keluarga harus di isi')
    return
  }
  let nomor = 0
  if (data3.value.length == 0) {
    nomor = 1
  } else {
    nomor = data3.value.length + 1
  }
  var data: any = {}
  var hubungankeluarga = ''
  for (let x = 0; x < d_hubungan.value.length; x++) {
    const element = d_hubungan.value[x]
    if (element.value == item.objectkdhubunganfk) {
     hubungankeluarga = element.label
      break
    }
  }
  if (item.no != undefined) {
    for (let x = 0; x < data3.value.length; x++) {
      const element = data3.value[x]
      if (element.no == item.no) {
        data.no = item.no
        data.objectkdhubunganfk = item.objectkdhubunganfk
        data.objectkdhubunganfk = hubungankeluarga
        data.namalengkap = item.namalengkap
        data.objectpendidikanfk = item.objectpendidikanfk
        data.objectjeniskelaminfk = item.objectjeniskelaminfk ? item.objectjeniskelaminfk : null
        data.namaayah = item.namaayah ? item.namaayah : null
        data.namaibu = item.namaibu ? item.namaibu : null
        data.objectstatusperkawinanfk = item.objectstatusperkawinanfk ? item.objectstatusperkawinanfk : null
        data.objectpekerjaanfk = item.objectpekerjaanfk ? item.objectpekerjaanfk : null
        data.alamat = item.alamat ? item.alamat : null

        data3.value[x] = data
      }
    }
  } else {
    data = {
      no: nomor,
      objectkdhubunganfk: item.objectkdhubunganfk,
      namalengkap : item.namalengkap,
      objectpendidikanfk: item.objectpendidikanfk,
      objectjeniskelaminfk : item.objectjeniskelaminfk,
      objectstatusperkawinanfk : item.objectstatusperkawinanfk ? item.objectstatusperkawinanfk : null,
      tgllahir : item.tgllahir ? H.formatDate(item.tgllahir, 'YYYY-MM-DD') : null,
      objectpekerjaanfk : item.objectpekerjaanfk ? item.objectpekerjaanfk : null,
      namaayah : item.namaayah ? item.namaayah : null,
      namaibu : item.namaibu ? item.namaibu : null,
      alamat : item.alamat ? item.alamat : null,
    }
    data3.value.push(data)
  }
  dataSourceKeluarga.value = data2.value
  clearInput()
}

// async function save() {
//   if (!item.namalengkap) {
//     useToaster().error('Nama Lengkap harus di pilih')
//     return
//   }
//   if (!item.suratkeputusanfk) {
//     useToaster().error('Surat  harus di pilih')
//     return
//   }
//   if (!item.objectkelasfk) {
//     useToaster().error('Kelas  harus di pilih')
//     return
//   }
//   if (!item.objectjenispelayananfk) {
//     useToaster().error('Jenis Pelayanan harus di pilih')
//     return
//   }

//   if (data2.value.length == 0) {
//     useToaster().error('Nama Tempat Pendidikan belum di pilih')
//     return
//   }
//   debugger
//   var hargaSatuan = 0
//   var hargadijamin = 0
//   var persendiscount = 0
//   for (let x = 0; x < data2.value.length; x++) {
//     const element = data2.value[x]
//     hargaSatuan = hargaSatuan + parseFloat(element.hargasatuan)
//     if (element.persendiscount)
//       hargadijamin = hargadijamin + parseFloat(element.persendiscount)
//     if (element.persendiscount)
//       persendiscount = persendiscount + parseFloat(element.persendiscount)
//   }
//   var objSave = {
//     harganettoprodukbykelas: {
//       id: item.id ? item.id : '',
//       suratkeputusanfk: item.suratkeputusanfk,
//       objectkelasfk: item.objectkelasfk,
//       objectjenistariffk: item.objectjenistariffk ? item.objectjenistariffk : null,
//       objectprodukfk: item.objectprodukfk,
//       objectasalprodukfk: item.objectasalprodukfk ? item.objectasalprodukfk : null,
//       objectjenispelayananfk: item.objectjenispelayananfk,
//       objectmatauangfk: item.objectmatauangfk ? item.objectmatauangfk : null,
//       tglberlakuawal: item.tglberlakuawal
//         ? H.formatDate(item.tglberlakuawal, 'YYYY-MM-DD')
//         : null,
//       tglberlakuakhir: item.tglberlakuakhir
//         ? H.formatDate(item.tglberlakuakhir, 'YYYY-MM-DD')
//         : null,
//       tglkadaluarsalast: item.tglkadaluarsalast
//         ? H.formatDate(item.tglkadaluarsalast, 'YYYY-MM-DD')
//         : null,
//       hargasatuan: hargaSatuan,
//       hargadijamin: hargadijamin,
//       persendiscount: persendiscount,
//     },
//     harganettoprodukbykelasd: data2.value,
//   }

//   isSimpan.value = true
//   await useApi()
//     .post(`/sysadmin/save-master-pegawai`, objSave)
//     .then(
//       (response: any) => {
//         isSimpan.value = false
//         window.history.back()
//       },
//       (error) => {
//         isSimpan.value = false
//         // console.log(error)
//       }
//     )
// }

function clearInput() {
  delete item.objectpendidikanfk
  delete item.namatempatpendidikan
   delete item.alamattempatpendidikan
   delete item.tglmasuk
   delete item.tgllulus
   delete item.tglijazah
   delete item.jurusan
   delete item.nilaiipk

  delete dataSelected.value
  modalInputPendidikan.value = false
  modalInputKeluarga.value = false
}

function back() {
  window.history.back()
}
onInit()

onMounted(() => {})
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';
// @import '/@src/scss/custom/config';

.form-layout .form-outer {
  border: 1px solid transparent;
  background-color: transparent;
}

// .form-layout {
//     .form-outer {
//         padding: 20px 40px 40px;
//     }
// }
//
</style>
