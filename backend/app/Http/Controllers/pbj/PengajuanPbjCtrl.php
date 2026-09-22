<?php

namespace App\Http\Controllers\Pbj;


use App\Http\Controllers\Controller;
use App\Models\Transaksi\PengajuanDetailPBJ;
use App\Models\Transaksi\PengajuanPBJ;
use App\Traits\Valet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;

class PengajuanPbjCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    private function pbjRejectionStages(): array
    {
        return [
            2 => ['label' => 'Asman', 'status_field' => 'statusorderasman', 'previous_status' => 0, 'note_field' => 'ketverifasman', 'date_field' => 'tglverifasman', 'verifier_field' => 'asmanveriffk'],
            4 => ['label' => 'Manager', 'status_field' => 'statusordermanager', 'previous_status' => 1, 'note_field' => 'ketverifmanager', 'date_field' => 'tglverifmanager', 'verifier_field' => 'managerveriffk'],
            6 => ['label' => 'Rendal', 'status_field' => 'statusorderrendal', 'previous_status' => 3, 'note_field' => 'ketverifrendal', 'date_field' => 'tglverifrendal', 'verifier_field' => 'rendalveriffk'],
            8 => ['label' => 'Inventory 1', 'status_field' => 'statusordinven1', 'previous_status' => 5, 'note_field' => 'ketverifinven1', 'date_field' => 'tglverifinven1', 'verifier_field' => 'invenveriffk'],
            10 => ['label' => 'Pengadaan', 'status_field' => 'statusorderpengadaan', 'previous_status' => 7, 'note_field' => 'ketverifpengadaan', 'date_field' => 'tglverifpengadaan', 'verifier_field' => 'pengadaanveriffk'],
            12 => ['label' => 'BA 1', 'status_field' => 'statusorderba1', 'previous_status' => 9, 'note_field' => 'ketverifba1', 'date_field' => 'tglverifba1', 'verifier_field' => 'ba1veriffk'],
            14 => ['label' => 'Asman untuk BA 1', 'status_field' => 'statusorderasmanba1', 'previous_status' => 11, 'note_field' => 'ketverifasmanba1', 'date_field' => 'tglverifasmanba1', 'verifier_field' => null],
            16 => ['label' => 'BA 2', 'status_field' => 'statusorderba2', 'previous_status' => 13, 'note_field' => 'ketverifba2', 'date_field' => 'tglverifba2fk', 'verifier_field' => 'ba2veriffk'],
            18 => ['label' => 'Pembayaran', 'status_field' => 'statusorderpembayaran', 'previous_status' => 15, 'note_field' => 'ketverifpembayaran', 'date_field' => 'tglverifpembayaranfk', 'verifier_field' => 'pembayaranveriffk'],
            20 => ['label' => 'Pembebanan', 'status_field' => 'statusorderpembebanan', 'previous_status' => 17, 'note_field' => 'ketverifpembebanan', 'date_field' => 'tglverifpembebananfk', 'verifier_field' => 'pembebananveriffk'],
            22 => ['label' => 'Permintaan Limit', 'status_field' => 'statusorderpermintaanlimit', 'previous_status' => 19, 'note_field' => 'ketverifpermitaanlimit', 'date_field' => 'tglverifpermintaanlimitfk', 'verifier_field' => 'permintaanlimitveriffk'],
        ];
    }

    private function getPbjRejectionStage($pbj): ?array
    {
        if (!$pbj || is_null($pbj->tglpengajuan)) {
            return null;
        }

        $status = (int) ($pbj->statusorder ?? 0);
        $stage = $this->pbjRejectionStages()[$status] ?? null;
        if (!$stage) {
            return null;
        }

        $stage['status'] = $status;
        return $stage;
    }

    private function tentukanKategoriRbk(array $item, ?string $jenisPbj): string
    {
        if (Str::upper(trim((string) $jenisPbj)) === 'MATERIAL') {
            return 'material';
        }

        if (trim((string) ($item['grupmobilisasi'] ?? '')) !== '') {
            return 'mobilisasi';
        }

        $teks = Str::lower(implode(' ', [
            (string) ($item['namaitem'] ?? ''),
            (string) ($item['uraianitem'] ?? ''),
            (string) ($item['judulmobilisasi'] ?? ''),
        ]));

        $penugasanMobilisasiAlat = preg_match('/penugasan/u', $teks)
            && preg_match('/tool|alat|peralatan/u', $teks)
            && preg_match('/kalibrasi|pengambilan|penyerahan|serah\s*terima/u', $teks);

        if (
            preg_match('/mobilisasi|mobilisai|demobilisasi|transportasi|pengantaran|pengiriman|tiket|travel|ojek|bagasi/u', $teks)
            || $penugasanMobilisasiAlat
        ) {
            return 'mobilisasi';
        }

        return 'manpower';
    }

    public function savePengajuanPBJ(Request $r)
    {
        DB::beginTransaction();
        try {
            $APD = json_decode($r->input('mappedOrderPbj'), true) ?: [];
            $isEdit = !empty($r->input('norec'));
            $model_PD = null;

            if (!$isEdit) {
                $model_PD = new PengajuanPBJ();
                $model_PD->norec = $model_PD->generateNewId();
                $model_PD->statusenabled = true;

                $model_PD->statusorder = null;
                $model_PD->statusorderasman = null;
                $model_PD->statusordermanager = null;
                $model_PD->tglpengajuan = null;

                $pemohonId = $r->input('pemohon');
                if (!$pemohonId) {
                    throw new \Exception('Pemohon (dropdown) wajib diisi untuk mendapatkan nomor surat PBJ.');
                }

                $code = DB::table('pemohonpbj_m')
                    ->where('id', $pemohonId)
                    ->value('code');
                $code = strtoupper($code ?? '');

                if ($code === '') {
                    throw new \Exception('Kode pemohon tidak ditemukan pada master pemohonpbj_m.');
                }

                $tahun4 = now('Asia/Jakarta')->format('Y');
                $this->lockNomorTransaksi('nomor-pbj', [$pemohonId, $tahun4]);

                $lastNum = PengajuanPBJ::where('pemohon', $pemohonId)
                    ->whereYear('created_at', $tahun4)
                    ->whereNotNull('nosuratpbj')
                    ->where('nosuratpbj', '!=', '')
                    ->whereRaw("split_part(nosuratpbj,'/',1) ~ '^[0-9]+$'")
                    ->max(DB::raw("CAST(split_part(nosuratpbj, '/', 1) AS INTEGER)"));

                $urut = max(1, ((int) ($lastNum ?? 0)) + 1);
                $urut3digit = str_pad($urut, 3, '0', STR_PAD_LEFT);
                $model_PD->nosuratpbj = $urut3digit . '/' . $code . '/' . $tahun4;
            } else {
                $model_PD = PengajuanPBJ::where('norec', $r->input('norec'))
                    ->where('statusenabled', true)
                    ->lockForUpdate()
                    ->first();

                if (!$model_PD) {
                    throw new \Exception('Data PBJ tidak ditemukan atau sudah nonaktif.');
                }

                if ((string) $model_PD->pegawaifk !== (string) $this->getPegawaiId()) {
                    throw new \Exception('Anda tidak berhak mengedit PBJ ini.');
                }

                $isDraft = is_null($model_PD->tglpengajuan);

                $isRejected = $this->getPbjRejectionStage($model_PD) !== null;

                if (!$isDraft && !$isRejected) {
                    throw new \Exception('PBJ sudah diajukan dan sedang/sudah diproses, tidak bisa diedit lagi.');
                }
            }

            $model_PD->judulpermintaan = $r->input('judulpermintaan') ?? null;
            $model_PD->jenispbj = $r->input('jenispbj') ?? null;

            $kebutuhanMode = $r->input('kebutuhan_mode');
            if ($kebutuhanMode === 'manual') {
                $model_PD->kebutuhan = null;
                $model_PD->kebutuhanmanual = $r->input('kebutuhanmanual') ?? null;
            } else {
                $model_PD->kebutuhan = $r->input('kebutuhan') ?? null;
                $model_PD->kebutuhanmanual = null;
            }

            $pemohonMode = $r->input('pemohon_mode');
            if ($pemohonMode === 'manual') {
                $model_PD->pemohon = null;
                $model_PD->pemohonmanual = $r->input('pemohonmanual') ?? null;
            } else {
                $model_PD->pemohon = $r->input('pemohon') ?? null;
                $model_PD->pemohonmanual = null;
            }

            $model_PD->project = $r->input('project') ?? null;

            $userMode = $r->input('user_mode');
            if ($userMode === 'manual') {
                $model_PD->user = null;
                $model_PD->usermanual = $r->input('user_manual') ?? null;
            } else {
                $model_PD->user = $r->input('user_dd') ?? null;
                $model_PD->usermanual = null;
            }

            $model_PD->noproyek = $r->input('noproyek') ?? null;

            $bidangMode = $r->input('bidang_mode');
            if ($bidangMode === 'manual') {
                $model_PD->bidang = null;
                $model_PD->bidangmanual = $r->input('bidang_manual') ?? null;
            } else {
                $model_PD->bidang = $r->input('bidang_dd') ?? null;
                $model_PD->bidangmanual = null;
            }

            $kepadaMode = $r->input('kepada_mode');
            if ($kepadaMode === 'manual') {
                $model_PD->kepada = null;
                $model_PD->kepadamanual = $r->input('kepada_manual') ?? null;
            } else {
                $model_PD->kepada = $r->input('kepada_dd') ?? null;
                $model_PD->kepadamanual = null;
            }

            $model_PD->pegawaifk = $this->getPegawaiId();

            $dasarAnggaran = null;
            if ($kebutuhanMode !== 'manual' && !empty($model_PD->kebutuhan)) {
                $dasarAnggaran = DB::table('dasaranggaranpbj_m')
                    ->where('id', $model_PD->kebutuhan)
                    ->where('statusenabled', true)
                    ->value('dasar');
            }

            $isAo = Str::upper(trim((string) $dasarAnggaran)) === 'AO';
            if ($isAo) {
                $prkMaster = DB::table('prk_m')
                    ->where('id', $r->input('prkfk'))
                    ->where('statusenabled', true)
                    ->first();

                if (empty($prkMaster)) {
                    throw new \Exception('PRK AO wajib dipilih dari dropdown.');
                }

                $model_PD->prkfk = $prkMaster->id;
                $model_PD->prk = $prkMaster->prk;
            } else {
                $prk = trim((string) $r->input('prk'));
                if ($prk === '') {
                    throw new \Exception('PRK wajib diisi.');
                }

                $model_PD->prkfk = null;
                $model_PD->prk = $prk;
            }
            $model_PD->wo = $r->input('wo') ?? null;
            $model_PD->pengadaan = $r->input('pengadaan') ?? null;
            $model_PD->prioritasfk = $r->input('prioritas') ?? null;
            $model_PD->lokasipbjfk = $r->input('lokasipbj') ?? null;
            $model_PD->costcode = $r->input('costcode') ?? null;
            $model_PD->ppn = $r->input('ppn') ?? null;
            $model_PD->notes = $r->input('notes') ?? null;
            if ($r->has('sinkronrbk')) {
                $model_PD->sinkronrbk = $r->boolean('sinkronrbk');
            } elseif (!$isEdit) {
                $model_PD->sinkronrbk = true;
            }
            $model_PD->save();

            $jenisPbj = DB::table('jenispbj_m')
                ->where('id', $model_PD->jenispbj)
                ->value('jenispbj');

            $this->simpanLampiranIhPbj($r, $model_PD->norec);

            $incomingNorecs = collect($APD)
                ->pluck('norec')
                ->filter(fn($x) => !empty($x))
                ->values()
                ->all();

            $existingDetails = count($incomingNorecs) > 0
                ? PengajuanDetailPBJ::whereIn('norec', $incomingNorecs)
                    ->where('noregpbjfk', $model_PD->norec)
                    ->get()
                    ->keyBy('norec')
                : collect();

            if ($isEdit) {
                PengajuanDetailPBJ::where('noregpbjfk', $model_PD->norec)
                    ->where('statusenabled', true)
                    ->when(count($incomingNorecs) > 0, function ($q) use ($incomingNorecs) {
                        $q->whereNotIn('norec', $incomingNorecs);
                    })
                    ->update([
                        'statusenabled' => false,
                        'updated_at' => now(),
                    ]);
            }

            foreach (array_values($APD) as $index => $alat) {
                $detail = null;
                if (!empty($alat['norec'])) {
                    $detail = $existingDetails->get($alat['norec']);
                }

                if (!$detail) {
                    $detail = new PengajuanDetailPBJ();
                    $detail->norec = $detail->generateNewId();
                }

                $detail->statusenabled = true;
                $detail->urutitem = $index + 1;

                $detail->namaitem = isset($alat['namaitem']) && $alat['namaitem'] !== ''
                    ? $alat['namaitem']
                    : null;

                $detail->uraianitem = isset($alat['uraianitem']) && $alat['uraianitem'] !== ''
                    ? $alat['uraianitem']
                    : null;

                $detail->stockcode = isset($alat['stockcode']) && $alat['stockcode'] !== ''
                    ? $alat['stockcode']
                    : null;

                $detail->banyak = isset($alat['banyak']) && $alat['banyak'] !== ''
                    ? (int) $alat['banyak']
                    : null;

                $detail->satuan = isset($alat['satuan']) && $alat['satuan'] !== ''
                    ? strtoupper(trim($alat['satuan']))
                    : null;

                $detail->hargasatuan = isset($alat['hargasatuan']) && $alat['hargasatuan'] !== ''
                    ? $alat['hargasatuan']
                    : null;

                $detail->keterangan = isset($alat['keterangan']) && $alat['keterangan'] !== ''
                    ? $alat['keterangan']
                    : null;

                $grupMobilisasi = trim((string) ($alat['grupmobilisasi'] ?? ''));
                $judulMobilisasi = trim((string) ($alat['judulmobilisasi'] ?? ''));
                $uraianMobilisasi = trim((string) ($alat['uraianmobilisasi'] ?? ''));
                $keteranganMobilisasi = trim((string) ($alat['keteranganmobilisasi'] ?? ''));

                if ($grupMobilisasi !== '' && $judulMobilisasi === '') {
                    throw new \Exception('Judul head mobilisasi wajib diisi.');
                }

                if (strlen($grupMobilisasi) > 64 || mb_strlen($judulMobilisasi) > 255) {
                    throw new \Exception('Data head mobilisasi melebihi panjang yang diizinkan.');
                }

                if (mb_strlen($uraianMobilisasi) > 5000 || mb_strlen($keteranganMobilisasi) > 5000) {
                    throw new \Exception('Uraian atau keterangan head mobilisasi terlalu panjang.');
                }

                $detail->grupmobilisasi = $grupMobilisasi !== '' ? $grupMobilisasi : null;
                $detail->judulmobilisasi = $grupMobilisasi !== '' ? $judulMobilisasi : null;
                $detail->uraianmobilisasi = $grupMobilisasi !== '' && $uraianMobilisasi !== ''
                    ? $uraianMobilisasi
                    : null;
                $detail->keteranganmobilisasi = $grupMobilisasi !== '' && $keteranganMobilisasi !== ''
                    ? $keteranganMobilisasi
                    : null;

                $kategoriDikirim = strtolower(trim((string) ($alat['kategorirbk'] ?? '')));
                $kategoriTersimpan = strtolower(trim((string) ($detail->kategorirbk ?? '')));
                if (in_array($kategoriDikirim, ['manpower', 'material', 'mobilisasi'], true)) {
                    $detail->kategorirbk = $kategoriDikirim;
                } elseif (in_array($kategoriTersimpan, ['manpower', 'material', 'mobilisasi'], true)) {
                    // Pertahankan koreksi manual di DB ketika form lama tidak mengirim kategorirbk.
                    $detail->kategorirbk = $kategoriTersimpan;
                } else {
                    $detail->kategorirbk = $this->tentukanKategoriRbk($alat, $jenisPbj);
                }

                $detail->noregpbjfk = $model_PD->norec;
                $detail->save();
            }

            DB::commit();

            $transMessage = $isEdit ? "Update Draft / Revisi PBJ Sukses" : "Simpan Draft PBJ Sukses";
            return $this->respond([
                "norec" => $model_PD->norec,
                "nosuratpbj" => $model_PD->nosuratpbj,
            ], 200, $transMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond($e->getMessage(), 400, "Simpan Gagal");
        }
    }

    public function ajukanPengajuanPBJ(Request $r)
    {
        DB::beginTransaction();
        try {
            $norec = $r->input('norec');
            if (!$norec) {
                throw new \Exception('norec wajib diisi.');
            }

            $pbj = PengajuanPBJ::where('norec', $norec)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$pbj) {
                throw new \Exception('Data PBJ tidak ditemukan');
            }

            if ((string) $pbj->pegawaifk !== (string) $this->getPegawaiId()) {
                throw new \Exception('Anda tidak berhak mengajukan ulang PBJ ini.');
            }

            $isDraft = is_null($pbj->tglpengajuan);

            $rejectionStage = $this->getPbjRejectionStage($pbj);
            $isResubmission = $rejectionStage !== null;

            if (!$isDraft && !$isResubmission) {
                throw new \Exception('PBJ ini sedang/sudah diproses dan tidak bisa diajukan ulang.');
            }

            $targetLabel = $rejectionStage['label'] ?? 'Asman';
            $reviewerId = 4;

            if ($isDraft) {
                $pbj->statusorder = 0;
                $pbj->statusorderasman = 0;
                $pbj->statusordermanager = 0;
            } else {
                $verifierField = $rejectionStage['verifier_field'] ?? null;
                $reviewerId = $verifierField ? ($pbj->{$verifierField} ?? null) : 4;

                $pbj->{$rejectionStage['status_field']} = 0;
                $pbj->statusorder = $rejectionStage['previous_status'];
                $pbj->{$rejectionStage['note_field']} = null;
                $pbj->{$rejectionStage['date_field']} = null;
                if ($verifierField) {
                    $pbj->{$verifierField} = null;
                }
            }

            $pbj->tglpengajuan = now('Asia/Jakarta');

            if ($isDraft) {
                $pbj->ketverifasman = null;
                $pbj->tglverifasman = null;
                $pbj->asmanveriffk = null;
            }

            $pbj->save();

            $penyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $asman = $reviewerId
                ? DB::table('pegawai_m')->where('id', $reviewerId)->first()
                : null;
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? $targetLabel;

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Telah dilakukan " . ($isResubmission ? "pengajuan ulang ke $targetLabel" : "pengajuan") . " Permintaan Barang dan Jasa (PBJ) oleh : *$namaPenyelia*\n"
                    . "No Surat: *$pbj->nosuratpbj*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }

            DB::commit();

            return $this->respond([
                'norec' => $pbj->norec,
                'nosuratpbj' => $pbj->nosuratpbj,
            ], 200, $isResubmission ? "Ajukan ulang ke {$targetLabel} sukses" : 'Ajukan ke Asman sukses');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->respond(null, 400, $e->getMessage());
        }
    }

    public function deleteDraftPengajuanPBJ(Request $r)
    {
        DB::beginTransaction();
        try {
            $norec = $r->input('norec');
            if (!$norec) {
                throw new \Exception('norec wajib diisi');
            }

            $pbj = PengajuanPBJ::where('norec', $norec)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$pbj) {
                throw new \Exception('Data PBJ tidak ditemukan.');
            }

            if (!is_null($pbj->tglpengajuan)) {
                throw new \Exception('PBJ sudah diajukan / diproses. Tidak bisa dihapus.');
            }
            $pbj->statusenabled = false;
            $pbj->save();

            PengajuanDetailPBJ::where('noregpbjfk', $pbj->norec)
                ->where('statusenabled', true)
                ->update(['statusenabled' => false]);

            DB::table('ihtpbj_t')
                ->where('noregpbjfk', $pbj->norec)
                ->where('statusenabled', true)
                ->update(['statusenabled' => false, 'updated_at' => now()]);

            DB::commit();
            return $this->respond(['norec' => $norec], 200, 'Hapus Draft PBJ sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond($e->getMessage(), 400, 'Hapus Draft PBJ gagal');
        }
    }

    public function deleteDraftIhtPbj(Request $r)
    {
        DB::beginTransaction();
        try {
            $lampiranNorec = $r->input('norec');
            if (!$lampiranNorec) {
                throw new \Exception('norec lampiran IH wajib diisi.');
            }

            $lampiran = DB::table('ihtpbj_t')
                ->where('norec', $lampiranNorec)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$lampiran) {
                throw new \Exception('Lampiran IH tidak ditemukan atau sudah dihapus.');
            }

            $this->ensureEditableIhtOwner($lampiran->noregpbjfk);

            DB::table('ihtpbj_t')
                ->where('norec', $lampiranNorec)
                ->update([
                    'statusenabled' => false,
                    'updated_at' => now(),
                ]);

            DB::commit();
            $this->deleteIhtFile($lampiran->namafileiht ?? null, $lampiranNorec);
            return $this->respond(['norec' => $lampiranNorec], 200, 'Lampiran IH berhasil dihapus');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->respond(null, 400, $e->getMessage());
        }
    }

    public function replaceDraftIhtPbj(Request $r)
    {
        $newFilePath = null;
        DB::beginTransaction();
        try {
            $lampiranNorec = $r->input('norec');
            if (!$lampiranNorec) {
                throw new \Exception('norec lampiran IH wajib diisi.');
            }

            if (!$r->hasFile('fileIht')) {
                throw new \Exception('File pengganti IH wajib dipilih.');
            }

            $lampiran = DB::table('ihtpbj_t')
                ->where('norec', $lampiranNorec)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$lampiran) {
                throw new \Exception('Lampiran IH tidak ditemukan atau sudah diganti.');
            }

            $this->ensureEditableIhtOwner($lampiran->noregpbjfk);

            $file = $r->file('fileIht');
            if (!$file || !$file->isValid()) {
                throw new \Exception('File pengganti IH tidak valid.');
            }

            $ext = strtolower($file->getClientOriginalExtension() ?? '');
            $signature = @file_get_contents($file->getRealPath(), false, null, 0, 5);
            if ($ext !== 'pdf' || $signature !== '%PDF-') {
                throw new \Exception('File pengganti IH harus berformat PDF.');
            }

            if ((int) $file->getSize() > 10000000) {
                throw new \Exception('Ukuran file pengganti IH maksimal 10 MB.');
            }

            $dest = public_path('berkas-pbj');
            if (!is_dir($dest) && !@mkdir($dest, 0775, true) && !is_dir($dest)) {
                throw new \Exception('Folder penyimpanan lampiran IH tidak dapat dibuat.');
            }

            $filename = 'iht-' . now('Asia/Jakarta')->format('YmdHis') . '-' . Str::lower(Str::random(6)) . '.pdf';
            $file->move($dest, $filename);
            $newFilePath = $dest . DIRECTORY_SEPARATOR . $filename;
            $newNorec = (string) Str::uuid();

            DB::table('ihtpbj_t')
                ->where('norec', $lampiranNorec)
                ->update([
                    'statusenabled' => false,
                    'updated_at' => now(),
                ]);

            $newLampiran = [
                'norec' => $newNorec,
                'statusenabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'namafileiht' => $filename,
                'noregpbjfk' => $lampiran->noregpbjfk,
            ];
            DB::table('ihtpbj_t')->insert($newLampiran);

            DB::commit();
            $this->deleteIhtFile($lampiran->namafileiht ?? null, $lampiranNorec);
            return $this->respond($newLampiran, 200, 'Lampiran IH berhasil diganti');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($newFilePath && is_file($newFilePath)) {
                @unlink($newFilePath);
            }
            return $this->respond(null, 400, $e->getMessage());
        }
    }

    private function ensureEditableIhtOwner($norecPbj)
    {
        $pbj = PengajuanPBJ::where('norec', $norecPbj)
            ->where('statusenabled', true)
            ->lockForUpdate()
            ->first();

        if (!$pbj) {
            throw new \Exception('Data PBJ pemilik lampiran tidak ditemukan.');
        }

        $isDraft = is_null($pbj->tglpengajuan)
            && is_null($pbj->statusorder)
            && is_null($pbj->statusorderasman);
        $isRejected = $this->getPbjRejectionStage($pbj) !== null;
        if (!$isDraft && !$isRejected) {
            throw new \Exception('Lampiran IH hanya bisa dihapus atau diganti saat PBJ draft atau ditolak.');
        }

        if ((string) $pbj->pegawaifk !== (string) $this->getPegawaiId()) {
            throw new \Exception('Anda tidak berhak mengubah lampiran IH PBJ ini.');
        }

        return $pbj;
    }

    private function deleteIhtFile($filename, $lampiranNorec)
    {
        try {
            $path = $this->resolveBerkasPbjPath($filename);
            if (!$path || !is_file($path)) {
                return;
            }

            if (@unlink($path)) {
                return;
            }

            Log::warning('File lampiran IH PBJ gagal dihapus', [
                'norec_lampiran' => $lampiranNorec,
                'namafileiht' => basename((string) $filename),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Penghapusan file lampiran IH PBJ mengalami error', [
                'norec_lampiran' => $lampiranNorec,
                'namafileiht' => basename((string) $filename),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function simpanLampiranIhPbj(Request $r, $norecPbj)
    {
        if (empty($norecPbj) || !$r->hasFile('fileIht')) {
            return 0;
        }

        $files = $this->flattenUploadedFiles($r->file('fileIht'));
        if (count($files) == 0) {
            return 0;
        }

        $dest = public_path('berkas-pbj');
        if (!is_dir($dest)) {
            @mkdir($dest, 0775, true);
        }

        $hashAktif = $this->ambilHashLampiranIhAktif($norecPbj);
        $hashRequest = array();
        $tersimpan = 0;
        $duplikat = 0;
        $lampiranRows = array();

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $ext = strtolower($file->getClientOriginalExtension() ?? '');
            if ($ext !== 'pdf') {
                throw new \Exception('File IHT harus berformat PDF.');
            }

            $hash = $this->hashFileAman($file->getRealPath());
            if ($hash && (isset($hashRequest[$hash]) || isset($hashAktif[$hash]))) {
                $duplikat++;
                continue;
            }

            $timestamp = now('Asia/Jakarta')->format('YmdHis');
            $rand = Str::lower(Str::random(6));
            $filename = "iht-{$timestamp}-{$rand}.pdf";

            $file->move($dest, $filename);

            $lampiranRows[] = [
                'norec' => (string) Str::uuid(),
                'statusenabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'namafileiht' => $filename,
                'noregpbjfk' => $norecPbj,
            ];

            if ($hash) {
                $hashRequest[$hash] = true;
                $hashAktif[$hash] = true;
            }

            $tersimpan++;
        }

        if (!empty($lampiranRows)) {
            DB::table('ihtpbj_t')->insert($lampiranRows);
        }

        if ($duplikat > 0) {
            Log::info('Upload IH PBJ duplikat dilewati', [
                'norec_pbj' => $norecPbj,
                'jumlah_duplikat' => $duplikat,
                'jumlah_tersimpan' => $tersimpan,
            ]);
        }

        return $tersimpan;
    }

    private function flattenUploadedFiles($files)
    {
        if (!is_array($files)) {
            return $files ? array($files) : array();
        }

        $result = array();
        foreach ($files as $file) {
            $result = array_merge($result, $this->flattenUploadedFiles($file));
        }

        return $result;
    }

    private function ambilHashLampiranIhAktif($norecPbj)
    {
        $hashes = array();

        foreach ($this->getLampiranIhPbj($norecPbj) as $lampiranIh) {
            if (!$lampiranIh || empty($lampiranIh->namafileiht)) {
                continue;
            }

            $path = $this->resolveBerkasPbjPath($lampiranIh->namafileiht);
            $hash = $this->hashFileAman($path);

            if ($hash) {
                $hashes[$hash] = true;
            }
        }

        return $hashes;
    }

    public function cetakIht(Request $request)
    {
        $data = DB::table('ihtpbj_t')
            ->where('norec', $request['norec'])
            ->where('statusenabled', true)
            ->first();

        if (!$data || !$data->namafileiht) {
            abort(404, 'Data IHT atau file tidak ditemukan');
        }

        $filename = basename($data->namafileiht);
        $filepath = asset('berkas-pbj/' . $filename);

        return view('report.customer.view-pdf', compact('filepath', 'data'));
    }

    public function getDashboardDataPbj(Request $r)
    {
        $currentYear = (int) now('Asia/Jakarta')->format('Y');
        $year = (int) $r->input('tahun', $currentYear);

        if ($year < 2000 || $year > $currentYear + 5) {
            return $this->respond([], 422, 'Tahun dashboard PBJ tidak valid.');
        }

        $paguTable = config('pbj.dashboard.pagu_table', 'pagumakspbj_m');
        $paguDefinitions = DB::table($paguTable . ' as pagu')
            ->join('prk_m as prkm', 'prkm.id', '=', 'pagu.prkfk')
            ->where('pagu.statusenabled', true)
            ->where('prkm.statusenabled', true)
            ->orderBy('prkm.prk')
            ->get([
                'prkm.prk as code',
                'pagu.detail as label',
                'pagu.pagumaks as pagu',
            ]);
        $specialPrkCodes = $paguDefinitions
            ->pluck('code')
            ->map(fn($code) => Str::upper(trim((string) $code)))
            ->values()
            ->all();

        $priceNumeric = "COALESCE(NULLIF(REGEXP_REPLACE(COALESCE(hargasatuan, ''), '[^0-9-]', '', 'g'), '')::numeric, 0)";
        $detailTotals = DB::table('pengajuandetailpbj_t')
            ->select('noregpbjfk')
            ->selectRaw('COUNT(*) AS total_item')
            ->selectRaw("SUM(COALESCE(banyak, 0) * ($priceNumeric)) AS subtotal")
            ->where('statusenabled', true)
            ->groupBy('noregpbjfk');

        $subtotalSql = 'COALESCE(detail_pbj.subtotal, 0)';
        $ppnSql = "ROUND(($subtotalSql) * COALESCE(ppbj.ppn, 0) / 100.0)";

        $rows = DB::table('pengajuanpbj_t as ppbj')
            ->leftJoinSub($detailTotals, 'detail_pbj', function ($join) {
                $join->on('detail_pbj.noregpbjfk', '=', 'ppbj.norec');
            })
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->leftJoin('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->leftJoin('pemohonpbj_m as pemohon', 'pemohon.id', '=', 'ppbj.pemohon')
            ->leftJoin('pengadaanpbj_m as pengadaan', 'pengadaan.id', '=', 'ppbj.pengadaan')
            ->leftJoin('prioritaspbj_m as prioritas', 'prioritas.id', '=', 'ppbj.prioritasfk')
            ->leftJoin('prk_m as dashboard_prk', 'dashboard_prk.id', '=', 'ppbj.prkfk')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.created_at',
                'ppbj.tanggalpembatalan',
                'ppbj.judulpermintaan',
                'ppbj.prk',
                'dashboard_prk.prk as prk_master',
                'ppbj.ppn',
                'ppbj.statusorder',
                'ppbj.pengadaan as pengadaan_id',
                'ppbj.nopr',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'pg.namalengkap as nama_pengaju',
                'jpbj.jenispbj',
                'pengadaan.pengadaanpbj',
                'prioritas.prioritaspbj',
                DB::raw("COALESCE(NULLIF(BTRIM(ppbj.pemohonmanual), ''), pemohon.pemohonpbj, '-') AS pemohon_label"),
                DB::raw('COALESCE(detail_pbj.total_item, 0) AS total_item'),
                DB::raw("$subtotalSql AS subtotal"),
                DB::raw("$ppnSql AS nilai_ppn"),
                DB::raw("($subtotalSql + $ppnSql) AS total_estimasi"),
                DB::raw('COALESCE(ppbj.tglpengajuan, ppbj.created_at) AS tanggal_acuan')
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusordermanager', 3)
            ->whereRaw(
                'EXTRACT(YEAR FROM COALESCE(ppbj.tglpengajuan, ppbj.created_at)) = ?',
                [$year]
            )
            ->orderByDesc(DB::raw('COALESCE(ppbj.tglpengajuan, ppbj.created_at)'))
            ->get()
            ->map(function ($row) use ($specialPrkCodes) {
                $procurementId = in_array((int) $row->pengadaan_id, [1, 2], true)
                    ? (int) $row->pengadaan_id
                    : 0;
                $prkRaw = trim((string) ($row->prk_master ?? $row->prk ?? ''));

                return [
                    'norec' => trim((string) $row->norec),
                    'nosuratpbj' => $row->nosuratpbj,
                    'tanggal_acuan' => $row->tanggal_acuan,
                    'tglpengajuan' => $row->tglpengajuan,
                    'is_draft' => empty($row->tglpengajuan),
                    'is_cancelled' => !empty($row->tanggalpembatalan),
                    'judulpermintaan' => $row->judulpermintaan,
                    'nama_pengaju' => $row->nama_pengaju,
                    'pemohon' => $row->pemohon_label,
                    'jenispbj' => $row->jenispbj,
                    'pengadaan' => $row->pengadaanpbj,
                    'prioritas' => $row->prioritaspbj,
                    'prk' => $prkRaw !== '' ? $prkRaw : '-',
                    'prk_key' => $this->dashboardPbjPrkKey($prkRaw),
                    'special_prk_code' => $this->dashboardPbjSpecialCode($prkRaw, $specialPrkCodes),
                    'pengadaan_id' => $procurementId,
                    'wilayah_pengadaan' => $procurementId === 1
                        ? 'Jakarta (JP 1 - Barat)'
                        : ($procurementId === 2 ? 'Gresik (JP 2 - Timur)' : 'Belum ditentukan'),
                    'statusorder' => $row->statusorder !== null ? (int) $row->statusorder : null,
                    'status_key' => $this->dashboardPbjStatusKey($row),
                    'status_label' => $this->dashboardPbjStatusLabel($row),
                    'total_item' => (int) $row->total_item,
                    'subtotal' => (float) $row->subtotal,
                    'ppn_persen' => (int) ($row->ppn ?? 0),
                    'nilai_ppn' => (float) $row->nilai_ppn,
                    'total_estimasi' => (float) $row->total_estimasi,
                    'nopr' => $row->nopr,
                    'nopo' => $row->nopo,
                    'nilai_po' => $this->dashboardPbjParseMoney($row->nilaipoppn),
                ];
            });

        $availableYears = DB::table('pengajuanpbj_t')
            ->where('statusenabled', true)
            ->where('statusordermanager', 3)
            ->whereNotNull('created_at')
            ->selectRaw('EXTRACT(YEAR FROM COALESCE(tglpengajuan, created_at))::int AS tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->map(fn($value) => (int) $value)
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values();

        $jakartaRows = $rows->where('pengadaan_id', 1)->values();
        $gresikRows = $rows->where('pengadaan_id', 2)->values();
        $combinedRows = $rows->whereIn('pengadaan_id', [1, 2])->values();
        $unassignedRows = $rows->where('pengadaan_id', 0)->values();

        return $this->respond([
            'year' => $year,
            'available_years' => $availableYears,
            'generated_at' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'locations' => [
                ['id' => 1, 'key' => 'jakarta', 'label' => 'Jakarta', 'pengadaan' => 'JP 1 - BARAT'],
                ['id' => 2, 'key' => 'gresik', 'label' => 'Gresik', 'pengadaan' => 'JP 2 - TIMUR'],
            ],
            'scopes' => [
                'gabungan' => $this->buildDashboardPbjScope($combinedRows),
                'jakarta' => $this->buildDashboardPbjScope($jakartaRows),
                'gresik' => $this->buildDashboardPbjScope($gresikRows),
            ],
            'all_data_summary' => $this->buildDashboardPbjScope($rows)['summary'],
            'data_quality' => [
                'without_pengadaan' => $this->buildDashboardPbjScope($unassignedRows)['summary'],
            ],
            'special_prk' => $this->buildDashboardPbjSpecial($rows, $paguDefinitions),
            'records' => $rows->values(),
        ]);
    }

    private function buildDashboardPbjScope(Collection $rows)
    {
        $totalEstimasi = (float) $rows->sum('total_estimasi');
        $totalPbj = $rows->count();
        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $monthly = collect(range(1, 12))->map(function ($month) use ($rows, $monthLabels) {
            $monthRows = $rows->filter(function ($row) use ($month) {
                return (int) Carbon::parse($row['tanggal_acuan'])->format('n') === $month;
            });

            return [
                'month' => $month,
                'label' => $monthLabels[$month - 1],
                'jumlah_pbj' => $monthRows->count(),
                'total_estimasi' => (float) $monthRows->sum('total_estimasi'),
            ];
        })->values();

        $statusOrder = [
            'draft', 'diajukan', 'asman', 'manager', 'rendal', 'inventory',
            'pengadaan', 'ba1', 'ba2', 'pembayaran', 'pembebanan', 'limit', 'dibatalkan',
        ];
        $statusLabels = [
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'asman' => 'Asman',
            'manager' => 'Manager',
            'rendal' => 'Rendal',
            'inventory' => 'Inventory',
            'pengadaan' => 'Pengadaan',
            'ba1' => 'BA 1',
            'ba2' => 'BA 2',
            'pembayaran' => 'Pembayaran',
            'pembebanan' => 'Pembebanan',
            'limit' => 'Permintaan Limit',
            'dibatalkan' => 'Dibatalkan',
        ];
        $status = collect($statusOrder)->map(function ($key) use ($rows, $statusLabels) {
            $statusRows = $rows->where('status_key', $key);
            return [
                'key' => $key,
                'label' => $statusLabels[$key],
                'jumlah_pbj' => $statusRows->count(),
                'total_estimasi' => (float) $statusRows->sum('total_estimasi'),
            ];
        })->filter(fn($item) => $item['jumlah_pbj'] > 0)->values();

        $otherPrk = $rows
            ->whereNull('special_prk_code')
            ->groupBy('prk_key')
            ->map(function (Collection $prkRows, $key) {
                return [
                    'prk' => $key,
                    'jumlah_pbj' => $prkRows->count(),
                    'total_item' => (int) $prkRows->sum('total_item'),
                    'total_estimasi' => (float) $prkRows->sum('total_estimasi'),
                    'rata_rata' => $prkRows->count() > 0
                        ? round((float) $prkRows->avg('total_estimasi'))
                        : 0,
                ];
            })
            ->sortByDesc('total_estimasi')
            ->values();

        return [
            'summary' => [
                'total_pbj' => $totalPbj,
                'submitted' => $rows->where('is_draft', false)->count(),
                'draft' => $rows->where('is_draft', true)->count(),
                'cancelled' => $rows->where('is_cancelled', true)->count(),
                'total_item' => (int) $rows->sum('total_item'),
                'total_estimasi' => $totalEstimasi,
                'rata_rata_estimasi' => $totalPbj > 0 ? round($totalEstimasi / $totalPbj) : 0,
                'total_prk' => $rows->pluck('prk_key')->filter(fn($value) => $value !== 'TANPA PRK')->unique()->count(),
                'sudah_po' => $rows->filter(fn($row) => trim((string) ($row['nopo'] ?? '')) !== '')->count(),
                'total_nilai_po' => (float) $rows->sum('nilai_po'),
            ],
            'monthly' => $monthly,
            'status' => $status,
            'other_prk' => $otherPrk,
        ];
    }

    private function buildDashboardPbjSpecial(Collection $rows, Collection $paguDefinitions)
    {
        $items = $paguDefinitions->map(function ($definition) use ($rows) {
            $code = Str::upper(trim((string) $definition->code));
            $codeRows = $rows->where('special_prk_code', $code);
            $jakarta = $codeRows->where('pengadaan_id', 1);
            $gresik = $codeRows->where('pengadaan_id', 2);
            $unassigned = $codeRows->where('pengadaan_id', 0);
            $combined = $codeRows->whereIn('pengadaan_id', [1, 2]);
            $total = (float) $combined->sum('total_estimasi');
            $budget = (float) $definition->pagu;
            $percentage = $budget && $budget > 0 ? round(($total / $budget) * 100, 2) : null;

            return [
                'code' => $code,
                'label' => $definition->label,
                'jumlah_pbj' => $combined->count(),
                'total_item' => (int) $combined->sum('total_item'),
                'total_estimasi' => $total,
                'jakarta' => [
                    'jumlah_pbj' => $jakarta->count(),
                    'total_estimasi' => (float) $jakarta->sum('total_estimasi'),
                ],
                'gresik' => [
                    'jumlah_pbj' => $gresik->count(),
                    'total_estimasi' => (float) $gresik->sum('total_estimasi'),
                ],
                'without_pengadaan' => [
                    'jumlah_pbj' => $unassigned->count(),
                    'total_estimasi' => (float) $unassigned->sum('total_estimasi'),
                ],
                'pagu' => $budget,
                'persentase_pagu' => $percentage,
                'sisa_pagu' => $budget !== null ? max(0, $budget - $total) : null,
                'melebihi_pagu' => $budget !== null ? max(0, $total - $budget) : 0,
                'budget_status' => $budget === null
                    ? null
                    : ($percentage > 100 ? 'over' : ($percentage >= 90 ? 'danger' : ($percentage >= 75 ? 'warning' : 'safe'))),
            ];
        })->values();

        return [
            'rule' => 'Kode PRK lengkap: ' . $items->pluck('code')->implode(', '),
            'summary' => [
                'jumlah_pbj' => (int) $items->sum('jumlah_pbj'),
                'total_item' => (int) $items->sum('total_item'),
                'total_estimasi' => (float) $items->sum('total_estimasi'),
            ],
            'items' => $items,
        ];
    }

    private function dashboardPbjSpecialCode($prk, array $validCodes)
    {
        $normalizedPrk = Str::upper(trim((string) $prk));

        foreach ($validCodes as $code) {
            $normalizedCode = Str::upper(trim((string) $code));
            if ($normalizedCode !== '' && preg_match(
                '/^' . preg_quote($normalizedCode, '/') . '(?=$|[\s\/(])/',
                $normalizedPrk
            )) {
                return $normalizedCode;
            }
        }

        return null;
    }

    private function dashboardPbjPrkKey($prk)
    {
        $normalized = Str::upper(trim(preg_replace('/\s+/', ' ', (string) $prk)));
        if ($normalized === '' || $normalized === '-') {
            return 'TANPA PRK';
        }

        if (preg_match('/^[A-Z0-9]+/', $normalized, $matches)) {
            return $matches[0];
        }

        return $normalized;
    }

    private function dashboardPbjStatusKey($row)
    {
        if (!empty($row->tanggalpembatalan)) {
            return 'dibatalkan';
        }
        if (empty($row->tglpengajuan)) {
            return 'draft';
        }

        $status = (int) ($row->statusorder ?? 0);
        if ($status <= 0) return 'diajukan';
        if ($status <= 2) return 'asman';
        if ($status <= 4) return 'manager';
        if ($status <= 6) return 'rendal';
        if ($status <= 8) return 'inventory';
        if ($status <= 10) return 'pengadaan';
        if ($status <= 14) return 'ba1';
        if ($status <= 16) return 'ba2';
        if ($status <= 18) return 'pembayaran';
        if ($status <= 20) return 'pembebanan';
        return 'limit';
    }

    private function dashboardPbjStatusLabel($row)
    {
        $labels = [
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'asman' => 'Verifikasi Asman',
            'manager' => 'Verifikasi Manager',
            'rendal' => 'Verifikasi Rendal',
            'inventory' => 'Proses Inventory',
            'pengadaan' => 'Proses Pengadaan',
            'ba1' => 'Proses BA 1',
            'ba2' => 'Proses BA 2',
            'pembayaran' => 'Proses Pembayaran',
            'pembebanan' => 'Proses Pembebanan',
            'limit' => 'Permintaan Limit',
            'dibatalkan' => 'Dibatalkan',
        ];

        $key = $this->dashboardPbjStatusKey($row);
        return $labels[$key] ?? 'Dalam Proses';
    }

    private function dashboardPbjParseMoney($value)
    {
        $digits = preg_replace('/[^0-9-]/', '', (string) $value);
        return $digits !== '' && is_numeric($digits) ? (float) $digits : 0;
    }

    public function getPengajuanPbj(Request $r)
    {
        $viewerId = $r->input('id');

        $q = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftJoin('lokasikalibrasi_m as lkal', 'lkal.id', '=', 'ppbj.lokasipbjfk')
            ->leftJoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin('prk_m as prkm', 'prkm.id', '=', 'ppbj.prkfk')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.prkfk',
                'prkm.prk as prk_master',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.ppn',
                'ppbj.notes',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'lkal.lokasi as lokasipbj',
                'lkal.id as lokasipbjfk',
                'prpbj.prioritaspbj as prioritaspbj',
                'prpbj.id as prioritaspbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true);

        if (!empty($viewerId) && $viewerId !== 'undefined') {
            $q->where('ppbj.pegawaifk', '=', $viewerId);
        }

        $rows = $q->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->orderByRaw('COALESCE(urutitem, 999999) ASC')
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($rows as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }

        return $this->respond($rows);
    }

    public function getDetailPengajuanPbj(Request $r)
    {
        $norec = $r->query('norec');

        if (!$norec) {
            return $this->respond([
                'timeline' => [],
            ]);
        }

        $row = DB::table('pengajuanpbj_t as ppbj')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->leftJoin('pegawai_m as pg1', 'pg1.id', '=', 'ppbj.asmanveriffk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'ppbj.managerveriffk')
            ->leftJoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftJoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->select(
                'ppbj.tglpengajuan',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.tglpo',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.verifikasiinvoice',
                'ppbj.statusorder',
                'ppbj.nosuratpbj',
                'ppbj.nopr',
                'ppbj.nopo',
                'dbpbj.namadistribusi',
                'spbj.statuspaid',
                'pg.namalengkap as nama_pengaju',
                'pg1.namalengkap as nama_asman',
                'pg2.namalengkap as nama_manager',
                'ppbj.tglverifasman',
                'ppbj.tglverifmanager',
                'ppbj.tglverifrendal',
                'ppbj.tglverifinven1',
                'ppbj.tglverifinven2',
                'ppbj.tglverifpengadaan',
                'ppbj.tglverifba1',
                'ppbj.tglverifasmanba1',
                'ppbj.tglverifba2fk',
                'ppbj.tglverifpembayaranfk',
                'ppbj.tglverifpembebananfk',
                'ppbj.tglverifpermintaanlimitfk',
            )
            ->where('ppbj.norec', $norec)
            ->first();

        if (!$row) {
            return $this->respond([
                'timeline' => [],
            ]);
        }

        $status = (int) ($row->statusorder ?? 0);

        if ($status === 0) {
            $progress_step = 1;
        } elseif ($status === 1 || $status === 2) {
            $progress_step = 2;
        } elseif ($status === 3 || $status === 4) {
            $progress_step = 3;
        } elseif ($status === 5 || $status === 6) {
            $progress_step = 4;
        } elseif ($status === 7 || $status === 8) {
            $progress_step = 5;
        } elseif ($status === 9 || $status === 10) {
            $progress_step = 6;
        } elseif ($status === 11 || $status === 12) {
            $progress_step = 7;
        } elseif ($status === 13 || $status === 14) {
            $progress_step = 8;
        } elseif ($status === 15 || $status === 16) {
            $progress_step = 9;
        } elseif ($status === 17 || $status === 18) {
            $progress_step = 10;
        } elseif ($status === 19 || $status === 20) {
            $progress_step = 11;
        } elseif ($status === 21 || $status === 22) {
            $progress_step = 12;
        } else {
            $progress_step = 1;
        }

        $progress_step = max(1, min(12, $progress_step));

        $steps = [
            1 => [
                'date' => $row->tglpengajuan,
                'type' => 'Diajukan',
                'nama' => $row->nama_pengaju,
            ],
            2 => [
                'date' => $row->tglverifasman,
                'type' => 'Diverifikasi Asman',
                'nama' => $row->nama_asman,
                // 'nama' => '',
            ],
            3 => [
                'date' => $row->tglverifmanager,
                'type' => 'Diverifikasi Manager',
                'nama' => $row->nama_manager,
                // 'nama' => '',
            ],
            4 => [
                'date' => $row->tglverifrendal,
                'type' => 'Diverifikasi Rendal',
                // 'nama' => $row->nopr,
                'nama' => '',
            ],
            5 => [
                'date' => $row->tglverifinven1,
                'type' => 'DIverifikasi Inventory',
                // 'nama' => $row->namadistribusi,
                'nama' => '',
            ],
            6 => [
                'date' => $row->tglverifpengadaan,
                'type' => 'Diverifikasi Pengadaan',
                // 'nama' => $row->nosuratpbj,
                'nama' => '',
            ],
            7 => [
                'date' => $row->tglverifba1,
                'type' => 'Diverifikasi BA 1',
                // 'nama' => $row->nosuratpbj,
                'nama' => '',
            ],
            8 => [
                'date' => $row->tglverifasmanba1,
                'type' => 'Diverifikasi Asman untuk BA 1',
                // 'nama' => $row->nosuratpbj,
                'nama' => '',
            ],
            9 => [
                'date' => $row->tglverifba2fk,
                'type' => 'Diverifikasi BA 2',
                // 'nama' => $row->nopo,
                'nama' => '',
            ],
            10 => [
                'date' => $row->tglverifpembayaranfk,
                'type' => 'Diverifikasi Pembayaran',
                // 'nama' => $row->nosuratpbj,
                'nama' => '',
            ],
            11 => [
                'date' => $row->tglverifpembebananfk,
                'type' => 'Diverifikasi Pembebanan',
                // 'nama' => $row->nosuratpbj,
                'nama' => '',
            ],
            12 => [
                'date' => $row->tglverifpermintaanlimitfk,
                'type' => 'Diverifikasi Permintaan Limit',
                // 'nama' => $row->statuspaid,
                'nama' => '',
            ],
        ];

        $timeline = [];

        for ($i = 1; $i <= $progress_step; $i++) {
            if (!isset($steps[$i])) {
                continue;
            }

            $s = $steps[$i];

            if (empty($s['date'])) {
                continue;
            }

            $timeline[] = [
                'date' => $s['date'],
                'type' => $s['type'],
                'nama' => $s['nama'] ?: '-',
            ];
        }

        return $this->respond([
            'timeline' => $timeline,
        ]);
    }

    private function ringkasMobilisasiUntukCetak(Collection $details): Collection
    {
        $hasil = collect();
        $heads = [];

        $parseHarga = function ($value): float {
            $raw = trim((string) $value);
            if ($raw === '') {
                return 0;
            }

            if (is_numeric($raw)) {
                return (float) $raw;
            }

            $digits = preg_replace('/[^0-9-]/', '', $raw);
            return is_numeric($digits) ? (float) $digits : 0;
        };

        foreach ($details as $detail) {
            $groupKey = trim((string) ($detail->grupmobilisasi ?? ''));

            if ($groupKey === '') {
                $hasil->push($detail);
                continue;
            }

            if (!isset($heads[$groupKey])) {
                $head = clone $detail;
                $head->namaitem = trim((string) ($detail->judulmobilisasi ?? '')) ?: 'Mobilisasi';
                $head->uraianitem = trim((string) ($detail->uraianmobilisasi ?? '')) ?: '-';
                $head->stockcode = '-';
                $head->banyak = 1;
                $head->satuan = 'LOT';
                $head->hargasatuan = 0;
                $head->keterangan = trim((string) ($detail->keteranganmobilisasi ?? '')) ?: '-';
                $head->jumlahsubitem = 0;

                $heads[$groupKey] = $head;
                $hasil->push($head);
            }

            $qty = is_numeric($detail->banyak ?? null) ? (float) $detail->banyak : 0;
            $heads[$groupKey]->hargasatuan += $qty * $parseHarga($detail->hargasatuan ?? 0);
            $heads[$groupKey]->jumlahsubitem += 1;
        }

        return $hasil->values();
    }

    public function cetakPBJ(Request $r)
    {
        $profile = $this->profile();
        $print = false;
        $pageWidth = 950;

        $res['pbj'] = $data = DB::table('pengajuanpbj_t as ppbj')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->leftJoin('pegawai_m as pg1', 'pg1.id', '=', 'ppbj.asmanveriffk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'ppbj.managerveriffk')
            ->leftJoin('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->leftJoin('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->leftJoin('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftJoin('lokasikalibrasi_m as lkal', 'lkal.id', '=', 'ppbj.lokasipbjfk')
            ->leftJoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan2fk')
            ->leftJoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftJoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftJoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin('prk_m as prkm', 'prkm.id', '=', 'ppbj.prkfk')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("COALESCE(NULLIF(TRIM(ppbj.kebutuhanmanual), ''), dspbj.dasar) as kebutuhan"),
                DB::raw("COALESCE(NULLIF(TRIM(ppbj.bidangmanual), ''), mbpbj.managerbidang) as bidang"),
                DB::raw("COALESCE(NULLIF(TRIM(ppbj.kepadamanual), ''), kpbj.kepada) as kepada"),
                DB::raw("COALESCE(NULLIF(TRIM(ppbj.usermanual), ''), mupbj.namaperusahaan) as user"),
                DB::raw("COALESCE(NULLIF(TRIM(ppbj.prk), ''), prkm.prk) as prk"),
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.ppn',
                'ppbj.notes',
                'ppbj.status',
                'ppbj.tglverifasman',
                'ppbj.tglverifmanager',
                'ppbj.statusordermanager',
                'ppbj.statusorderasman',
                'ppbj.nopr',
                'pg.namalengkap',
                'pg.nid as nidpenyelia',
                'pg1.namalengkap as asmanverif',
                'pg1.nid as nidasman',
                'pg2.namalengkap as managerverif',
                'pg2.nid as nidmanager',
                'jpbj.jenispbj',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'lkal.lokasi as lokasipbj',
                'lkal.id as lokasipbjfk',
                'prpbj.prioritaspbj as prioritaspbj',
                'prpbj.id as prioritaspbjfk',
                'jb.namajabatan'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.norec', $r['norec'])
            ->get();

        if (count($res['pbj']) == 0) {
            abort(404, 'Data PBJ tidak ditemukan');
        }

        $namaFileCetak = $this->formatNamaFilePdf($res['pbj'][0]->nosuratpbj ?? null);

        $detailPbj = DB::table('pengajuanpbj_t as ppbj')
            ->join('pengajuandetailpbj_t as pdpbj', 'pdpbj.noregpbjfk', '=', 'ppbj.norec')
            ->select(
                'pdpbj.norec',
                'pdpbj.urutitem',
                'pdpbj.namaitem',
                'pdpbj.uraianitem',
                'pdpbj.stockcode',
                'pdpbj.banyak',
                'pdpbj.satuan',
                'pdpbj.hargasatuan',
                'pdpbj.keterangan',
                'pdpbj.grupmobilisasi',
                'pdpbj.judulmobilisasi',
                'pdpbj.uraianmobilisasi',
                'pdpbj.keteranganmobilisasi'
            )
            ->where('pdpbj.statusenabled', true)
            ->where('ppbj.norec', $r['norec'])
            ->orderByRaw('COALESCE(pdpbj.urutitem, 999999) ASC')
            ->orderBy('pdpbj.created_at', 'ASC')
            ->orderBy('pdpbj.norec', 'ASC')
            ->get();

        // Baris lama tidak memiliki grupmobilisasi dan selalu diteruskan apa adanya.
        // Hanya input mobilisasi baru yang diringkas menjadi satu baris LOT saat cetak.
        $res['pbjdetail'] = $data = $this->ringkasMobilisasiUntukCetak($detailPbj);

        $res['pdf'] = $r['pdf'] ?? null;

        $validateBaseUrl = rtrim(env('PBJ_VALIDATE_FRONTEND_URL', 'https://ulabumro.id'), '/');

        $validateUrlBase = $validateBaseUrl
            . '/validasi?jenis=pbj'
            . '&norec=' . urlencode($res['pbj'][0]->norec);

        $res['validateUrl'] = $validateUrlBase;
        $res['validateUrlPemohon'] = $validateUrlBase . '&ttd=pemohon';
        $res['validateUrlAsman'] = $validateUrlBase . '&ttd=asman';
        $res['validateUrlManager'] = $validateUrlBase . '&ttd=manager';

        $res['ttdPenyelia'] = base64_encode(
            QrCode::format('svg')->size(75)->generate($res['validateUrlPemohon'])
        );

        $res['ttdAsman'] = base64_encode(
            QrCode::format('svg')->size(75)->generate($res['validateUrlAsman'])
        );

        $res['ttdManager'] = base64_encode(
            QrCode::format('svg')->size(75)->generate($res['validateUrlManager'])
        );

        $blade = 'report.pbj.pengajuan-pbj';

        $lampiranIhs = $this->getLampiranIhPbj($res['pbj'][0]->norec);
        $lampiranIhPaths = array();
        $jumlahHalamanLampiranIh = 0;
        $tempLampiranIhPaths = array();

        if ($res['pdf'] == 'true') {
            $lampiranSiapCetak = $this->siapkanLampiranIhPathsUntukCetak(
                $lampiranIhs,
                $res['pbj'][0]->norec
            );

            $lampiranIhPaths = $lampiranSiapCetak['paths'];
            $jumlahHalamanLampiranIh = $lampiranSiapCetak['jumlah_halaman'];
            $tempLampiranIhPaths = $lampiranSiapCetak['temporary_paths'];
        }

        $res['lampiranIh'] = $lampiranIhs;
        $res['lampiranIhs'] = $lampiranIhs;
        $res['lampiranIhPath'] = count($lampiranIhPaths) > 0 ? $lampiranIhPaths[0] : null;
        $res['lampiranIhPaths'] = $lampiranIhPaths;

        if ($res['pdf'] == 'true') {
            $pdfDummy = App::make('dompdf.wrapper');
            $pdfDummy->setpaper('a4', 'landscape');
            $pdfDummy->loadView(
                $blade,
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => null,
                )
            );

            $dompdfDummy = $pdfDummy->getDomPDF();
            $dompdfDummy->render();

            $jumlahHalamanPbj = $dompdfDummy->getCanvas()->get_page_count();
            $jumlahHalaman = $jumlahHalamanPbj + $jumlahHalamanLampiranIh;

            $pdf = App::make('dompdf.wrapper');
            $pdf->setpaper('a4', 'landscape');
            $pdf->loadView(
                $blade,
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => $jumlahHalaman,
                )
            );

            if (count($lampiranIhPaths) == 0) {
                $this->hapusFileSementara($tempLampiranIhPaths);
                return $pdf->stream($namaFileCetak);
            }

            try {
                $pbjPdfBinary = $pdf->output();

                $mergedPdfBinary = $this->gabungkanPdfDenganLampiran(
                    $pbjPdfBinary,
                    $lampiranIhPaths
                );

                return response($mergedPdfBinary, 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="' . $namaFileCetak . '"');
            } catch (\Throwable $e) {
                Log::error('Gagal menggabungkan PDF PBJ dengan lampiran IH', [
                    'norec_pbj' => $res['pbj'][0]->norec ?? null,
                    'nosuratpbj' => $res['pbj'][0]->nosuratpbj ?? null,
                    'lampiran_ih' => array_map('basename', $lampiranIhPaths),
                    'error' => $e->getMessage(),
                ]);

                return $pdf->stream($namaFileCetak);
            } finally {
                $this->hapusFileSementara($tempLampiranIhPaths);
            }
        }

        if (isset($r['storage'])) {
            $res['storage'] = true;

            $pdf = App::make('dompdf.wrapper');
            $pdf->setpaper('a4', 'landscape');
            $pdf->loadView(
                $blade,
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                )
            );

            return $pdf;
        }

        return view(
            $blade,
            compact('profile', 'pageWidth', 'print', 'res')
        );
    }

    private function formatNamaFilePdf($nosurat)
    {
        $nosurat = trim((string) $nosurat);

        if ($nosurat === '') {
            return 'PBJ.pdf';
        }

        $filename = str_replace(array('/', '\\'), '-', $nosurat);
        $filename = preg_replace('/[<>:"|?*\x00-\x1F]/', '-', $filename);
        $filename = preg_replace('/\s+/', ' ', $filename);
        $filename = trim($filename, " .-\t\n\r\0\x0B");

        if ($filename === '') {
            $filename = 'PBJ';
        }

        return $filename . '.pdf';
    }

    private function siapkanLampiranIhPathsUntukCetak($lampiranIhs, $norecPbj)
    {
        $result = array(
            'paths' => array(),
            'jumlah_halaman' => 0,
            'temporary_paths' => array(),
        );

        $seenRealPaths = array();
        $seenHashes = array();

        foreach ($lampiranIhs as $lampiranIh) {
            if (!$lampiranIh || empty($lampiranIh->namafileiht)) {
                continue;
            }

            $lampiranIhPath = $this->resolveBerkasPbjPath($lampiranIh->namafileiht);
            if (
                !$lampiranIhPath ||
                !file_exists($lampiranIhPath) ||
                strtolower(pathinfo($lampiranIhPath, PATHINFO_EXTENSION)) !== 'pdf'
            ) {
                Log::warning('Lampiran IH PBJ tidak ditemukan saat cetak PBJ', [
                    'norec_pbj' => $norecPbj,
                    'namafileiht' => $lampiranIh->namafileiht ?? null,
                ]);

                continue;
            }

            $realPath = realpath($lampiranIhPath) ?: $lampiranIhPath;
            if (isset($seenRealPaths[$realPath])) {
                continue;
            }

            $hash = $this->hashFileAman($lampiranIhPath);
            if ($hash && isset($seenHashes[$hash])) {
                Log::info('Lampiran IH PBJ duplikat dilewati saat cetak PBJ', [
                    'norec_pbj' => $norecPbj,
                    'namafileiht' => basename($lampiranIhPath),
                ]);

                continue;
            }

            $seenRealPaths[$realPath] = true;
            if ($hash) {
                $seenHashes[$hash] = true;
            }

            $prepared = $this->siapkanPdfUntukFpdi($lampiranIhPath, $norecPbj);
            if (!$prepared) {
                continue;
            }

            $result['paths'][] = $prepared['path'];
            $result['jumlah_halaman'] += $prepared['jumlah_halaman'];

            if (!empty($prepared['temporary'])) {
                $result['temporary_paths'][] = $prepared['path'];
            }
        }

        return $result;
    }

    private function siapkanPdfUntukFpdi($pdfPath, $norecPbj)
    {
        $jumlahHalaman = $this->hitungJumlahHalamanPdf($pdfPath);
        if ($jumlahHalaman > 0) {
            return array(
                'path' => $pdfPath,
                'jumlah_halaman' => $jumlahHalaman,
                'temporary' => false,
            );
        }

        $normalized = $this->normalisasiPdfUntukFpdi($pdfPath, $norecPbj);
        if ($normalized) {
            return $normalized;
        }

        Log::warning('Lampiran IH PBJ dilewati karena PDF tidak bisa dibaca FPDI', [
            'norec_pbj' => $norecPbj,
            'namafileiht' => basename($pdfPath),
        ]);

        return null;
    }

    private function normalisasiPdfUntukFpdi($pdfPath, $norecPbj)
    {
        $candidates = array(
            array('type' => 'qpdf', 'binary' => 'qpdf'),
            array('type' => 'mutool', 'binary' => 'mutool'),
            array('type' => 'gs', 'binary' => 'gswin64c'),
            array('type' => 'gs', 'binary' => 'gswin32c'),
            array('type' => 'gs', 'binary' => 'gs'),
        );

        foreach ($candidates as $candidate) {
            if (!$this->commandExists($candidate['binary'])) {
                continue;
            }

            $targetPath = $this->buatTempPdfPath('ih_norm_');
            $command = $this->buildPdfNormalizerCommand(
                $candidate['type'],
                $candidate['binary'],
                $pdfPath,
                $targetPath
            );

            if (!$command) {
                $this->hapusFileSementara(array($targetPath));
                continue;
            }

            try {
                $process = new Process($command);
                $process->setTimeout(90);
                $process->run();

                if (!$process->isSuccessful()) {
                    Log::warning('Normalisasi PDF IH PBJ gagal', [
                        'norec_pbj' => $norecPbj,
                        'normalizer' => $candidate['binary'],
                        'namafileiht' => basename($pdfPath),
                        'error' => trim($process->getErrorOutput()),
                    ]);

                    $this->hapusFileSementara(array($targetPath));
                    continue;
                }

                $jumlahHalaman = $this->hitungJumlahHalamanPdf($targetPath);
                if ($jumlahHalaman > 0) {
                    Log::info('Normalisasi PDF IH PBJ berhasil', [
                        'norec_pbj' => $norecPbj,
                        'normalizer' => $candidate['binary'],
                        'namafileiht' => basename($pdfPath),
                    ]);

                    return array(
                        'path' => $targetPath,
                        'jumlah_halaman' => $jumlahHalaman,
                        'temporary' => true,
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Normalisasi PDF IH PBJ error', [
                    'norec_pbj' => $norecPbj,
                    'normalizer' => $candidate['binary'],
                    'namafileiht' => basename($pdfPath),
                    'error' => $e->getMessage(),
                ]);
            }

            $this->hapusFileSementara(array($targetPath));
        }

        return null;
    }

    private function buildPdfNormalizerCommand($type, $binary, $sourcePath, $targetPath)
    {
        if ($type === 'qpdf') {
            return array(
                $binary,
                '--object-streams=disable',
                '--stream-data=uncompress',
                '--force-version=1.4',
                $sourcePath,
                $targetPath,
            );
        }

        if ($type === 'mutool') {
            return array(
                $binary,
                'clean',
                '-gg',
                $sourcePath,
                $targetPath,
            );
        }

        if ($type === 'gs') {
            return array(
                $binary,
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dNOPAUSE',
                '-dBATCH',
                '-dSAFER',
                '-sOutputFile=' . $targetPath,
                $sourcePath,
            );
        }

        return null;
    }

    private function commandExists($command)
    {
        static $cache = array();

        if (array_key_exists($command, $cache)) {
            return $cache[$command];
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $checker = $isWindows ? array('where', $command) : array('which', $command);

        try {
            $process = new Process($checker);
            $process->setTimeout(5);
            $process->run();
            $cache[$command] = $process->isSuccessful();
        } catch (\Throwable $e) {
            $cache[$command] = false;
        }

        return $cache[$command];
    }

    private function buatTempPdfPath($prefix)
    {
        $tempPath = tempnam(sys_get_temp_dir(), $prefix);
        if ($tempPath === false) {
            return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . Str::lower(Str::random(12)) . '.pdf';
        }

        $pdfPath = $tempPath . '.pdf';
        @unlink($tempPath);

        return $pdfPath;
    }

    private function hashFileAman($path)
    {
        if (empty($path) || !is_string($path) || !file_exists($path) || !is_file($path)) {
            return null;
        }

        try {
            $hash = hash_file('sha256', $path);
            return $hash ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function hapusFileSementara($paths)
    {
        if (empty($paths)) {
            return;
        }

        if (is_string($paths)) {
            $paths = array($paths);
        }

        $tempDir = realpath(sys_get_temp_dir());
        foreach ($paths as $path) {
            if (empty($path) || !file_exists($path)) {
                continue;
            }

            $realPath = realpath($path);
            if (!$realPath || !$tempDir) {
                continue;
            }

            if (stripos($realPath, $tempDir) === 0) {
                @unlink($realPath);
            }
        }
    }

    private function getLampiranIhPbj($norecPbj)
    {
        if (empty($norecPbj)) {
            return collect();
        }

        if (!Schema::hasTable('ihtpbj_t')) {
            return collect();
        }

        if (!Schema::hasColumn('ihtpbj_t', 'namafileiht')) {
            return collect();
        }

        $possibleRelationColumns = array(
            'noregpbjfk',
            'pengajuanpbjfk',
            'pbjfk',
            'norecpbj',
            'norec_pbj',
            'norec'
        );

        $existingRelationColumns = array();

        foreach ($possibleRelationColumns as $column) {
            if (Schema::hasColumn('ihtpbj_t', $column)) {
                $existingRelationColumns[] = $column;
            }
        }

        if (count($existingRelationColumns) == 0) {
            return collect();
        }

        $query = DB::table('ihtpbj_t')
            ->whereNotNull('namafileiht')
            ->where('namafileiht', '<>', '');

        if (Schema::hasColumn('ihtpbj_t', 'statusenabled')) {
            $query->where('statusenabled', true);
        }

        $query->where(function ($q) use ($existingRelationColumns, $norecPbj) {
            foreach ($existingRelationColumns as $index => $column) {
                if ($index == 0) {
                    $q->where($column, $norecPbj);
                } else {
                    $q->orWhere($column, $norecPbj);
                }
            }
        });

        if (Schema::hasColumn('ihtpbj_t', 'created_at')) {
            $query->orderBy('created_at', 'ASC');
        }

        if (Schema::hasColumn('ihtpbj_t', 'id')) {
            $query->orderBy('id', 'ASC');
        } elseif (Schema::hasColumn('ihtpbj_t', 'norec')) {
            $query->orderBy('norec', 'ASC');
        }

        return $query->get();
    }

    private function resolveBerkasPbjPath($filename)
    {
        if (empty($filename)) {
            return null;
        }

        $filename = basename($filename);

        $paths = array(
            public_path('berkas-pbj/' . $filename),
            storage_path('app/public/berkas-pbj/' . $filename),
            storage_path('app/berkas-pbj/' . $filename),
        );

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function hitungJumlahHalamanPdf($pdfPath)
    {
        if (empty($pdfPath) || !file_exists($pdfPath)) {
            return 0;
        }

        try {
            $fpdi = new Fpdi();
            return $fpdi->setSourceFile($pdfPath);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function gabungkanPdfDenganLampiran($pbjPdfBinary, $lampiranPaths)
    {
        if (empty($pbjPdfBinary)) {
            throw new \Exception('PDF PBJ kosong.');
        }

        if (is_string($lampiranPaths)) {
            $lampiranPaths = array($lampiranPaths);
        }

        if (!is_array($lampiranPaths) || count($lampiranPaths) == 0) {
            throw new \Exception('File lampiran IH tidak ditemukan.');
        }

        $validLampiranPaths = $this->filterPdfPathsUnik($lampiranPaths);

        if (count($validLampiranPaths) == 0) {
            throw new \Exception('Tidak ada lampiran IH PDF yang valid.');
        }

        $tempPbjPath = $this->buatTempPdfPath('pbj_');
        if (file_put_contents($tempPbjPath, $pbjPdfBinary) === false) {
            throw new \Exception('Gagal membuat file sementara PDF PBJ.');
        }

        $pdf = new Fpdi();

        try {
            $allPdfPaths = array_merge(array($tempPbjPath), $validLampiranPaths);

            foreach ($allPdfPaths as $index => $pdfPath) {
                try {
                    $pageCount = $pdf->setSourceFile($pdfPath);

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);

                        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

                        $pdf->AddPage($orientation, array($size['width'], $size['height']));
                        $pdf->useTemplate($templateId);
                    }
                } catch (\Throwable $e) {
                    if ($index == 0) {
                        throw $e;
                    }

                    Log::warning('Lampiran IH PBJ dilewati saat proses merge', [
                        'namafileiht' => basename($pdfPath),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $pdf->Output('S');
        } finally {
            $this->hapusFileSementara(array($tempPbjPath));
        }
    }

    private function filterPdfPathsUnik($paths)
    {
        if (is_string($paths)) {
            $paths = array($paths);
        }

        if (!is_array($paths)) {
            return array();
        }

        $validPaths = array();
        $seenRealPaths = array();
        $seenHashes = array();

        foreach ($paths as $path) {
            if (
                empty($path) ||
                !file_exists($path) ||
                strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf'
            ) {
                continue;
            }

            $realPath = realpath($path) ?: $path;
            if (isset($seenRealPaths[$realPath])) {
                continue;
            }

            $hash = $this->hashFileAman($path);
            if ($hash && isset($seenHashes[$hash])) {
                continue;
            }

            $seenRealPaths[$realPath] = true;
            if ($hash) {
                $seenHashes[$hash] = true;
            }

            $validPaths[] = $path;
        }

        return $validPaths;
    }

    public function getPengajuanPbjAsman(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.notes',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'prpbj.prioritaspbj',
                'prpbj.id as prioritaspbjfk'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderasman', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function getRiwayatPbjAsman(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.notes',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'prpbj.prioritaspbj',
                'prpbj.id as prioritaspbjfk'

            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderasman', '!=', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }

        return $this->respond($data);
    }

    public function saveApprovalPbjAsman(Request $request)
    {
        DB::beginTransaction();
        try {
            $statusAsman = (int) ($request['statusorderasman'] ?? 0);
            $ketAsman = $request['ketverifasman'] ?? null;
            $norec = $request['norec'] ?? null;

            if (!$norec) {
                throw new \Exception('norec wajib diisi');
            }

            $pbj = PengajuanPBJ::where('norec', $norec)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$pbj) {
                throw new \Exception('Data PBJ tidak ditemukan');
            }

            $pbj->statusorderasman = $statusAsman;
            $pbj->statusorder = $statusAsman;
            $pbj->ketverifasman = $ketAsman;
            $pbj->tglverifasman = now();
            $pbj->asmanveriffk = $this->getPegawaiId();
            $pbj->save();

            $isApprove = $statusAsman === 1;
            $aksiText = $isApprove ? 'persetujuan' : 'penolakan';

            $nosuratpbj = $pbj->nosuratpbj ?? '-';

            $asman = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaAsman = $asman->namalengkap ?? '-';

            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            // notif ke pengaju
            $penyeliaId = $pbj->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';

                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Asman NMW : *$namaAsman*\n"
                        . "Keterangan Asman : *" . ($ketAsman ?: '-') . "*\n"
                        . "Pada $waktuVerifStr\n\n";

                    if (!$isApprove) {
                        $pesanPenyelia .= "Silakan lakukan revisi pada aplikasi, lalu ajukan ulang PBJ tersebut.\n\n";
                    }

                    $pesanPenyelia .= "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            // notif ke manager HANYA jika approve
            if ($isApprove) {
                $manager = DB::table('pegawai_m')->where('id', 3)->first();
                $nohpManager = $manager->nohandphone ?? null;
                $namaManager = $manager->namalengkap ?? '-';

                if ($nohpManager) {
                    $pesanManager = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaManager\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan persetujuan oleh Asman NMW : *$namaAsman*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Silahkan cek dan review Permintaan Barang dan Jasa (PBJ) pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    $this->kirimWhatsappNotifikasi($nohpManager, $pesanManager);
                }
            }

            DB::commit();

            return $this->respond([
                "norec" => $pbj->norec,
                "nosuratpbj" => $pbj->nosuratpbj,
            ], 200, "Sukses Approval");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond($e->getMessage(), 400, "Approval Gagal");
        }
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

    public function getPengajuanPbjManager(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.notes',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'prpbj.prioritaspbj',
                'prpbj.id as prioritaspbjfk'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderasman', 1)
            ->where('ppbj.statusordermanager', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');
        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function getRiwayatPbjManager(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.notes',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'prpbj.prioritaspbj',
                'prpbj.id as prioritaspbjfk'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusordermanager', '!=', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');
        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }

        return $this->respond($data);
    }

    public function saveApprovalPbjManager(Request $request)
    {
        DB::beginTransaction();
        try {

            PengajuanPBJ::where('norec', $request['norec'])->update([
                'statusordermanager' => $request['statusordermanager'],
                'statusorderrendal' => 0,
                'statusorder' => $request['statusordermanager'],
                'ketverifmanager' => $request['ketverifmanager'],
                'tglverifmanager' => now(),
                'managerveriffk' => $this->getPegawaiId(),
            ]);

            $aksiText = (int)($request['statusordermanager'] ?? 0) === 3 ? 'persetujuan' : 'penolakan';
            $ketManager = $request['ketverifmanager'];
            $detail = DB::table('pengajuanpbj_t')->where('norec', $request['norec'])->first();
            $nosuratpbj = $detail->nosuratpbj ?? '-';
            $manager = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaManager = $manager->namalengkap ?? '-';
            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $penyeliaId = $detail->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Manager Repair Unit Maintenance Repair dan Overhaul : *$namaManager*\n"
                        . "Keterangan Manager : *$ketManager*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $asman = DB::table('pegawai_m')->where('id', 4)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Manager Repair Unit Maintenance Repair dan Overhaul : *$namaManager*\n"
                    . "Keterangan Manager : *$ketManager*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }

            $pegawaiPBJlList = DB::table('pegawai_m')
                ->where('objectjenispegawaifk', 4)
                ->get();

            foreach ($pegawaiPBJlList as $rowPegawaiPBJ) {
                $nohpPegawaiPbjAll  = $rowPegawaiPBJ->nohandphone ?? null;
                $namaPegawaiPbjAll  = $rowPegawaiPBJ->namalengkap ?? '-';
                if ($nohpPegawaiPbjAll) {
                    $pesanPegawaiPbjlAll = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPegawaiPbjAll\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Manager Repair Unit Maintenance Repair dan Overhaul : *$namaManager*\n"
                        . "Keterangan Manager : *$ketManager*\n"
                        . "Mohon untuk dilihat dan dilakuakan proses verifikasi\n\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPegawaiPbjAll, $pesanPegawaiPbjlAll);
                }
            }

            DB::commit();
            $transMessage = "Sukses Approval";

            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Approval Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getPengajuanPbjRendal(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'prpbj.prioritaspbj',
                'prpbj.id as prioritaspbjfk'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusordermanager', 3)
            ->where('ppbj.statusorderrendal', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function getRiwayatPbjRendal(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'prpbj.prioritaspbj',
                'prpbj.id as prioritaspbjfk'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderrendal', '!=', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }

        return $this->respond($data);
    }

    private function mergeSuperAdminOptionalApprovalFields(Request $request, array $baseUpdates, array $optionalFields): array
    {
        $partialUpdate = $request->boolean('superadmin_partial');

        foreach ($optionalFields as $column => $requestKey) {
            if (!$partialUpdate || $request->exists($requestKey)) {
                $baseUpdates[$column] = $request->input($requestKey);
            }
        }

        return $baseUpdates;
    }

    public function saveApprovalPbjRendal(Request $request)
    {
        DB::beginTransaction();
        try {

            $updates = $this->mergeSuperAdminOptionalApprovalFields($request, [
                'statusorderrendal' => $request['statusorderrendal'],
                'statusorder' => $request['statusorderrendal'],
                'statusordinven1' => 0,
                'tglverifrendal' => now(),
                'tglpr' => now(),
                'rendalveriffk' => $this->getPegawaiId(),
            ], [
                'ketverifrendal' => 'ketverifrendal',
                'nopr' => 'nopr',
                'tglpembuatanpr' => 'tglpembuatanpr',
            ]);
            PengajuanPBJ::where('norec', $request['norec'])->update($updates);

            $aksiText = (int)($request['statusorderrendal'] ?? 0) === 5 ? 'persetujuan' : 'penolakan';
            $ketRendal = $request['ketverifrendal'];
            $noPr = $request['nopr'];
            $detail = DB::table('pengajuanpbj_t')->where('norec', $request['norec'])->first();
            $nosuratpbj = $detail->nosuratpbj ?? '-';
            $Rendal = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaRendal = $Rendal->namalengkap ?? '-';
            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $penyeliaId = $detail->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Rendal : *$namaRendal*\n"
                        . "No PR : *$noPr*\n"
                        . "Keterangan Rendal : *$ketRendal*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $asman = DB::table('pegawai_m')->where('id', 4)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Rendal : *$namaRendal*\n"
                    . "No PR : *$noPr*\n"
                    . "Keterangan Rendal : *$ketRendal*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }

            $manager = DB::table('pegawai_m')->where('id', 3)->first();
            $nohpManager = $manager->nohandphone ?? null;
            $namaManager = $manager->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanManager = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaManager\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Rendal: *$namaRendal*\n"
                    . "No PR : *$noPr*\n"
                    . "Keterangan Rendal : *$ketRendal*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpManager, $pesanManager);
            }

            $pegawaiPBJlList = DB::table('pegawai_m')
                ->where('objectjenispegawaifk', 5)
                ->get();

            foreach ($pegawaiPBJlList as $rowPegawaiPBJ) {
                $nohpPegawaiPbjAll  = $rowPegawaiPBJ->nohandphone ?? null;
                $namaPegawaiPbjAll  = $rowPegawaiPBJ->namalengkap ?? '-';
                if ($nohpPegawaiPbjAll) {
                    $pesanPegawaiPbjlAll = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPegawaiPbjAll\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Rendal : *$namaRendal*\n"
                        . "No PR : *$noPr*\n"
                        . "Keterangan Rendal : *$ketRendal*\n"
                        . "Mohon untuk dilihat dan dilakuakan proses verifikasi\n\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPegawaiPbjAll, $pesanPegawaiPbjlAll);
                }
            }

            DB::commit();
            $transMessage = "Sukses Approval";

            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Approval Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getPengajuanPbjInven1(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'prpbj.prioritaspbj',
                'prpbj.id as prioritaspbjfk'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderrendal', 5)
            ->where('ppbj.statusordinven1', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function getRiwayatPbjInven1(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'prpbj.prioritaspbj',
                'prpbj.id as prioritaspbjfk'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusordinven1', '!=', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }
        return $this->respond($data);
    }

    public function saveApprovalPbjInven1(Request $request)
    {
        DB::beginTransaction();
        try {

            $updates = $this->mergeSuperAdminOptionalApprovalFields($request, [
                'statusordinven1' => $request['statusordinven1'],
                'statusorder' => $request['statusordinven1'],
                'statusorderpengadaan' => 0,
                'tglverifinven1' => now(),
                'invenveriffk' => $this->getPegawaiId(),
            ], [
                'ketverifinven1' => 'ketverifinven1',
                'tglpenerimaanberkaspr' => 'tglpenerimaanberkaspr',
                'tglpenerimaanro' => 'tglpenerimaanro',
                'tglpenerimaanhpe' => 'tglpenerimaanhpe',
                'tglpenerimaanrks' => 'tglpenerimaanrks',
                'tglpenyerahandokumen' => 'tglpenyerahandokumen',
            ]);
            PengajuanPBJ::where('norec', $request['norec'])->update($updates);

            $aksiText = (int)($request['statusordinven1'] ?? 0) === 7 ? 'persetujuan' : 'penolakan';
            $ketInven1 = $request['ketverifinven1'];
            $detail = DB::table('pengajuanpbj_t')->where('norec', $request['norec'])->first();
            $nosuratpbj = $detail->nosuratpbj ?? '-';
            $inven1 = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaInven1 = $inven1->namalengkap ?? '-';
            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $penyeliaId = $detail->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Inventory 1 : *$namaInven1*\n"
                        . "Dengan keterngan $ketInven1"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $asman = DB::table('pegawai_m')->where('id', 4)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Inventory 1 : *$namaInven1*\n"
                    . "Dengan keterngan $ketInven1"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }

            $manager = DB::table('pegawai_m')->where('id', 3)->first();
            $nohpManager = $manager->nohandphone ?? null;
            $namaManager = $manager->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanManager = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaManager\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Inventory 1 : *$namaInven1*\n"
                    . "Dengan keterngan $ketInven1"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpManager, $pesanManager);
            }

            $pegawaiPBJlList = DB::table('pegawai_m')
                ->where('objectjenispegawaifk', 6)
                ->get();

            foreach ($pegawaiPBJlList as $rowPegawaiPBJ) {
                $nohpPegawaiPbjAll  = $rowPegawaiPBJ->nohandphone ?? null;
                $namaPegawaiPbjAll  = $rowPegawaiPBJ->namalengkap ?? '-';
                if ($nohpPegawaiPbjAll) {
                    $pesanPegawaiPbjlAll = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPegawaiPbjAll\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Inventory 1 : *$namaInven1*\n"
                        . "Keterangan Inventory 1 : *$ketInven1*\n"
                        . "Mohon untuk dilihat dan dilakuakan proses verifikasi\n\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPegawaiPbjAll, $pesanPegawaiPbjlAll);
                }
            }

            DB::commit();
            $transMessage = "Sukses Approval";

            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Approval Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }




    public function getPengajuanPbjBa1(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderpengadaan', 9)
            ->where('ppbj.statusorderba1', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function getRiwayatPbjBa1(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderba1', '!=', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }

        return $this->respond($data);
    }

    public function saveApprovalPbjBa1(Request $request)
    {
        DB::beginTransaction();
        try {

            $updates = $this->mergeSuperAdminOptionalApprovalFields($request, [
                'statusorderba1' => $request['statusorderba1'],
                'statusorder' => $request['statusorderba1'],
                'statusorderasmanba1' => 0,
                'tglverifba1' => now(),
                'ba1veriffk' => $this->getPegawaiId(),
            ], [
                'ketverifba1' => 'ketverifba1',
                'sppp' => 'sppp',
                'tglmulairealisasi' => 'tglmulairealisasi',
                'tglselesairealisasi' => 'tglselesairealisasi',
                'dendahari' => 'dendahari',
            ]);
            PengajuanPBJ::where('norec', $request['norec'])->update($updates);

            $aksiText = (int)($request['statusorderba1'] ?? 0) === 11 ? 'persetujuan' : 'penolakan';
            $ketBa1 = $request['ketverifba1'];
            $detail = DB::table('pengajuanpbj_t')->where('norec', $request['norec'])->first();
            $nosuratpbj = $detail->nosuratpbj ?? '-';
            $ba1 = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaBa1 = $ba1->namalengkap ?? '-';
            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $penyeliaId = $detail->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh BA 1 : *$namaBa1*\n"
                        . "Dengan keterngan $ketBa1"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $asman = DB::table('pegawai_m')->where('id', 4)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh BA 1 : *$namaBa1*\n"
                    . "Dengan keterngan $ketBa1"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }

            $manager = DB::table('pegawai_m')->where('id', 3)->first();
            $nohpManager = $manager->nohandphone ?? null;
            $namaManager = $manager->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanManager = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaManager\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh BA 1 : *$namaBa1*\n"
                    . "Dengan keterngan $ketBa1"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpManager, $pesanManager);
            }

            DB::commit();
            $transMessage = "Sukses Approval";

            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Approval Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getPengajuanPbjAsmanBa1(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderasmanba1', 0)
            ->where('ppbj.statusorderba1', 11);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function saveApprovalPbjAsmanBa1(Request $request)
    {
        DB::beginTransaction();
        try {

            PengajuanPBJ::where('norec', $request['norec'])->update([
                'statusorderasmanba1' => $request['statusorderasmanba1'],
                'statusorder' => $request['statusorderasmanba1'],
                'statusorderba2' => 0,
                'ketverifasmanba1' => $request['ketverifasmanba1'],
                'nilaisla' => $request['nilai_sla'],
                'konfirmasivalidasirendalproyek' => $request['konfirmasi_rendal'],
                'statusdarikatimpro' => $request['status_katimpro'],
                'tglverifasmanba1' => now(),
            ]);

            $aksiText = (int)($request['statusorderasmanba1'] ?? 0) === 13 ? 'persetujuan' : 'penolakan';
            $ketAsmanBa1 = $request['ketverifasmanba1'];
            $detail = DB::table('pengajuanpbj_t')->where('norec', $request['norec'])->first();
            $nosuratpbj = $detail->nosuratpbj ?? '-';
            $asmanBa1 = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaAsmanBa1 = $asmanBa1->namalengkap ?? '-';
            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $penyeliaId = $detail->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Asman NMW Untuk BA 1 : *$namaAsmanBa1*\n"
                        . "Keterangan Asman : *$ketAsmanBa1*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $pegawaiPBJlList = DB::table('pegawai_m')
                ->where('objectjenispegawaifk', 9)
                ->get();

            foreach ($pegawaiPBJlList as $rowPegawaiPBJ) {
                $nohpPegawaiPbjAll  = $rowPegawaiPBJ->nohandphone ?? null;
                $namaPegawaiPbjAll  = $rowPegawaiPBJ->namalengkap ?? '-';
                if ($nohpPegawaiPbjAll) {
                    $pesanPegawaiPbjlAll = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPegawaiPbjAll\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Asman NMW Untuk BA 2 : *$namaAsmanBa1*\n"
                        . "Keterangan Asman NMW Untuk BA 2 : *$ketAsmanBa1*\n"
                        . "Mohon untuk dilihat dan dilakuakan proses verifikasi\n\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPegawaiPbjAll, $pesanPegawaiPbjlAll);
                }
            }


            DB::commit();
            $transMessage = "Sukses Approval";

            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Approval Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getPengajuanPbjBa2(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'ppbj.statusorderba2',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderasmanba1', 13)
            ->where('ppbj.statusorderba2', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function getRiwayatPbjBa2(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'dbpbj.namadistribusi',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderba2', '!=', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }
        return $this->respond($data);
    }

    public function saveApprovalPbjBa2(Request $request)
    {
        DB::beginTransaction();
        try {

            $updates = $this->mergeSuperAdminOptionalApprovalFields($request, [
                'statusorderba2' => $request['statusorderba2'],
                'statusorder' => $request['statusorderba2'],
                'statusorderpembayaran' => 0,
                'tglverifba2fk' => now(),
                'ba2veriffk' => $this->getPegawaiId(),
            ], [
                'ketverifba2' => 'ketverifba2',
                'noba' => 'noba',
                'posisiba' => 'posisiba',
                'statusba' => 'statusba',
            ]);
            PengajuanPBJ::where('norec', $request['norec'])->update($updates);

            $aksiText = (int)($request['statusorderba2'] ?? 0) === 15 ? 'persetujuan' : 'penolakan';
            $ketBa2 = $request['ketverifba2'];
            $detail = DB::table('pengajuanpbj_t')->where('norec', $request['norec'])->first();
            $nosuratpbj = $detail->nosuratpbj ?? '-';
            $ba2 = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaBa2 = $ba2->namalengkap ?? '-';
            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $penyeliaId = $detail->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh BA 2 : *$namaBa2*\n"
                        . "Dengan keterngan $ketBa2"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $asman = DB::table('pegawai_m')->where('id', 4)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh BA 2 : *$namaBa2*\n"
                    . "Dengan keterngan $ketBa2"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }

            $manager = DB::table('pegawai_m')->where('id', 3)->first();
            $nohpManager = $manager->nohandphone ?? null;
            $namaManager = $manager->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanManager = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaManager\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh BA 2 : *$namaBa2*\n"
                    . "Dengan keterngan $ketBa2"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpManager, $pesanManager);
            }

            $pegawaiPBJlList = DB::table('pegawai_m')
                ->where('objectjenispegawaifk', 10)
                ->get();

            foreach ($pegawaiPBJlList as $rowPegawaiPBJ) {
                $nohpPegawaiPbjAll  = $rowPegawaiPBJ->nohandphone ?? null;
                $namaPegawaiPbjAll  = $rowPegawaiPBJ->namalengkap ?? '-';
                if ($nohpPegawaiPbjAll) {
                    $pesanPegawaiPbjlAll = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPegawaiPbjAll\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh BA 2 : *$namaBa2*\n"
                        . "Keterangan BA 2 : *$ketBa2*\n"
                        . "Mohon untuk dilihat dan dilakuakan proses verifikasi\n\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPegawaiPbjAll, $pesanPegawaiPbjlAll);
                }
            }

            DB::commit();
            $transMessage = "Sukses Approval";

            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Approval Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getPengajuanPbjPembayaran(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.statusorderpembayaran',
                'dbpbj.namadistribusi',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderba2', 15)
            ->where('ppbj.statusorderpembayaran', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function getRiwayatPbjPembayaran(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'dbpbj.namadistribusi',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderpembayaran', '!=', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }

        return $this->respond($data);
    }

    public function saveApprovalPbjPembayaran(Request $request)
    {
        DB::beginTransaction();
        try {

            $updates = $this->mergeSuperAdminOptionalApprovalFields($request, [
                'statusorderpembayaran' => $request['statusorderpembayaran'],
                'statusorder' => $request['statusorderpembayaran'],
                'statusorderpembebanan' => 0,
                'tglverifpembayaranfk' => now(),
                'pembayaranveriffk' => $this->getPegawaiId(),
            ], [
                'ketverifpembayaran' => 'ketverifpembayaran',
                'verifikasiinvoice' => 'verifikasiinvoice',
                'nobkk' => 'nobkk',
                'statuspembayaran' => 'statuspembayaran',
            ]);
            PengajuanPBJ::where('norec', $request['norec'])->update($updates);

            $aksiText = (int)($request['statusorderpembayaran'] ?? 0) === 17 ? 'persetujuan' : 'penolakan';
            $ketPembayaran = $request['ketverifpembayaran'];
            $detail = DB::table('pengajuanpbj_t')->where('norec', $request['norec'])->first();
            $nosuratpbj = $detail->nosuratpbj ?? '-';
            $pembayaran = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaPembayaran = $pembayaran->namalengkap ?? '-';
            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $penyeliaId = $detail->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Pembayaran : *$namaPembayaran*\n"
                        . "Dengan keterngan $ketPembayaran"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $asman = DB::table('pegawai_m')->where('id', 4)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Pembayaran : *$namaPembayaran*\n"
                    . "Dengan keterngan $ketPembayaran"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }

            $manager = DB::table('pegawai_m')->where('id', 3)->first();
            $nohpManager = $manager->nohandphone ?? null;
            $namaManager = $manager->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanManager = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaManager\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Pembayaran : *$namaPembayaran*\n"
                    . "Dengan keterngan $ketPembayaran"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpManager, $pesanManager);
            }

            $pegawaiPBJlList = DB::table('pegawai_m')
                ->where('objectjenispegawaifk', 11)
                ->get();

            foreach ($pegawaiPBJlList as $rowPegawaiPBJ) {
                $nohpPegawaiPbjAll  = $rowPegawaiPBJ->nohandphone ?? null;
                $namaPegawaiPbjAll  = $rowPegawaiPBJ->namalengkap ?? '-';
                if ($nohpPegawaiPbjAll) {
                    $pesanPegawaiPbjlAll = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPegawaiPbjAll\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Pembayaran : *$namaPembayaran*\n"
                        . "Keterangan Pembayaran : *$ketPembayaran*\n"
                        . "Mohon untuk dilihat dan dilakuakan proses verifikasi\n\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPegawaiPbjAll, $pesanPegawaiPbjlAll);
                }
            }


            DB::commit();
            $transMessage = "Sukses Approval";

            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Approval Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getPengajuanPbjPembebanan(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpembebanan',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'dbpbj.namadistribusi',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderpembayaran', 17)
            ->where('ppbj.statusorderpembebanan', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function getRiwayatPbjPembebanan(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'dbpbj.namadistribusi',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderpembebanan', '!=', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }

        return $this->respond($data);
    }

    public function saveApprovalPbjPembebanan(Request $request)
    {
        DB::beginTransaction();
        try {

            $updates = $this->mergeSuperAdminOptionalApprovalFields($request, [
                'statusorderpembebanan' => $request['statusorderpembebanan'],
                'statusorder' => $request['statusorderpembebanan'],
                'statusorderpermintaanlimit' => 0,
                'tglverifpembebananfk' => now(),
                'pembebananveriffk' => $this->getPegawaiId(),
            ], [
                'ketverifpembebanan' => 'ketverifpembebanan',
                'nojurnal' => 'nojurnal',
                'periode' => 'periode',
            ]);
            PengajuanPBJ::where('norec', $request['norec'])->update($updates);

            $aksiText = (int)($request['statusorderpembebanan'] ?? 0) === 19 ? 'persetujuan' : 'penolakan';
            $ketPembebanan = $request['ketverifpembebanan'];
            $detail = DB::table('pengajuanpbj_t')->where('norec', $request['norec'])->first();
            $nosuratpbj = $detail->nosuratpbj ?? '-';
            $pembebanan = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaPembebanan = $pembebanan->namalengkap ?? '-';
            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $penyeliaId = $detail->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Pembebanan : *$namaPembebanan*\n"
                        . "Dengan keterngan $ketPembebanan"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $asman = DB::table('pegawai_m')->where('id', 4)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Pembebanan : *$namaPembebanan*\n"
                    . "Dengan keterngan $ketPembebanan"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }

            $manager = DB::table('pegawai_m')->where('id', 3)->first();
            $nohpManager = $manager->nohandphone ?? null;
            $namaManager = $manager->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanManager = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaManager\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Pembebanan : *$namaPembebanan*\n"
                    . "Dengan keterngan $ketPembebanan"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpManager, $pesanManager);
            }

            $pegawaiPBJlList = DB::table('pegawai_m')
                ->where('objectjenispegawaifk', 12)
                ->get();

            foreach ($pegawaiPBJlList as $rowPegawaiPBJ) {
                $nohpPegawaiPbjAll  = $rowPegawaiPBJ->nohandphone ?? null;
                $namaPegawaiPbjAll  = $rowPegawaiPBJ->namalengkap ?? '-';
                if ($nohpPegawaiPbjAll) {
                    $pesanPegawaiPbjlAll = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPegawaiPbjAll\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Pembebanan : *$namaPembebanan*\n"
                        . "Keterangan Pembebanan : *$ketPembebanan*\n"
                        . "Mohon untuk dilihat dan dilakuakan proses verifikasi\n\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPegawaiPbjAll, $pesanPegawaiPbjlAll);
                }
            }

            DB::commit();
            $transMessage = "Sukses Approval";

            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Approval Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getPengajuanPbjPermintaanlimit(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'ppbj.statusorderba2',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpembebanan',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'dbpbj.namadistribusi',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderpembebanan', 19)
            ->where('ppbj.statusorderpermintaanlimit', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
        }

        return $this->respond($data);
    }

    public function getRiwayatPbjPermintaanlimit(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'dbpbj.namadistribusi',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusorderpermintaanlimit', '!=', 0);

        $data = $data->orderByDesc('ppbj.tglpengajuan')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = (int) ($row->statusorder ?? 0);
            if ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }

        return $this->respond($data);
    }

    public function saveApprovalPbjPermintaanlimit(Request $request)
    {
        DB::beginTransaction();
        try {

            $updates = $this->mergeSuperAdminOptionalApprovalFields($request, [
                'statusorderpermintaanlimit' => $request['statusorderpermintaanlimit'],
                'statusorder' => $request['statusorderpermintaanlimit'],
                'tglverifpermintaanlimitfk' => now(),
                'permintaanlimitveriffk' => $this->getPegawaiId(),
            ], [
                'ketverifpermitaanlimit' => 'ketverifpermitaanlimit',
                'periodepermintaanlimit' => 'periodepermintaanlimit',
            ]);
            PengajuanPBJ::where('norec', $request['norec'])->update($updates);

            $aksiText = (int)($request['statusorderpermintaanlimit'] ?? 0) === 21 ? 'persetujuan' : 'penolakan';
            $ketPermintaan = $request['ketverifpermitaanlimit'];
            $detail = DB::table('pengajuanpbj_t')->where('norec', $request['norec'])->first();
            $nosuratpbj = $detail->nosuratpbj ?? '-';
            $permintaanLimit = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaPermintaanLimit = $permintaanLimit->namalengkap ?? '-';
            $dt = now('Asia/Jakarta');
            try {
                $waktuVerifStr = $dt->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Throwable $e) {
                $waktuVerifStr = $dt->format('d-m-Y H:i') . ' WIB';
            }

            $penyeliaId = $detail->pegawaifk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                        . "Telah dilakukan $aksiText oleh Permintaan Limit : *$namaPermintaanLimit*\n"
                        . "Dengan keterngan $ketPermintaan"
                        . "Pada $waktuVerifStr\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $asman = DB::table('pegawai_m')->where('id', 4)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Permintaan Limit : *$namaPermintaanLimit*\n"
                    . "Dengan keterngan $ketPermintaan"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }

            $manager = DB::table('pegawai_m')->where('id', 3)->first();
            $nohpManager = $manager->nohandphone ?? null;
            $namaManager = $manager->namalengkap ?? '-';
            if ($nohpAsman) {
                $pesanManager = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaManager\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Pengajuan Barang dan Jasa dengan No Surat : *$nosuratpbj*\n"
                    . "Telah dilakukan $aksiText oleh Permintaan Limit : *$namaPermintaanLimit*\n"
                    . "Dengan keterngan $ketPermintaan"
                    . "Pada $waktuVerifStr\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpManager, $pesanManager);
            }

            DB::commit();
            $transMessage = "Sukses Approval";

            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Approval Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function fetchUnitSurkes(Request $r)
    {
        $search = $r['query'];
        $tahun = (int) $r->input('tahun', date('Y'));

        $data = DB::table('mitra_m as mt')
            ->leftJoin('rbk_rencana_t as rbk', function ($join) use ($tahun) {
                $join->whereRaw('CAST(rbk.unitfk AS TEXT) = CAST(mt.id AS TEXT)')
                    ->where('rbk.statusenabled', true)
                    ->where('rbk.tahun', $tahun)
                    ->whereColumn('rbk.lokasi_id', 'mt.lokasisurkes');
            })
            ->select(
                'mt.id',
                'mt.namaperusahaan',
                'rbk.norec as rbk_norec',
                'rbk.no_prk',
                'rbk.cost_code',
            )
            ->where('mt.statusenabled', true)
            ->where('mt.issurkes', true);

        if (!empty($r['param_search']) && $search != '') {
            $exp = explode(',', $r['param_search']);
            $data = $data->where(function ($query) use ($exp, $search) {
                foreach ($exp as $item) {
                    $query->orWhere($item, 'ILIKE', '%' . $search . '%');
                }
            });
        }

        $result['data'] = $data->get();
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function getRiwayatPbjSuperAdmin(Request $r)
    {
        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->join('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->join('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->join('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftjoin('distribusiinventorypbj_m as dbpbj', 'dbpbj.id', '=', 'ppbj.distribusiinvenfk')
            ->leftjoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
            ->leftjoin('statuspaidpbj_m as spbj', 'spbj.id', '=', 'ppbj.statuspembayaran')
            ->leftjoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
            ->leftjoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
            ->leftjoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
            ->leftJoin('lokasikalibrasi_m as lkal', 'lkal.id', '=', 'ppbj.lokasipbjfk')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.statusorder',
                'lkal.lokasi as lokasipbj',
                'lkal.id as lokasipbjfk',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kebutuhanmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kebutuhan_mode"),
                'ppbj.kebutuhanmanual',
                'ppbj.kebutuhan as kebutuhan_fk',
                'dspbj.dasar as kebutuhan_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.bidangmanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as bidang_mode"),
                'ppbj.bidangmanual',
                'ppbj.bidang as bidang_fk',
                'mbpbj.managerbidang as bidang_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.kepadamanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as kepada_mode"),
                'ppbj.kepadamanual',
                'ppbj.kepada as kepada_fk',
                'kpbj.kepada as kepada_label',
                DB::raw("CASE WHEN NULLIF(TRIM(ppbj.usermanual), '') IS NOT NULL THEN 'manual' ELSE 'dropdown' END as user_mode"),
                'ppbj.usermanual',
                'ppbj.user as user_fk',
                'mupbj.namaperusahaan as user_label',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.status',
                'ppbj.statusorderasman',
                'ppbj.statusordermanager',
                'ppbj.statusorderrendal',
                'ppbj.statusordinven1',
                'ppbj.statusordinven2',
                'ppbj.statusorderpengadaan',
                'ppbj.statusorderba1',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglterimapr',
                'ppbj.tglpembuatanpr',
                'ppbj.tglpenerimaanberkaspr',
                'ppbj.tglterimainven2',
                'ppbj.tglaanwijzing',
                'ppbj.tglklartek',
                'ppbj.tglpembukaanpenawaran',
                'ppbj.tglpp',
                'ppbj.nopo',
                'ppbj.nilaipoppn',
                'ppbj.tglpo',
                'ppbj.pelaksana',
                'ppbj.hps',
                'ppbj.notes',
                'ppbj.sppp',
                'ppbj.tglmulairealisasi',
                'ppbj.tglselesairealisasi',
                'ppbj.dendahari',
                'ppbj.statusorderasmanba1',
                'ppbj.nilaisla',
                'ppbj.konfirmasivalidasirendalproyek',
                'ppbj.statusdarikatimpro',
                'dbpbj.namadistribusi',
                'ppbj.statusorderba2',
                'ppbj.statusorderpembayaran',
                'ppbj.statusorderpermintaanlimit',
                'ppbj.noba',
                'ppbj.posisiba',
                'ppbj.statusba',
                'ppbj.ketverifpembayaran',
                'ppbj.verifikasiinvoice',
                'ppbj.nobkk',
                'ppbj.periodepermintaanlimit',
                'spbj.statuspaid',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'ppbj.nopr',
                'ppbj.tglpr',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'phpbj.id as pemohonpbjfk',
                'pgpbj.pengadaanpbj',
                'pgpbj.id as pengadaanpbjfk',
                'ppbj.tglpenerimaanro',
                'ppbj.tglpenerimaanhpe',
                'ppbj.tglpenerimaanrks',
                'ppbj.tglpenyerahandokumen',
                'ppbj.nojurnal',
                'ppbj.periode',
                'ppbj.ketverifpembebanan',
                'prpbj.prioritaspbj',
                'prpbj.id as prioritaspbjfk'

            )
            ->where('ppbj.statusenabled', true);
        // ->where('ppbj.statusordermanager', 3);
        // ->where('ppbj.statusorderrendal', 0);

        $data = $data->orderByDesc('ppbj.nosuratpbj')->get();

        $detailAll = DB::table('pengajuandetailpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        $dataIht = DB::table('ihtpbj_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregpbjfk');

        foreach ($data as $row) {
            $row->detailList = $detailAll[$row->norec] ?? collect();
            $row->dataListIht = $dataIht[$row->norec] ?? collect();
            $status = $row->statusorder;
            if ($status === null) {
                $row->progress_step = 0;
                $row->progress      = 'Draft';
            } elseif ($status === 0) {
                $row->progress_step = 1;
                $row->progress      = 'Selesai Diajukan';
            } elseif ($status === 1 || $status === 2) {
                $row->progress_step = 2;
                $row->progress      = 'Selesai Diverifikasi Asman';
            } elseif ($status === 3 || $status === 4) {
                $row->progress_step = 3;
                $row->progress      = 'Selesai Diverifikasi Manager';
            } elseif ($status === 5 || $status === 6) {
                $row->progress_step = 4;
                $row->progress      = 'Selesai Diverifikasi Rendal';
            } elseif ($status === 7 || $status === 8) {
                $row->progress_step = 5;
                $row->progress      = 'Selesai Diverifikasi Inventory';
            } elseif ($status === 9 || $status === 10) {
                $row->progress_step = 6;
                $row->progress      = 'Selesai Diverifikasi Pengadaan';
            } elseif ($status === 11 || $status === 12) {
                $row->progress_step = 7;
                $row->progress      = 'Selesai Diverifikasi BA 1';
            } elseif ($status === 13 || $status === 14) {
                $row->progress_step = 8;
                $row->progress      = 'Selesai Diverifikasi Asman untuk BA 1';
            } elseif ($status === 15 || $status === 16) {
                $row->progress_step = 9;
                $row->progress      = 'Selesai Diverifikasi BA 2';
            } elseif ($status === 17 || $status === 18) {
                $row->progress_step = 10;
                $row->progress      = 'Selesai Diverifikasi Pembayaran';
            } elseif ($status === 19 || $status === 20) {
                $row->progress_step = 11;
                $row->progress      = 'Selesai Diverifikasi Pembebanan';
            } elseif ($status === 21 || $status === 22) {
                $row->progress_step = 12;
                $row->progress      = 'Selesai Diverifikasi Permintaan Limit';
            }

            if ($status === 1) {
                $row->statusText = 'Selesai Disetujui Asman';
                $row->color      = 'success';
            } elseif ($status === 3) {
                $row->statusText = 'Selesai Disetujui Manager';
                $row->color      = 'success';
            } elseif ($status === 5) {
                $row->statusText = 'Selesai Disetujui Rendal';
                $row->color      = 'success';
            } elseif ($status === 7) {
                $row->statusText = 'Selesai Disetujui Inventory 1';
                $row->color      = 'success';
            } elseif ($status === 9) {
                $row->statusText = 'Selesai Disetujui Pengadaan';
                $row->color      = 'success';
            } elseif ($status === 11) {
                $row->statusText = 'Selesai Disetujui BA1';
                $row->color      = 'success';
            } elseif ($status === 13) {
                $row->statusText = 'Selesai Disetujui Asman Untuk BA1';
                $row->color      = 'success';
            } elseif ($status === 15) {
                $row->statusText = 'Selesai Disetujui BA2';
                $row->color      = 'success';
            } elseif ($status === 17) {
                $row->statusText = 'Selesai Disetujui Pembayaran';
                $row->color      = 'success';
            } elseif ($status === 19) {
                $row->statusText = 'Selesai Disetujui Pembebanan';
                $row->color      = 'success';
            } elseif ($status === 21) {
                $row->statusText = 'Selesai Disetujui Permintaan Limit';
                $row->color      = 'success';
            } elseif ($status === 2) {
                $row->statusText = 'Ditolak Asman';
                $row->color      = 'danger';
            } elseif ($status === 4) {
                $row->statusText = 'Ditolak Manager';
                $row->color      = 'danger';
            } elseif ($status === 6) {
                $row->statusText = 'Ditolak Rendal';
                $row->color      = 'danger';
            } elseif ($status === 8) {
                $row->statusText = 'Ditolak Inventory 1';
                $row->color      = 'danger';
            } elseif ($status === 10) {
                $row->statusText = 'Ditolak Pengadaan';
                $row->color      = 'danger';
            } elseif ($status === 12) {
                $row->statusText = 'Ditolak BA1';
                $row->color      = 'danger';
            } elseif ($status === 14) {
                $row->statusText = 'Ditolak Asman Untuk BA1';
                $row->color      = 'danger';
            } elseif ($status === 16) {
                $row->statusText = 'Ditolak BA2';
                $row->color      = 'danger';
            } elseif ($status === 18) {
                $row->statusText = 'Ditolak Pembayaran';
                $row->color      = 'danger';
            } elseif ($status === 20) {
                $row->statusText = 'Ditolak Pembebanan';
                $row->color      = 'danger';
            } elseif ($status === 22) {
                $row->statusText = 'Ditolak Permintaan Limit';
                $row->color      = 'danger';
            } else {
                $row->statusText = 'Diajukan';
                $row->color      = 'warning';
            }

            $row->progress_step = max(1, min(12, (int) $row->progress_step));
        }

        return $this->respond($data);
    }
}
