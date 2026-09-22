<?php

namespace App\Http\Controllers\Registrasi;


use App\Http\Controllers\Controller;
use App\Models\Master\PaketKalibrasi;
use App\Models\Transaksi\MitraRegistrasi;
use App\Models\Transaksi\MitraRegistrasiDetail;
use App\Models\Transaksi\SurveyDetailPelanggan;
use App\Models\Transaksi\SurveyPelanggan;
use App\Traits\Valet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RegistrasiMitraCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function mitraRegistrasi(Request $r)
    {
        $data = DB::table('mitra_m as mt')
            ->select(
                'mt.id as nocmfk',
                'mt.namaperusahaan',
                'mt.nohp',
                'mt.email',
                'mt.tgldaftar',
                'mt.alamatktr',
            )
            ->where('mt.statusenabled', true);

        if (isset($r['id']) && $r['id'] != '' && $r['id'] != 'undefined') {
            $data = $data->where('mt.id', $r['id']);
        }

        $data = $data->first();

        $result['mitra'] = $data;
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function saveRegistrasiMitra(Request $r)
    {
        DB::beginTransaction();
        try {
            $APD = json_decode($r->input('mitraregistrasidetail'), true);

            $file = $r->file('fileAms');
            $allowedExtensions = ['pdf'];
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions)) {
                throw new \Exception("File harus berupa gambar (pdf).");
            }
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('berkas-customer'), $filename);

            $filenameTools = null;
            if ($r->file('fileCustomerTools') != null) {
                $fileTools = $r->file('fileCustomerTools');
                $allowedExtensionsTools = ['xls', 'xlsx'];
                $extensionTools = strtolower($fileTools->getClientOriginalExtension());
                if (!in_array($extensionTools, $allowedExtensionsTools)) {
                    throw new \Exception("File harus berupa excel.");
                }
                $filenameTools = time() . '_' . preg_replace('/\s+/', '_', $fileTools->getClientOriginalName());
                $fileTools->move(public_path('berkas-customer'), $filenameTools);
            }


            if ($r['norec'] == '') {
                $lokasi = $r['lokasikalibrasi'];
                $nopendaftaran = $this->generateNomorPendaftaran(
                    $lokasi,
                    'kalibrasi',
                    $r['tglregistrasi']
                );
                $model_PD = new MitraRegistrasi();
                $model_PD->norec = $model_PD->generateNewId();
                $model_PD->statusenabled = true;
            } else {
                $model_PD =  MitraRegistrasi::where('norec', $r['norec'])->first();
                $nopendaftaran = $model_PD->nopendaftaran;
                $this->validateNomorPendaftaran(
                    $nopendaftaran,
                    $r['lokasikalibrasi'],
                    'kalibrasi',
                    $r['tglregistrasi']
                );
            }
            $model_PD->nomitrafk = $r['nomitrafk'];
            $model_PD->tglregistrasi =  $r['tglregistrasi'];
            $model_PD->nopendaftaran = $nopendaftaran;
            $model_PD->catatan = $r['catatan'];
            $model_PD->lokasikalibrasi = $r['lokasikalibrasi'];
            $model_PD->paketkalibrasi = $r['paketkalibrasi'];
            $model_PD->namapenanggungjawab = $r['namapenanggungjawab'];
            $model_PD->nohppenanggungjawab = $r['nohppenanggungjawab'];
            $model_PD->jabatanpenanggungjawab = $r['jabatanpenanggungjawab'];
            $model_PD->petugas = $this->getNamaPegawai();
            $model_PD->statusorder = 0;
            $model_PD->statusordermanager = 0;
            $model_PD->jenisorder = 'kalibrasi';
            $model_PD->rentangUkur = $r['rentangUkur'];
            $model_PD->rentangUkurketPermintaanPelanggan = $r['rentangUkurketPermintaanPelanggan'];
            $model_PD->verifregiscustomer = true;
            $model_PD->tanggalverifregiscustomer = now();
            $model_PD->filecustomerams = $filename;
            $model_PD->filecustomertools = $filenameTools ?? null;
            $model_PD->isstandarulab = $r['isstandarulab'] ?? null;
            $model_PD->save();

            $durasikalibrasi = null;
            if (!empty($r['paketkalibrasi'])) {
                $paket = PaketKalibrasi::find($r['paketkalibrasi']);
                if ($paket) {
                    $durasikalibrasi = $paket->hari;
                }
            }
            $groupedApd = collect($APD);
            $incomingDetailNorecs = $groupedApd->pluck('norec')->filter()->unique()->values();
            $existingDetails = $incomingDetailNorecs->isNotEmpty()
                ? MitraRegistrasiDetail::whereIn('norec', $incomingDetailNorecs)->get()->keyBy('norec')
                : collect();
            $alatIds = $groupedApd->pluck('namaalatfk')->filter(function ($id) {
                return $id !== null && $id !== '';
            })->unique()->values();
            $alatRows = $alatIds->isNotEmpty()
                ? DB::table('mapunittoalat_m')->whereIn('id', $alatIds)->get()->keyBy('id')
                : collect();
            $dataAPD = [];
            $alatCheckout = [];
            foreach ($groupedApd as $alat) {
                if (isset($alat['norec']) && $alat['norec'] != '') {
                    $model_APD = $existingDetails->get($alat['norec']);
                } else {
                    $model_APD = new MitraRegistrasiDetail;
                    $model_APD->norec = $model_APD->generateNewId();
                    $model_APD->statusenabled = true;
                }
                $model_APD->namaalatfk = $alat['namaalatfk'] ?? null;
                $model_APD->lingkupkalibrasifk = $alat['lingkupkalibrasifk'] ?? null;
                $model_APD->noregistrasifk = $model_PD->norec;
                if ($durasikalibrasi !== null) {
                    $model_APD->durasikalbrasi = $durasikalibrasi;
                }
                $model_APD->save();
                $dataAPD[] = $model_APD;

                $detailAlat = '-';
                if (isset($alat['namaalatfk'])) {
                    $alatRow = $alatRows->get($alat['namaalatfk']);
                    if ($alatRow) {
                        $detailAlat =
                            ($alatRow->namaproduk ?? 'Alat Tidak Dikenal')
                            . ' | Merk: ' . ($alatRow->namamerk ?? '-')
                            . ' | Tipe: ' . ($alatRow->namatipe ?? '-')
                            . ' | SN: ' . ($alatRow->namaserialnumber ?? '-');
                    }
                }
                $alatCheckout[] = $detailAlat;
            }

            DB::commit();
            $namaCustomer = $model_PD->namapenanggungjawab ?? '-';
            $nohpCustomer = $model_PD->nohppenanggungjawab ?? null;
            $waktuOrder = $model_PD->tglregistrasi ? \Carbon\Carbon::parse($model_PD->tglregistrasi) : now();
            $waktuOrderStr = $waktuOrder->locale('id')->translatedFormat('d F Y H:i') . ' WIB';

            if ($nohpCustomer) {
                $pesan = "Yth. {$namaCustomer},\n\n"
                    . "Pendaftaran alat *kalibrasi* Anda telah berhasil diterima.\n"
                    . "Tanggal/Waktu Pendaftaran: *$waktuOrderStr*\n"
                    . "Total alat yang didaftarkan: *" . count($alatCheckout) . "* buah.\n\n"
                    . "Rincian alat yang didaftarkan:\n";
                foreach ($alatCheckout as $idx => $detailAlat) {
                    $pesan .= ($idx + 1) . ". " . $detailAlat . "\n";
                }
                $unit = '-';
                if ($r['nomitrafk']) {
                    $mitra = DB::table('mitra_m')
                        ->whereRaw('CAST(id AS INTEGER) = ?', $r['nomitrafk'])
                        ->first();
                    $unit = $mitra->namaperusahaan ?? '-';
                }
                $pesan .= "\nKami akan segera memproses permintaan Anda.\n\n"
                    . "Terima kasih Yth Bpk/Ibu $namaCustomer dari $unit\n"
                    . "atas kepercayaan Anda kepada Tim U-LAB\n"
                    . "Laboratorium Kalibrasi Multilokasi PT PLN Nusantara Power terakreditasi KAN.\n\n"
                    . "Bagi kami, setiap alat ukur bukan hanya sekadar perangkat, tapi bagian penting dari produktivitas dan keselamatan kerja Anda.\n\n"
                    . "Dengan akurasi, kecepatan, dan layanan 24/7, kami hadir sebagai mitra strategis untuk industri Anda.\n\n"
                    . "🌐 Layanan Multilokasi (Jakarta & Gresik)\n"
                    . "🕐 Operasional 24/7\n"
                    . "🔧 Kalibrasi & Repair dalam satu pintu\n"
                    . "📄 Sertifikat ISO/IEC 17025\n\n"
                    . "Karena kepuasan Anda adalah tolok ukur keberhasilan kami.\n"
                    . "Bersama U-LAB, akurasi & presisi bukan lagi sekadar janji — tapi bukti.\n\n"
                    . "Pagi cerah langit membiru,\n"
                    . "Langkah mantap menuju tujuan.\n"
                    . "Mari bekerja penuh rindu,\n"
                    . "Demi pelayanan dan kepuasan pelanggan.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpCustomer, $pesan);
            }

            $transMessage = "Simpan Registrasi Kalibrasi Mitra Sukses";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "result"  => $e->getMessage()
            );
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function saveRegistrasiStandarUlab(Request $r)
    {
        DB::beginTransaction();
        try {
            $APD = json_decode($r->input('mitraregistrasidetail'), true);

            if ($r['norec'] == '') {
                $lokasi = $r['lokasikalibrasi'];
                $nopendaftaran = $this->generateNomorPendaftaran(
                    $lokasi,
                    'kalibrasi',
                    $r['tglregistrasi']
                );
                $model_PD = new MitraRegistrasi();
                $model_PD->norec = $model_PD->generateNewId();
                $model_PD->statusenabled = true;
            } else {
                $model_PD =  MitraRegistrasi::where('norec', $r['norec'])->first();
                $nopendaftaran = $model_PD->nopendaftaran;
                $this->validateNomorPendaftaran(
                    $nopendaftaran,
                    $r['lokasikalibrasi'],
                    'kalibrasi',
                    $r['tglregistrasi']
                );
            }
            $model_PD->nomitrafk = $r['nomitrafk'];
            $model_PD->tglregistrasi =  $r['tglregistrasi'];
            $model_PD->nopendaftaran = $nopendaftaran;
            $model_PD->catatan = $r['catatan'];
            $model_PD->lokasikalibrasi = $r['lokasikalibrasi'];
            $model_PD->namapenanggungjawab = $r['namapenanggungjawab'];
            $model_PD->petugas = $this->getNamaPegawai();
            $model_PD->statusorder = 0;
            $model_PD->statusordermanager = 0;
            $model_PD->jenisorder = 'kalibrasi';
            $model_PD->verifregiscustomer = true;
            $model_PD->tanggalverifregiscustomer = now();
            $model_PD->isstandarulab = true;
            $model_PD->iskaji = true;
            $model_PD->petugaskaji = $this->getPegawaiId();
            $model_PD->tglkajiulang = now();
            $model_PD->save();

            $groupedApd = collect($APD);
            $incomingDetailNorecs = $groupedApd->pluck('norec')->filter()->unique()->values();
            $existingDetails = $incomingDetailNorecs->isNotEmpty()
                ? MitraRegistrasiDetail::whereIn('norec', $incomingDetailNorecs)->get()->keyBy('norec')
                : collect();
            $dataAPD = [];
            foreach ($groupedApd as $alat) {
                if (isset($alat['norec']) && $alat['norec'] != '') {
                    $model_APD = $existingDetails->get($alat['norec']);
                } else {
                    $model_APD = new MitraRegistrasiDetail;
                    $model_APD->norec = $model_APD->generateNewId();
                    $model_APD->statusenabled = true;
                }
                $model_APD->namaalatfk = $alat['namaalatfk'] ?? null;
                $model_APD->lingkupkalibrasifk = $alat['lingkupkalibrasifk'] ?? null;
                $model_APD->lokasikajifk = $r['lokasikalibrasi'] ?? null;
                $model_APD->penyeliateknikfk = $alat['penyeliateknik'] ?? null;
                $model_APD->pelaksanateknikfk = $alat['pelaksana'] ?? null;
                $model_APD->statusorderpelaksana = 0;
                $model_APD->statusorderpenyelia = 0;
                $model_APD->iskaji = true;
                $model_APD->noregistrasifk = $model_PD->norec;
                $model_APD->save();
                $dataAPD[] = $model_APD;
            }

            DB::commit();

            $transMessage = "Simpan Registrasi Standar Ulab Sukses";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "result"  => $e->getMessage()
            );
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }


    public function saveRegisKalibrasiUlab(Request $r)
    {
        DB::beginTransaction();
        try {
            $APD = json_decode($r->input('mitraregistrasidetail'), true);

            if ($r['norec'] == '') {
                $lokasi = $r['lokasikalibrasi'];
                $nopendaftaran = $this->generateNomorPendaftaran(
                    $lokasi,
                    'kalibrasi',
                    $r['tglregistrasi']
                );
                $model_PD = new MitraRegistrasi();
                $model_PD->norec = $model_PD->generateNewId();
                $model_PD->statusenabled = true;
            } else {
                $model_PD =  MitraRegistrasi::where('norec', $r['norec'])->first();
                $nopendaftaran = $model_PD->nopendaftaran;
                $this->validateNomorPendaftaran(
                    $nopendaftaran,
                    $r['lokasikalibrasi'],
                    'kalibrasi',
                    $r['tglregistrasi']
                );
            }
            $model_PD->nomitrafk = $r['nomitrafk'];
            $model_PD->tglregistrasi =  $r['tglregistrasi'];
            $model_PD->nopendaftaran = $nopendaftaran;
            $model_PD->catatan = $r['catatan'];
            $model_PD->lokasikalibrasi = $r['lokasikalibrasi'];
            $model_PD->namapenanggungjawab = $r['namapenanggungjawab'];
            $model_PD->petugas = $this->getNamaPegawai();
            $model_PD->statusorder = 0;
            $model_PD->statusordermanager = 0;
            $model_PD->jenisorder = 'kalibrasi';
            $model_PD->verifregiscustomer = true;
            $model_PD->tanggalverifregiscustomer = now();
            $model_PD->iskalibrasiinternal = true;
            $model_PD->iskaji = true;
            $model_PD->petugaskaji = $this->getPegawaiId();
            $model_PD->tglkajiulang = now();
            $model_PD->save();

            $groupedApd = collect($APD);
            $incomingDetailNorecs = $groupedApd->pluck('norec')->filter()->unique()->values();
            $existingDetails = $incomingDetailNorecs->isNotEmpty()
                ? MitraRegistrasiDetail::whereIn('norec', $incomingDetailNorecs)->get()->keyBy('norec')
                : collect();
            $dataAPD = [];
            foreach ($groupedApd as $alat) {
                if (isset($alat['norec']) && $alat['norec'] != '') {
                    $model_APD = $existingDetails->get($alat['norec']);
                } else {
                    $model_APD = new MitraRegistrasiDetail;
                    $model_APD->norec = $model_APD->generateNewId();
                    $model_APD->statusenabled = true;
                }
                $model_APD->namaalatfk = $alat['namaalatfk'] ?? null;
                $model_APD->lingkupkalibrasifk = $alat['lingkupkalibrasifk'] ?? null;
                $model_APD->lokasikajifk = $r['lokasikalibrasi'] ?? null;
                $model_APD->penyeliateknikfk = $alat['penyeliateknik'] ?? null;
                $model_APD->pelaksanateknikfk = $alat['pelaksana'] ?? null;
                $model_APD->statusorderpelaksana = 0;
                $model_APD->statusorderpenyelia = 0;
                $model_APD->iskaji = true;
                $model_APD->noregistrasifk = $model_PD->norec;
                $model_APD->save();
                $dataAPD[] = $model_APD;
            }

            DB::commit();

            $transMessage = "Simpan Registrasi Kalibrasi Internal Ulab Sukses";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "result"  => $e->getMessage()
            );
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function saveRegistrasiRepairMitra(Request $r)
    {
        DB::beginTransaction();
        try {
            $APD = json_decode($r->input('mitraregistrasidetail'), true);

            $file = $r->file('fileAms');
            $allowedExtensions = ['pdf'];
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions)) {
                throw new \Exception("File harus berupa gambar (pdf).");
            }
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('berkas-customer'), $filename);

            $filenameTools = null;
            if ($r->file('fileCustomerTools') != null) {
                $fileTools = $r->file('fileCustomerTools');
                $allowedExtensionsTools = ['xls', 'xlsx'];
                $extensionTools = strtolower($fileTools->getClientOriginalExtension());
                if (!in_array($extensionTools, $allowedExtensionsTools)) {
                    throw new \Exception("File harus berupa excel.");
                }
                $filenameTools = time() . '_' . preg_replace('/\s+/', '_', $fileTools->getClientOriginalName());
                $fileTools->move(public_path('berkas-customer'), $filenameTools);
            }

            if ($r['norec'] == '') {
                $lokasi = $r['lokasirepair'];
                $nopendaftaran = $this->generateNomorPendaftaran(
                    $lokasi,
                    'repair',
                    $r['tglregistrasi']
                );
                $model_PD = new MitraRegistrasi();
                $model_PD->norec = $model_PD->generateNewId();
                $model_PD->statusenabled = true;
            } else {
                $model_PD =  MitraRegistrasi::where('norec', $r['norec'])->first();
                $nopendaftaran = $model_PD->nopendaftaran;
                $this->validateNomorPendaftaran(
                    $nopendaftaran,
                    $r['lokasirepair'],
                    'repair',
                    $r['tglregistrasi']
                );
            }
            $model_PD->nomitrafk = $r['nomitrafk'];
            $model_PD->tglregistrasi =  $r['tglregistrasi'];
            $model_PD->nopendaftaran = $nopendaftaran;
            $model_PD->catatan = $r['catatan'];
            $model_PD->lokasirepair = $r['lokasirepair'];
            $model_PD->namapenanggungjawab = $r['namapenanggungjawab'];
            $model_PD->nohppenanggungjawab = $r['nohppenanggungjawab'];
            $model_PD->jabatanpenanggungjawab = $r['jabatanpenanggungjawab'];
            $model_PD->petugas = $this->getNamaPegawai();
            $model_PD->statusorder = 0;
            $model_PD->statusordermanager = 0;
            $model_PD->jenisorder = 'repair';
            $model_PD->verifregiscustomer = true;
            $model_PD->tanggalverifregiscustomer = now();
            $model_PD->filecustomerams = $filename;
            $model_PD->filecustomertools = $filenameTools ?? null;
            $model_PD->save();

            $groupedApd = collect($APD);
            $incomingDetailNorecs = $groupedApd->pluck('norec')->filter()->unique()->values();
            $existingDetails = $incomingDetailNorecs->isNotEmpty()
                ? MitraRegistrasiDetail::whereIn('norec', $incomingDetailNorecs)->get()->keyBy('norec')
                : collect();
            $alatIds = $groupedApd->pluck('namaalatfk')->filter(function ($id) {
                return $id !== null && $id !== '';
            })->unique()->values();
            $alatRows = $alatIds->isNotEmpty()
                ? DB::table('mapunittoalat_m')->whereIn('id', $alatIds)->get()->keyBy('id')
                : collect();
            $dataAPD = [];
            $alatCheckout = [];
            foreach ($groupedApd as $alat) {
                if (isset($alat['norec']) && $alat['norec'] != '') {
                    $model_APD = $existingDetails->get($alat['norec']);
                } else {
                    $model_APD = new MitraRegistrasiDetail;
                    $model_APD->norec = $model_APD->generateNewId();
                    $model_APD->statusenabled = true;
                }
                $model_APD->namaalatfk = $alat['namaalatfk'] ?? null;
                $model_APD->noregistrasifk = $model_PD->norec;
                $model_APD->save();

                $detailAlat = '-';
                if (isset($alat['namaalatfk'])) {
                    $alatRow = $alatRows->get($alat['namaalatfk']);
                    if ($alatRow) {
                        $detailAlat =
                            ($alatRow->namaproduk ?? 'Alat Tidak Dikenal')
                            . ' | Merk: ' . ($alatRow->namamerk ?? '-')
                            . ' | Tipe: ' . ($alatRow->namatipe ?? '-')
                            . ' | SN: ' . ($alatRow->namaserialnumber ?? '-');
                    }
                }
                $alatCheckout[] = $detailAlat;

                $dataAPD[] = $model_APD;
            }

            DB::commit();

            $namaCustomer = $model_PD->namapenanggungjawab ?? '-';
            $nohpCustomer = $model_PD->nohppenanggungjawab ?? null;
            $waktuOrder = $model_PD->tglregistrasi ? \Carbon\Carbon::parse($model_PD->tglregistrasi) : now();
            $waktuOrderStr = $waktuOrder->locale('id')->translatedFormat('d F Y H:i') . ' WIB';

            if ($nohpCustomer) {
                $pesan = "Yth. {$namaCustomer},\n\n"
                    . "Pendaftaran alat *Repair* Anda telah berhasil diterima.\n"
                    . "Tanggal/Waktu Pendaftaran: *$waktuOrderStr*\n"
                    . "Total alat yang didaftarkan: *" . count($alatCheckout) . "* buah.\n\n"
                    . "Rincian alat yang didaftarkan:\n";
                foreach ($alatCheckout as $idx => $detailAlat) {
                    $pesan .= ($idx + 1) . ". " . $detailAlat . "\n";
                }
                $unit = '-';
                if ($r['nomitrafk']) {
                    $mitra = DB::table('mitra_m')
                        ->whereRaw('CAST(id AS INTEGER) = ?', $r['nomitrafk'])
                        ->first();
                    $unit = $mitra->namaperusahaan ?? '-';
                }
                $pesan .= "\nKami akan segera memproses permintaan Anda.\n\n"
                    . "Terima kasih Yth Bpk/Ibu $namaCustomer dari $unit\n"
                    . "atas kepercayaan Anda kepada Tim U-LAB\n"
                    . "Laboratorium Kalibrasi Multilokasi PT PLN Nusantara Power terakreditasi KAN.\n\n"
                    . "Bagi kami, setiap alat ukur bukan hanya sekadar perangkat, tapi bagian penting dari produktivitas dan keselamatan kerja Anda.\n\n"
                    . "Dengan akurasi, kecepatan, dan layanan 24/7, kami hadir sebagai mitra strategis untuk industri Anda.\n\n"
                    . "🌐 Layanan Multilokasi (Jakarta & Gresik)\n"
                    . "🕐 Operasional 24/7\n"
                    . "🔧 Kalibrasi & Repair dalam satu pintu\n"
                    . "📄 Sertifikat ISO/IEC 17025\n\n"
                    . "Karena kepuasan Anda adalah tolok ukur keberhasilan kami.\n"
                    . "Bersama U-LAB, akurasi & presisi bukan lagi sekadar janji — tapi bukti.\n\n"
                    . "Pagi cerah langit membiru,\n"
                    . "Langkah mantap menuju tujuan.\n"
                    . "Mari bekerja penuh rindu,\n"
                    . "Demi pelayanan dan kepuasan pelanggan.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpCustomer, $pesan);
            }

            $transMessage = "Simpan Registrasi Repair Mitra Sukses";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "result"  => $e->getMessage()
            );
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function kirimWhatsappNotifikasi($nohp, $pesan)
    {
        try {
            if (empty($nohp)) {
                Log::warning('WA tidak dikirim: nomor HP kosong', [
                    'pesan' => $pesan,
                ]);
                return [
                    'status' => false,
                    'message' => 'Nomor HP kosong',
                    'skip' => true,
                ];
            }
            $nomor = preg_replace('/^0/', '62', $nohp);
            $nomor = preg_replace('/[^0-9]/', '', $nomor);
            if (strlen($nomor) < 10) {
                Log::warning('WA tidak dikirim: nomor HP tidak valid', [
                    'nohp_asli' => $nohp,
                    'nomor_format' => $nomor,
                    'pesan' => $pesan,
                ]);
                return [
                    'status' => false,
                    'message' => 'Nomor HP tidak valid',
                    'skip' => true,
                ];
            }
            $token = config('whatsapp.token');
            $secret_key = config('whatsapp.secret_key');
            if (empty($token) || empty($secret_key)) {
                Log::warning('WA tidak dikirim: token atau secret Wablas kosong', [
                    'phone' => $nomor,
                ]);
                return [
                    'status' => false,
                    'message' => 'Token atau secret Wablas kosong',
                ];
            }
            $response = \App\Helpers\WhatsAppHelper::send($nomor, $pesan);
            if (!$response->successful()) {
                Log::warning('WA gagal dikirim: HTTP Wablas tidak sukses', [
                    'phone' => $nomor,
                    'http_status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return [
                    'status' => false,
                    'message' => 'HTTP Wablas tidak sukses',
                    'http_status' => $response->status(),
                ];
            }
            $result = $response->json();
            if (!is_array($result)) {
                Log::warning('WA gagal dikirim: response Wablas bukan JSON valid', [
                    'phone' => $nomor,
                    'response' => $response->body(),
                ]);
                return [
                    'status' => false,
                    'message' => 'Response Wablas tidak valid',
                ];
            }
            if (!isset($result['status']) || $result['status'] == false) {
                Log::warning('WA gagal dikirim dari Wablas', [
                    'phone' => $nomor,
                    'message' => $result['message'] ?? 'Tidak diketahui',
                    'response' => $result,
                ]);
                return [
                    'status' => false,
                    'message' => $result['message'] ?? 'Gagal kirim WhatsApp',
                    'response' => $result,
                ];
            }
            Log::info('WA berhasil dikirim', [
                'phone' => $nomor,
                'response' => $result,
            ]);
            return $result;
        } catch (\Throwable $e) {
            Log::error('WA error tapi proses utama tetap dilanjutkan', [
                'nohp' => $nohp,
                'message_error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [
                'status' => false,
                'message' => 'WA error: ' . $e->getMessage(),
                'error_handled' => true,
            ];
        }
    }

    public function dropdownPaketKalibrasi(Request $r)
    {
        try {
            $search = $r['query'] ?? '';
            $concat_label = "
                CONCAT_WS(
                    ' - ', 
                    COALESCE(pk.namapaket, '-'), 
                    COALESCE(pk.hari::text || ' hari', '-')
                )
            ";

            $data = DB::table('paketkalibrasi_m as pk')
                ->select(
                    'pk.id as value',
                    DB::raw("$concat_label as label")
                )
                ->where('pk.statusenabled', true);

            if (!empty($search)) {
                $data = $data->whereRaw("$concat_label ILIKE ?", ["%$search%"]);
            }

            if (isset($r['orderby']) && $r['orderby'] != '') {
                $data = $data->orderBy($r['orderby']);
            }
            if (isset($r['limit']) && $r['limit'] != '') {
                $data = $data->limit($r['limit']);
            }
            $data = $data->get();
        } catch (Exception $e) {
            $data = $e->getMessage() . ' ' . $e->getLine();
        }

        return $this->respond($data);
    }

    public function saveSurveyPelanggan(Request $r)
    {
        DB::beginTransaction();
        try {
            $PD = $r['survey'];
            if (!is_array($PD)) {
                throw new \Exception('Data survey tidak valid.');
            }

            $atributList = $PD['atributList'] ?? [];
            $registrasiFk = trim((string) ($PD['registrasifk'] ?? ''));

            if ($registrasiFk === '') {
                throw new \Exception('Nomor pendaftaran survey tidak valid.');
            }

            $registrasi = DB::table('mitraregistrasi_t')
                ->where('norec', $registrasiFk)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$registrasi) {
                throw new \Exception('Data pendaftaran tidak ditemukan.');
            }

            if ($this->isInternalCalibrationFlag($registrasi->iskalibrasiinternal ?? false)) {
                throw new \Exception('Survey kepuasan tidak berlaku untuk order kalibrasi internal.');
            }

            $completionColumn = strtolower((string) ($registrasi->jenisorder ?? '')) === 'repair'
                ? 'tglsetujumanagerlaporanrepair'
                : 'tglsetujumanagerlembarkerja';
            $hasCompletedTool = DB::table('mitraregistrasidetail_t')
                ->where('noregistrasifk', $registrasiFk)
                ->where('statusenabled', true)
                ->whereNotNull($completionColumn)
                ->exists();

            if (!$hasCompletedTool) {
                throw new \Exception('Survey kepuasan baru dapat diisi setelah minimal satu alat selesai.');
            }

            $requiredFields = [
                'namaresponden' => 'Nama lengkap wajib diisi.',
                'unitdivisikerja' => 'Unit / Divisi Kerja wajib diisi.',
                'jabatanresponden' => 'Jabatan / Posisi wajib diisi.',
                'notelpon' => 'No. Telp / HP wajib diisi.',
            ];

            foreach ($requiredFields as $field => $message) {
                if (trim((string)($PD[$field] ?? '')) === '') {
                    throw new \Exception($message);
                }
            }

            if (!is_array($atributList) || count($atributList) !== 13) {
                throw new \Exception('Seluruh 13 Atribut Kepuasan wajib diisi dengan lengkap.');
            }

            $nomorAtributSurvey = collect($atributList)
                ->pluck('no')
                ->map(fn ($no) => (int) $no)
                ->unique()
                ->sort()
                ->values()
                ->all();
            if ($nomorAtributSurvey !== range(1, 13)) {
                throw new \Exception('Seluruh 13 Atribut Kepuasan wajib diisi dengan lengkap.');
            }

            foreach ($atributList as $alat) {
                if (
                    !isset($alat['harapan']) || $alat['harapan'] === '' ||
                    !isset($alat['kepuasan']) || $alat['kepuasan'] === ''
                ) {
                    throw new \Exception('Harapan dan kepuasan pada seluruh Atribut Kepuasan wajib diisi.');
                }

                if (
                    !is_numeric($alat['harapan']) || (int) $alat['harapan'] < 1 || (int) $alat['harapan'] > 6 ||
                    !is_numeric($alat['kepuasan']) || (int) $alat['kepuasan'] < 1 || (int) $alat['kepuasan'] > 6
                ) {
                    throw new \Exception('Nilai harapan dan kepuasan harus berada pada skala 1 sampai 6.');
                }
            }

            $model_PD = SurveyPelanggan::where('registrasifk', $registrasiFk)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();
            $isNewSurvey = !$model_PD;

            if (!$model_PD) {
                $model_PD = new SurveyPelanggan();
                $model_PD->norec = $model_PD->generateNewId();
                $model_PD->statusenabled = true;
            }
            $model_PD->registrasifk = $registrasiFk;
            $model_PD->namaresponden = $PD['namaresponden'] ?? null;
            $model_PD->unitdivisikerja = $PD['unitdivisikerja'] ?? null;
            $model_PD->jabatanresponden = $PD['jabatanresponden'] ?? null;
            $model_PD->lamabekerja = $PD['lamabekerja'] ?? null;
            $model_PD->jeniskelamin = $PD['jeniskelamin'] ?? null;
            $model_PD->usia = $PD['usia'] ?? null;
            $model_PD->notelpon = $PD['notelpon'] ?? null;
            $model_PD->pendidikan = $PD['pendidikan'] ?? $PD['notependidikanlpon'] ?? null;
            $model_PD->lamamenjadimitra = $PD['lamamenjadimitra'] ?? null;
            $model_PD->b21 = $PD['b21'] ?? null;
            $model_PD->b22 = $PD['b22'] ?? null;
            $model_PD->b23 = $PD['b23'] ?? null;
            $model_PD->ketidakpuasanlayanan = $PD['ketidakpuasanlayanan'] ?? null;
            $model_PD->masukansaran = $PD['masukansaran'] ?? null;
            $model_PD->d1_skor = $PD['d1_skor'] ?? null;
            $model_PD->d1_penjelasan = $PD['d1_penjelasan'] ?? null;
            $model_PD->d2_skor = $PD['d2_skor'] ?? null;
            $model_PD->d2_penjelasan = $PD['d2_penjelasan'] ?? null;
            $model_PD->namapenanggungjawab = $PD['namapenanggungjawab'] ?? null;
            $model_PD->notelpon = $PD['notelpon'] ?? null;
            $model_PD->jabatanresponden = $PD['jabatanresponden'] ?? null;

            $model_PD->save();
            $dataAPD = [];
            $existingSurveyDetails = SurveyDetailPelanggan::where('surveyfk', $model_PD->norec)
                ->where('statusenabled', true)
                ->get()
                ->keyBy(fn ($detail) => (string) $detail->no);
            foreach ($atributList as $index => $alat) {
                $nomorAtribut = $alat['no'] ?? ($index + 1);
                $model_APD = $existingSurveyDetails->get((string) $nomorAtribut);

                if (!$model_APD) {
                    $model_APD = new SurveyDetailPelanggan();
                    $model_APD->norec = $model_APD->generateNewId();
                    $model_APD->statusenabled = true;
                }

                $model_APD->surveyfk = $model_PD->norec;
                $model_APD->no = $nomorAtribut;
                $model_APD->harapan = $alat['harapan'] ?? null;
                $model_APD->kepuasan = $alat['kepuasan'] ?? null;

                $model_APD->save();
                $dataAPD[] = $model_APD;
            }

            DB::table('mitraregistrasi_t')
                ->where('norec', $registrasiFk)
                ->update([
                    'isikepuasanpelanggan' => true,
                ]);

            DB::commit();

            try {
                $customer = DB::table('users')->where('id', $this->getPegawaiId())->first();
                $namaCustomer = $customer->name ?? '-';
                $regis = DB::table('mitraregistrasi_t')
                    ->where('norec', $model_PD->registrasifk)
                    ->first();
                $idAdmin = $regis->petugaskaji ?? null;
                $nopendaftaran = $regis->nopendaftaran ?? '-';

                if ($isNewSurvey && $idAdmin) {
                    $admin = DB::table('pegawai_m')->where('id', $idAdmin)->first();
                    $nohpAdmin = $admin->nohandphone ?? null;
                    $namaAdmin = $admin->namalengkap ?? '-';

                    $pesanAdmin = "Halo Tim Hebat U-LAB ! 👋\n"
                        . "Yth. $namaAdmin,\n\n"
                        . "Pemberitahuan: customer telah mengisi survey  atas nama *$namaCustomer*.\n"
                        . "Pada No Pendaftaran : $nopendaftaran\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    if ($nohpAdmin) {
                        $this->kirimWhatsappNotifikasi($nohpAdmin, $pesanAdmin);
                    }
                }
            } catch (\Throwable $notificationError) {
                Log::warning('Survey tersimpan, tetapi notifikasi admin gagal dikirim.', [
                    'registrasifk' => $registrasiFk,
                    'message' => $notificationError->getMessage(),
                ]);
            }

            $transMessage = "Simpan Survey Pelanggan Sukses";
            $result = array(
                "status" => 200,
                "result" => array(
                    "norec" => $model_PD->norec,
                    "survey_saved" => true,
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (\Throwable $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "result"  => $e->getMessage()
            );
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getSurveyPelanggan(Request $r)
    {
        $norecPd = trim((string) $r->input('norec_pd'));
        $registrasi = $norecPd !== ''
            ? DB::table('mitraregistrasi_t')
                ->select('jenisorder', 'iskalibrasiinternal')
                ->where('norec', $norecPd)
                ->where('statusenabled', true)
                ->first()
            : null;
        $isKalibrasiInternal = $this->isInternalCalibrationFlag(
            $registrasi->iskalibrasiinternal ?? false
        );
        $surveyApplicable = $registrasi !== null && !$isKalibrasiInternal;
        $data = collect();

        if ($surveyApplicable) {
            $data = DB::table('surveypelanggan_t as st')
                ->leftJoin('jeniskelamin_m as jk', 'jk.id', '=', 'st.jeniskelamin')
                ->leftJoin('pendidikan_m as pd', 'pd.id', '=', 'st.pendidikan')
                ->select(
                    'st.*',
                    'jk.id as idjeniskelamin',
                    'jk.jeniskelamin',
                    'pd.id as idpendidikan',
                    'pd.pendidikan',
                )
                ->where('st.statusenabled', true)
                ->where('st.registrasifk', $norecPd)
                ->get();

            $surveyIds = $data->pluck('norec')->filter()->values();
            $detailAll = DB::table('surveydetailpelanggan_t')
                ->where('statusenabled', true)
                ->whereIn('surveyfk', $surveyIds)
                ->get()
                ->groupBy('surveyfk');

            foreach ($data as $item) {
                $item->detailSurvey = $detailAll[$item->norec] ?? [];
            }
        }

        $completionColumn = strtolower((string) ($registrasi->jenisorder ?? '')) === 'repair'
            ? 'tglsetujumanagerlaporanrepair'
            : 'tglsetujumanagerlembarkerja';
        $hasCompletedTool = $surveyApplicable && DB::table('mitraregistrasidetail_t')
            ->where('noregistrasifk', $norecPd)
            ->where('statusenabled', true)
            ->whereNotNull($completionColumn)
            ->exists();

        $result['data'] = $data;
        $result['has_completed_tool'] = $hasCompletedTool;
        $result['survey_sudah_diisi'] = $data->isNotEmpty();
        $result['survey_required'] = $surveyApplicable && $hasCompletedTool && $data->isEmpty();
        $result['survey_applicable'] = $surveyApplicable;
        $result['iskalibrasiinternal'] = $isKalibrasiInternal;
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function savePenilaianPelanggan(Request $r)
    {
        DB::beginTransaction();
        try {
            $PD = $r['penilaian'];

            $detailRegistrasi = DB::table('mitraregistrasidetail_t as mtrd')
                ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
                ->select('mtr.norec', 'mtr.iskalibrasiinternal')
                ->where('mtrd.norec', $PD['norec_detail'])
                ->where('mtrd.statusenabled', true)
                ->where('mtr.statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$detailRegistrasi) {
                throw new \Exception('Data alat pendaftaran tidak ditemukan.');
            }

            if ($this->isInternalCalibrationFlag($detailRegistrasi->iskalibrasiinternal ?? false)) {
                throw new \Exception('Penilaian pelanggan tidak berlaku untuk order kalibrasi internal.');
            }

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $PD['norec_detail'])
                ->update([
                    'isireviewalat' => true,
                    'bintangpenilaian' => $PD['bintang'],
                    'ulasanpenilaian' => $PD['ulasan'],
                    'tglisipenilaian' => now(),
                ]);

            DB::commit();

            $customer = DB::table('users')->where('id', $this->getPegawaiId(),)->first();
            $namaCustomer = $customer->name ?? '-';
            $regis = DB::table('mitraregistrasi_t')
                ->where('norec', $PD['norec'])
                ->first();
            $idAdmin = $regis->petugaskaji ?? '-';

            if ($idAdmin) {
                $admin = DB::table('pegawai_m')->where('id', $idAdmin)->first();
                $nohpAdmin = $admin->nohandphone ?? null;
                $namaAdmin = $admin->namalengkap ?? '-';

                $pesanAdmin = "Halo Tim Hebat U-LAB! 👋\n"
                    . "Yth. $namaAdmin,\n\n"
                    . "📢 *Penilaian Layanan Baru dari Customer*\n\n"
                    . "👤 *Nama Customer:* $namaCustomer\n"
                    . "🛠️ *Nomor Order Alat:* {$PD['noorderalat']}\n"
                    . "⭐ *Rating:* " . str_repeat('⭐', (int) $PD['bintang']) . " ({$PD['bintang']} dari 5)\n"
                    . "📝 *Ulasan:*\n"
                    . "{$PD['ulasan']}\n\n"
                    . "Terima kasih atas perhatian Anda.\n"
                    . "Salam,\n"
                    . "U-LAB – Cepat, Tepat, Akurat 💯";

                if ($nohpAdmin) {
                    $this->kirimWhatsappNotifikasi($nohpAdmin, $pesanAdmin);
                }
            }

            $transMessage = "Simpan Penilaian Pelanggan Sukses";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "result"  => $e->getMessage()
            );
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    private function isInternalCalibrationFlag($value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 't', 'yes', 'y', 'on'], true);
    }
}
