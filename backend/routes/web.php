<?php
/*
| Ever tried.
| Ever failed.
| No matter.
| Try Again.
| Fail again.
| Fail better".
| , Samuel Beckett
|
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthCtrl;
use App\Http\Controllers\General\GeneralCtrl;
use App\Http\Controllers\General\SysAdminCtrl;
use App\Http\Controllers\Registrasi\MitraCtrl;
use App\Http\Controllers\Customer\CustomerCtrl;
use App\Http\Controllers\Asman\AsmanCtrl;
use App\Http\Controllers\Manager\ManagerCtrl;
use App\Http\Controllers\Penyelia\PenyeliaCtrl;
use App\Http\Controllers\Pelaksana\PelaksanaCtrl;
use App\Http\Controllers\Sysadmin\MasterSukuCtrl;
use App\Http\Controllers\Sysadmin\MasterAgamaCtrl;
use App\Http\Controllers\Registrasi\MitraBaruCtrl;
use App\Http\Controllers\Registrasi\MitraLamaCtrl;
use App\Http\Controllers\Sysadmin\MasterNegaraCtrl;
use App\Http\Controllers\Sysadmin\MasterProdukCtrl;
use App\Http\Controllers\Sysadmin\MasterJabatanCtrl;
use App\Http\Controllers\Sysadmin\MasterPegawaiCtrl;
use App\Http\Controllers\Sysadmin\MasterRuanganCtrl;
use App\Http\Controllers\Sysadmin\MasterProvinsiCtrl;
use App\Http\Controllers\Sysadmin\MasterKecamatanCtrl;
use App\Http\Controllers\Sysadmin\MasterPekerjaanCtrl;
use App\Http\Controllers\Sysadmin\MasterPendidikanCtrl;
use App\Http\Controllers\Sysadmin\SettingDataFixedCtrl;
use App\Http\Controllers\Dashboard\DashboardPegawaiCtrl;
use App\Http\Controllers\Dashboard\DashboardMasterDataCtrl;
use App\Http\Controllers\Sysadmin\MasterJenisProdukCtrl;
use App\Http\Controllers\Sysadmin\MasterMapKelompokCtrl;
use App\Http\Controllers\Sysadmin\MasterJenisKelaminCtrl;
use App\Http\Controllers\Sysadmin\MasterInstruksiKerjaCtrl;
use App\Http\Controllers\Sysadmin\MasterAlatStandarCtrl;
use App\Http\Controllers\Sysadmin\MasterSnAlatCtrl;
use App\Http\Controllers\Sysadmin\MasterJenisPegawaiCtrl;
use App\Http\Controllers\Sysadmin\MasterKelompokUserCtrl;
use App\Http\Controllers\Registrasi\RegistrasiMitraCtrl;
use App\Http\Controllers\pbj\PengajuanPbjCtrl;
use App\Http\Controllers\pbj\KendaliPengadaanCtrl;
use App\Http\Controllers\Sysadmin\MasterModulAplikasiCtrl;
use App\Http\Controllers\Sysadmin\MasterDokumenCtrl;
use App\Http\Controllers\Udr\UdrCtrl;
use App\Http\Controllers\Sysadmin\MasterKelompokProdukCtrl;
use App\Http\Controllers\Sysadmin\MasterTambahLoginUserCtrl;
use App\Http\Controllers\Sysadmin\MasterMapLoginUserToRuanganCtrl;
use App\Http\Controllers\Sysadmin\MasterMapLoginUserToModulAplikasiCtrl;
use App\Http\Controllers\Sysadmin\MasterListCtrl;
use App\Http\Controllers\Laporan\LaporanPelaksanaCtrl;
use App\Http\Controllers\Laporan\LaporanRegistrasiAlatCtrl;
use App\Http\Controllers\Laporan\LaporanTindakanPasienCtrl;
use App\Models\Master\User;
use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\Landing\NoAuthCtrl;
use App\Http\Controllers\Mutu\MutuCtrl;
use App\Http\Controllers\Sysadmin\MasterVendorCtrl;
use App\Http\Controllers\Sysadmin\MasterUserUnitCtrl;
use App\Http\Controllers\Chat\ChatCtrl;
use App\Http\Controllers\ChatBot\ApiChatBotCtrl;
use App\Http\Controllers\RBK\RbkCtrl;
use App\Http\Controllers\Sysadmin\HariLiburCtrl;
use App\Http\Controllers\Mobile\MobileNotificationCtrl;

Route::middleware(['jwt.auth'])->prefix("service")->group(function () {
    Route::middleware(['log'])->group(function () {

        Route::prefix("general/menu")->group(function () {
            Route::controller(SysAdminCtrl::class)->group(function () {
                Route::get('/list-menu', 'listMenu');
            });
        });

        Route::controller(SysAdminCtrl::class)->group(function () {
            Route::get('/get-time-server', 'getTimeServer');
            Route::get('/surkes/mapping-surkes-dropdown', 'getDropdownMappingSurkes');
            Route::get('/surkes/get-alat-by-unit', 'getAlatByUnit');
            Route::get('/surkes/list-mapping-alat', 'getListMappingAlat');
            Route::get('/surkes/monitoring-alat', 'getMonitoringAlatSurkes');
            Route::post('/surkes/save-mapping-alat-lingkup', 'saveMappingAlatLingkup');
            Route::post('/surkes/delete-mapping-alat', 'deleteMappingAlatSurkes');
            Route::get('/layanan/mapping-layanan-dropdown', 'getDropdownMappingLayanan');
            Route::get('/layanan/list-mapping-layanan', 'getListMappingLayanan');
            Route::post('/layanan/save-mapping-layanan', 'saveMappingLayanan');
            Route::post('/layanan/upload-bulk-mapping-layanan-image', 'uploadBulkMappingLayananImage');
            Route::post('/layanan/set-status-mapping-layanan', 'setStatusMappingLayanan');
        });

        Route::controller(DashboardPegawaiCtrl::class)->group(function () {
            Route::get('dashboard/data-pegawai', 'dashboardPegawai');
            Route::get('dashboard/data-pegawaiaktif', 'DataPegawaiAktif');
            Route::get('dashboard/get-ruangpegawai', 'getRuangKerja');

            Route::get('dashboard/get-detail-pegawai', 'getJumlah');
        });
        Route::controller(DashboardMasterDataCtrl::class)->group(function () {
            Route::get('dashboard/dashboard-sysadmin', 'dashboardMasterData');
            Route::get('dashboard/dashboard-masterdata', 'DataMaster');

            Route::post('dashboard/save-master-list', 'saveListMaster');
        });

        Route::controller(MitraLamaCtrl::class)->group(function () {
            Route::get('registrasi/mitra-lama', 'mitraLama');
            Route::get('registrasi/cek-pasien-pulang', 'cekPulangpasien');
            Route::get('registrasi/cek-sep', 'cekSEPpasien');
            Route::post('registrasi/delete-mitra', 'deleteMitra');
        });

        Route::controller(MitraBaruCtrl::class)->group(function () {
            Route::get('registrasi/desa-kelurahan-paging', 'listDesaKelurahanPaging');
            Route::get('registrasi/kecamatan-paging', 'listKecamatanPaging');
            Route::get('registrasi/kotakabupaten', 'listKotaKab');
            Route::get('registrasi/kecamatan', 'listKecamatan');
            Route::get('registrasi/desakelurahan', 'listDesa');
            Route::get('registrasi/list-mitra-dropdown', 'listDropdown');
            Route::get('registrasi/mitra', 'mitraByID');
            Route::get('registrasi/riwayat-registrasi', 'riwayatRegistrasi');

            Route::post('registrasi/save-mitra', 'saveMitra');
            Route::post('registrasi/save-pasien-foto', 'saveMitraFoto');
        });
        Route::controller(RegistrasiMitraCtrl::class)->group(function () {
            Route::get('registrasi/mitra-registrasi', 'mitraRegistrasi');
            Route::post('registrasi/save-registrasi-mitra', 'saveRegistrasiMitra');
            Route::post('registrasi/save-registrasi-repair-mitra', 'saveRegistrasiRepairMitra');
            Route::post('registrasi/save-survey-pelanggan', 'saveSurveyPelanggan');
            Route::post('registrasi/save-penilaian-pelanggan', 'savePenilaianPelanggan');
            Route::get('registrasi/get-survey-pelanggan', 'getSurveyPelanggan');
            Route::get('registrasi/dropdown-paket-kalibrasi', 'dropdownPaketKalibrasi');
            Route::post('registrasi/save-registrasi-standar-ulab', 'saveRegistrasiStandarUlab');
            Route::post('registrasi/save-registrasi-kalibrasi-ulab', 'saveRegisKalibrasiUlab');
        });

        Route::controller(PengajuanPbjCtrl::class)->group(function () {
            Route::get('pbj/dashboard-data', 'getDashboardDataPbj');
            Route::post('pbj/save-pengajuan-pbj', 'savePengajuanPBJ');
            Route::post('pbj/submit-pengajuan-pbj-asman', 'ajukanPengajuanPBJ');
            Route::post('pbj/delete-draft-pengajuan-pbj', 'deleteDraftPengajuanPBJ');
            Route::post('pbj/delete-draft-iht-pbj', 'deleteDraftIhtPbj');
            Route::post('pbj/replace-draft-iht-pbj', 'replaceDraftIhtPbj');
            Route::post('pbj/approval-asman', 'saveApprovalPbjAsman');
            Route::post('pbj/approval-manager', 'saveApprovalPbjManager');
            Route::post('pbj/approval-rendal', 'saveApprovalPbjRendal');
            Route::post('pbj/approval-inven1', 'saveApprovalPbjInven1');
            Route::post('pbj/approval-inven2', 'saveApprovalPbjInven2');
            Route::post('pbj/approval-ba1', 'saveApprovalPbjBa1');
            Route::post('pbj/approval-asmanba1', 'saveApprovalPbjAsmanBa1');
            Route::post('pbj/approval-ba2', 'saveApprovalPbjBa2');
            Route::post('pbj/approval-pembayaran', 'saveApprovalPbjPembayaran');
            Route::post('pbj/approval-pembebanan', 'saveApprovalPbjPembebanan');
            Route::post('pbj/approval-permintaan-limit', 'saveApprovalPbjPermintaanlimit');
            Route::get('pbj/cetak-pbj-iht', 'cetakIht');
            Route::get('pbj/get-pengajuan-pbj', 'getPengajuanPbj');
            Route::get('pbj/get-pengajuan-pbj-detail', 'getDetailPengajuanPbj');
            Route::get('pbj/get-pengajuan-pbj-asman', 'getPengajuanPbjAsman');
            Route::get('pbj/get-riwayat-pbj-asman', 'getRiwayatPbjAsman');
            Route::get('pbj/get-pengajuan-pbj-manager', 'getPengajuanPbjManager');
            Route::get('pbj/get-riwayat-pbj-manager', 'getRiwayatPbjManager');
            Route::get('pbj/get-pengajuan-pbj-rendal', 'getPengajuanPbjRendal');
            Route::get('pbj/get-riwayat-pbj-rendal', 'getRiwayatPbjRendal');
            Route::get('pbj/get-pengajuan-pbj-inven1', 'getPengajuanPbjInven1');
            Route::get('pbj/get-riwayat-pbj-inven1', 'getRiwayatPbjInven1');
            Route::get('pbj/get-pengajuan-pbj-inven2', 'getPengajuanPbjInven2');
            Route::get('pbj/get-riwayat-pbj-inven2', 'getRiwayatPbjInven2');
            Route::get('pbj/get-pengajuan-pbj-ba1', 'getPengajuanPbjBa1');
            Route::get('pbj/get-riwayat-pbj-ba1', 'getRiwayatPbjBa1');
            Route::get('pbj/get-pengajuan-pbj-asmanba1', 'getPengajuanPbjAsmanBa1');
            Route::get('pbj/get-riwayat-pbj-asmanba1', 'getRiwayatPbjAsmanBa1');
            Route::get('pbj/get-pengajuan-pbj-ba2', 'getPengajuanPbjBa2');
            Route::get('pbj/get-riwayat-pbj-ba2', 'getRiwayatPbjBa2');
            Route::get('pbj/get-pengajuan-pbj-pembayaran', 'getPengajuanPbjPembayaran');
            Route::get('pbj/get-riwayat-pbj-pembayaran', 'getRiwayatPbjPembayaran');
            Route::get('pbj/get-pengajuan-pbj-pembebanan', 'getPengajuanPbjPembebanan');
            Route::get('pbj/get-riwayat-pbj-pembebanan', 'getRiwayatPbjPembebanan');
            Route::get('pbj/get-pengajuan-pbj-permintaan-limit', 'getPengajuanPbjPermintaanlimit');
            Route::get('pbj/get-riwayat-pbj-permintaan-limit', 'getRiwayatPbjPermintaanlimit');
            Route::get('pbj/cetak-pbj', 'cetakPBJ');
            Route::get('pbj/fetch-unit-surkes', 'fetchUnitSurkes');
            Route::get('pbj/get-riwayat-pbj-super-admin', 'getRiwayatPbjSuperAdmin');
        });

        Route::controller(KendaliPengadaanCtrl::class)->group(function () {
            Route::get('pbj/pengadaan/hps', 'getDaftarHps');
            Route::get('pbj/pengadaan/hps/nomor-next', 'nextHpsNumber');
            Route::post('pbj/pengadaan/hps/nomor', 'saveHpsNumber');
            Route::get('pbj/pengadaan/hps/cetak', 'cetakHps');
            Route::get('pbj/pengadaan/pp', 'getDaftarPp');
            Route::post('pbj/pengadaan/pp', 'savePp');
            Route::post('pbj/pengadaan/pp/upload', 'uploadPpFiles');
            Route::get('pbj/pengadaan/pp/file', 'downloadPpFile');
            Route::get('pbj/pengadaan/pp/file/view', 'viewPpFile');
            Route::post('pbj/pengadaan/pp/file/hapus', 'deletePpFile');
            Route::get('pbj/pengadaan/pp/cetak', 'cetakPp');
            Route::get('pbj/pengadaan/bapp', 'getDaftarBapp');
            Route::post('pbj/pengadaan/bapp', 'saveBapp');
            Route::post('pbj/pengadaan/bapp/penyedia', 'saveBappPenyedia');
            Route::post('pbj/pengadaan/bapp/penyedia/hapus', 'deleteBappPenyedia');
            Route::get('pbj/pengadaan/bapp/cetak', 'cetakBapp');
        });

        Route::controller(CustomerCtrl::class)->group(function () {
            Route::get('customer/get-status-customer', 'getStatusCustomer');
            Route::get('customer/get-alat-customer', 'getAlatCustomer');
            Route::get('customer/get-keranjang-customer', 'keranjangCustomer');
            Route::get('customer/get-history-order-customer', 'historyOrder');
            Route::get('customer/get-history-order-customer-kelompok', 'historyOrderKelompok');
            Route::get('customer/laporan-customer', 'laporanCustomer');
            Route::post('customer/save-keranjang-customer', 'saveKeranjangCustomer');
            Route::post('customer/save-checkout', 'saveRegistrasiCustomer');
            Route::post('customer/save-status-customer', 'saveStatusCustomer');
            Route::get('customer/dropdown-unit-eksternal', 'dropdownUnitEksternal');
            Route::post('customer/hapus-keranjang-customer', 'hapusKeranjangCustomer');
            Route::get('customer/get-alat', 'fetchAlat');
            Route::get('customer/history-alat', 'historyAlat');
            Route::get('customer/cetak-qr-alat', 'cetakBarcodeAlat');
            Route::get('customer/detail-registrasi-alat', 'getHistoryRegisAlatCustomer');
            Route::get('customer/riwayat-amandemen', 'getRiwayatAmandemen');
        });

        Route::controller(MutuCtrl::class)->group(function () {
            Route::get('mutu/get-history', 'getHistory');
            Route::get('mutu/get-penilaian-pelanggan', 'getPenilaianPelanggan');
            Route::get('mutu/get-survey-pelanggan-chart', 'getSurveyPelangganChart');
            Route::get('mutu/get-survey-pelanggan-coverage', 'getSurveyPelangganCoverage');
            Route::get('mutu/get-analisis-ai-pelanggan', 'getAnalisiAISurvey');
            Route::get('mutu/master-isi-survey', 'masterIsiSurvey');
            Route::get('mutu/master-history-survey-nourut', 'lastNourutHistorySurvey');
            Route::post('mutu/kirim-isi-survey-pelanggan', 'kirimPeringatanIsiSurvey');
            Route::post('mutu/save-isi-dokumen-survey', 'saveIsiHistorySurvey');
            Route::get('mutu/cetak-dokumen-history-survey', 'cetakDokumenSurvey');
            Route::post('mutu/hapus-isi-survey', 'hapusIsiSurvey');
            Route::post('mutu/save-master-pelatihan', 'saveMasterPelatihan');
            Route::post('mutu/pengajuan-pelatihan', 'savePengajuanPelatihan');
            Route::get('mutu/list-pelatihan', 'getListPelatihan');
            Route::get('mutu/list-pengajuan-pelatihan', 'getPengajuanPelatihan');
            Route::post('mutu/hapus-master-pelatihan', 'hapusMasterPelatihan');
            Route::post('mutu/approval-pengajuan-pelatihan', 'saveApprovalPelatihan');
            Route::post('mutu/hapus-pengajuan-pelatihan', 'hapusPengajuanPelatihan');
            Route::post('mutu/save-riwayat-pelatihan-manual', 'saveRiwayatPelatihanManual');
            Route::get('mutu/cv-pegawai', 'getCvPegawai');
            Route::post('mutu/save-cv-pegawai', 'saveCvPegawai');
            Route::get('mutu/cv-pegawai-riwayat-pelatihan', 'getCvPegawaiRiwayatPelatihan');
            Route::get('mutu/pegawai-by-id', 'pegawaiByID');
            Route::get('mutu/cetak-cv-pegawai', 'cetakCvPegawai');
            Route::get('mutu/referensi-temuan-ketidaksesuaian', 'referensiTemuanKetidaksesuaian');
            Route::get('mutu/pakta-integritas-auditor', 'getPaktaIntegritasAuditor');
            Route::post('mutu/save-pakta-integritas-auditor', 'savePaktaIntegritasAuditor');
            Route::get('mutu/temuan-ketidaksesuaian', 'getTemuanKetidaksesuaian');
            Route::post('mutu/save-audit-ketidaksesuaian', 'saveAuditKetidaksesuaian');
            Route::post('mutu/save-temuan-ketidaksesuaian', 'saveTemuanKetidaksesuaian');
            Route::post('mutu/hapus-temuan-ketidaksesuaian', 'hapusTemuanKetidaksesuaian');
            Route::post('mutu/hapus-audit-ketidaksesuaian', 'hapusAuditKetidaksesuaian');
            Route::get('mutu/cetak-temuan-ketidaksesuaian', 'cetakTemuanKetidaksesuaian');
            Route::get('mutu/cetak-pakta-integritas-auditor', 'cetakPaktaIntegritasAuditor');
            Route::get('mutu/view-dokumen-audit-internal', 'viewDokumenAuditInternal');
            Route::get('mutu/mapping-auditor-internal', 'getMappingAuditorInternal');
            Route::post('mutu/save-mapping-auditor-internal', 'saveMappingAuditorInternal');
            Route::post('mutu/hapus-mapping-auditor-internal', 'hapusMappingAuditorInternal');
            Route::post('mutu/salin-mapping-auditor-internal', 'salinMappingAuditorInternal');
            Route::get('mutu/closed-verification-auditor', 'getClosedVerificationAuditor');
            Route::get('mutu/pilihan-dokumen-audit-internal', 'pilihanDokumenAuditInternal');
            Route::post('mutu/save-closed-verification-auditor', 'saveClosedVerificationAuditor');
            Route::get('mutu/cetak-closed-verification-auditor', 'cetakClosedVerificationAuditor');
            Route::post('mutu/save-tanggal-penetapan-lha', 'saveTanggalPenetapanLha');
            Route::get('mutu/cetak-laporan-hasil-audit', 'cetakLaporanHasilAudit');
        });

        Route::controller(MitraCtrl::class)->group(function () {
            Route::get('registrasi/list-mitra-grid', 'listMitraGrid');
            Route::get('registrasi/dashboard-statistik', 'dashboardStatistik');
            Route::get('registrasi/get-alat-registrasi', 'getAlatRegistrasi');
            Route::get('registrasi/count-daftar', 'CountDaftar');
            Route::get('registrasi/header-mitra', 'HeaderMitra');
            Route::get('registrasi/layana-mitra', 'LayananKajian');
            Route::get('registrasi/alat-tersedia-kaji-ulang', 'alatTersediaKajiUlang');
            Route::post('registrasi/tambah-alat-kaji-ulang', 'tambahAlatKajiUlang');
            Route::get('registrasi/pegawai-kalibrasi', 'pegawaiManager');
            Route::get('registrasi/pegawai-lokasi-kalibrasi', 'pegawaiLokasi');
            Route::post('registrasi/save-kajian-ulang-item', 'saveKajianUlangItem');
            Route::post('registrasi/save-kajian-ulang-item-vendor', 'saveKajianUlangItemVendor');
            Route::post('registrasi/save-kaji-ulang', 'saveKajiUlang');
            Route::post('registrasi/save-batal-regis-mitra', 'saveBatalRegis');
            Route::post('registrasi/save-konfirmasi-pendaftaran', 'saveKonfirmasiPendaftaran');
            Route::get('registrasi/cetak-tanda-terima', 'cetakTandaTerima');
            Route::get('registrasi/cetak-permintaan-kalibrasi', 'cetakPermintaanKalibrasi');
            Route::post('registrasi/save-penolakan-alat', 'savePenolakanALat');
            Route::get('registrasi/produk-by-id', 'produkByMitra');
            Route::get('registrasi/get-alat-verif-customer', 'getVerifAlatCustomer');
            Route::post('registrasi/save-verifikasi-regis-customer', 'saveVerifikasiRegisCustomer');
            Route::get('registrasi/cetak-ams', 'cetakAms');
            Route::get('registrasi/download-tools-customer', 'downloadToolsCustomer');
            Route::get('registrasi/cetak-barcode-order', 'cetakBarcodeOrder');
            Route::post('registrasi/save-selesai-penerima', 'saveSelesaiTerima');
            Route::get('registrasi/cetak-selesai-terima', 'cetakSelesaiTerima');
            Route::post('registrasi/save-sertifikat-vendor', 'saveSertifikatVendor');
            Route::get('registrasi/cetak-sertifikat-vendor', 'cetakSertiVendor');
            Route::post('registrasi/save-batal-kalibrasi-alat', 'saveBatalKalibrasiAlat');
            Route::post('registrasi/save-ubah-pelaksana', 'saveUbahPelaksana');
            Route::post('registrasi/save-ubah-vendor', 'saveUbahVendor');
            Route::get('registrasi/layanan-terima', 'LayananVerif');
            Route::get('registrasi/list-versi-terima', 'listVersiTerima');
            Route::post('registrasi/save-ams-baru', 'saveAmsBaru');
            Route::get('registrasi/fetch-ulab', 'fetcUlab');
            Route::get('registrasi/alat-standar-ulab', 'alatStandarUlab');
            Route::get('registrasi/cetak-sertif-customer-pdf', 'cetakSertifikatLembarKerjaPdf');
            Route::get('registrasi/cetak-laporan-repair-pdf', 'cetakLaporanRepairPdf');
            Route::get('registrasi/riwayat-amandemen', 'getRiwayatAmandemen');
            Route::get('registrasi/list-registrasi-surat-jalan',  'listRegistrasiSuratJalan');
            Route::get('registrasi/layanan-surat-jalan', 'layananSuratJalan');
            Route::get('registrasi/riwayat-surat-jalan', 'riwayatSuratJalan');
            Route::get('registrasi/detail-surat-jalan', 'detailSuratJalan');
            Route::post('registrasi/save-surat-jalan', 'saveSuratJalan');
            Route::post('registrasi/batal-surat-jalan', 'batalSuratJalan');
            Route::get('registrasi/cetak-surat-jalan', 'cetakSuratJalan');
        });

        Route::controller(AsmanCtrl::class)->group(function () {
            Route::get('asman/list-mitra-regis', 'listMitraAsmanGrid');
            Route::get('asman/get-detail-pegawai', 'getAsmanDetail');
            Route::get('asman/layanan-verif', 'LayananVerif');
            Route::get('asman/detail-produk', 'detailProduk');
            Route::post('asman/save-verif-item', 'saveVerifItem');
            Route::post('asman/save-update-paket', 'updatePaket');
            Route::post('asman/save-update-tglmulai', 'updateTglMulai');
            Route::post('asman/save-verif', 'saveVerif');
            Route::post('asman/save-penolakan', 'savePenolakanAsman');
            Route::get('asman/header-mitra', 'HeaderMitra');
            Route::get('asman/cetak-spk', 'cetakSPK');
            Route::get('asman/get-alat-asman', 'getAlatAsman');
            Route::get('asman/get-lembar-kerja-asman', 'getLembarKerjaAsman');
            Route::get('asman/get-lembar-kerja-sumber', 'getLembarKerjaSumber');
            Route::get('asman/get-lembar-kerja-clamp', 'getLembarKerjaClamp');
            Route::get('asman/get-lembar-kerja-vibration', 'getLembarKerjaVibrationMeter');
            Route::get('asman/get-lembar-kerja-vibration-calibrator', 'getLembarKerjaVibrationCalibrator');
            Route::get('asman/get-lembar-kerja-accellerometer', 'getLembarKerjaAccellerometer');
            Route::get('asman/get-lembar-kerja-tekanan', 'getLembarKerjaTekanan');
            Route::get('asman/get-lembar-kerja-clamp-meter', 'getLembarKerjaCLampMeter');
            Route::get('asman/get-lembar-kerja-meter-sumber', 'getLembarKerjaMeterSumber');
            Route::get('asman/get-lembar-kerja-clamp-meter-sumber', 'getLembarKerjaClampMeterSumber');
            Route::get('asman/get-lembar-kerja-thermometer-infrared', 'getLembarKerjaThermometerInfrared');
            Route::get('asman/get-lembar-kerja-thermal-imager', 'getLembarKerjaThermalImager');
            Route::get('asman/get-lembar-kerja-thermohygrometer', 'getLembarKerjaThermohygrometer');
            Route::get('asman/get-lembar-kerja-dryblock', 'getLembarKerjaDryblock');
            Route::get('asman/get-lembar-kerja-caliper-micrometer', 'getLembarKerjaCaliperMicrometer');
            Route::get('asman/get-lembar-kerja-micrometer-head', 'getLembarKerjaMicrometerHead');
            Route::get('asman/get-lembar-kerja-outside-micrometer', 'getLembarKerjaOutsideMicrometer');
            Route::get('asman/get-lembar-kerja-oscilloscope', 'getLembarKerjaOscilloscope');
            Route::get('asman/get-lembar-kerja-dial-indicator', 'getLembarKerjaDialIndicator');
            Route::get('asman/get-lembar-kerja-bore-gauge', 'getLembarKerjaBoreGauge');
            Route::get('asman/get-lembar-kerja-AI', 'getLembarKerjaAI');
            Route::get('asman/get-lembar-kerja-continuity-source', 'getLembarKerjaContinuitySource');
            Route::get('asman/get-lembar-kerja-insulation-source', 'getLembarKerjaInsulationSource');
            Route::get('asman/get-lembar-kerja-thermocouple-rtd-simulator', 'getLembarKerjaThermocoupleRTDSimulator');
            Route::get('asman/get-lembar-kerja-tachometer', 'getLembarKerjaTachometer');
            Route::get('asman/get-lembar-kerja-insulation-meter-resistansi', 'getLembarKerjaInsulationMeterResistansi');
            Route::get('asman/get-lembar-kerja-insulation-tester', 'getLembarKerjaInsulationTester');
            Route::get('asman/get-lembar-kerja-torque-wrench', 'getLembarKerjaTorqueWrench');
            Route::get('asman/get-lembar-kerja-temperature-indicator', 'getLembarKerjaTemperatureIndicator');
            Route::get('asman/get-lembar-kerja-ultrasonic-thickness', 'getLembarKerjaUltrasonicThickness');
            Route::get('asman/get-lembar-kerja-thickness-gauge', 'getLembarKerjaThicknessGauge');
            Route::get('asman/get-lembar-kerja-feeler-gauge', 'getLembarKerjaFeelerGauge');
            Route::post('asman/save-setujui-serti-asman', 'setujuiSertifikatAsman');
            Route::get('asman/cetak-sertifikat-lembar-kerja', 'cetakSertifikatLembarKerja');
            Route::get('asman/download-file-terunggah', 'downloadFileTerunggah');
            Route::get('asman/excel-length', 'getExcelLength');
            Route::get('asman/get-laporan-repair', 'getLaporanRepairAsman');
            Route::get('asman/detail-alat-repair', 'detailAlatRepairAsman');
            Route::post('asman/hapus-laporan-repair', 'hapusLaporanRepairAsman');
            Route::post('asman/save-setujui-laporan-repair', 'setujuiLaporanRepairAsman');
            Route::get('asman/cetak-laporan-repair', 'cetakLaporanRepairAsman');
            Route::post('asman/hapus-status-repair', 'hapusLaporanStatus');
            Route::get('asman/get-data-chart-integrasi', 'dataChartIntegrasi');
            Route::get('asman/get-detail-chart-integrasi', 'detailChartIntegrasi');
            Route::get('asman/unit-by-idmitra', 'dataUnitPerId');
            Route::get('asman/get-history-order-customer', 'historyOrder');
            Route::get('asman/get-history-order-customer-kelompok', 'historyOrderKelompok');
            Route::get('asman/get-laporan-verifikasi', 'getLaporanVerifikasi');
            Route::get('asman/cetak-laporan-verifikasi', 'cetakLaporanVerifikasi');
            Route::get('asman/get-sensor', 'getSensor');
            Route::post('asman/save-batal-order-alat', 'saveBatalOrderAlat');
            Route::post('asman/save-penolakan-sertifikat', 'savePenolakanSerti');
            Route::post('asman/save-penolakan-laporan-repair', 'savePenolakanLapRepair');
            Route::get('asman/get-data-chart-dashboard', 'dataChartDashboard');
            Route::post('asman/approval-pengajuan-amandemen', 'saveApprovalPengajuanAmandemen');
            Route::get('asman/get-pengajuan-amandemen', 'getPengajuanAmandemen');
            Route::get('asman/riwayat-amandemen', 'getRiwayatAmandemen');
            Route::get('asman/get-surat-jalan', 'getSuratJalanAsman');
            Route::get('asman/detail-surat-jalan', 'detailSuratJalanAsman');
            Route::post('asman/save-setujui-surat-jalan', 'saveSetujuiSuratJalanAsman');
            Route::post('asman/save-tolak-surat-jalan', 'saveTolakSuratJalanAsman');
        });

        Route::controller(ManagerCtrl::class)->group(function () {
            Route::get('manager/list-mitra-regis', 'listMitraAsmanGrid');
            Route::get('manager/get-detail-pegawai', 'getAsmanDetail');
            Route::get('manager/detail-produk', 'detailProduk');
            Route::get('manager/header-mitra', 'HeaderMitra');
            Route::get('manager/cetak-spk', 'cetakSPK');
            Route::get('manager/get-alat-manager', 'getAlatManager');
            Route::get('manager/get-lembar-kerja-manager', 'getLembarKerjaManager');
            Route::get('manager/get-lembar-kerja-sumber', 'getLembarKerjaSumber');
            Route::get('manager/get-lembar-kerja-clamp', 'getLembarKerjaClamp');
            Route::get('manager/get-lembar-kerja-vibration', 'getLembarKerjaVibrationMeter');
            Route::get('manager/get-lembar-kerja-vibration-calibrator', 'getLembarKerjaVibrationCalibrator');
            Route::get('manager/get-lembar-kerja-accellerometer', 'getLembarKerjaAccellerometer');
            Route::get('manager/get-lembar-kerja-tekanan', 'getLembarKerjaTekanan');
            Route::get('manager/get-lembar-kerja-clamp-meter', 'getLembarKerjaCLampMeter');
            Route::get('manager/get-lembar-kerja-meter-sumber', 'getLembarKerjaMeterSumber');
            Route::get('manager/get-lembar-kerja-clamp-meter-sumber', 'getLembarKerjaClampMeterSumber');
            Route::get('manager/get-lembar-kerja-thermometer-infrared', 'getLembarKerjaThermometerInfrared');
            Route::get('manager/get-lembar-kerja-thermal-imager', 'getLembarKerjaThermalImager');
            Route::get('manager/get-lembar-kerja-thermohygrometer', 'getLembarKerjaThermohygrometer');
            Route::get('manager/get-lembar-kerja-dryblock', 'getLembarKerjaDryblock');
            Route::get('manager/get-lembar-kerja-caliper-micrometer', 'getLembarKerjaCaliperMicrometer');
            Route::get('manager/get-lembar-kerja-micrometer-head', 'getLembarKerjaMicrometerHead');
            Route::get('manager/get-lembar-kerja-outside-micrometer', 'getLembarKerjaOutsideMicrometer');
            Route::get('manager/get-lembar-kerja-oscilloscope', 'getLembarKerjaOscilloscope');
            Route::get('manager/get-lembar-kerja-dial-indicator', 'getLembarKerjaDialIndicator');
            Route::get('manager/get-lembar-kerja-bore-gauge', 'getLembarKerjaBoreGauge');
            Route::get('manager/get-lembar-kerja-AI', 'getLembarKerjaAI');
            Route::get('manager/get-lembar-kerja-continuity-source', 'getLembarKerjaContinuitySource');
            Route::get('manager/get-lembar-kerja-insulation-source', 'getLembarKerjaInsulationSource');
            Route::get('manager/get-lembar-kerja-thermocouple-rtd-simulator', 'getLembarKerjaThermocoupleRTDSimulator');
            Route::get('manager/get-lembar-kerja-tachometer', 'getLembarKerjaTachometer');
            Route::get('manager/get-lembar-kerja-insulation-meter-resistansi', 'getLembarKerjaInsulationMeterResistansi');
            Route::get('manager/get-lembar-kerja-insulation-tester', 'getLembarKerjaInsulationTester');
            Route::get('manager/get-lembar-kerja-torque-wrench', 'getLembarKerjaTorqueWrench');
            Route::get('manager/get-lembar-kerja-temperature-indicator', 'getLembarKerjaTemperatureIndicator');
            Route::get('manager/get-lembar-kerja-ultrasonic-thickness', 'getLembarKerjaUltrasonicThickness');
            Route::get('manager/get-lembar-kerja-thickness-gauge', 'getLembarKerjaThicknessGauge');
            Route::get('manager/get-lembar-kerja-feeler-gauge', 'getLembarKerjaFeelerGauge');
            Route::post('manager/save-setujui-serti-manager', 'setujuiSertifikatManager');
            Route::get('manager/cetak-sertifikat-lembar-kerja', 'cetakSertifikatLembarKerja');
            Route::get('manager/download-file-terunggah', 'downloadFileTerunggah');
            Route::get('manager/excel-length', 'getExcelLength');
            Route::get('manager/get-laporan-repair', 'getLaporanRepairManager');
            Route::get('manager/detail-alat-repair', 'detailAlatRepairManager');
            Route::post('manager/hapus-laporan-repair', 'hapusLaporanRepairManager');
            Route::post('manager/save-setujui-laporan-repair', 'setujuiLaporanRepairManager');
            Route::get('manager/cetak-laporan-repair', 'cetakLaporanRepairManager');
            Route::post('manager/hapus-status-repair', 'hapusLaporanStatus');
            Route::get('manager/get-laporan-verifikasi', 'getLaporanVerifikasi');
            Route::get('manager/cetak-laporan-verifikasi', 'cetakLaporanVerifikasi');
            Route::post('manager/save-penolakan-sertifikat', 'savePenolakanSerti');
            Route::post('manager/save-penolakan-laporan-repair', 'savePenolakanLapRepair');
            Route::post('manager/approval-pengajuan-amandemen', 'saveApprovalPengajuanAmandemen');
            Route::get('manager/get-pengajuan-amandemen', 'getPengajuanAmandemen');
            Route::get('manager/riwayat-amandemen', 'getRiwayatAmandemen');
        });

        Route::controller(PenyeliaCtrl::class)->group(function () {
            Route::post('penyelia/save-verif-item', 'saveVerifItem');
            Route::get('penyelia/get-alat-penyelia', 'getAlatPenyelia');
            Route::get('penyelia/layanan-verif-penyelia', 'LayananVerifPenyelia');
            Route::post('penyelia/save-verif', 'saveVerif');
            Route::post('penyelia/save-setujui-serti', 'setujuiSertifikat');
            Route::get('penyelia/detail-produk', 'detailProduk');
            Route::get('penyelia/get-lembar-kerja', 'getLembarKerjaPenyelia');
            Route::get('penyelia/get-lembar-kerja-sumber', 'getLembarKerjaSumber');
            Route::get('penyelia/get-lembar-kerja-clamp', 'getLembarKerjaClamp');
            Route::get('penyelia/get-lembar-kerja-vibration', 'getLembarKerjaVibrationMeter');
            Route::get('penyelia/get-lembar-kerja-vibration-calibrator', 'getLembarKerjaVibrationCalibrator');
            Route::get('penyelia/get-lembar-kerja-accellerometer', 'getLembarKerjaAccellerometer');
            Route::get('penyelia/get-lembar-kerja-tekanan', 'getLembarKerjaTekanan');
            Route::get('penyelia/get-lembar-kerja-clamp-meter', 'getLembarKerjaCLampMeter');
            Route::get('penyelia/get-lembar-kerja-meter-sumber', 'getLembarKerjaMeterSumber');
            Route::get('penyelia/get-lembar-kerja-clamp-meter-sumber', 'getLembarKerjaClampMeterSumber');
            Route::get('penyelia/get-lembar-kerja-thermometer-infrared', 'getLembarKerjaThermometerInfrared');
            Route::get('penyelia/get-lembar-kerja-thermal-imager', 'getLembarKerjaThermalImager');
            Route::get('penyelia/get-lembar-kerja-thermohygrometer', 'getLembarKerjaThermohygrometer');
            Route::get('penyelia/get-lembar-kerja-dryblock', 'getLembarKerjaDryblock');
            Route::get('penyelia/get-lembar-kerja-caliper-micrometer', 'getLembarKerjaCaliperMicrometer');
            Route::get('penyelia/get-lembar-kerja-micrometer-head', 'getLembarKerjaMicrometerHead');
            Route::get('penyelia/get-lembar-kerja-outside-micrometer', 'getLembarKerjaOutsideMicrometer');
            Route::get('penyelia/get-lembar-kerja-oscilloscope', 'getLembarKerjaOscilloscope');
            Route::get('penyelia/get-lembar-kerja-dial-indicator', 'getLembarKerjaDialIndicator');
            Route::get('penyelia/get-lembar-kerja-bore-gauge', 'getLembarKerjaBoreGauge');
            Route::get('penyelia/get-lembar-kerja-AI', 'getLembarKerjaAI');
            Route::get('penyelia/get-lembar-kerja-continuity-source', 'getLembarKerjaContinuitySource');
            Route::get('penyelia/get-lembar-kerja-insulation-source', 'getLembarKerjaInsulationSource');
            Route::get('penyelia/get-lembar-kerja-thermocouple-rtd-simulator', 'getLembarKerjaThermocoupleRTDSimulator');
            Route::get('penyelia/get-lembar-kerja-tachometer', 'getLembarKerjaTachometer');
            Route::get('penyelia/get-lembar-kerja-insulation-meter-resistansi', 'getLembarKerjaInsulationMeterResistansi');
            Route::get('penyelia/get-lembar-kerja-insulation-tester', 'getLembarKerjaInsulationTester');
            Route::get('penyelia/get-lembar-kerja-torque-wrench', 'getLembarKerjaTorqueWrench');
            Route::get('penyelia/get-lembar-kerja-temperature-indicator', 'getLembarKerjaTemperatureIndicator');
            Route::get('penyelia/get-lembar-kerja-ultrasonic-thickness', 'getLembarKerjaUltrasonicThickness');
            Route::get('penyelia/get-lembar-kerja-thickness-gauge', 'getLembarKerjaThicknessGauge');
            Route::get('penyelia/get-lembar-kerja-feeler-gauge', 'getLembarKerjaFeelerGauge');
            Route::get('penyelia/download-template-lembar-kerja', 'downloadTemplateLembarKerjaMeter');
            Route::get('penyelia/download-template-lembar-kerja-clamp', 'downloadTemplateLembarKerjaClamp');
            Route::get('penyelia/download-template-lembar-kerja-sumber', 'downloadTemplateLembarKerjaSumber');
            Route::get('penyelia/download-template-lembar-kerja-vibration', 'downloadTemplateLembarKerjaVibrationMeter');
            Route::get('penyelia/download-template-lembar-kerja-vibration-calibration', 'downloadTemplateLembarKerjaVibrationCalibration');
            Route::get('penyelia/download-template-lembar-kerja-accellerometer', 'downloadTemplateLembarKerjaAccellerometer');
            Route::get('penyelia/download-template-lembar-kerja-tekanan', 'downloadTemplateLembarKerjaTekanan');
            Route::get('penyelia/download-template-lembar-kerja-clamp-meter', 'downloadTemplateLembarKerjaClampMeter');
            Route::get('penyelia/download-template-lembar-kerja-meter-sumber', 'downloadTemplateLembarKerjaMeterSumber');
            Route::get('penyelia/download-template-lembar-kerja-clamp-meter-sumber', 'downloadTemplateLembarKerjaClampMeterSumber');
            Route::get('penyelia/download-template-lembar-kerja-thermometer-infrared', 'downloadTemplateLembarKerjaThermometerInfrared');
            Route::get('penyelia/download-template-lembar-kerja-thermal-imager', 'downloadTemplateLembarKerjaThermalImager');
            Route::get('penyelia/download-template-lembar-kerja-thermohygrometer', 'downloadTemplateLembarKerjaThermohygrometer');
            Route::get('penyelia/download-template-lembar-kerja-dryblock', 'downloadTemplateLembarKerjaDryblock');
            Route::get('penyelia/download-template-lembar-kerja-caliper-micrometer', 'downloadTemplateLembarKerjaCaliperMicrometer');
            Route::get('penyelia/download-template-lembar-kerja-micrometer-head', 'downloadTemplateLembarKerjaMicrometerHead');
            Route::get('penyelia/download-template-lembar-kerja-outside-micrometer', 'downloadTemplateLembarKerjaOutsideMicrometer');
            Route::get('penyelia/download-template-lembar-kerja-oscilloscope', 'downloadTemplateLembarKerjaOscilloscope');
            Route::get('penyelia/download-template-lembar-kerja-dial-indicator', 'downloadTemplateLembarKerjaDialIndicator');
            Route::get('penyelia/download-template-lembar-kerja-bore-gauge', 'downloadTemplateLembarKerjaBoreGauge');
            Route::get('penyelia/download-template-lembar-kerja-continuity-source', 'downloadTemplateLembarKerjaContinuitySource');
            Route::get('penyelia/download-template-lembar-kerja-insulation-source', 'downloadTemplateLembarKerjaInsulationSource');
            Route::get('penyelia/download-template-lembar-kerja-thermocouple-rtd-simulator', 'downloadTemplateLembarKerjaThermocoupleRTDSimulator');
            Route::get('penyelia/download-template-lembar-kerja-tachometer', 'downloadTemplateLembarKerjaTachometer');
            Route::get('penyelia/download-template-lembar-kerja-insulation-meter-resistansi', 'downloadTemplateLembarKerjaInsulationMeterResistansi');
            Route::get('penyelia/download-template-lembar-kerja-insulation-tester', 'downloadTemplateLembarKerjaInsulationTester');
            Route::get('penyelia/download-template-lembar-kerja-torque-wrench', 'downloadTemplateLembarKerjaTorqueWrench');
            Route::get('penyelia/download-template-lembar-kerja-temperature-indicator', 'downloadTemplateLembarKerjaTemperatureIndicator');
            Route::get('penyelia/download-template-lembar-kerja-ultrasonic-thickness', 'downloadTemplateLembarKerjaUltrasonicThickness');
            Route::get('penyelia/download-template-lembar-kerja-thickness-gauge', 'downloadTemplateLembarKerjaThicknessGauge');
            Route::get('penyelia/download-template-lembar-kerja-feeler-gauge', 'downloadTemplateLembarKerjaFeelerGauge');
            Route::post('penyelia/save-data-upload-lembar-kerja', 'simpanUploadLembaKerjaPenyelia');
            Route::post('penyelia/save-data-upload-lembar-kerja-sumber', 'simpanUploadLembaKerjaSumber');
            Route::post('penyelia/save-data-upload-lembar-kerja-clamp', 'simpanUploadLembaKerjaClamp');
            Route::post('penyelia/save-data-upload-lembar-kerja-vibration', 'simpanUploadLembaKerjaVibrationMeter');
            Route::post('penyelia/save-data-upload-lembar-kerja-vibration-calibrator', 'simpanUploadLembaKerjaVibrationCalibrator');
            Route::post('penyelia/save-data-upload-lembar-kerja-accellerometer', 'simpanUploadLembaKerjaAccellerometer');
            Route::post('penyelia/save-data-upload-lembar-kerja-tekanan', 'simpanUploadLembaKerjaTekanan');
            Route::post('penyelia/save-data-upload-lembar-kerja-clamp-meter', 'simpanUploadLembaKerjaClampMeter');
            Route::post('penyelia/save-data-upload-lembar-kerja-meter-sumber', 'simpanUploadLembaKerjaMeterSumber');
            Route::post('penyelia/save-data-upload-lembar-kerja-clamp-meter-sumber', 'simpanUploadLembaKerjaClampMeterSumber');
            Route::post('penyelia/save-data-upload-lembar-kerja-thermometer-infrared', 'simpanUploadLembaKerjaThermometerInfrared');
            Route::post('penyelia/save-data-upload-lembar-kerja-thermal-imager', 'simpanUploadLembaKerjaThermalImager');
            Route::post('penyelia/save-data-upload-lembar-kerja-thermohygrometer', 'simpanUploadLembaKerjaThermohygrometer');
            Route::post('penyelia/save-data-upload-lembar-kerja-dryblock', 'simpanUploadLembaKerjaDryblock');
            Route::post('penyelia/save-data-upload-lembar-kerja-caliper-micrometer', 'simpanUploadLembaKerjaCaliperMicrometer');
            Route::post('penyelia/save-data-upload-lembar-kerja-micrometer-head', 'simpanUploadLembaKerjaMicrometerHead');
            Route::post('penyelia/save-data-upload-lembar-kerja-outside-micrometer', 'simpanUploadLembaKerjaOutsideMicrometer');
            Route::post('penyelia/save-data-upload-lembar-kerja-oscilloscope', 'simpanUploadLembaKerjaOscilloscope');
            Route::post('penyelia/save-data-upload-lembar-kerja-dial-indicator', 'simpanUploadLembaKerjaDialIndicator');
            Route::post('penyelia/save-data-upload-lembar-kerja-bore-gauge', 'simpanUploadLembaKerjaBoreGauge');
            Route::post('penyelia/save-data-upload-lembar-kerja-ai', 'simpanUploadLembaKerjaAI');
            Route::post('penyelia/save-data-upload-lembar-kerja-continuity-source', 'simpanUploadLembaKerjaContinuitySource');
            Route::post('penyelia/save-data-upload-lembar-kerja-insulation-source', 'simpanUploadLembaKerjaInsulationSource');
            Route::post('penyelia/save-data-upload-lembar-kerja-thermocouple-rtd-simulator', 'simpanUploadLembarKerjaThermocoupleRTDSimulator');
            Route::post('penyelia/save-data-upload-lembar-kerja-tachometer', 'simpanUploadLembaKerjaTachometer');
            Route::post('penyelia/save-data-upload-lembar-kerja-insulation-meter-resistansi', 'simpanUploadLembaKerjaInsulationMeterResistansi');
            Route::post('penyelia/save-data-upload-lembar-kerja-insulation-tester', 'simpanUploadLembaKerjaInsulationTester');
            Route::post('penyelia/save-data-upload-lembar-kerja-torque-wrench', 'simpanUploadLembaKerjaTorqueWrench');
            Route::post('penyelia/save-data-upload-lembar-kerja-temperature-indicator', 'simpanUploadLembaKerjaTemperatureIndicator');
            Route::post('penyelia/save-data-upload-lembar-kerja-ultrasonic-thickness', 'simpanUploadLembaKerjaUltrasonicThickness');
            Route::post('penyelia/save-data-upload-lembar-kerja-thickness-gauge', 'simpanUploadLembaKerjaThicknessGauge');
            Route::post('penyelia/save-data-upload-lembar-kerja-feeler-gauge', 'simpanUploadLembaKerjaFeelerGauge');
            Route::get('penyelia/detail-produk-lembar-kerja', 'detailProdukLembarKerjaPenyelia');
            Route::get('penyelia/cetak-spk', 'cetakSPK');
            Route::get('penyelia/cetak-sertifikat-lembar-kerja', 'cetakSertifikatLembarKerja');
            Route::get('penyelia/download-file-terunggah', 'downloadFileTerunggah');
            Route::get('penyelia/excel-length', 'getExcelLength');
            Route::get('penyelia/get-pegawai-pelaksana', 'getPegawaiPelaksana');
            Route::post('penyelia/save-data-laporan-repair', 'simpanLaporanRepairPenyelia');
            Route::get('penyelia/get-laporan-repair', 'getLaporanRepairPenyelia');
            Route::get('penyelia/detail-alat-repair', 'detailAlatRepairPenyelia');
            Route::post('penyelia/hapus-laporan-repair', 'hapusLaporanRepairPenyelia');
            Route::post('penyelia/hapus-hasil-repair', 'hapusHasilRepair');
            Route::post('penyelia/update-status-repair', 'updateStatusRepair');
            Route::post('penyelia/update-laporan-repair', 'updateLaporanRepair');
            Route::post('penyelia/update-hasil-repair', 'updateHasilRepair');
            Route::post('penyelia/update-kesimpulan-repair', 'updateKesimpulanRepair');
            Route::post('penyelia/save-setujui-laporan-repair', 'setujuiLaporanRepair');
            Route::post('penyelia/hapus-status-repair', 'hapusLaporanStatus');
            Route::get('penyelia/cetak-laporan-repair', 'cetakLaporanRepairPenyelia');
            Route::get('penyelia/alat-standar', 'getAlatStandar');
            Route::post('penyelia/edit-lembar-kerja', 'editLembaKerja');
            Route::post('penyelia/save-status-kan', 'saveStatusKan');
            Route::post('penyelia/save-excel-laporan-verif', 'saveExcelLaporanVerif');
            Route::get('penyelia/get-laporan-verifikasi', 'getLaporanVerifikasi');
            Route::get('penyelia/cetak-laporan-verifikasi', 'cetakLaporanVerifikasi');
            Route::get('penyelia/get-sensor', 'getSensor');
            Route::post('penyelia/save-penolakan-sertifikat', 'savePenolakanSerti');
            Route::post('penyelia/save-penolakan-laporan-repair', 'savePenolakanLapRepair');
            Route::post('penyelia/save-excel-lembar-kerja', 'saveExcelLembarKerja');
            Route::post('penyelia/delete-file-lembar-kerja', 'deleteFileLembarKerja');
            Route::post('penyelia/update-metadata-lembar-kerja', 'updateMetadataLembarKerja');
            Route::post('penyelia/approval-pengajuan-amandemen', 'saveApprovalPengajuanAmandemen');
            Route::get('penyelia/get-pengajuan-amandemen', 'getPengajuanAmandemen');
        });

        Route::controller(PelaksanaCtrl::class)->group(function () {
            Route::get('pelaksana/get-alat-pelaksana', 'getAlatPelaksana');
            Route::get('pelaksana/layanan-verif-pelaksana', 'LayananVerifPelaksana');
            Route::post('pelaksana/save-verif', 'saveVerif');
            Route::get('pelaksana/detail-produk', 'detailProduk');
            Route::get('pelaksana/get-lembar-kerja', 'getLembarKerja');
            Route::get('pelaksana/get-lembar-kerja-sumber', 'getLembarKerjaSumber');
            Route::get('pelaksana/get-lembar-kerja-clamp', 'getLembarKerjaClamp');
            Route::get('pelaksana/get-lembar-kerja-vibration', 'getLembarKerjaVibrationMeter');
            Route::get('pelaksana/get-lembar-kerja-vibration-calibrator', 'getLembarKerjaVibrationCalibrator');
            Route::get('pelaksana/get-lembar-kerja-accellerometer', 'getLembarKerjaAccellerometer');
            Route::get('pelaksana/get-lembar-kerja-tekanan', 'getLembarKerjaTekanan');
            Route::get('pelaksana/get-lembar-kerja-clamp-meter', 'getLembarKerjaCLampMeter');
            Route::get('pelaksana/get-lembar-kerja-meter-sumber', 'getLembarKerjaMeterSumber');
            Route::get('pelaksana/get-lembar-kerja-clamp-meter-sumber', 'getLembarKerjaClampMeterSumber');
            Route::get('pelaksana/get-lembar-kerja-thermometer-infrared', 'getLembarKerjaThermometerInfrared');
            Route::get('pelaksana/get-lembar-kerja-thermal-imager', 'getLembarKerjaThermalImager');
            Route::get('pelaksana/get-lembar-kerja-thermohygrometer', 'getLembarKerjaThermohygrometer');
            Route::get('pelaksana/get-lembar-kerja-dryblock', 'getLembarKerjaDryblock');
            Route::get('pelaksana/get-lembar-kerja-caliper-micrometer', 'getLembarKerjaCaliperMicrometer');
            Route::get('pelaksana/get-lembar-kerja-micrometer-head', 'getLembarKerjaMicrometerHead');
            Route::get('pelaksana/get-lembar-kerja-outside-micrometer', 'getLembarKerjaOutsideMicrometer');
            Route::get('pelaksana/get-lembar-kerja-oscilloscope', 'getLembarKerjaOscilloscope');
            Route::get('pelaksana/get-lembar-kerja-dial-indicator', 'getLembarKerjaDialIndicator');
            Route::get('pelaksana/get-lembar-kerja-bore-gauge', 'getLembarKerjaBoreGauge');
            Route::get('pelaksana/get-lembar-kerja-AI', 'getLembarKerjaAI');
            Route::get('pelaksana/get-lembar-kerja-continuity-source', 'getLembarKerjaContinuitySource');
            Route::get('pelaksana/get-lembar-kerja-insulation-source', 'getLembarKerjaInsulationSource');
            Route::get('pelaksana/get-lembar-kerja-thermocouple-rtd-simulator', 'getLembarKerjaThermocoupleRTDSimulator');
            Route::get('pelaksana/get-lembar-kerja-tachometer', 'getLembarKerjaTachometer');
            Route::get('pelaksana/get-lembar-kerja-insulation-meter-resistansi', 'getLembarKerjaInsulationMeterResistansi');
            Route::get('pelaksana/get-lembar-kerja-insulation-tester', 'getLembarKerjaInsulationTester');
            Route::get('pelaksana/get-lembar-kerja-torque-wrench', 'getLembarKerjaTorqueWrench');
            Route::get('pelaksana/get-lembar-kerja-temperature-indicator', 'getLembarKerjaTemperatureIndicator');
            Route::get('pelaksana/get-lembar-kerja-ultrasonic-thickness', 'getLembarKerjaUltrasonicThickness');
            Route::get('pelaksana/get-lembar-kerja-thickness-gauge', 'getLembarKerjaThicknessGauge');
            Route::get('pelaksana/get-lembar-kerja-feeler-gauge', 'getLembarKerjaFeelerGauge');
            Route::get('pelaksana/download-template-lembar-kerja', 'downloadTemplateLembarKerja');
            Route::get('pelaksana/download-template-lembar-kerja-meter', 'downloadTemplateLembarKerjaMeter');
            Route::get('pelaksana/download-template-lembar-kerja-clamp', 'downloadTemplateLembarKerjaClamp');
            Route::get('pelaksana/download-template-lembar-kerja-sumber', 'downloadTemplateLembarKerjaSumber');
            Route::get('pelaksana/download-template-lembar-kerja-vibration', 'downloadTemplateLembarKerjaVibrationMeter');
            Route::get('pelaksana/download-template-lembar-kerja-vibration-calibration', 'downloadTemplateLembarKerjaVibrationCalibration');
            Route::get('pelaksana/download-template-lembar-kerja-accellerometer', 'downloadTemplateLembarKerjaAccellerometer');
            Route::get('pelaksana/download-template-lembar-kerja-tekanan', 'downloadTemplateLembarKerjaTekanan');
            Route::get('pelaksana/download-template-lembar-kerja-clamp-meter', 'downloadTemplateLembarKerjaClampMeter');
            Route::get('pelaksana/download-template-lembar-kerja-meter-sumber', 'downloadTemplateLembarKerjaMeterSumber');
            Route::get('pelaksana/download-template-lembar-kerja-clamp-meter-sumber', 'downloadTemplateLembarKerjaClampMeterSumber');
            Route::get('pelaksana/download-template-lembar-kerja-thermometer-infrared', 'downloadTemplateLembarKerjaThermometerInfrared');
            Route::get('pelaksana/download-template-lembar-kerja-thermal-imager', 'downloadTemplateLembarKerjaThermalImager');
            Route::get('pelaksana/download-template-lembar-kerja-thermohygrometer', 'downloadTemplateLembarKerjaThermohygrometer');
            Route::get('pelaksana/download-template-lembar-kerja-dryblock', 'downloadTemplateLembarKerjaDryblock');
            Route::get('pelaksana/download-template-lembar-kerja-caliper-micrometer', 'downloadTemplateLembarKerjaCaliperMicrometer');
            Route::get('pelaksana/download-template-lembar-kerja-micrometer-head', 'downloadTemplateLembarKerjaMicrometerHead');
            Route::get('pelaksana/download-template-lembar-kerja-outside-micrometer', 'downloadTemplateLembarKerjaOutsideMicrometer');
            Route::get('pelaksana/download-template-lembar-kerja-oscilloscope', 'downloadTemplateLembarKerjaOscilloscope');
            Route::get('pelaksana/download-template-lembar-kerja-dial-indicator', 'downloadTemplateLembarKerjaDialIndicator');
            Route::get('pelaksana/download-template-lembar-kerja-bore-gauge', 'downloadTemplateLembarKerjaBoreGauge');
            Route::get('pelaksana/download-template-lembar-kerja-continuity-source', 'downloadTemplateLembarKerjaContinuitySource');
            Route::get('pelaksana/download-template-lembar-kerja-insulation-source', 'downloadTemplateLembarKerjaInsulationSource');
            Route::get('pelaksana/download-template-lembar-kerja-thermocouple-rtd-simulator', 'downloadTemplateLembarKerjaThermocoupleRTDSimulator');
            Route::get('pelaksana/download-template-lembar-kerja-insulation-meter-resistansi', 'downloadTemplateLembarKerjaInsulationMeterResistansi');
            Route::get('pelaksana/download-template-lembar-kerja-insulation-tester', 'downloadTemplateLembarKerjaInsulationTester');
            Route::get('pelaksana/download-template-lembar-kerja-tachometer', 'downloadTemplateLembarKerjaTachometer');
            Route::get('pelaksana/download-template-lembar-kerja-torque-wrench', 'downloadTemplateLembarKerjaTorqueWrench');
            Route::get('pelaksana/download-template-lembar-kerja-temperature-indicator', 'downloadTemplateLembarKerjaTemperatureIndicator');
            Route::get('pelaksana/download-template-lembar-kerja-ultrasonic-thickness', 'downloadTemplateLembarKerjaUltrasonicThickness');
            Route::get('pelaksana/download-template-lembar-kerja-thickness-gauge', 'downloadTemplateLembarKerjaThicknessGauge');
            Route::get('pelaksana/download-template-lembar-kerja-feeler-gauge', 'downloadTemplateLembarKerjaFeelerGauge');
            Route::post('pelaksana/save-data-upload-lembar-kerja', 'simpanUploadLembaKerja');
            Route::post('pelaksana/save-data-upload-lembar-kerja-sumber', 'simpanUploadLembaKerjaSumber');
            Route::post('pelaksana/save-data-upload-lembar-kerja-clamp', 'simpanUploadLembaKerjaClamp');
            Route::post('pelaksana/save-data-upload-lembar-kerja-vibration', 'simpanUploadLembaKerjaVibrationMeter');
            Route::post('pelaksana/save-data-upload-lembar-kerja-vibration-calibrator', 'simpanUploadLembaKerjaVibrationCalibrator');
            Route::post('pelaksana/save-data-upload-lembar-kerja-accellerometer', 'simpanUploadLembaKerjaAccellerometer');
            Route::post('pelaksana/save-data-upload-lembar-kerja-tekanan', 'simpanUploadLembaKerjaTekanan');
            Route::post('pelaksana/save-data-upload-lembar-kerja-clamp-meter', 'simpanUploadLembaKerjaClampMeter');
            Route::post('pelaksana/save-data-upload-lembar-kerja-meter-sumber', 'simpanUploadLembaKerjaMeterSumber');
            Route::post('pelaksana/save-data-upload-lembar-kerja-clamp-meter-sumber', 'simpanUploadLembaKerjaClampMeterSumber');
            Route::post('pelaksana/save-data-upload-lembar-kerja-thermometer-infrared', 'simpanUploadLembaKerjaThermometerInfrared');
            Route::post('pelaksana/save-data-upload-lembar-kerja-thermal-imager', 'simpanUploadLembaKerjaThermalImager');
            Route::post('pelaksana/save-data-upload-lembar-kerja-thermohygrometer', 'simpanUploadLembaKerjaThermohygrometer');
            Route::post('pelaksana/save-data-upload-lembar-kerja-dryblock', 'simpanUploadLembaKerjaDryblock');
            Route::post('pelaksana/save-data-upload-lembar-kerja-caliper-micrometer', 'simpanUploadLembaKerjaCaliperMicrometer');
            Route::post('pelaksana/save-data-upload-lembar-kerja-micrometer-head', 'simpanUploadLembaKerjaMicrometerHead');
            Route::post('pelaksana/save-data-upload-lembar-kerja-outside-micrometer', 'simpanUploadLembaKerjaOutsideMicrometer');
            Route::post('pelaksana/save-data-upload-lembar-kerja-oscilloscope', 'simpanUploadLembaKerjaOscilloscope');
            Route::post('pelaksana/save-data-upload-lembar-kerja-dial-indicator', 'simpanUploadLembaKerjaDialIndicator');
            Route::post('pelaksana/save-data-upload-lembar-kerja-bore-gauge', 'simpanUploadLembaKerjaBoreGauge');
            Route::post('pelaksana/save-data-upload-lembar-kerja-ai', 'simpanUploadLembaKerjaAI');
            Route::post('pelaksana/save-data-upload-lembar-kerja-continuity-source', 'simpanUploadLembaKerjaContinuitySource');
            Route::post('pelaksana/save-data-upload-lembar-kerja-insulation-source', 'simpanUploadLembaKerjaInsulationSource');
            Route::post('pelaksana/save-data-upload-lembar-kerja-thermocouple-rtd-simulator', 'simpanUploadLembarKerjaThermocoupleRTDSimulator');
            Route::post('pelaksana/save-data-upload-lembar-kerja-tachometer', 'simpanUploadLembaKerjaTachometer');
            Route::post('pelaksana/save-data-upload-lembar-kerja-insulation-meter-resistansi', 'simpanUploadLembaKerjaInsulationMeterResistansi');
            Route::post('pelaksana/save-data-upload-lembar-kerja-insulation-tester', 'simpanUploadLembaKerjaInsulationTester');
            Route::post('pelaksana/save-data-upload-lembar-kerja-torque-wrench', 'simpanUploadLembaKerjaTorqueWrench');
            Route::post('pelaksana/save-data-upload-lembar-kerja-temperature-indicator', 'simpanUploadLembaKerjaTemperatureIndicator');
            Route::post('pelaksana/save-data-upload-lembar-kerja-ultrasonic-thickness', 'simpanUploadLembaKerjaUltrasonicThickness');
            Route::post('pelaksana/save-data-upload-lembar-kerja-thickness-gauge', 'simpanUploadLembaKerjaThicknessGauge');
            Route::post('pelaksana/save-data-upload-lembar-kerja-feeler-gauge', 'simpanUploadLembaKerjaFeelerGauge');
            Route::get('pelaksana/detail-produk-lembar-kerja', 'detailProdukLembarKeerja');
            Route::get('pelaksana/cetak-spk', 'cetakSPK');
            Route::get('pelaksana/cetak-sertifikat-lembar-kerja', 'cetakSertifikatLembarKerja');
            Route::get('pelaksana/get-merk-standar', 'getMerkStandar');
            Route::get('pelaksana/get-tipe-standar', 'getTipeStandar');
            Route::get('pelaksana/get-sn-standar', 'getSnStandar');
            Route::post('pelaksana/save-excel-lembar-kerja', 'saveExcelLembarKerja');
            Route::post('pelaksana/delete-file-lembar-kerja', 'deleteFileLembarKerja');
            Route::post('pelaksana/update-metadata-lembar-kerja', 'updateMetadataLembarKerja');
            Route::get('pelaksana/download-file-terunggah', 'downloadFileTerunggah');
            Route::get('pelaksana/excel-length', 'getExcelLength');
            Route::post('pelaksana/save-data-laporan-repair', 'simpanLaporanRepair');
            Route::get('pelaksana/get-laporan-repair', 'getLaporanRepair');
            Route::get('pelaksana/detail-alat-repair', 'detailAlatRepair');
            Route::post('pelaksana/hapus-laporan-repair', 'hapusLaporanRepair');
            Route::post('pelaksana/hapus-status-repair', 'hapusLaporanStatus');
            Route::post('pelaksana/hapus-hasil-repair', 'hapusHasilRepair');
            Route::post('pelaksana/update-status-repair', 'updateStatusRepair');
            Route::post('pelaksana/update-laporan-repair', 'updateLaporanRepair');
            Route::post('pelaksana/update-hasil-repair', 'updateHasilRepair');
            Route::post('pelaksana/update-kesimpulan-repair', 'updateKesimpulanRepair');
            Route::get('pelaksana/cetak-laporan-repair', 'cetakLaporanRepair');
            Route::get('pelaksana/get-sublingkup', 'getSubLingkupDropdown');
            Route::post('pelaksana/simpan-progres-harian-repair', 'saveProgresHarian');
            Route::get('pelaksana/get-progres-harian', 'getProgresHarian');
            Route::get('pelaksana/alat-standar', 'getAlatStandar');
            Route::post('pelaksana/edit-lembar-kerja', 'editLembaKerja');
            Route::post('pelaksana/save-status-kan', 'saveStatusKan');
            Route::post('pelaksana/excel-to-pdf', 'laporanVerifikasi');
            Route::post('pelaksana/save-excel-laporan-verif', 'saveExcelLaporanVerif');
            Route::get('pelaksana/get-laporan-verifikasi', 'getLaporanVerifikasi');
            Route::get('pelaksana/cetak-laporan-verifikasi', 'cetakLaporanVerifikasi');
            Route::get('pelaksana/get-sensor', 'getSensor');
            Route::post('pelaksana/save-pengajuan-amandemen', 'savePengajuanAmandemen');
            Route::get('pelaksana/get-pengajuan-amandemen', 'getPengajuanAmandemen');
        });

        Route::controller(RbkCtrl::class)->group(function () {
            Route::get('rbk/get-units-monitoring', 'getUnitsMonitoring');
            Route::get('rbk/get-unit-candidates', 'getUnitCandidates');
            Route::post('rbk/add-unit', 'addUnit');
            Route::get('rbk/get-rbk-by-unit', 'getRbkByUnit');
            Route::post('rbk/save-rbk', 'saveRbk');
            Route::post('rbk/save-realisasi', 'addRealisasi');
            Route::post('rbk/update-realisasi', 'updateRealisasi');
            Route::get('rbk/get-realisasi-by-unit', 'getRealisasiByUnit');
            Route::get('rbk/get-realisasi-by-item', 'getRealisasiByItem');
            Route::post('rbk/disable-item', 'disableItem');
            Route::post('rbk/disable-rbk', 'disableRbk');
        });

        Route::controller(LaporanRegistrasiAlatCtrl::class)->group(function () {
            Route::get('laporan/get-laporan-registrasi-alat', 'getLaporanRegistrasiAlat');
            Route::get('laporan/get-laporan-monitoring', 'getLaporanMonitoring');
        });

        Route::controller(LaporanPelaksanaCtrl::class)->group(function () {
            Route::get('laporan/get-laporan-alat-pelaksana', 'getLaporanAlatPelaksana');
            Route::get('laporan/get-isi-synergy-pagi', 'getIsiSynergy');
            Route::get('laporan/get-notes-unit', 'getNotesUnit');
            Route::get('laporan/get-global-notes', 'getGlobalNotes');
            Route::get('laporan/get-unit-items', 'getUnitItems');
            Route::post('laporan/save-notes-per-unit', 'saveNotesPerUnit');
            Route::post('laporan/save-global-note', 'saveGlobalNote');
            Route::post('laporan/delete-global-note', 'hapusGlobalNote');
            Route::post('laporan/delete-unit-note', 'hapusUnitNote');
            Route::post('laporan/delete-detail-note', 'hapusDetailNote');
            Route::get('laporan/get-notes-peralat', 'getNotesDetail');
            Route::post('laporan/save-notes-peralat', 'saveNotesDetail');
        });

        Route::controller(LaporanTindakanPasienCtrl::class)->group(function () {
            Route::get('laporan/get-laporan-pengunjung', 'getPelayananTindakan');
            Route::get('laporan/laporan-time-respon', 'getTimeRespon');
            Route::get('laporan/pilihan-search', 'pilihanSearch');
        });

        Route::controller(MasterRuanganCtrl::class)->group(function () {
            Route::get('sysadmin/master-ruangan', 'masterRuangan');
            Route::post('sysadmin/save-master-ruangan', 'saveRuangan');
            Route::post('sysadmin/delete-master-ruangan', 'deleteRuangan');
        });

        Route::controller(MasterMapKelompokCtrl::class)->group(function () {
            Route::get('sysadmin/master-map-kelompok', 'masterMapKelompok');
            Route::get('sysadmin/master-map-kelompok-dropdown', 'masterMapKelompokdropdown');

            Route::post('sysadmin/save-map-kelompok', 'saveMapKelompok');
            Route::post('sysadmin/delete-map-kelompok', 'deleteMapKelompok');
            Route::post('sysadmin/detail-map-kelompok', 'detailMapKelompok');
        });

        Route::controller(HariLiburCtrl::class)->group(function () {
            Route::get('sysadmin/hari-libur/sync', 'syncHariLibur');
            Route::get('sysadmin/hari-libur', 'getHariLibur');
            Route::get('sysadmin/hari-libur/test-api', 'testApiHariLibur');
        });

        Route::controller(GeneralCtrl::class)->group(function () {
            Route::get('general/show-file', 'showFileGeneral');
            Route::get('general/dropdown/{table}', 'dropdownGeneral');
            Route::get('general/printer', 'masterPrinter');
            Route::get('general/list-log-user', 'getLogUser');
            Route::get('general/device-name', function () {
                $response = array(
                    'metaData' => array(
                        "code" => 200,
                        "message" => 'Sukses',
                    ),
                    'response' => gethostname(),
                );
                return $response;
            });
            Route::get('/jumlah', 'getTerbilang');
            Route::post('general/save-printer', 'savePrinter');
            Route::post('general/delete-printer', 'deletePrinter');
            Route::post('general/store-notif', 'storeNotif');
        });
        Route::controller(MasterKelompokProdukCtrl::class)->group(function () {
            Route::get('sysadmin/master-kelompok-produk', 'masterKelompokProduk');
            Route::get('sysadmin/master-kelompok-produk-dropdown', 'masterKelompokProdukdropdown');

            Route::post('sysadmin/save-master-kelompok-produk', 'saveKelompokProduk');
            Route::post('sysadmin/delete-kelompok-produk', 'deleteKelompokProduk');
        });
        Route::controller(MasterJenisProdukCtrl::class)->group(function () {
            Route::get('sysadmin/master-jenis-produk', 'masterJenisProduk');
            Route::get('sysadmin/master-jenis-produk-dropdown', 'masterJenisProdukdropdown');

            Route::post('sysadmin/save-master-jenis-produk', 'saveJenisProduk');
            Route::post('sysadmin/delete-jenis-produk', 'deleteJenisProduk');
            Route::post('sysadmin/detail-jenis-produk', 'detailJenisProduk');
        });
        Route::controller(MasterProdukCtrl::class)->group(function () {
            Route::get('sysadmin/master-produk', 'masterProduk');
            Route::post('sysadmin/save-master-produk', 'saveProduk');
            Route::post('sysadmin/delete-master-produk', 'deleteProduk');
            Route::post('sysadmin/detail-produk', 'detailProduk');
            Route::post('sysadmin/update-produk', 'updateProduk');
        });
        Route::controller(MasterAgamaCtrl::class)->group(function () {
            Route::get('sysadmin/master-agama', 'masterAgama');

            Route::post('sysadmin/save-agama', 'saveAgama');
            Route::post('sysadmin/delete-agama', 'deleteAgama');
            Route::post('sysadmin/detail-agama', 'detailAgama');
        });
        Route::controller(MasterJenisKelaminCtrl::class)->group(function () {
            Route::get('sysadmin/master-jenis-kelamin', 'masterJenisKelamin');
            Route::post('sysadmin/save-jenis-kelamin', 'saveJenisKelamin');
            Route::post('sysadmin/delete-jenis-kelamin', 'deleteJenisKelamin');
            Route::post('sysadmin/detail-jenis-kelamin', 'detailJenisKelamin');
        });
        Route::controller(MasterInstruksiKerjaCtrl::class)->group(function () {
            Route::get('sysadmin/worksheet-instructions', 'worksheetInstructions');
            Route::get('sysadmin/master-instruksi-kerja', 'masterInstruksiKerja');
            Route::get('sysadmin/history-instruksi-kerja', 'historyInstruksiKerja');
            Route::get('sysadmin/open-instruksi-kerja-file', 'openInstruksiKerjaFile');
            Route::get('sysadmin/download-instruksi-kerja-file', 'downloadInstruksiKerjaFile');
            Route::post('sysadmin/save-instruksi-kerja', 'saveInstruksiKerja');
            Route::post('sysadmin/delete-instruksi-kerja', 'deleteInstruksiKerja');
        });
        Route::controller(MasterVendorCtrl::class)->group(function () {
            Route::get('sysadmin/master-vendor', 'masterVendor');
            Route::post('sysadmin/save-vendor', 'saveVendor');
            Route::post('sysadmin/delete-vendor', 'deleteVendor');
        });
        Route::controller(MasterUserUnitCtrl::class)->group(function () {
            Route::get('sysadmin/master-user-unit', 'masterUserUnit');
            Route::post('sysadmin/verify-user-unit', 'verifyUserUnit');
            Route::post('sysadmin/update-unit-user', 'updateUnitUser');
        });
        Route::controller(MasterAlatStandarCtrl::class)->group(function () {
            Route::get('sysadmin/master-alat-standar', 'masterAlatStandar');
            Route::post('sysadmin/save-alat-standar', 'saveAlatStandar');
            Route::post('sysadmin/delete-alat-standar', 'deleteAlatStandar');
            Route::post('sysadmin/upload-sertifikat-standar', 'uploadSertifikatStandar');
            Route::get('sysadmin/sertifikat-standar', 'viewSertifikatStandar');
            Route::get('sysadmin/history-alat', 'historyAlat');
            Route::get('sysadmin/get-alat', 'fetchAlat');
        });
        Route::controller(MasterSnAlatCtrl::class)->group(function () {
            Route::get('sysadmin/master-sn-alat', 'masterSnAlat');
            Route::post('sysadmin/save-sn-alat', 'saveSnAlat');
            Route::post('sysadmin/delete-sn-alat', 'deleteSnAlat');
        });
        Route::controller(MasterPendidikanCtrl::class)->group(function () {
            Route::get('sysadmin/master-pendidikan', 'masterPendidikan');
            Route::get('sysadmin/master-pendidikan-dropdown', 'masterPendidikandropdown');

            Route::post('sysadmin/save-pendidikan', 'savePendidikan');
            Route::post('sysadmin/delete-pendidikan', 'deletePendidikan');
        });
        Route::controller(MasterPekerjaanCtrl::class)->group(function () {
            Route::get('sysadmin/master-pekerjaan', 'masterPekerjaan');
            Route::get('sysadmin/master-pekerjaan-dropdown', 'masterPekerjaandropdown');

            Route::post('sysadmin/save-pekerjaan', 'savePekerjaan');
            Route::post('sysadmin/delete-pekerjaan', 'deletePekerjaan');
        });
        Route::controller(MasterNegaraCtrl::class)->group(function () {
            Route::get('sysadmin/master-negara', 'masterNegara');

            Route::post('sysadmin/save-negara', 'saveNegara');
            Route::post('sysadmin/delete-negara', 'deleteNegara');
        });
        Route::controller(MasterSukuCtrl::class)->group(function () {
            Route::get('sysadmin/master-suku', 'masterSuku');

            Route::post('sysadmin/save-suku', 'saveSuku');
            Route::post('sysadmin/delete-suku', 'deleteSuku');
        });
        Route::controller(MasterProvinsiCtrl::class)->group(function () {
            Route::get('sysadmin/master-provinsi', 'masterProvinsi');

            Route::post('sysadmin/save-provinsi', 'saveProvinsi');
            Route::post('sysadmin/delete-provinsi', 'deleteProvinsi');
        });
        Route::controller(MasterKecamatanCtrl::class)->group(function () {
            Route::get('sysadmin/master-kecamatan', 'masterKecamatan');
            Route::get('sysadmin/master-kecamatan-dropdown', 'masterKecamatandropdown');

            Route::post('sysadmin/save-kecamatan', 'saveKecamatan');
            Route::post('sysadmin/delete-kecamatan', 'deleteKecamatan');
        });
        Route::controller(MasterJabatanCtrl::class)->group(function () {
            Route::get('sysadmin/master-jabatan', 'masterJabatan');
            Route::get('sysadmin/master-jabatan-dropdown', 'masterJabatandropdown');

            Route::post('sysadmin/save-master-jabatan', 'saveJabatan');
            Route::post('sysadmin/delete-master-jabatan', 'deleteJabatan');
        });
        Route::controller(MasterKelompokUserCtrl::class)->group(function () {
            Route::get('sysadmin/master-kelompok-user', 'masterKelompokUser');

            Route::post('sysadmin/save-master-kelompok-user', 'saveKelompokUser');
            Route::post('sysadmin/delete-master-kelompok-user', 'deleteKelompokUser');
        });
        Route::controller(MasterPegawaiCtrl::class)->group(function () {
            Route::get('sysadmin/master-pegawai', 'masterPegawai');
            Route::get('sysadmin/master-pegawai-dropdown', 'masterPegawaidropdown');
            Route::get('sysadmin/pegawai', 'pegawaiByID');
            Route::get('sysadmin/pegawai-by-id', 'pegawaiByID');
            Route::get('sysadmin/jadwal-kerja', 'jadwalKerja');
            Route::get('sysadmin/master-pegawai-satset', 'masterPegawaiSatset');

            Route::post('sysadmin/save-pegawai', 'savePegawai');
            Route::post('sysadmin/save-pegawai-foto', 'savePegawaiFoto');
            Route::post('sysadmin/delete-pegawai', 'deletePegawai');
            Route::post('sysadmin/update-pegawai', 'updatePegawai');
            Route::post('sysadmin/save-pegawai-face', 'savePegawaiFace');
            Route::get('/sysadmin/foto-pegawai/{filename}', 'getFotoPegawai');
            Route::get('sysadmin/profile-user-detail', 'profileUserDetail');
            Route::post('sysadmin/save-user-foto', 'saveUserFoto');
            Route::post('sysadmin/save-profile-user', 'saveProfileUser');
        });
        Route::controller(MasterJenisPegawaiCtrl::class)->group(function () {
            Route::get('sysadmin/master-jenis-pegawai', 'masterJenisPegawai');
            Route::get('sysadmin/master-jenis-pegawai-dropdown', 'masterJenisPegawaidropdown');

            Route::post('sysadmin/save-jenis-pegawai', 'saveJenisPegawai');
            Route::post('sysadmin/delete-jenis-pegawai', 'deleteJenisPegawai');
        });
        Route::controller(MasterTambahLoginUserCtrl::class)->group(function () {
            Route::get('sysadmin/master-tambah-login-user', 'masterTambahLoginUser');
            Route::get('sysadmin/master-tambah-login-user-dropdown', 'masterTambahLoginUserDropdown');

            Route::post('sysadmin/save-login-user', 'saveLoginUser');
            Route::post('sysadmin/delete-login-user', 'deleteLoginUser');
        });
        Route::controller(MasterModulAplikasiCtrl::class)->group(function () {
            Route::get('sysadmin/master-modul-aplikasi', 'masterModulAplikasi');
            Route::get('sysadmin/master-objek-modul-aplikasi', 'masterObjekModulAplikasi');
            Route::get('sysadmin/master-modul-aplikasi-by-head', 'masterModulAplikasiHead');
            Route::get('sysadmin/master-menu-modul-aplikasi', 'masterMenuObjek');
            Route::get('sysadmin/master-modul-aplikasi-nourut', 'lastNourutObjekModul');


            Route::post('sysadmin/save-modul-aplikasi', 'saveModulAplikasi');
            Route::post('sysadmin/delete-modul-aplikasi', 'deleteModulAplikasi');
            Route::post('sysadmin/save-objek-modul-aplikasi', 'saveObjekModulAplikasi');
            Route::post('sysadmin/delete-objek-modul-aplikasi', 'deleteObjekModulAplikasi');
            Route::post('sysadmin/save-objek-modul-aplikasi-map', 'saveObjekModulAplikasiMap');
            Route::post('sysadmin/hapus-objek-modul-aplikasi-map', 'hapusObjekModulAplikasiMap');
        });
        Route::controller(MasterDokumenCtrl::class)->group(function () {
            Route::get('sysadmin/master-dokumen', 'masterDokumen');
            Route::get('sysadmin/master-objek-modul-aplikasi', 'masterObjekModulAplikasi');
            Route::get('sysadmin/master-dokumen-by-head', 'masterDokumenHead');
            Route::get('sysadmin/master-isi-dokumen', 'masterIsiDokumen');
            Route::get('sysadmin/master-modul-aplikasi-nourut', 'lastNourutObjekModul');
            Route::get('sysadmin/cetak-dokumen-mutu', 'cetakDokumenMutu');

            Route::post('sysadmin/save-dokumen', 'saveDokumen');
            Route::post('sysadmin/delete-rincian-dokumen', 'deleteRincianDokumen');
            Route::post('sysadmin/save-objek-modul-aplikasi', 'saveObjekModulAplikasi');
            Route::post('sysadmin/delete-objek-modul-aplikasi', 'deleteObjekModulAplikasi');
            Route::post('sysadmin/save-isi-dokumen-map', 'saveIsiDokumenMap');
            Route::post('sysadmin/hapus-isi-dokumen', 'hapusIsiDokumen');
            Route::get('sysadmin/riwayat-isi-dokumen', 'riwayatIsiDokumen');
            Route::get('sysadmin/viewer-isi-dokumen', 'isiDokumenViewer');
            Route::post('sysadmin/hit-isi-dokumen', 'hitIsiDokumen');
            Route::get('sysadmin/viewer-isi-dokumen-detail', 'viewerIsiDokumenDetail');
        });
        Route::controller(UdrCtrl::class)->group(function () {
            Route::get('udr/master-isi-udr', 'masterIsiUdr');
            Route::get('udr/nourut-udr', 'lastNourutUdr');
            Route::post('udr/hapus-isi-udr', 'hapusIsiUdr');
            Route::post('udr/save-isi-udr', 'saveIsiDokumenUdr');
            Route::get('udr/riwayat-isi-udr', 'riwayatIsiUdr');
            Route::get('udr/cetak-dokumen-udr', 'cetakDokumenUdr');
            Route::get('udr/log-isi-udr', 'logIsiUdr');
            Route::post('udr/upload-multi-udr', 'uploadMultiUdr');

            Route::get('udr/master-nomor-induk',  'masterNomorInduk');
            Route::get('udr/lookup-nomor-induk', 'lookupNomorInduk');
            Route::get('udr/nourut-nomor-induk', 'lastNourutNomorInduk');
            Route::post('udr/save-nomor-induk',  'saveNomorInduk');
            Route::post('udr/hapus-nomor-induk', 'hapusNomorInduk');
            Route::get('udr/resolve-open-udr-by-nomor-induk', 'resolveOpenUdrByNomorInduk');

            Route::get('udr/master-isi-dokumen', 'masterIsiDokumenSurveilan');
            Route::get('udr/log-isi-dokumen', 'logIsiDokumenSurveilan');
            Route::post('udr/save-isi-dokumen', 'saveIsiDokumenSurveilan');
            Route::post('udr/upload-multi-dokumen', 'uploadMultiDokumenSurveilan');
            Route::post('udr/hapus-isi-dokumen', 'hapusIsiDokumenSurveilan');
            Route::get('udr/nourut-dokumen', 'lastNourutDokumenSurveilan');
            Route::get('udr/riwayat-isi-dokumen', 'riwayatIsiDokumenSurveilan');
            Route::get('udr/cetak-dokumen', 'cetakDokumenSurveilan');
            Route::get('udr/preview-excel/{id}', 'previewExcelSurveilan');

            // UDS Drive baru. Endpoint lama di atas tetap dipertahankan.
            Route::get('udr/new-drive-items', 'listNewDriveItems');
            Route::get('udr/new-drive-folders', 'listNewDriveFolders');
            Route::get('udr/new-drive-activity', 'activityNewDriveItem');
            Route::get('udr/new-drive-open', 'openNewDriveItem');
            Route::get('udr/new-drive-download', 'downloadNewDriveItem');
            Route::get('udr/new-drive-open-revision', 'openNewDriveRevision');
            Route::get('udr/new-drive-download-revision', 'downloadNewDriveRevision');
            Route::get('udr/new-drive-link-info', 'newDriveLinkInfo');
            Route::get('udr/new-drive-folder-access', 'newDriveFolderAccess');
            Route::post('udr/new-drive-sync-audit-internal', 'syncAuditInternalDrive');
            Route::post('udr/new-drive-create-folder', 'createNewDriveFolder');
            Route::post('udr/new-drive-cv', 'createNewDriveCvLink');
            Route::post('udr/new-drive-folder-access', 'saveNewDriveFolderAccess');
            Route::post('udr/new-drive-check-upload', 'checkNewDriveUploadConflicts');
            Route::post('udr/new-drive-upload', 'uploadNewDriveItems');
            Route::post('udr/new-drive-replace-file', 'replaceNewDriveFile');
            Route::post('udr/new-drive-link-nomor-induk', 'linkNewDriveNomorInduk');
            Route::post('udr/new-drive-rename', 'renameNewDriveItem');
            Route::post('udr/new-drive-move', 'moveNewDriveItem');
            Route::post('udr/new-drive-trash', 'trashNewDriveItem');
            Route::post('udr/new-drive-restore', 'restoreNewDriveItem');
        });
        Route::controller(ChatCtrl::class)->group(function () {
            Route::get('/chat/contacts', 'contacts');
            Route::post('/chat/room/upsert', 'upsertPrivateRoom');
            Route::get('/chat/rooms', 'rooms');
            Route::get('/chat/messages', 'messages');
            Route::post('/chat/messages/send', 'send');
            Route::post('/chat/messages/delete', 'deleteMessages');
            Route::post('/chat/rooms/read', 'markRead');
        });

        Route::controller(MasterMapLoginUserToModulAplikasiCtrl::class)->group(function () {
            Route::get('sysadmin/master-map-loginuser-to-modulaplikasi', 'masterMapLoginUserToModulAplikasi');
            Route::get('sysadmin/master-map-loginuser-to-modulaplikasi-dropdown', 'masterMapLoginUserToModulAplikasidropdown');

            Route::post('sysadmin/save-map-loginuser-to-modulaplikasi', 'saveMapLoginUserToModulAplikasi');
            Route::post('sysadmin/delete-map-loginuser-to-modulaplikasi', 'deleteMapLoginUserToModulAplikasi');
        });
        Route::controller(MasterMapLoginUserToRuanganCtrl::class)->group(function () {
            Route::get('sysadmin/master-map-loginuser-to-ruangan', 'masterMapLoginUserToRuangan');
            Route::get('sysadmin/master-map-loginuser-to-ruangan-dropdown', 'masterMapLoginUserToRuangandropdown');

            Route::post('sysadmin/save-map-loginuser-to-ruangan', 'saveMapLoginUserToRuangan');
            Route::post('sysadmin/delete-map-loginuser-to-ruangan', 'deleteMapLoginUserToRuangan');
        });
        Route::controller(SettingDataFixedCtrl::class)->group(function () {
            Route::get('sysadmin/get-settingdatafixed', 'getDataFixed');
            Route::get('sysadmin/get-settingdatafixedbyid', 'getSettingById');
            Route::get('sysadmin/update-status-enabled', 'updateStatuEnabled');
            Route::get('sysadmin/get-kelompok-setting', 'getKelompokSettingDataFix');
            Route::get('sysadmin/get-setting-detail', 'getSettingDetail');
            Route::get('sysadmin/get-setting-combo', 'getComboPart');
            Route::get('sysadmin/get-table', 'getTable');
            Route::get('sysadmin/get-field-table', 'getFieldTable');
            Route::get('sysadmin/get-data-from-table', 'getDataFromTable');
            Route::get('sysadmin/get-report-display', 'getReportDisplayTable');
            Route::get('sysadmin/get/{namaField}', 'getSettingDataFixedGeneric');

            Route::post('sysadmin/post-settingdatafixe', 'SaveSettingDataFixed');
            Route::post('sysadmin/hapus-settingdatafixe', 'HapusSettingDataFixed');
            Route::post('sysadmin/tambah-settingdatafixe', 'TambahSettingDataFixed');
            Route::post('sysadmin/delete-settingdatafixed', 'deleteSettingDataFix');
            Route::post('sysadmin/update-setting', 'updateSettingDataFix');
        });

        Route::controller(MasterListCtrl::class)->group(function () {
            Route::get('sysadmin/master-list', 'masterList');
            Route::post('sysadmin/save-master-list', 'saveList');
            Route::post('sysadmin/delete-master-list', 'deleteList');
        });

        Route::controller(ApiChatBotCtrl::class)->group(function () {
            Route::post('chatbot/customer/history-order', 'historyOrderCustomer');
            Route::post('chatbot/customer/alat', 'alatCustomer');
            Route::post('chatbot/customer/keranjang', 'keranjangCustomer');
            Route::post('chatbot/customer/summary', 'summaryCustomer');
            Route::post('chatbot/customer/certificate-status', 'certificateStatusCustomer');
            Route::post('chatbot/customer/profile', 'profileCustomer');
            Route::post('chatbot/customer/history-order-group', 'historyOrderGroupCustomer');
        });
    });
});
Route::controller(AuthCtrl::class)->group(function () {
    Route::post('service/auth/login', 'login');
    Route::post('service/auth/register', 'registerUser');
    Route::post('service/auth/otp/verify', 'verifyOtp');
    Route::post('service/auth/otp/resend', 'resendOtp');
    Route::post('service/auth/login-face', 'loginByFace');
    Route::post('service/auth/forgot-password/request-otp', 'forgotPasswordRequestOtp');
    Route::post('service/auth/forgot-password/verify-otp', 'verifyForgotPasswordOtp');
    Route::post('service/auth/forgot-password/reset', 'resetPasswordByOtp');
    Route::post('service/auth/mobile-print-login', 'mobilePrintLogin');
    Route::post('service/auth/mobile-print-file', 'mobilePrintFile');
    Route::post('service/auth/mobile-print-registration-document', 'mobilePrintRegistrationDocument');
    Route::post('service/auth/mobile-selesai-terima-tools', 'mobileSelesaiTerimaTools');
    Route::post('service/auth/mobile-selesai-terima-versions', 'mobileSelesaiTerimaVersions');
    Route::post('service/auth/mobile-save-selesai-terima', 'mobileSaveSelesaiTerima');
});

Route::controller(NoAuthCtrl::class)->group(function () {
    Route::get('service/get-data-chart-integrasi', 'dataChartIntegrasi');
    Route::get('service/get-landing-service-stats', 'getLandingServiceStats');
    Route::get('service/get-landing-mapping-layanan', 'getLandingMappingLayanan');
    Route::post('service/save-data-temperature', 'saveDataTemperature');
    Route::post('service/save-kalibrasi-to-web', 'saveKalibrasiAI');
    Route::get('service/measurement-corrections', 'getMeasurementCorrections');
    Route::get('service/measurement-corrections-ai', 'getMeasurementCorrectionsFromKalibrasiAi');
    Route::get('service/get-nav', 'getNav');
    Route::get('service/get-nav/{slug}', 'getNavBySlug');
    Route::post('service/mobile-print-token', 'getMobilePrintToken');
    Route::get('service/validasi', 'validasiPbjPublic');
    Route::get('service/validasi-sertifikat', 'validasiSertifikatPublic');
    Route::get('service/validasi-surat-jalan', 'validasiSuratJalan');
});

Route::controller(MobileNotificationCtrl::class)->group(function () {
    Route::post('/mobile/register-fcm-token', 'registerFcmToken');
    Route::post('/mobile/test-send-fcm-user', 'testSendFcmToUser');
    Route::post('/mobile/test-send-fcm-mitra', 'testSendFcmToMitra');
});

Route::get('/service/auth/verify-email/{id}/{hash}', [AuthCtrl::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::get('service/sysadmin/preview-excel/{id}', [MasterDokumenCtrl::class, 'previewExcel'])
    ->name('mutu.preview.excel');

Route::get('/', function () {
    return view('welcome');
});
