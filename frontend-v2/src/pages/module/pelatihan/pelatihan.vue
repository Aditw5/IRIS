<template>
  <ConfirmDialog />
  <div class="column">
    <VCard class="kp-header-card">
      <div class="kp-hero">
        <div class="kp-hero-left">
          <img src="/@src/assets/illustrations/dashboards/personal/UMRO.png" alt="UMRO Laboratory"
            class="kp-hero-logo" />
          <div class="kp-hero-text">
            <h3 class="kp-hero-title">FMMO-163-14.4.3.b-62.3 Usulan Pelatihan/Serfikasi Personel</h3>
            <!-- <p class="kp-hero-sub">Kelola master & pengajuan pelatihan</p> -->
          </div>
        </div>

        <div class="kp-hero-right">
          <VButton v-if="canManagePelatihan()" color="primary" raised icon="feather:plus" @click="openModal()">
            Buat Pelatihan
          </VButton>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <div class="columns is-variable is-5">
      <div class="column is-12">
        <VCard>
          <div class="is-flex is-align-items-center is-justify-content-space-between">
            <h3 class="title is-6">Daftar Pelatihan (Master)</h3>
            <div class="is-flex" style="gap:.5rem">
              <VButton color="info" outlined icon="feather:refresh-ccw" @click="fetchData()">Refresh
              </VButton>
            </div>
          </div>

          <div class="list-view list-view-v3">
            <div class="list-view-inner mt-2" style="max-height:1000px;overflow: auto;">
              <div name="list-complete" tag="div">
                <div v-if="isPlaceLoad && dataSourceListPelatihan.length === 0">
                  <VPlaceloadWrap v-for="n in 6" :key="'sk-m-' + n">
                    <VPlaceload class="mx-2 mb-3" />
                    <VPlaceload class="mx-2" />
                  </VPlaceloadWrap>
                </div>

                <div v-else-if="dataSourceListPelatihan.length === 0" class="notification is-light">
                  Belum ada pelatihan.
                </div>

                <div v-else v-for="(p, idx) in dataSourceListPelatihan" :key="p.id">
                  <div class="list-view-item">
                    <div class="list-view-item-inner">
                      <div class="meta-left">
                        <h3>
                          {{ p.judulpelatihan }}
                          <VTag class="ml-2" :color="getJenisPelatihanRow(p) === 'PUBLIK' ? 'info' : getJenisPelatihanRow(p) === 'EKSTERNAL' ? 'primary' : 'warning'" rounded>
                            {{ (p).jenispelatihan }}
                          </VTag>
                        </h3>

                        <span>
                          <i aria-hidden="true" class="iconify" data-icon="feather:calendar"></i>
                          <span>Pendaftaran:
                            {{ H.formatDateToLocalString(p.periodependaftaranawal) }} →
                            {{ H.formatDateToLocalString(p.periodependaftaranakhir) }}
                          </span>
                          <i aria-hidden="true" class="fas fa-circle icon-separator"></i>
                          <i aria-hidden="true" class="iconify" data-icon="feather:calendar"></i>
                          <span>Pelatihan: {{ H.formatDateToLocalString(p.tglpelatihan) }}</span>
                        </span>

                        <div class="mt-2">
                          <VTag :color="statusTag(p).color" rounded>{{ statusTag(p).text }}</VTag>
                        </div>

                        <div class="is-size-7 has-text-grey mt-2 ellipsis-2">{{ p.deskripsi }}</div>
                      </div>

                      <div class="meta-right flex justify-center items-center" v-if="canManagePelatihan()">
                        <div class="buttons">
                          <VIconButton v-tooltip.bottom.left="'Edit'" icon="feather:edit-2"
                            @click="startEditPelatihan(p)" color="warning" outlined class="mr-2" />
                          <VIconButton v-tooltip.bottom.left="'Hapus'" icon="feather:trash-2" @click="dialogConfirm(p)"
                            color="danger" outlined />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <VFlexPagination v-model:current-page="pageMaster.page" :item-per-page="pageMaster.limit"
            :total-items="totalMaster" :max-links-displayed="5" class="mt-3">
            <template #before-navigation>
              <VFlex class="mr-4 mt-1" column-gap="1rem">
                <VField>
                  <VControl>
                    <div class="select is-rounded">
                      <select v-model="pageMaster.limit">
                        <option :value="1">1 results per page</option>
                        <option :value="5">6 results per page</option>
                        <option :value="10">10 results per page</option>
                        <option :value="15">15 results per page</option>
                        <option :value="25">25 results per page</option>
                        <option :value="50">50 results per page</option>
                      </select>
                    </div>
                  </VControl>
                </VField>
              </VFlex>
            </template>
          </VFlexPagination>
        </VCard>
      </div>
    </div>
  </div>

  <div class="column">
    <!-- CARD PUBLIK -->
    <div class="column is-12">
      <VCard>
        <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
          <h3 class="title is-5 mb-2">Riwayat Pengajuan Pelatihan - PUBLIK</h3>
          <div class="is-flex is-align-items-center" style="gap:.5rem">
            <VButton color="primary" icon="feather:plus" raised @click="openManualModal('PUBLIK')">
              Tambah Riwayat Manual
            </VButton>
            <VTag :color="'warning'" rounded>Diajukan</VTag>
            <VTag :color="'success'" rounded>Disetujui</VTag>
            <VTag :color="'danger'" rounded>Ditolak</VTag>
          </div>
        </div>

        <div class="column" v-if="isPlaceLoad">
          <VPlaceloadWrap v-for="data in 12" :key="'pl-pub-' + data">
            <VPlaceload class="mx-2 mb-3" />
            <VPlaceload class="mx-2" />
          </VPlaceloadWrap>
        </div>

        <div class="column" v-else>
          <VPlaceholderPage v-if="dataSourcePublik.length == 0" title="Belum ada pengajuan pelatihan PUBLIK"
            subtitle="Silakan buat pengajuan pelatihan baru atau gunakan pencarian di atas" larger>
            <template #image>
              <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
              <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
            </template>
          </VPlaceholderPage>

          <div v-else>
            <DataTable :value="dataSourcePublik" class="p-datatable-sm" :loading="isLoading" :paginator="true"
              :rows="10" :rowsPerPageOptions="[5, 10, 25]" scrollable
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
              <template #header>
                <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                  <div class="is-flex is-align-items-center" style="gap:.5rem">
                    <VButton color="warning" class="mr-2 mb-3" icon="fas fa-file-excel" raised
                      @click="exportExcel(dataSourcePublik, 'riwayat-pelatihan-publik.xlsx')">
                      Export Excel
                    </VButton>
                    <VButton color="info" class="mb-3" icon="feather:refresh-ccw" outlined @click="fetchDataRiwayat()">
                      Refresh
                    </VButton>
                  </div>
                </div>
              </template>

              <Column field="no" header="#" style="width:70px;min-width:70px"></Column>

              <Column field="Aksi" header="Aksi" style="min-width: 120px; text-align: center;">
                <template #body="slotProps">
                  <VIconButton v-if="(slotProps.data.status == null)" v-tooltip.top.left="'Setujui'" color="success"
                    outlined circle icon="fas fa-check" @click="confirmApproval(slotProps.data, 'Disetujui')" />
                  <VIconButton v-if="(slotProps.data.status == null)" v-tooltip.top.left="'Tolak'" class="ml-3"
                    color="warning" outlined circle icon="feather:x"
                    @click="confirmApproval(slotProps.data, 'Ditolak')" />
                  <VIconButton v-if="(slotProps.data.status != null)" v-tooltip.top.left="'Hapus'" class="ml-3"
                    color="danger" outlined circle icon="feather:trash"
                    @click="deletePengajuanPelatihan(slotProps.data)" />
                </template>
              </Column>

              <Column field="status" header="Status" :sortable="true" style="min-width:140px">
                <template #body="slotProps">
                  <VTag class="ml-2" :color="slotProps.data.color" rounded>{{ slotProps.data.status }}</VTag>
                </template>
              </Column>
              <Column field="keteranganstatus" header="Keterangan Status" :sortable="true" style="min-width:240px">
              </Column>
              <Column field="namalengkap" header="Nama Pegawai" :sortable="true" style="min-width:240px"></Column>
              <Column field="judulpelatihan" header="Judul" :sortable="true" style="min-width:240px"></Column>

              <Column field="periode" header="Periode Pendaftaran" :sortable="false" style="min-width:240px">
                <template #body="slotProps">
                  <span>
                    {{ H.formatDateToLocalString(slotProps.data.periodependaftaranawal) }} →
                    {{ H.formatDateToLocalString(slotProps.data.periodependaftaranakhir) }}
                  </span>
                </template>
              </Column>

              <Column field="tglpelatihan" header="Tgl Pelatihan" :sortable="true" style="min-width:180px">
                <template #body="slotProps">
                  <span>{{ H.formatDateToLocalString(slotProps.data.tglpelatihan) }}</span>
                </template>
              </Column>

              <Column field="created_at" header="Dibuat" :sortable="true" style="min-width:200px">
                <template #body="slotProps">
                  <span>{{ H.formatDateToLocalString(slotProps.data.created_at) }}</span>
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </VCard>
    </div>

    <!-- CARD IHT -->
    <div class="column is-12">
      <VCard>
        <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
          <h3 class="title is-5 mb-2">Riwayat Pengajuan Pelatihan - IHT</h3>
          <div class="is-flex is-align-items-center" style="gap:.5rem">
            <VButton color="primary" icon="feather:plus" raised @click="openManualModal('IHT')">
              Tambah Riwayat Manual
            </VButton>
            <VTag :color="'warning'" rounded>Diajukan</VTag>
            <VTag :color="'success'" rounded>Disetujui</VTag>
            <VTag :color="'danger'" rounded>Ditolak</VTag>
          </div>
        </div>

        <div class="column" v-if="isPlaceLoad">
          <VPlaceloadWrap v-for="data in 12" :key="'pl-iht-' + data">
            <VPlaceload class="mx-2 mb-3" />
            <VPlaceload class="mx-2" />
          </VPlaceloadWrap>
        </div>

        <div class="column" v-else>
          <VPlaceholderPage v-if="dataSourceIht.length == 0" title="Belum ada pengajuan pelatihan IHT"
            subtitle="Silakan buat pengajuan pelatihan baru atau gunakan pencarian di atas" larger>
            <template #image>
              <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
              <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
            </template>
          </VPlaceholderPage>

          <div v-else>
            <DataTable :value="dataSourceIht" class="p-datatable-sm" :loading="isLoading" :paginator="true" :rows="10"
              :rowsPerPageOptions="[5, 10, 25]" scrollable
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
              <template #header>
                <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                  <div class="is-flex is-align-items-center" style="gap:.5rem">
                    <VButton color="warning" class="mr-2 mb-3" icon="fas fa-file-excel" raised
                      @click="exportExcel(dataSourceIht, 'riwayat-pelatihan-iht.xlsx')">
                      Export Excel
                    </VButton>
                    <VButton color="info" class="mb-3" icon="feather:refresh-ccw" outlined @click="fetchDataRiwayat()">
                      Refresh
                    </VButton>
                  </div>
                </div>
              </template>

              <Column field="no" header="#" style="width:70px;min-width:70px"></Column>

              <Column field="Aksi" header="Aksi" style="min-width: 120px; text-align: center;">
                <template #body="slotProps">
                  <VIconButton v-if="(slotProps.data.status == null)" v-tooltip.top.left="'Setujui'" color="success"
                    outlined circle icon="fas fa-check" @click="confirmApproval(slotProps.data, 'Disetujui')" />
                  <VIconButton v-if="(slotProps.data.status == null)" v-tooltip.top.left="'Tolak'" class="ml-3"
                    color="warning" outlined circle icon="feather:x"
                    @click="confirmApproval(slotProps.data, 'Ditolak')" />
                  <VIconButton v-if="(slotProps.data.status != null)" v-tooltip.top.left="'Hapus'" class="ml-3"
                    color="danger" outlined circle icon="feather:trash"
                    @click="deletePengajuanPelatihan(slotProps.data)" />
                </template>
              </Column>

              <Column field="status" header="Status" :sortable="true" style="min-width:140px">
                <template #body="slotProps">
                  <VTag class="ml-2" :color="slotProps.data.color" rounded>{{ slotProps.data.status }}</VTag>
                </template>
              </Column>
              <Column field="keteranganstatus" header="Keterangan Status" :sortable="true" style="min-width:240px">
              </Column>
              <Column field="namalengkap" header="Nama Pegawai" :sortable="true" style="min-width:240px"></Column>
              <Column field="judulpelatihan" header="Judul" :sortable="true" style="min-width:240px"></Column>

              <Column field="periode" header="Periode Pendaftaran" :sortable="false" style="min-width:240px">
                <template #body="slotProps">
                  <span>
                    {{ H.formatDateToLocalString(slotProps.data.periodependaftaranawal) }} →
                    {{ H.formatDateToLocalString(slotProps.data.periodependaftaranakhir) }}
                  </span>
                </template>
              </Column>

              <Column field="tglpelatihan" header="Tgl Pelatihan" :sortable="true" style="min-width:180px">
                <template #body="slotProps">
                  <span>{{ H.formatDateToLocalString(slotProps.data.tglpelatihan) }}</span>
                </template>
              </Column>

              <Column field="created_at" header="Dibuat" :sortable="true" style="min-width:200px">
                <template #body="slotProps">
                  <span>{{ H.formatDateToLocalString(slotProps.data.created_at) }}</span>
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </VCard>
    </div>

    <!-- CARD EKSTERNAL -->
    <div class="column is-12">
      <VCard>
        <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
          <h3 class="title is-5 mb-2">Riwayat Pengajuan Pelatihan - EKSTERNAL</h3>
          <div class="is-flex is-align-items-center" style="gap:.5rem">
            <VButton color="primary" icon="feather:plus" raised @click="openManualModal('EKSTERNAL')">
              Tambah Riwayat Manual
            </VButton>
            <VTag :color="'warning'" rounded>Diajukan</VTag>
            <VTag :color="'success'" rounded>Disetujui</VTag>
            <VTag :color="'danger'" rounded>Ditolak</VTag>
          </div>
        </div>

        <div class="column" v-if="isPlaceLoad">
          <VPlaceloadWrap v-for="data in 12" :key="'pl-eks-' + data">
            <VPlaceload class="mx-2 mb-3" />
            <VPlaceload class="mx-2" />
          </VPlaceloadWrap>
        </div>

        <div class="column" v-else>
          <VPlaceholderPage v-if="dataSourceEksternal.length == 0" title="Belum ada pengajuan pelatihan EKSTERNAL"
            subtitle="Silakan buat pengajuan pelatihan baru atau gunakan pencarian di atas" larger>
            <template #image>
              <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
              <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
            </template>
          </VPlaceholderPage>

          <div v-else>
            <DataTable :value="dataSourceEksternal" class="p-datatable-sm" :loading="isLoading" :paginator="true"
              :rows="10" :rowsPerPageOptions="[5, 10, 25]" scrollable
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
              <template #header>
                <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                  <div class="is-flex is-align-items-center" style="gap:.5rem">
                    <VButton color="warning" class="mr-2 mb-3" icon="fas fa-file-excel" raised
                      @click="exportExcel(dataSourceEksternal, 'riwayat-pelatihan-eksternal.xlsx')">
                      Export Excel
                    </VButton>
                    <VButton color="info" class="mb-3" icon="feather:refresh-ccw" outlined @click="fetchDataRiwayat()">
                      Refresh
                    </VButton>
                  </div>
                </div>
              </template>

              <Column field="no" header="#" style="width:70px;min-width:70px"></Column>

              <Column field="Aksi" header="Aksi" style="min-width: 120px; text-align: center;">
                <template #body="slotProps">
                  <VIconButton v-if="(slotProps.data.status == null)" v-tooltip.top.left="'Setujui'" color="success"
                    outlined circle icon="fas fa-check" @click="confirmApproval(slotProps.data, 'Disetujui')" />
                  <VIconButton v-if="(slotProps.data.status == null)" v-tooltip.top.left="'Tolak'" class="ml-3"
                    color="warning" outlined circle icon="feather:x"
                    @click="confirmApproval(slotProps.data, 'Ditolak')" />
                  <VIconButton v-if="(slotProps.data.status != null)" v-tooltip.top.left="'Hapus'" class="ml-3"
                    color="danger" outlined circle icon="feather:trash"
                    @click="deletePengajuanPelatihan(slotProps.data)" />
                </template>
              </Column>

              <Column field="status" header="Status" :sortable="true" style="min-width:140px">
                <template #body="slotProps">
                  <VTag class="ml-2" :color="slotProps.data.color" rounded>{{ slotProps.data.status }}</VTag>
                </template>
              </Column>
              <Column field="keteranganstatus" header="Keterangan Status" :sortable="true" style="min-width:240px">
              </Column>
              <Column field="namalengkap" header="Nama Pegawai" :sortable="true" style="min-width:240px"></Column>
              <Column field="judulpelatihan" header="Judul" :sortable="true" style="min-width:240px"></Column>
              <Column field="lembaga" header="Lembaga" :sortable="true" style="min-width:200px"></Column>

              <Column field="periode" header="Periode Pendaftaran" :sortable="false" style="min-width:240px">
                <template #body="slotProps">
                  <span>
                    {{ H.formatDateToLocalString(slotProps.data.periodependaftaranawal) }} →
                    {{ H.formatDateToLocalString(slotProps.data.periodependaftaranakhir) }}
                  </span>
                </template>
              </Column>

              <Column field="tglpelatihan" header="Tgl Pelatihan" :sortable="true" style="min-width:180px">
                <template #body="slotProps">
                  <span>{{ H.formatDateToLocalString(slotProps.data.tglpelatihan) }}</span>
                </template>
              </Column>

              <Column field="created_at" header="Dibuat" :sortable="true" style="min-width:200px">
                <template #body="slotProps">
                  <span>{{ H.formatDateToLocalString(slotProps.data.created_at) }}</span>
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </VCard>
    </div>
  </div>

  <!-- Modal Buat / Edit Pelatihan -->
  <div :class="['modal', modalInput ? 'is-active' : '']">
    <div class="modal-background" @click="closeModal()"></div>
    <div class="modal-card" style="width: 920px; max-width: 92vw">
      <header class="modal-card-head">
        <p class="modal-card-title">{{ isEditMode ? 'Edit Pelatihan' : 'Buat Pengajuan Pelatihan' }}</p>
        <button class="delete" aria-label="close" @click="closeModal()"></button>
      </header>
      <section class="modal-card-body">
        <div class="columns is-multiline">
          <div class="column is-8">
            <VField label="Judul Pelatihan">
              <VControl icon="feather:book-open">
                <VInput v-model="form.judul" placeholder="Contoh: Pelatihan ISO/IEC 17025:2017" />
              </VControl>
            </VField>
          </div>

          <div class="column is-6">
            <VField label="Periode Pendaftaran">
              <VDatePicker v-model="form.pendaftaran" is-range color="info" trim-weeks>
                <template #default="{ inputValue, inputEvents }">
                  <VField addons>
                    <VControl icon="feather:calendar">
                      <VInput :value="inputValue.start" class="input-calendar" v-on="inputEvents.start" />
                    </VControl>
                    <VControl>
                      <VButton static><i class="fas fa-arrow-right" aria-hidden="true"></i></VButton>
                    </VControl>
                    <VControl icon="feather:calendar">
                      <VInput :value="inputValue.end" class="input-calendar" v-on="inputEvents.end" />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </VField>
          </div>

          <div class="column is-3">
            <VField label="Tanggal Pelatihan">
              <VDatePicker v-model="form.tglPelatihan" color="info">
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VControl icon="feather:calendar">
                      <VInput :value="inputValue" class="input-calendar" v-on="inputEvents" />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </VField>
          </div>

          <div class="column is-3">
            <VField label="Jenis Pelatihan">
              <AutoComplete v-model="form.jenispelatihan" :suggestions="d_jenisPelatihan"
                @complete="fetchJenisPelatihan($event)" :optionLabel="'label'" :dropdown="true" :minLength="0"
                class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                placeholder="ketik untuk mencari..." />
            </VField>
          </div>

          <div class="column is-12">
            <VField label="Deskripsi">
              <VControl icon="feather:file-text">
                <textarea class="textarea" rows="3" v-model="form.deskripsi"
                  placeholder="Ringkasan materi, tujuan pelatihan, dll."></textarea>
              </VControl>
            </VField>
          </div>
        </div>

        <div class="notification is-light" :class="validError ? 'is-danger' : 'is-info'">
          <i class="fas" :class="validError ? 'fa-triangle-exclamation' : 'fa-info-circle'" />
          <span class="ml-2">{{ validError ? validError : 'Pengajuan akan berstatus Diajukan dan menunggupersetujuanatasan.' }}</span>
        </div>
      </section>
      <footer class="modal-card-foot is-justify-content-space-between">
        <div>
          <VButton color="dark" outlined icon="feather:x" @click="closeModal()">Batal</VButton>
        </div>
        <div>
          <VButton :loading="isSubmitting" color="primary" raised icon="feather:plus" @click="submitPelatihan()">
            {{ isEditMode ? 'Simpan Perubahan' : 'Simpan Pelatihan' }}
          </VButton>
        </div>
      </footer>
    </div>
  </div>

  <!-- Modal Approval -->
  <div :class="['modal', modalApproval ? 'is-active' : '']">
    <div class="modal-background" @click="closeApprovalModal()"></div>
    <div class="modal-card" style="width: 640px; max-width: 92vw">
      <header class="modal-card-head">
        <p class="modal-card-title">Keterangan {{ approvalStatusBaru }}</p>
        <button class="delete" aria-label="close" @click="closeApprovalModal()"></button>
      </header>
      <section class="modal-card-body">
        <VField label="Keterangan">
          <VControl>
            <textarea class="textarea" rows="5" v-model="approvalNote"
              :placeholder="approvalStatusBaru === 'Disetujui' ? 'Contoh: Disetujui oleh atasan, silakan lanjut proses.' : 'Contoh: Ditolak karena jadwal bentrok / alasan lain.'"></textarea>
          </VControl>
        </VField>

        <div v-if="approvalError" class="notification is-danger is-light">
          <i class="fas fa-triangle-exclamation"></i>
          <span class="ml-2">{{ approvalError }}</span>
        </div>
      </section>
      <footer class="modal-card-foot is-justify-content-space-between">
        <div>
          <VButton color="dark" outlined icon="feather:x" @click="closeApprovalModal()">Batal</VButton>
        </div>
        <div>
          <VButton :loading="approvalSubmitting" color="primary" raised icon="feather:check"
            @click="submitApprovalWithNote()">Simpan</VButton>
        </div>
      </footer>
    </div>
  </div>

  <!-- Modal Tambah Riwayat Manual -->
  <div :class="['modal', modalManualRiwayat ? 'is-active' : '']">
    <div class="modal-background" @click="closeManualModal()"></div>
    <div class="modal-card" style="width: 980px; max-width: 94vw">
      <header class="modal-card-head">
        <p class="modal-card-title">Tambah Riwayat Pelatihan Manual - {{ manualForm.jenisLabel }}</p>
        <button class="delete" aria-label="close" @click="closeManualModal()"></button>
      </header>

      <section class="modal-card-body">
        <div class="columns is-multiline">
          <div class="column is-6">
            <VField label="Nama Pegawai">
              <AutoComplete v-model="manualForm.pegawai" :suggestions="d_pegawai" @complete="fetchPegawai($event)"
                :optionLabel="'label'" :dropdown="true" :minLength="0" class="is-input" :appendTo="'body'"
                :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik nama pegawai..." />
            </VField>
          </div>

          <div class="column is-6">
            <VField label="Status">
              <VControl>
                <div class="select is-fullwidth">
                  <select v-model="manualForm.status">
                    <option value="Diajukan">Diajukan</option>
                    <option value="Disetujui">Disetujui</option>
                    <option value="Ditolak">Ditolak</option>
                  </select>
                </div>
              </VControl>
            </VField>
          </div>

          <div class="column is-8">
            <VField label="Judul Pelatihan">
              <VControl icon="feather:book-open">
                <VInput v-model="manualForm.judul" placeholder="Contoh: Pelatihan ISO/IEC 17025:2017" />
              </VControl>
            </VField>
          </div>

          <div class="column is-4">
            <VField label="Jenis Pelatihan">
              <AutoComplete v-model="manualForm.jenispelatihan" :suggestions="d_jenisPelatihan"
                @complete="fetchJenisPelatihan($event)" :optionLabel="'label'" :dropdown="true" :minLength="0"
                class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                placeholder="ketik untuk mencari..." />
            </VField>
          </div>

          <div class="column is-12" v-if="(manualForm.jenispelatihan?.label || '').toString().trim().toUpperCase() === 'EKSTERNAL'">
            <VField label="Lembaga">
              <VControl icon="feather:home">
                <VInput v-model="manualForm.lembaga" placeholder="Contoh: BSN, SNSU, KAN, Sucofindo, dll." />
              </VControl>
            </VField>
          </div>

          <div class="column is-6">
            <VField label="Periode Pendaftaran">
              <VDatePicker v-model="manualForm.pendaftaran" is-range color="info" trim-weeks>
                <template #default="{ inputValue, inputEvents }">
                  <VField addons>
                    <VControl icon="feather:calendar">
                      <VInput :value="inputValue.start" class="input-calendar" v-on="inputEvents.start" />
                    </VControl>
                    <VControl>
                      <VButton static><i class="fas fa-arrow-right" aria-hidden="true"></i></VButton>
                    </VControl>
                    <VControl icon="feather:calendar">
                      <VInput :value="inputValue.end" class="input-calendar" v-on="inputEvents.end" />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </VField>
          </div>

          <div class="column is-3">
            <VField label="Tanggal Pelatihan">
              <VDatePicker v-model="manualForm.tglPelatihan" color="info">
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VControl icon="feather:calendar">
                      <VInput :value="inputValue" class="input-calendar" v-on="inputEvents" />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </VField>
          </div>

          <div class="column is-3">
            <VField label="Tanggal Input Riwayat">
              <VDatePicker v-model="manualForm.tglInputRiwayat" color="info">
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VControl icon="feather:calendar">
                      <VInput :value="inputValue" class="input-calendar" v-on="inputEvents" />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </VField>
          </div>

          <div class="column is-12">
            <VField label="Keterangan Status">
              <VControl icon="feather:edit-3">
                <textarea class="textarea" rows="3" v-model="manualForm.keteranganstatus"
                  placeholder="Contoh: Riwayat lama sebelum penggunaan web / disetujui / ditolak, dll."></textarea>
              </VControl>
            </VField>
          </div>

          <div class="column is-12">
            <VField label="Deskripsi">
              <VControl icon="feather:file-text">
                <textarea class="textarea" rows="3" v-model="manualForm.deskripsi"
                  placeholder="Ringkasan materi, tujuan pelatihan, dll."></textarea>
              </VControl>
            </VField>
          </div>
        </div>

        <div class="notification is-light" :class="manualError ? 'is-danger' : 'is-info'">
          <i class="fas" :class="manualError ? 'fa-triangle-exclamation' : 'fa-info-circle'" />
          <span class="ml-2">
            {{ manualError ? manualError : 'Form ini dipakai untuk menambahkan riwayat lama secara manual tanpa lewatproses pengajuan otomatis.' }}
          </span>
        </div>
      </section>

      <footer class="modal-card-foot is-justify-content-space-between">
        <div>
          <VButton color="dark" outlined icon="feather:x" @click="closeManualModal()">Batal</VButton>
        </div>
        <div>
          <VButton :loading="manualSubmitting" color="primary" raised icon="feather:save"
            @click="submitManualRiwayat()">
            Simpan Riwayat Manual
          </VButton>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useThemeColors } from '/@src/composable/useThemeColors'
import * as H from '/@src/utils/appHelper'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import moment from 'moment'
import * as XLSX from 'xlsx'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from "primevue/useconfirm"
import AutoComplete from 'primevue/autocomplete'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'

useHead({ title: 'Usulan Pelatihan - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const themeColors = useThemeColors()
const userLogin = useUserSession().getUser()
const isLoading: any = ref(false)
const isPlaceLoad: any = ref(false)
const isSubmitting: any = ref(false)
const modalInput: any = ref(false)
const isEditMode: any = ref(false)
const editingId: any = ref(null)
const dataSource: any = ref([])
const dataSourcePublik: any = ref([])
const dataSourceIht: any = ref([])
const dataSourceEksternal: any = ref([])
const dataSourceListPelatihan: any = ref([])
const dataApproval: any = ref([])
const pageMaster: any = reactive({ page: 1, limit: 3 })
const totalMaster: any = ref(0)
const pageApproval: any = reactive({ page: 1, limit: 3 })
const totalApproval: any = ref(0)
const item: any = ref({ search: '' })
const confirm = useConfirm()
const d_jenisPelatihan = ref([])
const d_pegawai = ref([])

const kelompokUser = String(userLogin?.kelompokUser?.kelompokUser ?? '').toLowerCase().trim()
const lokasiKalibrasiFk = Number(userLogin?.kelompokUser?.lokasiKalibrasiFk ?? 0)

const canManagePelatihan = () => {
  return kelompokUser === 'mutu' && lokasiKalibrasiFk === 1
}

const form: any = ref({
  judul: '',
  pendaftaran: { start: new Date(), end: new Date() },
  tglPelatihan: new Date(),
  deskripsi: '',
  jenispelatihan: '',
})

const validError: any = ref('')
const approvingAct: any = ref('')

const modalManualRiwayat = ref(false)
const manualSubmitting = ref(false)
const manualError = ref('')
const manualForm: any = ref({
  pegawai: null,
  judul: '',
  pendaftaran: { start: new Date(), end: new Date() },
  tglPelatihan: new Date(),
  tglInputRiwayat: new Date(),
  deskripsi: '',
  keteranganstatus: '',
  status: 'Disetujui',
  jenisLabel: 'PUBLIK',
  jenispelatihan: null,
  lembaga: '',
})

const statusColor = (status: string) => {
  if (!status) return 'warning'
  const s = (status || '').toLowerCase()
  if (s.includes('setuju')) return 'success'
  if (s.includes('tolak')) return 'danger'
  return 'warning'
}

const statusTag = (p: any): { text: string; color: 'info' | 'danger' | 'success'; daysLeft: number } => {
  const now = moment()
  const start = moment(p.periodependaftaranawal)
  const end = moment(p.periodependaftaranakhir).endOf('day')
  if (now.isBefore(start)) return { text: 'Belum Dibuka', color: 'info', daysLeft: start.diff(now, 'days') }
  if (now.isAfter(end)) return { text: 'Ditutup', color: 'danger', daysLeft: 0 }
  return { text: 'Dibuka', color: 'success', daysLeft: end.diff(now, 'days') }
}

const getJenisPelatihanRow = (row: any) => {
  const raw = row?.jenispelatihan ?? row?.jenis_pelatihan ?? row?.jenisPelatihan ?? ''
  return (raw || '').toString().trim().toUpperCase()
}

const fetchJenisPelatihan = async (filter: any) => {
  const q = filter?.query || ''
  await useApi().get(
    `general/dropdown/jenispelatihan_m?select=id,jenispelatihan&param_search=jenispelatihan&query=${encodeURIComponent(q)}&limit=10`
  ).then((response) => {
    d_jenisPelatihan.value = response
  })
}

const fetchPegawai = async (filter: any) => {
  const q = filter?.query || ''
  await useApi().get(
    `general/dropdown/pegawai_m?select=id,namalengkap&param_search=namalengkap&query=${encodeURIComponent(q)}&limit=20`
  ).then((response) => {
    d_pegawai.value = response
  })
}

const fetchData = async () => {
  isPlaceLoad.value = true
  try {
    const res = await useApi().get(`mutu/list-pelatihan?page=${pageMaster.page}&limit=${pageMaster.limit}`)
    const list = res?.data ?? []
    dataSourceListPelatihan.value = list
    totalMaster.value = res?.length ?? res?.total ?? list.length
  } catch (e) {
    dataSourceListPelatihan.value = []
    totalMaster.value = 0
  } finally {
    isPlaceLoad.value = false
  }
}

const fetchDataRiwayat = async () => {
  isPlaceLoad.value = true
  const search = item.value.search ? `&search=${encodeURIComponent(item.value.search)}` : ''
  const id = `id=${userLogin.pegawai?.id || ''}`
  try {
    const res = await useApi().get(`mutu/list-pengajuan-pelatihan?${id}${search}`)
    res.forEach((e: any, i: number) => {
      e.no = i + 1
      e.color = e.color || statusColor(e.status)
    })
    dataSource.value = res
    dataSourcePublik.value = (res || []).filter((r: any) => getJenisPelatihanRow(r) === 'PUBLIK')
    dataSourceIht.value = (res || []).filter((r: any) => getJenisPelatihanRow(r) === 'IHT')
    dataSourceEksternal.value = (res || []).filter((r: any) => getJenisPelatihanRow(r) === 'EKSTERNAL')
  } catch (e) {
    dataSource.value = []
    dataSourcePublik.value = []
    dataSourceIht.value = []
    dataSourceEksternal.value = []
  } finally {
    isPlaceLoad.value = false
  }
}

const submitPelatihan = async () => {
  if (!canManagePelatihan()) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat menambah atau mengubah pelatihan')
    return
  }

  validError.value = ''
  if (!form.value.judul) { validError.value = 'Judul wajib diisi.'; return }
  if (!form.value.pendaftaran?.start || !form.value.pendaftaran?.end) { validError.value = 'Periode pendaftaran belum dipilih.'; return }
  if (!form.value.tglPelatihan) { validError.value = 'Tanggal pelatihan belum dipilih.'; return }
  if (!form.value.jenispelatihan?.value) { validError.value = 'Jenis pelatihan wajib dipilih.'; return }

  isSubmitting.value = true
  const payload: any = {
    judul: form.value.judul,
    pendaftaranMulai: moment(form.value.pendaftaran.start).format('YYYY-MM-DD'),
    pendaftaranSelesai: moment(form.value.pendaftaran.end).format('YYYY-MM-DD'),
    tglPelatihan: moment(form.value.tglPelatihan).format('YYYY-MM-DD'),
    deskripsi: form.value.deskripsi,
    jenispelatihanfk: form.value.jenispelatihan?.value || null,
    pengajuId: userLogin.pegawai?.id || null,
    status: 'Diajukan',
  }
  if (isEditMode.value && editingId.value) payload.id = editingId.value

  try {
    await useApi().post('mutu/save-master-pelatihan', payload)
    closeModal()
    await fetchData()
  } catch (e) {
    validError.value = 'Simpan gagal. Coba lagi.'
  } finally {
    isSubmitting.value = false
  }
}

const startEditPelatihan = (p: any) => {
  if (!canManagePelatihan()) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat mengubah pelatihan')
    return
  }

  validError.value = ''
  isEditMode.value = true
  editingId.value = p.id
  form.value = {
    judul: p.judulpelatihan || '',
    pendaftaran: {
      start: new Date(p.periodependaftaranawal),
      end: new Date(p.periodependaftaranakhir),
    },
    tglPelatihan: new Date(p.tglpelatihan),
    deskripsi: p.deskripsi || '',
    jenispelatihan: {
      label: p.jenispelatihan,
      value: p.idjenispelatihan
    }
  }
  modalInput.value = true
}

const dialogConfirm = (p: any) => {
  if (!canManagePelatihan()) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat menghapus pelatihan')
    return
  }

  confirm.require({
    message: 'Apakah anda serius menghapus data ini ?',
    header: 'Konfirmasi Hapus Data',
    icon: 'pi pi-info-circle',
    acceptClass: 'p-button-danger',
    accept: () => { deletePelatihan(p) },
    reject: () => { },
  })
}

const deletePelatihan = async (p: any) => {
  if (!canManagePelatihan()) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat menghapus pelatihan')
    return
  }

  try {
    await useApi().post('mutu/hapus-master-pelatihan', { id: p.id })
    await fetchData()
  } catch (e) { }
}

const deletePengajuanPelatihan = async (p: any) => {
  try {
    await useApi().post('mutu/hapus-pengajuan-pelatihan', { norec: p.norec })
    await fetchDataRiwayat()
    await fetchData()
  } catch (e) { }
}

const modalApproval = ref(false)
const approvalTargetRow: any = ref(null)
const approvalStatusBaru = ref<'Disetujui' | 'Ditolak' | ''>('')
const approvalNote = ref('')
const approvalError = ref('')
const approvalSubmitting = ref(false)

const confirmApproval = (row: any, statusBaru: 'Disetujui' | 'Ditolak') => {
  const isApprove = statusBaru === 'Disetujui'
  confirm.require({
    message: `Yakin ingin ${isApprove ? 'MENYETUJUI' : 'MENOLAK'} pengajuan ini?`,
    header: isApprove ? 'Konfirmasi Persetujuan' : 'Konfirmasi Penolakan',
    icon: 'pi pi-question-circle',
    acceptLabel: isApprove ? 'Setujui' : 'Tolak',
    rejectLabel: 'Batal',
    acceptClass: isApprove ? 'p-button-success' : 'p-button-danger',
    rejectClass: 'p-button-text',
    accept: () => openApprovalModal(row, statusBaru),
    reject: () => { },
  })
}

const openApprovalModal = (row: any, statusBaru: 'Disetujui' | 'Ditolak') => {
  approvalTargetRow.value = row
  approvalStatusBaru.value = statusBaru
  approvalNote.value = ''
  approvalError.value = ''
  modalApproval.value = true
}

const closeApprovalModal = () => {
  modalApproval.value = false
  approvalTargetRow.value = null
  approvalStatusBaru.value = ''
  approvalNote.value = ''
  approvalError.value = ''
}

const submitApprovalWithNote = async () => {
  approvalError.value = ''
  if (!approvalNote.value?.trim()) {
    approvalError.value = 'Keterangan wajib diisi.'
    return
  }

  if (!approvalTargetRow.value || !approvalStatusBaru.value) return

  approvalSubmitting.value = true
  try {
    await useApi().post('mutu/approval-pengajuan-pelatihan', {
      norec: approvalTargetRow.value.norec,
      status: approvalStatusBaru.value,
      keteranganstatus: approvalNote.value || null,
    })
    closeApprovalModal()
    await fetchDataRiwayat()
  } catch (e) {
    approvalError.value = 'Gagal menyimpan approval. Coba lagi.'
  } finally {
    approvalSubmitting.value = false
  }
}

const handleApproval = async (r: any, statusBaru: 'Disetujui' | 'Ditolak') => {
  await useApi().post('mutu/approval-pengajuan-pelatihan', { norec: r.norec, status: statusBaru })
  await fetchDataRiwayat()
}

const openManualModal = async (jenisLabel: 'PUBLIK' | 'IHT' | 'EKSTERNAL') => {
  manualError.value = ''
  manualForm.value = {
    pegawai: null,
    judul: '',
    pendaftaran: { start: new Date(), end: new Date() },
    tglPelatihan: new Date(),
    tglInputRiwayat: new Date(),
    deskripsi: '',
    keteranganstatus: '',
    status: 'Disetujui',
    jenisLabel,
    jenispelatihan: null,
    lembaga: '',
  }

  await fetchJenisPelatihan({ query: jenisLabel })
  const selectedJenis = (d_jenisPelatihan.value || []).find((e: any) =>
    (e?.label || '').toString().trim().toUpperCase() === jenisLabel
  )
  if (selectedJenis) {
    manualForm.value.jenispelatihan = selectedJenis
  } else {
    manualForm.value.jenispelatihan = { label: jenisLabel, value: null }
  }

  modalManualRiwayat.value = true
}

const closeManualModal = () => {
  modalManualRiwayat.value = false
}

const submitManualRiwayat = async () => {
  manualError.value = ''

  if (!manualForm.value.pegawai?.value) {
    manualError.value = 'Nama pegawai wajib dipilih.'
    return
  }
  if (!manualForm.value.judul) {
    manualError.value = 'Judul pelatihan wajib diisi.'
    return
  }
  if (!manualForm.value.pendaftaran?.start || !manualForm.value.pendaftaran?.end) {
    manualError.value = 'Periode pendaftaran wajib dipilih.'
    return
  }
  if (!manualForm.value.tglPelatihan) {
    manualError.value = 'Tanggal pelatihan wajib diisi.'
    return
  }
  if (!manualForm.value.jenispelatihan?.value) {
    manualError.value = 'Jenis pelatihan wajib dipilih.'
    return
  }

  const jenisManual = (manualForm.value.jenispelatihan?.label || '').toString().trim().toUpperCase()
  if (jenisManual === 'EKSTERNAL' && !manualForm.value.lembaga) {
    manualError.value = 'Lembaga wajib diisi untuk pelatihan eksternal.'
    return
  }

  manualSubmitting.value = true
  try {
    await useApi().post('mutu/save-riwayat-pelatihan-manual', {
      pegawaifk: manualForm.value.pegawai?.value,
      judul: manualForm.value.judul,
      pendaftaranMulai: moment(manualForm.value.pendaftaran.start).format('YYYY-MM-DD'),
      pendaftaranSelesai: moment(manualForm.value.pendaftaran.end).format('YYYY-MM-DD'),
      tglPelatihan: moment(manualForm.value.tglPelatihan).format('YYYY-MM-DD'),
      tglInputRiwayat: manualForm.value.tglInputRiwayat
        ? moment(manualForm.value.tglInputRiwayat).format('YYYY-MM-DD HH:mm:ss')
        : moment().format('YYYY-MM-DD HH:mm:ss'),
      deskripsi: manualForm.value.deskripsi,
      jenispelatihanfk: manualForm.value.jenispelatihan?.value,
      lembaga: manualForm.value.lembaga || null,
      status: manualForm.value.status,
      keteranganstatus: manualForm.value.keteranganstatus,
      ismanual: true,
    })
    closeManualModal()
    await fetchDataRiwayat()
    await fetchData()
  } catch (e: any) {
    manualError.value = e?.response?._data?.message || 'Simpan riwayat manual gagal. Coba lagi.'
  } finally {
    manualSubmitting.value = false
  }
}

const remakeData: any = ref([])
const exportExcel = (rows: any[] = [], filename = 'riwayat-pelatihan.xlsx') => {
  remakeData.value = (rows || []).map((e: any) => ({
    No: e.no,
    Jenis: (e.jenispelatihan ?? e.jenis_pelatihan ?? e.jenisPelatihan ?? ''),
    Lembaga: e.lembaga ?? '',
    NamaPegawai: e.namalengkap ?? '',
    Judul: e.judulpelatihan ?? '',
    PendaftaranMulai: e.periodependaftaranawal ?? '',
    PendaftaranSelesai: e.periodependaftaranakhir ?? '',
    TglPelatihan: e.tglpelatihan ?? '',
    Status: e.status ?? '',
    KeteranganStatus: e.keteranganstatus ?? '',
    Dibuat: e.created_at ?? '',
  }))
  const ws = XLSX.utils.json_to_sheet(remakeData.value)
  const wb = { Sheets: { data: ws }, SheetNames: ['data'] }
  const buff: any = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })
  const data = new Blob([buff], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8' })
  const url = window.URL.createObjectURL(data)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
  window.URL.revokeObjectURL(url)
}

watch(() => [pageMaster.page, pageMaster.limit], () => fetchData(), { immediate: true })
fetchDataRiwayat()

const openModal = () => {
  if (!canManagePelatihan()) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat menambah pelatihan')
    return
  }

  validError.value = ''
  isEditMode.value = false
  editingId.value = null
  form.value = {
    judul: '',
    pendaftaran: { start: new Date(), end: new Date() },
    tglPelatihan: new Date(),
    deskripsi: '',
    jenispelatihan: '',
  }
  modalInput.value = true
}

const closeModal = () => {
  modalInput.value = false
}
</script>

<style lang="scss">
.input-calendar {
  min-width: 140px;
}

.modal-card-title {
  font-weight: 600;
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

.kp-hero-right .button {
  border-radius: 9999px;
  padding: 0 18px;
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

  .kp-hero-right {
    width: 100%;
  }

  .kp-hero-right .button {
    width: 100%;
  }
}
</style>