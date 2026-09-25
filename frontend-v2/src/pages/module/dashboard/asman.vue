<template>
    <ConfirmDialog />
    <div>
        <div class="business-dashboard hr-dashboard">
            <div class="columns">
                <div class="column is-12">
                    <div class="columns is-multiline">
                        <div class="column is-12">
                            <div class="illustration-header-2 large-screen iris-dashboard-hero">
                                <div class="header-image">
                                    <img src="/@src/assets/illustrations/dashboards/lifestyle/Picture2.png" alt=""
                                        style="max-width:75%; margin-left: 2rem; margin-top: 0.5rem;" />
                                </div>
                                <div class="header-meta" style="margin-left : -2rem;">
                                    <h3 style="color:white"><i class="fas fa-id-card" aria-hidden="true"></i> Dashboard
                                        Asman
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
                            <TabView v-model:activeIndex="activeTab" class="tabview-custom " :scrollable="true"
                                @tab-click="klikTab($event)">
                                <TabPanel>
                                    <template #header>
                                        <i class="fas fa-users mr-2" aria-hidden="true"></i>
                                        <span>Daftar Unit Registrasi</span>
                                        <Badge :value="dataOrder.length" v-if="dataOrder.length > 0" severity="danger"
                                            class="ml-2" />
                                    </template>

                                    <div v-if="activeTab == 0">
                                        <div class="column is-6 date-filter-container">
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
                                            <div class="search-menu-rad mb-2">
                                                <div class="search-location-rad" style="width: 100%">
                                                    <i class="iconify" data-icon="feather:search"></i>
                                                    <input type="text"
                                                        placeholder="Cari Nama Perusahaan, No Pendaftaran"
                                                        v-model="item.search"
                                                        v-on:keyup.enter="fetchDataOrder(order)" />
                                                </div>
                                                <VButton raised class="search-button-rad" @click="fetchDataOrder(order)"
                                                    :loading="isLoading"> Cari Data
                                                </VButton>
                                            </div>
                                            <VCard class="text-center pt-0 pb-0 mt-0">
                                                <VRadio v-model="order" value="0" label="Belum Verif"
                                                    name="outlined_radio" color="warning" />
                                                <VRadio v-model="order" value="1" label="Sudah Verif"
                                                    name="outlined_radio" color="info" />
                                            </VCard>
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
                                            <div class="list-view-inner"
                                                style="max-height:500px;overflow: auto; margin-top: 1rem; ">
                                                <TransitionGroup name="list-complete" tag="div">
                                                    <div v-for="(items, m) in dataOrder" :key="m"
                                                        class="list-view-item">
                                                        <div class="list-view-item-inner">
                                                            <VAvatar size="small" style="left: 8px;top: 4px;"
                                                                :color="listColor[i]" :initials="items.initials" />
                                                            <div class="meta-left">
                                                                <h3>
                                                                    {{ items.namaperusahaan }}
                                                                    <span>
                                                                        <b>
                                                                            ({{ items.nopendaftaran ?? '-' }})
                                                                        </b>
                                                                    </span>
                                                                </h3>
                                                                <span style="color: black">
                                                                    <i aria-hidden="true" class="iconify"
                                                                        data-icon="feather:calendar"></i>
                                                                    <span>{{ items.tglregistrasi }}</span>
                                                                </span>
                                                                <br>
                                                                <VTag v-if="items.isstandarulab" color="warning"
                                                                    class="mr-1" rounded>Rekalibrasi Standar
                                                                    Internal</VTag>
                                                                <VTag v-if="items.iskalibrasiinternal" color="warning"
                                                                    class="mr-1" rounded>Kalibrasi
                                                                    Internal</VTag>
                                                                <VTag v-if="items.jenisorder == 'repair'"
                                                                    color="warning" rounded>Repair</VTag>
                                                                <VTag v-if="items.jenisorder == 'kalibrasi'"
                                                                    color="info" rounded>Kalibrasi</VTag>
                                                                <div v-if="items.jumlahdetail && items.jumlahselesai != null"
                                                                    style="font-weight: 600; font-size: 0.98em; color: #2abb4a; margin-top: 5px;">
                                                                    Selesai {{ items.jumlahselesai }}/{{
                                                                        items.jumlahdetail }}
                                                                    <div
                                                                        style="background:#e9ecef; border-radius:8px; width:85%; height:7px; margin: 6px 0 0 0;">
                                                                        <div :style="{
                                                                            width: ((items.jumlahselesai / items.jumlahdetail) * 100) + '%',
                                                                            background: '#53dd6c',
                                                                            height: '100%',
                                                                            borderRadius: '8px',
                                                                            transition: 'width 0.5s'
                                                                        }"></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="meta-right">
                                                                <VIconButton v-tooltip.bottom.left="'Verifikasi'"
                                                                    label="Bottom Left" color="primary" circle
                                                                    icon="pi pi-check-circle"
                                                                    v-if="items.statusorder == 0 && items.jenisorder == 'kalibrasi'"
                                                                    @click="orderVerify(items)"
                                                                    style="margin-right: 15px;" />
                                                                <VIconButton v-tooltip.bottom.left="'Verifikasi Repair'"
                                                                    label="Bottom Left" color="warning" circle
                                                                    icon="pi pi-check-circle"
                                                                    v-if="items.statusorder == 0 && items.jenisorder == 'repair'"
                                                                    @click="orderVerifyRepair(items)"
                                                                    style="margin-right: 15px;" />
                                                                <VIconButton v-tooltip.bottom.left="'Detail'"
                                                                    label="Bottom Left" v-else color="primary" circle
                                                                    icon="pi pi-book" @click="getDetailVerify(items)"
                                                                    style="margin-right: 15px;" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </TransitionGroup>

                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>
                                <TabPanel>
                                    <template #header>
                                        <i class="fas fa-tools mr-2" aria-hidden="true"></i>
                                        <span>Daftar Alat</span>
                                        <Badge :value="dataAlatKalibrasi.length" v-if="dataAlatKalibrasi.length > 0"
                                            severity="danger" class="ml-2" />
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
                                                        placeholder="Cari Nama Alat, Nama Perusahaan, No order Alat, No Pendaftaran"
                                                        v-model="item.qsearch"
                                                        v-on:keyup.enter="fetchAlatKalibrasi(orderAlat)" />
                                                </div>
                                                <VButton raised class="search-button-igd"
                                                    @click="fetchAlatKalibrasi(orderAlat)" :loading="isLoading">
                                                    Cari Data
                                                </VButton>
                                            </div>
                                            <VCard class="text-center pt-0 pb-0 mt-0">
                                                <VRadio v-model="orderAlat" value="0" label="Belum Setujui"
                                                    name="outlined_radio" color="success" />
                                                <VRadio v-model="orderAlat" value="2" label="Sudah Setujui"
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
                                                        <div class="list-view-item"
                                                            :class="{ 'card-tolak-serti': item.istolaksertiasman == true }">
                                                            <div class="list-view-item-inner">
                                                                <VAvatar size="small"
                                                                    :picture="getLingkupAvatar(item.lingkupkalibrasi).picture"
                                                                    :color="getLingkupAvatar(item.lingkupkalibrasi).color"
                                                                    bordered />
                                                                <div class="meta-left">
                                                                    <h3>
                                                                        <b>{{ item.namaproduk }}</b>
                                                                        <VTag
                                                                            v-if="item.versisertifikat > 1 || item.versilaporanrepair > 1"
                                                                            :label="'Amandemen'" :color="'info'"
                                                                            class="ml-2" />
                                                                        <VTag v-if="item.tanda_kalibrasi_ai == 'ADA'"
                                                                            class="ml-1"
                                                                            :label="'Kalibrasi AI Tersedia'"
                                                                            :color="'success'" />
                                                                    </h3>
                                                                    <h5>Merk/Tipe : {{ item.namamerk }}/{{
                                                                        item.namatipe }}</h5>
                                                                    <h5>S/N : {{ item.namaserialnumber }}</h5>
                                                                    <span>
                                                                        <i aria-hidden="true" class="iconify"
                                                                            data-icon="feather:home"></i>
                                                                        <span>{{ item.namaperusahaan }}</span>
                                                                        <i aria-hidden="true"
                                                                            class="fas fa-circle icon-separator"></i>
                                                                        <i aria-hidden="true" class="iconify"
                                                                            data-icon="feather:calendar"></i>
                                                                        <span>{{ item.tglverifasman }}</span>
                                                                        <i aria-hidden="true"
                                                                            class="fas fa-circle icon-separator"></i>
                                                                        <i aria-hidden="true" class="iconify"
                                                                            data-icon="feather:check-circle"></i>
                                                                        <span>{{ item.nopendaftaran }}</span>
                                                                        <i aria-hidden="true"
                                                                            class="fas fa-circle icon-separator"></i>
                                                                        <i aria-hidden="true" class="iconify"
                                                                            data-icon="feather:check-circle"></i>
                                                                        <span>{{ item.noorderalat }}</span>

                                                                    </span>
                                                                    <div>
                                                                        <VTag class="mr-1"
                                                                            :label="item.jenissurkesfk == 1 ? 'SURKES' : 'NON SURKES'"
                                                                            :color="item.jenissurkesfk == 1 ? 'success' : 'danger'" />
                                                                        <VTag v-if="item.isstandarulab" class="mr-1"
                                                                            :label="'Rekalibrasi Standar Internal'"
                                                                            :color="'warning'" />
                                                                        <VTag v-if="item.iskalibrasiinternal"
                                                                            class="mr-1" :label="'Kalibrasi Internal'"
                                                                            :color="'warning'" />
                                                                        <VTag
                                                                            v-if="item.tanggalmulai != null && item.jenisorder == 'kalibrasi'"
                                                                            :label="'Tanggal Mulai : ' + item.tanggalmulai"
                                                                            :color="'warning'" />
                                                                        <VTag v-if="item.durasi_proses != null"
                                                                            :label="item.durasi_proses" color="info"
                                                                            class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && item.isverifikasi == null"
                                                                            :label="'Sertifikat Disetujui Penyelia'"
                                                                            :color="'primary'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && item.isverifikasi == true"
                                                                            :label="'Laporan Verifikasi Disetujui Penyelia'"
                                                                            :color="'primary'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.penyeliasetujulaporanrepairfk != null"
                                                                            :label="'Laporan Repair Disetujui Penyelia'"
                                                                            :color="'info'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.asmansetujulaporanrepairfk != null"
                                                                            :label="'Laporan Repair Disetujui Asman'"
                                                                            :color="'info'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true && item.isverifikasi == null"
                                                                            :label="'Sertifikat Disetujui Asman'"
                                                                            :color="'primary'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true && item.isverifikasi == true"
                                                                            :label="'Laporan Verifikasi Disetujui Asman'"
                                                                            :color="'primary'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjamanager != null && item.setujuilembarkerjamanager == true && item.isverifikasi == null"
                                                                            :label="'Sertifikat Disetujui Manager'"
                                                                            :color="'primary'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.managersetujulaporanrepairfk != null"
                                                                            :label="'Laporan Repair Disetujui Manager'"
                                                                            :color="'info'" class="ml-2" />
                                                                        <VTag
                                                                            v-if="item.setujuilembarkerjamanager != null && item.setujuilembarkerjamanager == true && item.isverifikasi == true"
                                                                            :label="'Laporan Verifikasi Disetujui Manager'"
                                                                            :color="'primary'" class="ml-2" />
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
                                                                    <div v-if="item.istolaksertiasman === true"
                                                                        class="tolak-serti-anim"
                                                                        style="font-weight: bold;">
                                                                        <svg viewBox="0 0 32 32" fill="none">
                                                                            <circle cx="16" cy="16" r="16"
                                                                                fill="#e57373" />
                                                                            <path d="M10 10L22 22M22 10L10 22"
                                                                                stroke="#fff" stroke-width="3.2"
                                                                                stroke-linecap="round" />
                                                                        </svg>

                                                                        <div class="tolak-serti-text">
                                                                            <div
                                                                                class="tolak-serti-title tolak-serti-red">
                                                                                Sertifikat Ditolak Asman
                                                                            </div>
                                                                            <div class="tolak-serti-ket">
                                                                                Keterangan: {{
                                                                                    item.alasanpenolakansertiasman
                                                                                    ?? '-' }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    class="meta-right flex justify-center items-center">
                                                                    <div class="buttons">
                                                                        <VIconButton v-tooltip.bottom.left="'SPK'"
                                                                            icon="feather:printer"
                                                                            @click="cetakSpk(item)" color="warning"
                                                                            raised circle class="mr-2">
                                                                        </VIconButton>
                                                                        <VIconButton
                                                                            v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                                                            v-tooltip.bottom.left="'Cetak Sertifikat'"
                                                                            icon="feather:printer"
                                                                            @click="cetakSertifikatLembarKerja(item)"
                                                                            color="info" raised circle class="mr-2">
                                                                        </VIconButton>
                                                                        <VIconButton
                                                                            v-if="item.setujuilembarkerjaasman != null && item.setujuilembarkerjaasman == true && item.jenisorder == 'kalibrasi' && item.isverifikasi == true"
                                                                            v-tooltip.bottom.left="'Cetak Laporan Verifikasi'"
                                                                            icon="feather:printer"
                                                                            @click="cetakLaporanVerfikasi(item)"
                                                                            color="success" raised circle class="mr-2">
                                                                        </VIconButton>
                                                                        <VIconButton
                                                                            v-if="item.asmansetujulaporanrepairfk != null && item.jenisorder == 'repair'"
                                                                            v-tooltip.bottom.left="'Cetak Laporan Repair'"
                                                                            icon="feather:printer"
                                                                            @click="cetakLaporanRepair(item)"
                                                                            color="success" raised circle class="mr-2">
                                                                        </VIconButton>
                                                                        <VIconButton
                                                                            v-if="item.sublingkupfk != null && item.lingkupfk != 2 && item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && (item.setujuilembarkerjaasman == null || item.setujuilembarkerjaasman == false) && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                                                            color="info" circle icon="fas fa-pager"
                                                                            outlined raised
                                                                            @click="masukLembarKerjaSudahIsi(item)"
                                                                            v-tooltip.bottom.left="'Lembar Kerja'" />
                                                                        <VIconButton
                                                                            v-if="item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && (item.setujuilembarkerjaasman == null || item.setujuilembarkerjaasman == false) && item.jenisorder == 'kalibrasi' && item.isverifikasi == true"
                                                                            color="success" circle icon="fas fa-file"
                                                                            outlined raised
                                                                            @click="masukLaporanVerifikasi(item)"
                                                                            v-tooltip.bottom.left="'Laporan Verifikasi'" />
                                                                        <VIconButton
                                                                            v-if="item.lingkupfk == 2 && item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && (item.setujuilembarkerjaasman == null || item.setujuilembarkerjaasman == false) && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
                                                                            color="info" circle icon="fas fa-pager"
                                                                            outlined raised
                                                                            @click="lembarKerjaTekanan(item)"
                                                                            v-tooltip.bottom.left="'Lembar Kerja'" />
                                                                        <VIconButton
                                                                            v-if="item.penyeliasetujulaporanrepairfk != null && item.jenisorder == 'repair' && item.asmansetujulaporanrepairfk == null"
                                                                            color="warning" circle icon="fas fa-tools"
                                                                            outlined raised @click="laporanRepair(item)"
                                                                            v-tooltip.bottom.left="'Laporan Repair'" />
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
                                                                            v-if="item.tanda_kalibrasi_ai == 'ADA' && item.setujuilembarkerjapenyelia != null && item.setujuilembarkerjapenyelia == true && (item.setujuilembarkerjaasman == null || item.setujuilembarkerjaasman == false) && item.jenisorder == 'kalibrasi' && item.isverifikasi == null"
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
        <VModal :open="modalDetailOrder" title="Verifikasi" noclose size="big" actions="right"
            @close="modalDetailOrder = false, clear()" cancelLabel="Tutup">
            <template #content>
                <div class="business-dashboard hr-dashboard">
                    <div class="columns is-multiline">
                        <div class="column is-12 p-0">
                            <div class="block-header">
                                <div class="left column is-6 p-0">
                                    <div class="current-user">
                                        <h3>{{ item.namaperusahaan }}</h3>
                                    </div>
                                </div>
                                <div class="center column is-6 p-0">
                                    <div>
                                        <div>
                                            <h4 class="block-heading">No. Pendaftaran</h4>
                                            <p class="block-hext">{{ item.nopendaftaran }}</p>
                                            <h4 class="block-heading">Tgl Registrasi</h4>
                                            <p class="block-hext">{{ item.tglregistrasi }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="column is-12 p-4 mt-5">
                    <Fieldset legend="Edit Tindakan" :toggleable="true">
                        <div class="columns pl-3">
                            <div class="column is-1 pr-0" style="padding-left: 0px;margin-right: -38px">
                                <VField label="No">
                                    <VAvatar initials="1" />
                                </VField>
                            </div>
                            <div class="column is-11 ml-5">
                                <div class="columns">
                                    <div class="column is-4">
                                        <VField>
                                            <VLabel>Lokasi Kalibrasi</VLabel>
                                            <VControl>
                                                <AutoComplete v-model="item.lokasikalibrasiUpdate"
                                                    :suggestions="d_lokasikalibrasi" @complete="fetchLokasi($event)"
                                                    :optionLabel="'label'" :dropdown="true" :minLength="3"
                                                    class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                                                    :field="'label'" placeholder="ketik untuk mencari..." />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="column is-4">
                                        <VField>
                                            <VLabel>Lingkup Kalibrasi</VLabel>
                                            <VControl>
                                                <AutoComplete v-model="item.lingkupkalibrasiUpdate"
                                                    :suggestions="d_lingkup" @complete="fetchLingkup($event)"
                                                    :optionLabel="'label'" :dropdown="true" :minLength="3"
                                                    class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                                                    :field="'label'" placeholder="ketik untuk mencari..." />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="column is-4" v-if="item.isVendor == true">
                                        <VField>
                                            <VLabel>Vendor Kalibrasi</VLabel>
                                            <VControl>
                                                <AutoComplete v-model="item.vendorkalibrasiUpdate"
                                                    :suggestions="d_vendor" @complete="fetchvendor($event)"
                                                    :optionLabel="'label'" :dropdown="true" :minLength="3"
                                                    class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                                                    :field="'label'" placeholder="ketik untuk mencari..." />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="column is-4" v-if="item.isVendor == null">
                                        <VField>
                                            <VLabel>Penyelia Teknik</VLabel>
                                            <VControl>
                                                <AutoComplete v-model="item.penyeliateknikUpdate"
                                                    :suggestions="d_penyelia" @complete="fetchPenyelia($event)"
                                                    :optionLabel="'label'" :dropdown="true" :minLength="3"
                                                    class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                                                    :field="'label'" placeholder="ketik untuk mencari..." />

                                            </VControl>
                                        </VField>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="columns pl-3">
                            <div class="column is-1 pr-0" style="padding-left: 0px;margin-right: -38px">
                            </div>
                            <div class="column is-11 ml-5">
                                <div class="columns">
                                    <div class="column is-4" v-if="item.isVendor == null">
                                        <VField>
                                            <VLabel>Pelaksana Teknik</VLabel>
                                            <VControl>
                                                <AutoComplete v-model="item.pelaksanaUpdate" :suggestions="d_pelaksana"
                                                    @complete="fetchPelaksana($event)" :optionLabel="'label'"
                                                    :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                                                    :loadingIcon="'pi pi-spinner'" :field="'label'"
                                                    placeholder="ketik untuk mencari..." />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="column is-2" v-if="item.isVendor == null && dataNamaPaket != null">
                                        <VField label="Durasi Hari">
                                            <VControl icon="lnir lnir-repeat-one">
                                                <VInput type="number" v-model="item.durasikalbrasiUpdate"
                                                    v-if="dataNamaPaket != null" placeholder="Jumlah" class="is-rounded"
                                                    disabled />
                                                <VInput type="number" v-model="item.durasikalbrasiUpdate" v-else
                                                    placeholder="Jumlah" class="is-rounded" />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="columns mt-2" style="margin-left:40px" v-if="item.isVendor == null">
                                        <VButtons>
                                            <VButton color="success" raised icon="feather:edit"
                                                v-if="item.pelaksanaUpdate" @click="update(item)"
                                                :loading="isLoadingSave"> Update
                                            </VButton>
                                            <VButton raised @click="clear()"> Batal </VButton>
                                        </VButtons>
                                    </div>
                                    <div class="columns mt-2" style="margin-left:40px" v-if="item.isVendor == true">
                                        <VButtons>
                                            <VButton color="success" raised icon="feather:edit"
                                                v-if="item.vendorkalibrasiUpdate" @click="update(item)"
                                                :loading="isLoadingSave"> Update
                                            </VButton>
                                            <VButton raised @click="clear()"> Batal </VButton>
                                        </VButtons>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </Fieldset>
                </div>
                <div class="column is-12" v-if="dataNamaPaket != null && !allIsVendor">
                    <div class="columns is-multiline">
                        <div class="ml-4 column is-3">
                            <div class="meta-container">
                                <div class="meta-content">
                                    <h4>Paket Kalibrasi {{ dataNamaPaket }}</h4>
                                    <p>
                                        <span>Estimasi Tanggal selesai</span>
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

                        <div class="column is-3">
                            <VField>
                                <VLabel>Paket Kalibrasi</VLabel>
                                <VControl fullwidth class="prime-auto ">
                                    <AutoComplete v-model="item.paketkalibrasiUpdate" :suggestions="d_paketkalibrasi"
                                        @complete="fetchpaketKalibrasi($event)" :optionLabel="'label'" :dropdown="true"
                                        :minLength="3" class="is-input" :appendTo="'body'"
                                        :loadingIcon="'pi pi-spinner'" :field="'label'"
                                        placeholder="ketik untuk mencari..." />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-4" style="display:flex;align-items:flex-end;">
                            <VButtons>
                                <VButton color="success" raised icon="feather:edit" @click="updatePaketLingkup()"
                                    :loading="isLoadingSave">
                                    Update Paket
                                </VButton>
                            </VButtons>
                        </div>

                        <div class="column is-6 mt-4">
                            <div class="column is-12"
                                v-if="isChartReady && chartLingkupSeries.length > 0 && chartLingkupOptions">
                                <ApexChart type="bar" height="260" :options="chartLingkupOptions"
                                    :series="chartLingkupSeries" />
                            </div>
                            <div class="column is-12" v-else-if="!isChartReady">
                                <VPlaceload height="260px" />
                            </div>
                        </div>

                        <div class="column is-6 mt-4">
                            <div class="column is-12"
                                v-if="isChartReady && chartEstimasiLingkupSeries.length > 0 && chartEstimasiLingkupOptions">
                                <ApexChart type="rangeBar" height="260" :options="chartEstimasiLingkupOptions"
                                    :series="chartEstimasiLingkupSeries" />
                            </div>
                            <div class="column is-12" v-else-if="!isChartReady">
                                <VPlaceload height="260px" />
                            </div>
                        </div>

                        <div class="column is-9">
                            <div v-for="(g, idx) in groupLingkup" :key="g.lingkupfk || idx"
                                class="columns is-multiline mb-2 paket-lingkup-row">
                                <div class="column is-12">
                                    <h4 style="font-weight: 600; font-size: 0.95rem;">
                                        Lingkup {{ g.lingkupkalibrasi || ('#' + (idx + 1)) }}
                                    </h4>
                                </div>
                                <div class="column is-4">
                                    <VField>
                                        <VLabel>Tanggal Mulai</VLabel>
                                        <VDatePicker v-model="g.tanggalmulai" mode="dateTime" style="width: 100%"
                                            trim-weeks>
                                            <template #default="{ inputValue, inputEvents }">
                                                <VField>
                                                    <VControl icon="feather:calendar" fullwidth>
                                                        <VInput :value="inputValue" placeholder="Tanggal"
                                                            v-on="inputEvents" />
                                                    </VControl>
                                                </VField>
                                            </template>
                                        </VDatePicker>
                                    </VField>
                                </div>

                                <div class="column is-4" style="display:flex;align-items:flex-end;">
                                    <VButtons>
                                        <VButton color="success" raised icon="feather:edit" @click="updateTglMulai(g)"
                                            :loading="isLoadingSave">
                                            Update Tanggal Mulai Lingkup
                                        </VButton>
                                    </VButtons>
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
                                    <div class="timeline-item is-unread" v-for="(items, index) in detailOrderLayanan"
                                        :key="items.norec">
                                        <div :class="'dot is-' + listColor[index + 1]"></div>

                                        <div class="content-wrap is-grey">
                                            <div class="content-box">
                                                <div class="status"></div>
                                                <VIconBox size="medium" :color="listColor[index + 1]" rounded>
                                                    <i class="iconify" data-icon="feather:package"
                                                        aria-hidden="true"></i>
                                                </VIconBox>
                                                <div class="box-text" style="width:70%">
                                                    <div class="meta-text">
                                                        <p>
                                                            <span>{{ items.namaproduk }}
                                                                <VTag v-if="items.tanggalpenolakanregis != null"
                                                                    color="danger" label="Alat Ditolak" rounded>
                                                                </VTag>
                                                            </span>
                                                        </p>
                                                        <table class="tb-order">
                                                            <tr>
                                                                <td>Merk/Tipe</td>
                                                                <td>:</td>
                                                                <td>{{ items.namamerk ?? '' }}/{{ items.namatipe }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>S/N</td>
                                                                <td>:</td>
                                                                <td>{{ items.namaserialnumber ?? '' }}</td>
                                                            </tr>
                                                            <tr v-if="items.tanggalpenolakanregis != null">
                                                                <td>Alasan Penolakan</td>
                                                                <td>:</td>
                                                                <td>{{ items.alasanpenolakanregis ?? '' }} </td>
                                                            </tr>
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
                                                            <tr v-if="items.isVendor == null">
                                                                <td>Penyelias Teknik </td>
                                                                <td>:</td>
                                                                <td class="font-values">{{ items.penyeliateknik }}</td>
                                                            </tr>
                                                            <tr v-if="items.isVendor == null">
                                                                <td>Pelaksana Teknik</td>
                                                                <td>:</td>
                                                                <td>{{ items.pelaksanateknik }} </td>
                                                            </tr>
                                                            <tr v-if="items.isVendor == null && dataNamaPaket != null">
                                                                <td>Durasi</td>
                                                                <td>:</td>
                                                                <td>
                                                                    <VTag v-if="items.durasikalbrasi" color="warning"
                                                                        rounded> {{
                                                                            items.durasikalbrasi }}
                                                                    </VTag>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Status Surkes</td>
                                                                <td>:</td>
                                                                <td>
                                                                    <VTag v-if="items.jenissurkesfk == 1"
                                                                        color="success" rounded>
                                                                        SURKES
                                                                    </VTag>
                                                                    <VTag v-else color="danger" rounded> NON SURKES
                                                                    </VTag>
                                                                </td>
                                                            </tr>
                                                            <tr v-if="items.isVendor == true">
                                                                <td>Vendor</td>
                                                                <td>:</td>
                                                                <td>
                                                                    <VTag color="success" rounded> {{
                                                                        items.namavendor }}
                                                                    </VTag>
                                                                </td>
                                                            </tr>
                                                        </table>

                                                    </div>
                                                </div>
                                                <div class="box-end" style="width: 30%">
                                                    <div class="columns is-multiline">
                                                        <div class="column is-6" style="margin-top: 0.5rem;">
                                                            <VIconButton v-tooltip.bottom.left="'Edit'"
                                                                icon="feather:edit" @click="edit(items)" color="warning"
                                                                raised circle class="mr-2">
                                                            </VIconButton>
                                                            <VIconButton v-tooltip.bottom.right="'Batal/Tolak'"
                                                                icon="feather:trash" @click="hapusItems(items)"
                                                                color="danger" raised circle>
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
                    </Fieldset>
                </div>
            </template>
            <template #action>
                <VButton icon="feather:save" @click="save('kalibrasi')" color="info" :loading="isLoadingSave" raised>
                    Simpan Verif
                </VButton>
            </template>
        </VModal>
        <VModal :open="modalDetailOrderRepair" title="Verifikasi Repair" noclose size="big" actions="right"
            @close="modalDetailOrderRepair = false, clear()" cancelLabel="Tutup">
            <template #content>
                <div class="business-dashboard hr-dashboard">
                    <div class="columns is-multiline">
                        <div class="column is-12 p-0">
                            <div class="block-header">
                                <div class="left column is-6 p-0">
                                    <div class="current-user">
                                        <h3>{{ item.namaperusahaan }}</h3>
                                    </div>
                                </div>
                                <div class="center column is-6 p-0">
                                    <div>
                                        <div>
                                            <h4 class="block-heading">No. Pendaftaran</h4>
                                            <p class="block-hext">{{ item.nopendaftaran }}</p>
                                            <h4 class="block-heading">Tgl Registrasi</h4>
                                            <p class="block-hext">{{ item.tglregistrasi }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="column is-12 p-4 mt-5">
                    <Fieldset legend="Edit Tindakan" :toggleable="true">
                        <div class="columns pl-3">
                            <div class="column is-1 pr-0" style="padding-left: 0px;margin-right: -38px">
                                <VField label="No">
                                    <VAvatar initials="1" />
                                </VField>
                            </div>
                            <div class="column is-11 ml-5">
                                <div class="columns">
                                    <div class="column is-4">
                                        <VField>
                                            <VLabel>Lokasi Repair</VLabel>
                                            <VControl>
                                                <AutoComplete v-model="item.lokasiRepairUpdate"
                                                    :suggestions="d_lokasikalibrasi" @complete="fetchLokasi($event)"
                                                    :optionLabel="'label'" :dropdown="true" :minLength="3"
                                                    class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                                                    :field="'label'" placeholder="ketik untuk mencari..." />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="column is-4" v-if="item.isVendor == true">
                                        <VField>
                                            <VLabel>Vendor Repair</VLabel>
                                            <VControl>
                                                <AutoComplete v-model="item.vendorkalibrasiUpdate"
                                                    :suggestions="d_vendor" @complete="fetchvendor($event)"
                                                    :optionLabel="'label'" :dropdown="true" :minLength="3"
                                                    class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                                                    :field="'label'" placeholder="ketik untuk mencari..." />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="column is-4" v-if="item.isVendor == null">
                                        <VField>
                                            <VLabel>Penyelia Teknik</VLabel>
                                            <VControl>
                                                <AutoComplete v-model="item.penyeliateknikUpdate"
                                                    :suggestions="d_penyelia" @complete="fetchPenyelia($event)"
                                                    :optionLabel="'label'" :dropdown="true" :minLength="3"
                                                    class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                                                    :field="'label'" placeholder="ketik untuk mencari..." />

                                            </VControl>
                                        </VField>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="columns pl-3">
                            <div class="column is-1 pr-0" style="padding-left: 0px;margin-right: -38px">
                            </div>
                            <div class="column is-11 ml-5">
                                <div class="columns">
                                    <div class="column is-4" v-if="item.isVendor == null">
                                        <VField>
                                            <VLabel>Pelaksana Teknik</VLabel>
                                            <VControl>
                                                <AutoComplete v-model="item.pelaksanaUpdate" :suggestions="d_pelaksana"
                                                    @complete="fetchPelaksana($event)" :optionLabel="'label'"
                                                    :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                                                    :loadingIcon="'pi pi-spinner'" :field="'label'"
                                                    placeholder="ketik untuk mencari..." />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="columns mt-2" style="margin-left:40px">
                                        <VButtons>
                                            <VButton color="success" raised icon="feather:edit"
                                                v-if="item.pelaksanaUpdate" @click="update(item)"
                                                :loading="isLoadingSave"> Update
                                            </VButton>
                                            <VButton raised @click="clear()"> Batal </VButton>
                                        </VButtons>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </Fieldset>
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
                                    <div class="timeline-item is-unread" v-for="(items, index) in detailOrderLayanan"
                                        :key="items.norec">
                                        <div :class="'dot is-' + listColor[index + 1]"></div>

                                        <div class="content-wrap is-grey">
                                            <div class="content-box">
                                                <div class="status"></div>
                                                <VIconBox size="medium" :color="listColor[index + 1]" rounded>
                                                    <i class="iconify" data-icon="feather:package"
                                                        aria-hidden="true"></i>
                                                </VIconBox>
                                                <div class="box-text" style="width:70%">
                                                    <div class="meta-text">
                                                        <p>
                                                            <span>{{ items.namaproduk }}
                                                                <VTag v-if="items.tanggalpenolakanregis != null"
                                                                    color="danger" label="Alat Ditolak" rounded>
                                                                </VTag>
                                                            </span>
                                                        </p>
                                                        <table class="tb-order">
                                                            <tr v-if="items.tanggalpenolakanregis != null">
                                                                <td>Alasan Penolakan</td>
                                                                <td>:</td>
                                                                <td>{{ items.alasanpenolakanregis ?? '' }} </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Lokasi</td>
                                                                <td>:</td>
                                                                <td>{{ items.lokasirepair }} </td>
                                                            </tr>
                                                            <tr v-if="items.isVendor == null">
                                                                <td>Penyelia Teknik </td>
                                                                <td>:</td>
                                                                <td class="font-values">{{ items.penyeliateknik }}</td>
                                                            </tr>
                                                            <tr v-if="items.isVendor == null">
                                                                <td>Pelaksana Teknik</td>
                                                                <td>:</td>
                                                                <td>{{ items.pelaksanateknik }} </td>
                                                            </tr>
                                                            <tr v-if="items.isVendor == true">
                                                                <td>Vendor</td>
                                                                <td>:</td>
                                                                <td>
                                                                    <VTag color="success" rounded> {{
                                                                        items.namavendor }}
                                                                    </VTag>
                                                                </td>
                                                            </tr>
                                                        </table>

                                                    </div>
                                                </div>
                                                <div class="box-end" style="width: 30%">
                                                    <div class="columns is-multiline">
                                                        <div class="column is-6" style="margin-top: 0.5rem;">
                                                            <VIconButton v-tooltip.bottom.left="'Edit'"
                                                                icon="feather:edit" @click="edit(items)" color="warning"
                                                                raised circle class="mr-2">
                                                            </VIconButton>
                                                            <VIconButton v-tooltip.bottom.right="'Batal/Tolak'"
                                                                icon="feather:trash" @click="hapusItems(items)"
                                                                color="danger" raised circle>
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
                    </Fieldset>
                </div>
            </template>
            <template #action>
                <VButton icon="feather:save" @click="save('repair')" color="info" :loading="isLoadingSave" raised>
                    Simpan Verif
                </VButton>
            </template>
        </VModal>
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
        <VModal :open="modalRiwayat" title="" noclose size="big" actions="right" @close="modalRiwayat = false"
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
        <VModal :open="modalPembatalanOrderAlat" title="Pembatalan Atau Penolakan" size="medium" actions="right"
            @close="modalPembatalanOrderAlat = false" cancelLabel="Tutup">
            <template #content>
                <div class="columns is-multiline">
                    <div class="column is-12">
                        <span
                            style="margin-bottom:1rem;font-weight: bold; font-size: 12px; font-family: var(--font-alt);">Alasan
                            Pembatalan/Penolakan
                        </span>

                        <VField>
                            <VControl>
                                <VTextarea class="textarea is-rounded" v-model="item.alasanpembatalan" rows="4"
                                    placeholder="Alasan Pembatalan/Penolakan" autocomplete="off" autocapitalize="off"
                                    spellcheck="true" />
                            </VControl>
                        </VField>
                    </div>

                </div>
            </template>
            <template #action>
                <VButton icon="feather:plus" color="primary" @click="SaveHapusItem(item)" :loading="isLoadingSave"
                    raised>Simpan
                </VButton>
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
                                    <VIconButton v-if="(slotProps.data.statusamandemen == 1)"
                                        v-tooltip.top.left="'Setujui'" color="success" outlined circle
                                        icon="fas fa-check" @click="confirmApproval(slotProps.data, 3)" />
                                    <VIconButton v-if="(slotProps.data.statusamandemen == 1)"
                                        v-tooltip.top.left="'Tolak'" class="ml-3" color="warning" outlined circle
                                        icon="feather:x" @click="confirmApproval(slotProps.data, 4)" />
                                </template>
                            </Column>
                            <Column field="status" header="Status" :sortable="true" style="min-width:140px">
                                <template #body="slotProps">
                                    <VTag class="ml-2" :color="slotProps.data.color" rounded>{{ slotProps.data.status }}
                                    </VTag>
                                </template>
                            </Column>
                            <Column field="alasanpengajuan" header="Alasan Pengajuan" :sortable="true"
                                style="min-width:240px">
                            </Column>
                            <Column field="namaproduk" header="Nama Alat" :sortable="true" style="min-width:100px">
                            </Column>
                            <Column field="namamerk" header="Merk Alat" :sortable="true" style="min-width:100px">
                            </Column>
                            <Column field="namatipe" header="Tipe Alat" :sortable="true" style="min-width:100px">
                            </Column>
                            <Column field="namaserialnumber" header="SN" :sortable="true" style="min-width:100px">
                            </Column>
                            <Column field="tglpengajuanamandemen" header="Tanggal Pengajuan" :sortable="false"
                                style="min-width:240px">
                                <template #body="slotProps">
                                    <span>
                                        {{ H.formatDateToLocalString(slotProps.data.tglpengajuanamandemen) }}
                                    </span>
                                </template>
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
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive, shallowRef, nextTick, onMounted } from 'vue'
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
    title: 'Dashboard Asman - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const chartLingkupOptions = shallowRef(null)
const chartLingkupSeries = shallowRef([])
const chartEstimasiLingkupOptions = shallowRef(null)
const chartEstimasiLingkupSeries = shallowRef([])
const isChartReady = ref(false)
const ACTIVE_TAB_KEY = 'asman_dashboard_active_tab_v1'
const getCachedActiveTab = () => {
    if (typeof window === 'undefined') return 0

    try {
        return Number(window.localStorage.getItem(ACTIVE_TAB_KEY)) === 1 ? 1 : 0
    } catch {
        return 0
    }
}
const cacheActiveTab = (index: number) => {
    if (typeof window === 'undefined') return

    try {
        window.localStorage.setItem(ACTIVE_TAB_KEY, String(index))
    } catch {
    }
}
const activeTab = ref(getCachedActiveTab())
onBeforeRouteLeave(() => {
    cacheActiveTab(activeTab.value)
})
const d_paketkalibrasi = ref([])
const dataNamaPaket: any = ref()
const dataPaket: any = ref()
const dataPaketTanggal: any = ref()
const allIsVendor: any = ref()
let modalRiwayat: any = ref(false)
let isLoadDataDeatilOrder: any = ref(false)
const timelineItems = ref([])
let dataAlatKalibrasi: any = ref([])
const rowGroupMetadata = ref({})
const currentPage: any = ref({
    limit: 5,
    rows: 50,
})
const openWelcome = ref(false)
const groupLingkup: any = ref([])
const FILTER_KEY = 'asman_dashboard_filters_v1'
const makeYearRange = (d = new Date()) => {
    const y = d.getFullYear()
    return {
        start: new Date(y, 0, 1, 0, 0, 0, 0),
        end: new Date(y, 11, 31, 23, 59, 59, 999),
    }
}
var date = new Date();
const dateNow = date.toLocaleString('id-ID', { year: "numeric", month: "long", day: "numeric" });
let listColor: any = ref(Object.keys(useThemeColors()))
const modalDetail = ref(false)
const route = useRoute()
const userLogin = useUserSession().getUser()
let statusOrder: any = ref([])
let isLoading: any = ref(false)
let modalDetailOrder: any = ref(false)
let modalDetailOrderRepair: any = ref(false)
let modalFilter: any = ref(false)
let isLoadingSave: any = ref(false)
let isLoadDataOrder: any = ref(false)
let isLoadDataSoNorec: any = ref(false)
let dataPegawaiJakarta: any = ref([])
let dataPegawaiGresik: any = ref([])
let detailOrderLayanan: any = ref(0)
let totalData: any = ref(0)
let isData: any = ref()
const d_lokasikalibrasi = ref([])
const d_lingkup = ref([])
const d_vendor = ref([])
const d_penyelia = ref([])
const d_pelaksana = ref([])
const modalPembatalanOrderAlat: any = ref(false)
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
type AvatarColor = 'primary' | 'info' | 'success' | 'warning' | 'danger'
type LingkupAvatar = { picture: string; color: AvatarColor }
const DEFAULT_AVATAR: LingkupAvatar = {
    picture: '/images/avatars/svg/propinsi.svg',
    color: 'primary',
}
const themeColors = useThemeColors()
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
        const response = await useApi().get(`/asman/riwayat-amandemen?norec_detail=${e.norec_detail}`)
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

const confirmApproval = (row: any, statusBaru: 3 | 4) => {
    const isApprove = statusBaru === 3
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

const submitApprovalWithNote = async (row: any, statusBaru: 3 | 4) => {
    approvalError.value = ''

    approvalSubmitting.value = true
    try {
        await useApi().post('asman/approval-pengajuan-amandemen', {
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
        const res = await useApi().get('asman/get-pengajuan-amandemen')
        const rows = res?.data ?? []
        pendingAmandemenCount.value = rows.filter((x: any) => x.statusamandemen == 1).length

        dataPengajuanAmandemen.value = rows.map((it: any, idx: number) => {
            const statusText =
                it?.statusamandemen === 1 ? 'Diajukan Penyelia' :
                    it?.statusamandemen === 2 ? 'Ditolak Penyelia' :
                        it?.statusamandemen === 3 ? 'Disetujui Asman' :
                            it?.statusamandemen === 4 ? 'Ditolak Asman' :
                                it?.statusamandemen === 5 ? 'Disetujui Manager' :
                                    it?.statusamandemen === 6 ? 'Ditolak Manager' : ''

            const color =
                it?.statusamandemen === 1 ? 'warning' :
                    it?.statusamandemen === 2 ? 'danger' :
                        it?.statusamandemen === 3 ? 'success' :
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

const saveFilters = () => {
    try {
        const payload = {
            filterTgl: {
                start: item.value.filterTgl?.start ? new Date(item.value.filterTgl.start).toISOString() : null,
                end: item.value.filterTgl?.end ? new Date(item.value.filterTgl.end).toISOString() : null,
            },
            search: item.value.search ?? '',
            qsearch: item.value.qsearch ?? '',
            order: order.value,
            orderAlat: orderAlat.value,
        }
        localStorage.setItem(FILTER_KEY, JSON.stringify(payload))
    } catch (e) {
    }
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

        if (typeof parsed?.search === 'string') item.value.search = parsed.search
        if (typeof parsed?.qsearch === 'string') item.value.qsearch = parsed.qsearch
        if (parsed?.order !== undefined) order.value = parsed.order
        if (parsed?.orderAlat !== undefined) orderAlat.value = parsed.orderAlat
    } catch (e) {
    }
}

const buildCharts = (response: any) => {
    // A. Chart Lingkup (Bar)
    const jkt = response.jumlahKalibrasiLingkupJakarta || []
    const grs = response.jumlahKalibrasiLingkupGresik || []
    const allLingkup = new Set([...jkt.map((x: any) => x.lingkup), ...grs.map((x: any) => x.lingkup)])
    const categories = Array.from(allLingkup).sort()

    const mapData = (source: any[]) => {
        const map = new Map(source.map((i: any) => [i.lingkup, Number(i.jumlahbelumselesai)]))
        return categories.map(cat => map.get(cat) || 0)
    }

    chartLingkupOptions.value = {
        chart: { type: 'bar', toolbar: { show: false }, animations: { enabled: false } },
        plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
        dataLabels: { enabled: true },
        xaxis: { categories: categories },
        yaxis: { title: { text: 'Alat belum selesai' } },
        legend: { position: 'top' },
    }

    chartLingkupSeries.value = [
        { name: 'Jakarta', data: mapData(jkt) },
        { name: 'Gresik', data: mapData(grs) }
    ]

    // B. Chart Estimasi (RangeBar/Timeline)
    const estimasi = response.estimasiLingkup || []
    const dataEstimasi: any[] = []

    estimasi.forEach((row: any) => {
        if (!row.start || !row.end) return
        const tStart = new Date(row.start).getTime()
        const tEnd = new Date(row.end).getTime()

        if (isNaN(tStart) || isNaN(tEnd)) return
        if (tStart > tEnd) return

        dataEstimasi.push({
            x: row.lingkupkalibrasi || 'Lingkup Lain',
            y: [tStart, tEnd],
            backlog: row.backlog_selesai
        })
    })

    if (dataEstimasi.length > 0) {
        chartEstimasiLingkupOptions.value = {
            chart: { type: 'rangeBar', height: 350, toolbar: { show: false }, animations: { enabled: false } },
            plotOptions: { bar: { horizontal: true, barHeight: '50%', borderRadius: 4 } },
            xaxis: { type: 'datetime' },
            tooltip: {
                x: { format: 'dd MMM yyyy' },
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                    const d = w?.globals?.initialSeries?.[seriesIndex]?.data?.[dataPointIndex]
                    if (!d) return ''
                    return `<div style="padding:5px; background:#fff; border:1px solid #ccc;">
                              <b>${d.x}</b><br>
                              <small>Est. Selesai: ${d.backlog || '-'}</small>
                            </div>`
                }
            },
            grid: { row: { colors: ['#f3f4f5', '#fff'], opacity: 1 } }
        }

        chartEstimasiLingkupSeries.value = [{ name: 'Jadwal', data: dataEstimasi }]
    } else {
        chartEstimasiLingkupSeries.value = []
    }
}


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


watch(
    () => [item.value.filterTgl?.start, item.value.filterTgl?.end],
    () => saveFilters()
)
watch(
    () => item.value.search,
    () => saveFilters()
)
watch(
    () => item.value.qsearch,
    () => saveFilters()
)
watch(
    () => activeTab.value,
    (index) => cacheActiveTab(index),
    { flush: 'sync' }
)
watch(
    () => order.value,
    () => { saveFilters(); changeSwitch(order.value) }
)
watch(
    () => orderAlat.value,
    () => { saveFilters(); changeSwitchAlat(orderAlat.value) }
)

const buildLingkupGroups = (details: any[]) => {
    const map = new Map<any, any>()

    details
        .filter((row: any) => row.jenisorder === 'kalibrasi' && row.isVendor == null)
        .forEach((row: any) => {
            const key = row.lingkupfk ?? row.lingkupkalibrasi
            if (!key) return

            const tglRaw = row.tanggalmulai ?? row.tglmulai ?? null
            const tglMulai = tglRaw ? new Date(tglRaw) : null

            if (!map.has(key)) {
                map.set(key, {
                    lingkupfk: row.lingkupfk ?? null,
                    lingkupkalibrasi: row.lingkupkalibrasi ?? '-',
                    paketkalibrasiUpdate: row.idpaket
                        ? { value: row.idpaket, label: row.namapaket }
                        : { value: null, label: '' },
                    tanggalmulai: tglMulai,
                })
            } else {
                const g = map.get(key)
                if (!g.tanggalmulai && tglMulai) {
                    g.tanggalmulai = tglMulai
                }
            }
        })

    groupLingkup.value = Array.from(map.values())
}

const detailOrder = async (e: any) => {
    modalRiwayat.value = true
    item.value.namaproduk = e.namaproduk
    item.value.namamerk = e.namamerk
    item.value.namatipe = e.namatipe
    item.value.namaserialnumber = e.namaserialnumber
    item.value.durasikalbrasi = e.durasikalbrasi
    isLoadDataDeatilOrder.value = true
    const response = await useApi().get(`/asman/detail-produk?norec_pd=${e.norec_detail}`)
    timelineItems.value = response.timeline
    isLoadDataDeatilOrder.value = false
}


const fetchAlatKalibrasi = async (q: any) => {
    let dari = ''
    if (item.value.filterTgl.start) {
        dari = H.formatDate(item.value.filterTgl.start, 'YYYY-MM-DD')
    }
    let sampai = ''
    if (item.value.filterTgl.end) {
        sampai = H.formatDate(item.value.filterTgl.end, 'YYYY-MM-DD')
    }
    let status = ''

    let statusorderasman = ''
        , search = ''
    item.value.statusorderasman = q
    if (orderAlat) statusorderasman = '&statusorderasman=' + q
    if (item.value.qsearch) search = item.value.qsearch
    isLoading.value = true
    dataAlatKalibrasi.value = []
    const response = await useApi().get(
        '/asman/get-alat-asman?dari=' + dari
        + '&sampai=' + sampai
        + '&search=' + search
        + statusorderasman
    )
    isLoading.value = false
    dataAlatKalibrasi.value = response.data

    updateRowGroupMetaData();

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
    cacheActiveTab(activeTab.value)
    if (activeTab.value == 0) {
        fetchDataOrder(0)
    }
    if (activeTab.value == 1) {
        fetchAlatKalibrasi(0)
    }
}


const fetchDataOrder = async (q: any) => {
    statusOrder.value = q
    isLoading.value = true
    let dari = ''
    if (item.value.filterTgl.start) {
        dari = H.formatDate(item.value.filterTgl.start, 'YYYY-MM-DD 00:00')
    }
    let sampai = ''
    if (item.value.filterTgl.end) {
        sampai = H.formatDate(item.value.filterTgl.end, 'YYYY-MM-DD 23:59')
    }
    let search = ''
    let StatusOrder = ''
    item.value.statusOrder = q
    if (order) StatusOrder = '&statusorder=' + q
    if (item.value.search) search = '&search=' + item.value.search
    let limit: any = currentPage.value.limit
    let offset: any = route.query.page ? route.query.page : 1
    offset = (parseInt(offset) - 1) * limit
    let page: any = route.query.page ? route.query.page : 1

    await useApi().get(`asman/list-mitra-regis?page=${page}&offset=${offset}&limit=${limit}&rows=${currentPage.value.rows}&` + '&dari=' + dari + '&sampai=' + sampai + StatusOrder + search).then((response) => {
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
    })
    isLoading.value = false
}

const fetchpaketKalibrasi = async (filter: any) => {
    await useApi().get(
        `registrasi/dropdown-paket-kalibrasi?query=${filter.query}&limit=10`
    ).then((response) => {
        d_paketkalibrasi.value = response
        console.log(response)
    })
}

const fetchDetail = async () => {
    dataPegawaiJakarta.value = []
    dataPegawaiGresik.value = []
    const response = await useApi().get(
        '/asman/get-detail-pegawai'
    )
    dataPegawaiJakarta.value = response.pegawaiJakarta
    dataPegawaiGresik.value = response.pegawaiGresik
}

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

const fetchvendor = async (filter: any) => {
    await useApi().get(
        `general/dropdown/vendor_m?select=id,namavendor&param_search=namavendor&query=${filter.query}&limit=10`
    ).then((response) => {
        d_vendor.value = response
    })
}

const fetchPenyelia = async (filter: any) => {
    console.log(item.value.lokasiRepairUpdate.value)
    let lokasi = item.value.lokasikalibrasiUpdate?.value ?? item.value.lokasiRepairUpdate?.value ?? null;
    await useApi().get(
        `registrasi/pegawai-lokasi-kalibrasi?lokasi=${lokasi}&param_search=namalengkap&query=${filter.query}&jenispegawai=${1}`).then((response) => {
            d_penyelia.value = response.data.map((e: any) => {
                return { label: e.namalengkap, value: e.id }
            })
        })
}

const fetchPelaksana = async (filter: any) => {
    let lokasi = item.value.lokasikalibrasiUpdate?.value ?? item.value.lokasiRepairUpdate?.value ?? null;
    await useApi().get(
        `registrasi/pegawai-lokasi-kalibrasi?lokasi=${lokasi}&param_search=namalengkap&query=${filter.query}&jenispegawai=${2}`).then((response) => {
            d_pelaksana.value = response.data.map((e: any) => {
                return { label: e.namalengkap, value: e.id }
            })
        })
}

const orderVerify = async (e: any) => {
    isChartReady.value = false // Hide chart first
    chartLingkupSeries.value = []
    chartEstimasiLingkupSeries.value = []

    detailOrderLayanan.value = []
    modalDetailOrder.value = true
    isLoadDataOrder.value = true

    item.value.namaperusahaan = e.namaperusahaan
    item.value.tglregistrasi = e.tglregistrasi
    item.value.inisial = e.initials
    item.value.nopendaftaran = e.nopendaftaran
    item.value.catatan = e.keterangan
    item.value.norec = e.iddetail
    item.value.lokasikalibrasi = e.lokasikalibrasi
    item.value.lokasirepair = null
    item.value.lingkupkalibrasi = e.lingkupkalibrasi

    try {
        const response = await useApi().get(`/asman/layanan-verif?norec_pd=${e.iddetail}`)

        if (response.detail && response.detail.length > 0) {
            const first = response.detail[0]
            dataNamaPaket.value = first.namapaket
            dataPaket.value = first.totalDurasi
            dataPaketTanggal.value = first.tanggalSelesai
            allIsVendor.value = first.allIsVendor

            item.value.paketkalibrasiUpdate = {
                value: first.idpaket ?? '',
                label: first.namapaket ?? '',
            }
            item.value.tanggalmulai = first.tanggalmulai ?? null

            buildLingkupGroups(response.detail)
            detailOrderLayanan.value = response.detail

            try {
                buildCharts(response)
            } catch (err) {
                console.error("Skip chart render", err)
            }

            setTimeout(() => {
                isChartReady.value = true
            }, 300)
        }
    } catch (error) {
        console.error("Error API:", error)
    } finally {
        isLoadDataOrder.value = false
    }
}

const orderVerifyRepair = async (e: any) => {
    detailOrderLayanan.value = []
    modalDetailOrderRepair.value = true
    item.value.namaperusahaan = e.namaperusahaan
    item.value.inisial = e.initials
    item.value.nopendaftaran = e.nopendaftaran
    item.value.catatan = e.keterangan
    item.value.norec = e.iddetail
    item.value.lokasikalibrasi = null
    item.value.lokasirepair = e.lokasirepair
    item.value.lingkupkalibrasi = e.lingkupkalibrasi
    isLoadDataOrder.value = true
    isLoadDataSoNorec.value = false
    const response = await useApi().get(`/asman/layanan-verif?norec_pd=${e.iddetail}`)
    response.detail.forEach((element: any, i: any) => {
        element.no = i + 1
    });
    isLoadDataOrder.value = false
    detailOrderLayanan.value = response.detail
}


const getDetailVerify = (e: any) => {
    router.push({
        name: 'module-asman-detail-registrasi',
        query: {
            norec_pd: e.iddetail,
        },
    })
}

const cetakSertifikatLembarKerja = (e: any) => {
    if (e.versisertifikat == null) {
        H.printBlade(`asman/cetak-sertifikat-lembar-kerja?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
    } else {
        H.printBlade(`registrasi/cetak-sertif-customer-pdf?norec_detail=${e.norec_detail}`)
    }
}

const edit = (e: any) => {
    item.value.lokasikalibrasiUpdate = {
        value: e.lokasikalibrasifk ?? '',
        label: e.lokasi ?? ''
    };
    item.value.lokasiRepairUpdate = {
        value: e.lokasirepairfk ?? '',
        label: e.lokasirepair ?? ''
    };
    item.value.lingkupkalibrasiUpdate = {
        value: e.lingkupfk ?? '',
        label: e.lingkupkalibrasi ?? ''
    };
    item.value.vendorkalibrasiUpdate = {
        value: e.vendorkalibrasifk ?? '',
        label: e.namavendor ?? ''
    };
    item.value.penyeliateknikUpdate = {
        value: e.penyeliateknikfk ?? '',
        label: e.penyeliateknik ?? ''
    };
    item.value.pelaksanaUpdate = {
        value: e.pelaksanateknikfk ?? '',
        label: e.pelaksanateknik ?? ''
    };
    item.value.norec_detail = e.norec_detail
    item.value.norec = e.norec
    item.value.durasikalbrasiUpdate = e.durasikalbrasi
    item.value.isVendor = e.isVendor
}

const nn = (v: any) => (v === undefined || v === null || v === '' ? null : v);

const update = async (e: any) => {
    const json = {
        veriItem: {
            norec: e.norec || '',
            norec_detail: e.norec_detail || '',
            lokasikalibrasi: nn(e.lokasikalibrasiUpdate?.value),
            lokasirepair: nn(e.lokasiRepairUpdate?.value),
            lingkupkalibrasi: nn(e.lingkupkalibrasiUpdate?.value),
            penyeliateknik: nn(e.penyeliateknikUpdate?.value),
            pelaksana: nn(e.pelaksanaUpdate?.value),
            durasikalbrasi: nn(e.durasikalbrasiUpdate),
            paketkalibrasi: nn(e.paketkalibrasiUpdate?.value),
            vendorfk: nn(e.vendorkalibrasiUpdate?.value),
        },
    };
    isLoadingSave.value = true
    await useApi().post('/asman/save-verif-item', json).then((r) => {
        isLoadingSave.value = false
        reloadItemVerify(e.norec)
        clear()
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


const save = async (jenis: 'kalibrasi' | 'repair') => {
    const e = item.value
    if (jenis === 'kalibrasi' && !allIsVendor.value && dataNamaPaket == null) {
        const belum = groupLingkup.value.filter((g: any) => !g.tanggalmulai)
        if (belum.length > 0) {
            const nama = belum.map((g: any) => g.lingkupkalibrasi || '').join(', ')
            useToaster().error(
                `Tanggal Mulai harus diisi untuk semua lingkup kalibrasi. Belum diisi: ${nama}`,
            )
            return
        }
    }

    const json = {
        verif: {
            norec: e.norec ?? '',
            lokasikalibrasi: jenis === 'kalibrasi' ? (e.lokasikalibrasi ?? null) : null,
            lingkupkalibrasi: e.lingkupkalibrasi ?? '',
            lokasirepair: jenis === 'repair' ? (e.lokasirepair ?? null) : null,
        },
    }

    isLoadingSave.value = true
    await useApi()
        .post('/asman/save-verif', json)
        .then((r) => {
            isLoadingSave.value = false
            clear()
            modalDetailOrder.value = false
            modalDetailOrderRepair.value = false
            fetchDataOrder(0)
        })
        .catch((error: any) => {
            isLoadingSave.value = false
            console.error('Error saat menyimpan verif:', error)
            if (error.response) {
                H.alert(
                    'error',
                    `Kesalahan: ${error.response.status} - ${error.response.data.message || 'Gagal menyimpan verifikasi'
                    }`,
                )
            } else if (error.request) {
                H.alert('error', 'Tidak ada respons dari server. Silakan coba lagi.')
            } else {
                H.alert('error', `Terjadi kesalahan: ${error.message}`)
            }
        })
}


const cetakSpk = (e: any) => {
    H.printBlade(`asman/cetak-spk?pdf=true&norec=${e.norec}&penyeliateknikfk=${e.penyeliateknikfk}`);
}

const cetakLaporanRepair = (e: any) => {
    if (e.versilaporanrepair == null) {
        H.printBlade(`asman/cetak-laporan-repair?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
    } else {
        H.printBlade(`registrasi/cetak-laporan-repair-pdf?norec_detail=${e.norec_detail}`)
    }
}

const cetakLaporanVerfikasi = (e: any) => {
    H.printBlade(`asman/cetak-laporan-verifikasi?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`)
}


const masukLaporanVerifikasi = async (e: any) => {
    router.push({
        name: 'module-asman-laporan-verifikasi',
        query: {
            norec: e.norec,
            norec_detail: e.norec_detail
        }
    })
}


const masukLembarKerjaSudahIsi = (e: any) => {
    if (e.namasublingkup == 'METER') {
        router.push({
            name: 'module-asman-lembar-kerja',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'SUMBER') {
        router.push({
            name: 'module-asman-lembar-kerja-sumber',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CLAMP') {
        router.push({
            name: 'module-asman-lembar-kerja-clamp',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'VIBRATION METER') {
        router.push({
            name: 'module-asman-lembar-kerja-vibration-meter',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'ACCELLEROMETER') {
        router.push({
            name: 'module-asman-lembar-kerja-accellerometer',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CLAMP & METER') {
        router.push({
            name: 'module-asman-lembar-kerja-clamp-meter',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'METER & SUMBER') {
        router.push({
            name: 'module-asman-lembar-kerja-meter-sumber',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THERMOMETER INFRARED') {
        router.push({
            name: 'module-asman-lembar-kerja-termometer-infrared',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THERMAL IMAGER') {
        router.push({
            name: 'module-asman-lembar-kerja-thermal-imager',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THERMOHYGROMETER') {
        router.push({
            name: 'module-asman-lembar-kerja-thermohygrometer',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'DRYBLOCK') {
        router.push({
            name: 'module-asman-lembar-kerja-dryblock',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'VIBRATION CALIBRATOR') {
        router.push({
            name: 'module-asman-lembar-kerja-vibration-calibrator',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CALIPER') {
        router.push({
            name: 'module-asman-lembar-kerja-caliper-micrometer',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'DIAL INDICATOR') {
        router.push({
            name: 'module-asman-lembar-kerja-dial-indicator',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'BORE GAUGE') {
        router.push({
            name: 'module-asman-lembar-kerja-bore-gauge',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CLAMP & METER & SUMBER') {
        router.push({
            name: 'module-asman-lembar-kerja-clamp-meter-sumber',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (['KALIBRASI AI', 'KALIBRASI AI SUHU'].includes(e.namasublingkup)) {
        router.push({
            name: 'module-asman-lembar-kerja-yolo',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'CONTINUITY +SOURCE') {
        router.push({
            name: 'module-asman-lembar-kerja-continuity-+source',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'TACHOMETER') {
        router.push({
            name: 'module-asman-lembar-kerja-tachometer',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'INSULATION + METER') {
        router.push({
            name: 'module-asman-lembar-kerja-insulation-meter-resistansi',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'TORQUE WRENCH') {
        router.push({
            name: 'module-asman-lembar-kerja-torque-wrench',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'OSCILLOSCOPE') {
        router.push({
            name: 'module-asman-lembar-kerja-oscilloscope',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'TEMPERATURE INDICATOR WITH SENSOR') {
        router.push({
            name: 'module-asman-lembar-kerja-temperature-indicator-with-sensor',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'ULTRASONIC THICKNESS') {
        router.push({
            name: 'module-asman-lembar-kerja-ultrasonic-thickness',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THICKNESS GAUGE') {
        router.push({
            name: 'module-asman-lembar-kerja-thickness-gauge',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
} else if (e.namasublingkup == 'FEELER GAUGE') {
        router.push({
            name: 'module-asman-lembar-kerja-feeler-gauge',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'INSULATION TESTER') {
        router.push({
            name: 'module-asman-lembar-kerja-insulation-tester',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'INSULATION SOURCE') {
        router.push({
            name: 'module-asman-lembar-kerja-insulation-source',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'THERMOCOUPLE/RTD SIMULATOR') {
        router.push({
            name: 'module-asman-lembar-kerja-thermocouple-rtd-simulator',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'MICROMETER HEAD') {
        router.push({
            name: 'module-asman-lembar-kerja-micrometer-head',
            query: {
                norec: e.norec,
                norec_detail: e.norec_detail,
                sublingkup: e.sublingkupfk,
            }
        })
    } else if (e.namasublingkup == 'OUTSIDE MICROMETER') {
        router.push({
            name: 'module-asman-lembar-kerja-outside-micrometer',
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
        name: 'module-asman-lembar-kerja-tekanan',
        query: {
            norec: e.norec,
            norec_detail: e.norec_detail
        }
    })
}

const laporanRepair = (e: any) => {
    router.push({
        name: 'module-asman-laporan-repair',
        query: {
            norec: e.norec,
            norec_detail: e.norec_detail,
        }
    })
}

const reloadItemVerify = async (e: any) => {
    const response = await useApi().get(`/asman/layanan-verif?norec_pd=${e}`)
    response.detail.forEach((element: any, i: any) => {
        element.no = i + 1
    })

    dataNamaPaket.value = response.detail[0].namapaket
    dataPaket.value = response.detail[0].totalDurasi
    dataPaketTanggal.value = response.detail[0].tanggalSelesai
    item.value.paketkalibrasiUpdate = {
        value: response.detail[0].idpaket ?? '',
        label: response.detail[0].namapaket ?? '',
    }

    detailOrderLayanan.value = response.detail
    buildLingkupGroups(response.detail)
    try {
        buildCharts(response)
    } catch (err) {
        console.error("Skip chart reload", err)
    }
}

const updatePaketLingkup = async () => {
    if (!item.value.paketkalibrasiUpdate?.value) {
        useToaster().error(`Paket kalibrasi belum dipilih}`)
        return
    }

    const json = {
        updatePaket: {
            norec: item.value.norec ? item.value.norec : '',
            paketkalibrasi: item.value.paketkalibrasiUpdate.value,
        },
    }

    isLoadingSave.value = true
    await useApi()
        .post('/asman/save-update-paket', json)
        .then(async () => {
            await reloadItemVerify(item.value.norec)
        })
        .catch((error: any) => {
            console.error('Error saat menyimpan paket per lingkup:', error)
            if (error.response) {
                H.alert(
                    'error',
                    `Kesalahan: ${error.response.status} - ${error.response.data.message || 'Gagal menyimpan paket per lingkup'
                    }`,
                )
            } else if (error.request) {
                H.alert('error', 'Tidak ada respons dari server. Silakan coba lagi.')
            } else {
                H.alert('error', `Terjadi kesalahan: ${error.message}`)
            }
        })
        .finally(() => {
            isLoadingSave.value = false
        })
}

const updateTglMulai = async (g: any) => {
    if (!g.tanggalmulai) {
        useToaster().error(
            `Tanggal mulai belum diisi untuk lingkup ${g.lingkupkalibrasi || ''}`
        )
        return
    }
    const snapshot = new Map<any, any>()
    groupLingkup.value.forEach((row: any) => {
        const key = row.lingkupfk ?? row.lingkupkalibrasi
        snapshot.set(key, row.tanggalmulai || null)
    })
    const json = {
        updatePaket: {
            norec: item.value.norec ? item.value.norec : '',
            tanggalmulai: H.formatDate(g.tanggalmulai, 'YYYY-MM-DD HH:mm:ss'),
            lingkupfk: g.lingkupfk ?? null,
        },
    }
    isLoadingSave.value = true
    await useApi()
        .post('/asman/save-update-tglmulai', json)
        .then(async () => {
            await reloadItemVerify(item.value.norec)
            groupLingkup.value.forEach((row: any) => {
                const key = row.lingkupfk ?? row.lingkupkalibrasi
                if (!row.tanggalmulai && snapshot.has(key)) {
                    row.tanggalmulai = snapshot.get(key)
                }
            })
        })
        .catch((error: any) => {
            console.error('Error saat menyimpan Tanggal Mulai lingkup:', error)
            if (error.response) {
                H.alert(
                    'error',
                    `Kesalahan: ${error.response.status} - ${error.response.data.message ||
                    'Gagal menyimpan Tanggal Mulai lingkup'
                    }`
                )
            } else if (error.request) {
                H.alert('error', 'Tidak ada respons dari server. Silakan coba lagi.')
            } else {
                H.alert('error', `Terjadi kesalahan: ${error.message}`)
            }
        })
        .finally(() => {
            isLoadingSave.value = false
        })
}

const hapusItems = (e: any) => {
    console.log(e)
    item.value.norec = e.norec
    item.value.norec_detail = e.norec_detail
    item.value.lokasikalibrasifk = e.lokasikalibrasifk
    item.value.nopendaftaran = e.nopendaftaran
    modalPembatalanOrderAlat.value = true
}

const SaveHapusItem = async (e: any) => {
    const json = {
        itembatal: {
            norec: e.norec || '',
            norec_detail: e.norec_detail || '',
            alasanpembatalan: e.alasanpembatalan || '',
            lokasikalibrasifk: e.lokasikalibrasifk || '',
            nopendaftaran: e.nopendaftaran || '',
        },
    };
    isLoadingSave.value = true
    await useApi().post('/asman/save-batal-order-alat', json).then((r) => {
        isLoadingSave.value = false
        modalPembatalanOrderAlat.value = false
        reloadItemVerify(e.norec)
        clear()
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


const changeSwitch = (e: any) => {
    fetchDataOrder(e)
}

const changeSwitchAlat = (e: any) => {
    fetchAlatKalibrasi(e)
}

const clear = () => {
    item.value.id = ''
    delete item.value.no
    item.value.pelaksanaUpdate = ''
    item.value.lokasikalibrasiUpdate = ''
    item.value.lingkupkalibrasiUpdate = ''
    item.value.penyeliateknikUpdate = ''
    item.value.durasikalbrasiUpdate = ''
    item.value.lokasiRepairUpdate = ''
    item.value.lokasikalibrasi = null
    item.value.lokasirepair = null
    item.value.paketkalibrasiUpdate = ''
    item.value.vendorkalibrasiUpdate = ''
    item.value.alasanpembatalan = ''
}


const changePeriode = () => {
    item.value.filterTgl = {
        start: item.value.periode.start,
        end: item.value.periode.end,
    }
    saveFilters()
    modalFilter.value = false
    fetchDataOrder(0)
}


const reload = async () => {
    fetchDataOrder(0)
}

onMounted(() => {
    const key = 'ulab_welcome_banner_customer_v1'
    if (localStorage.getItem(key) !== '1') {
        openWelcome.value = true
    }
})

loadFilters()
fetchAlatKalibrasi(0)
fetchDataOrder(0)
fetchDetail()
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

/* Style untuk layar besar (desktop) */
.date-filter-container {
    margin-left: 23rem;
    margin-top: -4.25rem;
    margin-bottom: 20px;
    padding: 0px;
}

/* Media Query untuk layar mobile (lebar maksimal 768px) */
@media (max-width: 768px) {
    .date-filter-container {
        margin-left: 0;
        margin-top: 1rem;
        margin-bottom: 1rem;
        width: 100%;
    }
}


.tolak-serti-anim {
    animation: shake-loop 1.3s cubic-bezier(.36, .07, .19, .97) infinite;
    display: flex;
    align-items: center;
    gap: 9px;
    color: #e53935;
    font-weight: bold;
    font-size: 1rem;
    /* Ukuran lebih besar */
    letter-spacing: 1px;
    margin-top: 10px;
    text-shadow: 0 2px 10px #fff6;
    user-select: none;
}

.tolak-serti-anim svg {
    width: 18px !important;
    height: 18px !important;
    flex-shrink: 0;
}

.tolak-serti-text {
    display: inline-block;
    vertical-align: middle;
    margin-left: 8px;
}

.tolak-serti-title {
    line-height: 1.2;
}

.tolak-serti-ket {
    margin-top: 4px;
    /* enter */
    line-height: 1.2;
}

.tolak-serti-red {
    color: #e53935;
    /* merah */
}

/* efek merah tipis untuk seluruh card */
.card-tolak-serti {
    background: rgba(237, 15, 11, 0.06) !important;
    border: 1px solid rgba(229, 57, 53, 0.18);
    box-shadow: 0 6px 18px rgba(229, 57, 53, 0.08);
}

/* biar sudutnya konsisten dengan card Anda (opsional) */
.card-tolak-serti .list-view-item-inner {
    border-radius: 12px !important;
}

/* transisi halus (opsional) */
.list-view-item {
    transition: background-color .2s ease, box-shadow .2s ease, border-color .2s ease !important;
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
