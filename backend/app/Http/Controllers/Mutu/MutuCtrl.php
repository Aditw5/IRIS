<?php

namespace App\Http\Controllers\Mutu;

use App\Http\Controllers\Controller;
use App\Models\Standar\HistorySurvey;
use App\Models\Standar\Pelatihan;
use App\Models\Transaksi\PengajuanPelatihan;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use setasign\Fpdi\Fpdi;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MutuCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    private function applyCustomerSurveyCoreFilters($query, Request $request, string $registrationAlias = 'mtr')
    {
        $query
            ->whereRaw("COALESCE({$registrationAlias}.isstandarulab, false) = false")
            ->whereRaw("COALESCE({$registrationAlias}.iskalibrasiinternal, false) = false");

        $year = $request->input('year', $request->input('tglAwal'));
        if (!empty($year)) {
            $query->whereRaw("TO_CHAR({$registrationAlias}.tglregistrasi, 'YYYY') = ?", [(string)$year]);
        }

        $locationId = $request->input('lokasi_id');
        if (in_array((string)$locationId, ['1', '2'], true)) {
            $query->whereRaw(
                "CASE WHEN LOWER(COALESCE({$registrationAlias}.jenisorder, '')) = 'repair'
                    THEN {$registrationAlias}.lokasirepair
                    ELSE {$registrationAlias}.lokasikalibrasi
                END = ?",
                [(int)$locationId]
            );
        }

        return $query;
    }

    private function applyCustomerSurveyRequestFilters(
        $query,
        Request $request,
        string $registrationAlias = 'mtr',
        string $unitAlias = 'mt'
    ) {
        $this->applyCustomerSurveyCoreFilters($query, $request, $registrationAlias);

        if ($request->filled('unit_id')) {
            $query->where("{$registrationAlias}.nomitrafk", (int)$request->input('unit_id'));
        }

        if ($request->filled('jenis_order') && $request->input('jenis_order') !== 'all') {
            $query->whereRaw(
                "LOWER(COALESCE({$registrationAlias}.jenisorder, '')) = ?",
                [strtolower((string)$request->input('jenis_order'))]
            );
        }

        $surveyStatus = strtolower((string)$request->input('survey_status', 'all'));
        if ($surveyStatus === 'sudah') {
            $query->whereNotNull("{$registrationAlias}.isikepuasanpelanggan");
        } else if ($surveyStatus === 'belum') {
            $query->whereNull("{$registrationAlias}.isikepuasanpelanggan");
        }

        $search = trim((string)$request->input('search', ''));
        if ($search !== '') {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($subQuery) use ($searchTerm, $registrationAlias, $unitAlias) {
                $subQuery->where("{$unitAlias}.namaperusahaan", 'ilike', $searchTerm)
                    ->orWhere("{$registrationAlias}.nopendaftaran", 'ilike', $searchTerm)
                    ->orWhere("{$registrationAlias}.namapenanggungjawab", 'ilike', $searchTerm)
                    ->orWhere("{$registrationAlias}.nohppenanggungjawab", 'ilike', $searchTerm);
            });
        }

        return $query;
    }

    public function getHistory(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.keterangan',
                'mtrd.noorderalat',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtrd.bintangpenilaian',
            )
            ->where('mtr.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereNotNull('mtrd.bintangpenilaian');

        $this->applyCustomerSurveyRequestFilters($data, $r);
        $data = $data->get();

        $rata2 = $data->avg('bintangpenilaian');

        $result = [
            'length'       => $data->count(),
            'rata2Bintang' => round((float) $rata2, 2),
            'detail'       => $data,
            'as'           => '@aditwiran19@gmail.com'
        ];

        return $this->respond($result);
    }

    public function getPenilaianPelanggan(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('lokasikalibrasi_m as lkk', 'lkk.id', '=', 'mtr.lokasikalibrasi')
            ->leftJoin('lokasikalibrasi_m as lkr', 'lkr.id', '=', 'mtr.lokasirepair')
            ->select(
                'mtr.norec',
                'mt.id as idunit',
                'mt.namaperusahaan',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtr.iskalibrasiinternal',
                'mtr.isikepuasanpelanggan',
                'mtr.namapenanggungjawab',
                'mtr.nohppenanggungjawab',
                'mtr.jabatanpenanggungjawab',
                DB::raw("CASE WHEN LOWER(COALESCE(mtr.jenisorder, '')) = 'repair'
                    THEN COALESCE(lkr.id, lkk.id)
                    ELSE COALESCE(lkk.id, lkr.id)
                END as lokasi_id"),
                DB::raw("CASE WHEN LOWER(COALESCE(mtr.jenisorder, '')) = 'repair'
                    THEN COALESCE(lkr.lokasi, lkk.lokasi, '-')
                    ELSE COALESCE(lkk.lokasi, lkr.lokasi, '-')
                END as lokasi")
            )
            ->where('mtr.statusenabled', true)
            ->where('mt.statusenabled', true)
            ->whereNotNull('mtr.nopendaftaran');

        $this->applyCustomerSurveyRequestFilters($data, $r);
        $data = $data->orderByDesc('mtr.tglregistrasi')->get();

        $registrationIds = $data->pluck('norec')->filter()->values();

        $dataDetail = DB::table('mitraregistrasidetail_t as mtrd')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->select(
                'mtrd.norec as norec_detail',
                'mtrd.noregistrasifk',
                'mtrd.keterangan',
                'mtrd.noorderalat',
                'mmp.namaproduk',
                'mtrd.ulasanpenilaian',
                'mtrd.bintangpenilaian',
            )
            ->where('mmp.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereIn('mtrd.noregistrasifk', $registrationIds)
            ->get()
            ->groupBy('noregistrasifk');

        $detailAll = DB::table('mitraregistrasidetail_t')
            ->where('statusenabled', true)
            ->whereIn('noregistrasifk', $registrationIds)
            ->get()
            ->groupBy('noregistrasifk');

        foreach ($data as $item) {
            $detailList = $detailAll[$item->norec] ?? collect();
            $item->jumlahdetail = $detailList->count();
            $item->jumlahselesai = 0;

            foreach ($detailList as $detail) {
                if (
                    ($item->jenisorder === 'kalibrasi' && $detail->tglsetujumanagerlembarkerja !== null) ||
                    ($item->jenisorder === 'repair' && $detail->tglsetujumanagerlaporanrepair !== null)
                ) {
                    $item->jumlahselesai++;
                }
            }
            $item->jumlahbelumselesai = $item->jumlahdetail - $item->jumlahselesai;

            $item->detail = [];
            foreach (($dataDetail[$item->norec] ?? collect()) as $dd) {
                $item->detail[] = [
                    'norec_detail'     => $dd->norec_detail,
                    'namaproduk'       => $dd->namaproduk,
                    'noorderalat'      => $dd->noorderalat,
                    'bintangpenilaian' => $dd->bintangpenilaian,
                    'keterangan'       => $dd->keterangan,
                    'ulasanpenilaian'  => $dd->ulasanpenilaian,
                ];
            }
            $ratings = array_filter(
                array_column($item->detail, 'bintangpenilaian'),
                fn($v) => $v !== null && is_numeric($v)
            );
            if (count($ratings) > 0) {
                $avg = array_sum($ratings) / count($ratings);
                $item->rata2Bintang = round($avg, 2);
            } else {
                $item->rata2Bintang = null;
            }
        }

        $progressStatus = strtolower((string)$r->input('progress_status', 'selesai'));
        if (in_array($progressStatus, ['selesai', 'proses'], true)) {
            $data = $data->filter(function ($item) use ($progressStatus) {
                $isComplete = (int)$item->jumlahdetail > 0
                    && (int)$item->jumlahselesai === (int)$item->jumlahdetail;
                return $progressStatus === 'selesai' ? $isComplete : !$isComplete;
            })->values();
        }

        $ratings = $data->flatMap(function ($item) {
            return collect($item->detail)->pluck('bintangpenilaian');
        })->filter(fn($value) => $value !== null && is_numeric($value));
        $totalRegistrasi = $data->count();
        $totalIsiSurvey = $data->filter(fn($item) => $item->isikepuasanpelanggan !== null)->count();
        $ratingDistribution = collect(range(1, 5))->map(function ($star) use ($ratings) {
            return [
                'label' => $star . ' Bintang',
                'value' => $ratings->filter(fn($rating) => (int)round((float)$rating) === $star)->count(),
            ];
        })->values();

        $byUnit = $data->groupBy('idunit')->map(function ($rows) {
            $unitRatings = $rows->pluck('rata2Bintang')->filter(fn($value) => $value !== null && is_numeric($value));
            return [
                'unit_id' => $rows->first()->idunit,
                'unit' => $rows->first()->namaperusahaan,
                'total' => $rows->count(),
                'sudah' => $rows->filter(fn($item) => $item->isikepuasanpelanggan !== null)->count(),
                'rating' => $unitRatings->count() ? round((float)$unitRatings->avg(), 2) : 0,
            ];
        })->sortByDesc('rating')->values();

        $byMonth = $data->groupBy(function ($item) {
            return Carbon::parse($item->tglregistrasi)->format('Y-m');
        })->map(function ($rows, $month) {
            return [
                'month' => $month,
                'total' => $rows->count(),
                'sudah' => $rows->filter(fn($item) => $item->isikepuasanpelanggan !== null)->count(),
                'belum' => $rows->filter(fn($item) => $item->isikepuasanpelanggan === null)->count(),
            ];
        })->sortBy('month')->values();

        $filteredRegistrationIds = $data->pluck('norec')->filter()->values();
        $attributeSummary = DB::table('surveydetailpelanggan_t as s')
            ->join('surveypelanggan_t as sp', 's.surveyfk', '=', 'sp.norec')
            ->select(
                's.no',
                DB::raw("AVG(NULLIF(s.harapan, '')::numeric) as avg_harapan"),
                DB::raw("AVG(NULLIF(s.kepuasan, '')::numeric) as avg_kepuasan")
            )
            ->where('s.statusenabled', true)
            ->where('sp.statusenabled', true)
            ->whereIn('sp.registrasifk', $filteredRegistrationIds)
            ->groupBy('s.no')
            ->orderBy('s.no')
            ->get();

        $unitOptionsQuery = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->where('mtr.statusenabled', true)
            ->where('mt.statusenabled', true)
            ->whereNotNull('mtr.nopendaftaran');
        $this->applyCustomerSurveyCoreFilters($unitOptionsQuery, $r);
        $unitOptions = $unitOptionsQuery
            ->select('mt.id as value', 'mt.namaperusahaan as label')
            ->distinct()
            ->orderBy('mt.namaperusahaan')
            ->get();

        $result = [
            'length'       => $data->count(),
            'detail'       => $data,
            'summary'      => [
                'totalRegistrasi' => $totalRegistrasi,
                'totalIsiSurvey' => $totalIsiSurvey,
                'totalBelumSurvey' => max($totalRegistrasi - $totalIsiSurvey, 0),
                'persentase' => $totalRegistrasi > 0
                    ? round(($totalIsiSurvey / $totalRegistrasi) * 100, 2)
                    : 0,
                'rata2Bintang' => $ratings->count() ? round((float)$ratings->avg(), 2) : 0,
            ],
            'charts'       => [
                'rating_distribution' => $ratingDistribution,
                'by_unit' => $byUnit,
                'by_month' => $byMonth,
                'attributes' => $attributeSummary,
            ],
            'filters'      => [
                'units' => $unitOptions,
            ],
            'as'           => '@aditwiran19@gmail.com'
        ];

        return $this->respond($result);
    }

    public function getSurveyPelangganChart(Request $r)
    {
        $query = DB::table('surveydetailpelanggan_t as s')
            ->join('surveypelanggan_t as sp', 's.surveyfk', '=', 'sp.norec')
            ->leftJoin('mitraregistrasi_t as mtr', 'sp.registrasifk', '=', 'mtr.norec')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->select(
                's.no',
                DB::raw("AVG(NULLIF(s.harapan, '')::numeric) as avg_harapan"),
                DB::raw("AVG(NULLIF(s.kepuasan, '')::numeric) as avg_kepuasan")
            )
            ->where('s.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mt.statusenabled', true);

        $this->applyCustomerSurveyRequestFilters($query, $r);

        if ($r->has('registrasifk') && $r->registrasifk) {
            $query->where('mtr.norec', $r->registrasifk);
        }

        $data = $query
            ->groupBy('no')
            ->orderBy('no')
            ->get();

        return $this->respond([
            'detail' => $data,
        ]);
    }

    public function getSurveyPelangganCoverage(Request $r)
    {
        $totalRegistrasi = DB::table('mitraregistrasi_t as mr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mr.nomitrafk')
            ->where('mr.statusenabled', true)
            ->where('mt.statusenabled', true);
        $this->applyCustomerSurveyRequestFilters($totalRegistrasi, $r, 'mr');
        $totalRegistrasi = $totalRegistrasi->count();

        $queryIsi = DB::table('mitraregistrasi_t as mr')
            ->join('surveypelanggan_t as sp', 'sp.registrasifk', '=', 'mr.norec')
            ->join('mitra_m as mt', 'mt.id', '=', 'mr.nomitrafk')
            ->where('mr.statusenabled', true)
            ->where('mt.statusenabled', true)
            ->where('sp.statusenabled', true)
            ->distinct('mr.norec');
        $this->applyCustomerSurveyRequestFilters($queryIsi, $r, 'mr');

        $totalIsiSurvey = $queryIsi->count('mr.norec');
        $totalBelum = max($totalRegistrasi - $totalIsiSurvey, 0);
        $persentase = $totalRegistrasi > 0
            ? round(($totalIsiSurvey / $totalRegistrasi) * 100, 2)
            : 0;

        return $this->respond([
            'totalRegistrasi'    => $totalRegistrasi,
            'totalIsiSurvey'     => $totalIsiSurvey,
            'totalBelumSurvey'   => $totalBelum,
            'persentase'         => $persentase,
        ]);
    }

    public function masterIsiSurvey(Request $request)
    {
        $dataRaw = DB::table('historysurvey_m as hts')
            ->where('hts.statusenabled', true)
            ->select(
                'hts.id as key',
                'hts.kdrincianisisurvey',
                'hts.namasurvey as label',
                'hts.nourut',
                'hts.kodeexternal',
                'hts.keterangan',
                'hts.fungsi',
                'hts.isisurvey',
            )
            ->orderBy('hts.nourut');
        $dataRaw = $dataRaw->get();
        $dataraw3 = [];
        foreach ($dataRaw as $dataRaw2) {
            if ($dataRaw2->kodeexternal == 'H') {
                $dataraw3[] = array(
                    'key' => $dataRaw2->key,
                    'label' => $dataRaw2->label,
                    'data' => $dataRaw2,
                    'nourut' => $dataRaw2->nourut,
                    'isidokumen' => $dataRaw2->isisurvey,
                    'parent_id' => 0,
                    'icon' => 'pi pi-fw pi-folder',
                );
            } else {
                if ($dataRaw2->kodeexternal != 'H') {
                    $dataraw3[] = array(
                        'key' => $dataRaw2->key,
                        'label' => $dataRaw2->label,
                        'data' => $dataRaw2,
                        'nourut' => $dataRaw2->nourut,
                        'isidokumen' => $dataRaw2->isisurvey,
                        'parent_id' => $dataRaw2->kdrincianisisurvey,
                        'icon' => 'pi pi-fw pi-folder',
                    );
                } else {
                    $dataraw3[] = array(
                        'key' => $dataRaw2->key,
                        'label' => $dataRaw2->label,
                        'data' => $dataRaw2,
                        'nourut' => $dataRaw2->nourut,
                        'isidokumen' => $dataRaw2->isisurvey,
                        'parent_id' => $dataRaw2->kdrincianisisurvey,
                        'icon' => 'pi pi-fw pi-folder',
                    );
                }
            }
        }
        $data = $dataraw3;

        function recursiveElements($data)
        {
            $elements = [];
            $tree = [];
            foreach ($data as &$element) {
                $id = $element['key'];
                $parent_id = $element['parent_id'];

                $elements[$id] = &$element;
                if (isset($elements[$parent_id])) {
                    $elements[$parent_id]['children'][] = &$element;
                } else {
                    if ($parent_id <= 10) {
                        $tree[] = &$element;
                    }
                }
            }
            return $tree;
        }
        $res['data'] = $dataRaw;
        $res['tree'] = recursiveElements($data);
        return $this->respond($res);
    }

    public function lastNourutHistorySurvey(Request $r)
    {
        $dataPS = HistorySurvey::where('statusenabled', true)->orderByDesc('nourut')->first();
        return $this->respond($dataPS);
    }

    public function saveIsiHistorySurvey(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();
        try {
            $filename = null;
            if ($request->hasFile('fileDokumenSurvey')) {
                $file = $request->file('fileDokumenSurvey');
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa gambar (jpg, jpeg, png, webp), PDF, Word (doc, docx), atau Excel (xls, xlsx).");
                }

                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move(public_path('berkas-mutu'), $filename);
            } else {
                $filename = $request['namaFileLama'] ?? null;
            }

            if ($request['id'] == '') {
                $isidokumen = new HistorySurvey();
                $newID =  $this->SEQUENCE_MASTER(new HistorySurvey(), 'id', $this->kdProfile);
                $isidokumen->id = $newID;
                $isidokumen->kdprofile = $kdProfile;
                $isidokumen->statusenabled = true;
            } else {
                $isidokumen = HistorySurvey::where('id', $request['id'])->first();
                $newID = $isidokumen->id;
            }

            $isidokumen->kodeexternal = empty($request['kdrincianisisurvey']) ? 'H' : null;
            $isidokumen->kdisisurvey = $newID;
            $isidokumen->keterangan = $request['keterangan'];
            $isidokumen->namasurvey = $request['namasurvey'];
            $isidokumen->nourut = $request['nourut'];
            $kdrinci = $request->has('kdrincianisisurvey') && $request['kdrincianisisurvey'] !== '' ? $request['kdrincianisisurvey'] : null;
            $isidokumen->kdrincianisisurvey = $kdrinci;
            $isidokumen->isisurvey = $filename;
            $isidokumen->save();

            DB::commit();
            $transMessage = "Sukses ";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => '@aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Simpan Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => '@aditwiran19@gmail.com',
                    "ex" => $e->getMessage() . ' ' . $e->getLine(),
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function cetakDokumenSurvey(Request $request)
    {
        $data = DB::table('historysurvey_m')
            ->where('id', $request['id'])
            ->first();

        if (!$data || !$data->isisurvey) {
            abort(404, 'Data Dokumen atau file tidak ditemukan');
        }

        $filename = basename($data->isisurvey);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, ['doc', 'docx'])) {
            $filepath = $this->publicFileUrl('berkas-mutu', $filename);
        } else {
            $filepath = $this->publicFileUrl('berkas-mutu', $filename);
        }
        return view('report.mutu.view-pdf', compact('filepath', 'data', 'extension'));
    }

    public function hapusIsiSurvey(Request $request)
    {
        DB::beginTransaction();
        try {

            HistorySurvey::where('id', $request['id'])->update([
                'statusenabled' => false
            ]);

            DB::commit();
            $transMessage = "Sukses ";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Hapus Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getAnalisiAISurvey(Request $r)
    {
        $dataSurvey = DB::table('surveypelanggan_t as st')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'st.registrasifk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
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
            ->where('mtr.statusenabled', true)
            ->where('mt.statusenabled', true);

        $this->applyCustomerSurveyRequestFilters($dataSurvey, $r);

        $dataSurvey = $dataSurvey->get();

        $dataDetailSurvey = DB::table('surveydetailpelanggan_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('surveyfk');

        foreach ($dataSurvey as $item) {
            $item->detailSurvey = $dataDetailSurvey[$item->norec] ?? [];
        }

        $prompt = "Berikan analisis menyeluruh terhadap data survei pelanggan berikut secara singkat.  
                   Sebutkan berapa jumlah total responden dan kesimpulan umum dari data tersebut tanpa menyebutkan nama atau detail per responden.  
                   Misalnya, Terdapat 2 responden dengan dominasi laki-laki, dengan kesimpulan berikut: ....  
                   Terakhir, berikan juga saran perbaikan yang relevan berdasarkan data survei.  
                   Jangan buat analisis panjang-panjang, cukup ringkas dan jelas..\n\n";
        $prompt .= json_encode($dataSurvey, JSON_PRETTY_PRINT);
        $analisisGemini = $this->callGeminiApi($prompt);

        $result = [
            'datasurvey'       => $dataSurvey,
            'analisisGemini' => $analisisGemini,
            'as'           => '@aditwiran19@gmail.com'
        ];

        return $this->respond($result);
    }

    private function callGeminiApi(string $prompt): ?string
    {
        $apiKey = $this->settingFix('apiKeyGemini');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $prompt
                        ]
                    ]
                ]
            ]
        ];

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post($url, [
                'headers' => [
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody(), true);

            Log::debug('Gemini API response body:', $body);

            if (isset($body['candidates'][0]['content'])) {
                $content = $body['candidates'][0]['content'];
                Log::debug('Gemini API content:', ['content' => $content, 'type' => gettype($content)]);

                if (is_string($content)) {
                    return $content;
                } else {
                    Log::error('Gemini API content is not string but ' . gettype($content));
                    return json_encode($content);
                }
            } else {
                Log::error('Gemini API response missing candidates content');
            }
        } catch (\Exception $e) {
            Log::error("Gemini API call failed: " . $e->getMessage());
        }

        return null;
    }

    public function kirimPeringatanIsiSurvey(Request $r)
    {
        DB::beginTransaction();
        try {
            $PD = $r['survey'];
            $regis = DB::table('mitraregistrasi_t')->where('norec', $PD['norec'])->first();

            if (!$regis) {
                throw new \Exception('Data pendaftaran tidak ditemukan.');
            }

            if (DB::table('mitraregistrasi_t')
                ->where('norec', $PD['norec'])
                ->whereRaw('COALESCE(iskalibrasiinternal, false) = true')
                ->exists()) {
                throw new \Exception('Pengingat survey tidak berlaku untuk order kalibrasi internal.');
            }

            $customerId = $regis->customerfk ?? null;
            if ($customerId) {
                $customer = DB::table('users')->where('id', $customerId)->first();
                $nohpCustomer = $customer->nowa ?? ($regis->nohppenanggungjawab ?? null);
                $namaCustomer = $customer->name ?? ($regis->namapenanggungjawab ?? 'Customer');
                $nopendaftaran = $regis->nopendaftaran ?? '-';
                $link = 'https://ulabumro.id/module/customer/detail-registrasi'
                    . '?norec_pd=' . $PD['norec'];
                $shortLink = $this->shortLink($link);
                $unit = '-';
                if ($customer && $customer->mitrafk) {
                    $mitra = DB::table('mitra_m')
                        ->whereRaw('CAST(id AS INTEGER) = ?', [$customer->mitrafk])
                        ->first();
                    $unit = $mitra->namaperusahaan ?? '-';
                }

                $pesanCustomer = "Yth. $namaCustomer,\n\n"
                    . "Terima kasih ata kepercayaan Anda kepada U-LAB untuk order No. Pendaftaran: *$nopendaftaran* di $unit.\n\n"
                    . "Kami berkomitmen untuk terus meningkatkan kualitas layanan kami, dan masukan Anda sangat berarti. "
                    . "Mohon kesediaan Bapak/Ibu untuk mengisi survei singkat berikut:\n$shortLink\n\n"
                    . "Waktu Anda hanya sekitar 1 menit, namun akan membantu kami melayani Anda dengan lebih baik di masa mendatang.\n\n"
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

                if ($nohpCustomer) {
                    $this->kirimWhatsappNotifikasi($nohpCustomer, $pesanCustomer);
                }
            }

            $transMessage = "Kirim Peringatan Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@adit',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Kirim Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function shortLink($longUrl)
    {
        $response = Http::get('https://tinyurl.com/api-create.php?url=' . urlencode($longUrl));
        if ($response->successful()) {
            return trim($response->body());
        }
        return $longUrl;
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

    public function saveMasterPelatihan(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();
        try {
            if ($request['id'] == '') {
                $pelatihan = new Pelatihan();
                $newID =  $this->SEQUENCE_MASTER(new Pelatihan(), 'id', $this->kdProfile);
                $pelatihan->id = $newID;
                $pelatihan->kdprofile = $kdProfile;
                $pelatihan->statusenabled = true;
            } else {
                $pelatihan = Pelatihan::where('id', $request['id'])->first();
                $newID = $pelatihan->id;
            }

            $pelatihan->judulpelatihan = $request['judul'] ?? null;
            $pelatihan->periodependaftaranawal = $request['pendaftaranMulai'] ?? null;
            $pelatihan->periodependaftaranakhir = $request['pendaftaranSelesai'] ?? null;
            $pelatihan->tglpelatihan = $request['tglPelatihan'] ?? null;
            $pelatihan->deskripsi = $request['deskripsi'] ?? null;
            $pelatihan->jenispelatihanfk = $request['jenispelatihanfk'] ?? null;
            $pelatihan->lembaga = $request['lembaga'] ?? null;
            $pelatihan->save();

            DB::commit();

            $jenisPegawaiTujuan = [1, 2, 3, 13, 14];
            $pegawaiList = DB::table('pegawai_m')
                ->whereIn('objectjenispegawaifk', $jenisPegawaiTujuan)
                ->where('statusenabled', true)
                ->whereNotNull('nohandphone')
                ->where('nohandphone', '<>', '')
                ->get();

            $namaPelatihan = $pelatihan->judulpelatihan ?? '-';
            $periodeAwal   = $pelatihan->periodependaftaranawal ?? '-';
            $periodeAkhir  = $pelatihan->periodependaftaranakhir ?? '-';
            $tglPelatihan  = $pelatihan->tglpelatihan ?? '-';

            foreach ($pegawaiList as $pg) {
                $nohp = $pg->nohandphone;
                $nama = $pg->namalengkap ?? 'Bapak/Ibu';
                if (empty($nohp)) {
                    continue;
                }
                $pesan = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $nama\n\n"
                    . "Kami informasikan bahwa telah tersedia *Pelatihan Baru* dari Tim Mutu:\n\n"
                    . "📘 *Nama Pelatihan* : $namaPelatihan\n"
                    . "🗓 *Periode Pendaftaran* : $periodeAwal s.d $periodeAkhir\n"
                    . "⏰ *Waktu Pelaksanaan* : $tglPelatihan\n\n"
                    . "Silakan melihat dan mengajukan pelatihan melalui menu *List Pengajuan Pelatihan* pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohp, $pesan);
            }

            $transMessage = "Sukses ";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => '@aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Simpan Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => '@aditwiran19@gmail.com',
                    "ex" => $e->getMessage() . ' ' . $e->getLine(),
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getListPelatihan(Request $r)
    {
        $data = DB::table('pelatihan_m as plt')
            ->leftJoin('jenispelatihan_m as jm', 'jm.id', '=', 'plt.jenispelatihanfk')
            ->select(
                'plt.id',
                'plt.judulpelatihan',
                'plt.periodependaftaranawal',
                'plt.periodependaftaranakhir',
                'plt.tglpelatihan',
                'plt.deskripsi',
                'jm.jenispelatihan',
                'jm.id as idjenispelatihan',
            )
            ->where('plt.statusenabled', true)
            ->where('jm.statusenabled', true)
            ->where('plt.ismanual', null);

        $page = 1;
        if (isset($r['page']) && $r['page'] != '') {
            $page = $r['page'];
        }
        $data = $data->orderBy('plt.tglpelatihan', 'asc');
        $data = $data->paginate(isset($r['limit']) ? $r['limit'] : 10, ['*'], 'page', $page);

        return $this->respond($data);
    }

    public function savePengajuanPelatihan(Request $request)
    {
        DB::beginTransaction();
        try {
            $pelatihanId = $request->input('pelatihanId');
            if (!$pelatihanId) {
                throw new \Exception('pelatihanId wajib diisi');
            }

            $pegawaiId = $this->getPegawaiId();
            $sudahAda = PengajuanPelatihan::where('pelatihanfk', $pelatihanId)
                ->where('pengajufk', $pegawaiId)
                ->where('statusenabled', true)
                ->exists();

            if ($sudahAda) {
                DB::rollBack();
                $transMessage = "Pengajuan untuk pelatihan ini sedang diajukan";
                $result = [
                    "status" => 409,
                    "result" => [
                        "as"      => '@aditwiran19@gmail.com',
                        "message" => "Pengajuan untuk pelatihan ini sudah pernah diajukan.",
                    ],
                ];
                return $this->respond($result['result'], $result['status'], $transMessage);
            }

            $pengajuan = new PengajuanPelatihan();
            $pengajuan->norec        = $pengajuan->generateNewId();
            $pengajuan->pelatihanfk  = $pelatihanId;
            $pengajuan->pengajufk    = $pegawaiId;
            $pengajuan->statusenabled = true;
            if (in_array('tglpengajuan', $pengajuan->getFillable() ?? [])) {
                $pengajuan->tglpengajuan = now();
            }
            $pengajuan->save();

            DB::commit();

            $data = DB::table('orderpelatihan_t as oplt')
                ->leftJoin('pelatihan_m as plt', 'plt.id', '=', 'oplt.pelatihanfk')
                ->join('pegawai_m as pg', 'pg.id', '=', 'oplt.pengajufk')
                ->select(
                    'plt.judulpelatihan',
                    'plt.tglpelatihan',
                    'pg.namalengkap',
                    'oplt.created_at'
                )
                ->where('oplt.statusenabled', true)
                ->where('oplt.norec', $pengajuan->norec)
                ->first();

            $jenisPegawaiMutu = $this->settingFix('idJenisPegawaiMutu');
            $rowPengajuan = $data;
            $mutuList = DB::table('pegawai_m')
                ->where('objectjenispegawaifk', $jenisPegawaiMutu)
                ->where('statusenabled', true)
                ->whereNotNull('nohandphone')
                ->where('nohandphone', '<>', '')
                ->get();
            $waktuPengajuan = now();
            $judulPelatihan = $rowPengajuan->judulpelatihan ?? '-';
            $namaPengaju    = $rowPengajuan->namalengkap ?? '-';

            foreach ($mutuList as $mutu) {
                $nohpMutu  = $mutu->nohandphone ?? null;
                $namaMutu  = $mutu->namalengkap ?? '-';
                if (empty($nohpMutu)) {
                    continue;
                }
                $pesanMutu = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaMutu\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Telah diajukan Pelatihan $judulPelatihan oleh $namaPengaju\n"
                    . "Pada $waktuPengajuan\n\n"
                    . "Silahkan cek dan review list pengajuan pelatihan pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpMutu, $pesanMutu);
            }

            $transMessage = "Sukses ";
            $result = [
                "status" => 200,
                "result" => [
                    "as"    => '@aditwiran19@gmail.com',
                    "norec" => $pengajuan->norec,
                ],
            ];
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Simpan Gagal";
            $result = [
                "status" => 400,
                "result" => [
                    "as" => '@aditwiran19@gmail.com',
                    "ex" => $e->getMessage() . ' ' . $e->getLine(),
                ],
            ];
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function saveRiwayatPelatihanManual(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();

        try {
            if (empty($request['pegawaifk'])) {
                throw new \Exception('Pegawai wajib dipilih');
            }
            if (empty($request['judul'])) {
                throw new \Exception('Judul pelatihan wajib diisi');
            }
            if (empty($request['pendaftaranMulai']) || empty($request['pendaftaranSelesai'])) {
                throw new \Exception('Periode pendaftaran wajib diisi');
            }
            if (empty($request['tglPelatihan'])) {
                throw new \Exception('Tanggal pelatihan wajib diisi');
            }
            if (empty($request['jenispelatihanfk'])) {
                throw new \Exception('Jenis pelatihan wajib dipilih');
            }

            $pegawai = DB::table('pegawai_m')
                ->where('id', $request['pegawaifk'])
                ->where('statusenabled', true)
                ->first();

            if (!$pegawai) {
                throw new \Exception('Pegawai tidak ditemukan');
            }

            $jenisPelatihan = DB::table('jenispelatihan_m')
                ->where('id', $request['jenispelatihanfk'])
                ->where('statusenabled', true)
                ->first();

            if (!$jenisPelatihan) {
                throw new \Exception('Jenis pelatihan tidak ditemukan');
            }

            $namaJenisPelatihan = strtoupper(trim($jenisPelatihan->jenispelatihan ?? ''));

            if ($namaJenisPelatihan == 'EKSTERNAL' && empty($request['lembaga'])) {
                throw new \Exception('Lembaga wajib diisi untuk pelatihan eksternal');
            }

            $pelatihan = new Pelatihan();
            $newPelatihanId = $this->SEQUENCE_MASTER(new Pelatihan(), 'id', $kdProfile);

            $pelatihan->id = $newPelatihanId;
            $pelatihan->kdprofile = $kdProfile;
            $pelatihan->statusenabled = true;
            $pelatihan->judulpelatihan = $request['judul'] ?? null;
            $pelatihan->periodependaftaranawal = $request['pendaftaranMulai'] ?? null;
            $pelatihan->periodependaftaranakhir = $request['pendaftaranSelesai'] ?? null;
            $pelatihan->tglpelatihan = $request['tglPelatihan'] ?? null;
            $pelatihan->deskripsi = $request['deskripsi'] ?? null;
            $pelatihan->jenispelatihanfk = $request['jenispelatihanfk'] ?? null;
            $pelatihan->lembaga = $request['lembaga'] ?? null;
            $pelatihan->ismanual = true;
            $pelatihan->save();

            $pengajuan = new PengajuanPelatihan();
            $pengajuan->norec = $pengajuan->generateNewId();
            $pengajuan->pelatihanfk = $pelatihan->id;
            $pengajuan->pengajufk = $request['pegawaifk'];
            $pengajuan->statusenabled = true;
            $pengajuan->status = $request['status'] ?? 'Disetujui';
            $pengajuan->keteranganstatus = $request['keteranganstatus'] ?? 'Riwayat manual sebelum penggunaan web';

            if (isset($request['tglInputRiwayat']) && $request['tglInputRiwayat'] != '') {
                $pengajuan->created_at = $request['tglInputRiwayat'];
                $pengajuan->updated_at = $request['tglInputRiwayat'];
            }

            if (in_array('tglpengajuan', $pengajuan->getFillable() ?? [])) {
                $pengajuan->tglpengajuan = isset($request['tglInputRiwayat']) && $request['tglInputRiwayat'] != ''
                    ? $request['tglInputRiwayat']
                    : now();
            }

            if (property_exists($pengajuan, 'ismanual')) {
                $pengajuan->ismanual = true;
            }

            $pengajuan->save();

            DB::commit();

            $transMessage = "Sukses simpan riwayat manual";
            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@aditwiran19@gmail.com',
                    "norec" => $pengajuan->norec,
                    "pelatihanid" => $pelatihan->id,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            $transMessage = "Simpan riwayat manual gagal";
            $result = [
                "status" => 400,
                "result" => [
                    "as" => '@aditwiran19@gmail.com',
                    "message" => $e->getMessage(),
                    "ex" => $e->getMessage() . ' ' . $e->getLine(),
                ],
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getPengajuanPelatihan(Request $r)
    {
        $viewerId = $r->input('id');
        $isAdmin = false;
        if (!empty($viewerId) && $viewerId !== 'undefined') {
            $isAdmin = DB::table('pegawai_m')
                ->where('id', $viewerId)
                ->where('objectjenispegawaifk', 3)
                ->exists();
        }

        $data = DB::table('orderpelatihan_t as oplt')
            ->leftJoin('pelatihan_m as plt', 'plt.id', '=', 'oplt.pelatihanfk')
            ->join('pegawai_m as pg', 'pg.id', '=', 'oplt.pengajufk')
            ->leftJoin('jenispelatihan_m as jm', 'jm.id', '=', 'plt.jenispelatihanfk')
            ->select(
                'oplt.norec',
                'oplt.pelatihanfk',
                'oplt.pengajufk',
                'oplt.status',
                'oplt.created_at',
                'oplt.keteranganstatus',
                'plt.judulpelatihan',
                'plt.periodependaftaranawal',
                'plt.periodependaftaranakhir',
                'plt.tglpelatihan',
                'plt.lembaga',
                'pg.namalengkap',
                'jm.jenispelatihan',
                'jm.id as idjenispelatihan',
                DB::raw("
            CASE
                WHEN UPPER(COALESCE(jm.jenispelatihan, '')) = 'IHT' THEN 'PLN NUSANTARA POWER'
                WHEN UPPER(COALESCE(jm.jenispelatihan, '')) = 'PUBLIK' THEN 'PUBLIK'
                WHEN UPPER(COALESCE(jm.jenispelatihan, '')) = 'EKSTERNAL' THEN COALESCE(plt.lembaga, 'EKSTERNAL')
                ELSE COALESCE(plt.lembaga, jm.jenispelatihan, 'Pelatihan')
            END as lembaga
        ")
            )
            ->where('oplt.statusenabled', true);

        if (!$isAdmin && !empty($viewerId) && $viewerId !== 'undefined') {
            $data->where('oplt.pengajufk', '=', $viewerId);
        }

        $data = $data->orderByDesc('oplt.created_at')->get();

        return $this->respond($data);
    }

    public function hapusMasterPelatihan(Request $request)
    {
        DB::beginTransaction();
        try {

            Pelatihan::where('id', $request['id'])->update([
                'statusenabled' => false
            ]);

            DB::commit();
            $transMessage = "Sukses ";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Hapus Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function hapusPengajuanPelatihan(Request $request)
    {
        DB::beginTransaction();
        try {

            PengajuanPelatihan::where('norec', $request['norec'])->update([
                'statusenabled' => false
            ]);

            DB::commit();
            $transMessage = "Sukses ";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Hapus Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function saveApprovalPelatihan(Request $request)
    {
        DB::beginTransaction();
        try {

            PengajuanPelatihan::where('norec', $request['norec'])->update([
                'status' => $request['status'],
                'keteranganstatus' => $request['keteranganstatus']
            ]);

            DB::commit();

            $data = DB::table('orderpelatihan_t as oplt')
                ->leftJoin('pelatihan_m as plt', 'plt.id', '=', 'oplt.pelatihanfk')
                ->join('pegawai_m as pg', 'pg.id', '=', 'oplt.pengajufk')
                ->select(
                    'plt.judulpelatihan',
                    'pg.namalengkap',
                    'pg.nohandphone'
                )
                ->where('oplt.statusenabled', true)
                ->where('oplt.norec', $request['norec'])
                ->first();

            $rowPengajuan = $data;
            $judulPelatihan = $rowPengajuan->judulpelatihan ?? '-';
            $statusApproval = $request['status'] ?? '-';
            $keteranganApproval = $request['keteranganstatus'] ?? '-';

            $nohpPengaju  = $rowPengajuan->nohandphone ?? null;
            $namaPengaju  = $rowPengajuan->namalengkap ?? '-';
            $pesanPengaju = "Halo Tim Hebat U-LAB ! 👋\n\n"
                . "Yth. $namaPengaju\n"
                . "Kami informasikan bahwa,\n\n"
                . "Telah dilakukan Approval Pelatihan: $judulPelatihan oleh Team Mutu\n"
                . "Status Pengajuan adalah : $statusApproval, dengan keterangan $keteranganApproval \n\n"
                . "Silahkan cek dan review list pengajuan pelatihan pada aplikasi.\n\n"
                . "Salam,\n"
                . "U-LAB ! Cepat, Tepat, Akurat 💯";
            $this->kirimWhatsappNotifikasi($nohpPengaju, $pesanPengaju);

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

    public function getCvPegawai(Request $request)
    {
        try {
            $pegawaiId = $request->id ?? $this->getPegawaiId();

            if (!$pegawaiId) {
                return $this->respond([
                    'message' => 'Pegawai tidak ditemukan'
                ], 400, 'Gagal');
            }

            $cv = DB::table('cvpegawai_t as cv')
                ->where('cv.statusenabled', true)
                ->where('cv.pegawaifk', $pegawaiId)
                ->first();

            if ($cv) {
                $cv->pendidikanformal = $cv->pendidikanformal ? json_decode($cv->pendidikanformal) : [];
                $cv->prestasi = $cv->prestasi ? json_decode($cv->prestasi) : [];
                $cv->pengalamankerja = $cv->pengalamankerja ? json_decode($cv->pengalamankerja) : [];

                return $this->respond([
                    'cv' => $cv,
                    'pegawai' => null,
                ]);
            }

            $pegawai = DB::table('pegawai_m as pg')
                ->leftjoin('agama_m as ag', 'pg.objectagamafk', '=', 'ag.id')
                ->leftjoin('jeniskelamin_m as jk', 'pg.objectjeniskelaminfk', '=', 'jk.id')
                ->leftjoin('jenispegawai_m as jp', 'pg.objectjenispegawaifk', '=', 'jp.id')
                ->leftjoin('pendidikan_m as pe', 'pg.objectpendidikanterakhirfk', '=', 'pe.id')
                ->leftjoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
                ->leftjoin('statuspegawai_m as sp', 'sp.id', '=', 'pg.statuspegawaifk')
                ->select(
                    'pg.id',
                    'ag.agama',
                    'pg.objectagamafk',
                    'pg.objectjenispegawaifk',
                    'pe.pendidikan',
                    'pg.objectpendidikanterakhirfk',
                    'jk.jeniskelamin',
                    'pg.objectjeniskelaminfk',
                    'jp.jenispegawai',
                    'pg.namalengkap',
                    'pg.kodepos',
                    'pg.statusenabled',
                    'pg.tempatlahir',
                    'pg.email',
                    'pg.nohandphone',
                    'pg.notlp',
                    'pg.alamat',
                    'pg.tgllahir',
                    'pg.tglmasuk',
                    'pg.tglkeluar',
                    'pg.isfoto',
                    'pg.filename',
                    'pg.nid',
                    'pg.nik',
                    'pg.jabatan1fk',
                    'pg.statuspegawaifk',
                    'pg.gelarbelakang',
                    'pg.gelardepan',
                    'pg.isfotoPegawai',
                    'pg.filenameFoto',
                    'jb.namajabatanulab',
                    'jb.id as idjabatan'
                )
                ->where('pg.kdprofile', (int)$this->kdProfile)
                ->where('pg.statusenabled', true)
                ->where('pg.id', $pegawaiId)
                ->first();

            return $this->respond([
                'cv' => null,
                'pegawai' => $pegawai,
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'message' => $e->getMessage(),
                'ex' => $e->getMessage() . ' ' . $e->getLine(),
            ], 400, 'Gagal');
        }
    }

    public function saveCvPegawai(Request $request)
    {
        DB::beginTransaction();
        try {
            $pegawaiId = $request->pegawaifk ?? $this->getPegawaiId();

            if (!$pegawaiId) {
                throw new \Exception('Pegawai tidak ditemukan');
            }

            $pegawai = DB::table('pegawai_m')
                ->where('id', $pegawaiId)
                ->where('statusenabled', true)
                ->first();

            if (!$pegawai) {
                throw new \Exception('Data pegawai tidak ditemukan');
            }

            $existing = DB::table('cvpegawai_t')
                ->where('pegawaifk', $pegawaiId)
                ->where('statusenabled', true)
                ->first();

            $dataSave = [
                'pegawaifk' => $pegawaiId,
                'nama' => $request->nama,
                'tempatlahir' => $request->tempatLahir,
                'tanggallahir' => $request->tanggalLahir,
                'objectjeniskelaminfk' => $request->objectjeniskelaminfk,
                'jeniskelamin' => $request->jenisKelamin,
                'objectagamafk' => $request->objectagamafk,
                'agama' => $request->agama,
                'statuspernikahan' => $request->statusPernikahan,
                'warganegara' => $request->wargaNegara,
                'penguasaanbahasa' => $request->penguasaanBahasa,
                'alamatktp' => $request->alamatKtp,
                'alamatsekarang' => $request->alamatSekarang,
                'nohp' => $request->noHp,
                'email' => $request->email,
                'akunmediasosial' => $request->akunMediaSosial,
                'jabatansaatini' => $request->jabatanSaatIni,
                'kotattd' => $request->kotaTtd,
                'tanggalttd' => $request->tanggalTtd,
                'namattd' => $request->namaTtd,
                'pendidikanformal' => json_encode($request->pendidikanFormal ?? []),
                'prestasi' => json_encode($request->prestasi ?? []),
                'pengalamankerja' => json_encode($request->pengalamanKerja ?? []),
                'statusenabled' => true,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('cvpegawai_t')
                    ->where('id', $existing->id)
                    ->update($dataSave);
                $cvId = $existing->id;
            } else {
                $dataSave['created_at'] = now();
                $cvId = DB::table('cvpegawai_t')->insertGetId($dataSave);
            }

            DB::commit();

            return $this->respond([
                'id' => $cvId,
                'message' => 'CV pegawai berhasil disimpan',
            ], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond([
                'message' => $e->getMessage(),
                'ex' => $e->getMessage() . ' ' . $e->getLine(),
            ], 400, 'Simpan Gagal');
        }
    }

    public function getCvPegawaiRiwayatPelatihan(Request $request)
    {
        try {
            $pegawaiId = $request->id ?? $this->getPegawaiId();

            if (!$pegawaiId) {
                return $this->respond([], 200, 'Sukses');
            }

            $mulaiTahun = Carbon::now()->subYears(5)->startOfYear()->format('Y-m-d');

            $data = DB::table('orderpelatihan_t as oplt')
                ->leftJoin('pelatihan_m as plt', 'plt.id', '=', 'oplt.pelatihanfk')
                ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'oplt.pengajufk')
                ->leftJoin('jenispelatihan_m as jm', 'jm.id', '=', 'plt.jenispelatihanfk')
                ->select(
                    'oplt.norec',
                    'oplt.status',
                    'oplt.keteranganstatus',
                    'oplt.created_at',
                    'plt.judulpelatihan',
                    'plt.tglpelatihan',
                    'plt.lembaga',
                    'pg.namalengkap',
                    'jm.jenispelatihan',
                    DB::raw("EXTRACT(YEAR FROM plt.tglpelatihan) as tahun"),
                    DB::raw("
                   CASE
                WHEN UPPER(COALESCE(jm.jenispelatihan, '')) = 'IHT' THEN 'PLN NUSANTARA POWER'
                WHEN UPPER(COALESCE(jm.jenispelatihan, '')) = 'PUBLIK' THEN 'PUBLIK'
                WHEN UPPER(COALESCE(jm.jenispelatihan, '')) = 'EKSTERNAL' THEN COALESCE(plt.lembaga, 'EKSTERNAL')
                ELSE COALESCE(plt.lembaga, jm.jenispelatihan, 'Pelatihan')
            END as lembaga
                "),
                    DB::raw("COALESCE(plt.judulpelatihan, '-') as keterampilan")
                )
                ->where('oplt.statusenabled', true)
                ->where('plt.statusenabled', true)
                ->where('oplt.pengajufk', $pegawaiId)
                ->whereNotNull('plt.tglpelatihan')
                ->whereDate('plt.tglpelatihan', '>=', $mulaiTahun)
                ->orderBy('plt.tglpelatihan', 'desc')
                ->get();

            return $this->respond($data);
        } catch (\Exception $e) {
            return $this->respond([
                'message' => $e->getMessage(),
                'ex' => $e->getMessage() . ' ' . $e->getLine(),
            ], 400, 'Gagal');
        }
    }

    public function pegawaiByID(Request $r)
    {
        $data  = DB::table('pegawai_m as pg')
            ->leftjoin('agama_m as ag', 'pg.objectagamafk', '=', 'ag.id')
            ->leftjoin('jeniskelamin_m as jk', 'pg.objectjeniskelaminfk', '=', 'jk.id')
            ->leftjoin('jenispegawai_m as jp', 'pg.objectjenispegawaifk', '=', 'jp.id')
            ->leftjoin('pendidikan_m as pe', 'pg.objectpendidikanterakhirfk', '=', 'pe.id')
            ->leftjoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->leftjoin('statuspegawai_m as sp', 'sp.id', '=', 'pg.statuspegawaifk')
            ->select(
                'pg.id',
                'ag.agama',
                'pg.objectagamafk',
                'pg.objectjenispegawaifk',
                'pe.pendidikan',
                'pg.objectpendidikanterakhirfk',
                'jk.jeniskelamin',
                'pg.objectjeniskelaminfk',
                'jp.jenispegawai',
                'pg.namalengkap',
                'pg.kodepos',
                'pg.statusenabled',
                'pg.namalengkap',
                'pg.tempatlahir',
                'pg.email',
                'pg.nohandphone',
                'pg.notlp',
                'pg.alamat',
                'pg.tgllahir',
                'pg.tglmasuk',
                'pg.tglkeluar',
                'pg.isfoto',
                'pg.filename',
                'pg.nid',
                'pg.nik',
                'pg.jabatan1fk',
                'pg.statuspegawaifk',
                'pg.gelarbelakang',
                'pg.gelardepan',
                'pg.isfotoPegawai',
                'pg.filenameFoto',
                'jb.namajabatanulab',
                'jb.id as idjabatan'
            )
            ->where('pg.kdprofile', (int)$this->kdProfile)
            ->where('pg.statusenabled', true)
            ->where('pg.id', $r['id'])
            ->first();

        $result = array(
            'pegawai' => $data,
            'as' => 'aditwiran19@gmail.com',
        );
        return $this->respond($result);
    }

    public function cetakCvPegawai(Request $r)
    {
        $profile = $this->profile();
        $print = false;
        $pageWidth = 950;

        $pegawaiId = $r['id'] ?? $this->getPegawaiId();

        $res = [];
        $res['pdf'] = $r['pdf'] ?? 'false';

        $cv = DB::table('cvpegawai_t as cv')
            ->where('cv.statusenabled', true)
            ->where('cv.pegawaifk', $pegawaiId)
            ->first();

        if (!$cv) {
            $pegawai = DB::table('pegawai_m as pg')
                ->leftJoin('agama_m as ag', 'pg.objectagamafk', '=', 'ag.id')
                ->leftJoin('jeniskelamin_m as jk', 'pg.objectjeniskelaminfk', '=', 'jk.id')
                ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
                ->select(
                    'pg.id',
                    'pg.namalengkap',
                    'pg.tempatlahir',
                    'pg.tgllahir',
                    'pg.email',
                    'pg.nohandphone',
                    'pg.notlp',
                    'pg.alamat',
                    'ag.agama',
                    'pg.objectagamafk',
                    'jk.jeniskelamin',
                    'pg.objectjeniskelaminfk',
                    'jb.namajabatanulab'
                )
                ->where('pg.kdprofile', (int)$this->kdProfile)
                ->where('pg.statusenabled', true)
                ->where('pg.id', $pegawaiId)
                ->first();

            if (!$pegawai) {
                abort(404, 'Data pegawai tidak ditemukan');
            }

            $cv = (object) [
                'pegawaifk' => $pegawai->id,
                'nama' => $pegawai->namalengkap,
                'tempatlahir' => $pegawai->tempatlahir,
                'tanggallahir' => $pegawai->tgllahir,
                'objectjeniskelaminfk' => $pegawai->objectjeniskelaminfk,
                'jeniskelamin' => $pegawai->jeniskelamin,
                'objectagamafk' => $pegawai->objectagamafk,
                'agama' => $pegawai->agama,
                'statuspernikahan' => null,
                'warganegara' => 'Indonesia',
                'penguasaanbahasa' => null,
                'alamatktp' => $pegawai->alamat,
                'alamatsekarang' => $pegawai->alamat,
                'nohp' => $pegawai->nohandphone ?? $pegawai->notlp,
                'email' => $pegawai->email,
                'akunmediasosial' => null,
                'jabatansaatini' => $pegawai->namajabatanulab,
                'kotattd' => 'Jakarta',
                'tanggalttd' => now()->format('Y-m-d'),
                'namattd' => $pegawai->namalengkap,
                'pendidikanformal' => json_encode([]),
                'prestasi' => json_encode([]),
                'pengalamankerja' => json_encode([]),
            ];
        }

        $cv->pendidikanformal = $cv->pendidikanformal ? json_decode($cv->pendidikanformal) : [];
        $cv->prestasi = $cv->prestasi ? json_decode($cv->prestasi) : [];
        $cv->pengalamankerja = $cv->pengalamankerja ? json_decode($cv->pengalamankerja) : [];

        $res['cv'] = $cv;

        $mulaiTahun = Carbon::now()->subYears(5)->startOfYear()->format('Y-m-d');

        $res['pelatihan'] = DB::table('orderpelatihan_t as oplt')
            ->leftJoin('pelatihan_m as plt', 'plt.id', '=', 'oplt.pelatihanfk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'oplt.pengajufk')
            ->leftJoin('jenispelatihan_m as jm', 'jm.id', '=', 'plt.jenispelatihanfk')
            ->select(
                'oplt.norec',
                'oplt.status',
                'oplt.keteranganstatus',
                'oplt.created_at',
                'plt.judulpelatihan',
                'plt.tglpelatihan',
                'plt.lembaga',
                'pg.namalengkap',
                'jm.jenispelatihan',
                DB::raw("EXTRACT(YEAR FROM plt.tglpelatihan) as tahun"),
                DB::raw("
                CASE
                    WHEN UPPER(COALESCE(jm.jenispelatihan, '')) = 'IHT' THEN 'PLN NUSANTARA POWER'
                    WHEN UPPER(COALESCE(jm.jenispelatihan, '')) = 'PUBLIK' THEN 'PUBLIK'
                    WHEN UPPER(COALESCE(jm.jenispelatihan, '')) = 'EKSTERNAL' THEN COALESCE(NULLIF(plt.lembaga, ''), 'EKSTERNAL')
                    ELSE COALESCE(NULLIF(plt.lembaga, ''), jm.jenispelatihan, 'Pelatihan')
                END as lembaga
            "),
                DB::raw("COALESCE(plt.judulpelatihan, '-') as keterampilan")
            )
            ->where('oplt.statusenabled', true)
            ->where('plt.statusenabled', true)
            ->where('oplt.pengajufk', $pegawaiId)
            ->whereNotNull('plt.tglpelatihan')
            ->whereDate('plt.tglpelatihan', '>=', $mulaiTahun)
            ->orderBy('plt.tglpelatihan', 'desc')
            ->get();

        $namaTtd = $res['cv']->namattd ?? $res['cv']->nama ?? '-';
        $jabatanTtd = $res['cv']->jabatansaatini ?? 'Pegawai';

        $res['ttdPegawai'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            $jabatanTtd . "\n" .
                $namaTtd . "\n" .
                'No.Form: FMMO-163-14.4.3.b-62.2' . "\n" .
                'Dokumen Ini Diproduksi oleh :' . "\n" .
                'https://ulabumro.id/'
        ));

        $blade = 'report.mutu.cv-pegawai';

        if ($res['pdf'] == 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("isPhpEnabled", true);
            $pdf->loadView(
                $blade . '-dom',
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                )
            );
            return $pdf->stream('cv-pegawai.pdf');
        }

        if (isset($r['storage'])) {
            $res['storage'] = true;
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("isPhpEnabled", true);
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

    private function referensiJenisAuditKetidaksesuaian()
    {
        return [
            [
                'value' => 'mutu',
                'label' => 'Audit Internal Mutu',
                'lingkup' => 'Mutu',
                'lokasi' => 'Jakarta & Gresik',
            ],
            [
                'value' => 'kelistrikan-jakarta',
                'label' => 'Audit Internal Teknik Kelistrikan Jakarta',
                'lingkup' => 'Kelistrikan',
                'lokasi' => 'Jakarta',
            ],
            [
                'value' => 'tekanan-jakarta',
                'label' => 'Audit Internal Teknik Tekanan Jakarta',
                'lingkup' => 'Tekanan',
                'lokasi' => 'Jakarta',
            ],
            [
                'value' => 'suhu-jakarta',
                'label' => 'Audit Internal Teknik Suhu & Kelembapan Jakarta',
                'lingkup' => 'Suhu',
                'lokasi' => 'Jakarta',
            ],
            [
                'value' => 'vibrasi-jakarta',
                'label' => 'Audit Internal Teknik Vibrasi Jakarta',
                'lingkup' => 'Vibrasi',
                'lokasi' => 'Jakarta',
            ],
            [
                'value' => 'kelistrikan-gresik',
                'label' => 'Audit Internal Teknik Kelistrikan Gresik',
                'lingkup' => 'Kelistrikan',
                'lokasi' => 'Gresik',
            ],
            [
                'value' => 'tekanan-gresik',
                'label' => 'Audit Internal Teknik Tekanan Gresik',
                'lingkup' => 'Tekanan',
                'lokasi' => 'Gresik',
            ],
            [
                'value' => 'suhu-gresik',
                'label' => 'Audit Internal Teknik Suhu Gresik',
                'lingkup' => 'Suhu',
                'lokasi' => 'Gresik',
            ],
            [
                'value' => 'dimensi-gresik',
                'label' => 'Audit Internal Teknik Dimensi Gresik',
                'lingkup' => 'Dimensi',
                'lokasi' => 'Gresik',
            ],
        ];
    }

    private function pegawaiPengisiKetidaksesuaian()
    {
        $pegawaiId = $this->getPegawaiId();
        $loginUserId = $this->getUserId();

        if (!$pegawaiId || !$loginUserId) {
            throw new \Exception('Sesi login atau data pegawai tidak ditemukan');
        }

        $pegawai = DB::table('loginuser_s as lu')
            ->join('pegawai_m as pg', 'pg.id', '=', 'lu.objectpegawaifk')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->select(
                'lu.id as loginuserid',
                'pg.id as pegawaiid',
                'pg.namalengkap',
                'pg.nid',
                'pg.nik',
                'jb.namajabatanulab as jabatan'
            )
            ->where('lu.id', $loginUserId)
            ->where('pg.id', $pegawaiId)
            ->where('pg.statusenabled', true)
            ->first();

        if (!$pegawai) {
            throw new \Exception('Data loginuser_s dan pegawai_m tidak terhubung');
        }

        $pegawai->nid = $pegawai->nid ?: $pegawai->nik;

        return $pegawai;
    }

    private function validasiTandaTangan($tandaTangan)
    {
        if (!is_string($tandaTangan) || !preg_match('/^data:image\/png;base64,/', $tandaTangan)) {
            throw new \Exception('Tanda tangan wajib diisi dalam format gambar PNG');
        }

        $encoded = substr($tandaTangan, strpos($tandaTangan, ',') + 1);
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || strlen($decoded) < 100) {
            throw new \Exception('Data tanda tangan tidak valid');
        }

        if (strlen($decoded) > 2 * 1024 * 1024) {
            throw new \Exception('Ukuran tanda tangan maksimal 2 MB');
        }
    }

    private function jabatanTimAuditInternal($pegawaiId, $tahun)
    {
        if ($this->pegawaiAdalahManagerAuditInternal($pegawaiId)) {
            return null;
        }

        $mapping = DB::table('mappingauditorinternal_m')
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('pegawaifk', (int) $pegawaiId)
            ->where('tahun', (int) $tahun)
            ->where('statusenabled', true)
            ->whereIn('peran', $this->peranAuditorYangBolehMengisi())
            ->orderByRaw("CASE peran
                WHEN 'lead_auditor' THEN 1
                WHEN 'auditor_observer' THEN 2
                WHEN 'auditor' THEN 3
                ELSE 4 END")
            ->orderBy('urutan')
            ->first();

        if (!$mapping) {
            return null;
        }

        $jabatan = [
            'lead_auditor' => 'Lead Auditor',
            'auditor_observer' => 'Auditor Observer',
            'auditor' => 'Anggota Auditor',
        ];

        return $jabatan[$mapping->peran] ?? null;
    }

    private function mappingAuditYangBolehDiisi($pegawaiId, $tahun)
    {
        if ($this->pegawaiAdalahManagerAuditInternal($pegawaiId)) {
            return collect();
        }

        return DB::table('mappingauditorinternal_m')
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('pegawaifk', (int) $pegawaiId)
            ->where('tahun', (int) $tahun)
            ->where('statusenabled', true)
            ->whereIn('peran', $this->peranAuditorYangBolehMengisi())
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
    }

    private function mappingAuditYangBolehDilihat($pegawaiId, $tahun)
    {
        if ($this->pegawaiAdalahManagerAuditInternal($pegawaiId)) {
            $pegawai = DB::table('pegawai_m')
                ->select('id', 'namalengkap')
                ->where('id', (int) $pegawaiId)
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('statusenabled', true)
                ->first();

            return collect($this->referensiJenisAuditKetidaksesuaian())
                ->map(function ($jenis, $index) use ($pegawai, $tahun) {
                    return (object) [
                        'id' => null,
                        'tahun' => (int) $tahun,
                        'kodejenisaudit' => $jenis['value'],
                        'jenisaudit' => $jenis['label'],
                        'lingkup' => $jenis['lingkup'],
                        'lokasi' => $jenis['lokasi'],
                        'pegawaifk' => $pegawai->id,
                        'namapegawai' => $pegawai->namalengkap,
                        'peran' => 'manager',
                        'tugas' => 'Manager (Lihat Saja)',
                        'urutan' => $index + 1,
                    ];
                });
        }

        return DB::table('mappingauditorinternal_m')
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('pegawaifk', (int) $pegawaiId)
            ->where('tahun', (int) $tahun)
            ->where('statusenabled', true)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
    }

    private function peranAuditorYangBolehMengisi()
    {
        return ['lead_auditor', 'auditor_observer', 'auditor'];
    }

    private function peranGlobalMappingAuditor()
    {
        return ['lead_auditor'];
    }

    private function pegawaiAdalahManagerAuditInternal($pegawaiId)
    {
        return DB::table('pegawai_m')
            ->where('id', (int) $pegawaiId)
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('jabatan1fk', 2)
            ->where('statusenabled', true)
            ->exists();
    }

    private function pegawaiManagerAuditInternal()
    {
        return DB::table('pegawai_m')
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('jabatan1fk', 2)
            ->where('statusenabled', true)
            ->pluck('id')
            ->map(function ($pegawaiId) {
                return (int) $pegawaiId;
            })
            ->unique()
            ->values()
            ->all();
    }

    private function validasiAksesTulisManagerAuditInternal($pegawaiId)
    {
        if ($this->pegawaiAdalahManagerAuditInternal($pegawaiId)) {
            throw new \Exception('Role Manager hanya memiliki akses lihat dan tidak dapat mengubah data audit atau mapping.');
        }
    }

    public function referensiTemuanKetidaksesuaian()
    {
        try {
            return $this->respond([
                'jenisAudit' => $this->referensiJenisAuditKetidaksesuaian(),
                'pegawai' => $this->pegawaiPengisiKetidaksesuaian(),
                'namaLpk' => 'Laboratorium Kalibrasi PT PLN NP UMRO',
                'standarAcuan' => 'SNI ISO/IEC 17025:2017 (ISO/IEC 17025:2017)',
            ]);
        } catch (\Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal');
        }
    }

    public function viewDokumenAuditInternal(Request $request)
    {
        $pegawai = $this->pegawaiPengisiKetidaksesuaian();
        $tahun = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));

        $documents = [
            'check-list-audit-internal' => 'FMMO-163-14.4.3.b-88.3 - Formulir Check List audit Internal.docx',
            'daftar-pertanyaan-audit' => 'FMMO-163-14.4.3.b-88.4_Daftar_Pertanyaan_Audit.docx',
            'penilaian-auditor' => 'FMMO-163-14.4.3.b-88.9_-_Penilaian_Auditor_Draft.docx',
        ];
        $kode = (string) $request->input('kode');

        if (!isset($documents[$kode])) {
            abort(404, 'Dokumen Audit Internal tidak ditemukan.');
        }

        $mappingPengguna = $kode === 'penilaian-auditor'
            ? $this->mappingAuditYangBolehDilihat($pegawai->pegawaiid, $tahun)
            : $this->mappingAuditYangBolehDiisi($pegawai->pegawaiid, $tahun);
        if ($mappingPengguna->isEmpty()) {
            abort(403, 'Dokumen ini hanya dapat dibuka oleh pegawai yang sudah dimapping pada Audit Internal.');
        }

        $filename = $documents[$kode];
        $fullPath = public_path('berkas-mutu/audit-internal/' . $filename);
        if (!is_file($fullPath)) {
            abort(404, 'File fisik Dokumen Audit Internal tidak ditemukan.');
        }

        if ($request->boolean('download')) {
            return response()->download($fullPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        $filepath = $this->publicFileUrl('berkas-mutu/audit-internal', $filename);
        $extension = 'docx';
        $data = (object) [
            'id' => 0,
            'namaisidokumen' => pathinfo($filename, PATHINFO_FILENAME),
        ];

        return view('report.mutu.view-pdf', compact('filepath', 'data', 'extension'));
    }

    public function getPaktaIntegritasAuditor(Request $request)
    {
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $tahun = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));
            $jabatanTimAudit = $this->jabatanTimAuditInternal($pegawai->pegawaiid, $tahun);
            $pakta = DB::table('paktaintegritasauditor_t')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('pegawaifk', $pegawai->pegawaiid)
                ->where('statusenabled', true)
                ->first();

            if ($pakta) {
                $pakta->jabatan = $jabatanTimAudit;
            }

            return $this->respond([
                'tahun' => $tahun,
                'pegawai' => $pegawai,
                'jabatanTimAudit' => $jabatanTimAudit,
                'pakta' => $pakta,
            ]);
        } catch (\Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal');
        }
    }

    public function savePaktaIntegritasAuditor(Request $request)
    {
        DB::beginTransaction();
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $tanggal = $request->input('tanggal');
            $lokasi = trim((string) $request->input('lokasi'));
            $tandaTangan = $request->input('tandatangan');
            $tahun = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));
            $jabatanTimAudit = $this->jabatanTimAuditInternal($pegawai->pegawaiid, $tahun);

            if (!$jabatanTimAudit) {
                throw new \Exception('Anda belum termapping sebagai Tim Audit Internal tahun ' . $tahun);
            }

            if (!$tanggal) {
                throw new \Exception('Tanggal pakta integritas wajib diisi');
            }
            Carbon::parse($tanggal);

            if (!in_array($lokasi, ['Jakarta', 'Gresik'], true)) {
                throw new \Exception('Lokasi pakta integritas harus Jakarta atau Gresik');
            }

            $this->validasiTandaTangan($tandaTangan);

            $existing = DB::table('paktaintegritasauditor_t')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('pegawaifk', $pegawai->pegawaiid)
                ->first();

            $data = [
                'statusenabled' => true,
                'loginuserfk' => $pegawai->loginuserid,
                'nama' => $pegawai->namalengkap,
                'nid' => $pegawai->nid,
                'jabatan' => $jabatanTimAudit,
                'tanggal' => Carbon::parse($tanggal)->format('Y-m-d'),
                'lokasi' => $lokasi,
                'tandatangan' => $tandaTangan,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('paktaintegritasauditor_t')
                    ->where('id', $existing->id)
                    ->update($data);
                $id = $existing->id;
            } else {
                $data['kdprofile'] = (int) $this->kdProfile;
                $data['norec'] = (string) $this->Uuid4();
                $data['pegawaifk'] = $pegawai->pegawaiid;
                $data['created_at'] = now();
                $id = DB::table('paktaintegritasauditor_t')->insertGetId($data);
            }

            DB::commit();
            return $this->respond([
                'id' => $id,
                'message' => 'Pakta integritas berhasil disimpan',
            ], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Simpan Gagal');
        }
    }

    public function getTemuanKetidaksesuaian(Request $request)
    {
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $tahun = (int) $request->input('tahun', now()->format('Y'));
            if ($tahun < 2000 || $tahun > 2100) {
                throw new \Exception('Tahun laporan tidak valid');
            }

            $mappingPengguna = $this->mappingAuditYangBolehDilihat($pegawai->pegawaiid, $tahun);
            $mappingPenggunaYangBolehMengisi = $mappingPengguna
                ->whereIn('peran', $this->peranAuditorYangBolehMengisi())
                ->values();
            $jumlahTemuanTahun = DB::table('temuanketidaksesuaiandetail_t as d')
                ->join('temuanketidaksesuaian_t as a', 'a.id', '=', 'd.auditfk')
                ->where('a.kdprofile', (int) $this->kdProfile)
                ->where('a.statusenabled', true)
                ->where('d.statusenabled', true)
                ->whereYear('a.tanggalaudit', $tahun)
                ->count();
            $kodeAudit = $mappingPengguna->pluck('kodejenisaudit')->unique()->values()->all();
            $kodeAuditYangBolehDiisi = $mappingPenggunaYangBolehMengisi
                ->pluck('kodejenisaudit')->unique()->values();
            $pegawaiManager = $this->pegawaiManagerAuditInternal();
            $audits = collect();
            if (count($kodeAudit) > 0) {
                $audits = DB::table('temuanketidaksesuaian_t as a')
                    ->where('a.kdprofile', (int) $this->kdProfile)
                    ->where('a.statusenabled', true)
                    ->whereYear('a.tanggalaudit', $tahun)
                    ->whereIn('a.kodejenisaudit', $kodeAudit)
                    ->orderBy('a.tanggalaudit')
                    ->orderBy('a.id')
                    ->get();
            }

            $auditIds = $audits->pluck('id')->all();
            $details = collect();
            $peserta = collect();

            if (count($auditIds) > 0) {
                $details = DB::table('temuanketidaksesuaiandetail_t')
                    ->whereIn('auditfk', $auditIds)
                    ->where('statusenabled', true)
                    ->orderBy('urutan')
                    ->orderBy('id')
                    ->get();

                $peserta = DB::table('temuanketidaksesuaianpeserta_t')
                    ->select('id', 'auditfk', 'tipe', 'pegawaifk', 'nama', 'tugas', 'urutan')
                    ->whereIn('auditfk', $auditIds)
                    ->where('statusenabled', true)
                    ->when(count($pegawaiManager) > 0, function ($query) use ($pegawaiManager) {
                        return $query->whereNotIn('pegawaifk', $pegawaiManager);
                    })
                    ->orderBy('tipe')
                    ->orderBy('urutan')
                    ->orderBy('id')
                    ->get();
            }

            $detailByAudit = $details->groupBy('auditfk');
            $pesertaByAudit = $peserta->groupBy('auditfk');

            foreach ($audits as $audit) {
                $audit->temuan = ($detailByAudit->get($audit->id) ?: collect())->values();
                $audit->timAudit = ($pesertaByAudit->get($audit->id) ?: collect())
                    ->where('tipe', 'auditor')->values();
                $audit->auditee = ($pesertaByAudit->get($audit->id) ?: collect())
                    ->where('tipe', 'auditee')->values();
                $audit->kategori1 = $audit->temuan->where('kategoritemuan', 1)->count();
                $audit->kategori2 = $audit->temuan->where('kategoritemuan', 2)->count();
                $audit->kategori3 = $audit->temuan->where('kategoritemuan', 3)->count();
                $audit->jumlahtemuan = $audit->temuan->count();
                $audit->bolehmengisi = $kodeAuditYangBolehDiisi->contains($audit->kodejenisaudit);
            }

            return $this->respond([
                'tahun' => $tahun,
                'audits' => $audits,
                'mappingPengguna' => $mappingPengguna,
                'mappingPenggunaYangBolehMengisi' => $mappingPenggunaYangBolehMengisi,
                'jumlahTemuanTahun' => $jumlahTemuanTahun,
                'bisaCetakLaporan' => $jumlahTemuanTahun > 0,
                'jenisAudit' => $this->referensiJenisAuditKetidaksesuaian(),
            ]);
        } catch (\Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal');
        }
    }

    public function saveAuditKetidaksesuaian(Request $request)
    {
        DB::beginTransaction();
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $kodeJenis = trim((string) $request->input('kodejenisaudit'));
            $jenis = collect($this->referensiJenisAuditKetidaksesuaian())
                ->first(function ($item) use ($kodeJenis) {
                    return $item['value'] === $kodeJenis;
                });

            if (!$jenis) {
                throw new \Exception('Jenis audit wajib dipilih dari daftar yang tersedia');
            }

            $tanggal = $request->input('tanggalaudit');
            if (!$tanggal) {
                throw new \Exception('Tanggal audit wajib diisi');
            }
            $tanggalAudit = Carbon::parse($tanggal)->format('Y-m-d');
            $tahunAudit = (int) Carbon::parse($tanggalAudit)->format('Y');
            $bolehMengisi = $this->mappingAuditYangBolehDiisi($pegawai->pegawaiid, $tahunAudit)
                ->where('kodejenisaudit', $jenis['value'])
                ->isNotEmpty();
            if (!$bolehMengisi) {
                throw new \Exception('Anda tidak termapping sebagai auditor untuk jenis audit ini');
            }

            $id = $request->input('id');
            $existing = null;
            if ($id) {
                $existing = DB::table('temuanketidaksesuaian_t')
                    ->where('id', $id)
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->where('statusenabled', true)
                    ->first();
                if (!$existing) {
                    throw new \Exception('Data audit tidak ditemukan');
                }
            }

            $data = [
                'kodejenisaudit' => $jenis['value'],
                'jenisaudit' => $jenis['label'],
                'lingkup' => $jenis['lingkup'],
                'lokasi' => $jenis['lokasi'],
                'tanggalaudit' => $tanggalAudit,
                'namalpk' => 'Laboratorium Kalibrasi PT PLN NP UMRO',
                'standaracuan' => 'SNI ISO/IEC 17025:2017 (ISO/IEC 17025:2017)',
                'pengisifk' => $pegawai->pegawaiid,
                'namapengisi' => $pegawai->namalengkap,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('temuanketidaksesuaian_t')->where('id', $existing->id)->update($data);
                $auditId = $existing->id;
            } else {
                $data['kdprofile'] = (int) $this->kdProfile;
                $data['statusenabled'] = true;
                $data['norec'] = (string) $this->Uuid4();
                $data['created_at'] = now();
                $auditId = DB::table('temuanketidaksesuaian_t')->insertGetId($data);
            }

            $timAudit = $request->input('timAudit', []);
            $auditee = $request->input('auditee', []);
            if (!is_array($timAudit) || !is_array($auditee)) {
                throw new \Exception('Data tim audit atau auditee tidak valid');
            }

            if ($request->boolean('gunakanMapping')) {
                $mappingRows = DB::table('mappingauditorinternal_m')
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->where('tahun', $tahunAudit)
                    ->where('kodejenisaudit', $jenis['value'])
                    ->where('statusenabled', true)
                    ->orderBy('urutan')
                    ->orderBy('id')
                    ->get();

                if ($mappingRows->isNotEmpty()) {
                    $timAudit = $mappingRows
                        ->whereIn('peran', $this->peranAuditorYangBolehMengisi())
                        ->map(function ($row) {
                            return ['pegawaifk' => $row->pegawaifk, 'tugas' => $row->tugas];
                        })->values()->all();
                    $auditee = $mappingRows
                        ->where('peran', 'auditee')
                        ->map(function ($row) {
                            return ['pegawaifk' => $row->pegawaifk, 'tugas' => $row->tugas];
                        })->values()->all();
                }
            }

            DB::table('temuanketidaksesuaianpeserta_t')->where('auditfk', $auditId)->delete();
            $pesertaRows = [];
            $pegawaiManager = $this->pegawaiManagerAuditInternal();
            foreach ([
                'auditor' => $timAudit,
                'auditee' => $auditee,
            ] as $tipe => $items) {
                foreach (array_values($items) as $index => $item) {
                    $pegawaiPesertaId = isset($item['pegawaifk']) ? (int) $item['pegawaifk'] : 0;
                    $tugas = trim((string) ($item['tugas'] ?? ''));
                    if (!$pegawaiPesertaId || $tugas === '') {
                        continue;
                    }
                    if (in_array($pegawaiPesertaId, $pegawaiManager, true)) {
                        continue;
                    }

                    $pegawaiPeserta = DB::table('pegawai_m')
                        ->select('id', 'namalengkap')
                        ->where('id', $pegawaiPesertaId)
                        ->where('kdprofile', (int) $this->kdProfile)
                        ->where('statusenabled', true)
                        ->first();
                    if (!$pegawaiPeserta) {
                        throw new \Exception('Salah satu pegawai peserta audit tidak ditemukan');
                    }

                    $pesertaRows[] = [
                        'auditfk' => $auditId,
                        'statusenabled' => true,
                        'tipe' => $tipe,
                        'pegawaifk' => $pegawaiPeserta->id,
                        'nama' => $pegawaiPeserta->namalengkap,
                        'tugas' => $tugas,
                        'urutan' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (count($pesertaRows) > 0) {
                DB::table('temuanketidaksesuaianpeserta_t')->insert($pesertaRows);
            }

            DB::commit();
            return $this->respond([
                'id' => $auditId,
                'message' => 'Data audit berhasil disimpan',
            ], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Simpan Gagal');
        }
    }

    public function saveTemuanKetidaksesuaian(Request $request)
    {
        DB::beginTransaction();
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $paktaTersimpan = DB::table('paktaintegritasauditor_t')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('pegawaifk', $pegawai->pegawaiid)
                ->where('statusenabled', true)
                ->exists();
            if (!$paktaTersimpan) {
                throw new \Exception('Pakta integritas wajib diisi sebelum mengisi temuan');
            }

            $klausul = trim((string) $request->input('klausul'));
            $uraian = trim((string) $request->input('uraianketidaksesuaian'));
            $kategori = (int) $request->input('kategoritemuan');
            $bagian = trim((string) $request->input('bagian'));

            if ($klausul === '' || $uraian === '') {
                throw new \Exception('Klausul dan uraian ketidaksesuaian wajib diisi');
            }
            if (!in_array($kategori, [1, 2, 3], true)) {
                throw new \Exception('Kategori temuan harus 1, 2, atau 3');
            }

            $auditId = (int) $request->input('auditfk');
            $audit = null;
            if ($auditId) {
                $audit = DB::table('temuanketidaksesuaian_t')
                    ->where('id', $auditId)
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->where('statusenabled', true)
                    ->first();
                if (!$audit) {
                    throw new \Exception('Data audit tidak ditemukan');
                }

                $tahunAudit = (int) Carbon::parse($audit->tanggalaudit)->format('Y');
                $bolehMengisi = $this->mappingAuditYangBolehDiisi($pegawai->pegawaiid, $tahunAudit)
                    ->where('kodejenisaudit', $audit->kodejenisaudit)
                    ->isNotEmpty();
                if (!$bolehMengisi) {
                    throw new \Exception('Anda tidak termapping sebagai auditor untuk jenis audit ini');
                }
            } else {
                $tahunAudit = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));
                if ($tahunAudit !== (int) now()->format('Y')) {
                    throw new \Exception('Temuan baru hanya dapat diisi pada periode tahun berjalan');
                }

                $kodeJenis = trim((string) $request->input('kodejenisaudit'));
                $mappingPengguna = $this->mappingAuditYangBolehDiisi($pegawai->pegawaiid, $tahunAudit)
                    ->where('kodejenisaudit', $kodeJenis);
                if ($mappingPengguna->isEmpty()) {
                    throw new \Exception('Anda tidak termapping sebagai auditor untuk jenis audit ini');
                }

                $jenis = collect($this->referensiJenisAuditKetidaksesuaian())
                    ->first(function ($item) use ($kodeJenis) {
                        return $item['value'] === $kodeJenis;
                    });
                if (!$jenis) {
                    throw new \Exception('Jenis audit tidak valid');
                }

                $audit = DB::table('temuanketidaksesuaian_t')
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->where('statusenabled', true)
                    ->where('kodejenisaudit', $kodeJenis)
                    ->whereYear('tanggalaudit', $tahunAudit)
                    ->orderBy('id')
                    ->first();

                if (!$audit) {
                    $tanggalAudit = now()->format('Y-m-d');
                    $auditId = DB::table('temuanketidaksesuaian_t')->insertGetId([
                        'kdprofile' => (int) $this->kdProfile,
                        'statusenabled' => true,
                        'norec' => (string) $this->Uuid4(),
                        'kodejenisaudit' => $jenis['value'],
                        'jenisaudit' => $jenis['label'],
                        'lingkup' => $jenis['lingkup'],
                        'lokasi' => $jenis['lokasi'],
                        'tanggalaudit' => $tanggalAudit,
                        'namalpk' => 'Laboratorium Kalibrasi PT PLN NP UMRO',
                        'standaracuan' => 'SNI ISO/IEC 17025:2017 (ISO/IEC 17025:2017)',
                        'pengisifk' => $pegawai->pegawaiid,
                        'namapengisi' => $pegawai->namalengkap,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $semuaMapping = DB::table('mappingauditorinternal_m')
                        ->where('kdprofile', (int) $this->kdProfile)
                        ->where('tahun', $tahunAudit)
                        ->where('kodejenisaudit', $kodeJenis)
                        ->where('statusenabled', true)
                        ->whereIn('peran', array_merge($this->peranAuditorYangBolehMengisi(), ['auditee']))
                        ->orderBy('urutan')
                        ->orderBy('id')
                        ->get();
                    $pesertaRows = $semuaMapping->map(function ($row) use ($auditId) {
                        return [
                            'auditfk' => $auditId,
                            'statusenabled' => true,
                            'tipe' => $row->peran === 'auditee' ? 'auditee' : 'auditor',
                            'pegawaifk' => $row->pegawaifk,
                            'nama' => $row->namapegawai,
                            'tugas' => $row->tugas,
                            'urutan' => $row->urutan,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->all();
                    if (count($pesertaRows) > 0) {
                        DB::table('temuanketidaksesuaianpeserta_t')->insert($pesertaRows);
                    }

                    $audit = (object) array_merge([
                        'id' => $auditId,
                        'tanggalaudit' => $tanggalAudit,
                    ], $jenis);
                    $audit->kodejenisaudit = $jenis['value'];
                } else {
                    $auditId = $audit->id;
                }
            }

            if ($bagian === '') {
                $bagian = $audit->lingkup === 'Mutu'
                    ? 'Mutu'
                    : trim($audit->lingkup . ' ' . $audit->lokasi);
            }

            $lokasiMutu = null;
            if ($audit->kodejenisaudit === 'mutu') {
                $lokasiMutu = (int) $request->input('lokasimutu');
                if (!in_array($lokasiMutu, [1, 2], true)) {
                    throw new \Exception('Lokasi Mutu harus dipilih: Jakarta atau Gresik');
                }
            }

            $id = $request->input('id');
            $existing = null;
            if ($id) {
                $existing = DB::table('temuanketidaksesuaiandetail_t')
                    ->where('id', $id)
                    ->where('auditfk', $auditId)
                    ->where('statusenabled', true)
                    ->first();
                if (!$existing) {
                    throw new \Exception('Detail temuan tidak ditemukan');
                }
            }

            $data = [
                'auditfk' => $auditId,
                'statusenabled' => true,
                'bagian' => $bagian,
                'klausul' => $klausul,
                'kategoritemuan' => $kategori,
                'uraianketidaksesuaian' => $uraian,
                'lokasimutu' => $lokasiMutu,
                'auditorfk' => $pegawai->pegawaiid,
                'auditor' => $pegawai->namalengkap,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('temuanketidaksesuaiandetail_t')->where('id', $existing->id)->update($data);
                $detailId = $existing->id;
            } else {
                $data['urutan'] = ((int) DB::table('temuanketidaksesuaiandetail_t')
                    ->where('auditfk', $auditId)->max('urutan')) + 1;
                $data['createdby'] = $this->getUserId();
                $data['created_at'] = now();
                $detailId = DB::table('temuanketidaksesuaiandetail_t')->insertGetId($data);
            }

            DB::commit();
            return $this->respond([
                'id' => $detailId,
                'auditid' => $auditId,
                'message' => 'Temuan ketidaksesuaian berhasil disimpan',
            ], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Simpan Gagal');
        }
    }

    public function hapusTemuanKetidaksesuaian(Request $request)
    {
        DB::beginTransaction();
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $detail = DB::table('temuanketidaksesuaiandetail_t as d')
                ->join('temuanketidaksesuaian_t as a', 'a.id', '=', 'd.auditfk')
                ->select('d.id', 'd.auditfk', 'a.kodejenisaudit', 'a.tanggalaudit')
                ->where('d.id', (int) $request->input('id'))
                ->where('a.kdprofile', (int) $this->kdProfile)
                ->where('a.statusenabled', true)
                ->where('d.statusenabled', true)
                ->first();
            if (!$detail) {
                throw new \Exception('Detail temuan tidak ditemukan');
            }

            $tahunAudit = (int) Carbon::parse($detail->tanggalaudit)->format('Y');
            $bolehMengisi = $this->mappingAuditYangBolehDiisi($pegawai->pegawaiid, $tahunAudit)
                ->where('kodejenisaudit', $detail->kodejenisaudit)
                ->isNotEmpty();
            if (!$bolehMengisi) {
                throw new \Exception('Anda tidak termapping sebagai auditor untuk jenis audit ini');
            }

            DB::table('temuanketidaksesuaiandetail_t')
                ->where('id', $detail->id)
                ->update(['statusenabled' => false, 'updated_at' => now()]);

            $remaining = DB::table('temuanketidaksesuaiandetail_t')
                ->where('auditfk', $detail->auditfk)
                ->where('statusenabled', true)
                ->orderBy('urutan')
                ->orderBy('id')
                ->get(['id']);
            foreach ($remaining as $index => $row) {
                DB::table('temuanketidaksesuaiandetail_t')
                    ->where('id', $row->id)
                    ->update(['urutan' => $index + 1]);
            }

            DB::commit();
            return $this->respond(['message' => 'Temuan berhasil dihapus'], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Hapus Gagal');
        }
    }

    public function hapusAuditKetidaksesuaian(Request $request)
    {
        DB::beginTransaction();
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $audit = DB::table('temuanketidaksesuaian_t')
                ->where('id', (int) $request->input('id'))
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('statusenabled', true)
                ->first();
            if (!$audit) {
                throw new \Exception('Data audit tidak ditemukan');
            }

            $tahunAudit = (int) Carbon::parse($audit->tanggalaudit)->format('Y');
            $bolehMengisi = $this->mappingAuditYangBolehDiisi($pegawai->pegawaiid, $tahunAudit)
                ->where('kodejenisaudit', $audit->kodejenisaudit)
                ->isNotEmpty();
            if (!$bolehMengisi) {
                throw new \Exception('Anda tidak termapping sebagai auditor untuk jenis audit ini');
            }

            DB::table('temuanketidaksesuaian_t')
                ->where('id', $audit->id)
                ->update(['statusenabled' => false, 'updated_at' => now()]);
            DB::table('temuanketidaksesuaianpeserta_t')
                ->where('auditfk', $audit->id)
                ->update(['statusenabled' => false, 'updated_at' => now()]);
            DB::table('temuanketidaksesuaiandetail_t')
                ->where('auditfk', $audit->id)
                ->update(['statusenabled' => false, 'updated_at' => now()]);

            DB::commit();
            return $this->respond(['message' => 'Data audit berhasil dihapus'], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Hapus Gagal');
        }
    }

    private function dataCetakTemuanKetidaksesuaian($tahun, $lokasiCetak)
    {
        $urutanJenis = collect($this->referensiJenisAuditKetidaksesuaian())
            ->pluck('value')
            ->flip();

        // Cetakan merupakan laporan bersama per tahun. Hak input tetap mengikuti
        // mapping auditor, tetapi isi PDF tidak dibatasi oleh pengguna yang mencetak.
        $audits = DB::table('temuanketidaksesuaian_t as a')
            ->where('a.kdprofile', (int) $this->kdProfile)
            ->where('a.statusenabled', true)
            ->whereYear('a.tanggalaudit', $tahun)
            ->when($lokasiCetak !== 'gabungan', function ($query) use ($lokasiCetak) {
                return $query->where(function ($lokasiQuery) use ($lokasiCetak) {
                    $lokasiQuery->where('a.kodejenisaudit', 'mutu')
                        ->orWhereRaw('LOWER(TRIM(a.lokasi)) = ?', [$lokasiCetak]);
                });
            })
            ->orderBy('a.id')
            ->get()
            ->sortBy(function ($audit) use ($urutanJenis) {
                return $urutanJenis->get($audit->kodejenisaudit, PHP_INT_MAX);
            })
            ->values();

        $auditIds = $audits->pluck('id')->all();
        $detailByAudit = collect();
        $pesertaByAudit = collect();
        $pegawaiManager = $this->pegawaiManagerAuditInternal();
        $tanggalMutuPerLokasi = null;

        if ($lokasiCetak !== 'gabungan') {
            $tanggalMutuPerLokasi = $audits
                ->filter(function ($audit) use ($lokasiCetak) {
                    return $audit->kodejenisaudit !== 'mutu'
                        && strtolower(trim((string) $audit->lokasi)) === $lokasiCetak;
                })
                ->pluck('tanggalaudit')
                ->filter()
                ->sort()
                ->first();
        }

        if (count($auditIds) > 0) {
            $detailByAudit = DB::table('temuanketidaksesuaiandetail_t')
                ->whereIn('auditfk', $auditIds)
                ->where('statusenabled', true)
                ->orderBy('urutan')
                ->orderBy('id')
                ->get()
                ->groupBy('auditfk');

            $pesertaByAudit = DB::table('temuanketidaksesuaianpeserta_t as p')
                ->leftJoin('paktaintegritasauditor_t as pi', function ($join) {
                    $join->on('pi.pegawaifk', '=', 'p.pegawaifk')
                        ->where('pi.kdprofile', (int) $this->kdProfile)
                        ->where('pi.statusenabled', true);
                })
                ->select(
                    'p.id',
                    'p.auditfk',
                    'p.tipe',
                    'p.pegawaifk',
                    'p.nama',
                    'p.tugas',
                    'p.urutan',
                    'pi.tandatangan'
                )
                ->whereIn('p.auditfk', $auditIds)
                ->where('p.statusenabled', true)
                ->when(count($pegawaiManager) > 0, function ($query) use ($pegawaiManager) {
                    return $query->whereNotIn('p.pegawaifk', $pegawaiManager);
                })
                ->orderBy('p.tipe')
                ->orderBy('p.urutan')
                ->orderBy('p.id')
                ->get()
                ->groupBy('auditfk');
        }

        foreach ($audits as $audit) {
            $temuanAudit = ($detailByAudit->get($audit->id) ?: collect());
            if ($audit->kodejenisaudit === 'mutu' && $lokasiCetak !== 'gabungan') {
                $lokasiMutu = $lokasiCetak === 'jakarta' ? 1 : 2;
                $temuanAudit = $temuanAudit->where('lokasimutu', $lokasiMutu);
                $audit->lokasi = ucfirst($lokasiCetak);
                if ($tanggalMutuPerLokasi) {
                    $audit->tanggalaudit = $tanggalMutuPerLokasi;
                }
            }
            $audit->temuan = $temuanAudit->values();
            $allPeserta = ($pesertaByAudit->get($audit->id) ?: collect());
            $audit->timAudit = $allPeserta->where('tipe', 'auditor')->values();
            $audit->auditee = $allPeserta->where('tipe', 'auditee')->values();
            $audit->kategori1 = $audit->temuan->where('kategoritemuan', 1)->count();
            $audit->kategori2 = $audit->temuan->where('kategoritemuan', 2)->count();
            $audit->kategori3 = $audit->temuan->where('kategoritemuan', 3)->count();
            $audit->jumlahtemuan = $audit->temuan->count();
        }

        // Pada cetakan per lokasi, audit Mutu hanya ditampilkan bila memiliki
        // temuan untuk lokasi tersebut. Cetakan gabungan tetap memuat semuanya.
        if ($lokasiCetak !== 'gabungan') {
            $audits = $audits->filter(function ($audit) {
                return $audit->kodejenisaudit !== 'mutu' || $audit->jumlahtemuan > 0;
            })->values();
        }

        return $audits;
    }

    private function penandatanganTemuanKetidaksesuaian($tahun)
    {
        $leadAuditor = DB::table('mappingauditorinternal_m')
            ->select('pegawaifk', 'namapegawai')
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('tahun', (int) $tahun)
            ->where('peran', 'lead_auditor')
            ->where('statusenabled', true)
            ->orderBy('urutan')
            ->orderBy('id')
            ->first();

        $kepalaLaboratorium = DB::table('pegawai_m as pg')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->select(
                'pg.id as pegawaifk',
                'pg.namalengkap as namapegawai',
                'jb.namajabatan',
                'jb.namajabatanulab'
            )
            ->where('pg.kdprofile', (int) $this->kdProfile)
            ->where('pg.jabatan1fk', 2)
            ->where('pg.statusenabled', true)
            ->orderBy('pg.id')
            ->first();

        $pegawaiIds = collect([
            $leadAuditor->pegawaifk ?? null,
            $kepalaLaboratorium->pegawaifk ?? null,
        ])->filter()->unique()->values();

        $paktaByPegawai = $pegawaiIds->isEmpty()
            ? collect()
            : DB::table('paktaintegritasauditor_t')
                ->select('pegawaifk', 'tandatangan')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('statusenabled', true)
                ->whereIn('pegawaifk', $pegawaiIds->all())
                ->orderByDesc('id')
                ->get()
                ->unique('pegawaifk')
                ->keyBy('pegawaifk');

        if ($leadAuditor) {
            $leadAuditor->tandatangan = optional($paktaByPegawai->get($leadAuditor->pegawaifk))->tandatangan;
        }
        if ($kepalaLaboratorium) {
            $kepalaLaboratorium->tandatangan = optional($paktaByPegawai->get($kepalaLaboratorium->pegawaifk))->tandatangan;
        }

        return [
            'leadAuditor' => $leadAuditor,
            'kepalaLaboratorium' => $kepalaLaboratorium,
        ];
    }

    public function cetakTemuanKetidaksesuaian(Request $request)
    {
        $this->pegawaiPengisiKetidaksesuaian();
        $tahun = (int) $request->input('tahun', now()->format('Y'));
        if ($tahun < 2000 || $tahun > 2100) {
            abort(400, 'Tahun laporan tidak valid');
        }

        $lokasiCetak = strtolower(trim((string) $request->input('lokasi')));
        if (!in_array($lokasiCetak, ['gresik', 'jakarta', 'gabungan'], true)) {
            abort(400, 'Lokasi cetakan harus Gresik, Jakarta, atau Gabungan');
        }

        $penandatangan = $this->penandatanganTemuanKetidaksesuaian($tahun);
        $res = [
            'tahun' => $tahun,
            'lokasiCetak' => $lokasiCetak === 'gabungan' ? 'Jakarta & Gresik' : ucfirst($lokasiCetak),
            'audits' => $this->dataCetakTemuanKetidaksesuaian($tahun, $lokasiCetak),
            'leadAuditor' => $penandatangan['leadAuditor'],
            'kepalaLaboratorium' => $penandatangan['kepalaLaboratorium'],
        ];
        $blade = 'report.mutu.temuan-ketidaksesuaian-dom';

        if ($request->input('pdf', 'false') === 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option('isPhpEnabled', true);
            $pdf->loadView($blade, [
                'profile' => $this->profile(),
                'res' => $res,
            ]);
            return $pdf->stream('temuan-ketidaksesuaian-' . $lokasiCetak . '-' . $tahun . '.pdf');
        }

        return view($blade, [
            'profile' => $this->profile(),
            'res' => $res,
        ]);
    }

    private function statusClosedVerificationAuditor()
    {
        return ['Belum Memenuhi', 'Memenuhi'];
    }

    private function dataClosedVerificationAuditor($pegawaiId, $tahun, $semuaLingkup = false)
    {
        $mappingPengguna = $semuaLingkup
            ? DB::table('mappingauditorinternal_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tahun', (int) $tahun)
                ->where('statusenabled', true)
                ->orderBy('urutan')
                ->orderBy('id')
                ->get()
            : $this->mappingAuditYangBolehDilihat($pegawaiId, $tahun)->values();
        $kodeAudit = $mappingPengguna->pluck('kodejenisaudit')->unique()->values()->all();
        if (count($kodeAudit) === 0) {
            return [
                'audits' => collect(),
                'mappingPengguna' => $mappingPengguna,
            ];
        }

        $urutanJenis = collect($this->referensiJenisAuditKetidaksesuaian())
            ->pluck('value')
            ->flip();
        $audits = DB::table('temuanketidaksesuaian_t as a')
            ->where('a.kdprofile', (int) $this->kdProfile)
            ->where('a.statusenabled', true)
            ->whereYear('a.tanggalaudit', (int) $tahun)
            ->whereIn('a.kodejenisaudit', $kodeAudit)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('temuanketidaksesuaiandetail_t as d_active')
                    ->whereColumn('d_active.auditfk', 'a.id')
                    ->where('d_active.statusenabled', true);
            })
            ->orderBy('a.id')
            ->get()
            ->sortBy(function ($audit) use ($urutanJenis) {
                return $urutanJenis->get($audit->kodejenisaudit, PHP_INT_MAX);
            })
            ->values();

        $auditIds = $audits->pluck('id')->all();
        $detailByAudit = collect();
        $pesertaByAudit = collect();
        if (count($auditIds) > 0) {
            $detailByAudit = DB::table('temuanketidaksesuaiandetail_t as d')
                ->leftJoin('closedverificationauditor_t as cv', function ($join) {
                    $join->on('cv.temuanfk', '=', 'd.id')
                        ->where('cv.kdprofile', (int) $this->kdProfile)
                        ->where('cv.statusenabled', true);
                })
                ->leftJoin('dokumenauditinternalfolder_m as daf', function ($join) {
                    $join->on('daf.referensifk', '=', 'd.id')
                        ->where('daf.kdprofile', (int) $this->kdProfile)
                        ->where('daf.tipefolder', 'temuan')
                        ->where('daf.statusenabled', true);
                })
                ->leftJoin('ulabdigitalrepo_m as dok_analisa', 'dok_analisa.id', '=', 'cv.analisadokumenauditfk')
                ->leftJoin('ulabdigitalrepo_m as dok_koreksi', 'dok_koreksi.id', '=', 'cv.koreksidokumenauditfk')
                ->leftJoin('ulabdigitalrepo_m as dok_korektif', 'dok_korektif.id', '=', 'cv.korektifdokumenauditfk')
                ->select(
                    'd.id',
                    'd.auditfk',
                    'd.urutan',
                    'd.bagian',
                    'd.klausul',
                    'd.kategoritemuan',
                    'd.uraianketidaksesuaian',
                    'd.lokasimutu',
                    'd.auditorfk',
                    'd.auditor',
                    'cv.id as closedverificationid',
                    'cv.analisapenyebab',
                    'cv.tindakankoreksi',
                    'cv.tindakankorektif',
                    'cv.rencanapenyelesaian',
                    'cv.statusverifikasi',
                    'cv.catatanverifikasi',
                    'cv.pengisifk',
                    'cv.namapengisi',
                    'cv.tanggaldiperbarui',
                    'cv.verifikatorfk',
                    'cv.namaverifikator',
                    'cv.tanggalverifikasi',
                    'cv.analisadokumenauditfk',
                    'cv.analisanamadokumen',
                    'cv.analisatautandokumen',
                    'cv.koreksidokumenauditfk',
                    'cv.koreksinamadokumen',
                    'cv.koreksitautandokumen',
                    'cv.korektifdokumenauditfk',
                    'cv.korektifnamadokumen',
                    'cv.korektiftautandokumen',
                    'daf.udrfolderfk as folderdokumenauditfk',
                    DB::raw("CASE WHEN COALESCE(dok_analisa.isidokumen, '') = '' AND COALESCE(dok_analisa.alamaturlform, '') = '' THEN 'folder' ELSE 'file' END as analisatipedokumen"),
                    DB::raw("CASE WHEN COALESCE(dok_koreksi.isidokumen, '') = '' AND COALESCE(dok_koreksi.alamaturlform, '') = '' THEN 'folder' ELSE 'file' END as koreksitipedokumen"),
                    DB::raw("CASE WHEN COALESCE(dok_korektif.isidokumen, '') = '' AND COALESCE(dok_korektif.alamaturlform, '') = '' THEN 'folder' ELSE 'file' END as korektiftipedokumen")
                )
                ->whereIn('d.auditfk', $auditIds)
                ->where('d.statusenabled', true)
                ->orderBy('d.urutan')
                ->orderBy('d.id')
                ->get()
                ->groupBy('auditfk');

            $pegawaiManager = $this->pegawaiManagerAuditInternal();
            $pesertaByAudit = DB::table('temuanketidaksesuaianpeserta_t')
                ->select('id', 'auditfk', 'tipe', 'pegawaifk', 'nama', 'tugas', 'urutan')
                ->whereIn('auditfk', $auditIds)
                ->where('statusenabled', true)
                ->when(count($pegawaiManager) > 0, function ($query) use ($pegawaiManager) {
                    return $query->whereNotIn('pegawaifk', $pegawaiManager);
                })
                ->orderBy('tipe')
                ->orderBy('urutan')
                ->orderBy('id')
                ->get()
                ->groupBy('auditfk');
        }

        foreach ($audits as $audit) {
            $roles = $mappingPengguna->where('kodejenisaudit', $audit->kodejenisaudit);
            $audit->bolehmengisitindaklanjut = $roles->contains('peran', 'auditee');
            $audit->bolehmengisistatus = $roles
                ->whereIn('peran', $this->peranAuditorYangBolehMengisi())
                ->isNotEmpty();
            $peserta = $pesertaByAudit->get($audit->id) ?: collect();
            $audit->timAudit = $peserta->where('tipe', 'auditor')->values();
            $audit->auditee = $peserta->where('tipe', 'auditee')->values();
            $namaAuditee = $audit->auditee->pluck('nama')->filter()->unique()->implode(', ');
            $audit->temuan = ($detailByAudit->get($audit->id) ?: collect())
                ->map(function ($detail) use ($namaAuditee) {
                    $detail->namaauditee = $namaAuditee;
                    $detail->tindaklanjutlengkap = trim((string) $detail->analisapenyebab) !== ''
                        && trim((string) $detail->tindakankoreksi) !== ''
                        && trim((string) $detail->tindakankorektif) !== ''
                        && !empty($detail->rencanapenyelesaian);
                    $detail->sudahdiverifikasi = !empty($detail->statusverifikasi);
                    return $detail;
                })
                ->values();
            $audit->jumlahtemuan = $audit->temuan->count();
            $audit->jumlahtindaklanjut = $audit->temuan->where('tindaklanjutlengkap', true)->count();
            $audit->jumlahterverifikasi = $audit->temuan->where('sudahdiverifikasi', true)->count();
        }

        return [
            'audits' => $audits,
            'mappingPengguna' => $mappingPengguna,
        ];
    }

    public function getClosedVerificationAuditor(Request $request)
    {
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $tahun = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));
            $data = $this->dataClosedVerificationAuditor($pegawai->pegawaiid, $tahun);
            $semuaTemuan = $data['audits']->pluck('temuan')->flatten(1);
            $tanggalPenetapanLha = DB::table('laporanhasilaudit_t')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tahun', $tahun)
                ->where('statusenabled', true)
                ->value('tanggalpenetapan');
            $tanggalDefaultLha = $data['audits']->pluck('tanggalaudit')->filter()->sort()->last()
                ?: Carbon::now()->format('Y-m-d');

            return $this->respond([
                'tahun' => $tahun,
                'pegawai' => $pegawai,
                'audits' => $data['audits'],
                'mappingPengguna' => $data['mappingPengguna'],
                'statusOptions' => $this->statusClosedVerificationAuditor(),
                'lha' => [
                    'tanggalpenetapan' => $tanggalPenetapanLha ?: $tanggalDefaultLha,
                    'sudahdisimpan' => !empty($tanggalPenetapanLha),
                    'bolehmengubahtanggal' => $data['mappingPengguna']->contains('peran', 'lead_auditor'),
                ],
                'ringkasan' => [
                    'jumlahAudit' => $data['audits']->count(),
                    'jumlahTemuan' => $semuaTemuan->count(),
                    'jumlahTindakLanjut' => $semuaTemuan->where('tindaklanjutlengkap', true)->count(),
                    'jumlahTerverifikasi' => $semuaTemuan->where('sudahdiverifikasi', true)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal');
        }
    }

    public function saveTanggalPenetapanLha(Request $request)
    {
        DB::beginTransaction();
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $tahun = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));
            $bolehMengubah = DB::table('mappingauditorinternal_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tahun', $tahun)
                ->where('pegawaifk', (int) $pegawai->pegawaiid)
                ->where('peran', 'lead_auditor')
                ->where('statusenabled', true)
                ->exists();
            if (!$bolehMengubah) {
                throw new \Exception('Tanggal penetapan LHA hanya dapat diubah oleh Lead Auditor');
            }

            $tanggalInput = trim((string) $request->input('tanggalpenetapan'));
            try {
                $tanggalPenetapan = Carbon::createFromFormat('Y-m-d', $tanggalInput)->format('Y-m-d');
            } catch (\Throwable $e) {
                throw new \Exception('Tanggal penetapan LHA tidak valid');
            }
            if ($tanggalPenetapan !== $tanggalInput) {
                throw new \Exception('Tanggal penetapan LHA tidak valid');
            }

            $existing = DB::table('laporanhasilaudit_t')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tahun', $tahun)
                ->first();
            $data = [
                'statusenabled' => true,
                'tanggalpenetapan' => $tanggalPenetapan,
                'updatedby' => $pegawai->loginuserid,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('laporanhasilaudit_t')->where('id', $existing->id)->update($data);
                $id = $existing->id;
            } else {
                $data['kdprofile'] = (int) $this->kdProfile;
                $data['norec'] = (string) $this->Uuid4();
                $data['tahun'] = $tahun;
                $data['createdby'] = $pegawai->loginuserid;
                $data['created_at'] = now();
                $id = DB::table('laporanhasilaudit_t')->insertGetId($data);
            }

            DB::commit();
            return $this->respond([
                'id' => $id,
                'tanggalpenetapan' => $tanggalPenetapan,
            ], 200, 'Tanggal penetapan LHA berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Simpan Gagal');
        }
    }

    public function pilihanDokumenAuditInternal(Request $request)
    {
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $temuan = DB::table('temuanketidaksesuaiandetail_t as d')
                ->join('temuanketidaksesuaian_t as a', 'a.id', '=', 'd.auditfk')
                ->where('d.id', (int) $request->input('temuanfk'))
                ->where('d.statusenabled', true)
                ->where('a.kdprofile', (int) $this->kdProfile)
                ->where('a.statusenabled', true)
                ->first(['d.id', 'd.auditfk', 'a.kodejenisaudit', 'a.tanggalaudit']);
            if (!$temuan) {
                throw new \Exception('Temuan ketidaksesuaian tidak ditemukan');
            }

            $tahun = (int) Carbon::parse($temuan->tanggalaudit)->format('Y');
            if ($this->mappingAuditYangBolehDilihat($pegawai->pegawaiid, $tahun)
                ->where('kodejenisaudit', $temuan->kodejenisaudit)->isEmpty()) {
                throw new \Exception('Anda tidak memiliki akses ke dokumen temuan ini');
            }

            $folderId = DB::table('dokumenauditinternalfolder_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tipefolder', 'temuan')
                ->where('referensifk', (int) $temuan->id)
                ->where('statusenabled', true)
                ->value('udrfolderfk');
            if (!$folderId) {
                return $this->respond(['folderId' => null, 'documents' => []], 200, 'Folder temuan belum tersedia');
            }

            $allRows = DB::table('ulabdigitalrepo_m')
                ->where('jenisudr', 'Audit Internal')
                ->where('statusenabled', true)
                ->orderBy('id')
                ->get()
                ->groupBy(function ($row) {
                    return (int) ($row->kdisidokumen ?: $row->id);
                })
                ->map(fn ($versions) => $versions->last())
                ->values();

            $parentOf = function ($row) {
                $rootId = (int) ($row->kdisidokumen ?: $row->id);
                if ($row->kodeexternal === 'H' || (int) $row->kdrinciandokumenhead === $rootId) {
                    return null;
                }
                return $row->kdrinciandokumenhead !== null ? (int) $row->kdrinciandokumenhead : null;
            };
            $children = $allRows->groupBy(fn ($row) => (string) ($parentOf($row) ?? 'root'));
            $base = $allRows->first(fn ($row) => (int) $row->id === (int) $folderId);
            $documents = collect();
            if ($base) {
                $documents->push([
                    'id' => (int) $base->id,
                    'name' => $base->namaisidokumen,
                    'path' => $base->namaisidokumen . ' (seluruh folder)',
                    'kind' => 'folder',
                ]);
            }

            $walk = function ($parentId, $path, $depth = 0) use (&$walk, $children, $documents) {
                if ($depth > 20) {
                    return;
                }
                foreach ($children->get((string) $parentId, collect()) as $row) {
                    $isFolder = trim((string) $row->isidokumen) === ''
                        && trim((string) $row->alamaturlform) === '';
                    $itemPath = trim($path . ' / ' . $row->namaisidokumen, ' /');
                    $documents->push([
                        'id' => (int) $row->id,
                        'name' => $row->namaisidokumen,
                        'path' => $itemPath,
                        'kind' => $isFolder ? 'folder' : 'file',
                    ]);
                    if ($isFolder) {
                        $walk((int) $row->id, $itemPath, $depth + 1);
                    }
                }
            };
            $walk((int) $folderId, '', 0);

            return $this->respond([
                'folderId' => (int) $folderId,
                'documents' => $documents->values(),
            ], 200, 'Pilihan dokumen berhasil dimuat');
        } catch (\Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memuat pilihan dokumen');
        }
    }

    private function dokumenAuditInternalDalamFolderTemuan($dokumenId, $folderId)
    {
        $current = DB::table('ulabdigitalrepo_m')
            ->where('id', (int) $dokumenId)
            ->where('jenisudr', 'Audit Internal')
            ->where('statusenabled', true)
            ->first();
        $guard = 0;

        while ($current && $guard < 50) {
            if ((int) $current->id === (int) $folderId) {
                return $current;
            }
            $rootId = (int) ($current->kdisidokumen ?: $current->id);
            if ($current->kodeexternal === 'H' || (int) $current->kdrinciandokumenhead === $rootId
                || $current->kdrinciandokumenhead === null) {
                break;
            }
            $current = DB::table('ulabdigitalrepo_m')
                ->where('id', (int) $current->kdrinciandokumenhead)
                ->where('jenisudr', 'Audit Internal')
                ->where('statusenabled', true)
                ->first();
            $guard++;
        }

        return null;
    }

    public function saveClosedVerificationAuditor(Request $request)
    {
        DB::beginTransaction();
        try {
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $mode = trim((string) $request->input('mode'));
            if (!in_array($mode, ['tindak_lanjut', 'status'], true)) {
                throw new \Exception('Mode pengisian Closed Verification tidak valid');
            }

            $temuan = DB::table('temuanketidaksesuaiandetail_t as d')
                ->join('temuanketidaksesuaian_t as a', 'a.id', '=', 'd.auditfk')
                ->select(
                    'd.id as temuanid',
                    'd.auditfk',
                    'a.kodejenisaudit',
                    'a.tanggalaudit'
                )
                ->where('d.id', (int) $request->input('temuanfk'))
                ->where('d.statusenabled', true)
                ->where('a.kdprofile', (int) $this->kdProfile)
                ->where('a.statusenabled', true)
                ->first();
            if (!$temuan) {
                throw new \Exception('Temuan ketidaksesuaian tidak ditemukan');
            }

            $tahunAudit = (int) Carbon::parse($temuan->tanggalaudit)->format('Y');
            $roles = $this->mappingAuditYangBolehDilihat($pegawai->pegawaiid, $tahunAudit)
                ->where('kodejenisaudit', $temuan->kodejenisaudit);
            if ($roles->isEmpty()) {
                throw new \Exception('Anda tidak memiliki akses untuk jenis audit ini');
            }

            $existing = DB::table('closedverificationauditor_t')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('temuanfk', $temuan->temuanid)
                ->first();

            if ($mode === 'tindak_lanjut') {
                if (!$roles->contains('peran', 'auditee')) {
                    throw new \Exception('Tindak lanjut hanya dapat diisi oleh auditee yang termapping pada audit ini');
                }

                $analisaPenyebab = trim((string) $request->input('analisapenyebab'));
                $tindakanKoreksi = trim((string) $request->input('tindakankoreksi'));
                $tindakanKorektif = trim((string) $request->input('tindakankorektif'));
                $rencanaPenyelesaian = $request->input('rencanapenyelesaian');

                $folderId = DB::table('dokumenauditinternalfolder_m')
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->where('tipefolder', 'temuan')
                    ->where('referensifk', (int) $temuan->temuanid)
                    ->where('statusenabled', true)
                    ->value('udrfolderfk');
                $labelBukti = [
                    'analisa' => 'Analisa Penyebab',
                    'koreksi' => 'Tindakan Koreksi',
                    'korektif' => 'Tindakan Korektif',
                ];
                $bukti = [];
                foreach ($labelBukti as $field => $label) {
                    $dokumenAuditId = $request->filled($field . 'dokumenauditfk')
                        ? (int) $request->input($field . 'dokumenauditfk')
                        : null;
                    $tautanDokumen = trim((string) $request->input($field . 'tautandokumen'));
                    if ($dokumenAuditId && $tautanDokumen !== '') {
                        throw new \Exception($label . ': pilih sambungkan dokumen langsung atau gunakan tautan salinan');
                    }
                    if ($tautanDokumen !== '') {
                        $scheme = strtolower((string) parse_url($tautanDokumen, PHP_URL_SCHEME));
                        if (!filter_var($tautanDokumen, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                            throw new \Exception('Tautan dokumen ' . $label . ' harus berupa URL http atau https yang valid');
                        }
                        if (mb_strlen($tautanDokumen) > 2000) {
                            throw new \Exception('Tautan dokumen ' . $label . ' terlalu panjang');
                        }
                    }

                    $namaDokumen = null;
                    if ($dokumenAuditId) {
                        $dokumen = $folderId
                            ? DB::table('ulabdigitalrepo_m')
                                ->where('id', $dokumenAuditId)
                                ->where('jenisudr', 'Audit Internal')
                                ->where('statusenabled', true)
                                ->first()
                            : null;
                        if (!$dokumen || !$this->dokumenAuditInternalDalamFolderTemuan($dokumenAuditId, $folderId)) {
                            throw new \Exception('Dokumen ' . $label . ' tidak berada di folder temuan ini');
                        }
                        $namaDokumen = $dokumen->namaisidokumen;
                    }

                    $bukti[$field . 'dokumenauditfk'] = $dokumenAuditId;
                    $bukti[$field . 'namadokumen'] = $namaDokumen;
                    $bukti[$field . 'tautandokumen'] = $tautanDokumen !== '' ? $tautanDokumen : null;
                }

                $data = array_merge([
                    'statusenabled' => true,
                    'auditfk' => $temuan->auditfk,
                    'analisapenyebab' => $analisaPenyebab,
                    'tindakankoreksi' => $tindakanKoreksi,
                    'tindakankorektif' => $tindakanKorektif,
                    'rencanapenyelesaian' => $rencanaPenyelesaian
                        ? Carbon::parse($rencanaPenyelesaian)->format('Y-m-d')
                        : null,
                ], $bukti, [
                    'pengisifk' => $pegawai->pegawaiid,
                    'namapengisi' => $pegawai->namalengkap,
                    'tanggaldiperbarui' => now(),
                    // Perubahan tindak lanjut wajib diverifikasi ulang oleh auditor.
                    'statusverifikasi' => null,
                    'catatanverifikasi' => null,
                    'verifikatorfk' => null,
                    'namaverifikator' => null,
                    'tanggalverifikasi' => null,
                    'updatedby' => $pegawai->loginuserid,
                    'updated_at' => now(),
                ]);

                if ($existing) {
                    DB::table('closedverificationauditor_t')->where('id', $existing->id)->update($data);
                    $id = $existing->id;
                } else {
                    $data['kdprofile'] = (int) $this->kdProfile;
                    $data['norec'] = (string) $this->Uuid4();
                    $data['temuanfk'] = $temuan->temuanid;
                    $data['createdby'] = $pegawai->loginuserid;
                    $data['created_at'] = now();
                    $id = DB::table('closedverificationauditor_t')->insertGetId($data);
                }
                $message = 'Tindak lanjut auditee berhasil disimpan';
            } else {
                $bolehVerifikasi = $roles
                    ->whereIn('peran', $this->peranAuditorYangBolehMengisi())
                    ->isNotEmpty();
                if (!$bolehVerifikasi) {
                    throw new \Exception('Status hanya dapat diisi oleh Auditor atau Lead Auditor yang termapping');
                }
                if (!$existing
                    || trim((string) $existing->analisapenyebab) === ''
                    || trim((string) $existing->tindakankoreksi) === ''
                    || trim((string) $existing->tindakankorektif) === ''
                    || empty($existing->rencanapenyelesaian)) {
                    throw new \Exception('Tindak lanjut auditee harus lengkap sebelum status diverifikasi');
                }

                $statusVerifikasi = trim((string) $request->input('statusverifikasi'));
                if (!in_array($statusVerifikasi, $this->statusClosedVerificationAuditor(), true)) {
                    throw new \Exception('Status verifikasi tidak valid');
                }
                $catatanVerifikasi = trim((string) $request->input('catatanverifikasi'));
                if (mb_strlen($catatanVerifikasi) > 2000) {
                    throw new \Exception('Catatan verifikasi maksimal 2000 karakter');
                }

                DB::table('closedverificationauditor_t')->where('id', $existing->id)->update([
                    'statusenabled' => true,
                    'statusverifikasi' => $statusVerifikasi,
                    'catatanverifikasi' => $catatanVerifikasi !== '' ? $catatanVerifikasi : null,
                    'verifikatorfk' => $pegawai->pegawaiid,
                    'namaverifikator' => $pegawai->namalengkap,
                    'tanggalverifikasi' => now(),
                    'updatedby' => $pegawai->loginuserid,
                    'updated_at' => now(),
                ]);
                $id = $existing->id;
                $message = 'Status Closed Verification berhasil disimpan';
            }

            DB::commit();
            return $this->respond(['id' => $id, 'message' => $message], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Simpan Gagal');
        }
    }

    public function cetakClosedVerificationAuditor(Request $request)
    {
        $pegawai = $this->pegawaiPengisiKetidaksesuaian();
        $tahun = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));

        $data = $this->dataClosedVerificationAuditor($pegawai->pegawaiid, $tahun, true);
        if ($data['audits']->isEmpty()) {
            abort(404, 'Belum ada temuan dari lingkup audit yang termapping pada periode ini');
        }

        $mappingPenandatangan = DB::table('mappingauditorinternal_m')
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('tahun', $tahun)
            ->where('statusenabled', true)
            ->whereIn('peran', array_merge($this->peranAuditorYangBolehMengisi(), ['auditee']))
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $managerPenandatangan = DB::table('pegawai_m')
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('jabatan1fk', 2)
            ->where('statusenabled', true)
            ->orderBy('id')
            ->pluck('namalengkap')
            ->filter()
            ->unique()
            ->values();

        $res = [
            'tahun' => $tahun,
            'lokasiCetak' => 'Jakarta & Gresik',
            'audits' => $data['audits'],
            'managerPenandatangan' => $managerPenandatangan,
            'leadAuditorPenandatangan' => $mappingPenandatangan
                ->where('peran', 'lead_auditor')->pluck('namapegawai')->filter()->unique()->values(),
            'auditorObserverPenandatangan' => $mappingPenandatangan
                ->where('peran', 'auditor_observer')->pluck('namapegawai')->filter()->unique()->values(),
        ];
        $blade = 'report.mutu.closed-verification-auditor-dom';

        if ($request->input('pdf', 'false') === 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option('isPhpEnabled', true);
            $pdf->setPaper('a4', 'landscape');
            $pdf->loadView($blade, [
                'profile' => $this->profile(),
                'res' => $res,
            ]);
            return $pdf->stream('closed-verification-auditor-semua-lingkup-' . $tahun . '.pdf');
        }

        return view($blade, [
            'profile' => $this->profile(),
            'res' => $res,
        ]);
    }

    private function pegawaiPenandatanganLha($jabatanId)
    {
        $pegawai = DB::table('pegawai_m as pg')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->select(
                'pg.id as pegawaifk',
                'pg.namalengkap as namapegawai',
                'pg.nid',
                'pg.nik',
                'jb.namajabatan',
                'jb.namajabatanulab'
            )
            ->where('pg.kdprofile', (int) $this->kdProfile)
            ->where('pg.jabatan1fk', (int) $jabatanId)
            ->where('pg.statusenabled', true)
            ->orderBy('pg.id')
            ->first();

        if ($pegawai) {
            $pegawai->nid = $pegawai->nid ?: $pegawai->nik;
        }
        return $pegawai;
    }

    private function penandatanganLaporanHasilAudit($tahun)
    {
        $leadAuditor = DB::table('mappingauditorinternal_m as m')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'm.pegawaifk')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->select(
                'm.pegawaifk',
                'm.namapegawai',
                'm.tugas',
                'pg.nid',
                'pg.nik',
                'jb.namajabatan',
                'jb.namajabatanulab'
            )
            ->where('m.kdprofile', (int) $this->kdProfile)
            ->where('m.tahun', (int) $tahun)
            ->where('m.peran', 'lead_auditor')
            ->where('m.statusenabled', true)
            ->orderBy('m.urutan')
            ->orderBy('m.id')
            ->first();
        if ($leadAuditor) {
            $leadAuditor->nid = $leadAuditor->nid ?: $leadAuditor->nik;
        }

        return [
            'asman' => $this->pegawaiPenandatanganLha(3),
            'leadAuditor' => $leadAuditor,
            'managerRepair' => $this->pegawaiPenandatanganLha(2),
            'seniorManagerJire' => $this->pegawaiPenandatanganLha(1),
        ];
    }

    private function renderBagianLha($blade, $res, $orientation)
    {
        $pdf = App::make('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);
        $pdf->setPaper('a4', $orientation);
        $pdf->loadView($blade, [
            'profile' => $this->profile(),
            'res' => $res,
        ]);
        return $pdf->output();
    }

    private function hitungHalamanPdf($binary)
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'lha_count_');
        if ($tempPath === false || file_put_contents($tempPath, $binary) === false) {
            throw new \Exception('Gagal menghitung jumlah halaman PDF LHA');
        }

        try {
            return (new Fpdi())->setSourceFile($tempPath);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function gabungkanBagianLha(array $bagianPdf)
    {
        $tempPaths = [];
        $bagianPaths = [];
        try {
            foreach ($bagianPdf as $bagian) {
                $binary = is_array($bagian) ? $bagian['binary'] : $bagian;
                $tempPath = tempnam(sys_get_temp_dir(), 'lha_');
                if ($tempPath === false || file_put_contents($tempPath, $binary) === false) {
                    throw new \Exception('Gagal menyiapkan bagian sementara PDF LHA');
                }
                $tempPaths[] = $tempPath;

                $overlayPath = null;
                if (is_array($bagian) && !empty($bagian['overlay'])) {
                    $overlayPath = tempnam(sys_get_temp_dir(), 'lha_overlay_');
                    if ($overlayPath === false || file_put_contents($overlayPath, $bagian['overlay']) === false) {
                        throw new \Exception('Gagal menyiapkan header dan footer PDF LHA');
                    }
                    $tempPaths[] = $overlayPath;
                }

                $bagianPaths[] = [
                    'content' => $tempPath,
                    'overlay' => $overlayPath,
                ];
            }

            $pageCounts = [];
            foreach ($bagianPaths as $bagianPath) {
                $probe = new Fpdi();
                $pageCounts[] = $probe->setSourceFile($bagianPath['content']);
            }

            $merged = new Fpdi();
            $merged->SetAutoPageBreak(false);
            foreach ($bagianPaths as $index => $bagianPath) {
                for ($pageNo = 1; $pageNo <= $pageCounts[$index]; $pageNo++) {
                    $merged->setSourceFile($bagianPath['content']);
                    $templateId = $merged->importPage($pageNo);
                    $size = $merged->getTemplateSize($templateId);
                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                    $merged->AddPage($orientation, [$size['width'], $size['height']]);
                    $merged->useTemplate($templateId);

                    if ($bagianPath['overlay']) {
                        $merged->setSourceFile($bagianPath['overlay']);
                        $overlayTemplateId = $merged->importPage(1);
                        $merged->useTemplate($overlayTemplateId, 0, 0, $size['width'], $size['height']);
                    }
                }
            }
            return $merged->Output('S');
        } finally {
            foreach ($tempPaths as $tempPath) {
                if (is_file($tempPath)) {
                    @unlink($tempPath);
                }
            }
        }
    }

    public function cetakLaporanHasilAudit(Request $request)
    {
        $pegawai = $this->pegawaiPengisiKetidaksesuaian();
        $tahun = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));
        $data = $this->dataClosedVerificationAuditor($pegawai->pegawaiid, $tahun, true);
        if ($data['audits']->isEmpty()) {
            abort(404, 'Belum ada temuan untuk Laporan Hasil Audit periode ini');
        }

        foreach ($data['audits'] as $audit) {
            $audit->kategori1 = $audit->temuan->where('kategoritemuan', 1)->count();
            $audit->kategori2 = $audit->temuan->where('kategoritemuan', 2)->count();
            $audit->kategori3 = $audit->temuan->where('kategoritemuan', 3)->count();
        }
        $semuaTemuan = $data['audits']->pluck('temuan')->flatten(1);
        $tanggalPenetapan = DB::table('laporanhasilaudit_t')
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('tahun', $tahun)
            ->where('statusenabled', true)
            ->value('tanggalpenetapan');
        $tanggalAudit = $data['audits']->pluck('tanggalaudit')->filter()->sort()->unique()->values();
        $tanggalPenetapan = $tanggalPenetapan ?: ($tanggalAudit->last() ?: Carbon::now()->format('Y-m-d'));
        $penandatangan = $this->penandatanganLaporanHasilAudit($tahun);

        $res = [
            'tahun' => $tahun,
            'lokasiCetak' => 'Jakarta & Gresik',
            'tanggalAudit' => $tanggalAudit,
            'tanggalPenetapan' => $tanggalPenetapan,
            'nomorLha' => '01/UMRO/LHAI-17025/' . Carbon::parse($tanggalPenetapan)->format('m') . '/' . $tahun,
            'audits' => $data['audits'],
            'semuaTemuan' => $semuaTemuan,
            'ringkasan' => [
                'jumlahTemuan' => $semuaTemuan->count(),
                'kategori1' => $semuaTemuan->where('kategoritemuan', 1)->count(),
                'kategori2' => $semuaTemuan->where('kategoritemuan', 2)->count(),
                'kategori3' => $semuaTemuan->where('kategoritemuan', 3)->count(),
                'tindakLanjutLengkap' => $semuaTemuan->where('tindaklanjutlengkap', true)->count(),
                'memenuhi' => $semuaTemuan->where('statusverifikasi', 'Memenuhi')->count(),
                'belumMemenuhi' => $semuaTemuan->where('statusverifikasi', 'Belum Memenuhi')->count(),
                'belumDiverifikasi' => $semuaTemuan->filter(function ($temuan) {
                    return empty($temuan->statusverifikasi);
                })->count(),
            ],
            'asman' => $penandatangan['asman'],
            'leadAuditor' => $penandatangan['leadAuditor'],
            'managerRepair' => $penandatangan['managerRepair'],
            'seniorManagerJire' => $penandatangan['seniorManagerJire'],
        ];

        if ($request->input('pdf', 'false') !== 'true') {
            return view('report.mutu.laporan-hasil-audit-sampul', [
                'profile' => $this->profile(),
                'res' => $res,
            ]);
        }

        $bagianSampul = $this->renderBagianLha('report.mutu.laporan-hasil-audit-sampul', $res, 'portrait');
        $bagianLandscape = $this->renderBagianLha('report.mutu.laporan-hasil-audit-landscape', $res, 'landscape');
        $bagianPortraitAwal = $this->renderBagianLha('report.mutu.laporan-hasil-audit-portrait-awal', $res, 'portrait');
        $res['tocBab3'] = max(
            1,
            $this->hitungHalamanPdf($bagianPortraitAwal) - 3 + $this->hitungHalamanPdf($bagianLandscape) + 1
        );
        $res['tocBab4'] = $res['tocBab3'] + 1;
        $bagianPortraitAwal = $this->renderBagianLha('report.mutu.laporan-hasil-audit-portrait-awal', $res, 'portrait');
        $overlayPortrait = $this->renderBagianLha('report.mutu.laporan-hasil-audit-overlay-portrait', $res, 'portrait');
        $overlayLandscape = $this->renderBagianLha('report.mutu.laporan-hasil-audit-overlay-landscape', $res, 'landscape');

        $binary = $this->gabungkanBagianLha([
            $bagianSampul,
            ['binary' => $bagianPortraitAwal, 'overlay' => $overlayPortrait],
            ['binary' => $bagianLandscape, 'overlay' => $overlayLandscape],
            [
                'binary' => $this->renderBagianLha('report.mutu.laporan-hasil-audit-portrait-akhir', $res, 'portrait'),
                'overlay' => $overlayPortrait,
            ],
        ]);

        $filename = 'laporan-hasil-audit-semua-lingkup-' . $tahun . '.pdf';
        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Content-Length' => strlen($binary),
        ]);
    }

    public function cetakPaktaIntegritasAuditor(Request $request)
    {
        $pegawaiLogin = $this->pegawaiPengisiKetidaksesuaian();
        $pegawaiId = (int) $request->input('pegawaifk', $pegawaiLogin->pegawaiid);
        $tahun = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));
        $jabatanTimAudit = $this->jabatanTimAuditInternal($pegawaiId, $tahun);
        $pakta = DB::table('paktaintegritasauditor_t')
            ->where('kdprofile', (int) $this->kdProfile)
            ->where('pegawaifk', $pegawaiId)
            ->where('statusenabled', true)
            ->first();

        if (!$pakta) {
            abort(404, 'Pakta integritas belum diisi');
        }
        if (!$jabatanTimAudit) {
            abort(400, 'Pegawai belum termapping sebagai Tim Audit Internal tahun ' . $tahun);
        }

        $pakta->jabatan = $jabatanTimAudit;

        $blade = 'report.mutu.pakta-integritas-auditor-dom';
        if ($request->input('pdf', 'false') === 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option('isPhpEnabled', true);
            $pdf->loadView($blade, [
                'profile' => $this->profile(),
                'pakta' => $pakta,
            ]);
            return $pdf->stream('pakta-integritas-auditor-' . $pegawaiId . '.pdf');
        }

        return view($blade, [
            'profile' => $this->profile(),
            'pakta' => $pakta,
        ]);
    }

    private function referensiPeranMappingAuditor()
    {
        return [
            ['value' => 'lead_auditor', 'label' => 'Lead Auditor'],
            ['value' => 'auditor_observer', 'label' => 'Auditor Observer'],
            ['value' => 'auditor', 'label' => 'Anggota Auditor'],
            ['value' => 'auditee', 'label' => 'Auditee'],
        ];
    }

    private function validasiTahunMapping($tahun)
    {
        $tahun = (int) $tahun;
        if ($tahun < 2000 || $tahun > 2100) {
            throw new \Exception('Tahun mapping audit tidak valid');
        }
        return $tahun;
    }

    public function getMappingAuditorInternal(Request $request)
    {
        try {
            $tahun = $this->validasiTahunMapping($request->input('tahun', now()->format('Y')));
            $pegawai = $this->pegawaiPengisiKetidaksesuaian();
            $pegawaiManager = $this->pegawaiManagerAuditInternal();

            $rows = DB::table('mappingauditorinternal_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tahun', $tahun)
                ->where('statusenabled', true)
                ->when(count($pegawaiManager) > 0, function ($query) use ($pegawaiManager) {
                    return $query->whereNotIn('pegawaifk', $pegawaiManager);
                })
                ->orderBy('kodejenisaudit')
                ->orderBy('urutan')
                ->orderBy('id')
                ->get();

            $paktaByPegawai = DB::table('paktaintegritasauditor_t')
                ->select('id', 'pegawaifk', 'tanggal')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('statusenabled', true)
                ->orderByDesc('id')
                ->get()
                ->unique('pegawaifk')
                ->keyBy('pegawaifk');

            $rows->transform(function ($row) use ($paktaByPegawai) {
                $pakta = $paktaByPegawai->get($row->pegawaifk);
                $row->sudahpakta = (bool) $pakta;
                $row->paktaid = $pakta->id ?? null;
                $row->tanggalpakta = $pakta->tanggal ?? null;
                return $row;
            });

            $byJenis = $rows->groupBy('kodejenisaudit');
            $leadAuditor = $rows->firstWhere('peran', 'lead_auditor');
            $groups = collect($this->referensiJenisAuditKetidaksesuaian())
                ->map(function ($jenis) use ($byJenis) {
                    return [
                        'value' => $jenis['value'],
                        'label' => $jenis['label'],
                        'lingkup' => $jenis['lingkup'],
                        'lokasi' => $jenis['lokasi'],
                        'mapping' => ($byJenis->get($jenis['value']) ?: collect())
                            ->whereNotIn('peran', $this->peranGlobalMappingAuditor())
                            ->values(),
                    ];
                });

            $peranSaya = $this->mappingAuditYangBolehDilihat($pegawai->pegawaiid, $tahun)->values();

            return $this->respond([
                'tahun' => $tahun,
                'jenisAudit' => $this->referensiJenisAuditKetidaksesuaian(),
                'peran' => $this->referensiPeranMappingAuditor(),
                'groups' => $groups,
                'rows' => $rows,
                'leadAuditor' => $leadAuditor,
                'peranSaya' => $peranSaya,
                'pegawai' => $pegawai,
                'sumberDokumen' => 'FMMO-163-14.4.3.b-88.2 Penunjukan Auditor Internal 2026',
            ]);
        } catch (\Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal');
        }
    }

    public function saveMappingAuditorInternal(Request $request)
    {
        DB::beginTransaction();
        try {
            $pengisi = $this->pegawaiPengisiKetidaksesuaian();
            $tahun = $this->validasiTahunMapping($request->input('tahun'));
            $this->validasiAksesTulisManagerAuditInternal($pengisi->pegawaiid);
            $peran = trim((string) $request->input('peran'));
            $peranValid = collect($this->referensiPeranMappingAuditor())->pluck('value')->all();
            if (!in_array($peran, $peranValid, true)) {
                throw new \Exception('Peran mapping auditor tidak valid');
            }

            $jenisAudit = collect($this->referensiJenisAuditKetidaksesuaian());
            $kodeJenis = trim((string) $request->input('kodejenisaudit'));
            $jenis = $jenisAudit
                ->first(function ($item) use ($kodeJenis) {
                    return $item['value'] === $kodeJenis;
                });
            $peranGlobal = in_array($peran, $this->peranGlobalMappingAuditor(), true);
            if (!$peranGlobal && !$jenis) {
                throw new \Exception('Jenis audit wajib dipilih dari daftar');
            }
            if ($peranGlobal) {
                $jenis = $jenisAudit->first();
            }

            $pegawaiId = (int) $request->input('pegawaifk');
            $pegawai = DB::table('pegawai_m')
                ->select('id', 'namalengkap', 'jabatan1fk')
                ->where('id', $pegawaiId)
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('statusenabled', true)
                ->first();
            if (!$pegawai) {
                throw new \Exception('Pegawai yang dipilih tidak ditemukan');
            }
            if ((int) $pegawai->jabatan1fk === 2) {
                throw new \Exception('Pegawai dengan jabatan Manager otomatis mendapat akses lihat saja dan tidak perlu dimapping.');
            }

            if ($peranGlobal) {
                $tugasGlobal = 'Lead Auditor';
                $urutanGlobal = 1;
                $mappingId = null;
                $sumberDokumen = trim((string) $request->input('sumberdokumen', '')) ?: null;

                foreach ($jenisAudit as $jenisLead) {
                    $globalRows = DB::table('mappingauditorinternal_m')
                        ->where('kdprofile', (int) $this->kdProfile)
                        ->where('tahun', $tahun)
                        ->where('kodejenisaudit', $jenisLead['value'])
                        ->where('peran', $peran)
                        ->orderBy('id')
                        ->get();
                    $globalAktif = $globalRows->first();
                    $dataGlobal = [
                        'statusenabled' => true,
                        'jenisaudit' => $jenisLead['label'],
                        'lingkup' => $jenisLead['lingkup'],
                        'lokasi' => $jenisLead['lokasi'],
                        'pegawaifk' => $pegawai->id,
                        'namapegawai' => trim($pegawai->namalengkap),
                        'tugas' => $tugasGlobal,
                        'urutan' => $urutanGlobal,
                        'sumberdokumen' => $sumberDokumen,
                        'updatedby' => $pengisi->loginuserid,
                        'updated_at' => now(),
                    ];

                    if ($globalAktif) {
                        DB::table('mappingauditorinternal_m')->where('id', $globalAktif->id)->update($dataGlobal);
                        $mappingId = $mappingId ?: $globalAktif->id;
                        DB::table('mappingauditorinternal_m')
                            ->whereIn('id', $globalRows->pluck('id')->filter(function ($id) use ($globalAktif) {
                                return (int) $id !== (int) $globalAktif->id;
                            })->all())
                            ->update([
                                'statusenabled' => false,
                                'updatedby' => $pengisi->loginuserid,
                                'updated_at' => now(),
                            ]);
                    } else {
                        $dataGlobal['kdprofile'] = (int) $this->kdProfile;
                        $dataGlobal['norec'] = (string) $this->Uuid4();
                        $dataGlobal['tahun'] = $tahun;
                        $dataGlobal['kodejenisaudit'] = $jenisLead['value'];
                        $dataGlobal['peran'] = $peran;
                        $dataGlobal['createdby'] = $pengisi->loginuserid;
                        $dataGlobal['created_at'] = now();
                        $insertedId = DB::table('mappingauditorinternal_m')->insertGetId($dataGlobal);
                        $mappingId = $mappingId ?: $insertedId;
                    }
                }

                DB::commit();
                return $this->respond([
                    'id' => $mappingId,
                    'message' => 'Lead Auditor tahunan berhasil disimpan untuk seluruh jenis audit',
                ], 200, 'Sukses');
            }

            $defaultTugas = [
                'lead_auditor' => 'Lead Auditor',
                'auditor_observer' => 'Auditor Observer',
                'auditor' => 'Anggota Auditor ' . $jenis['lingkup']
                    . ($jenis['lokasi'] === 'Jakarta & Gresik' ? '' : ' ' . $jenis['lokasi']),
                'auditee' => 'Auditee ' . $jenis['lingkup']
                    . ($jenis['lokasi'] === 'Jakarta & Gresik' ? '' : ' ' . $jenis['lokasi']),
            ];
            $tugas = trim((string) $request->input('tugas', '')) ?: $defaultTugas[$peran];

            $id = $request->input('id');
            $existing = null;
            if ($id) {
                $existing = DB::table('mappingauditorinternal_m')
                    ->where('id', (int) $id)
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->first();
                if (!$existing) {
                    throw new \Exception('Data mapping auditor tidak ditemukan');
                }
            }

            $duplicate = DB::table('mappingauditorinternal_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tahun', $tahun)
                ->where('kodejenisaudit', $kodeJenis)
                ->where('pegawaifk', $pegawai->id)
                ->where('peran', $peran)
                ->when($existing, function ($query) use ($existing) {
                    return $query->where('id', '<>', $existing->id);
                })
                ->first();

            if ($duplicate && $duplicate->statusenabled) {
                throw new \Exception('Pegawai sudah memiliki peran tersebut pada jenis audit dan tahun yang sama');
            }

            $urutanDefault = $peran === 'lead_auditor' ? 1
                : ($peran === 'auditor_observer' ? 2 : ($peran === 'auditor' ? 5 : 10));
            $data = [
                'statusenabled' => true,
                'tahun' => $tahun,
                'kodejenisaudit' => $jenis['value'],
                'jenisaudit' => $jenis['label'],
                'lingkup' => $jenis['lingkup'],
                'lokasi' => $jenis['lokasi'],
                'pegawaifk' => $pegawai->id,
                'namapegawai' => trim($pegawai->namalengkap),
                'peran' => $peran,
                'tugas' => $tugas,
                'urutan' => (int) $request->input('urutan', $urutanDefault),
                'sumberdokumen' => trim((string) $request->input('sumberdokumen', '')) ?: null,
                'updatedby' => $pengisi->loginuserid,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('mappingauditorinternal_m')->where('id', $existing->id)->update($data);
                $mappingId = $existing->id;
            } else if ($duplicate) {
                DB::table('mappingauditorinternal_m')->where('id', $duplicate->id)->update($data);
                $mappingId = $duplicate->id;
            } else {
                $data['kdprofile'] = (int) $this->kdProfile;
                $data['norec'] = (string) $this->Uuid4();
                $data['createdby'] = $pengisi->loginuserid;
                $data['created_at'] = now();
                $mappingId = DB::table('mappingauditorinternal_m')->insertGetId($data);
            }

            DB::commit();
            return $this->respond([
                'id' => $mappingId,
                'message' => 'Mapping auditor berhasil disimpan',
            ], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Simpan Gagal');
        }
    }

    public function hapusMappingAuditorInternal(Request $request)
    {
        try {
            $pengisi = $this->pegawaiPengisiKetidaksesuaian();
            $mapping = DB::table('mappingauditorinternal_m')
                ->where('id', (int) $request->input('id'))
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('statusenabled', true)
                ->first();
            if (!$mapping) {
                throw new \Exception('Data mapping auditor tidak ditemukan');
            }
            $this->validasiAksesTulisManagerAuditInternal($pengisi->pegawaiid);

            DB::table('mappingauditorinternal_m')
                ->where('id', $mapping->id)
                ->update([
                    'statusenabled' => false,
                    'updatedby' => $this->getUserId(),
                    'updated_at' => now(),
                ]);

            return $this->respond(['message' => 'Mapping auditor berhasil dihapus'], 200, 'Sukses');
        } catch (\Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Hapus Gagal');
        }
    }

    public function salinMappingAuditorInternal(Request $request)
    {
        DB::beginTransaction();
        try {
            $pengisi = $this->pegawaiPengisiKetidaksesuaian();
            $tahunSumber = $this->validasiTahunMapping($request->input('tahunSumber'));
            $tahunTujuan = $this->validasiTahunMapping($request->input('tahunTujuan'));
            $this->validasiAksesTulisManagerAuditInternal($pengisi->pegawaiid);
            if ($tahunSumber === $tahunTujuan) {
                throw new \Exception('Tahun sumber dan tujuan harus berbeda');
            }

            $sourceRows = DB::table('mappingauditorinternal_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tahun', $tahunSumber)
                ->where('statusenabled', true)
                ->orderBy('urutan')
                ->get();
            if ($sourceRows->isEmpty()) {
                throw new \Exception('Mapping pada tahun sumber belum tersedia');
            }

            $copied = 0;
            foreach ($sourceRows as $source) {
                $target = DB::table('mappingauditorinternal_m')
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->where('tahun', $tahunTujuan)
                    ->where('kodejenisaudit', $source->kodejenisaudit)
                    ->where('pegawaifk', $source->pegawaifk)
                    ->where('peran', $source->peran)
                    ->first();

                $data = [
                    'statusenabled' => true,
                    'jenisaudit' => $source->jenisaudit,
                    'lingkup' => $source->lingkup,
                    'lokasi' => $source->lokasi,
                    'namapegawai' => $source->namapegawai,
                    'tugas' => $source->tugas,
                    'urutan' => $source->urutan,
                    'sumberdokumen' => 'Disalin dari mapping tahun ' . $tahunSumber,
                    'updatedby' => $this->getUserId(),
                    'updated_at' => now(),
                ];

                if ($target) {
                    DB::table('mappingauditorinternal_m')->where('id', $target->id)->update($data);
                } else {
                    $data['kdprofile'] = (int) $this->kdProfile;
                    $data['norec'] = (string) $this->Uuid4();
                    $data['tahun'] = $tahunTujuan;
                    $data['kodejenisaudit'] = $source->kodejenisaudit;
                    $data['pegawaifk'] = $source->pegawaifk;
                    $data['peran'] = $source->peran;
                    $data['createdby'] = $this->getUserId();
                    $data['created_at'] = now();
                    DB::table('mappingauditorinternal_m')->insert($data);
                    $copied++;
                }
            }

            DB::commit();
            return $this->respond([
                'copied' => $copied,
                'message' => 'Mapping tahun ' . $tahunSumber . ' berhasil disalin ke ' . $tahunTujuan,
            ], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Salin Gagal');
        }
    }
}
