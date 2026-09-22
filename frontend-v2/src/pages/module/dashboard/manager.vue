<template>
    <ConfirmDialog />
    <div>
        <div class="business-dashboard hr-dashboard">
            <div class="columns">
                <div class="column is-12">
                    <div class="columns is-multiline">
                        <div class="column is-12">
                            <div class="illustration-header-2 large-screen">
                                <div class="header-image">
                                    <img src="/@src/assets/illustrations/dashboards/lifestyle/Picture2.png" alt=""
                                        style="max-width:75%; margin-left: 2rem; margin-top: 0.5rem;" />
                                </div>
                                <div class="header-meta" style="margin-left : -2rem;">
                                    <h3 style="color:white"><i class="fas fa-id-card" aria-hidden="true"></i> Dashboard
                                        Manager
                                    </h3>
                                    <p>
                                        Selamat Datang , {{ userLogin.pegawai.namaLengkap }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="columns is-multiline">
                        <div class="column is-8" style="margin-top: 2rem;">
                            <VButton rounded color="info" class="mb-1 amandemen-btn" icon="feather:list" raised bold
                                @click="openPengajuan(item)">
                                <span>Daftar Pengajuan Amandemen</span>
                                <Badge v-if="pendingAmandemenCount > 0" :value="pendingAmandemenCount" severity="danger"
                                    class="ml-2" />
                            </VButton>
                            <TabView class="tabview-custom " :scrollable="true" @tab-click="klikTab($event)">
                                <TabPanel>
                                    <template #header>
                                        <i class="fas fa-tools mr-2" aria-hidden="true"></i>
                                        <span>Daftar Alat</span>
                                        <Badge :value="dataAlatKalibrasi.length" v-if="dataAlatKalibrasi.length > 0"
                                            severity="danger" class="ml-2" />
                                    </template>

                                    <div v-if="activeTab == 0">
                                        <div class="column is-6"
                                            style="margin-left: 23rem;margin-bottom: 20px;padding: 0px;margin-top: -4.25rem;">
                                            <VDatePicker v-model="item.filterTgl" is-range color="pink" trim-weeks>
                                                <template #default="{ inputValue, inputEvents }">
                                                    <VField addons>
                                                        <VControl icon="feather:calendar">
                                                            <VInput :value="inputValue.start"
                                                                v-on="inputEvents.start" />
                                                        </VControl>
                                                        <VControl>
                                                            <VButton static><i class="fas fa-arrow-right"
                                                                    aria-hidden="true"></i></VButton>
                                                        </VControl>
                                                        <VControl icon="feather:calendar">
                                                            <VInput :value="inputValue.end" v-on="inputEvents.end" />
                                                        </VControl>
                                                    </VField>
                                                </template>
                                            </VDatePicker>
                                        </div>
                                        <div class="list-view list-view-v3">
                                            <div class="search-menu-igd mb-2">
                                                <div class="search-location-igd" style="width: 100%">
                                                    <i class="iconify" data-icon="feather:search"></i>
                                                    <input type="text"
                                                        placeholder="Cari Nama Alat, No Order Alat, No Pendaftaran"
                                                        v-model="item.qsearch"
                                                        v-on:keyup.enter="fetchAlatKalibrasi(orderAlat)" />
                                                </div>
                                                <VButton raised class="search-button-igd"
                                                    @click="fetchAlatKalibrasi(orderAlat)" :loading="isLoading">
                                                    Cari Data
                                                </VButton>
                                            </div>
                                            <VCard class="text-center pt-0 pb-0 mt-0">
                                                <VRadio v-model="orderAlat" value="0" label="Belum Disetujui"
                                                    name="outlined_radio" color="success" />
                                                <VRadio v-model="orderAlat" value="2" label="Sudah Disetujui"
                                                    name="outlined_radio" color="info" />
                                            </VCard>
                                            <VPlaceholderPage :class="[dataAlatKalibrasi.length !== 0 && 'is-hidden']"
                                                title="Tidak Ada Alat Hari Ini." subtitle="Silakan Pilih Tanggal"
                                                larger>
                                                <template #image>
                                                    <img class="light-image"
                                                        src="/@src/assets/illustrations/placeholders/search-4.png"
                                                        alt="" />
                                                    <img class="dark-image"
                                                        src="/@src/assets/illustrations/placeholders/search-4-dark.svg"
                                                        alt="" />
                                                </template>
                                            </VPlaceholderPage>
                                            <div class="list-view-inner mt-2" style="max-height:1000px;overflow: auto;">
                                                <div name="list-complete" tag="div">
                                                    <div v-for="(item, rowIndex) in dataAlatKalibrasi" :key="rowIndex">
                                                        <div
                                                            v-if="rowGroupMetadata[item.lingkupkalibrasi].index === rowIndex">
                                                            <span
                                                                style="font-weight: bold; font-size: 16px; font-family: var(--font-alt);">{{
                                                                    item.lingkupkalibrasi }}</span>
                                                            <Badge :value="rowGroupMetadata[item.lingkupkalibrasi].size"
                                                                v-if="rowGroupMetadata[item.lingkupkalibrasi].size > 0"
                                                                class="ml-2 mt-2-min" />
                                                        </div>
                                                        <div class="list-view-item ">
                                                            <div class="list-view-item-inner">
                                                                <VAvatar size="small"
                                                                    :picture="getLingkupAvatar(item.lingkupkalibrasi).picture"
                                                                    :color="getLingkupAvatar(item.lingkupkalibrasi).color"
                                                                    bordered />
                                                                <div class="meta-left">
                                                                    <h3>
                                                                        <b>{{ item.namaproduk }}</b>
                                                                        <VTag v-if="item.tanda_kalibrasi_ai == 'ADA'"
                                                                            class="ml-1"
                                                                            :label="'Kalibrasi AI Tersedia'"
                                                                            :color="'success'" />
                                                                    </h3>
                                                                    <h5>Merk/Tipe : {{ item.namamerk }}/{{ item.namatipe
                                                                    }}</h5>
                                                                    <h5>S/N : {{ item.namaserialnumber }}</h5>
                                                                    <span>
                                                                        <i aria-hidden="true" class="iconify"
                                                                            data-icon="feather:calendar"></i>
                                                                        <span>{{ item.namaperusahaan }}</span>
                                                                        <i aria-hidden="true"
                                                                            class="fas fa-circle icon-separator"></i>
                                                                        <i aria-hidden="true" class="iconify"
                                                                            data-icon="feather:calendar"></i>
                                                                        <span>{{ item.tglsetujuasmanlembarkerja
                                                                            }}</span>
                                                                        <i aria-hidden="true"
                                                                            class="fas fa-circle icon-separator"></i>
                                                                        <i aria-hidden="true" class="iconify"
                                                                            data-icon="feather:check-circle"></i>
                                                                        <span>{{ item.noorderalat }}</span>
                                                                        <i aria-hidden="true"
                                                                            class="fas fa-circle icon-separator"></i>
                                                                        <i aria-hidden="true" class="iconify"
                                                                            data-icon="feather:check-circle"></i>
                                                                        <span>{{ item.nopendaftaran }}</span>

                                                                    </span>
                                                                    <div>
                                                                        <VTag class="mr-1"
                                                                            :label="item.jenissurkesfk == 1 ? 'SURKES' : 'NON SURKES'"
                                                                            :color="item.jenissurkesfk == 1 ? 'success' : 'danger'" />
                                                                        <VTag v-if="item.isstandarulab"
                                                                            :label="'Rekalibrasi Standar Internal'"
                                                                            :color="'warning'" />
                                                                        <VTag v-if="item.iskalibrasiinternal"
                                                                            :label="'Kalibrasi Internal'"
                                                                            :color="'warning'" />
                                                                        <VTag v-if="item.durasi_proses != null"
                                                                            :label="item.durasi_proses" color="info"
                                                                            class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true"
                                                                            :label="'Sertifikat Disetujui Asman'"
                                                                            :color="'primary'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjamanager != null && item.setujuilembarkerjamanager == true && item.isverifikasi == null"
                                                                            :label="'Sertifikat Disetujui Manager'"
                                                                            :color="'primary'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true && item.isverifikasi == true"
                                                                            :label="'Laporan Verifikasi Disetujui Asman'"
                                                                            :color="'success'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjamanager != null && item.setujuilembarkerjamanager == true && item.isverifikasi == true"
                                                                            :label="'Laporan Verifikasi Disetujui Manager'"
                                                                            :color="'success'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.asmansetujulaporanrepairfk != null"
                                                                            :label="'Laporan Repair Disetujui Asman'"
                                                                            :color="'info'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.managersetujulaporanrepairfk != null"
                                                                            :label="'Laporan Repair Disetujui Manager'"
                                                                            :color="'info'" class="ml-2" />
                                                                    </div>
                                                                    <div>
                                                                        <span style="font-weight: bold;">Penyelia Teknik
                                                                            :
                                                                            {{ item.penyeliateknik ?? '-' }}
                                                                        </span>
                                                                    </div>
                                                                    <div>
                                                                        <span style="font-weight: bold;">Pelaksana
                                                                            Teknik :
                                                                            {{ item.pelaksanateknik ?? '-' }}
                                                                        </span>
                                                                    </div>
                                                                    <div v-if="item.statusrepairfk == 2 && item.jenisorder === 'repair'"
                                                                        class="gagal-repair-anim">
                                                                        <svg viewBox="0 0 32 32" fill="none">
                                                                            <circle cx="16" cy="16" r="16"
                                                                                fill="#e57373" />
                                                                            <path d="M10 10L22 22M22 10L10 22"
                                                                                stroke="#fff" stroke-width="3.2"
                                                                                stroke-linecap="round" />
                                                                        </svg>
                                                                        GAGAL REPAIR
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    class="meta-right flex justify-center items-center">
                                                                    <div class="buttons">
                                                                        <VIconButton
                                                                            v-if="(item.setujuilembarkerjamanager != null || item.setujuilembarkerjamanager == true) && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                                                            v-tooltip.bottom.left="'Cetak Sertifikat'"
                                                                            icon="feather:printer"
                                                                            @click="cetakSertifikatLembarKerja(item)"
                                                                            color="info" raised circle class="mr-2">
                                                                        </VIconButton>
                                                                        <VIconButton
                                                                            v-if="item.setujuilembarkerjamanager != null && item.setujuilembarkerjamanager == true && item.jenisorder == 'kalibrasi' && item.isverifikasi == true"
                                                                            v-tooltip.bottom.left="'Cetak Laporan Verifikasi'"
                                                                            icon="feather:printer"
                                                                            @click="cetakLaporanVerfikasi(item)"
                                                                            color="success" raised circle class="mr-2">
                                                                        </VIconButton>
                                                                        <VIconButton
                                                                            v-if="item.sublingkupfk != null && item.lingkupfk != 2 && (item.setujuilembarkerjamanager == null || item.setujuilembarkerjamanager == false) && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                                                            color="info" circle icon="fas fa-pager"
                                                                            outlined raised
                                                                            @click="masukLembarKerjaSudahIsi(item)"
                                                                            v-tooltip.bottom.left="'Lembar Kerja'" />
                                                                        <VIconButton
                                                                            v-if="(item.setujuilembarkerjamanager == null || item.setujuilembarkerjamanager == false) && item.jenisorder == 'kalibrasi' && item.isverifikasi == true"
                                                                            color="success" circle icon="fas fa-file"
                                                                            outlined raised
                                                                            @click="masukLaporanVerifikasi(item)"
                                                                            v-tooltip.bottom.left="'Laporan Verifikasi'" />
                                                                        <VIconButton
                                                                            v-if="item.lingkupfk == 2 && (item.setujuilembarkerjamanager == null || item.setujuilembarkerjamanager == false) && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                                                            color="info" circle icon="fas fa-pager"
                                                                            outlined raised
                                                                            @click="lembarKerjaTekanan(item)"
                                                                            v-tooltip.bottom.left="'Lembar Kerja'" />
                                                                        <VIconButton color="warning" circle
                                                                            v-if="item.managersetujulaporanrepairfk == null && item.jenisorder == 'repair'"
                                                                            icon="fas fa-tools" outlined raised
                                                                            @click="laporanRepair(item)"
                                                                            v-tooltip.bottom.left="'Laporan Repair'" />
                                                                        <VIconButton
                                                                            v-if="item.managersetujulaporanrepairfk != null && item.jenisorder == 'repair'"
                                                                            v-tooltip.bottom.left="'Cetak Laporan Repair'"
                                                                            icon="feather:printer"
                                                                            @click="cetakLaporanRepair(item)"
                                                                            color="success" raised circle class="mr-2">
                                                                        </VIconButton>
                                                                        <VIconButton v-tooltip.bottom.left="'Aktivitas'"
                                                                            icon="feather:activity"
                                                                            @click="detailOrder(item)" color="info"
                                                                            raised circle class="mr-2">
                                                                        </VIconButton>
                                                                        <VIconButton
                                                                            v-tooltip.bottom.left="'Riwayat Amandemen'"
                                                                            icon="feather:repeat"
                                                                            v-if="item.versisertifikat > 1 || item.versilaporanrepair > 1"
                                                                            @click="riwayatAmandemen(item)"
                                                                            color="warning" raised circle class="mr-2">
                                                                        </VIconButton>
                                                                        <VIconButton
                                                                            v-if="item.tanda_kalibrasi_ai == 'ADA' && (item.setujuilembarkerjamanager == null || item.setujuilembarkerjamanager == false) && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                                                            color="info" circle icon="fas fa-robot"
                                                                            outlined raised
                                                                            @click="masukLembarKerjaSudahIsi(item)"
                                                                            v-tooltip.bottom.left="'Lembar Kerja AI'" />
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
                                <TabPanel>
                                    <template #header>
                                        <i class="fas fa-users mr-2" aria-hidden="true"></i>
                                        <span>Daftar Unit Mendaftar</span>
                                        <Badge :value="dataOrder.length" v-if="dataOrder.length > 0" severity="danger"
                                            class="ml-2" />
                                    </template>

                                    <div v-if="activeTab == 1">
                                        <div class="column is-6"
                                            style="margin-left: 23rem;margin-bottom: 20px;padding: 0px;margin-top: -4.25rem;">
                                            <VDatePicker v-model="item.filterTgl" is-range color="pink" trim-weeks>
                                                <template #default="{ inputValue, inputEvents }">
                                                    <VField addons>
                                                        <VControl icon="feather:calendar">
                                                            <VInput :value="inputValue.start"
                                                                v-on="inputEvents.start" />
                                                        </VControl>
                                                        <VControl>
                                                            <VButton static><i class="fas fa-arrow-right"
                                                                    aria-hidden="true"></i></VButton>
                                                        </VControl>
                                                        <VControl icon="feather:calendar">
                                                            <VInput :value="inputValue.end" v-on="inputEvents.end" />
                                                        </VControl>
                                                    </VField>
                                                </template>
                                            </VDatePicker>

                                        </div>

                                        <div class="list-view list-view-v3">
                                            <div class="search-menu-igd mb-2">
                                                <div class="search-location-igd" style="width: 100%">
                                                    <i class="iconify" data-icon="feather:search"></i>
                                                    <input type="text"
                                                        placeholder="Cari Nama Perusahaan, No Pendaftaran"
                                                        v-model="item.search"
                                                        v-on:keyup.enter="fetchDataOrder(order)" />
                                                </div>
                                                <VButton raised class="search-button-igd" @click="fetchDataOrder(order)"
                                                    :loading="isLoading"> Cari Data
                                                </VButton>
                                            </div>
                                            <VPlaceholderPage :title="H.assets().notFound"
                                                :subtitle="H.assets().notFoundSubtitle" class="my-6"
                                                :class="[dataOrder.length !== 0 && 'is-hidden']">
                                                <template #image>
                                                    <img class="light-image" :src="H.assets().iconNotFound_rev"
                                                        alt="" />
                                                    <img class="dark-image"
                                                        src="/@src/assets/illustrations/placeholders/search-4-dark.svg"
                                                        alt="" />
                                                </template>
                                            </VPlaceholderPage>
                                            <div class="list-view-inner mt-2"
                                                style="max-height:500px;overflow: auto; margin-top: 1rem; ">
                                                <TransitionGroup name="list-complete" tag="div">
                                                    <div v-for="(items, m) in dataOrder" :key="m"
                                                        class="list-view-item">
                                                        <div class="list-view-item-inner">
                                                            <VAvatar size="small" style="left: 8px;top: 4px;"
                                                                :color="listColor[i]" :initials="items.initials" />
                                                            <div class="meta-left">
                                                                <h3>
                                                                    {{ items.namaperusahaan }} <i
                                                                        aria-hidden="true"></i>
                                                                </h3>
                                                                <span style="color: black">
                                                                    <i aria-hidden="true" class="iconify"
                                                                        data-icon="teenyicons:id-outline"></i>
                                                                    <span> {{ items.nopendaftaran }}</span>
                                                                    <i aria-hidden="true"
                                                                        class="fas fa-circle icon-separator"></i>
                                                                    <i aria-hidden="true" class="iconify"
                                                                        data-icon="feather:calendar"></i>
                                                                    <span>{{ items.tglregistrasi }}</span>
                                                                </span>
                                                                <br>
                                                                <VTag v-if="items.jenisorder == 'repair'"
                                                                    color="warning" rounded>Repair</VTag>
                                                                <VTag v-if="items.jenisorder == 'kalibrasi'"
                                                                    color="info" rounded>Kalibrasi</VTag>
                                                            </div>
                                                            <div class="meta-right">
                                                                <VIconButton v-tooltip.bottom.left="'Detail'"
                                                                    label="Bottom Left" color="primary" circle
                                                                    icon="pi pi-book" @click="getDetailVerify(items)"
                                                                    style="margin-right: 15px;" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </TransitionGroup>
                                            </div>
                                        </div>
                                    </div>
                                    <VFlexPagination v-model:current-page="currentPage.page"
                                        :item-per-page="currentPage.limit" :total-items="totalData"
                                        :max-links-displayed="5">
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
                            </TabView>
                        </div>
                        <div class="column is-4" style="margin-top: 4rem;">
                            <VTabs slider selected="Jakarta" :tabs="[
                                { label: 'Jakarta', value: 'Jakarta', icon: 'fas fa-users' },
                                { label: 'Gresik', value: 'Gresik', icon: 'fas fa-users' },
                            ]" style="margin-top: -2rem;">
                                <template #tab="{ activeValue }">
                                    <p v-if="activeValue === 'Jakarta'">
                                        <!-- <div class="tile-grid tile-grid-v2"> -->
                                        <VCard class="mt-4">
                                            <!-- <div class="dashboard-card"> -->
                                            <ApexChart :options="UnitOptionsLingkup" :series="UnitOptionsLingkup.series"
                                                type="pie" height="420" width="100%" />
                                            <!-- </div> -->
                                        </VCard>
                                        <!-- </div> -->
                                    </p>
                                    <p v-else-if="activeValue === 'Gresik'">
                                        <!-- <div class="tile-grid tile-grid-v2"> -->
                                        <VCard class="mt-4">
                                            <!-- <div class="dashboard-card"> -->
                                            <ApexChart :options="UnitOptionsLingkupGresik"
                                                :series="UnitOptionsLingkupGresik.series" type="pie" height="420"
                                                width="100%" />
                                            <!-- </div> -->
                                        </VCard>
                                        <!-- </div> -->
                                    </p>
                                </template>
                            </VTabs>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <VModal :open="modalFilter" title="Filter Periode" :noclose="true" size="small" actions="right"
            @close="modalFilter = false">
            <template #content>
                <form class="modal-form">
                    <div class="columns">
                        <div class="column is-12" style="text-align: center">
                            <VField class="is-centered">
                                <v-date-picker v-model="item.periode" class="is-centered" is-range trim-weeks />
                            </VField>
                        </div>
                    </div>
                </form>
            </template>
            <template #action>
                <VButton icon="feather:search" @click="changePeriode()" :loading="isLoading" color="primary" raised>
                    Filter</VButton>
            </template>
        </VModal>
        <VModal :open="modalRiwayat" title="" noclose size="big" actions="right" @close="modalRiwayat = false, clear()"
            cancelLabel="Tutup">
            <template #content>
                <div class="column">
                    <div class="business-dashboard hr-dashboard">
                        <div class="columns is-multiline">
                            <div class="column is-12 p-0">
                                <div class="block-header">
                                    <div class="left column is-6 p-0">
                                        <div class="current-user">
                                            <h3>{{ item.namaproduk }}</h3>
                                        </div>
                                    </div>
                                    <div class="center column is-6 p-0">
                                        <div>
                                            <div>
                                                <h4 class="block-heading">Merk/Tipe</h4>
                                                <p class="block-hext">{{ item.namamerk }}/{{ item.namatipe }}</p>
                                                <h4 class="block-heading">S/N</h4>
                                                <p class="block-hext">{{ item.namaserialnumber }}</p>
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
                                    <div class="timeline-item is-unread" v-for="(item, index) in timelineItems"
                                        :key="index">
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
        <VModal :open="modalPengajuanAmandemen" title="Pengajuan Amandemen" noclose size="big" actions="right"
            @close="modalPengajuanAmandemen = false" cancelLabel="Tutup">
            <template #content>

                <VCard>
                    <div class="is-flex is-align-items-center is-justify-content-space-between mb-2">
                        <h3 class="title is-5 mb-2">Riwayat Pengajuan Amandemen</h3>
                        <div class="is-flex is-align-items-center" style="gap:.5rem">
                            <VTag :color="'warning'" rounded>Diajukan</VTag>
                            <VTag :color="'success'" rounded>Disetujui</VTag>
                            <VTag :color="'danger'" rounded>Ditolak</VTag>
                        </div>
                    </div>
                    <div>
                        <DataTable :value="dataPengajuanAmandemen" class="p-datatable-sm" :loading="isLoading"
                            :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]" scrollable
                            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

                            <Column field="no" header="#" style="width:70px;min-width:70px"></Column>
                            <Column field="Aksi" header="Aksi" style="min-width: 120px; text-align: center;">
                                <template #body="slotProps">
                                    <VIconButton v-if="(slotProps.data.statusamandemen == 3)"
                                        v-tooltip.top.left="'Setujui'" color="success" outlined circle
                                        icon="fas fa-check" @click="confirmApproval(slotProps.data, 5)" />
                                    <VIconButton v-if="(slotProps.data.statusamandemen == 3)"
                                        v-tooltip.top.left="'Tolak'" class="ml-3" color="warning" outlined circle
                                        icon="feather:x" @click="confirmApproval(slotProps.data, 6)" />
                                </template>
                            </Column>
                            <Column field="status" header="Status" :sortable="true" style="min-width:140px">
                                <template #body="slotProps">
                                    <VTag class="ml-2" :color="slotProps.data.color" rounded>{{ slotProps.data.status }}
                                    </VTag>
                                </template>
                            </Column>
                            <Column field="alasanpengajuan" header="Alasan Pengajuan" :sortable="true"
                                style="min-width:140px">
                            </Column>
                            <Column field="tglpengajuanamandemen" header="Tanggal Pengajuan" :sortable="true"
                                style="min-width:140px">
                                <template #body="slotProps">
                                    <span>
                                        {{ H.formatDateToLocalString(slotProps.data.tglpengajuanamandemen) }}
                                    </span>
                                </template>
                            </Column>
                            <Column field="namaproduk" header="Nama Alat" :sortable="true" style="min-width:100px">
                            </Column>
                            <Column field="namamerk" header="Merk Alat" :sortable="true" style="min-width:100px">
                            </Column>
                            <Column field="namatipe" header="Tipe Alat" :sortable="true" style="min-width:100px">
                            </Column>
                            <Column field="namaserialnumber" header="SN" :sortable="true" style="min-width:100px">
                            </Column>
                            <Column field="penyeliateknik" header="Penyelia Teknik" :sortable="true"
                                style="min-width:100px">
                            </Column>
                            <Column field="pelaksanateknik" header="Pelaksana Teknik" :sortable="true"
                                style="min-width:100px">
                            </Column>
                        </DataTable>
                    </div>
                </VCard>

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
    </div>
    <WelcomeBannerModalV v-model="openWelcome" :images="[
        '/welcome1.png',
        '/welcome2.png'
    ]" title="Seputar Layanan" subtitle="Info terbaru sebelum menggunakan web"
        storage-key="ulab_welcome_banner_customer_v1" />
</template>
<script setup lang="ts">
import ApexChart from 'vue3-apexcharts'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive, onMounted } from 'vue'
import { useThemeColors } from '/@src/composable/useThemeColors'
import { useHead } from '@vueuse/head'
import { useUserSession } from '/@src/stores/userSession'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import moment, { isDate } from 'moment'
import { useToaster } from '/@src/composable/toaster'
import Fieldset from 'primevue/fieldset'
import Dropdown from 'primevue/dropdown'
import * as H from '/@src/utils/appHelper'
import Badge from 'primevue/badge';
import * as qzService from '/@src/utils/qzTrayService'
import AutoComplete from 'primevue/autocomplete';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import WelcomeBannerModalV from '/@src/components/WelcomeBannerModalV.vue'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from "primevue/useconfirm"
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

useHead({
    title: 'Dashboard Manager - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const themeColors = useThemeColors()
const dataSource: any = ref([])
const filters = ref('')
let modalRiwayat: any = ref(false)
let isLoadDataDeatilOrder: any = ref(false)
const timelineItems = ref([])
let chartOP: any = ref({
    series: [],
})
const rowGroupMetadata = ref({})
const currentPage: any = ref({
    limit: 5,
    rows: 50,
})
const openWelcome = ref(false)
var date = new Date();
const dateNow = date.toLocaleString('id-ID', { year: "numeric", month: "long", day: "numeric" });
let listColor: any = ref(Object.keys(useThemeColors()))
const modalDetail = ref(false)
const route = useRoute()
const userLogin = useUserSession().getUser()
let statusOrder: any = ref([])
let isLoading: any = ref(false)
let modalFilter: any = ref(false)
let dataPegawaiJakarta: any = ref([])
let dataPegawaiGresik: any = ref([])
let totalData: any = ref(0)
let isData: any = ref()
let dataAlatKalibrasi: any = ref([])
const activeTab = ref(0)
const item: any = ref({
    periode: reactive({
        start: new Date(),
        end: new Date(),
    }),
    filterTgl: reactive({
        start: new Date(),
        end: new Date(),
    }),
})
const order: any = ref(0)
const orderAlat: any = ref(0)
const dataOrder: any = ref(0)
const router = useRouter()
const FILTER_KEY = 'manager_dashboard_filters_v1'
type AvatarColor = 'primary' | 'info' | 'success' | 'warning' | 'danger'
type LingkupAvatar = { picture: string; color: AvatarColor }
const DEFAULT_AVATAR: LingkupAvatar = {
    picture: '/images/avatars/svg/propinsi.svg',
    color: 'primary',
}
let modalPengajuanAmandemen: any = ref(false)
const confirm = useConfirm();
const dataPengajuanAmandemen = ref<any[]>([])
const approvalError = ref('')
const approvalSubmitting = ref(false)
const pendingAmandemenCount = ref(0)
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
        const response = await useApi().get(`/manager/riwayat-amandemen?norec_detail=${e.norec_detail}`)
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


const openPengajuan = async (e: any) => {
    modalPengajuanAmandemen.value = true
    await fetchDataRiwayat()
}

const confirmApproval = (row: any, statusBaru: 5 | 6) => {
    const isApprove = statusBaru === 5
    confirm.require({
        message: `Yakin ingin ${isApprove ? 'MENYETUJUI' : 'MENOLAK'} pengajuan ini?`,
        header: isApprove ? 'Konfirmasi Persetujuan' : 'Konfirmasi Penolakan',
        icon: 'pi pi-question-circle',
        acceptLabel: isApprove ? 'Setujui' : 'Tolak',
        rejectLabel: 'Batal',
        acceptClass: isApprove ? 'p-button-success' : 'p-button-danger',
        rejectClass: 'p-button-text',
        accept: () => submitApprovalWithNote(row, statusBaru),
        reject: () => { },
    })
};

const submitApprovalWithNote = async (row: any, statusBaru: 5 | 6) => {
    approvalError.value = ''

    approvalSubmitting.value = true
    try {
        await useApi().post('manager/approval-pengajuan-amandemen', {
            norec: row.norec_detail,
            status: statusBaru,
            noorderalat: row.noorderalat
        })
        await fetchDataRiwayat()
    } catch (e) {
        approvalError.value = 'Gagal menyimpan approval. Coba lagi.'
    } finally {
        approvalSubmitting.value = false
    }
}

const fetchDataRiwayat = async () => {
    isLoading.value = true
    try {
        const res = await useApi().get('manager/get-pengajuan-amandemen')
        const rows = res?.data ?? []
        pendingAmandemenCount.value = rows.filter((x: any) => x.statusamandemen == 3).length

        dataPengajuanAmandemen.value = rows.map((it: any, idx: number) => {
            const statusText =
                it?.statusamandemen === 1 ? 'Disetujui Penyelia' :
                    it?.statusamandemen === 2 ? 'Ditolak Penyelia' :
                        it?.statusamandemen === 3 ? 'Diajukan Asman' :
                            it?.statusamandemen === 4 ? 'Ditolak Asman' :
                                it?.statusamandemen === 5 ? 'Disetujui Manager' :
                                    it?.statusamandemen === 6 ? 'Ditolak Manager' : ''

            const color =
                it?.statusamandemen === 1 ? 'success' :
                    it?.statusamandemen === 2 ? 'danger' :
                        it?.statusamandemen === 3 ? 'warning' :
                            it?.statusamandemen === 4 ? 'danger' :
                                it?.statusamandemen === 5 ? 'success' :
                                    it?.statusamandemen === 6 ? 'danger' : ''

            return {
                ...it,
                no: idx + 1,
                status: statusText,
                color,
            }
        })
    } catch (e) {
        console.error('Error fetching data riwayat:', e)
    } finally {
        isLoading.value = false
    }
}

const UnitOptionsLingkup = ref<any>({
    series: [],
    chart: {
        type: 'donut',
        height: 420,
        toolbar: {
            show: false,
            tools: {
                download: true,
                customIcons: [
                    { icon: 'DETAIL', index: 0, title: 'Detail per Lingkup', class: 'custom-icon', click: () => { detailOpenGlobalJrs.value = true } }
                ],
            },
        },
    },
    labels: [
        'Kelistrikan', 'Tekanan', 'Waktu & Frekuensi', 'Suhu & Kelembaban', 'Vibrasi', 'Dimensi', 'Gaya', 'Repair',
    ],
    colors: [
        themeColors.orange, themeColors.success, themeColors.yellow, themeColors.purple,
        themeColors.info, themeColors.warning, themeColors.danger, themeColors.secondary,
    ],
    title: { text: 'Jumlah Order ULAB Jakarta', align: 'left' },

    legend: {
        position: 'bottom',
        horizontalAlign: 'center',
        fontSize: '13px',
        itemMargin: { horizontal: 10, vertical: 6 },
    },

    plotOptions: {
        pie: {
            donut: { size: '65%' },
            offsetY: 0,
        },
    },

    dataLabels: { enabled: true },
    tooltip: { y: { formatter: (val: number) => new Intl.NumberFormat('id-ID').format(val) } },
})


const UnitOptionsLingkupGresik = ref<any>({
    series: [],
    chart: {
        type: 'donut',
        height: 420,
        toolbar: {
            show: false,
            tools: {
                download: true,
                customIcons: [
                    { icon: 'DETAIL', index: 0, title: 'Detail per Lingkup', class: 'custom-icon', click: () => { detailOpenGlobalJrs.value = true } }
                ],
            },
        },
    },
    labels: [
        'Kelistrikan', 'Tekanan', 'Waktu & Frekuensi', 'Suhu & Kelembaban', 'Vibrasi', 'Dimensi', 'Gaya', 'Repair',
    ],
    colors: [
        themeColors.orange, themeColors.success, themeColors.yellow, themeColors.purple,
        themeColors.info, themeColors.warning, themeColors.danger, themeColors.secondary,
    ],
    title: { text: 'Jumlah Order ULAB Gresik', align: 'left' },

    legend: {
        position: 'bottom',
        horizontalAlign: 'center',
        fontSize: '13px',
        itemMargin: { horizontal: 10, vertical: 6 },
    },

    plotOptions: {
        pie: {
            donut: { size: '65%' },
            offsetY: 0,
        },
    },

    dataLabels: { enabled: true },
    tooltip: { y: { formatter: (val: number) => new Intl.NumberFormat('id-ID').format(val) } },
})



const allLabels = [
    'Kelistrikan', 'Tekanan', 'Waktu & Frekuensi', 'Suhu & Kelembaban', 'Vibrasi', 'Dimensi', 'Gaya', 'Repair',
];

const fetchDataChart = async () => {

    const response = await useApi().get(`asman/get-data-chart-dashboard`)

    const lingkup = response.jumlahAlatLingkup?.[0] ?? {}
    const rawSeries = [
        lingkup.kelistrikan ?? 0,
        lingkup.tekanan ?? 0,
        lingkup['waktu & frekuensi'] ?? 0,
        lingkup['suhu & kelembaban'] ?? 0,
        lingkup.vibrasi ?? 0,
        lingkup.dimensi ?? 0,
        lingkup.gaya ?? 0,
        lingkup.repair ?? 0,
    ];

    const filteredLabels: string[] = [];
    const filteredSeries: number[] = [];
    rawSeries.forEach((val, i) => {
        if (val > 0) {
            filteredSeries.push(val);
            filteredLabels.push(allLabels[i]);
        }
    });
    UnitOptionsLingkup.value.series = filteredSeries;
    UnitOptionsLingkup.value.labels = filteredLabels;

    const lingkupgresik = response.jumlahAlatLingkupGresik?.[0] ?? {}
    const rawSeriesgresik = [
        lingkupgresik.kelistrikan ?? 0,
        lingkupgresik.tekanan ?? 0,
        lingkupgresik['waktu & frekuensi'] ?? 0,
        lingkupgresik['suhu & kelembaban'] ?? 0,
        lingkupgresik.vibrasi ?? 0,
        lingkupgresik.dimensi ?? 0,
        lingkupgresik.gaya ?? 0,
        lingkupgresik.repair ?? 0,
    ];

    const filteredLabelsGresik: string[] = [];
    const filteredSeriesGresik: number[] = [];
    rawSeriesgresik.forEach((val, i) => {
        if (val > 0) {
            filteredSeriesGresik.push(val);
            filteredLabelsGresik.push(allLabels[i]);
        }
    });

    UnitOptionsLingkupGresik.value.series = filteredSeriesGresik;
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

const makeYearRange = (d = new Date()) => {
    const y = d.getFullYear()
    return {
        start: new Date(y, 0, 1, 0, 0, 0, 0),
        end: new Date(y, 11, 31, 23, 59, 59, 999),
    }
}

const saveFilters = () => {
    try {
        const payload = {
            filterTgl: {
                start: item.value.filterTgl?.start ? new Date(item.value.filterTgl.start).toISOString() : null,
                end: item.value.filterTgl?.end ? new Date(item.value.filterTgl.end).toISOString() : null,
            },
            qsearch: item.value.qsearch ?? '',
            search: item.value.search ?? '',
            order: order.value,
            orderAlat: orderAlat.value,
        }
        localStorage.setItem(FILTER_KEY, JSON.stringify(payload))
    } catch { }
}

const loadFilters = () => {
    const def = makeYearRange()
    item.value.filterTgl.start = def.start
    item.value.filterTgl.end = def.end
    item.value.periode.start = def.start
    item.value.periode.end = def.end

    try {
        const raw = localStorage.getItem(FILTER_KEY)
        if (!raw) return
        const parsed = JSON.parse(raw)
        if (parsed?.filterTgl?.start) item.value.filterTgl.start = new Date(parsed.filterTgl.start)
        if (parsed?.filterTgl?.end) item.value.filterTgl.end = new Date(parsed.filterTgl.end)
        item.value.periode.start = item.value.filterTgl.start
        item.value.periode.end = item.value.filterTgl.end
        if (typeof parsed?.qsearch === 'string') item.value.qsearch = parsed.qsearch
        if (typeof parsed?.search === 'string') item.value.search = parsed.search
        if (parsed?.order !== undefined) order.value = parsed.order
        if (parsed?.orderAlat !== undefined) orderAlat.value = parsed.orderAlat
    } catch { }
}

watch(() => [item.value.filterTgl?.start, item.value.filterTgl?.end], () => { saveFilters() })
watch(() => item.value.qsearch, () => saveFilters())
watch(() => item.value.search, () => saveFilters())
watch(() => order.value, () => saveFilters())
watch(() => orderAlat.value, () => saveFilters())

currentPage.value.page = computed(() => {
    try {
        return Number.parseInt(route.query.page as string) || 1
    } catch { }
    return 1
})
watch(
    () => currentPage.value.page,
    (newValue, oldValue) => {
        if (newValue != oldValue) {
            fetchDataOrder(0)
        }
    }
)
watch(
    () => currentPage.value.limit,
    (newValue, oldValue) => {
        if (newValue != oldValue) {
            fetchDataOrder(0)
        }
    }
)

const fetchAlatKalibrasi = async (q: any) => {
    let dari = ''
    if (item.value.filterTgl.start) {
        dari = H.formatDate(item.value.filterTgl.start, 'YYYY-MM-DD')
    }
    let sampai = ''
    if (item.value.filterTgl.end) {
        sampai = H.formatDate(item.value.filterTgl.end, 'YYYY-MM-DD')
    }
    let statusordermanager = ''
        , search = ''
    item.value.statusordermanager = q
    if (orderAlat) statusordermanager = '&statusordermanager=' + q
    if (item.value.qsearch) search = encodeURIComponent(item.value.qsearch)
    isLoading.value = true
    dataAlatKalibrasi.value = []
    const response = await useApi().get(
        '/manager/get-alat-manager?dari=' + dari
        + '&sampai=' + sampai
        + '&search=' + search
        + statusordermanager
    )
    isLoading.value = false
    dataAlatKalibrasi.value = response.data
    updateRowGroupMetaData();
}

const fetchDataOrder = async (q: any) => {
    statusOrder.value = q
    isLoading.value = true
    const dari = 'dari=' + H.formatDate(item.value.filterTgl.start, 'YYYY-MM-DD 00:00')
    const sampai = '&sampai=' + H.formatDate(item.value.filterTgl.end, 'YYYY-MM-DD 23:59')
    let search = ''
    let StatusOrder = ''
    item.value.statusOrder = q
    if (order) StatusOrder = '&statusorder=' + q
    if (item.value.search) search = '&search=' + item.value.search
    let limit: any = currentPage.value.limit
    let offset: any = route.query.page ? route.query.page : 1
    offset = (parseInt(offset) - 1) * limit
    let page: any = route.query.page ? route.query.page : 1

    await useApi().get(`manager/list-mitra-regis?page=${page}&offset=${offset}&limit=${limit}&rows=${currentPage.value.rows}&` + dari + sampai + StatusOrder + search).then((response) => {
        modalFilter.value = false
        dataOrder.value = response.data
        totalData.value = response.total
        response.data.forEach((element: any, i: any) => {
            element.no = i + 1
            let ini = element.namaperusahaan.split(' ')
            let init = element.namaperusahaan.substr(0, 2)
            if (ini.length > 1) {
                init = init + ini[1].substr(0, 1)
            }
            element.initials = init
        });
        isData.value = response.total
        isLoading.value = false
    }).catch((err) => {
        modalFilter.value = false
        isLoading.value = false
    })
}

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
        fetchAlatKalibrasi(0)
    }
    if (activeTab.value == 1) {
        fetchDataOrder(0)
    }
}

const masukLembarKerjaSudahIsi = (e: any) => {
    if (e.namasublingkup == 'METER') {
        router.push({
            name: 'module-manager-lembar-kerja',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'SUMBER') {
        router.push({
            name: 'module-manager-lembar-kerja-sumber',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CLAMP') {
        router.push({
            name: 'module-manager-lembar-kerja-clamp',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'VIBRATION METER') {
        router.push({
            name: 'module-manager-lembar-kerja-vibration-meter',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'ACCELLEROMETER') {
        router.push({
            name: 'module-manager-lembar-kerja-accellerometer',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CLAMP & METER') {
        router.push({
            name: 'module-manager-lembar-kerja-clamp-meter',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'METER & SUMBER') {
        router.push({
            name: 'module-manager-lembar-kerja-meter-sumber',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THERMOMETER INFRARED') {
        router.push({
            name: 'module-manager-lembar-kerja-termometer-infrared',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THERMAL IMAGER') {
        router.push({
            name: 'module-manager-lembar-kerja-thermal-imager',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THERMOHYGROMETER') {
        router.push({
            name: 'module-manager-lembar-kerja-thermohygrometer',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'DRYBLOCK') {
        router.push({
            name: 'module-manager-lembar-kerja-dryblock',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'VIBRATION CALIBRATOR') {
        router.push({
            name: 'module-manager-lembar-kerja-vibration-calibrator',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CALIPER') {
        router.push({
            name: 'module-manager-lembar-kerja-caliper-micrometer',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'DIAL INDICATOR') {
        router.push({
            name: 'module-manager-lembar-kerja-dial-indicator',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'BORE GAUGE') {
        router.push({
            name: 'module-manager-lembar-kerja-bore-gauge',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CLAMP & METER & SUMBER') {
        router.push({
            name: 'module-manager-lembar-kerja-clamp-meter-sumber',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (['KALIBRASI AI', 'KALIBRASI AI SUHU'].includes(e.namasublingkup)) {
        router.push({
            name: 'module-manager-lembar-kerja-yolo',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CONTINUITY +SOURCE') {
        router.push({
            name: 'module-manager-lembar-kerja-continuity-+source',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'TACHOMETER') {
        router.push({
            name: 'module-manager-lembar-kerja-tachometer',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'INSULATION + METER') {
        router.push({
            name: 'module-manager-lembar-kerja-insulation-meter-resistansi',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'TORQUE WRENCH') {
        router.push({
            name: 'module-manager-lembar-kerja-torque-wrench',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'OSCILLOSCOPE') {
        router.push({
            name: 'module-manager-lembar-kerja-oscilloscope',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'TEMPERATURE INDICATOR WITH SENSOR') {
        router.push({
            name: 'module-manager-lembar-kerja-temperature-indicator-with-sensor',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'ULTRASONIC THICKNESS') {
        router.push({
            name: 'module-manager-lembar-kerja-ultrasonic-thickness',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THICKNESS GAUGE') {
        router.push({
            name: 'module-manager-lembar-kerja-thickness-gauge',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
} else if (e.namasublingkup == 'FEELER GAUGE') {
        router.push({
            name: 'module-manager-lembar-kerja-feeler-gauge',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'INSULATION TESTER') {
        router.push({
            name: 'module-manager-lembar-kerja-insulation-tester',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'INSULATION SOURCE') {
        router.push({
            name: 'module-manager-lembar-kerja-insulation-source',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THERMOCOUPLE/RTD SIMULATOR') {
        router.push({
            name: 'module-manager-lembar-kerja-thermocouple-rtd-simulator',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'MICROMETER HEAD') {
        router.push({
            name: 'module-manager-lembar-kerja-micrometer-head',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'OUTSIDE MICROMETER') {
        router.push({
            name: 'module-manager-lembar-kerja-outside-micrometer',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    }
}

const lembarKerjaTekanan = (e: any) => {
    router.push({
        name: 'module-manager-lembar-kerja-tekanan',
        query: {
            norec: e.norec,
            norec_detail: e.norec_detail
        }
    })
}

const laporanRepair = (e: any) => {
    router.push({
        name: 'module-manager-laporan-repair',
        query: {
            norec: e.norec,
            norec_detail: e.norec_detail,
        }
    })
}


const getDetailVerify = (e: any) => {
    router.push({
        name: 'module-manager-detail-registrasi',
        query: {
            norec_pd: e.iddetail,
        },
    })
}

const detailOrder = async (e) => {
    modalRiwayat.value = true
    item.value.namaproduk = e.namaproduk
    item.value.namamerk = e.namamerk
    item.value.namatipe = e.namatipe
    item.value.namaserialnumber = e.namaserialnumber
    item.value.durasikalbrasi = e.durasikalbrasi
    isLoadDataDeatilOrder.value = true
    const response = await useApi().get(`/manager/detail-produk?norec_pd=${e.norec_detail}`)
    timelineItems.value = response.timeline
    isLoadDataDeatilOrder.value = false
}


const changeSwitch = (e: any) => {
    fetchDataOrder(e)
}

const changeSwitchAlat = (e: any) => {
    fetchAlatKalibrasi(e)
}

const clear = () => {
    item.value.id = ''
    delete item.value.no
    item.value.pelaksana = ''
    item.value.lokasikalibrasi = ''
    item.value.lingkupkalibrasi = ''
    item.value.penyeliateknik = ''
    item.value.durasikalbrasi = ''
}

const cetakLaporanVerfikasi = (e: any) => {
    H.printBlade(`manager/cetak-laporan-verifikasi?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`)
}

const masukLaporanVerifikasi = async (e: any) => {
    router.push({
        name: 'module-manager-laporan-verifikasi',
        query: {
            norec: e.norec,
            norec_detail: e.norec_detail
        }
    })
}

const cetakSertifikatLembarKerja = (e: any) => {
    if (e.versisertifikat == null) {
        H.printBlade(`manager/cetak-sertifikat-lembar-kerja?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
    } else {
        H.printBlade(`registrasi/cetak-sertif-customer-pdf?norec_detail=${e.norec_detail}`)
    }

}

const cetakLaporanRepair = (e: any) => {
    if (e.versilaporanrepair == null) {
        H.printBlade(`manager/cetak-laporan-repair?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
    } else {
        H.printBlade(`registrasi/cetak-laporan-repair-pdf?norec_detail=${e.norec_detail}`)
    }
}

// Sinkron modal filter dengan filterTgl & terapkan
const showModalFilter = () => {
    item.value.periode = {
        start: item.value.filterTgl.start,
        end: item.value.filterTgl.end,
    }
    modalFilter.value = true
}

const changePeriode = () => {
    item.value.filterTgl = {
        start: item.value.periode.start,
        end: item.value.periode.end,
    }
    saveFilters()
    modalFilter.value = false
    if (activeTab.value === 0) fetchAlatKalibrasi(orderAlat.value)
    if (activeTab.value === 1) fetchDataOrder(order.value)
}

const reload = async () => {
    fetchDataOrder(0)
}

watch(
    () => [
        order.value
    ], () => {
        changeSwitch(order.value)
    }
)

watch(
    () => [
        orderAlat.value
    ], () => {
        changeSwitchAlat(orderAlat.value)
    }
)

// init: load default/persisted filters lalu fetch
onMounted(() => {
    const key = 'ulab_welcome_banner_customer_v1'
    if (localStorage.getItem(key) !== '1') {
        openWelcome.value = true
    }
})

loadFilters()
fetchAlatKalibrasi(0)
fetchDataOrder(0)
fetchDataChart()
fetchDataRiwayat()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/penyelia.scss';
@import '/@src/scss/module/dashboard/bedah.scss';

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

    .search-location-igd,
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

    .search-button-igd {
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

.riwayat-right {
    display: flex;
    align-items: center;
}
</style>
