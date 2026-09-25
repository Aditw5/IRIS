<?php

namespace App\Http\Controllers\Pbj;

use App\Http\Controllers\Controller;
use App\Traits\Valet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KendaliPengadaanCtrl extends Controller
{
    use Valet;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    /**
     * Daftar kerja HPS berisi PBJ yang telah diteruskan Inventory 1,
     * masih menunggu proses Pengadaan, dan belum mempunyai nomor PO.
     */
    public function getDaftarHps(Request $request)
    {
        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));

        $data = DB::table('pengajuanpbj_t as ppbj')
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->leftJoin('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->leftJoin('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
            ->leftJoin('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
            ->leftJoin('lokasikalibrasi_m as lokasi', 'lokasi.id', '=', 'ppbj.lokasipbjfk')
            ->leftJoin('dasaranggaranpbj_m as anggaran', 'anggaran.id', '=', 'ppbj.kebutuhan')
            ->leftJoin('managerbidangpbj_m as bidang', 'bidang.id', '=', 'ppbj.bidang')
            ->leftJoin('kepadapbj_m as kepada', 'kepada.id', '=', 'ppbj.kepada')
            ->leftJoin(DB::raw('mitra_m as pengguna'), DB::raw('CAST(pengguna.id AS INTEGER)'), '=', 'ppbj.user')
            ->leftJoin('hpspengadaan_t as hp', function ($join) use ($profileId) {
                $join->on('hp.pbjfk', '=', 'ppbj.norec')
                    ->where('hp.kdprofile', '=', $profileId)
                    ->where('hp.statusenabled', '=', true);
            })
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.tglpembuatanpr',
                'ppbj.prk',
                'ppbj.wo',
                'ppbj.project',
                'ppbj.noproyek',
                'ppbj.costcode',
                'ppbj.ppn',
                'ppbj.statusorderpengadaan',
                'pg.namalengkap',
                'jpbj.jenispbj',
                'jpbj.id as jenispbjfk',
                'phpbj.pemohonpbj',
                'pgpbj.pengadaanpbj',
                'lokasi.id as lokasipbjfk',
                'lokasi.lokasi as lokasipbj',
                DB::raw("COALESCE(NULLIF(TRIM(ppbj.kebutuhanmanual), ''), anggaran.dasar) as kebutuhan"),
                DB::raw("COALESCE(NULLIF(TRIM(ppbj.bidangmanual), ''), bidang.managerbidang) as bidang"),
                DB::raw("COALESCE(NULLIF(TRIM(ppbj.kepadamanual), ''), kepada.kepada) as kepada"),
                DB::raw("COALESCE(NULLIF(TRIM(ppbj.usermanual), ''), pengguna.namaperusahaan) as pengguna"),
                'hp.nomorhps',
                'hp.nourut as nomoruruthps',
                'hp.tahun as tahunhps',
                'hp.tanggalhps'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.statusordinven1', 7)
            ->where('ppbj.statusorderpengadaan', 0)
            ->where(function ($query) {
                $query->whereNull('ppbj.nopo')
                    ->orWhereRaw("TRIM(COALESCE(ppbj.nopo, '')) = ''");
            });

        if ($request->filled('search')) {
            $keyword = '%' . trim((string) $request->input('search')) . '%';
            $data->where(function ($query) use ($keyword) {
                $query->where('ppbj.nosuratpbj', 'ILIKE', $keyword)
                    ->orWhere('ppbj.judulpermintaan', 'ILIKE', $keyword)
                    ->orWhere('pg.namalengkap', 'ILIKE', $keyword)
                    ->orWhere('ppbj.nopr', 'ILIKE', $keyword);
            });
        }

        if ($request->filled('lokasi')) {
            $data->where('lokasi.id', $request->input('lokasi'));
        }

        if ($request->filled('jenis')) {
            $data->where('jpbj.id', $request->input('jenis'));
        }

        $data = $data
            ->orderByDesc(DB::raw('COALESCE(ppbj.tglpr, ppbj.tglpengajuan)'))
            ->get();

        $norecPbj = $data->pluck('norec')->filter()->values();
        $detailAll = collect();
        $ihtAll = collect();

        if ($norecPbj->isNotEmpty()) {
            $detailAll = DB::table('pengajuandetailpbj_t')
                ->where('statusenabled', true)
                ->whereIn('noregpbjfk', $norecPbj)
                ->orderByRaw('COALESCE(urutitem, 999999) ASC')
                ->orderBy('created_at')
                ->get()
                ->groupBy('noregpbjfk');

            $ihtAll = DB::table('ihtpbj_t')
                ->where('statusenabled', true)
                ->whereIn('noregpbjfk', $norecPbj)
                ->orderBy('created_at')
                ->get()
                ->groupBy('noregpbjfk');
        }

        foreach ($data as $row) {
            $row->detailList = ($detailAll[$row->norec] ?? collect())->values();
            $row->dataListIht = ($ihtAll[$row->norec] ?? collect())->values();
        }

        return $this->respond($data);
    }

    /**
     * Menyiapkan nomor HPS berikutnya tanpa menyimpannya.
     * Nomor baru hanya disimpan setelah pengguna menekan tombol Simpan.
     */
    public function nextHpsNumber(Request $request)
    {
        $request->validate([
            'norec' => 'required|uuid',
        ]);

        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));
        $pbj = DB::table('pengajuanpbj_t')
            ->where('norec', $request->input('norec'))
            ->where('statusenabled', true)
            ->first();

        if (!$pbj) {
            return $this->respond([], 404, 'Data PBJ tidak ditemukan.');
        }

        if (!empty(trim((string) ($pbj->nopo ?? '')))) {
            return $this->respond([], 422, 'HPS tidak dapat dibuat karena nomor PO sudah diinput.');
        }

        if (empty($pbj->tglpr)) {
            return $this->respond([], 422, 'Tanggal PR belum diinput. Nomor HPS belum dapat dibuat.');
        }

        $tanggalPr = Carbon::parse($pbj->tglpr, 'Asia/Jakarta');
        $tahun = (int) $tanggalPr->format('Y');

        $existing = DB::table('hpspengadaan_t')
            ->where('kdprofile', $profileId)
            ->where('pbjfk', $pbj->norec)
            ->where('statusenabled', true)
            ->first();

        $nomorUrut = $existing
            ? (int) $existing->nourut
            : ((int) DB::table('hpspengadaan_t')
                ->where('kdprofile', $profileId)
                ->where('tahun', $tahun)
                ->where('statusenabled', true)
                ->max('nourut') + 1);

        return $this->respond([
            'norec' => $existing ? $existing->norec : null,
            'norecPbj' => $pbj->norec,
            'nourut' => $nomorUrut,
            'tahun' => $tahun,
            'nomorhps' => sprintf('HTD.HPS/%03d/UMRO/%d', $nomorUrut, $tahun),
            'tanggalhps' => $tanggalPr->toDateString(),
            'existing' => (bool) $existing,
        ], 200, 'Nomor HPS berikutnya siap digunakan.');
    }

    /**
     * Menyimpan nomor HPS yang telah dikonfirmasi pengguna.
     */
    public function saveHpsNumber(Request $request)
    {
        $request->validate([
            'norec' => 'required|uuid',
            'nourut' => 'required|integer|min:1|max:999999',
        ]);

        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));
        $pbj = DB::table('pengajuanpbj_t')
            ->where('norec', $request->input('norec'))
            ->where('statusenabled', true)
            ->first();

        if (!$pbj) {
            return $this->respond([], 404, 'Data PBJ tidak ditemukan.');
        }

        if (!empty(trim((string) ($pbj->nopo ?? '')))) {
            return $this->respond([], 422, 'HPS tidak dapat dibuat karena nomor PO sudah diinput.');
        }

        if (empty($pbj->tglpr)) {
            return $this->respond([], 422, 'Tanggal PR belum diinput. Nomor HPS belum dapat dibuat.');
        }

        $tanggalPr = Carbon::parse($pbj->tglpr, 'Asia/Jakarta');
        $tahun = (int) $tanggalPr->format('Y');
        $nomorUrut = (int) $request->input('nourut');

        $hps = DB::transaction(function () use ($profileId, $pbj, $tanggalPr, $tahun, $nomorUrut) {
            // Advisory lock menjaga urutan nomor ketika dua pengguna menyimpan
            // nomor pada tahun yang sama secara bersamaan.
            DB::select('SELECT pg_advisory_xact_lock(?)', [$profileId * 10000 + $tahun]);

            $conflict = DB::table('hpspengadaan_t')
                ->where('kdprofile', $profileId)
                ->where('tahun', $tahun)
                ->where('nourut', $nomorUrut)
                ->where('statusenabled', true)
                ->where('pbjfk', '<>', $pbj->norec)
                ->exists();

            if ($conflict) {
                return null;
            }

            $nomorHps = sprintf('HTD.HPS/%03d/UMRO/%d', $nomorUrut, $tahun);
            $existing = DB::table('hpspengadaan_t')
                ->where('kdprofile', $profileId)
                ->where('pbjfk', $pbj->norec)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            $payload = [
                'kdprofile' => $profileId,
                'statusenabled' => true,
                'pbjfk' => $pbj->norec,
                'nourut' => $nomorUrut,
                'tahun' => $tahun,
                'nomorhps' => $nomorHps,
                'tanggalhps' => $tanggalPr->toDateString(),
                'updatedby' => $this->getPegawaiId(),
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('hpspengadaan_t')
                    ->where('norec', $existing->norec)
                    ->update($payload);

                return DB::table('hpspengadaan_t')->where('norec', $existing->norec)->first();
            }

            $norecHps = (string) Str::uuid();
            DB::table('hpspengadaan_t')->insert(array_merge($payload, [
                'norec' => $norecHps,
                'createdby' => $this->getPegawaiId(),
                'created_at' => now(),
            ]));

            return DB::table('hpspengadaan_t')->where('norec', $norecHps)->first();
        });

        if (!$hps) {
            return $this->respond([], 422, 'Nomor urut HPS tersebut sudah digunakan PBJ lain pada tahun yang sama.');
        }

        return $this->respond($hps, 200, 'Nomor HPS berhasil disimpan.');
    }

    public function cetakHps(Request $request)
    {
        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));

        $hps = DB::table('pengajuanpbj_t as ppbj')
            ->leftJoin('hpspengadaan_t as hp', function ($join) use ($profileId) {
                $join->on('hp.pbjfk', '=', 'ppbj.norec')
                    ->where('hp.kdprofile', '=', $profileId)
                    ->where('hp.statusenabled', '=', true);
            })
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->leftJoin('lokasikalibrasi_m as lokasi', 'lokasi.id', '=', 'ppbj.lokasipbjfk')
            ->select(
                'hp.norec as norechps',
                'hp.nomorhps',
                'hp.nourut',
                'hp.tahun',
                'hp.tanggalhps',
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.judulpermintaan',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.ppn',
                'pg.namalengkap',
                'lokasi.lokasi as lokasipbj'
            )
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.norec', $request->input('norec'))
            ->first();

        if (!$hps) {
            abort(404, 'Data PBJ tidak ditemukan.');
        }

        // Cetakan tetap dapat dibuka sebelum nomor HPS disimpan. Placeholder
        // ini hanya untuk dokumen cetak dan tidak mengubah data pada database.
        $hps->nomorhps = $hps->nomorhps ?: 'BELUM ADA NOMOR HPS';
        $hps->tanggalhps = $hps->tanggalhps ?: ($hps->tglpr ?: now()->toDateString());

        $details = DB::table('pengajuandetailpbj_t')
            ->where('noregpbjfk', $hps->norec)
            ->where('statusenabled', true)
            ->orderByRaw('COALESCE(urutitem, 999999) ASC')
            ->orderBy('created_at')
            ->get();

        $subtotal = 0;
        foreach ($details as $detail) {
            $detail->hargaangka = $this->nilaiHarga($detail->hargasatuan ?? 0);
            $detail->totalharga = ((float) ($detail->banyak ?? 0)) * $detail->hargaangka;
            $subtotal += $detail->totalharga;
        }

        $ppnPersen = is_numeric($hps->ppn) ? (float) $hps->ppn : 12;
        $dppNilaiLain = $ppnPersen > 0 ? ($subtotal * 11 / 12) : $subtotal;
        $nilaiPpn = $ppnPersen > 0 ? ($dppNilaiLain * $ppnPersen / 100) : 0;
        $grandTotal = $subtotal + $nilaiPpn;

        $tanggalHps = Carbon::parse($hps->tanggalhps, 'Asia/Jakarta')
            ->locale('id')
            ->translatedFormat('d F Y');

        $res = compact(
            'hps',
            'details',
            'subtotal',
            'dppNilaiLain',
            'nilaiPpn',
            'grandTotal',
            'ppnPersen',
            'tanggalHps'
        );

        $blade = 'report.pbj.hps-pengadaan';
        $namaFile = str_replace('/', '-', $hps->nomorhps) . '.pdf';

        if ($request->input('pdf') === 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->setPaper('a4', 'landscape');
            $pdf->loadView($blade, compact('res'));

            return $pdf->stream($namaFile);
        }

        return view($blade, compact('res'));
    }

    /**
     * Daftar PP hanya berisi PBJ yang telah mempunyai nomor HPS.
     */
    public function getDaftarPp(Request $request)
    {
        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));

        $data = DB::table('hpspengadaan_t as hp')
            ->join('pengajuanpbj_t as ppbj', 'ppbj.norec', '=', 'hp.pbjfk')
            ->leftJoin('pppengadaan_t as pp', function ($join) use ($profileId) {
                $join->on('pp.pbjfk', '=', 'ppbj.norec')
                    ->where('pp.kdprofile', '=', $profileId)
                    ->where('pp.statusenabled', '=', true);
            })
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->leftJoin('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->leftJoin('lokasikalibrasi_m as lokasi', 'lokasi.id', '=', 'ppbj.lokasipbjfk')
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.ppn',
                'pg.namalengkap',
                'jpbj.id as jenispbjfk',
                'jpbj.jenispbj',
                'lokasi.id as lokasipbjfk',
                'lokasi.lokasi as lokasipbj',
                'hp.norec as norechps',
                'hp.nomorhps',
                'hp.nourut as nomoruruthps',
                'hp.tahun as tahunhps',
                'pp.norec as norecpp',
                'pp.nomorpp',
                'pp.tanggalpp',
                'pp.bataspemasukan'
            )
            ->where('hp.kdprofile', $profileId)
            ->where('hp.statusenabled', true)
            ->where('ppbj.statusenabled', true)
            ->where(function ($query) {
                $query->whereNull('ppbj.nopo')
                    ->orWhereRaw("TRIM(COALESCE(ppbj.nopo, '')) = ''");
            })
            ->orderByDesc('hp.tahun')
            ->orderByDesc('hp.nourut')
            ->get();

        $pbjIds = $data->pluck('norec')->filter()->values();
        $details = collect();
        $files = collect();

        if ($pbjIds->isNotEmpty()) {
            $details = DB::table('pengajuandetailpbj_t')
                ->where('statusenabled', true)
                ->whereIn('noregpbjfk', $pbjIds)
                ->orderByRaw('COALESCE(urutitem, 999999) ASC')
                ->orderBy('created_at')
                ->get()
                ->groupBy('noregpbjfk');

            $files = DB::table('pppengadaanfile_t')
                ->where('kdprofile', $profileId)
                ->where('statusenabled', true)
                ->whereIn('pbjfk', $pbjIds)
                ->orderByDesc('created_at')
                ->get([
                    'norec',
                    'pbjfk',
                    'namaasli',
                    'namafile',
                    'mimetype',
                    'ukuran',
                    'created_at',
                ])
                ->groupBy('pbjfk');
        }

        foreach ($data as $row) {
            $row->nomorpp = sprintf(
                'HTD.PP/%03d/UMRO/%d',
                (int) $row->nomoruruthps,
                (int) $row->tahunhps
            );
            $row->detailList = ($details[$row->norec] ?? collect())->values();
            $row->fileList = ($files[$row->norec] ?? collect())->values();
            $row->jumlahfilepp = $row->fileList->count();
            $row->siapbapp = $row->jumlahfilepp > 0;
        }

        return $this->respond($data);
    }

    public function savePp(Request $request)
    {
        $request->validate([
            'norec' => 'required|uuid',
            'tanggalpp' => 'nullable|date',
            'bataspemasukan' => 'nullable|date',
        ]);

        if ($request->filled('tanggalpp') && $request->filled('bataspemasukan')) {
            $tanggal = Carbon::parse($request->input('tanggalpp'));
            $batas = Carbon::parse($request->input('bataspemasukan'));
            if ($batas->lt($tanggal)) {
                return $this->respond([], 422, 'Batas pemasukan penawaran tidak boleh lebih awal dari tanggal PP.');
            }
        }

        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));
        $hps = $this->getHpsAktif($request->input('norec'), $profileId);
        if (!$hps) {
            return $this->respond([], 422, 'PBJ belum mempunyai nomor HPS dan belum dapat diproses pada tahap PP.');
        }

        $nomorPp = sprintf('HTD.PP/%03d/UMRO/%d', (int) $hps->nourut, (int) $hps->tahun);
        $pp = DB::transaction(function () use ($request, $profileId, $hps, $nomorPp) {
            $existing = DB::table('pppengadaan_t')
                ->where('kdprofile', $profileId)
                ->where('pbjfk', $hps->pbjfk)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            $payload = [
                'hpsfk' => $hps->norec,
                'nomorpp' => $nomorPp,
                'tanggalpp' => $request->input('tanggalpp') ?: null,
                'bataspemasukan' => $request->input('bataspemasukan') ?: null,
                'updatedby' => $this->getPegawaiId(),
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('pppengadaan_t')->where('norec', $existing->norec)->update($payload);
                return DB::table('pppengadaan_t')->where('norec', $existing->norec)->first();
            }

            $norec = (string) Str::uuid();
            DB::table('pppengadaan_t')->insert(array_merge($payload, [
                'kdprofile' => $profileId,
                'statusenabled' => true,
                'norec' => $norec,
                'pbjfk' => $hps->pbjfk,
                'createdby' => $this->getPegawaiId(),
                'created_at' => now(),
            ]));

            return DB::table('pppengadaan_t')->where('norec', $norec)->first();
        });

        return $this->respond($pp, 200, 'Tanggal Permintaan Penawaran berhasil disimpan.');
    }

    public function uploadPpFiles(Request $request)
    {
        $request->validate([
            'norec' => 'required|uuid',
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:15360|mimes:pdf,doc,docx,xls,xlsx',
        ]);

        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));
        $hps = $this->getHpsAktif($request->input('norec'), $profileId);
        if (!$hps) {
            return $this->respond([], 422, 'PBJ belum mempunyai nomor HPS dan belum dapat diproses pada tahap PP.');
        }

        $pp = DB::table('pppengadaan_t')
            ->where('kdprofile', $profileId)
            ->where('pbjfk', $hps->pbjfk)
            ->where('statusenabled', true)
            ->first();

        if (!$pp || !$pp->tanggalpp || !$pp->bataspemasukan) {
            return $this->respond([], 422, 'Simpan tanggal PP dan batas pemasukan penawaran sebelum mengupload file.');
        }

        $destination = public_path('berkas-pbj/pp');
        if (!is_dir($destination) && !@mkdir($destination, 0775, true) && !is_dir($destination)) {
            return $this->respond([], 500, 'Folder penyimpanan file PP tidak dapat dibuat.');
        }

        $savedPaths = [];
        try {
            $rows = [];
            foreach ((array) $request->file('files') as $file) {
                if (!$file || !$file->isValid()) {
                    throw new \RuntimeException('Salah satu file PP tidak valid.');
                }

                $extension = strtolower((string) $file->getClientOriginalExtension());
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getClientMimeType();
                $fileSize = (int) $file->getSize();
                $baseName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'dokumen-pp';
                $storedName = 'pp-' . now('Asia/Jakarta')->format('YmdHisv') . '-'
                    . Str::lower(Str::random(6)) . '-' . Str::limit($baseName, 70, '') . '.' . $extension;
                $file->move($destination, $storedName);
                $savedPaths[] = $destination . DIRECTORY_SEPARATOR . $storedName;

                $rows[] = [
                    'kdprofile' => $profileId,
                    'statusenabled' => true,
                    'norec' => (string) Str::uuid(),
                    'ppfk' => $pp->norec,
                    'pbjfk' => $hps->pbjfk,
                    'namaasli' => $originalName,
                    'namafile' => $storedName,
                    'mimetype' => $mimeType,
                    'ukuran' => $fileSize,
                    'createdby' => $this->getPegawaiId(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('pppengadaanfile_t')->insert($rows);
            return $this->respond(['jumlah' => count($rows)], 200, count($rows) . ' file PP berhasil diupload.');
        } catch (\Throwable $e) {
            foreach ($savedPaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            return $this->respond([], 400, $e->getMessage());
        }
    }

    public function downloadPpFile(Request $request)
    {
        [$file, $path] = $this->resolvePpFile($request);

        return response()->download($path, $file->namaasli);
    }

    public function viewPpFile(Request $request)
    {
        [$file] = $this->resolvePpFile($request);
        $filename = basename((string) $file->namafile);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filepath = $this->publicFileUrl('berkas-pbj/pp', $filename);

        return view('report.pbj.view-pp-file', compact('file', 'filepath', 'extension'));
    }

    public function deletePpFile(Request $request)
    {
        $request->validate(['norec' => 'required|uuid']);
        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));
        $file = DB::table('pppengadaanfile_t')
            ->where('kdprofile', $profileId)
            ->where('norec', $request->input('norec'))
            ->where('statusenabled', true)
            ->first();

        if (!$file) {
            return $this->respond([], 404, 'File PP tidak ditemukan.');
        }

        DB::table('pppengadaanfile_t')
            ->where('norec', $file->norec)
            ->update([
                'statusenabled' => false,
                'updated_at' => now(),
            ]);

        return $this->respond([], 200, 'File PP berhasil dihapus dari riwayat upload.');
    }

    public function cetakPp(Request $request)
    {
        $request->validate(['norec' => 'required|uuid']);
        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));

        $pp = DB::table('hpspengadaan_t as hp')
            ->join('pengajuanpbj_t as ppbj', 'ppbj.norec', '=', 'hp.pbjfk')
            ->leftJoin('pppengadaan_t as pp', function ($join) use ($profileId) {
                $join->on('pp.pbjfk', '=', 'ppbj.norec')
                    ->where('pp.kdprofile', '=', $profileId)
                    ->where('pp.statusenabled', '=', true);
            })
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.judulpermintaan',
                'ppbj.nopr',
                'ppbj.tglpr',
                'ppbj.ppn',
                'hp.norec as norechps',
                'hp.nomorhps',
                'hp.nourut',
                'hp.tahun',
                'pp.norec as norecpp',
                'pp.nomorpp',
                'pp.tanggalpp',
                'pp.bataspemasukan'
            )
            ->where('hp.kdprofile', $profileId)
            ->where('hp.statusenabled', true)
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.norec', $request->input('norec'))
            ->first();

        if (!$pp) {
            abort(404, 'Data PP tidak ditemukan atau nomor HPS belum dibuat.');
        }

        $pp->nomorpp = sprintf('HTD.PP/%03d/UMRO/%d', (int) $pp->nourut, (int) $pp->tahun);
        $details = DB::table('pengajuandetailpbj_t')
            ->where('noregpbjfk', $pp->norec)
            ->where('statusenabled', true)
            ->orderByRaw('COALESCE(urutitem, 999999) ASC')
            ->orderBy('created_at')
            ->get();

        $tanggalPp = $pp->tanggalpp
            ? Carbon::parse($pp->tanggalpp, 'Asia/Jakarta')->locale('id')->translatedFormat('d F Y')
            : '-';
        $batasPemasukan = $pp->bataspemasukan
            ? Carbon::parse($pp->bataspemasukan, 'Asia/Jakarta')->locale('id')->translatedFormat('d F Y')
            : '-';
        $res = compact('pp', 'details', 'tanggalPp', 'batasPemasukan');
        $blade = 'report.pbj.pp-pengadaan';
        $namaFile = str_replace('/', '-', $pp->nomorpp) . '.pdf';

        if ($request->input('pdf') === 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->setPaper('a4', 'portrait');
            $pdf->loadView($blade, compact('res'));
            return $pdf->stream($namaFile);
        }

        return view($blade, compact('res'));
    }

    /**
     * Daftar BAPP hanya berisi PP yang sudah mempunyai minimal satu file aktif.
     */
    public function getDaftarBapp(Request $request)
    {
        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));

        $data = DB::table('pppengadaan_t as pp')
            ->join('hpspengadaan_t as hp', function ($join) {
                $join->on(DB::raw('CAST(hp.norec AS TEXT)'), '=', 'pp.hpsfk');
            })
            ->join('pengajuanpbj_t as ppbj', function ($join) {
                $join->on(DB::raw('CAST(ppbj.norec AS TEXT)'), '=', 'pp.pbjfk');
            })
            ->join('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
            ->leftJoin('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
            ->leftJoin('lokasikalibrasi_m as lokasi', 'lokasi.id', '=', 'ppbj.lokasipbjfk')
            ->leftJoin('bapppengadaan_t as bapp', function ($join) use ($profileId) {
                $join->on('bapp.pbjfk', '=', DB::raw('CAST(ppbj.norec AS TEXT)'))
                    ->where('bapp.kdprofile', '=', $profileId)
                    ->where('bapp.statusenabled', '=', true);
            })
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.tglpengajuan',
                'ppbj.judulpermintaan',
                'ppbj.nopr',
                'ppbj.tglpr',
                'pg.namalengkap',
                'jpbj.id as jenispbjfk',
                'jpbj.jenispbj',
                'lokasi.id as lokasipbjfk',
                'lokasi.lokasi as lokasipbj',
                'hp.norec as norechps',
                'hp.nomorhps',
                'hp.nourut as nomoruruthps',
                'hp.tahun as tahunhps',
                'pp.norec as norecpp',
                'pp.nomorpp',
                'pp.tanggalpp',
                'pp.bataspemasukan',
                'bapp.norec as norecbapp',
                'bapp.nomorbapp',
                'bapp.tanggalbapp'
            )
            ->where('pp.kdprofile', $profileId)
            ->where('pp.statusenabled', true)
            ->where('hp.kdprofile', $profileId)
            ->where('hp.statusenabled', true)
            ->where('ppbj.statusenabled', true)
            ->whereExists(function ($query) use ($profileId) {
                $query->select(DB::raw(1))
                    ->from('pppengadaanfile_t as ppf')
                    ->whereRaw('ppf.ppfk = CAST(pp.norec AS TEXT)')
                    ->where('ppf.kdprofile', $profileId)
                    ->where('ppf.statusenabled', true);
            })
            ->orderByDesc('hp.tahun')
            ->orderByDesc('hp.nourut')
            ->get();

        $pbjIds = $data->pluck('norec')->filter()->values();
        $bappIds = $data->pluck('norecbapp')->filter()->values();
        $fileCounts = collect();
        $penyedia = collect();

        if ($pbjIds->isNotEmpty()) {
            $fileCounts = DB::table('pppengadaanfile_t')
                ->where('kdprofile', $profileId)
                ->where('statusenabled', true)
                ->whereIn('pbjfk', $pbjIds)
                ->select('pbjfk', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('pbjfk')
                ->pluck('jumlah', 'pbjfk');
        }

        if ($bappIds->isNotEmpty()) {
            $penyedia = DB::table('bapppengadaanpenyedia_t')
                ->where('kdprofile', $profileId)
                ->where('statusenabled', true)
                ->whereIn('bappfk', $bappIds)
                ->orderBy('created_at')
                ->get([
                    'norec',
                    'bappfk',
                    'namapenyedia',
                    'totalharga',
                    'keterangan',
                    'created_at',
                ])
                ->groupBy('bappfk');
        }

        foreach ($data as $row) {
            $row->nomorpp = sprintf(
                'HTD.PP/%03d/UMRO/%d',
                (int) $row->nomoruruthps,
                (int) $row->tahunhps
            );
            $row->nomorbapp = sprintf(
                'HTD.BAPP/%03d/UMRO/%d',
                (int) $row->nomoruruthps,
                (int) $row->tahunhps
            );
            $row->jumlahfilepp = (int) ($fileCounts[$row->norec] ?? 0);
            $row->penyediaList = $row->norecbapp
                ? ($penyedia[$row->norecbapp] ?? collect())->values()
                : collect();
            $row->jumlahpenyedia = $row->penyediaList->count();
        }

        return $this->respond($data);
    }

    public function saveBapp(Request $request)
    {
        $request->validate([
            'norec' => 'required|uuid',
            'tanggalbapp' => 'required|date',
        ]);

        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));
        $pp = $this->getPpSiapBapp($request->input('norec'), $profileId);
        if (!$pp) {
            return $this->respond([], 422, 'PBJ belum mempunyai file PP dan belum dapat diproses pada tahap BAPP.');
        }

        $bapp = DB::transaction(function () use ($request, $profileId, $pp) {
            $bapp = $this->ensureBappAktif($pp, $profileId);
            DB::table('bapppengadaan_t')
                ->where('norec', $bapp->norec)
                ->update([
                    'tanggalbapp' => Carbon::parse($request->input('tanggalbapp'))->toDateString(),
                    'updatedby' => $this->getPegawaiId(),
                    'updated_at' => now(),
                ]);

            return DB::table('bapppengadaan_t')->where('norec', $bapp->norec)->first();
        });

        return $this->respond($bapp, 200, 'Tanggal BAPP berhasil disimpan.');
    }

    public function saveBappPenyedia(Request $request)
    {
        $request->validate([
            'norec' => 'required|uuid',
            'norecpenyedia' => 'nullable|uuid',
            'namapenyedia' => 'required|string|max:255',
            'totalharga' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string|max:2000',
        ]);

        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));
        $pp = $this->getPpSiapBapp($request->input('norec'), $profileId);
        if (!$pp) {
            return $this->respond([], 422, 'PBJ belum mempunyai file PP dan belum dapat diproses pada tahap BAPP.');
        }

        $isEdit = $request->filled('norecpenyedia');
        $penyedia = DB::transaction(function () use ($request, $profileId, $pp, $isEdit) {
            $bapp = $this->ensureBappAktif($pp, $profileId);
            $payload = [
                'namapenyedia' => trim((string) $request->input('namapenyedia')),
                'totalharga' => (float) $request->input('totalharga'),
                'keterangan' => $request->filled('keterangan')
                    ? trim((string) $request->input('keterangan'))
                    : null,
                'updatedby' => $this->getPegawaiId(),
                'updated_at' => now(),
            ];

            if ($isEdit) {
                $existing = DB::table('bapppengadaanpenyedia_t')
                    ->where('kdprofile', $profileId)
                    ->where('norec', $request->input('norecpenyedia'))
                    ->where('bappfk', $bapp->norec)
                    ->where('pbjfk', $pp->pbjfk)
                    ->where('statusenabled', true)
                    ->lockForUpdate()
                    ->first();

                if (!$existing) {
                    abort(404, 'Data penyedia yang akan diedit tidak ditemukan.');
                }

                DB::table('bapppengadaanpenyedia_t')
                    ->where('norec', $existing->norec)
                    ->update($payload);

                return DB::table('bapppengadaanpenyedia_t')->where('norec', $existing->norec)->first();
            }

            $norec = (string) Str::uuid();
            DB::table('bapppengadaanpenyedia_t')->insert(array_merge($payload, [
                'kdprofile' => $profileId,
                'statusenabled' => true,
                'norec' => $norec,
                'bappfk' => $bapp->norec,
                'pbjfk' => $pp->pbjfk,
                'createdby' => $this->getPegawaiId(),
                'created_at' => now(),
            ]));

            return DB::table('bapppengadaanpenyedia_t')->where('norec', $norec)->first();
        });

        return $this->respond(
            $penyedia,
            200,
            $isEdit ? 'Data penyedia berhasil diperbarui.' : 'Penyedia berhasil ditambahkan.'
        );
    }

    public function deleteBappPenyedia(Request $request)
    {
        $request->validate(['norec' => 'required|uuid']);
        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));

        $penyedia = DB::table('bapppengadaanpenyedia_t')
            ->where('kdprofile', $profileId)
            ->where('norec', $request->input('norec'))
            ->where('statusenabled', true)
            ->first();

        if (!$penyedia) {
            return $this->respond([], 404, 'Data penyedia tidak ditemukan.');
        }

        DB::table('bapppengadaanpenyedia_t')
            ->where('norec', $penyedia->norec)
            ->update([
                'statusenabled' => false,
                'updatedby' => $this->getPegawaiId(),
                'updated_at' => now(),
            ]);

        return $this->respond([], 200, 'Penyedia berhasil dihapus dari daftar BAPP.');
    }

    public function cetakBapp(Request $request)
    {
        $request->validate(['norec' => 'required|uuid']);
        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));

        $bapp = DB::table('pppengadaan_t as pp')
            ->join('hpspengadaan_t as hp', function ($join) {
                $join->on(DB::raw('CAST(hp.norec AS TEXT)'), '=', 'pp.hpsfk');
            })
            ->join('pengajuanpbj_t as ppbj', function ($join) {
                $join->on(DB::raw('CAST(ppbj.norec AS TEXT)'), '=', 'pp.pbjfk');
            })
            ->leftJoin('bapppengadaan_t as bapp', function ($join) use ($profileId) {
                $join->on('bapp.pbjfk', '=', DB::raw('CAST(ppbj.norec AS TEXT)'))
                    ->where('bapp.kdprofile', '=', $profileId)
                    ->where('bapp.statusenabled', '=', true);
            })
            ->select(
                'ppbj.norec',
                'ppbj.nosuratpbj',
                'ppbj.judulpermintaan',
                'ppbj.nopr',
                'hp.nourut',
                'hp.tahun',
                'pp.norec as norecpp',
                'pp.nomorpp',
                'pp.tanggalpp',
                'pp.bataspemasukan',
                'bapp.norec as norecbapp',
                'bapp.tanggalbapp'
            )
            ->where('pp.kdprofile', $profileId)
            ->where('pp.statusenabled', true)
            ->where('hp.kdprofile', $profileId)
            ->where('hp.statusenabled', true)
            ->where('ppbj.statusenabled', true)
            ->where('ppbj.norec', $request->input('norec'))
            ->whereExists(function ($query) use ($profileId) {
                $query->select(DB::raw(1))
                    ->from('pppengadaanfile_t as ppf')
                    ->whereRaw('ppf.ppfk = CAST(pp.norec AS TEXT)')
                    ->where('ppf.kdprofile', $profileId)
                    ->where('ppf.statusenabled', true);
            })
            ->first();

        if (!$bapp) {
            abort(404, 'Data BAPP tidak ditemukan atau file PP belum diupload.');
        }

        $bapp->nomorpp = sprintf('HTD.PP/%03d/UMRO/%d', (int) $bapp->nourut, (int) $bapp->tahun);
        $bapp->nomorbapp = sprintf('HTD.BAPP/%03d/UMRO/%d', (int) $bapp->nourut, (int) $bapp->tahun);
        $penyedia = $bapp->norecbapp
            ? DB::table('bapppengadaanpenyedia_t')
                ->where('kdprofile', $profileId)
                ->where('bappfk', $bapp->norecbapp)
                ->where('statusenabled', true)
                ->orderBy('created_at')
                ->get()
            : collect();

        $tanggalBappRaw = $bapp->tanggalbapp ?: $bapp->bataspemasukan;
        $tanggalBapp = $tanggalBappRaw
            ? Carbon::parse($tanggalBappRaw, 'Asia/Jakarta')->locale('id')->translatedFormat('d F Y')
            : '-';
        $tanggalPp = $bapp->tanggalpp
            ? Carbon::parse($bapp->tanggalpp, 'Asia/Jakarta')->locale('id')->translatedFormat('d F Y')
            : '-';

        $res = compact('bapp', 'penyedia', 'tanggalBapp', 'tanggalPp');
        $blade = 'report.pbj.bapp-pengadaan';
        $namaFile = str_replace('/', '-', $bapp->nomorbapp) . '.pdf';

        if ($request->input('pdf') === 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->setPaper('a4', 'portrait');
            $pdf->loadView($blade, compact('res'));
            return $pdf->stream($namaFile);
        }

        return view($blade, compact('res'));
    }

    private function resolvePpFile(Request $request): array
    {
        $request->validate(['norec' => 'required|uuid']);
        $profileId = (int) ($this->kdProfile ?: $request->input('kdprofile', 1));
        $file = DB::table('pppengadaanfile_t')
            ->where('kdprofile', $profileId)
            ->where('norec', $request->input('norec'))
            ->where('statusenabled', true)
            ->first();

        if (!$file) {
            abort(404, 'File PP tidak ditemukan.');
        }

        $path = public_path('berkas-pbj/pp/' . basename((string) $file->namafile));
        if (!is_file($path)) {
            abort(404, 'Berkas fisik PP tidak ditemukan.');
        }

        return [$file, $path];
    }

    private function getPpSiapBapp(string $pbjNorec, int $profileId)
    {
        return DB::table('pppengadaan_t as pp')
            ->join('hpspengadaan_t as hp', function ($join) {
                $join->on(DB::raw('CAST(hp.norec AS TEXT)'), '=', 'pp.hpsfk');
            })
            ->join('pengajuanpbj_t as ppbj', function ($join) {
                $join->on(DB::raw('CAST(ppbj.norec AS TEXT)'), '=', 'pp.pbjfk');
            })
            ->where('pp.kdprofile', $profileId)
            ->where('pp.pbjfk', $pbjNorec)
            ->where('pp.statusenabled', true)
            ->where('hp.kdprofile', $profileId)
            ->where('hp.statusenabled', true)
            ->where('ppbj.statusenabled', true)
            ->whereExists(function ($query) use ($profileId) {
                $query->select(DB::raw(1))
                    ->from('pppengadaanfile_t as ppf')
                    ->whereRaw('ppf.ppfk = CAST(pp.norec AS TEXT)')
                    ->where('ppf.kdprofile', $profileId)
                    ->where('ppf.statusenabled', true);
            })
            ->first([
                'pp.norec as norecpp',
                'pp.pbjfk',
                'pp.hpsfk',
                'pp.nomorpp',
                'pp.tanggalpp',
                'pp.bataspemasukan',
                'hp.nourut',
                'hp.tahun',
            ]);
    }

    private function ensureBappAktif($pp, int $profileId)
    {
        $existing = DB::table('bapppengadaan_t')
            ->where('kdprofile', $profileId)
            ->where('pbjfk', $pp->pbjfk)
            ->where('statusenabled', true)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        DB::table('pppengadaan_t')
            ->where('norec', $pp->norecpp)
            ->lockForUpdate()
            ->first();

        $existing = DB::table('bapppengadaan_t')
            ->where('kdprofile', $profileId)
            ->where('pbjfk', $pp->pbjfk)
            ->where('statusenabled', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        $norec = (string) Str::uuid();
        DB::table('bapppengadaan_t')->insert([
            'kdprofile' => $profileId,
            'statusenabled' => true,
            'norec' => $norec,
            'pbjfk' => $pp->pbjfk,
            'hpsfk' => $pp->hpsfk,
            'ppfk' => $pp->norecpp,
            'nomorbapp' => sprintf('HTD.BAPP/%03d/UMRO/%d', (int) $pp->nourut, (int) $pp->tahun),
            'tanggalbapp' => null,
            'createdby' => $this->getPegawaiId(),
            'updatedby' => $this->getPegawaiId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('bapppengadaan_t')->where('norec', $norec)->first();
    }

    private function getHpsAktif(string $pbjNorec, int $profileId)
    {
        return DB::table('hpspengadaan_t as hp')
            ->join('pengajuanpbj_t as ppbj', 'ppbj.norec', '=', 'hp.pbjfk')
            ->where('hp.kdprofile', $profileId)
            ->where('hp.pbjfk', $pbjNorec)
            ->where('hp.statusenabled', true)
            ->where('ppbj.statusenabled', true)
            ->first([
                'hp.norec',
                'hp.pbjfk',
                'hp.nourut',
                'hp.tahun',
                'hp.nomorhps',
            ]);
    }

    private function nilaiHarga($nilai): float
    {
        if (is_int($nilai) || is_float($nilai)) {
            return (float) $nilai;
        }

        $angka = preg_replace('/[^0-9-]/', '', (string) $nilai);

        return is_numeric($angka) ? (float) $angka : 0;
    }
}
