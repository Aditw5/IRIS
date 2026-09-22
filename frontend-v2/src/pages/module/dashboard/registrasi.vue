<template>
  <div class="columns is-multiline">
    <div class="column is-12">
      <div class="columns is-multiline">
        <div class="column is-12">
          <div class="illustration-header-2 large-screen">
            <div class="header-image">
              <img src="/@src/assets/illustrations/dashboards/lifestyle/Picture1.png" alt=""
                style="max-width:84%; margin-left: 2rem; margin-bottom: 1rem;" />
            </div>
            <div class="header-meta">
              <h3 style="color:white"><i class="fas fa-id-card" aria-hidden="true"></i> Dashboard
                Registrasi</h3>
              <p>
                Selamat Datang , {{ userLogin.pegawai.namaLengkap }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div>
    <div class="business-dashboard hr-dashboard">
      <div class="columns">
        <div class="column is-12">
          <VCard radius="rounded">
            <div class="columns is-multiline">
              <div class="column is-4 mt-5">
                <VDatePicker v-model="item.filterTgl" is-range color="pink" trim-weeks>
                  <template #default="{ inputValue, inputEvents }">
                    <VField addons>
                      <VControl icon="feather:calendar">
                        <VInput :value="inputValue.start" v-on="inputEvents.start" />
                      </VControl>
                      <VControl>
                        <VButton static><i class="fas fa-arrow-right" aria-hidden="true"></i></VButton>
                      </VControl>
                      <VControl icon="feather:calendar">
                        <VInput :value="inputValue.end" v-on="inputEvents.end" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </div>
              <div class="column is-8 mt-5">
                <VField>
                  <RouterLink :to="{ name: 'module-registrasi-mitra-lama', }">
                    <VIconButton class="ml-1 is-pulled-right" type="button" color="info" rounded circle raised
                      icon="fas fa-users" v-tooltip.bubble="'Unit Lama'">
                    </VIconButton>
                  </RouterLink>
                  <VButton class="ml-1 is-pulled-right" type="button" color="info" rounded raised
                    icon="fas fa-long-arrow-alt-right" @click="mitraBaru()">
                    Unit Baru
                  </VButton>
                  <RouterLink :to="{ name: 'module-registrasi-rekalibrasi-standar', }">
                    <VButton class="ml-1 is-pulled-right" type="button" color="warning" rounded raised
                      icon="fas fa-tools">
                      Rekalibrasi Standar Internal
                    </VButton>
                  </RouterLink>
                  <RouterLink :to="{ name: 'module-registrasi-kalibrasi-internal', }">
                    <VButton class="is-pulled-right" type="button" color="success" rounded raised icon="fas fa-tools">
                      Kalibrasi Internal
                    </VButton>
                  </RouterLink>
                </VField>
              </div>
            </div>
            <div class="columns is-multiline">
              <div class="column is-3" v-if="activeTab == 0">
                <VField label="Cari Registrasi Unit">
                  <VControl icon="fas fa-id-card" fullwidth>
                    <VInput type="text" placeholder="Nama Perusahaan, No Pendaftaran" autocomplete="off"
                      v-model="item.search" class="is-rounded" v-on:keyup.enter="cari()" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-3" v-if="activeTab == 1">
                <VField label="Cari Registrasi Alat">
                  <VControl icon="fas fa-id-card" fullwidth>
                    <VInput type="text"
                      placeholder="Nama alat, Merk/Tipe, Serialnumber, No Orderalat, Unit, No Pendaftaran"
                      autocomplete="off" v-model="item.qsearch" class="is-rounded"
                      v-on:keyup.enter="fetchAlatKalibrasi(orderAlat)" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-2">
                <VField label="Lokasi">
                  <VControl>
                    <div class="select is-fullwidth is-rounded">
                      <select v-model="item.lokasiDashboard">
                        <option v-for="option in lokasiDashboardOptions" :key="option.value || 'all'"
                          :value="option.value">
                          {{ option.label }}
                        </option>
                      </select>
                    </div>
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
              <div class="column is-3" v-if="activeTab == 0">
                <VField class="is-autocomplete-select" label="Jenis Order">
                  <VControl icon="feather:search">
                    <Multiselect v-model="item.jenisorder" :attrs="{ id }" :options="optionsKelompokLayanan"
                      placeholder="Pilih Jenis Order" :searchable="true" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-3" v-if="activeTab == 1">
                <VField class="is-autocomplete-select" label="Ruang Lingkup">
                  <VControl icon="feather:search">
                    <AutoComplete v-model="item.ruanglingkupfk" :suggestions="d_lingkup"
                      @complete="fetchLingkup($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                      :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                      placeholder="ketik Ruang Lingkup" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-1 mt-5" v-if="activeTab == 0">
                <VIconButton type="button" color="success" class="is-rounded" rounded raised icon="fas fa-search"
                  @click="cari()" :loading="isLoading">
                </VIconButton>
              </div>
              <div class="column is-1 mt-5" v-if="activeTab == 1">
                <VIconButton type="button" color="success" class="is-rounded" rounded raised icon="fas fa-search"
                  @click="fetchAlatKalibrasi(orderAlat)" :loading="isLoading">
                </VIconButton>
              </div>
            </div>
          </VCard>
          <div class="column is-12" style="margin-top: 2rem;">
            <div class="columns is-multiline registrasi-content-layout">
              <div class="column is-12-tablet is-8-widescreen is-9-fullhd">
                <VCard class="registrasi-main-card">
                <TabView class="tabview-custom " :scrollable="true" v-model:activeIndex="activeTab"
                  @tab-click="klikTab($event)">
                  <TabPanel>
                    <template #header>
                      <i class="fas fa-users mr-2" aria-hidden="true"></i>
                      <span>Pendaftaran Unit</span>
                      <Badge :value="ds_MITRA.length" v-if="ds_MITRA.length > 0" severity="danger" class="ml-2" />
                    </template>
                    <div v-if="activeTab == 0">
                      <div class="user-grid user-grid-v2">
                        <VPlaceholderPage :title="H.assets().notFound" :subtitle="H.assets().notFoundSubtitle"
                          class="my-6" v-if="ds_MITRA.length === 0">
                          <template #image>
                            <img class="light-image" :src="H.assets().iconNotFound_rev" alt="" />
                            <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg"
                              alt="" />
                          </template>
                        </VPlaceholderPage>
                        <TransitionGroup name="list" tag="div" class="columns is-multiline" v-else>
                          <div v-for="(item, key) in ds_MITRA" :key="key"
                            class="column is-12-mobile is-6-tablet is-6-desktop is-4-fullhd">
                            <div class="grid-item-wrap is-clickable">
                              <!-- @click="clickCard(item)" -->
                              <div class="grid-item-head is-registrasi">
                                <div class="flex-head">

                                  <div class="meta">
                                    <span>
                                      {{
                                        H.formatDateIndoSimple(item.tglregistrasi)
                                      }}
                                    </span>
                                  </div>

                                </div>
                              </div>
                              <div class="flex-head" style=" display: flex; justify-content: space-between;">
                                <VDropdown icon="feather:more-vertical" spaced left>
                                  <template #content>
                                    <a role="menuitem" @click="batalRegis(item)" class="dropdown-item is-media">
                                      <div class="icon">
                                        <i aria-hidden="true" class="lnil lnil-trash"></i>
                                      </div>
                                      <div class="meta">
                                        <span>Batal Registrasi</span>
                                      </div>
                                    </a>
                                    <a style="background-color:darkcyan;"
                                      v-if="item.verifregiscustomer == null && item.isregiscustomer && !item.isstandarulab && !item.iskalibrasiinternal"
                                      role="menuitem" @click="verifikasiPendaftaranCustomer(item)"
                                      class="dropdown-item is-media">
                                      <div class="icon">
                                        <i aria-hidden="true" class="lnil lnil-checkmark"></i>
                                      </div>
                                      <div class="meta">
                                        <span>Verifikasi Registrasi Customer </span>
                                      </div>
                                    </a>
                                    <a s v-if="item.iskaji !== null && !item.isstandarulab && !item.iskalibrasiinternal"
                                      role="menuitem" @click="cetakTandaTerima(item)" class="dropdown-item is-media">
                                      <div class="icon">
                                        <i aria-hidden="true" class="lnil lnil-printer"></i>
                                      </div>
                                      <div class="meta">
                                        <span>Cetak Tanda Terima </span>
                                      </div>
                                    </a>
                                    <a v-if="item.iskaji !== null && item.statusorder == 1 && !item.isstandarulab && !item.iskalibrasiinternal"
                                      role="menuitem" @click="cetakPermintaanKalibrasi(item)"
                                      class="dropdown-item is-media">
                                      <div class="icon">
                                        <i aria-hidden="true" class="lnil lnil-printer"></i>
                                      </div>
                                      <div class="meta">
                                        <span>Cetak Permintaan Kalibrasi dan Kontrak </span>
                                      </div>
                                    </a>
                                    <a v-if="item.iskaji !== null" role="menuitem"
                                      @click="cetakBarcodeOrder(item)" class="dropdown-item is-media">
                                      <div class="icon">
                                        <i aria-hidden="true" class="lnil lnil-printer"></i>
                                      </div>
                                      <div class="meta">
                                        <span>Cetak Label Order </span>
                                      </div>
                                    </a>
                                    <a v-if="item.statusorder == 1 && !item.isstandarulab && !item.iskalibrasiinternal"
                                      role="menuitem" @click="cetakAmsDashboard(item)" class="dropdown-item is-media">
                                      <div class="icon">
                                        <i aria-hidden="true" class="lnil lnil-printer"></i>
                                      </div>
                                      <div class="meta">
                                        <span>Cetak AMS </span>
                                      </div>
                                    </a>
                                    <a v-if="item.statusorder == 1 && !item.isstandarulab && !item.iskalibrasiinternal"
                                      role="menuitem" @click="uploadGantiAMS(item)" class="dropdown-item is-media">
                                      <div class="icon">
                                        <i aria-hidden="true" class="lnil lnil-upload"></i>
                                      </div>
                                      <div class="meta">
                                        <span>Ganti AMS </span>
                                      </div>
                                    </a>
                                    <a v-if="item.iskaji !== null && item.statusorder == 1 && !item.isstandarulab && !item.iskalibrasiinternal"
                                      role="menuitem" @click="openSelesaiTerima(item)" class="dropdown-item is-media">
                                      <div class="icon">
                                        <i aria-hidden="true" class="lnil lnil-printer"></i>
                                      </div>
                                      <div class="meta">
                                        <span>Cetak Tanda Terima Selesai </span>
                                      </div>
                                    </a>
                                    <a v-if="item.jumlahselesai === item.jumlahdetail && !item.isstandarulab && !item.iskalibrasiinternal"
                                      role="menuitem" @click="isiSurvey(item)" class="dropdown-item is-media">
                                      <div class="icon">
                                        <i aria-hidden="true" class="lnil lnil-happy-2"></i>
                                      </div>
                                      <div class="meta">
                                        <span>Isi Survey Pelanggan</span>
                                      </div>
                                    </a>

                                  </template>
                                </VDropdown>
                                <div class="is-flex is-justify-content-flex-end is-flex-wrap gap-2">
                                  <VTag v-if="item.verifregiscustomer == null && item.isregiscustomer" color="danger"
                                    rounded>
                                    Belum Admin Verif</VTag>
                                  <VTag v-if="item.isregiscustomer" color="info" rounded>Registrasi Customer</VTag>
                                  <VTag v-if="!item.iskaji" color="danger" rounded>Belum Kaji</VTag>
                                  <VTag v-if="item.iskaji && item.statusorder != 1" color="warning" rounded>Sudah Kaji
                                  </VTag>
                                  <VTag v-if="item.statusorder == 1" color="info" rounded>Sudah Diverif Asman</VTag>
                                </div>
                              </div>
                              <div class="grid-item">
                                <VAvatar :picture="(item.foto != null ? item.foto : '/images/other/no_image.jpg')"
                                  size="big" />
                                <h3 class="dark-inverted">{{ item.namaperusahaan }}</h3>
                                <VTag v-if="item.jenisorder == 'repair'" color="warning" rounded>Repair</VTag>
                                <VTag v-if="item.jenisorder == 'kalibrasi'" color="info" rounded>Kalibrasi</VTag>
                                <h3 class="dark-inverted">{{ item.nopendaftaran ?? '-' }}</h3>
                                <!-- <p>{{ item.nocm }}</p> -->
                                <p v-if="!item.isstandarulab && !item.iskalibrasiinternal">Email : {{ item.email }}</p>
                                <p v-if="!item.isstandarulab && !item.iskalibrasiinternal">No HP : {{ item.nohp }}</p>
                                <div class="order-progress-info" style="text-align:center; margin-bottom: 0.3rem;">
                                  <template v-if="item.jumlahdetail && item.jumlahselesai != null">
                                    <span style="font-weight: 600; font-size: 1.03em;">
                                      Selesai {{ item.jumlahselesai }}/{{ item.jumlahdetail }}
                                    </span>
                                    <div class="progress-bar-wrap"
                                      style="background:#e9ecef; border-radius:8px; width:85%; height:9px; margin: 6px auto 0;">
                                      <div :style="{
                                        width: ((item.jumlahselesai / item.jumlahdetail) * 100) + '%',
                                        background: '#53dd6c',
                                        height: '100%',
                                        borderRadius: '8px',
                                        transition: 'width 0.5s'
                                      }"></div>
                                    </div>
                                  </template>
                                </div>
                                <div v-if="!item.isstandarulab && !item.iskalibrasiinternal" class="order-progress-info"
                                  style="text-align:center; margin-bottom: 0.3rem;">
                                  <template v-if="item.jumlahdetail && item.jumlahselesai != null">
                                    <span style="font-weight: 600; font-size: 1.03em;">
                                      Diambil {{ item.jumlahditerima }}/{{ item.jumlahdetail }}
                                    </span>
                                    <div class="progress-bar-wrap"
                                      style="background:#e9ecef; border-radius:8px; width:85%; height:9px; margin: 6px auto 0;">
                                      <div :style="{
                                        width: ((item.jumlahditerima / item.jumlahdetail) * 100) + '%',
                                        background: '#1478e3',
                                        height: '100%',
                                        borderRadius: '8px',
                                        transition: 'width 0.5s'
                                      }"></div>
                                    </div>
                                  </template>
                                </div>
                                <div class="buttons mt-4" style="display: flex; justify-content: center;">
                                  <VIconButton
                                    v-if="!item.isstandarulab && !item.iskalibrasiinternal && item.statusorder != 1 && item.jenisorder == 'kalibrasi' && (!item.isregiscustomer || (item.isregiscustomer && item.verifregiscustomer !== null))"
                                    v-tooltip.bottom.left="'Kaji Ulang'" label="Bottom center" color="info" outlined
                                    circle icon="pi pi-arrow-right" @click="kajiUlang(item)" />
                                  <VIconButton
                                    v-if="!item.isstandarulab && !item.iskalibrasiinternal && item.statusorder != 1 && item.jenisorder == 'repair' && (!item.isregiscustomer || (item.isregiscustomer && item.verifregiscustomer !== null))"
                                    v-tooltip.bottom.left="'Kaji Ulang Repair'" label="Bottom center" color="info"
                                    outlined circle icon="pi pi-arrow-right" @click="kajiUlangRepair(item)" />
                                  <VIconButton
                                    v-if="!item.isstandarulab && !item.iskalibrasiinternal && item.statusorder == 1 || (item.verifregiscustomer == null && item.isregiscustomer)"
                                    v-tooltip.bottom.left="'Kaji Ulang'" label="Bottom center" color="info" outlined
                                    circle icon="pi pi-arrow-right" disabled />
                                  <VTag v-if="item.isstandarulab" color="warning" rounded>Rekalbirasi Standar Internal
                                  </VTag>
                                  <VTag v-if="item.iskalibrasiinternal" color="warning" rounded>Kalibrasi Internal
                                  </VTag>
                                </div>
                              </div>
                            </div>
                          </div>
                        </TransitionGroup>
                      </div>
                    </div>
                    <VFlexPagination v-model:current-page="currentPage.page" :item-per-page="currentPage.limit"
                      :total-items="ds_MITRA.total" :max-links-displayed="5">
                      <template #before-pagination>
                      </template>
                      <template #before-navigation>
                        <VFlex class="mr-4 mt-1" column-gap="1rem">
                          <VField>

                          </VField>
                          <VField>
                            <VControl>
                              <div class="select is-rounded">
                                <select v-model="currentPage.limit">
                                  <option :value="1">1 results per page</option>
                                  <option :value="5">5 results per page</option>
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
                  </TabPanel>
                  <TabPanel>
                    <template #header>
                      <i class="fas fa-tools mr-2" aria-hidden="true"></i>
                      <span>Daftar Alat</span>
                      <Badge :value="dataAlatKalibrasi.length" v-if="dataAlatKalibrasi.length > 0" severity="danger"
                        class="ml-2" />
                    </template>
                    <div v-if="activeTab == 1">
                      <div class="list-view list-view-v3">
                        <VCard class="alat-filter-card pt-0 pb-0 mt-0">
                          <div class="alat-filter-inner">
                            <div class="alat-status-filter">
                              <VRadio v-model="orderAlat" value="0" label="Belum Selesai" name="outlined_radio"
                                color="success" />
                              <VRadio v-model="orderAlat" value="2" label="Sudah Selesai" name="outlined_radio"
                                color="info" />
                              <VRadio v-model="orderAlat" value="3" label="Ditolak/Batal Order" name="outlined_radio"
                                color="info" />
                            </div>
                            <div class="alat-activity-filter">
                              <VField class="mb-0">
                                <VControl>
                                  <div class="select is-rounded">
                                    <select v-model="item.lastActivityAlat">
                                      <option v-for="opt in optionsAktivitasAlat" :key="opt.value"
                                        :value="opt.value">
                                        {{ opt.label }}
                                      </option>
                                    </select>
                                  </div>
                                </VControl>
                              </VField>
                            </div>
                            <div class="alat-sort-filter">
                              <VField class="mb-0">
                                <VControl>
                                  <div class="select is-rounded">
                                    <select v-model="item.sortAlat">
                                      <option value="tgl_desc">Tanggal Registrasi Terbaru</option>
                                      <option value="tgl_asc">Tanggal Registrasi Terlama</option>
                                    </select>
                                  </div>
                                </VControl>
                              </VField>
                            </div>
                          </div>
                        </VCard>
                        <VPlaceholderPage :class="[dataAlatKalibrasi.length !== 0 && 'is-hidden']"
                          title="Tidak Ada Alat Hari Ini." subtitle="Silakan Pilih Tanggal" larger>
                          <template #image>
                            <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.png"
                              alt="" />
                            <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg"
                              alt="" />
                          </template>
                        </VPlaceholderPage>
                        <div class="list-view-inner mt-2" style="max-height:1000px;overflow: auto;">
                          <div name="list-complete" tag="div">
                            <div v-for="(item, rowIndex) in dataAlatKalibrasi" :key="rowIndex">
                              <div v-if="rowGroupMetadata[item.lingkupkalibrasi].index === rowIndex">
                                <span style="font-weight: bold; font-size: 16px; font-family: var(--font-alt);">{{
                                  item.lingkupkalibrasi }}</span>
                                <Badge :value="rowGroupMetadata[item.lingkupkalibrasi].size"
                                  v-if="rowGroupMetadata[item.lingkupkalibrasi].size > 0" class="ml-2 mt-2-min" />
                              </div>
                              <div class="list-view-item ">
                                <div class="list-view-item-inner">
                                  <VAvatar size="small" :picture="getLingkupAvatar(item.lingkupkalibrasi).picture"
                                    :color="getLingkupAvatar(item.lingkupkalibrasi).color" bordered />
                                  <div class="meta-left">
                                    <h3>
                                      <b>{{ item.namaproduk }}</b>
                                      <VTag v-if="item.versisertifikat > 1 || item.versilaporanrepair > 1"
                                        :label="'Amandemen'" :color="'info'" class="ml-2" />
                                      <VTag v-if="item.iskaji != true" label="Belum Kaji Ulang" :color="'danger'"
                                        class="ml-2" />
                                    </h3>
                                    <h5 v-if="item.isVendor == true && item.namavendor">Vendor : {{ item.namavendor }}
                                    </h5>
                                    <h5>Merk/Tipe : {{ item.namamerk }}/{{ item.namatipe }}</h5>
                                    <h5>S/N : {{ item.namaserialnumber }}</h5>
                                    <span>
                                      <i aria-hidden="true" class="iconify" data-icon="feather:home"></i>
                                      <span>{{ item.namaperusahaan }}</span>
                                      <i aria-hidden="true" class="fas fa-circle icon-separator"></i>
                                      <i aria-hidden="true" class="iconify" data-icon="feather:calendar"></i>
                                      <span>{{ item.tglverifasman }}</span>
                                      <i aria-hidden="true" class="fas fa-circle icon-separator"></i>
                                      <i aria-hidden="true" class="iconify" data-icon="feather:check-circle"></i>
                                      <span>{{ item.nopendaftaran }}</span>
                                      <i aria-hidden="true" class="fas fa-circle icon-separator"></i>
                                      <i aria-hidden="true" class="iconify" data-icon="feather:check-circle"></i>
                                      <span>{{ item.noorderalat }}</span>
                                      <i v-if="item.nosertifikatamandemen == null" aria-hidden="true" class="fas fa-circle icon-separator"></i>
                                      <i v-if="item.nosertifikatamandemen == null" aria-hidden="true" class="iconify" data-icon="feather:check-circle"></i>
                                      <span v-if="item.nosertifikatamandemen == null" >{{ item.nosertifikat }}</span>
                                      <i v-if="item.nosertifikatamandemen != null" aria-hidden="true" class="fas fa-circle icon-separator"></i>
                                      <i v-if="item.nosertifikatamandemen != null" aria-hidden="true" class="iconify" data-icon="feather:check-circle"></i>
                                      <span v-if="item.nosertifikatamandemen != null">{{ item.nosertifikatamandemen }}</span>

                                    </span>
                                    <div>
                                      <VTag :label="item.jenissurkesfk == 1 ? 'SURKES' : 'NON SURKES'"
                                        :color="item.jenissurkesfk == 1 ? 'success' : 'danger'" class="ml-2" />
                                      <VTag v-if="item.isstandarulab" :label="'Rekalibrasi Standar Internal'"
                                        :color="'warning'" class="ml-2" />
                                      <VTag v-if="item.iskalibrasiinternal" :label="'Kalibrasi Internal'"
                                        :color="'warning'" class="ml-2" />
                                      <VTag
                                        v-if="!item.isstandarulab && !item.iskalibrasiinternal && item.statusPengerjaan == null && item.iskaji == true && item.jenisorder == 'kalibrasi' && item.isVendor == null"
                                        :label="'Durasi Kalibrasi : ' + item.durasikalbrasi" :color="'warning'"
                                        class="ml-2" />
                                      <VTag v-if="item.durasi_proses != null" :label="item.durasi_proses" color="info"
                                        class="ml-2" />
                                      <VTag
                                        v-if="item.pelaksanaisilembarkerjafk != null && (item.setujuilembarkerjapenyelia == null || item.setujuilembarkerjapenyelia == false) && item.isverifikasi == null"
                                        :label="'Sudah Isi Lembar Kerja'" :color="'info'" class="ml-2" />
                                      <VTag
                                        v-if="item.pelaksanaisilembarkerjafk != null && (item.setujuilembarkerjapenyelia == null || item.setujuilembarkerjapenyelia == false) && item.isverifikasi == true"
                                        :label="'Sudah Isi Laporan Verifikasi'" :color="'success'" class="ml-2" />
                                      <VTag v-if="item.pelaksanaisilaporanrepairfk != null"
                                        :label="'Sudah Isi Laporan Repair'" :color="'success'" class="ml-2" />
                                      <VTag
                                        v-if="item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && item.isverifikasi == null"
                                        :label="'Sertifikat Disetujui Penyelia'" :color="'primary'" class="ml-2" />
                                      <VTag
                                        v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true && item.isverifikasi == null"
                                        :label="'Sertifikat Disetujui Asman'" :color="'primary'" class="ml-2" />
                                      <VTag
                                        v-if="(item.setujuilembarkerjamanager != null || item.setujuilembarkerjamanager == true) && item.isverifikasi == null"
                                        :label="'Sertifikat Disetujui Manager'" :color="'primary'" class="ml-2" />
                                      <VTag
                                        v-if="item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && item.isverifikasi == true"
                                        :label="'Laporan Verifikasi Disetujui Penyelia'" :color="'success'"
                                        class="ml-2" />
                                      <VTag
                                        v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true && item.isverifikasi == true"
                                        :label="'Laporan Verifikasi Disetujui Asman'" :color="'success'" class="ml-2" />
                                      <VTag
                                        v-if="(item.setujuilembarkerjamanager != null || item.setujuilembarkerjamanager == true) && item.isverifikasi == true"
                                        :label="'Laporan Verifikasi Disetujui Manager'" :color="'success'"
                                        class="ml-2" />
                                      <VTag v-if="item.penyeliasetujulaporanrepairfk != null"
                                        :label="'Laporan Repair Disetujui Penyelia'" :color="'info'" class="ml-2" />
                                      <VTag v-if="item.asmansetujulaporanrepairfk != null"
                                        :label="'Laporan Repair Disetujui Asman'" :color="'info'" class="ml-2" />
                                      <VTag v-if="item.managersetujulaporanrepairfk != null"
                                        :label="'Laporan Repair Disetujui Manager'" :color="'info'" class="ml-2" />
                                    </div>
                                    <div v-if="item.statusrepairfk == 2 && item.jenisorder === 'repair'"
                                      class="gagal-repair-anim">
                                      <svg viewBox="0 0 32 32" fill="none">
                                        <circle cx="16" cy="16" r="16" fill="#e57373" />
                                        <path d="M10 10L22 22M22 10L10 22" stroke="#fff" stroke-width="3.2"
                                          stroke-linecap="round" />
                                      </svg>
                                      GAGAL REPAIR
                                    </div>
                                    <div v-if="item.isVendor == null">
                                      <span style="font-weight: bold;">Penyelia Teknik
                                        :
                                        {{ item.penyeliateknik ?? '-' }}
                                      </span>
                                    </div>
                                    <div v-if="item.isVendor == null">
                                      <span style="font-weight: bold;">Pelaksana
                                        Teknik :
                                        {{ item.pelaksanateknik ?? '-' }}
                                      </span>
                                    </div>
                                    <div v-if="item.ketgagalkalibrasi != null">
                                      <span style="font-weight: bold; color: red;">Keterangan Batal/Ditolak :
                                        {{ item.ketgagalkalibrasi ?? '-' }}
                                      </span>
                                    </div>
                                  </div>
                                  <div class="meta-right flex justify-center items-center">
                                    <div class="buttons">
                                      <VIconButton v-if="item.isVendor == true"
                                        v-tooltip.bottom.left="'Upload Sertifikat Vendor'" icon="feather:upload"
                                        @click="uploadSertiVendor(item)" color="success" raised circle class="mr-2">
                                      </VIconButton>
                                      <VIconButton v-if="item.isVendor == true && item.fileSertiVendor != null"
                                        v-tooltip.bottom.left="'Cetak Sertifikat Vendor'" icon="feather:printer"
                                        @click="cetakSertiVendor(item)" color="warning" raised circle class="mr-2">
                                      </VIconButton>
                                      <VIconButton
                                        v-if="item.asmansetujulembarkerjafk != null && item.jenisorder == 'kalibrasi' && item.isverifikasi == true"
                                        v-tooltip.bottom.left="'Cetak Laporan Verifikasi'" icon="feather:printer"
                                        @click="cetakLaporanVerfikasi(item)" color="success" raised circle class="mr-2">
                                      </VIconButton>
                                      <VIconButton
                                        v-if="item.asmansetujulembarkerjafk != null && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                        v-tooltip.bottom.left="'Cetak Sertifikat'" icon="feather:printer"
                                        @click="cetakSertifikatLembarKerja(item)" color="info" raised circle
                                        class="mr-2">
                                      </VIconButton>
                                      <VIconButton
                                        v-if="item.managersetujulaporanrepairfk != null && item.jenisorder == 'repair'"
                                        v-tooltip.bottom.left="'Cetak Laporan Repair'" icon="feather:printer"
                                        @click="cetakLaporanRepair(item)" color="success" raised circle class="mr-2">
                                      </VIconButton>
                                      <VIconButton
                                        v-if="item.setujuilembarkerjamanager == null && item.ketgagalkalibrasi == null && item.jenisorder == 'kalibrasi'"
                                        v-tooltip.bottom.left="'Batal Kalibrasi'" icon="feather:trash"
                                        @click="hapusItems(item)" color="danger" raised circle class="mr-2">
                                      </VIconButton>
                                      <VIconButton
                                        v-if="item.lingkupkalibrasi !== 'vendor' && item.pelaksanaisilembarkerjafk == null && item.pelaksanaisilaporanrepairfk == null"
                                        v-tooltip.bottom.left="'Ubah Penyelia & Pelaksana'" icon="feather:edit-2"
                                        @click="ubahPelaksana(item)" color="warning" raised circle class="mr-2">
                                      </VIconButton>
                                      <VIconButton
                                        v-if="item.lingkupkalibrasi == 'vendor' && item.fileSertiVendor == null"
                                        v-tooltip.bottom.left="'Ubah Vendor'" icon="feather:edit-2"
                                        @click="ubahVendor(item)" color="info" raised circle class="mr-2">
                                      </VIconButton>
                                      <VIconButton v-tooltip.bottom.left="'Aktivitas'" icon="feather:activity"
                                        @click="detailOrder(item)" color="info" raised circle class="mr-2">
                                      </VIconButton>
                                      <VIconButton v-tooltip.bottom.left="'Riwayat Amandemen'" icon="feather:repeat"
                                        v-if="item.versisertifikat > 1 || item.versilaporanrepair > 1"
                                        @click="riwayatAmandemen(item)" color="warning" raised circle class="mr-2">
                                      </VIconButton>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </TabPanel>
                </TabView>
              </VCard>
            </div>
            <div class="column is-12-tablet is-4-widescreen is-3-fullhd">
              <aside class="registrasi-insight-panel">
                <div class="insight-head">
                  <div>
                    <span class="insight-kicker">Statistik</span>
                    <h3>{{ activeTab == 0 ? 'Pendaftaran Unit' : 'Daftar Alat' }}</h3>
                    <span class="insight-location">{{ dashboardLokasiLabel }}</span>
                  </div>
                  <span class="insight-period">{{ filterPeriodLabel }}</span>
                </div>

                <div v-if="activeTab == 0">
                  <div v-if="isLoadingStats" class="insight-loading">
                    <VPlaceload height="78px" class="mb-3" />
                    <VPlaceload height="260px" class="mb-3" />
                    <VPlaceload height="220px" />
                  </div>
                  <template v-else>
                    <div class="insight-summary-grid">
                      <div v-for="metric in registrasiSummaryCards" :key="metric.label" class="insight-stat-card"
                        :class="metric.className">
                        <div class="stat-icon">
                          <i :class="metric.icon" aria-hidden="true"></i>
                        </div>
                        <div>
                          <span>{{ metric.label }}</span>
                          <strong>{{ metric.value }}</strong>
                          <small>{{ metric.caption }}</small>
                        </div>
                      </div>
                    </div>

                    <div class="insight-chart-card" v-if="hasRegistrasiUnitChart">
                      <div class="chart-card-head">
                        <div>
                          <span>Top Unit</span>
                          <h4>Pendaftaran, selesai, diambil</h4>
                        </div>
                      </div>
                      <ApexChart type="bar" :height="registrasiUnitChartHeight" :options="registrasiUnitChartOptions"
                        :series="registrasiUnitChartSeries" />
                    </div>
                    <div v-else class="insight-empty">Belum ada data pendaftaran pada periode ini.</div>

                    <div class="insight-chart-card compact">
                      <div class="chart-card-head">
                        <div>
                          <span>Progress Alat</span>
                          <h4>Selesai dan diserahkan</h4>
                        </div>
                      </div>
                      <ApexChart type="radialBar" height="245" :options="registrasiProgressOptions"
                        :series="registrasiProgressSeries" />
                    </div>

                    <div class="insight-chart-card compact" v-if="hasRegistrasiJenisChart">
                      <div class="chart-card-head">
                        <div>
                          <span>Jenis Order</span>
                          <h4>Kalibrasi dan repair</h4>
                        </div>
                      </div>
                      <ApexChart type="donut" height="230" :options="registrasiJenisOrderOptions"
                        :series="registrasiJenisOrderSeries" />
                    </div>
                  </template>
                </div>

                <div v-else>
                  <div v-if="isLoadingAlatStats" class="insight-loading">
                    <VPlaceload height="78px" class="mb-3" />
                    <VPlaceload height="260px" class="mb-3" />
                    <VPlaceload height="220px" />
                  </div>
                  <template v-else>
                    <div class="insight-note">
                      {{ alatScopeNote }}
                    </div>
                    <div class="insight-summary-grid">
                      <div v-for="metric in alatSummaryCards" :key="metric.label" class="insight-stat-card"
                        :class="metric.className">
                        <div class="stat-icon">
                          <i :class="metric.icon" aria-hidden="true"></i>
                        </div>
                        <div>
                          <span>{{ metric.label }}</span>
                          <strong>{{ metric.value }}</strong>
                          <small>{{ metric.caption }}</small>
                        </div>
                      </div>
                    </div>

                    <div class="insight-chart-card" v-if="hasAlatLingkupChart">
                      <div class="chart-card-head">
                        <div>
                          <span>Ruang Lingkup</span>
                          <h4>Sebaran alat terdaftar</h4>
                        </div>
                      </div>
                      <ApexChart type="bar" :height="alatLingkupChartHeight" :options="alatLingkupChartOptions"
                        :series="alatLingkupChartSeries" />
                    </div>
                    <div v-else class="insight-empty">Belum ada alat pada filter ini.</div>

                    <div class="insight-chart-card compact" v-if="hasAlatStatusChart">
                      <div class="chart-card-head">
                        <div>
                          <span>Status Alat</span>
                          <h4>Alur pengerjaan saat ini</h4>
                        </div>
                      </div>
                      <ApexChart type="donut" height="245" :options="alatStatusChartOptions"
                        :series="alatStatusChartSeries" />
                    </div>
                  </template>
                </div>
              </aside>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <VModal :open="modalRiwayat" title="" noclose size="big" actions="right" @close="modalRiwayat = false, clear()"
    cancelLabel="Tutup">
    <template #content>
      <div class="column">
        <div class="business-dashboard hr-dashboard">
          <div class="columns is-multiline">
            <div class="column is-12 p-0">
              <div class="block-header">
                <div class="left column is-12 ">
                  <div class="current-user">
                    <h3>{{ item.namaproduk }}</h3>
                  </div>
                </div>
                <div class="center column is-6 p-0">
                  <div>
                    <div>
                      <h4 class="block-heading" v-if="item.namamerk || item.namatipe">Merk/Tipe</h4>
                      <p class="block-hext" v-if="item.namamerk || item.namatipe">{{ item.namamerk }}/{{ item.namatipe
                      }}</p>
                      <h4 class="block-heading" v-if="item.namaserialnumber">S/N</h4>
                      <p class="block-hext" v-if="item.namaserialnumber">{{ item.namaserialnumber }}</p>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
      <div class="column is-12">
        <Fieldset legend="Data Alat" :toggleable="true">
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
  <VModal :open="modalBatalRegis" title="Batal Registrasi" size="medium" actions="right"
    @close="modalBatalRegis = false" cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-6">
          <VField>
            <VLabel class="required-field">Tanggal Batal</VLabel>
            <VDatePicker v-model="item.tanggalpembatalan" mode="dateTime" style="width: 100%" trim-weeks
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
        <div class="column is-6">
          <VField>
            <VLabel class="required-field">Nama Peerusahaan</VLabel>
            <VControl icon="feather:map-pin">
              <VInput type="text" v-model="item.perusahaan" placeholder="Tempat Lahir" class="is-rounded_Z" disabled />
            </VControl>
          </VField>
        </div>
        <div class="column is-12">
          <span style="margin-bottom:1rem;font-weight: bold; font-size: 12px; font-family: var(--font-alt);">Alasan
            Pembatalan
          </span>

          <VField>
            <VControl>
              <VTextarea class="textarea is-rounded" v-model="item.alasanpembatalan" rows="4"
                placeholder="Alasan Pembatalan" autocomplete="off" autocapitalize="off" spellcheck="true" />
            </VControl>
          </VField>
        </div>

      </div>
    </template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="saveBatalRegis" :loading="isLoading" raised>Simpan
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalKonfirmasiPendaftaran" title="Konfirmasi Pendaftaran" size="big" actions="right"
    @close="modalKonfirmasiPendaftaran = false" cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-6">
          <VField>
            <VLabel class="required-field">Tanggal Konfirmasi</VLabel>
            <VDatePicker v-model="item.tanggalkonfirmasi" mode="dateTime" style="width: 100%" trim-weeks
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
        <div class="column is-6">
          <VField>
            <VLabel class="required-field">Nama Perusahaan</VLabel>
            <VControl icon="feather:map-pin">
              <VInput type="text" v-model="item.perusahaan" placeholder="Tempat Lahir" class="is-rounded_Z" disabled />
            </VControl>
          </VField>
        </div>
        <div class="column is-12" v-if="dataNamaPaket != null">
          <div class="columns is-multiline">
            <div class="ml-4 column is-5">
              <div class="meta-container">
                <div class="meta-content">
                  <h4>Paket Kalibrasi {{ dataNamaPaket }}</h4>
                  <p>
                    <span>Estimaasi Tanggal selesai</span>
                  </p>
                  <p>
                    <span><b>{{ dataPaketTanggal }}</b></span>
                  </p>
                </div>
                <div class="timer ml-4">
                  <div>
                    <span>{{ dataPaket }}</span>
                    <span>Hari</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="column is-12">
          <Fieldset legend="Data Alat" :toggleable="true">
            <div class="column" v-for="(data) in 3" style="text-align:center" v-if="isLoadDataOrder">
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
                  <div class="timeline-item is-unread" v-for="(items, index) in detailOrderLayanan" :key="items.norec">
                    <div :class="'dot is-' + listColor[index + 1]"></div>

                    <div class="content-wrap is-grey">
                      <div class="content-box">
                        <div class="status"></div>
                        <VIconBox size="medium" :color="listColor[index + 1]" rounded>
                          <i class="iconify" data-icon="feather:package" aria-hidden="true"></i>
                        </VIconBox>
                        <div class="box-text" style="width:70%">
                          <div class="meta-text">
                            <p>
                              <span>{{ items.namaproduk }}</span>
                            </p>
                            <table class="tb-order">
                              <tr>
                                <td>Lingkup</td>
                                <td>:</td>
                                <td>{{ items.lingkupkalibrasi }} </td>
                              </tr>
                              <tr>
                                <td>Lokasi</td>
                                <td>:</td>
                                <td>{{ items.lokasi }} </td>
                              </tr>
                              <tr>
                                <td>Penyelias Teknik </td>
                                <td>:</td>
                                <td class="font-values">{{ items.penyeliateknik }}</td>
                              </tr>
                              <tr>
                                <td>Pelaksana Teknik</td>
                                <td>:</td>
                                <td>{{ items.pelaksanateknik }} </td>
                              </tr>
                              <tr>
                                <td>Durasi</td>
                                <td>:</td>
                                <td>
                                  <VTag v-if="items.durasikalbrasi" color="warning" rounded> {{ items.durasikalbrasi }}
                                  </VTag>
                                </td>
                              </tr>
                            </table>

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
        <div class="column is-12">
          <div class="box has-text-centered" style="border: 1px solid #000; padding: 2rem;">
            <p style="color: black;" class="has-text-weight-bold is-size-5 mb-4">Penanggung Jawab</p>
            <img v-if="item.namapenanggungjawab"
              :src="'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + (item.namapenanggungjawab.label ? item.namapenanggungjawab.label : item.namapenanggungjawab)">
            <div class="mt-4 is-flex is-justify-content-center">
              <VField style="width: 300px;">
                <VControl>
                  <VInput type="text" v-model="item.namapenanggungjawab" class="has-text-centered"
                    placeholder="Nama Penanggung Jawab" />
                </VControl>
              </VField>
            </div>
          </div>
        </div>
        <!-- <div class="column is-9" style="text-align: center;">

        </div>
        <div class="column is-3" style="text-align: center;">
          <p class="label-ppap" style="font-weight: bold;">Penanggung Jawab</p>
          <TandaTangan :elemenID="'signaturePenanggungJawab'" :width="'180'" :height="'180'" class="dek" />
          <div class="column pl-0 pr-0 pt-5">
            <VField class="pt-2">
              <VControl class="prime-auto">
                <VInput type="text" v-model="item.namapenanggungjawab" />
              </VControl>
            </VField>
          </div>
        </div> -->
      </div>
    </template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="saveKonfirmasiPendaftaran" :loading="isLoading" raised>Simpan
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalverifikasiRegistrasiCustomer" title="Verfikasi Registrasi Customer" size="big" actions="right"
    @close="modalverifikasiRegistrasiCustomer = false" cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-4">
          <VField>
            <VLabel class="required-field">Tanggal Verifikasi Registrasi</VLabel>
            <VDatePicker v-model="item.tanggalverifikasiregistrasi" mode="dateTime" style="width: 100%" trim-weeks
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
        <div class="column is-4">
          <VField>
            <VLabel class="required-field">Nama Perusahaan</VLabel>
            <VControl icon="feather:map-pin">
              <VInput type="text" v-model="item.perusahaancustomer" placeholder="Tempat Lahir" class="is-rounded_Z"
                disabled />
            </VControl>
          </VField>
        </div>
        <div class="column is-4">
          <VField>
            <VLabel class="required-field">Peenanggung Jawab</VLabel>
            <VControl icon="feather:map-pin">
              <VInput type="text" v-model="item.namapenaggungjawabcustomer" placeholder="Tempat Lahir"
                class="is-rounded_Z" disabled />
            </VControl>
          </VField>
        </div>
        <div class="column is-4">
          <VField>
            <VLabel class="required-field">Catatan Untuk Customer</VLabel>
            <VTextarea rows="2" placeholder="......" v-model="item.catatanuntukcustomer">
            </VTextarea>
          </VField>
        </div>
        <!-- Status Pendaftaran -->
        <div class="column is-8">
          <p class="v-label required-field">Status Pendaftaran (diisi oleh petugas U-Lab)</p>

          <div class="selection-group">
            <div class="selection-card" :class="{ 'is-selected': item.statusPendaftaran === 'diterima' }"
              @click="setStatusPendaftaran('diterima')">
              <span>Pendaftaran diterima, alat harus diserahkan ke U-Lab</span>
            </div>

            <div class="selection-card" :class="{ 'is-selected': item.statusPendaftaran === 'ditangguhkan' }"
              @click="setStatusPendaftaran('ditangguhkan')">
              <span>Layanan ditangguhkan</span>
            </div>
          </div>

          <!-- Jika DITERIMA -> Tanggal ... s/d ... -->
          <Transition name="fade-slow">
            <div v-if="item.statusPendaftaran === 'diterima'" class="mt-3">
              <div class="columns is-multiline">
                <div class="column is-3">
                  <VField>
                    <VLabel class="required-field">Tanggal (dari)</VLabel>
                    <VDatePicker v-model="item.tanggalSerahDari" mode="date" style="width: 100%" trim-weeks>
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

                <div class="column is-3">
                  <VField>
                    <VLabel class="required-field">Tanggal (s/d)</VLabel>
                    <VDatePicker v-model="item.tanggalSerahSd" mode="date" style="width: 100%" trim-weeks>
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
              </div>
            </div>
          </Transition>

          <!-- Jika DITANGGUHKAN -> alasan -->
          <Transition name="fade-slow">
            <div v-if="item.statusPendaftaran === 'ditangguhkan'" class="mt-3">
              <p class="v-label required-field">Layanan ditangguhkan karena</p>

              <div class="selection-group is-vertical">
                <div class="selection-card" :class="{ 'is-selected': item.alasanDitangguhkan === 'kapasitasPenuh' }"
                  @click="item.alasanDitangguhkan = 'kapasitasPenuh'">
                  <span>Kapasitas penuh</span>
                </div>

                <div class="selection-card" :class="{ 'is-selected': item.alasanDitangguhkan === 'diluarLingkup' }"
                  @click="item.alasanDitangguhkan = 'diluarLingkup'">
                  <span>Di luar lingkup pelayanan</span>
                </div>

                <div class="selection-card" :class="{ 'is-selected': item.alasanDitangguhkan === 'pemilihanStandar' }"
                  @click="item.alasanDitangguhkan = 'pemilihanStandar'">
                  <span>Sedang dalam pemilihan standar</span>
                </div>

                <div class="selection-card" :class="{ 'is-selected': item.alasanDitangguhkan === 'lainLain' }"
                  @click="item.alasanDitangguhkan = 'lainLain'">
                  <span>Lain-lain</span>
                </div>
              </div>

              <Transition name="fade-slow">
                <div v-if="item.alasanDitangguhkan === 'lainLain'" class="mt-3">
                  <VField>
                    <VControl>
                      <VTextarea v-model="item.alasanDitangguhkanLain" rows="2"
                        placeholder="Jelaskan alasan lain-lain..." />
                    </VControl>
                  </VField>
                </div>
              </Transition>
            </div>
          </Transition>
        </div>

        <div class="column is-4 mt-5">
          <VButton v-if="item.filecustomerams != null" icon="feather:printer" color="primary" @click="cetakAms(item)"
            :loading="isLoading" raised>
            Cetak AMS
          </VButton>
          <VButton v-if="item.filecustomertools != null" rounded color="warning" class="" icon="feather:download" raised
            bold @click="downloadTools(item)">
            Download List Tools
          </VButton>
        </div>
        <div class="column is-12" v-if="dataNamaPaketCustomer != null">
          <div class="columns is-multiline">
            <div class="ml-4 column is-5">
              <div class="meta-container">
                <div class="meta-content">
                  <h4>Paket Kalibrasi {{ dataNamaPaketCustomer }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="column is-12">
          <Fieldset legend="Data Alat" :toggleable="true">
            <div class="column" v-for="(data) in 3" style="text-align:center" v-if="isLoadDataOrder">
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
                  <div class="timeline-item is-unread" v-for="(items, index) in detailOrderLayananCustomer"
                    :key="items.norec">
                    <div :class="'dot is-' + listColor[index + 1]"></div>

                    <div class="content-wrap is-grey">
                      <div class="content-box">
                        <div class="status"></div>
                        <VIconBox size="medium" :color="listColor[index + 1]" rounded>
                          <i class="iconify" data-icon="feather:package" aria-hidden="true"></i>
                        </VIconBox>
                        <div class="box-text" style="width:70%">
                          <div class="meta-text">
                            <p>
                              <span>{{ items.namaproduk }}</span>
                            </p>
                            <table class="tb-order">
                              <tr>
                                <td>Merk/Tipe</td>
                                <td>:</td>
                                <td>{{ items.namamerk }} - {{ items.namatipe }} </td>
                              </tr>
                              <tr>
                                <td>Serial Number</td>
                                <td>:</td>
                                <td>{{ items.namaserialnumber }} </td>
                              </tr>
                              <tr>
                                <td>Duraasi Permintaan Paket</td>
                                <td>:</td>
                                <td class="font-values">{{ items.hari_paket }}</td>
                              </tr>
                            </table>
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
      </div>
    </template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="saveVerifikasiPendaftaranCustomer" :loading="isLoading"
        raised>Simpan Verifikasi
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalIsiSurvey" title="FMMO-163-14.4.3.b-86.1Survey Kepuasan Pelanggan" size="big" actions="right"
    @close="modalIsiSurvey = false" cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-12">
          <Fieldset class="p-fieldsets" legend="A.INFORMASI UMUM RESPONDEN" :toggleable="true">
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-3">
                  <span> Nama Lengkap : </span>
                </div>
                <div class="column is-9">
                  <VField>
                    <VInput placeholder=" Nama Lengkap....." v-model="item.namaresponden"></VInput>
                  </VField>
                </div>
              </div>
            </div>
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-3">
                  <span> Unit/ Devisi Kerja : </span>
                </div>
                <div class="column is-9">
                  <VField>
                    <VInput placeholder="Unit/ Devisi Kerja....." v-model="item.unitdivisikerja"></VInput>
                  </VField>
                </div>
              </div>
            </div>
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-3">
                  <span> Jabatan/Posisi : </span>
                </div>
                <div class="column is-9">
                  <VField>
                    <VInput placeholder="Unit/ Devisi Kerja....." v-model="item.jabatanresponden"></VInput>
                  </VField>
                </div>
              </div>
            </div>
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-3">
                  <span> Lama Bekerja : </span>
                </div>
                <div class="column is-3">
                  <VField addons>
                    <VControl>
                      <VInput v-model="item.lamabekerja" placeholder="Lama Bekerja" />
                    </VControl>
                    <VControl class="field-addon-body">
                      <VButton static>Tahun</VButton>
                    </VControl>
                  </VField>
                </div>
                <div class="column is-3">
                  <span> Jenis Kelamin : </span>
                </div>
                <div class="column is-3">
                  <VField>
                    <AutoComplete v-model="item.jeniskelamin" :suggestions="d_jenisKelamin"
                      @complete="feetchJenisKelamin($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                      class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                      placeholder="ketik untuk mencari..." />
                  </VField>
                </div>
              </div>
            </div>
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-3">
                  <span> Usia : </span>
                </div>
                <div class="column is-3">
                  <VField addons>
                    <VControl>
                      <VInput v-model="item.usia" placeholder="Usia" />
                    </VControl>
                    <VControl class="field-addon-body">
                      <VButton static>Tahun</VButton>
                    </VControl>
                  </VField>
                </div>
                <div class="column is-3">
                  <span> No. Telp / Hp : </span>
                </div>
                <div class="column is-3">
                  <VField>
                    <VInput placeholder=" No. Telp / Hp....." v-model="item.notelpon"></VInput>
                  </VField>
                </div>
              </div>
            </div>
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-3">
                  <span> Pendidikan : </span>
                </div>
                <div class="column is-3">
                  <VField>
                    <AutoComplete v-model="item.pendidikan" :suggestions="d_pendidikan"
                      @complete="feetchPendidikan($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                      class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                      placeholder="ketik untuk mencari..." />
                  </VField>
                </div>
              </div>
            </div>
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-12">
                  <span> Berapa lama Bapak/Ibu menjadi Mitra Unit Maintenance, Repair, Overhaul: : </span>
                </div>
                <div class="column is-6">
                  <div class="columns is-multiline">
                    <div class="column is-3">
                      <VField>
                        <VControl raw subcontrol>
                          <VCheckbox v-model="item.lamamenjadimitra" true-value="≤1 tahun" label="≤1 tahun" class="p-0"
                            color="primary" square />
                        </VControl>
                      </VField>
                    </div>
                    <div class="column is-6">
                      <VField>
                        <VControl raw subcontrol>
                          <VCheckbox v-model="item.lamamenjadimitra" true-value="1 tahun < lama menjadi mitra ≤ tahun"
                            label="1 tahun < lama menjadi mitra ≤ tahun" class="p-0" color="primary" square />
                        </VControl>
                      </VField>
                    </div>
                    <div class="column is-3">
                      <VField>
                        <VControl raw subcontrol>
                          <VCheckbox v-model="item.lamamenjadimitra" true-value=" > 3 tahun" label=" > 3 tahun"
                            class="p-0" color="primary" square />
                        </VControl>
                      </VField>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Fieldset>
          <!-- B – PENILAIAN TERHADAP KUALITAS LAYANAN  -->
          <Fieldset class="p-fieldsets" legend="B. PENILAIAN TERHADAP KUALITAS LAYANAN " :toggleable="true">
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-6">
                  <strong>Skala Kepuasan:</strong>
                  <ol class="ml-6">
                    <li>Sangat Tidak Puas</li>
                    <li>Tidak Puas</li>
                    <li>Kurang Puas</li>
                    <li>Cukup Puas</li>
                    <li>Puas</li>
                    <li>Sangat Puas</li>
                  </ol>
                </div>
                <div class="column is-6">
                  <strong>Skala Harapan / Kepentingan:</strong>
                  <ol class="ml-6">
                    <li>Sangat Tidak Penting</li>
                    <li>Tidak Penting</li>
                    <li>Kurang Penting</li>
                    <li>Cukup Penting</li>
                    <li>Penting</li>
                    <li>Sangat Penting</li>
                  </ol>
                </div>
                <div class="column is-12">
                  <p style="color: black;"><strong>(B1-1)</strong> Berdasarkan pengalaman Bapak/Ibu, seberapa puaskah
                    Bapak/Ibu terhadap
                    pelayanan Unit Maintenance, Repair, Overhaul?</p>
                </div>
                <div class="column is-12 mt-4-min">
                  <p style="color: black;"><strong>(B1-2)</strong> Berdasarkan pengalaman Bapak/Ibu, bagaimana harapan
                    Bapak/Ibu terhadap
                    pelayanan Unit Maintenance, Repair, Overhaul?</p>
                </div>
                <div class="column is-12 mt-4-min">
                  <p style="color: blue; font-style: italic;">[petunjuk pengisian: Lingkarilah skala yang sesuai dengan
                    kepuasan Bapak/Ibu terhadap setiap atribut ini!]</p>
                </div>

              </div>
            </div>

            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="table-container">
                  <table class="table is-bordered is-fullwidth is-striped is-hoverable">
                    <thead>
                      <tr>
                        <th>NO</th>
                        <th>DIMENSI</th>
                        <th>ATRIBUT KEPUASAN</th>
                        <th class="has-text-centered">HARAPAN<br><small>(Skala 1 - 6)</small></th>
                        <th class="has-text-centered">KEPUASAN<br><small>(Skala 1 - 6)</small></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, index) in atributList" :key="item.no">
                        <td>{{ item.no }}</td>
                        <td>{{ item.dimensi }}</td>
                        <td>{{ item.atribut }}</td>
                        <!-- HARAPAN -->
                        <td>
                          <div class="columns is-multiline is-gapless is-mobile">
                            <div class="column is-2-desktop is-full-mobile has-text-centered" v-for="skala in 6"
                              :key="`harapan-${index}-${skala}`">
                              <VCheckbox v-model="item.harapan" :true-value="skala" :label="skala.toString()" square
                                color="info" class="p-0" />
                            </div>
                          </div>
                        </td>
                        <!-- KEPUASAN -->
                        <td>
                          <div class="columns is-multiline is-gapless is-mobile">
                            <div class="column is-2-desktop is-full-mobile has-text-centered" v-for="skala in 6"
                              :key="`kepuasan-${index}-${skala}`">
                              <VCheckbox v-model="item.kepuasan" :true-value="skala" :label="skala.toString()" square
                                color="success" class="p-0" />
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-12">
                  <p style="color: black;"><strong>(B2.1)</strong> Secara umum, seberapa puas Bapak/Ibu dengan layanan
                    yang diberikan oleh
                    Unit Maintenance, Repair, Overhaul?</p>
                  <div class="columns is-mobile is-multiline is-centered mt-2">
                    <div v-for="n in 6" :key="'skala-b21-' + n" class="column is-narrow">
                      <VCheckbox v-model="item.b21" :true-value="n" :label="n.toString()" color="primary" square />
                    </div>
                  </div>
                </div>
                <div class="column is-12">
                  <p class="mb-2" style="color: black;">
                    <strong>(B2.2) DENGAN MENGACU PADA JAWABAN PERTANYAAN B1</strong><br>
                    Jika Bapak/Ibu merasa sangat puas terhadap jenis layanan Divisi/Bidang sebagaimana disebutkan di
                    atas,
                    atau merasa puas/ sangat puas terhadap layanan Divisi/Bidang namun informasi jenis layanan tersebut
                    belum tercakup dalam instrument di atas (bagian B), mohon Bapak/Ibu berkenan memberikan ulasan lebih
                    detil tentang layanan tersebut (*sifat : optional)
                  </p>
                  <VField>
                    <VControl>
                      <VTextarea v-model="item.b22" placeholder="Tuliskan ulasan Anda di sini..." rows="5" />
                    </VControl>
                  </VField>
                </div>
                <div class="column is-12">
                  <p class="mb-2" style="color: black;">
                    <strong>(B2.3) INOVASI</strong><br>
                    Sebutkan inovasi/peningkatan layanan dari Unit Maintenance, Repair, Overhaul pada tahun 2023 yang
                    diterima oleh Bapak/Ibu (Jika ada)
                  </p>
                  <VField>
                    <VControl>
                      <VTextarea v-model="item.b23" placeholder="Tuliskan inovasi yang Anda rasakan..." rows="5" />
                    </VControl>
                  </VField>
                </div>
              </div>
            </div>
          </Fieldset>
          <Fieldset class="p-fieldsets" legend="C. AREAS for IMPROVEMENT" :toggleable="true">
            <div class="column is-12">
              <div class="content mb-3">
                <p style="color: black;">
                  Apabila terdapat ketidakpuasan terhadap layanan yang diterima, mohon Bapak/Ibu dapat memberikan
                  komentar secara
                  rinci, serta masukan dan saran untuk peningkatan kinerja Unit/Divisi
                </p>
              </div>
              <div>
                <p><strong>C1. Ketidakpuasan terhadap Layanan</strong></p>
                <VField>
                  <VTextarea v-model="item.ketidakpuasanlayanan" placeholder="Tulis ketidakpuasan di sini" rows="4"
                    class="mt-2" />
                </VField>
              </div>
              <div class="mt-4">
                <p><strong>C2. Masukan dan Saran</strong></p>
                <VField>
                  <VTextarea v-model="item.masukansaran" placeholder="Tulis masukan dan saran di sini" rows="4"
                    class="mt-2" />
                </VField>
              </div>
            </div>
          </Fieldset>
          <Fieldset class="p-fieldsets" legend="D. KETERIKATAN PELANGGAN" :toggleable="true">
            <div class="column is-12">
              <div class="columns is-multiline">
                <div class="column is-12">
                  <p class="has-text-danger has-text-weight-bold mb-2">
                    *Mohon dibantu isi skala keterikatan pelanggan berikut:
                  </p>
                  <p class="has-text-danger has-text-weight-bold">Skala Kepuasan:</p>
                  <ol class="pl-4 ml-6 has-text-danger">
                    <li>Sangat Tidak Puas</li>
                    <li>Tidak Puas</li>
                    <li>Kurang Puas</li>
                    <li>Cukup Puas</li>
                    <li>Puas</li>
                    <li>Sangat Puas</li>
                  </ol>
                </div>

                <div class="column is-12">
                  <p class="mb-2 has-text-black">
                    <strong>1. a.</strong> Seberapa besar kemungkinan Unit/Divisi Bapak/Ibu untuk menjalin kerjasama
                    kembali dengan Unit/Divisi ini?
                  </p>
                  <VField>
                    <div class="columns is-mobile is-gapless is-multiline is-centered">
                      <div class="column is-1 has-text-centered" v-for="skala in 10" :key="`d1-${skala}`">
                        <label class="checkbox-scale">
                          <input type="checkbox" :checked="Number(item.d1_skor) === skala"
                            @change="onChangeD1(skala, $event)" />
                          <span>{{ skala }}</span>
                        </label>
                      </div>
                    </div>
                  </VField>
                </div>

                <div class="column is-12 ml-4">
                  <p class="mb-2 has-text-black">
                    <strong>b.</strong> Berikan penjelasan tentang pertimbangan Bapak/Ibu untuk jawaban poin a
                    (*optional)
                  </p>
                  <VField>
                    <VTextarea v-model="item.d1_penjelasan" color="info" placeholder="Tulis penjelasan Anda di sini"
                      :rows="4" />
                  </VField>
                </div>

                <div class="column is-12 mt-4">
                  <p class="mb-2 has-text-black">
                    <strong>2. a.</strong> Apakah Bapak/Ibu bersedia merekomendasikan Unit/Divisi ini kepada
                    kolega/divisi lain?
                  </p>
                  <VField>
                    <div class="columns is-mobile is-gapless is-multiline is-centered">
                      <div class="column is-1 has-text-centered" v-for="skala in 10" :key="`d2-${skala}`">
                        <label class="checkbox-scale">
                          <input type="checkbox" :checked="Number(item.d2_skor) === skala"
                            @change="onChangeD2(skala, $event)" />
                          <span>{{ skala }}</span>
                        </label>
                      </div>
                    </div>
                  </VField>
                </div>

                <div class="column is-12 ml-4">
                  <p class="m-2 has-text-black">
                    <strong>b.</strong> Berikan penjelasan tentang pertimbangan Bapak/Ibu untuk jawaban poin a
                    (*optional)
                  </p>
                  <VField>
                    <VTextarea v-model="item.d2_penjelasan" color="info" placeholder="Tulis penjelasan Anda di sini"
                      :rows="4" />
                  </VField>
                </div>
              </div>
            </div>
          </Fieldset>
        </div>
        <div class="column is-12">
          <div class="box has-text-centered" style="border: 1px solid #000; padding: 2rem;">
            <p style="color: black;" class="has-text-weight-bold is-size-5 mb-4">PERNYATAAN</p>
            <p style="color: black;">
              Saya yang bertanda tangan di bawah ini, menyatakan bahwa pengisian jawaban di atas sudah dapat mewakili
              <br />
              Divisi/Bidang/Unit Kerja dan mengisi dengan benar.
            </p>
            <p style="color: black;" class="mt-5">Tanda Tangan</p>
            <img v-if="item.namaresponden"
              :src="'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + (item.namaresponden.label ? item.namaresponden.label : item.namaresponden)">
            <div class="mt-4 is-flex is-justify-content-center">
              <VField style="width: 300px;">
                <VControl>
                  <VInput type="text" v-model="item.namaresponden" class="has-text-centered"
                    placeholder="Nama Penanggung Jawab" />
                </VControl>
              </VField>
            </div>
          </div>
        </div>
      </div>
    </template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="saveSurveyKepuasan" :loading="isLoadDataOrder" raised>Simpan
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalselesaiterima" title="Cetak Tanda Selesai Terima" size="medium"
    @close="modalselesaiterima = false" cancelLabel="Tutup">
    <template #content>
      <div class="column is-12">
        <VField>
          <VLabel class="required-field">Tanggal Terima ALat</VLabel>
          <VDatePicker v-model="item.tanggalselesaiterima" mode="dateTime" style="width: 100%" trim-weeks
            :max-date="new Date()">
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
      <div class="column is-12">
        <VField>
          <VLabel class="required-field">Tempat Terima</VLabel>
          <VControl icon="feather:map-pin">
            <VInput type="text" v-model="item.tempatterima" placeholder="Tempat Terima" class="is-rounded_Z" />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField>
          <VLabel class="required-field">Petugas Terima</VLabel>
          <VControl icon="feather:grup">
            <VInput type="text" v-model="item.petugasterima" placeholder="Petugas Penerima" class="is-rounded_Z" />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField>
          <VLabel class="required-field">Jabatan Petugas</VLabel>
          <VControl icon="feather:grup">
            <VInput type="text" v-model="item.jabatanpetugas" placeholder="Jabatan Petugas" class="is-rounded_Z" />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField>
          <VLabel class="required-field">Pelanggan Penerima</VLabel>
          <VControl icon="feather:grup">
            <VInput type="text" v-model="item.penerima" placeholder="Pelanggan Penerima" class="is-rounded_Z" />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField>
          <VLabel class="required-field">Jabatan Pelanggan Penerima</VLabel>
          <VControl icon="feather:grup">
            <VInput type="text" v-model="item.jabatanpenerima" placeholder="Jabatan Pelanggan Penerima"
              class="is-rounded_Z" />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField>
          <VLabel class="required-field mb-2">Pilih Alat</VLabel>
          <div class="checklist-card">
            <div class="checklist-toolbar">
              <label class="master">
                <input type="checkbox" v-model="pilihSemua" />
                <span class="ml-2">Pilih semua</span>
              </label>
              <div class="right">
                <span class="tag is-info is-light">{{ jumlahDipilih }} dipilih</span>
              </div>
            </div>
            <div class="checklist-scroll">
              <template v-if="daftarterima.length">
                <div v-for="(row, index) in daftarterima" :key="row._id" class="checklist-row-container" :class="{
                  'is-selected': row._checked && !row.isterima,
                  'is-disabled': row.isterima
                }" @click="!row.isterima && toggleCheck(row)">

                  <div class="row-left-content">
                    <input class="row-checkbox" type="checkbox" v-model="row._checked" :disabled="row.isterima"
                      @click.stop :aria-label="`${row.namaproduk}`" />

                    <div class="row-text">
                      <div class="row-title">
                        <span class="product">{{ row.namaproduk }}</span>
                        <span class="sep">–</span>
                        <span class="brand">{{ row.namamerk }}/{{ row.namatipe }}</span>
                      </div>
                      <div class="row-sub">SN: {{ row.namaserialnumber || '-' }}</div>
                      <span v-if="row.isterima" class="tag is-success is-light row-tag">Sudah diterima</span>
                    </div>
                  </div>

                  <div class="row-actions" v-if="!row.isterima && row._checked" @click.stop>

                    <div class="is-flex is-flex-wrap-wrap mr-2" style="gap:8px;">
                      <div v-for="(p, i) in row._previews" :key="p.id" class="image-preview-mini"
                        @click="previewImageUrl(p.url)" style="position:relative;">
                        <img :src="p.url" alt="Preview"
                          style="width:46px;height:46px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
                        <button class="delete is-small" style="position:absolute;top:-6px;right:-6px;"
                          @click.stop="removeImageRow(row, i)"></button>
                      </div>

                      <button v-if="row._previews?.length" class="button is-small is-danger is-light"
                        @click.stop="clearRowImages(row)" title="Hapus semua foto">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>

                    <div class="file is-small is-info is-boxed mr-1">
                      <label class="file-label">
                        <input class="file-input" type="file" accept="image/*" multiple
                          @change="onFileRowChange($event, row)">
                        <span class="file-cta" style="padding: 0.5em 0.75em;">
                          <span class="file-icon"><i class="fas fa-upload"></i></span>
                        </span>
                      </label>
                    </div>

                    <button class="button is-small is-primary is-outlined" @click="openRowCamera(row)">
                      <i class="fas fa-camera"></i>
                    </button>
                  </div>

                </div>
              </template>
              <div v-else class="empty-state">
                Tidak ada item untuk ditampilkan.
              </div>
            </div>
            <div v-if="isRowCameraActive" class="box camera-overlay"
              style="margin-top: 1rem; border: 2px solid var(--primary);">
              <h5 class="title is-6 has-text-centered">
                Ambil Foto: {{ activeCameraRow?.namaproduk }}
              </h5>

              <div class="has-text-centered">
                <video ref="videoRow" autoplay muted playsinline
                  style="width:500px; max-width:100%; height:auto; border-radius:10px; border:1px solid #ddd;"></video>
              </div>

              <div class="buttons is-centered mt-4">
                <button type="button" @click="takePhotoRow" class="button is-success">
                  <span class="icon"><i class="fas fa-camera"></i></span>
                  <span>Jepret (Tambah)</span>
                </button>

                <button type="button" @click="closeRowCamera" class="button is-warning">
                  Selesai
                </button>
              </div>

              <p class="has-text-centered has-text-grey is-size-7 mt-2">
                Kamu bisa klik Jepret berkali-kali. Foto akan menambah, bukan mengganti.
              </p>
            </div>

          </div>
        </VField>
      </div>
      <div class="column is-12">
        <VField>
          <VLabel>Riwayat Dokumen</VLabel>
          <div class="is-flex is-wrap is-gap-2">
            <template v-if="versiList.length">
              <VButton v-for="v in versiList" :key="v.terimafk" color="info" outlined rounded class="is-small"
                @click="H.printBlade(`registrasi/cetak-selesai-terima?pdf=true&terimafk=${v.terimafk}`)">
                <i class="fas fa-eye mr-2"></i>
                Dokumen {{ v.versi.toString().padStart(2, '0') }}
                <span class="ml-2 has-text-grey is-size-7">
                  ({{ H.formatDate(v.tanggalterima, 'DD MMM YYYY') }})
                </span>
              </VButton>
            </template>
            <template v-else>
              <span class="has-text-grey">Belum ada riwayat dokumen.</span>
            </template>
          </div>
        </VField>
        <div class="right">
          <VButton class="ml-3 right" icon="feather:save" @click="saveSelesaiTerima()" color="primary"
            :loading="isLoading" raised>
            Simpan
          </VButton>
        </div>
      </div>
    </template>
  </VModal>
  <VModal :open="modalCheckout" title="" noclose size="big" actions="right" @close="modalCheckout = false, clear()"
    cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <span class="label-pengkajian required-field ml-4"> Upload Sertifikat Vendor</span>
        <div class="column is-12">
          <FileUpload v-model="fileSertiVendor" mode="advanced" name="demo" accept="application/pdf"
            :maxFileSize="10000000" outlined :invalidFileTypeMessage="'{0}: File yang diupload harus PDF.'"
            :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
            style="background-color: transparent; color: var(--danger); border: 1px solid;"
            :chooseLabel="isFile(fileSertiVendor) ? fileSertiVendor.name : item.fileLamaVendor || 'Unggah File'"
            @select="onSelect($event)" class="is-rounded w-100" />
        </div>
      </div>
    </template>
    <template #action>
      <VButton icon="feather:save" @click="simpanSertifikatVendor(item)" color="info" :loading="isLoading" raised>
        Simpan
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalGantiAms" title="" noclose size="big" actions="right" @close="modalGantiAms = false, clear()"
    cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <span class="label-pengkajian required-field ml-4"> Upload AMS Baru</span>
        <div class="column is-12">
          <FileUpload v-model="fileAMSBaru" mode="advanced" name="demo" accept="application/pdf" :maxFileSize="10000000"
            outlined :invalidFileTypeMessage="'{0}: File yang diupload harus PDF.'"
            :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
            style="background-color: transparent; color: var(--danger); border: 1px solid;"
            :chooseLabel="isFile(fileAMSBaru) ? fileAMSBaru.name : item.filecustomeramsLama || 'Unggah File'"
            @select="onSelectAms($event)" class="is-rounded w-100" />
        </div>
      </div>
    </template>
    <template #action>
      <VButton icon="feather:save" @click="simpanAMSBaru(item)" color="info" :loading="isLoading" raised>
        Simpan
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalPembatalanOrderAlat" title="Batal Kalibrasi" size="medium" actions="right"
    @close="modalPembatalanOrderAlat = false" cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-12">
          <span style="margin-bottom:1rem;font-weight: bold; font-size: 12px; font-family: var(--font-alt);">Alasan
            Batal Kalibrasi
          </span>

          <VField>
            <VControl>
              <VTextarea class="textarea is-rounded" v-model="item.ketgagalkalibrasi" rows="4"
                placeholder="Batal Kalibrasi" autocomplete="off" autocapitalize="off" spellcheck="true" />
            </VControl>
          </VField>
        </div>

      </div>
    </template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="SaveHapusItem(item)" :loading="isLoadingSave" raised>Simpan
      </VButton>
    </template>
  </VModal>
  <VModal :open="modalUbahPelaksana" title="Ubah Penyelia dan Pelaksana" size="medium" actions="right"
    @close="modalUbahPelaksana = false" cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-6">
          <VField>
            <VLabel>Penyelia Teknik</VLabel>
            <VControl>
              <AutoComplete v-model="item.penyeliabaru" :suggestions="d_penyelia" @complete="fetchPenyelia($event)"
                :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
            </VControl>
          </VField>
        </div>
        <div class="column is-6">
          <VField>
            <VLabel>Pelaksana Teknik</VLabel>
            <VControl>
              <AutoComplete v-model="item.pelaksanabaru" :suggestions="d_pelaksana" @complete="fetchPelaksana($event)"
                :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
            </VControl>
          </VField>
        </div>

      </div>
    </template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="SaveUbahPelaksana(item)" :loading="isLoadingSave" raised>
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
  <VModal :open="modalUbahVendor" title="Ubah Vendor" size="medium" actions="right" @close="modalUbahVendor = false"
    cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-6">
          <VField>
            <VLabel>Vendor</VLabel>
            <VControl>
              <AutoComplete v-model="item.vendorbaru" :suggestions="d_vendor" @complete="fetchVendor($event)"
                :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
            </VControl>
          </VField>
        </div>
      </div>
    </template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="SaveUbahVendor(item)" :loading="isLoadingSave" raised>Simpan
      </VButton>
    </template>
  </VModal>
  <WelcomeBannerModalV v-model="openWelcome" :images="[
    '/welcome1.png',
    '/welcome2.png'
  ]" title="Seputar Layanan" subtitle="Info terbaru sebelum menggunakan web"
    storage-key="ulab_welcome_banner_customer_v1" />
</template>
<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { ref, computed, reactive, watch, nextTick, onMounted } from 'vue'
import { useThemeColors } from '/@src/composable/useThemeColors'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import AutoComplete from 'primevue/autocomplete';
import * as qzService from '/@src/utils/qzTrayService'
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import FileUpload from 'primevue/fileupload';
import Badge from 'primevue/badge';
import ApexChart from 'vue3-apexcharts'
import WelcomeBannerModalV from '/@src/components/WelcomeBannerModalV.vue'

useHead({
  title: 'Dashboard Registrasi ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

let listColor: any = ref(Object.keys(useThemeColors()))
let isLoading: any = ref(false)
let btnLoadSimpan: any = ref(false)
let ds_MITRA: any = ref([])
isLoading.value = false
let dataAlatKalibrasi: any = ref([])
let dataAlatKalibrasiStatistik: any = ref([])
let modalCheckout: any = ref(false)
let modalGantiAms: any = ref(false)
const fileSertiVendor: any = ref()
const fileAMSBaru: any = ref()
const route = useRoute()
const router = useRouter()
const modalBatalRegis: any = ref(false)
const modalIsiSurvey: any = ref(false)
const modalKonfirmasiPendaftaran: any = ref(false)
let isLoadDataOrder: any = ref(false)
const themeColors = useThemeColors()
const userLogin = useUserSession().getUser()
const IS_REGISTRASI: any = ref(true)
const isBtnLoading: any = ref(false)
const dataSource: any = ref([])
let isLoadDataDeatilOrder: any = ref(false)
const kelompokUser = useUserSession().getUser().kelompokUser.kelompokUser
const selectView: any = ref()
let modalRiwayat: any = ref(false)
let modalverifikasiRegistrasiCustomer: any = ref(false)
let modalselesaiterima: any = ref(false)
selectView.value = 'grid'
const activeTab = ref(0)
const dataNamaPaket: any = ref()
const dataNamaPaketCustomer: any = ref()
const dataPaket: any = ref()
const dataPaketTanggal: any = ref()
const modalPembatalanOrderAlat: any = ref(false)
const modalUbahPelaksana: any = ref(false)
const modalUbahVendor: any = ref(false)
const d_penyelia = ref([])
const d_pelaksana = ref([])
const d_vendor = ref([])
let isLoadingSave: any = ref(false)
const openWelcome = ref(false)
const currentPage: any = ref({
  limit: 6,
  rows: 50,
})
const currentPageReservation: any = ref({
  limit: 6,
  rows: 50,
})
type AvatarColor = 'primary' | 'info' | 'success' | 'warning' | 'danger'
type LingkupAvatar = { picture: string; color: AvatarColor }
const DEFAULT_AVATAR: LingkupAvatar = {
  picture: '/images/avatars/svg/propinsi.svg',
  color: 'primary',
}

const fetchPenyelia = async (filter: any) => {
  await useApi().get(
    `registrasi/pegawai-lokasi-kalibrasi?param_search=namalengkap&query=${filter.query}&jenispegawai=${1}`
  ).then((response) => {
    d_penyelia.value = response.data.map((e: any) => ({ label: e.namalengkap, value: e.id }))
  })
}

const fetchPelaksana = async (filter: any) => {
  await useApi().get(
    `registrasi/pegawai-lokasi-kalibrasi?param_search=namalengkap&query=${filter.query}&jenispegawai=${2}`
  ).then((response) => {
    d_pelaksana.value = response.data.map((e: any) => ({ label: e.namalengkap, value: e.id }))
  })
}

const normalizeLingkupKey = (s?: string) =>
  (s ?? '')
    .toLowerCase()
    .replace(/&/g, 'dan')
    .replace(/[^a-z0-9]/g, '')
    .trim()

const LINGKUP_AVATAR_MAP: Record<string, LingkupAvatar> = {
  kelistrikan: { picture: '/images/avatars/lingkup/kelistrikan.png', color: 'warning' },
  tekanan: { picture: '/images/avatars/lingkup/tekanan.png', color: 'info' },
  suhudankelembaban: { picture: '/images/avatars/lingkup/suhu.png', color: 'success' },
  vibrasi: { picture: '/images/avatars/lingkup/vibrasi.png', color: 'danger' },
  dimensi: { picture: '/images/avatars/lingkup/dimensi.png', color: 'primary' },
  repair: { picture: '/images/avatars/lingkup/repair.png', color: 'info' },
  vendor: { picture: '/images/avatars/lingkup/vendor.png', color: 'success' },
}

const getLingkupAvatar = (lingkup: string): LingkupAvatar => {
  const key = normalizeLingkupKey(lingkup)
  const alias: Record<string, string> = {
    emisi: 'emisigas',
    instrument: 'instrumentanalitik',
  }
  const finalKey = alias[key] ?? key
  return LINGKUP_AVATAR_MAP[finalKey] ?? DEFAULT_AVATAR
}

const listRiwayatAmandemen = ref<any[]>([])
let modalRiwayatAmandemen: any = ref(false)
const isLoadingRiwayat = ref(false)
const amandemenForm = reactive({
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
  amandemenForm.namaalat = e?.namaproduk ?? '-'
  amandemenForm.namatipe = e?.namatipe ?? '-'
  amandemenForm.namamerk = e?.namamerk ?? '-'
  amandemenForm.namaserialnumber = e?.namaserialnumber ?? '-'
  amandemenForm.noorderalat = e?.noorderalat ?? '-'
  amandemenForm.norec_detail = e?.norec_detail ?? '-'

  try {
    isLoadingRiwayat.value = true
    const response = await useApi().get(`/registrasi/riwayat-amandemen?norec_detail=${e.norec_detail}`)
    listRiwayatAmandemen.value = response ?? []
  } catch (err) {
    listRiwayatAmandemen.value = []
    H.alert('error', 'Gagal mengambil riwayat amandemen')
  } finally {
    isLoadingRiwayat.value = false
  }

  modalRiwayatAmandemen.value = true
}

const cetakRiwayatAmandemen = (item: any) => {
  if ((item?.jenisorder ?? '').toLowerCase() === 'repair') {
    H.printBlade(`registrasi/cetak-laporan-repair-pdf?norec_detail=${item.norec_detail}&version=${item.version}`)
    return
  }
  H.printBlade(`registrasi/cetak-sertif-customer-pdf?norec_detail=${item.norec_detail}&version=${item.version}`)
}


currentPage.value.page = computed(() => {
  try {
    return Number.parseInt(route.query.page as string) || 1
  } catch { }
  return 1
})
watch(currentPage.value, () => {
  if (activeTab.value == 0) {
    fetchMitra()
  }
})
currentPageReservation.value.page = computed(() => {
  try {
    return Number.parseInt(route.query.page as string) || 1
  } catch { }
  return 1
})
const d_jenisKelamin = ref([])
const d_pendidikan = ref([])
const timelineItems = ref([])
const rowGroupMetadata = ref({})
const d_unit = ref([])
const d_lingkup = ref([])
let detailOrderLayanan: any = ref(0)
let detailOrderLayananCustomer: any = ref(0)
const daftarterima = ref<any[]>([])
const pilihSemua = ref(false)
const versiList = ref([])
const startOfCurrentYear = () => new Date(new Date().getFullYear(), 0, 1)
const today = () => new Date()
const DEFAULT_LOKASI_DASHBOARD = ''
const lokasiDashboardOptions = [
  { value: '1', label: 'Jakarta' },
  { value: '2', label: 'Gresik' },
  { value: '', label: 'Semua Lokasi' },
]
const item = reactive({
  filterDate: new Date(),
  tanggalkonfirmasi: new Date(),
  tanggalverifikasiregistrasi: new Date(),
  qPeriode: [
    startOfCurrentYear(),
    today()
  ],
  filterTgl: {
    start: startOfCurrentYear(),
    end: today()
  },
  pelaksanabaru: null as any,
  penyeliabaru: null as any,
  vendorbaru: null as any,
  norec: '' as string,
  norec_detail: '' as string,
  nopendaftaran: '' as string,
  noorderalat: '' as string,
  namaproduk: '' as string,
  namamerk: '' as string,
  namatipe: '' as string,
  namaserialnumber: '' as string,
  durasikalbrasi: '' as string,
  perusahaan: '' as string,
  norecregis: '' as string,
  alasanpembatalan: '' as string,
  tanggalpembatalan: new Date(),
  catatanuntukcustomer: '' as string,
  ketgagalkalibrasi: '' as string,
  tanggalselesaiterima: new Date(),
  tempatterima: '' as string,
  petugasterima: '' as string,
  jabatanpetugas: '' as string,
  penerima: '' as string,
  jabatanpenerima: '' as string,
  perusahaancustomer: '' as string,
  namapenaggungjawabcustomer: '' as string,
  namapenanggungjawab: null as any,
  fileLamaVendor: '' as string,
  filecustomeramsLama: '' as string,
  jenisorder: '' as string,
  iddetail: '' as string,
  statusPendaftaran: null as string | null,
  alasanDitangguhkan: null as string | null,
  alasanDitangguhkanLain: '' as string,
  tanggalSerahDari: null as Date | null,
  tanggalSerahSd: null as Date | null,
  lokasikalibrasi: null as any,
  lokasirepair: null as any,
  namaresponden: '' as string,
  unitdivisikerja: '' as string,
  jabatanresponden: '' as string,
  lamabekerja: '' as string,
  jeniskelamin: null as any,
  pendidikan: null as any,
  usia: '' as string,
  notelpon: '' as string,
  lamamenjadimitra: null as string | null,
  b21: null as number | null,
  b22: '' as string,
  b23: '' as string,
  ketidakpuasanlayanan: '' as string,
  masukansaran: '' as string,
  d1_skor: null as number | null,
  d1_penjelasan: '' as string,
  d2_skor: null as number | null,
  d2_penjelasan: '' as string,
  search: '' as string,
  lokasiDashboard: DEFAULT_LOKASI_DASHBOARD,
  sortAlat: 'tgl_desc' as string,
  lastActivityAlat: '' as string,
  qsearch: '' as string,
  unitfk: null as any,
  ruanglingkupfk: null as any
})
const orderAlat: any = ref(0)
const FILTER_CACHE_KEY = 'ulab_dashboard_registrasi_filters_v3'
let isRestoringFilterCache = false

const toCachedDate = (value: any) => {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(value)
  return Number.isNaN(date.getTime()) ? null : date.toISOString()
}

const fromCachedDate = (value: any, fallback: Date) => {
  if (!value) return fallback
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? fallback : date
}

const saveFilterCache = () => {
  if (isRestoringFilterCache || typeof localStorage === 'undefined') return

  const payload = {
    activeTab: activeTab.value,
    filterTgl: {
      start: toCachedDate(item.filterTgl.start),
      end: toCachedDate(item.filterTgl.end),
    },
    search: item.search,
    qsearch: item.qsearch,
    lokasiDashboard: item.lokasiDashboard,
    unitfk: item.unitfk,
    jenisorder: item.jenisorder,
    ruanglingkupfk: item.ruanglingkupfk,
    orderAlat: orderAlat.value,
    sortAlat: item.sortAlat,
    lastActivityAlat: item.lastActivityAlat,
    limit: currentPage.value.limit,
  }

  localStorage.setItem(FILTER_CACHE_KEY, JSON.stringify(payload))
}

const restoreFilterCache = () => {
  if (typeof localStorage === 'undefined') return

  const raw = localStorage.getItem(FILTER_CACHE_KEY)
  if (!raw) return

  try {
    const cache = JSON.parse(raw)
    isRestoringFilterCache = true

    const cachedActiveTab = Number(cache.activeTab ?? 0)
    activeTab.value = Number.isNaN(cachedActiveTab) ? 0 : cachedActiveTab
    item.filterTgl = {
      start: fromCachedDate(cache.filterTgl?.start, startOfCurrentYear()),
      end: fromCachedDate(cache.filterTgl?.end, today()),
    }
    item.search = cache.search ?? ''
    item.qsearch = cache.qsearch ?? ''
    item.lokasiDashboard = cache.lokasiDashboard ?? DEFAULT_LOKASI_DASHBOARD
    item.unitfk = cache.unitfk ?? null
    item.jenisorder = cache.jenisorder ?? ''
    item.ruanglingkupfk = cache.ruanglingkupfk ?? null
    item.sortAlat = cache.sortAlat ?? 'tgl_desc'
    item.lastActivityAlat = cache.lastActivityAlat ?? ''
    orderAlat.value = cache.orderAlat ?? 0
    const cachedLimit = Number(cache.limit ?? currentPage.value.limit)
    currentPage.value.limit = Number.isNaN(cachedLimit) ? currentPage.value.limit : cachedLimit
  } catch {
    localStorage.removeItem(FILTER_CACHE_KEY)
  } finally {
    nextTick(() => {
      isRestoringFilterCache = false
    })
  }
}

watch(
  () => ({
    activeTab: activeTab.value,
    filterTgl: item.filterTgl,
    search: item.search,
    qsearch: item.qsearch,
    lokasiDashboard: item.lokasiDashboard,
    unitfk: item.unitfk,
    jenisorder: item.jenisorder,
    ruanglingkupfk: item.ruanglingkupfk,
    orderAlat: orderAlat.value,
    sortAlat: item.sortAlat,
    lastActivityAlat: item.lastActivityAlat,
    limit: currentPage.value.limit,
  }),
  saveFilterCache,
  { deep: true }
)

watch(
  () => item.lokasiDashboard,
  () => {
    if (isRestoringFilterCache) return

    if (activeTab.value == 0) {
      fetchMitra()
    } else {
      fetchAlatKalibrasi(orderAlat.value)
    }
  }
)

const chart: any = ref({
  aktif: true
})
const canvasRow = ref<HTMLCanvasElement | null>(null)
const cameraStream = ref<MediaStream | null>(null)
const videoRow = ref<HTMLVideoElement | null>(null)
let rowCameraStream: MediaStream | null = null
const isRowCameraActive = ref(false)
const activeCameraRow = ref<any>(null)
const isMobileDevice = () => {
  return /Mobi|Android/i.test(navigator.userAgent)
}

const emptyRegistrasiStats = () => ({
  summary: {
    total_registrasi: 0,
    total_alat: 0,
    total_selesai: 0,
    total_diambil: 0,
    belum_selesai: 0,
    belum_diambil: 0,
  },
  status: {
    belum_kaji: 0,
    sudah_kaji: 0,
    verif_asman: 0,
    menunggu_admin: 0,
  },
  by_unit: [] as any[],
  by_jenisorder: [] as any[],
  monthly_trend: [] as any[],
})

const registrasiStats = ref<any>(emptyRegistrasiStats())
const isLoadingStats = ref(false)
const isLoadingAlatStats = ref(false)
const metricFormatter = new Intl.NumberFormat('id-ID')
const chartColors = ['#1478e3', '#06d6a0', '#f59e0b', '#ef4444', '#7c3aed', '#14b8a6']

const toNumber = (value: any) => {
  const parsed = Number(value ?? 0)
  return Number.isNaN(parsed) ? 0 : parsed
}

const toBool = (value: any) => value === true || value === 1 || value === '1' || value === 'true'
const percentOf = (value: number, total: number) => total > 0 ? Math.round((value / total) * 100) : 0
const formatMetric = (value: any) => metricFormatter.format(toNumber(value))
const formatJenisOrder = (value: any) => {
  const text = String(value || 'Lainnya').trim()
  return text.charAt(0).toUpperCase() + text.slice(1)
}
const shortChartLabel = (value: any, max = 24) => {
  const text = String(value || '-')
  return text.length > max ? `${text.slice(0, max - 1)}...` : text
}

const filterPeriodLabel = computed(() => {
  const start = item.filterTgl.start ? H.formatDate(item.filterTgl.start, 'DD MMM YYYY') : '-'
  const end = item.filterTgl.end ? H.formatDate(item.filterTgl.end, 'DD MMM YYYY') : '-'
  return `${start} - ${end}`
})

const selectedLokasiDashboard = computed(() => {
  return lokasiDashboardOptions.find((option) => option.value === item.lokasiDashboard) ?? lokasiDashboardOptions[0]
})

const dashboardLokasiLabel = computed(() => selectedLokasiDashboard.value.label)

const selectedLokasiDashboardQuery = () => {
  return item.lokasiDashboard ? `&lokasifk=${encodeURIComponent(item.lokasiDashboard)}` : ''
}

const registrasiSummaryCards = computed(() => {
  const summary = registrasiStats.value.summary ?? emptyRegistrasiStats().summary
  const totalAlat = toNumber(summary.total_alat)
  const selesai = toNumber(summary.total_selesai)
  const diambil = toNumber(summary.total_diambil)

  return [
    {
      label: 'Registrasi',
      value: formatMetric(summary.total_registrasi),
      caption: 'pendaftaran unit',
      icon: 'fas fa-id-card',
      className: 'is-primary',
    },
    {
      label: 'Total Alat',
      value: formatMetric(totalAlat),
      caption: 'alat terdaftar',
      icon: 'fas fa-tools',
      className: 'is-info',
    },
    {
      label: 'Selesai',
      value: formatMetric(selesai),
      caption: `${percentOf(selesai, totalAlat)}% dari alat`,
      icon: 'fas fa-check-circle',
      className: 'is-success',
    },
    {
      label: 'Diambil',
      value: formatMetric(diambil),
      caption: `${percentOf(diambil, totalAlat)}% diserahkan`,
      icon: 'fas fa-hand-holding',
      className: 'is-warning',
    },
  ]
})

const registrasiUnitRows = computed(() => registrasiStats.value.by_unit ?? [])
const hasRegistrasiUnitChart = computed(() => registrasiUnitRows.value.length > 0)
const registrasiUnitChartHeight = computed(() => Math.max(260, registrasiUnitRows.value.length * 46))
const registrasiUnitChartSeries = computed(() => [
  {
    name: 'Pendaftaran',
    data: registrasiUnitRows.value.map((row: any) => toNumber(row.total_registrasi)),
  },
  {
    name: 'Alat selesai',
    data: registrasiUnitRows.value.map((row: any) => toNumber(row.total_selesai)),
  },
  {
    name: 'Alat diambil',
    data: registrasiUnitRows.value.map((row: any) => toNumber(row.total_diambil)),
  },
])
const registrasiUnitChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'var(--font)',
  },
  colors: [chartColors[0], chartColors[1], chartColors[2]],
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 5,
      barHeight: '68%',
    },
  },
  dataLabels: { enabled: false },
  grid: {
    borderColor: '#eef0f6',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: registrasiUnitRows.value.map((row: any) => shortChartLabel(row.namaperusahaan, 26)),
    labels: {
      formatter: (value: any) => formatMetric(value),
      style: { colors: '#8a90a8' },
    },
  },
  yaxis: {
    labels: {
      style: { colors: '#6b7280', fontSize: '11px' },
    },
  },
  legend: {
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '12px',
    markers: { radius: 12 },
  },
  tooltip: {
    y: {
      formatter: (value: any) => formatMetric(value),
    },
  },
}))

const registrasiProgressSeries = computed(() => {
  const summary = registrasiStats.value.summary ?? emptyRegistrasiStats().summary
  const totalAlat = toNumber(summary.total_alat)
  return [
    percentOf(toNumber(summary.total_selesai), totalAlat),
    percentOf(toNumber(summary.total_diambil), totalAlat),
  ]
})
const registrasiProgressOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'var(--font)',
  },
  labels: ['Selesai', 'Diambil'],
  colors: [chartColors[1], chartColors[0]],
  plotOptions: {
    radialBar: {
      hollow: { size: '36%' },
      track: { background: '#edf2f7' },
      dataLabels: {
        name: { fontSize: '12px' },
        value: {
          fontSize: '18px',
          fontWeight: 700,
          formatter: (value: any) => `${Math.round(Number(value) || 0)}%`,
        },
        total: {
          show: true,
          label: 'Progress',
          formatter: () => `${registrasiProgressSeries.value[0]}%`,
        },
      },
    },
  },
  legend: {
    show: true,
    position: 'bottom',
    fontSize: '12px',
  },
}))

const registrasiJenisRows = computed(() => registrasiStats.value.by_jenisorder ?? [])
const hasRegistrasiJenisChart = computed(() => registrasiJenisRows.value.some((row: any) => toNumber(row.total) > 0))
const registrasiJenisOrderSeries = computed(() => registrasiJenisRows.value.map((row: any) => toNumber(row.total)))
const registrasiJenisOrderOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'var(--font)',
  },
  labels: registrasiJenisRows.value.map((row: any) => formatJenisOrder(row.jenisorder)),
  colors: [chartColors[0], chartColors[2], chartColors[4], chartColors[5]],
  dataLabels: {
    formatter: (value: any) => `${Math.round(Number(value) || 0)}%`,
  },
  legend: {
    position: 'bottom',
    fontSize: '12px',
  },
  tooltip: {
    y: {
      formatter: (value: any) => `${formatMetric(value)} pendaftaran`,
    },
  },
}))

const alatRows = computed(() => Array.isArray(dataAlatKalibrasiStatistik.value) ? dataAlatKalibrasiStatistik.value : [])
const isAlatDibatalkan = (row: any) => {
  return row?.statusaktifpendaftaranalat === false
    || row?.statusaktifpendaftaranalat === 0
    || row?.statusaktifpendaftaranalat === '0'
    || !!row?.alasanpembatalanorderalat
    || !!row?.ketgagalkalibrasi
}
const isAlatSelesai = (row: any) => {
  const jenisOrder = String(row?.jenisorder ?? '').toLowerCase()
  if (jenisOrder === 'repair') {
    return !!row?.tglsetujumanagerlaporanrepair || !!row?.managersetujulaporanrepairfk || toNumber(row?.statusordermanager) === 2
  }
  return !!row?.tglsetujumanagerlembarkerja || !!row?.managersetujulembarkerjafk || toNumber(row?.statusordermanager) === 2
}
const alatStatusLabel = (row: any) => {
  if (isAlatDibatalkan(row)) return 'Ditolak/Batal'
  if (toBool(row?.isterima)) return 'Sudah Diambil'
  if (isAlatSelesai(row)) return 'Selesai'
  if (!toBool(row?.iskaji)) return 'Belum Kaji'
  return 'Proses'
}
const groupRowsByLabel = (rows: any[], labelGetter: (row: any) => string, limit = 8) => {
  const groups = rows.reduce((acc: Record<string, any>, row: any) => {
    const label = labelGetter(row) || 'Lainnya'
    if (!acc[label]) {
      acc[label] = { label, total: 0 }
    }
    acc[label].total += 1
    return acc
  }, {})

  return Object.values(groups)
    .sort((a: any, b: any) => b.total - a.total)
    .slice(0, limit)
}

const alatStats = computed(() => {
  const rows = alatRows.value
  const total = rows.length
  const batalRows = rows.filter(isAlatDibatalkan)
  const aktifRows = rows.filter((row: any) => !isAlatDibatalkan(row))
  const batal = batalRows.length
  const aktif = aktifRows.length
  const selesai = aktifRows.filter(isAlatSelesai).length
  const belum = Math.max(aktif - selesai, 0)
  const diambil = aktifRows.filter((row: any) => toBool(row?.isterima)).length
  const vendor = aktifRows.filter((row: any) => toBool(row?.isVendor)).length
  const unitCount = new Set(aktifRows.map((row: any) => row?.idunit ?? row?.namaperusahaan).filter(Boolean)).size
  const lingkupCount = new Set(aktifRows.map((row: any) => row?.lingkupkalibrasi).filter(Boolean)).size

  return {
    total,
    aktif,
    belum,
    selesai,
    batal,
    diambil,
    vendor,
    unitCount,
    lingkupCount,
  }
})

const alatScopeNote = computed(() => {
  return `${dashboardLokasiLabel.value}: total ${formatMetric(alatStats.value.total)} alat, ${formatMetric(alatStats.value.aktif)} aktif, ${formatMetric(alatStats.value.batal)} batal`
})

const alatSummaryCards = computed(() => [
  {
    label: 'Aktif',
    value: formatMetric(alatStats.value.aktif),
    caption: `${formatMetric(alatStats.value.unitCount)} unit aktif`,
    icon: 'fas fa-tools',
    className: 'is-primary',
  },
  {
    label: 'Belum',
    value: formatMetric(alatStats.value.belum),
    caption: `${percentOf(alatStats.value.belum, alatStats.value.aktif)}% dari aktif`,
    icon: 'fas fa-clock',
    className: 'is-info',
  },
  {
    label: 'Selesai',
    value: formatMetric(alatStats.value.selesai),
    caption: `${percentOf(alatStats.value.selesai, alatStats.value.aktif)}% dari aktif`,
    icon: 'fas fa-clipboard-check',
    className: 'is-success',
  },
  {
    label: 'Batal',
    value: formatMetric(alatStats.value.batal),
    caption: `${percentOf(alatStats.value.batal, alatStats.value.total)}% dari total`,
    icon: 'fas fa-ban',
    className: 'is-danger',
  },
  {
    label: 'Diambil',
    value: formatMetric(alatStats.value.diambil),
    caption: `${percentOf(alatStats.value.diambil, alatStats.value.aktif)}% diserahkan`,
    icon: 'fas fa-handshake',
    className: 'is-info',
  },
  {
    label: 'Vendor',
    value: formatMetric(alatStats.value.vendor),
    caption: `${formatMetric(alatStats.value.lingkupCount)} lingkup aktif`,
    icon: 'fas fa-industry',
    className: 'is-warning',
  },
])

const alatLingkupRows = computed(() => groupRowsByLabel(alatRows.value, (row: any) => row?.lingkupkalibrasi ?? 'Tanpa Lingkup'))
const hasAlatLingkupChart = computed(() => alatLingkupRows.value.length > 0)
const alatLingkupChartHeight = computed(() => Math.max(250, alatLingkupRows.value.length * 42))
const alatLingkupChartSeries = computed(() => [
  {
    name: 'Alat',
    data: alatLingkupRows.value.map((row: any) => toNumber(row.total)),
  },
])
const alatLingkupChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'var(--font)',
  },
  colors: chartColors,
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 5,
      barHeight: '62%',
      distributed: true,
    },
  },
  dataLabels: {
    enabled: true,
    formatter: (value: any) => formatMetric(value),
    style: { colors: ['#fff'] },
  },
  grid: {
    borderColor: '#eef0f6',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: alatLingkupRows.value.map((row: any) => shortChartLabel(row.label, 24)),
    labels: {
      formatter: (value: any) => formatMetric(value),
      style: { colors: '#8a90a8' },
    },
  },
  yaxis: {
    labels: {
      style: { colors: '#6b7280', fontSize: '11px' },
    },
  },
  legend: { show: false },
  tooltip: {
    y: {
      formatter: (value: any) => `${formatMetric(value)} alat`,
    },
  },
}))

const alatStatusRows = computed(() => groupRowsByLabel(alatRows.value, alatStatusLabel, 6))
const hasAlatStatusChart = computed(() => alatStatusRows.value.length > 0)
const alatStatusChartSeries = computed(() => alatStatusRows.value.map((row: any) => toNumber(row.total)))
const alatStatusChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'var(--font)',
  },
  labels: alatStatusRows.value.map((row: any) => row.label),
  colors: [chartColors[3], chartColors[0], chartColors[1], chartColors[2], chartColors[4]],
  dataLabels: {
    formatter: (value: any) => `${Math.round(Number(value) || 0)}%`,
  },
  legend: {
    position: 'bottom',
    fontSize: '12px',
  },
  tooltip: {
    y: {
      formatter: (value: any) => `${formatMetric(value)} alat`,
    },
  },
}))

const fetchRegistrasiStats = async () => {
  let dari = ''
  if (item.filterTgl.start) {
    dari = H.formatDate(item.filterTgl.start, 'YYYY-MM-DD 00:00')
  }

  let sampai = ''
  if (item.filterTgl.end) {
    sampai = H.formatDate(item.filterTgl.end, 'YYYY-MM-DD 23:59')
  }

  const params = new URLSearchParams()
  if (dari) params.set('dari', dari)
  if (sampai) params.set('sampai', sampai)
  if (item.search) params.set('search', item.search)
  if (item.lokasiDashboard) params.set('lokasifk', item.lokasiDashboard)
  if (item.unitfk?.value) params.set('unitfk', item.unitfk.value)
  if (item.jenisorder) params.set('jenisorder', item.jenisorder)

  isLoadingStats.value = true
  try {
    registrasiStats.value = await useApi().get(`/registrasi/dashboard-statistik?${params.toString()}`)
  } catch (error) {
    registrasiStats.value = emptyRegistrasiStats()
  } finally {
    isLoadingStats.value = false
  }
}

const normalizeSortText = (value: any) => {
  return String(value ?? '')
    .toLowerCase()
    .trim()
}

const parseTanggalRegistrasiAlat = (row: any) => {
  const tanggal =
    row?.tglregistrasi ??
    row?.tglverifasman ??
    row?.created_at ??
    row?.tanggalregistrasi ??
    null

  if (!tanggal) return 0

  const parsed = new Date(tanggal).getTime()

  return Number.isNaN(parsed) ? 0 : parsed
}

const sortDataAlatKalibrasi = () => {
  const arahSortTanggal = item.sortAlat === 'tgl_asc' ? 1 : -1
  dataAlatKalibrasi.value = [...(dataAlatKalibrasi.value ?? [])].sort((a: any, b: any) => {
    const lingkupA = normalizeSortText(a.lingkupkalibrasi)
    const lingkupB = normalizeSortText(b.lingkupkalibrasi)
    const compareLingkup = lingkupA.localeCompare(lingkupB, 'id', {
      numeric: true,
      sensitivity: 'base',
    })
    if (compareLingkup !== 0) {
      return compareLingkup
    }

    const tanggalA = parseTanggalRegistrasiAlat(a)
    const tanggalB = parseTanggalRegistrasiAlat(b)
    if (tanggalA !== tanggalB) {
      return (tanggalA - tanggalB) * arahSortTanggal
    }
    const namaAlatA = normalizeSortText(a.namaproduk)
    const namaAlatB = normalizeSortText(b.namaproduk)
    const compareNamaAlat = namaAlatA.localeCompare(namaAlatB, 'id', {
      numeric: true,
      sensitivity: 'base',
    })

    if (compareNamaAlat !== 0) {
      return compareNamaAlat
    }
    const noOrderA = normalizeSortText(a.noorderalat)
    const noOrderB = normalizeSortText(b.noorderalat)
    return noOrderA.localeCompare(noOrderB, 'id', {
      numeric: true,
      sensitivity: 'base',
    })
  })

  updateRowGroupMetaData()
}

const setStatusPendaftaran = (val: 'diterima' | 'ditangguhkan') => {
  item.statusPendaftaran = val

  if (val === 'diterima') {
    item.alasanDitangguhkan = null
    item.alasanDitangguhkanLain = null
  } else {
    item.tanggalSerahDari = null
    item.tanggalSerahSd = null
  }
}


const onChangeD1 = (val: number, e: Event) => {
  const checked = (e.target as HTMLInputElement).checked
  if (checked) {
    item.value.d1_skor = val
  } else if (Number(item.value.d1_skor) === val) {
    item.value.d1_skor = null
  }
}

const onChangeD2 = (val: number, e: Event) => {
  const checked = (e.target as HTMLInputElement).checked
  if (checked) {
    item.value.d2_skor = val
  } else if (Number(item.value.d2_skor) === val) {
    item.value.d2_skor = null
  }
}

const isFile = (obj: any): obj is File => {
  return obj instanceof File || (obj && typeof obj === 'object' && 'name' in obj && 'size' in obj && 'type' in obj);
};

const hapusItems = (e: any) => {
  item.norec = e.norec
  item.norec_detail = e.norec_detail
  item.nopendaftaran = e.nopendaftaran
  item.noorderalat = e.noorderalat
  modalPembatalanOrderAlat.value = true
}

const ubahPelaksana = (e: any) => {
  item.norec = e.norec
  item.norec_detail = e.norec_detail
  item.nopendaftaran = e.nopendaftaran
  item.noorderalat = e.noorderalat
  item.pelaksanabaru = {
    value: e.pelaksanateknikfk ?? '',
    label: e.pelaksanateknik ?? ''
  };
  item.penyeliabaru = {
    value: e.penyeliateknikfk ?? '',
    label: e.penyeliateknik ?? ''
  };
  modalUbahPelaksana.value = true
}

const ubahVendor = (e: any) => {
  item.norec_detail = e.norec_detail
  item.noorderalat = e.noorderalat
  item.vendorbaru = {
    value: e.vendorkalibrasifk ?? '',
    label: e.namavendor ?? ''
  };
  modalUbahVendor.value = true
}

const SaveUbahVendor = async (e: any) => {
  if (!e.vendorbaru?.value) {
    H.alert('warning', 'Vendor Baru Harus disi.')
    return
  }
  const json = {
    itemVendor: {
      norec_detail: e.norec_detail || '',
      vendorbaru: e.vendorbaru.value || '',
    },
  };
  isLoadingSave.value = true
  await useApi().post('/registrasi/save-ubah-vendor', json).then((r) => {
    isLoadingSave.value = false
    modalUbahVendor.value = false
    clear()
    fetchAlatKalibrasi(0)
  }).catch((error: any) => {
    isLoadingSave.value = false
    if (error.response) {

      H.alert('error', `Kesalahan: ${error.response.status} - ${error.response.data.message || 'Gagal menyimpan berkas mitra'}`);
    } else if (error.request) {

      H.alert('error', 'Tidak ada respons dari server. Silakan coba lagi.');
    } else {

      H.alert('error', `Terjadi kesalahan: ${error.message}`);
    }
  })
}

watch(pilihSemua, (v) => {
  daftarterima.value.forEach((row) => {
    if (!row?.isterima) row._checked = v
  })
})

const jumlahDipilih = computed(() => daftarterima.value.filter(x => x._checked && !x.isterima).length)

const SaveHapusItem = async (e: any) => {
  if (!e.ketgagalkalibrasi) {
    H.alert('warning', 'Keterangan Harus disi.')
    return
  }
  const json = {
    itembatal: {
      norec: e.norec || '',
      norec_detail: e.norec_detail || '',
      ketgagalkalibrasi: e.ketgagalkalibrasi || '',
      nopendaftaran: e.nopendaftaran || '',
      noorderalat: e.noorderalat || '',
    },
  };
  isLoadingSave.value = true
  await useApi().post('/registrasi/save-batal-kalibrasi-alat', json).then((r) => {
    isLoadingSave.value = false
    modalPembatalanOrderAlat.value = false
    clear()
    fetchAlatKalibrasi(0)
  }).catch((error: any) => {
    isLoadingSave.value = false
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

const SaveUbahPelaksana = async (e: any) => {
  if (!e.penyeliabaru?.value) {
    H.alert('warning', 'Penyelia Baru Harus disi.')
    return
  }
  if (!e.pelaksanabaru?.value) {
    H.alert('warning', 'Pelaksana Baru Harus disi.')
    return
  }
  const json = {
    itemPelaksana: {
      norec_detail: e.norec_detail || '',
      pelaksanbaru: e.pelaksanabaru.value || '',
      penyeliabaru: e.penyeliabaru.value || '',
    },
  };
  isLoadingSave.value = true
  await useApi().post('/registrasi/save-ubah-pelaksana', json).then((r) => {
    isLoadingSave.value = false
    modalUbahPelaksana.value = false
    clear()
    fetchAlatKalibrasi(0)
  }).catch((error: any) => {
    isLoadingSave.value = false
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

const uploadSertiVendor = async (e: any[]) => {
  modalCheckout.value = true
  item.norec_detail = e.norec_detail
  item.fileLamaVendor = e.fileSertiVendor
  item.jenisorder = e.jenisorder
}

const uploadGantiAMS = async (e: any[]) => {
  modalGantiAms.value = true
  console.log(e)
  item.iddetail = e.iddetail
  item.filecustomeramsLama = e.filecustomerams
}

const onSelect = async (filez: any) => {
  const file = filez.files[0];
  if (!file) return;
  if (file.size > 10000000) {
    H.alert('error', 'Maksimal file size adalah 10 MB');
    return;
  }
  if (file.type !== "application/pdf") {
    H.alert('error', 'File yang diizinkan harus berupa PDF');
    return;
  }
  fileSertiVendor.value = file;
}

const onSelectAms = async (filez: any) => {
  const file = filez.files[0];
  if (!file) return;
  if (file.size > 10000000) {
    H.alert('error', 'Maksimal file size adalah 10 MB');
    return;
  }
  if (file.type !== "application/pdf") {
    H.alert('error', 'File yang diizinkan harus berupa PDF');
    return;
  }
  fileAMSBaru.value = file;
}

const simpanAMSBaru = async (e: any) => {
  const formData = new FormData()
  if (isFile(fileAMSBaru.value)) {
    formData.append('fileAMSBaru', fileAMSBaru.value);
  } else if (item.filecustomeramsLama) {
    formData.append('namaFileLama', item.filecustomeramsLama);
  }
  formData.append('norec', e.iddetail)

  isLoading.value = true
  await useApi().post(`/registrasi/save-ams-baru`, formData).then(async (response: any) => {
    isLoading.value = false
    clear()
    fetchAlatKalibrasi(1)
    modalGantiAms.value = false
  }).catch((e: any) => {
    isLoading.value = false
    console.clear()
    console.log(e)
  })
  isLoading.value = false
}

const simpanSertifikatVendor = async (e: any) => {
  const formData = new FormData()
  if (isFile(fileSertiVendor.value)) {
    formData.append('fileSertiVendor', fileSertiVendor.value);
  } else if (item.fileLamaVendor) {
    formData.append('namaFileLama', item.fileLamaVendor);
  }
  formData.append('norec', e.norec_detail)
  formData.append('jenisorder', e.jenisorder)

  isLoading.value = true
  await useApi().post(`/registrasi/save-sertifikat-vendor`, formData).then(async (response: any) => {
    isLoading.value = false
    clear()
    fetchAlatKalibrasi(1)
    modalCheckout.value = false
  }).catch((e: any) => {
    isLoading.value = false
    console.clear()
    console.log(e)
  })
  isLoading.value = false
}

const cetakSertiVendor = (e: any) => {
  console.log(e)
  H.printBlade(`registrasi/cetak-sertifikat-vendor?norec=${e.norec_detail}`);
}

const optionsKelompokLayanan: any = [
  { value: 'kalibrasi', label: 'Kalibrasi' },
  { value: 'repair', label: 'Repair' },
]

const optionsAktivitasAlat = [
  { value: '', label: 'Semua Aktivitas' },
  { value: 'belum_kaji', label: 'Belum Kaji Ulang' },
  { value: 'sudah_kaji', label: 'Sudah Kaji Ulang' },
  { value: 'diisi_pelaksana', label: 'Diisi Pelaksana' },
  { value: 'disetujui_penyelia', label: 'Disetujui Penyelia' },
  { value: 'disetujui_asman', label: 'Disetujui Asman' },
  { value: 'disetujui_manager', label: 'Disetujui Manager' },
  { value: 'vendor', label: 'Vendor' },
]

const fetchUnit = async (filter: any) => {
  await useApi().get(
    `general/dropdown/mitra_m?select=id,namaperusahaan&param_search=namaperusahaan&query=${filter.query}&limit=10`
  ).then((response) => {
    d_unit.value = response
  })
}

const fetchVendor = async (filter: any) => {
  await useApi().get(
    `general/dropdown/vendor_m?select=id,namavendor&param_search=namavendor&query=${filter.query}&limit=10`
  ).then((response) => {
    d_vendor.value = response
  })
}

const fetchLingkup = async (filter: any) => {
  await useApi().get(
    `general/dropdown/lingkupkalibrasi_m?select=id,lingkupkalibrasi&param_search=lingkupkalibrasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lingkup.value = response
  })
}

const onPageChange = () => {
  console.log("successfully", currentPage.value);
  fetchMitra()
}

interface AtributKepuasan {
  no: number
  dimensi: string
  atribut: string
  harapan: number | null
  kepuasan: number | null
}

const atributList = ref<AtributKepuasan[]>([
  {
    no: 1,
    dimensi: 'Assurance',
    atribut: 'Jaminan keamanan data/dokumen, tools, asset dan kerahasiaan informasi pelanggan',
    harapan: null,
    kepuasan: null
  },
  {
    no: 2,
    dimensi: 'Assurance',
    atribut: 'Kompetensi personel Laboratorium Kalibrasi',
    harapan: null,
    kepuasan: null
  },
  {
    no: 3,
    dimensi: 'Assurance',
    atribut: 'Peralatan standar yang digunakan sudah sesuai',
    harapan: null,
    kepuasan: null
  },
  {
    no: 4,
    dimensi: 'Assurance',
    atribut: 'Kejelasan informasi pada sertifikat kalibrasi',
    harapan: null,
    kepuasan: null
  },
  {
    no: 5,
    dimensi: 'Empathy',
    atribut: 'Kepedulian terhadap handling tools pelanggan',
    harapan: null,
    kepuasan: null
  },
  {
    no: 6,
    dimensi: 'Empathy',
    atribut: 'Kemampuan komunikasi/koordinasi personel dalam melayani pelanggan',
    harapan: null,
    kepuasan: null
  },
  {
    no: 7,
    dimensi: 'Reliability',
    atribut: 'Ketersediaan personel Laboratorium',
    harapan: null,
    kepuasan: null
  },
  {
    no: 8,
    dimensi: 'Reliability',
    atribut: 'Ketepatan penjadwalan kalibrasi',
    harapan: null,
    kepuasan: null
  },
  {
    no: 9,
    dimensi: 'Responsiveness',
    atribut: 'Kecepatan merespons permintaan dan pengaduan dari pelanggan',
    harapan: null,
    kepuasan: null
  },
  {
    no: 10,
    dimensi: 'Responsiveness',
    atribut: 'Kemampuan mengakomodasi diluar scope kalibrasi',
    harapan: null,
    kepuasan: null
  },
  {
    no: 11,
    dimensi: 'Responsiveness',
    atribut: 'Ketepatan penyampaian sertifikat hasil kalibrasi',
    harapan: null,
    kepuasan: null
  },
  {
    no: 12,
    dimensi: 'Tangible',
    atribut: 'Ketersediaan schedule, dan prosedur administratif lainnya',
    harapan: null,
    kepuasan: null
  },
  {
    no: 13,
    dimensi: 'Tangible',
    atribut: 'Keterbukaan informasi mengenai proses kalibrasi',
    harapan: null,
    kepuasan: null
  }

])

const isiSurvey = async (e: any) => {
  modalIsiSurvey.value = true
  const response = await useApi().get(`/asman/header-mitra?norec_pd=${e.iddetail}`)
  item.namaresponden = response.mitra[0].name
  item.jabatanresponden = response.mitra[0].jabatan
  item.notelpon = response.mitra[0].nowa
  item.norec = response.mitra[0].norec_pd
  getisiSurvey(e.iddetail)
}

const fetchVersiList = async (norecPd) => {
  const res = await useApi().get(`/registrasi/list-versi-terima?norec=${norecPd}`)
  versiList.value = Array.isArray(res) ? res : (res?.data ?? [])
}

const makeId = () => `${Date.now()}_${Math.random().toString(16).slice(2)}`

const addFilesToRow = (row: any, files: File[], renamePrefix = '') => {
  if (!files?.length) return

  files.forEach((file) => {
    if (!file) return
    if (file.size > 10000000) { H.alert('error', 'Max 10MB'); return }
    const ext = (file.name.split('.').pop() || 'jpeg').toLowerCase()
    const safePrefix = renamePrefix ? renamePrefix.replace(/\s+/g, '_') : 'alat'
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

const previewImageUrl = (url: string) => {
  if (url) window.open(url, '_blank')
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

const openSelesaiTerima = async (e: any) => {
  modalselesaiterima.value = true
  const response = await useApi().get(`/asman/header-mitra?norec_pd=${e.iddetail}`)
  item.norec = response?.mitra?.[0]?.norec_pd ?? e.iddetail
  const r = await useApi().get(`/registrasi/layanan-terima?norec_pd=${e.iddetail}`)
  const rows = Array.isArray(r?.detail) ? r.detail : (Array.isArray(r) ? r : (r?.detail ?? []))
  daftarterima.value = rows.map((row: any) => ({
    ...row,
    _id: row?.norec_detail,
    _checked: false,
    _files: [] as File[],
    _previews: [] as Array<{ id: string; url: string; name: string }>
  }))
  pilihSemua.value = false
  isRowCameraActive.value = false
  await fetchVersiList(item.norec)
}

const toggleCheck = (row: any) => {
  row._checked = !row._checked
  if (!row._checked) {
    clearRowImages(row)
  }
}

const onFileRowChange = (event: any, row: any) => {
  const files: File[] = Array.from(event.target.files || [])
  if (!files.length) return

  addFilesToRow(row, files, row.namaproduk || 'alat')
  event.target.value = ''
}

const previewImageRow = (row: any) => {
  if (row._preview) window.open(row._preview, '_blank');
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
      addFilesToRow(row, files, row.namaproduk || 'alat')
    }

    videoInput.click()
    return
  }
  isRowCameraActive.value = true
  await nextTick()

  try {
    if (!navigator.mediaDevices?.getUserMedia) {
      H.alert('error', 'Browser tidak mendukung kamera')
      isRowCameraActive.value = false
      return
    }

    rowCameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
      audio: false,
    })

    if (videoRow.value) {
      videoRow.value.srcObject = rowCameraStream
      videoRow.value.muted = true
      videoRow.value.playsInline = true
      await videoRow.value.play()
    }
  } catch (err) {
    console.error(err)
    H.alert('error', 'Gagal mengakses kamera')
    closeRowCamera()
  }
}

const takePhotoRow = async () => {
  if (!videoRow.value || !activeCameraRow.value) return

  const v = videoRow.value
  if (!v.videoWidth || !v.videoHeight) {
    H.alert('error', 'Kamera belum siap, coba lagi 1–2 detik.')
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

  const file = new File([blob], `camera_${activeCameraRow.value._id}_${Date.now()}.jpeg`, { type: 'image/jpeg' })
  addFilesToRow(activeCameraRow.value, [file], activeCameraRow.value.namaproduk || 'alat')
}

const closeRowCamera = () => {
  try {
    if (rowCameraStream) {
      rowCameraStream.getTracks().forEach(t => t.stop())
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

const saveSelesaiTerima = async () => {
  if (!item.tanggalselesaiterima || !item.tempatterima || !item.penerima || !item.jabatanpenerima) {
    H.alert('error', 'Lengkapi semua field yang wajib diisi.')
    return
  }
  const selectedRows = daftarterima.value.filter(x => x._checked && !x.isterima);
  if (!selectedRows.length) {
    H.alert('warning', 'Tidak ada Alat yang dipilih.')
    return
  }
  const formData = new FormData();
  formData.append('norec', item.norec);
  formData.append('tanggalselesaiterima', H.formatDate(item.tanggalselesaiterima, 'YYYY-MM-DD'));
  formData.append('tempatterima', item.tempatterima ?? '');
  formData.append('penerima', item.penerima ?? '');
  formData.append('petugasterima', item.petugasterima ?? '');
  formData.append('jabatanpenerima', item.jabatanpenerima ?? '');
  formData.append('jabatanpetugas', item.jabatanpetugas ?? '');
  selectedRows.forEach((row, index) => {
    formData.append(`detailalatterima[${index}]`, row._id);
    if (Array.isArray(row._files) && row._files.length) {
      row._files.forEach((f: File) => {
        formData.append(`files[${row._id}][]`, f);
      })
    }
  });
  isLoading.value = true
  try {
    const res = await useApi().post('registrasi/save-selesai-penerima', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
    closeRowCamera();
    clear();
    await fetchVersiList(item.norec);
    const r = await useApi().get(`/registrasi/layanan-terima?norec_pd=${item.norec}`)
    const rows = r?.detail ?? [];
    daftarterima.value = rows.map((row: any) => ({
      ...row, _id: row?.norec_detail, _checked: false, _file: null, _preview: null
    }));
    fetchMitra();
  } catch (e: any) {
    console.error(e)
    H.alert('error', e.response?.data?.message || 'Gagal menyimpan');
  } finally {
    isLoading.value = false;
  }
}

const getisiSurvey = async (e: any) => {
  isLoadDataOrder.value = true
  const response = await useApi().get(`/registrasi/get-survey-pelanggan?norec_pd=${e}`)
  const dataArray = response.data
  if (Array.isArray(dataArray) && dataArray.length > 0) {
    const data = dataArray[0]
    item.namaresponden = data.namaresponden
    item.unitdivisikerja = data.unitdivisikerja
    item.jabatanresponden = data.jabatanresponden
    item.lamabekerja = data.lamabekerja
    item.jeniskelamin = {
      value: data.idjeniskelamin ?? '',
      label: data.jeniskelamin ?? ''
    }
    item.pendidikan = {
      value: data.idpendidikan ?? '',
      label: data.pendidikan ?? ''
    }
    item.usia = data.usia
    item.notelpon = data.notelpon
    item.lamamenjadimitra = data.lamamenjadimitra
    if (data.detailSurvey && Array.isArray(data.detailSurvey)) {
      data.detailSurvey.forEach((d: any) => {
        const index = atributList.value.findIndex((x) => x.no == d.no)
        if (index !== -1) {
          atributList.value[index].harapan = Number(d.harapan)
          atributList.value[index].kepuasan = Number(d.kepuasan)
        }
      })
    }
    item.b21 = data.b21
    item.b22 = data.b22
    item.b23 = data.b23
    item.ketidakpuasanlayanan = data.ketidakpuasanlayanan
    item.masukansaran = data.masukansaran
    item.d1_skor = data.d1_skor
    item.d1_penjelasan = data.d1_penjelasan
    item.d2_skor = data.d2_skor
    item.d2_penjelasan = data.d2_penjelasan
  }
  isLoadDataOrder.value = false
}

const saveSurveyKepuasan = async () => {
  let json = {
    survey: {
      registrasifk: item.norec,
      namaresponden: item.namaresponden,
      unitdivisikerja: item.unitdivisikerja,
      jabatanresponden: item.jabatanresponden,
      lamabekerja: item.lamabekerja,
      jeniskelamin: item.jeniskelamin.value,
      usia: item.usia,
      notelpon: item.notelpon,
      notependidikanlpon: item.pendidikan.value,
      lamamenjadimitra: item.lamamenjadimitra,
      atributList: atributList.value.map(a => ({
        no: a.no,
        harapan: a.harapan,
        kepuasan: a.kepuasan
      })),
      b21: item.b21 ?? null,
      b22: item.b22 ?? '',
      b23: item.b23 ?? '',
      ketidakpuasanlayanan: item.ketidakpuasanlayanan ?? '',
      masukansaran: item.masukansaran ?? '',
      d1_skor: item.d1_skor ?? null,
      d1_penjelasan: item.d1_penjelasan ?? '',
      d2_skor: item.d2_skor ?? null,
      d2_penjelasan: item.d2_penjelasan ?? '',
      namapenanggungjawab: item.namapenanggungjawab?.label || item.namapenanggungjawab || ''
    }
  }
  isLoadDataOrder.value = true
  await useApi()
    .post(`/registrasi/save-survey-pelanggan`, json)
    .then((response: any) => {
      isLoadDataOrder.value = false
      modalIsiSurvey.value = false
      fetchMitra()
    })
    .catch((e: any) => {
      isLoadDataOrder.value = false
    })
}

const feetchJenisKelamin = async (filter: any) => {
  await useApi().get(
    `general/dropdown/jeniskelamin_m?select=id,jeniskelamin&param_search=jeniskelamin&query=${filter.query}&limit=10`
  ).then((response) => {
    d_jenisKelamin.value = response
  })
}

const feetchPendidikan = async (filter: any) => {
  await useApi().get(
    `general/dropdown/pendidikan_m?select=id,pendidikan&param_search=pendidikan&query=${filter.query}&limit=10`
  ).then((response) => {
    d_pendidikan.value = response
  })
}

const buildAlatQueryParams = () => {
  let dari = ''
  if (item.filterTgl.start) {
    dari = H.formatDate(item.filterTgl.start, 'YYYY-MM-DD')
  }

  let sampai = ''
  if (item.filterTgl.end) {
    sampai = H.formatDate(item.filterTgl.end, 'YYYY-MM-DD')
  }

  let search = ''
  if (item.qsearch) search = encodeURIComponent(item.qsearch)

  return {
    dari,
    sampai,
    search,
    lokasikalibrasi: selectedLokasiDashboardQuery(),
    unitfk: item.unitfk ? `&unitfk=${item.unitfk.value}` : '',
    ruanglingkupfk: item.ruanglingkupfk ? `&ruanglingkupfk=${item.ruanglingkupfk.value}` : '',
  }
}

const fetchAlatKalibrasiStatistik = async () => {
  const params = buildAlatQueryParams()

  isLoadingAlatStats.value = true
  try {
    const response = await useApi().get(
      '/registrasi/get-alat-registrasi?dari=' + params.dari
      + '&sampai=' + params.sampai
      + '&search=' + params.search
      + params.lokasikalibrasi
      + params.ruanglingkupfk
      + params.unitfk
      + '&semuastatus=1'
    )
    dataAlatKalibrasiStatistik.value = response.data ?? []
  } catch (error) {
    dataAlatKalibrasiStatistik.value = []
  } finally {
    isLoadingAlatStats.value = false
  }
}

const fetchAlatKalibrasi = async (q: any, refreshStats = true) => {
  const params = buildAlatQueryParams()
  const statusOrderValue = q ?? orderAlat.value
  let statusordermanager = ''
  item.statusordermanager = statusOrderValue
  let aktivitasalat = item.lastActivityAlat ? `&aktivitasalat=${encodeURIComponent(item.lastActivityAlat)}` : ''
  if (statusOrderValue !== null && statusOrderValue !== undefined && statusOrderValue !== '') {
    statusordermanager = '&statusordermanager=' + encodeURIComponent(statusOrderValue)
  }
  isLoading.value = true
  dataAlatKalibrasi.value = []
  const response = await useApi().get(
    '/registrasi/get-alat-registrasi?dari=' + params.dari
    + '&sampai=' + params.sampai
    + '&search=' + params.search
    + statusordermanager
    + params.lokasikalibrasi
    + params.ruanglingkupfk
    + params.unitfk
    + aktivitasalat
  )
  isLoading.value = false
  dataAlatKalibrasi.value = response.data ?? []

  sortDataAlatKalibrasi()

  if (refreshStats) {
    fetchAlatKalibrasiStatistik()
  }
}

watch(
  () => orderAlat.value,
  () => {
    changeSwitchAlat(orderAlat.value)
  }
)

watch(
  () => item.lastActivityAlat,
  () => {
    if (activeTab.value == 1) {
      fetchAlatKalibrasi(orderAlat.value, false)
    }
  }
)

watch(
  () => item.sortAlat,
  () => {
    sortDataAlatKalibrasi()
  }
)

const updateRowGroupMetaData = () => {
  rowGroupMetadata.value = {};

  if (dataAlatKalibrasi.value) {
    for (let i = 0; i < dataAlatKalibrasi.value.length; i++) {
      let rowData = dataAlatKalibrasi.value[i];
      let lingkupkalibrasi = rowData.lingkupkalibrasi;

      if (i == 0) {
        rowGroupMetadata.value[lingkupkalibrasi] = { index: 0, size: 1 };
      }
      else {
        let previousRowData = dataAlatKalibrasi.value[i - 1];
        let previousRowGroup = previousRowData.lingkupkalibrasi;
        if (lingkupkalibrasi === previousRowGroup) {
          rowGroupMetadata.value[lingkupkalibrasi].size++;
        }
        else {
          rowGroupMetadata.value[lingkupkalibrasi] = { index: i, size: 1 };
        }
      }
    }
  }
}


const klikTab = (e: any) => {
  activeTab.value = e.index
  if (activeTab.value == 0) {
    fetchMitra()
  }
  if (activeTab.value == 1) {
    fetchAlatKalibrasi(orderAlat.value)
  }
}

const detailOrder = async (e) => {
  modalRiwayat.value = true
  item.namaproduk = e.namaproduk
  item.namamerk = e.namamerk
  item.namatipe = e.namatipe
  item.namaserialnumber = e.namaserialnumber
  item.durasikalbrasi = e.durasikalbrasi
  isLoadDataDeatilOrder.value = true
  const response = await useApi().get(`/asman/detail-produk?norec_pd=${e.norec_detail}`)
  timelineItems.value = response.timeline
  isLoadDataDeatilOrder.value = false
}

const fetchMitra = async () => {

  ds_MITRA.value = []
  ds_MITRA.value.loading = true

  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = (offset * limit) - limit
  let page: any = route.query.page ? route.query.page : 1
  let search = ''
  if (item.search) search = `&search=${encodeURIComponent(item.search)}`
  let dari = ''
  if (item.filterTgl.start) {
    dari = H.formatDate(item.filterTgl.start, 'YYYY-MM-DD 00:00')
  }
  let sampai = ''
  if (item.filterTgl.end) {
    sampai = H.formatDate(item.filterTgl.end, 'YYYY-MM-DD 23:59')
  }
  let lokasikalibrasi = selectedLokasiDashboardQuery()
  let unitfk = item.unitfk ? `&unitfk=${item.unitfk.value}` : ''
  let jenisorder = item.jenisorder ? `&jenisorder=${encodeURIComponent(item.jenisorder)}` : ''

  isLoading.value = true
  const response = await useApi().get(`/registrasi/list-mitra-grid?page=${page}&dari=${dari}&sampai=${sampai}&limit=${limit}&offset=${offset}${lokasikalibrasi}${unitfk}${jenisorder}${search}`)
  ds_MITRA.value.loading = false
  ds_MITRA.value = response.data
  ds_MITRA.value.total = response.total
  isLoading.value = false
  dataSource.value = response.data;
  await fetchRegistrasiStats()
}


const filter = () => {
  fetchMitra()
}

const cari = () => {
  if (IS_REGISTRASI.value) {
    fetchMitra()
  }
}

const mitraBaru = () => {
  router.push({
    name: 'module-registrasi-mitra-baru',
  })
}

const cetakTandaTerima = (e) => {
  // console.log(e)
  H.printBlade(`registrasi/cetak-tanda-terima?pdf=true&norec=${e.iddetail}`);
}

const cetakSelesaaiTerima = (e) => {
  H.printBlade(`registrasi/cetak-selesai-terima?pdf=true&norec=${e}`);
}

const cetakPermintaanKalibrasi = (e) => {
  // console.log(e)
  H.printBlade(`registrasi/cetak-permintaan-kalibrasi?pdf=true&norec=${e.iddetail}`);
}

const cetakBarcodeOrder = (e) => {
  console.log(e)
  H.printBlade(`registrasi/cetak-barcode-order?pdf=true&norec=${e.iddetail}`);
}


const cetakLabel = async (e: any) => {
  qzService.printData(`dashboard/registrasi/cetak-label-pasien?pdf=true&noregistrasi=${e.noregistrasi}`, 'LABEL PASIEN', 1)
  // H.printBlade(`dashboard/registrasi/cetak-label-pasien?pdf=true&noregistrasi=${e.noregistrasi}`)
}

const detailRegistrasi = async (e: any) => {
  let apd = await getAPD(e);

  router.push({
    name: 'module-registrasi-detail-registrasi',
    query: {
      noregistrasi: e.noregistrasi,
      nopendaftaran: e.nopendaftaran,
      norec_apd: apd,
    },
  })
}

const batalRegis = async (e: any) => {
  item.perusahaan = e.namaperusahaan
  item.norecregis = e.iddetail

  modalBatalRegis.value = true
}

const konfirmaasiPendaftaran = async (e: any) => {
  console.log(e)
  detailOrderLayanan.value = []
  modalKonfirmasiPendaftaran.value = true
  item.perusahaan = e.namaperusahaan
  item.namapenanggungjawab = e.namapenanggungjawab
  item.norecregis = e.iddetail
  isLoadDataOrder.value = true
  const response = await useApi().get(`/asman/layanan-verif?norec_pd=${e.iddetail}`)
  response.detail.forEach((element: any, i: any) => {
    element.no = i + 1
  });
  dataNamaPaket.value = response.detail[0].namapaket
  dataPaket.value = response.detail[0].totalDurasi
  dataPaketTanggal.value = response.detail[0].tanggalSelesai
  isLoadDataOrder.value = false
  detailOrderLayanan.value = response.detail
}

const verifikasiPendaftaranCustomer = async (e: any) => {
  console.log(e)
  detailOrderLayananCustomer.value = []
  modalverifikasiRegistrasiCustomer.value = true
  item.perusahaancustomer = e.namaperusahaan
  item.namapenaggungjawabcustomer = e.namapenanggungjawab
  item.norecregis = e.iddetail
  item.filecustomerams = e.filecustomerams
  item.filecustomertools = e.filecustomertools
  isLoadDataOrder.value = true
  const response = await useApi().get(`/registrasi/get-alat-verif-customer?norec_pd=${e.iddetail}`)
  response.detail.forEach((element: any, i: any) => {
    element.no = i + 1
  });
  item.lokasikalibrasi = response.detail[0].lokasikalibrasifk
  item.lokasirepair = response.detail[0].lokasirepairfk
  dataNamaPaketCustomer.value = response.detail[0].namapaket
  isLoadDataOrder.value = false
  detailOrderLayananCustomer.value = response.detail
}

const saveVerifikasiPendaftaranCustomer = async () => {
  if (!item.tanggalverifikasiregistrasi) {
    H.alert('warning', 'Tanggal Verifikasi Registrasi wajib diisi.')
    return
  }
  if (!item.catatanuntukcustomer || String(item.catatanuntukcustomer).trim() === '') {
    H.alert('warning', 'Catatan untuk customer wajib diisi.')
    return
  }
  if (!item.statusPendaftaran) {
    H.alert('warning', 'Status pendaftaran wajib dipilih.')
    return
  }
  if (item.statusPendaftaran === 'diterima') {
    if (!item.tanggalSerahDari) {
      H.alert('warning', 'Tanggal serah (dari) wajib diisi jika pendaftaran diterima.')
      return
    }
    if (!item.tanggalSerahSd) {
      H.alert('warning', 'Tanggal serah (s/d) wajib diisi jika pendaftaran diterima.')
      return
    }
    if (new Date(item.tanggalSerahSd).getTime() < new Date(item.tanggalSerahDari).getTime()) {
      H.alert('warning', 'Tanggal serah (s/d) tidak boleh lebih kecil dari tanggal (dari).')
      return
    }
  }

  if (item.statusPendaftaran === 'ditangguhkan') {
    if (!item.alasanDitangguhkan) {
      H.alert('warning', 'Alasan penangguhan wajib dipilih.')
      return
    }
    if (
      item.alasanDitangguhkan === 'lainLain' &&
      (!item.alasanDitangguhkanLain || String(item.alasanDitangguhkanLain).trim() === '')
    ) {
      H.alert('warning', 'Keterangan lain-lain wajib diisi.')
      return
    }
  }

  const status = item.statusPendaftaran ?? null

  const tanggalSerahDari =
    status === 'diterima' ? (item.tanggalSerahDari ?? null) : null

  const tanggalSerahSd =
    status === 'diterima' ? (item.tanggalSerahSd ?? null) : null

  const alasanDitangguhkan =
    status === 'ditangguhkan' ? (item.alasanDitangguhkan ?? null) : null

  const alasanDitangguhkanLain =
    status === 'ditangguhkan' && item.alasanDitangguhkan === 'lainLain'
      ? (item.alasanDitangguhkanLain ?? null)
      : null

  const json = {
    verifregistrasi: {
      norecregis: item.norecregis,
      catatancustomer: item.catatanuntukcustomer,
      tanggalverifregiscustomer: item.tanggalverifikasiregistrasi,
      lokasikalibrasi: item.lokasikalibrasi ?? null,
      lokasirepair: item.lokasirepair ?? null,
      statuspendaftaran: status,
      tanggalserahdari: tanggalSerahDari,
      tanggalserahsd: tanggalSerahSd,
      alasanditangguhkan: alasanDitangguhkan,
      alasanditangguhkanlain: alasanDitangguhkanLain,
    },
  }

  isLoading.value = true
  await useApi()
    .post(`/registrasi/save-verifikasi-regis-customer`, json)
    .then(() => {
      isLoading.value = false
      modalverifikasiRegistrasiCustomer.value = false
      clear()
      fetchMitra()
    })
    .catch(() => {
      isLoading.value = false
    })
}

const saveBatalRegis = async () => {
  if (!item.alasanpembatalan) { H.alert('warning', 'Alasan Pembatalan harus di isi'); return }
  let json = {
    mitraregis: {
      'norecregis': item.norecregis,
      'tanggalpembatalan': item.tanggalpembatalan,
      'alasanpembatalan': item.alasanpembatalan,
    }
  }
  isLoading.value = true
  await useApi()
    .post(`/registrasi/save-batal-regis-mitra`, json)
    .then((response: any) => {
      isLoading.value = false
      clear()
      fetchMitra()
    })
    .catch((e: any) => {
      isLoading.value = false
    })
}

const saveKonfirmasiPendaftaran = async () => {
  let object: any = {}
  object.ttdPenanggungJawab = H.tandaTangan().get("signaturePenanggungJawab")

  console.log(object.ttdPenanggungJawab)

  let json = {
    mitrakonfirmasi: {
      'norecregis': item.norecregis,
      'tanggalkonfirmasi': item.tanggalkonfirmasi,
      'namapenanggungjawab': item.namapenanggungjawab,
      'datattd': object,
    }
  }
  isLoading.value = true
  await useApi()
    .post(`/registrasi/save-konfirmasi-pendaftaran`, json)
    .then((response: any) => {
      isLoading.value = false
      modalKonfirmasiPendaftaran.value = false
      clear()
      fetchMitra()
    })
    .catch((e: any) => {
      isLoading.value = false
    })
}

const detail = (e: any) => {
  // console.log(e)
  item.nocmfk_baru = e.id
  item.nocm_tujuan = e.nocm
  H.alert('success', 'Data telah terpilih')
  return
};

const clear = () => {
  item.alasanpembatalan = ''
  item.tanggalpembatalan = ''
  item.tanggalkonfirmasi = ''
  item.catatanuntukcustomer = ''
  item.ketgagalkalibrasi = ''
  item.tanggalselesaiterima = ''
  item.tempatterima = ''
  item.petugasterima = ''
  item.jabatanpetugas = ''
  item.penerima = ''
  item.jabatanpenerima = ''

  modalBatalRegis.value = false
}

const kajiUlang = (e: any) => {
  console.log(e)
  router.push({
    name: 'module-registrasi-kaji-ulang',
    query: {
      nocmfk: e.id,
      norec_mitra_daftar: e.iddetail,
      tglregistrasi: e.tglregistrasi
    },

  })
}

const kajiUlangRepair = (e: any) => {
  console.log(e)
  router.push({
    name: 'module-registrasi-kaji-ulang-repair',
    query: {
      nocmfk: e.id,
      norec_mitra_daftar: e.iddetail,
      tglregistrasi: e.tglregistrasi
    },

  })
}


const listButton: any = ref([
  {
    label: 'Mitra Lama ',
    icon: 'fas fa-users',
    command: () => {
      router.push({ name: 'module-registrasi-mitra-lama' });
    }
  },
  {
    label: 'Mitra Baru',
    icon: 'fas fa-user-plus',
    command: () => {
      router.push({ name: 'module-registrasi-mitra-baru' });
    }
  }
])


const changeSwitchAlat = (e: any) => {
  fetchAlatKalibrasi(e, false)
}

const cetakLaporanVerfikasi = (e: any) => {
  H.printBlade(`asman/cetak-laporan-verifikasi?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`)
}

const cetakSertifikatLembarKerja = (e: any) => {
  if (e.versisertifikat == null) {
    H.printBlade(`asman/cetak-sertifikat-lembar-kerja?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
  } else {
    H.printBlade(`registrasi/cetak-sertif-customer-pdf?norec_detail=${e.norec_detail}`)
  }
}

const cetakLaporanRepair = (e: any) => {
  if (e.versilaporanrepair == null) {
    H.printBlade(`asman/cetak-laporan-repair?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
  } else {
    H.printBlade(`registrasi/cetak-laporan-repair-pdf?norec_detail=${e.norec_detail}`)
  }
}

const cetakAms = (item: any) => {
  if (!item.norecregis) {
    H.alert('warning', 'Data tidak valid');
    return;
  }

  H.printBlade(`registrasi/cetak-ams?norecregis=${item.norecregis}`);
};

const cetakAmsDashboard = (item: any) => {
  H.printBlade(`registrasi/cetak-ams?norecregis=${item.iddetail}`);
};

const downloadTools = (item: any) => {
  const norec = item.norecregis;
  const token = useUserSession().token;
  // const url = `http://localhost:8000/service/registrasi/download-tools-customer?norecregis=${norec}&token=${token}`;
  const url = `/service/registrasi/download-tools-customer?norecregis=${norec}&token=${token}`;

  window.open(url, '_blank');
};

// qzService.connect()
// fetchdDropdown()
restoreFilterCache()
if (activeTab.value == 1) {
  fetchAlatKalibrasi(orderAlat.value)
} else {
  fetchMitra()
}
onMounted(() => {
  const key = 'ulab_welcome_banner_customer_v1'
  if (localStorage.getItem(key) !== '1') {
    openWelcome.value = true
  }
})

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/registrasi.scss';
@import '/@src/scss/module/dashboard/penyelia.scss';
@import '/@src/scss/module/registrasi/list-pasien';
@import '/@src/scss/module/dashboard/bedah.scss';

.timer {

  bottom: 10px;
  left: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 50px;
  width: 50px;
  border-radius: 12px;
  background: var(--primary);
  border: 1px solid var(--primary);
  font-family: var(--font);
  text-align: center;

  span {
    display: block;

    &:first-child {
      font-size: 1.3rem;
      color: var(--smoke-white);
      font-weight: 600;
      line-height: 1;
    }

    &:nth-child(2) {
      font-size: 0.7rem;
      text-transform: uppercase;
      color: var(--primary-light-40);
    }
  }
}

.meta-container {
  display: flex;
  align-items: center;
  padding: 5px;

  .meta-icon {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 46px;
    min-width: 46px;
    height: 46px;
    max-height: 46px;
    background: var(--white);
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: 500px;

    img {
      display: flex;
      height: 22px;
      width: 22px;
    }
  }

  .meta-content {
    margin-left: 8px;
    font-family: var(--font);
    line-height: 1.3;

    h4 {
      font-family: var(--font-alt);
      font-weight: 600;
      font-size: 1rem;
      color: var(--dark-text);
    }

    p {
      display: flex;
      align-items: center;

      .fa-circle {
        font-size: 5px;
        margin: 0 10px;
      }

      .fa-star {
        position: relative;
        top: -1px;
        font-size: 12px;
        color: #fab82a;

        +span {
          color: var(--dark-text);
        }
      }
    }
  }
}

.c-title {
  border-top-left-radius: 11px;
  border-left: solid hsl(19deg 100% 75% / 72%) 3px;
}

.block-heading {
  font-family: var(--font-alt);
  font-weight: 600;
  font-size: 1.1rem;
  color: var(--white);
  margin-bottom: 4px;
}

.checklist-card {
  border: none !important;
  /* <— no border */
  border-radius: 14px;
  background: var(--body, #fff);
  overflow: hidden;
  box-shadow: none;
  /* opsional: tetap flat */
}

.checklist-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .75rem 1rem;
  position: sticky;
  top: 0;
  z-index: 1;
  background: inherit;
  border-bottom: none !important;
  /* <— no border */
}

.checklist-scroll {
  max-height: 260px;
  overflow: auto;
}

.checklist-row {
  width: 100%;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: .75rem;
  padding: .75rem 1rem;
  background: transparent;
  text-align: left;
  cursor: pointer;
  border-bottom: none !important;
  /* <— no border antar-row */
}

/* Biar tetap kebaca tanpa garis, kasih sedikit jarak antar item */
.checklist-row+.checklist-row {
  margin-top: .25rem;
}

/* Hover & selected tanpa border */
.checklist-row:hover {
  background: rgba(0, 0, 0, .03);
}

.is-selected {
  background: rgba(0, 149, 255, .08);
}

.is-disabled {
  opacity: .6;
  cursor: not-allowed;
}

.row-checkbox {
  width: 20px;
  height: 20px;
}

.row-text {
  min-width: 0;
}

.row-title {
  font-weight: 600;
  line-height: 1.2;
  display: flex;
  flex-wrap: wrap;
  gap: .35rem;
}

.row-title .product {
  text-transform: uppercase;
}

.row-title .sep {
  opacity: .6;
}

.row-sub {
  font-size: .84rem;
  color: #6b7280;
}

.row-tag {
  align-self: center;
  margin-left: .5rem;
}

.empty-state {
  padding: 1rem;
  color: #6b7280;
  font-size: .9rem;
}

/* Mobile */
@media (max-width: 640px) {
  .checklist-row {
    grid-template-columns: auto 1fr;
  }

  .row-tag {
    grid-column: 1 / -1;
    justify-self: start;
    margin-top: .25rem;
  }
}

.checklist-row {
  width: 100%;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: .75rem;
  padding: .75rem 1rem;
  text-align: left;
  cursor: pointer;
  background: transparent;

  /* HAPUS SEMUA BORDER/OUTLINE BAWAAN BUTTON */
  border: none !important;
  border-left: none !important;
  border-right: none !important;
  border-top: none !important;
  border-bottom: none !important;
  outline: none !important;
  box-shadow: none !important;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
}

/* opsional: fokus custom biar aksesibel */
.checklist-row:focus-visible {
  outline: 2px solid rgba(0, 149, 255, .6);
  outline-offset: 2px;
}


.checkbox-scale {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.checkbox-scale input {
  width: 16px;
  height: 16px;
}

/* Ganti style .checklist-row yang lama dengan ini */
.checklist-row-container {
  width: 100%;
  display: flex;
  /* Ganti grid ke flex agar lebih fleksibel */
  justify-content: space-between;
  align-items: center;
  padding: .75rem 1rem;
  border-bottom: 1px solid #eee;
  /* Tambahkan border tipis */
  cursor: pointer;
  transition: background-color 0.2s;

  &:hover {
    background-color: #f9fafb;
  }

  &.is-selected {
    background: rgba(0, 149, 255, .05);
  }

  &.is-disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.row-left-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  /* Mengisi ruang sisa */
}

.row-actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.image-preview-mini {
  position: relative;
  width: 40px;
  height: 40px;
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .delete {
    position: absolute;
    top: 0;
    right: 0;
    transform: scale(0.7);
  }
}

/* Style Video Kamera Overlay */
.camera-overlay {
  background: #f5f5f5;
  padding: 1rem;
}


@keyframes shake-loop {
  0% {
    transform: translateX(0);
  }

  10% {
    transform: translateX(-10px);
  }

  20% {
    transform: translateX(10px);
  }

  30% {
    transform: translateX(-7px);
  }

  40% {
    transform: translateX(7px);
  }

  50% {
    transform: translateX(-4px);
  }

  60% {
    transform: translateX(4px);
  }

  70% {
    transform: translateX(-2px);
  }

  80% {
    transform: translateX(2px);
  }

  100% {
    transform: translateX(0);
  }
}

.gagal-repair-anim {
  animation: shake-loop 1.3s cubic-bezier(.36, .07, .19, .97) infinite;
  display: flex;
  align-items: center;
  gap: 18px;
  color: #e53935;
  font-weight: 900;
  font-size: 2rem;
  /* Ukuran lebih besar */
  letter-spacing: 1px;
  margin-top: 16px;
  text-shadow: 0 2px 10px #fff6;
  user-select: none;
}

.gagal-repair-anim svg {
  width: 38px !important;
  height: 38px !important;
  flex-shrink: 0;
}

.selection-group {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.selection-group.is-vertical {
  flex-direction: column;
  align-items: flex-start;
}

.selection-card {
  border: 1px solid #e5e7eb;
  background: #fff;
  padding: 10px 12px;
  border-radius: 10px;
  cursor: pointer;
  user-select: none;
  min-width: 220px;
}

.selection-card.is-selected {
  border-color: #22c55e;
  background: #f0fdf4;
}

.fade-slow-enter-active,
.fade-slow-leave-active {
  transition: opacity .25s ease, transform .25s ease;
}

.fade-slow-enter-from,
.fade-slow-leave-to {
  opacity: 0;
  transform: translateY(-4px);
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

// Riwayat Styles
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

.registrasi-content-layout {
  align-items: flex-start;
}

.registrasi-main-card {
  min-height: 640px;
}

.registrasi-insight-panel {
  position: sticky;
  top: 84px;
  display: grid;
  gap: 14px;
  max-height: calc(100vh - 110px);
  overflow: auto;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--card-bg-color, #fff);
  box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
}

.insight-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.insight-head h3 {
  margin: 2px 0 0;
  color: var(--dark-text);
  font-family: var(--font-alt);
  font-size: 1.05rem;
  font-weight: 700;
  line-height: 1.2;
}

.insight-kicker {
  display: block;
  color: var(--light-text);
  font-size: .76rem;
  font-weight: 700;
  text-transform: uppercase;
}

.insight-location {
  display: block;
  margin-top: 3px;
  color: #1478e3;
  font-size: .74rem;
  font-weight: 700;
}

.insight-period {
  max-width: 150px;
  padding: 5px 8px;
  border-radius: 999px;
  background: rgba(20, 120, 227, .1);
  color: #1478e3;
  font-size: .72rem;
  font-weight: 700;
  line-height: 1.25;
  text-align: right;
}

.insight-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-bottom: 14px;
}

.insight-stat-card {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 9px;
  align-items: center;
  min-height: 78px;
  padding: 10px;
  border: 1px solid var(--border);
  border-left: 4px solid #1478e3;
  border-radius: 8px;
  background: var(--card-bg-color, #fff);
}

.insight-stat-card .stat-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: rgba(20, 120, 227, .1);
  color: #1478e3;
}

.insight-stat-card span,
.insight-stat-card small {
  display: block;
  overflow-wrap: anywhere;
}

.insight-stat-card span {
  color: var(--light-text);
  font-size: .74rem;
  font-weight: 700;
}

.insight-stat-card strong {
  display: block;
  color: var(--dark-text);
  font-size: 1.25rem;
  line-height: 1.05;
}

.insight-stat-card small {
  color: #7b8195;
  font-size: .72rem;
  line-height: 1.2;
}

.insight-stat-card.is-success {
  border-left-color: #06d6a0;
}

.insight-stat-card.is-success .stat-icon {
  background: rgba(6, 214, 160, .12);
  color: #079b78;
}

.insight-stat-card.is-info {
  border-left-color: #1478e3;
}

.insight-stat-card.is-warning {
  border-left-color: #f59e0b;
}

.insight-stat-card.is-warning .stat-icon {
  background: rgba(245, 158, 11, .13);
  color: #b87507;
}

.insight-stat-card.is-danger {
  border-left-color: #ef4444;
}

.insight-stat-card.is-danger .stat-icon {
  background: rgba(239, 68, 68, .12);
  color: #dc2626;
}

.insight-chart-card,
.insight-empty {
  margin-top: 12px;
  padding: 12px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--card-bg-color, #fff);
}

.insight-chart-card.compact {
  padding-bottom: 4px;
}

.chart-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}

.chart-card-head span {
  display: block;
  color: var(--light-text);
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
}

.chart-card-head h4 {
  margin: 1px 0 0;
  color: var(--dark-text);
  font-size: .9rem;
  font-weight: 700;
  line-height: 1.2;
}

.insight-empty {
  color: var(--light-text);
  font-size: .86rem;
  line-height: 1.35;
}

.insight-note {
  padding: 8px 10px;
  border-radius: 8px;
  background: rgba(20, 120, 227, .08);
  color: #1478e3;
  font-size: .74rem;
  font-weight: 700;
  line-height: 1.25;
}

.insight-loading {
  display: grid;
  gap: 10px;
}

.riwayat-right {
  display: flex;
  align-items: center;
}

.alat-filter-card {
  border-radius: 14px;
}

.alat-filter-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 10px 14px;
  flex-wrap: wrap;
}

.alat-status-filter {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  flex: 1;
  flex-wrap: wrap;
}

.alat-sort-filter {
  min-width: 190px;
  display: flex;
  justify-content: flex-end;
}

.alat-activity-filter {
  min-width: 220px;
  display: flex;
  justify-content: flex-end;
}

.alat-activity-filter .select,
.alat-activity-filter select,
.alat-sort-filter .select,
.alat-sort-filter select {
  width: 100%;
}

@media (max-width: 768px) {
  .registrasi-insight-panel {
    position: static;
    max-height: none;
  }

  .insight-period {
    max-width: 100%;
    text-align: left;
  }

  .insight-summary-grid {
    grid-template-columns: 1fr;
  }

  .alat-filter-inner {
    flex-direction: column;
    align-items: stretch;
  }

  .alat-status-filter {
    justify-content: flex-start;
    gap: 10px;
  }

  .alat-sort-filter {
    width: 100%;
    justify-content: stretch;
  }

  .alat-activity-filter {
    width: 100%;
    justify-content: stretch;
  }
}
</style>
