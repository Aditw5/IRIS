<?php

namespace App\Http\Controllers\ChatBot;

use App\Http\Controllers\Controller;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiChatBotCtrl extends Controller
{
    use Valet;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    /* ============================================================
     * CUSTOMER ENDPOINTS
     * ============================================================ */

    public function historyOrderCustomer(Request $r)
    {
        $ctx = $this->getCustomerContext($r);

        if (!$ctx['success']) {
            return $this->respond($ctx, $ctx['statusCode'] ?? 401);
        }

        $mitraUser = $ctx['mitrafk'];
        $search = trim((string) $r->input('search', ''));
        $limit = $this->safeLimit($r->input('limit', 20), 50);

        $query = $this->baseCustomerHistoryQuery($mitraUser);
        $searchCandidates = $this->buildSearchCandidates($search);

        if (count($searchCandidates) > 0) {
            $this->applyCustomerHistorySearch($query, $searchCandidates);
        }

        $totalData = (clone $query)->count();

        $countVerif = (clone $query)
            ->where('mtr.verifregiscustomer', true)
            ->count();

        $countBelumVerif = (clone $query)
            ->whereNull('mtr.verifregiscustomer')
            ->count();

        $rows = $query
            ->orderBy('mtr.jenisorder', 'desc')
            ->orderBy('mtr.tglregistrasi', 'desc')
            ->limit($limit)
            ->get();

        $items = $this->mapCustomerHistoryRows($rows);

        return $this->respond([
            'success' => true,
            'message' => 'History order customer berhasil diambil.',
            'user_login' => [
                'userId' => $ctx['userId'],
                'username' => $ctx['username'],
                'kelompokUserId' => $ctx['kelompokUserId'],
                'mitrafk' => $mitraUser,
            ],
            'search' => $search,
            'search_candidates' => $searchCandidates,
            'limit' => $limit,
            'totalData' => $totalData,
            'countVerif' => $countVerif,
            'countBelumVerif' => $countBelumVerif,
            'chatbot_summary' => $this->buildCustomerHistorySummary($items, $search),
            'data' => $items,
        ]);
    }

    public function alatCustomer(Request $r)
    {
        $ctx = $this->getCustomerContext($r);

        if (!$ctx['success']) {
            return $this->respond($ctx, $ctx['statusCode'] ?? 401);
        }

        $mitraUser = $ctx['mitrafk'];
        $search = trim((string) $r->input('search', ''));
        $limit = $this->safeLimit($r->input('limit', 20), 50);

        $query = DB::table('mapunittoalat_m as mmp')
            ->select(
                'mmp.id',
                'mmp.namaproduk',
                'mmp.namatipe',
                'mmp.namamerk',
                'mmp.namaserialnumber',
                'mmp.fotoproduk',
                'mmp.statuskanfk',
                'mmp.created_at'
            )
            ->where('mmp.objectmitrafk', $mitraUser)
            ->where('mmp.statusenabled', true);

        $searchCandidates = $this->buildSearchCandidates($search);

        if (count($searchCandidates) > 0) {
            $query->where(function ($q) use ($searchCandidates) {
                foreach ($searchCandidates as $candidate) {
                    $term = '%' . $candidate . '%';

                    $q->orWhere('mmp.namaproduk', 'ilike', $term)
                        ->orWhere('mmp.namamerk', 'ilike', $term)
                        ->orWhere('mmp.namatipe', 'ilike', $term)
                        ->orWhere('mmp.namaserialnumber', 'ilike', $term)
                        ->orWhereRaw('CAST(mmp.id AS TEXT) ILIKE ?', [$term]);
                }
            });
        }

        $totalData = (clone $query)->count();

        $rows = $query
            ->orderBy('mmp.created_at', 'desc')
            ->limit($limit)
            ->get();

        $items = $rows->map(function ($item) {
            return [
                'idalat' => $item->id,
                'namaproduk' => $item->namaproduk,
                'namamerk' => $item->namamerk,
                'namatipe' => $item->namatipe,
                'namaserialnumber' => $item->namaserialnumber,
                'statuskanfk' => $item->statuskanfk,
                'fotoproduk' => $item->fotoproduk,
                'created_at' => $item->created_at,
                'display_name' => trim(
                    ($item->namaproduk ?? '-') .
                    ' merk ' . ($item->namamerk ?? '-') .
                    ' tipe ' . ($item->namatipe ?? '-') .
                    ' SN ' . ($item->namaserialnumber ?? '-')
                ),
            ];
        })->values();

        return $this->respond([
            'success' => true,
            'message' => 'Daftar alat customer berhasil diambil.',
            'search' => $search,
            'search_candidates' => $searchCandidates,
            'limit' => $limit,
            'totalData' => $totalData,
            'chatbot_summary' => $this->buildCustomerAlatSummary($items, $search),
            'data' => $items,
        ]);
    }

    public function keranjangCustomer(Request $r)
    {
        $ctx = $this->getCustomerContext($r);

        if (!$ctx['success']) {
            return $this->respond($ctx, $ctx['statusCode'] ?? 401);
        }

        $userId = $ctx['userId'];
        $search = trim((string) $r->input('search', ''));

        $query = DB::table('keranjangcustomer_t as kc')
            ->leftJoin('mapunittoalat_m as mmp', 'mmp.id', '=', 'kc.idalat')
            ->select(
                'kc.norec',
                'kc.jenisorder',
                'kc.idalat',
                'mmp.id',
                'mmp.namaproduk',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mmp.fotoproduk'
            )
            ->where('kc.customerid', $userId)
            ->where('kc.statusenabled', true)
            ->where('mmp.statusenabled', true);

        $searchCandidates = $this->buildSearchCandidates($search);

        if (count($searchCandidates) > 0) {
            $query->where(function ($q) use ($searchCandidates) {
                foreach ($searchCandidates as $candidate) {
                    $term = '%' . $candidate . '%';

                    $q->orWhere('mmp.namaproduk', 'ilike', $term)
                        ->orWhere('mmp.namamerk', 'ilike', $term)
                        ->orWhere('mmp.namatipe', 'ilike', $term)
                        ->orWhere('mmp.namaserialnumber', 'ilike', $term)
                        ->orWhereRaw('CAST(mmp.id AS TEXT) ILIKE ?', [$term]);
                }
            });
        }

        $rows = $query
            ->orderBy('kc.jenisorder')
            ->get();

        $items = $rows->map(function ($item) {
            return [
                'norec' => $item->norec,
                'jenisorder' => $item->jenisorder,
                'idalat' => $item->idalat,
                'alat' => [
                    'namaproduk' => $item->namaproduk,
                    'namamerk' => $item->namamerk,
                    'namatipe' => $item->namatipe,
                    'namaserialnumber' => $item->namaserialnumber,
                    'fotoproduk' => $item->fotoproduk,
                ],
            ];
        })->values();

        return $this->respond([
            'success' => true,
            'message' => 'Keranjang customer berhasil diambil.',
            'search' => $search,
            'search_candidates' => $searchCandidates,
            'totalData' => $items->count(),
            'chatbot_summary' => $this->buildCustomerKeranjangSummary($items, $search),
            'data' => $items,
        ]);
    }

    public function summaryCustomer(Request $r)
    {
        $ctx = $this->getCustomerContext($r);

        if (!$ctx['success']) {
            return $this->respond($ctx, $ctx['statusCode'] ?? 401);
        }

        $mitraUser = $ctx['mitrafk'];
        $userId = $ctx['userId'];

        $totalAlat = DB::table('mapunittoalat_m')
            ->where('objectmitrafk', $mitraUser)
            ->where('statusenabled', true)
            ->count();

        $totalKeranjang = DB::table('keranjangcustomer_t')
            ->where('customerid', $userId)
            ->where('statusenabled', true)
            ->count();

        $historyQuery = $this->baseCustomerHistoryQuery($mitraUser);
        $rows = (clone $historyQuery)->get();

        $totalOrderAlat = $rows->count();
        $countSelesai = 0;
        $countProses = 0;
        $countBelumVerif = 0;
        $countMenungguPersetujuan = 0;

        foreach ($rows as $row) {
            $progress = $this->buildCustomerProgress($row);

            if (($progress['status'] ?? '') === 'Selesai') {
                $countSelesai++;
            } elseif (($progress['status'] ?? '') === 'Menunggu Verifikasi') {
                $countBelumVerif++;
            } elseif (($progress['status'] ?? '') === 'Menunggu Persetujuan Hasil') {
                $countMenungguPersetujuan++;
            } else {
                $countProses++;
            }
        }

        $profile = $this->getCustomerProfileData($ctx);

        return $this->respond([
            'success' => true,
            'message' => 'Ringkasan customer berhasil diambil.',
            'profile' => $profile,
            'summary' => [
                'total_alat' => $totalAlat,
                'total_keranjang' => $totalKeranjang,
                'total_order_alat' => $totalOrderAlat,
                'total_selesai' => $countSelesai,
                'total_proses' => $countProses,
                'total_belum_verifikasi' => $countBelumVerif,
                'total_menunggu_persetujuan' => $countMenungguPersetujuan,
            ],
            'chatbot_summary' =>
                'Ringkasan akun customer: total alat terdaftar ' . $totalAlat .
                ', alat di keranjang ' . $totalKeranjang .
                ', total order alat ' . $totalOrderAlat .
                ', selesai ' . $countSelesai .
                ', masih proses ' . $countProses .
                ', belum verifikasi ' . $countBelumVerif .
                ', dan menunggu persetujuan hasil ' . $countMenungguPersetujuan . '.',
        ]);
    }

    public function certificateStatusCustomer(Request $r)
    {
        $ctx = $this->getCustomerContext($r);

        if (!$ctx['success']) {
            return $this->respond($ctx, $ctx['statusCode'] ?? 401);
        }

        $mitraUser = $ctx['mitrafk'];
        $search = trim((string) $r->input('search', ''));
        $limit = $this->safeLimit($r->input('limit', 20), 50);

        $query = $this->baseCustomerHistoryQuery($mitraUser);
        $searchCandidates = $this->buildSearchCandidates($search);

        if (count($searchCandidates) > 0) {
            $this->applyCustomerHistorySearch($query, $searchCandidates);
        }

        $rows = $query
            ->orderBy('mtr.tglregistrasi', 'desc')
            ->limit($limit)
            ->get();

        $items = $rows->map(function ($item) {
            $progress = $this->buildCustomerProgress($item);

            $isDone = ($progress['status'] ?? '') === 'Selesai';
            $isSurveyFilled = $this->toBool($item->isireviewalat ?? false);
            $isReceived = $this->toBool($item->isterima ?? false);

            $canPrint = $isDone && $isReceived && $isSurveyFilled;

            $documentType = strtolower((string) $item->jenisorder) === 'repair'
                ? 'laporan repair'
                : 'sertifikat kalibrasi';

            return [
                'norec' => $item->norec,
                'norec_detail' => $item->norec_detail,
                'nopendaftaran' => $item->nopendaftaran,
                'noorderalat' => $item->noorderalat,
                'jenisorder' => $item->jenisorder,
                'document_type' => $documentType,
                'alat' => [
                    'idalat' => $item->idalat,
                    'namaproduk' => $item->namaproduk,
                    'namamerk' => $item->namamerk,
                    'namatipe' => $item->namatipe,
                    'namaserialnumber' => $item->namaserialnumber,
                ],
                'progress' => $progress,
                'is_done' => $isDone,
                'is_received' => $isReceived,
                'is_survey_filled' => $isSurveyFilled,
                'can_print' => $canPrint,
                'print_label' => $canPrint
                    ? ucfirst($documentType) . ' sudah dapat dicetak.'
                    : $this->buildCertificateBlockingReason($isDone, $isReceived, $isSurveyFilled, $documentType),
            ];
        })->values();

        return $this->respond([
            'success' => true,
            'message' => 'Status sertifikat/laporan customer berhasil diambil.',
            'search' => $search,
            'search_candidates' => $searchCandidates,
            'totalData' => $items->count(),
            'chatbot_summary' => $this->buildCustomerCertificateSummary($items, $search),
            'data' => $items,
        ]);
    }

    public function profileCustomer(Request $r)
    {
        $ctx = $this->getCustomerContext($r);

        if (!$ctx['success']) {
            return $this->respond($ctx, $ctx['statusCode'] ?? 401);
        }

        $profile = $this->getCustomerProfileData($ctx);

        return $this->respond([
            'success' => true,
            'message' => 'Profil customer berhasil diambil.',
            'profile' => $profile,
            'chatbot_summary' =>
                'Akun login terdaftar atas nama ' . ($profile['namaUser'] ?? '-') .
                ', kelompok user customer, unit/perusahaan ' . ($profile['mitra']['namaperusahaan'] ?? '-') .
                ', jabatan ' . ($profile['jabatan'] ?? '-') . '.',
        ]);
    }

    public function historyOrderGroupCustomer(Request $r)
    {
        $ctx = $this->getCustomerContext($r);

        if (!$ctx['success']) {
            return $this->respond($ctx, $ctx['statusCode'] ?? 401);
        }

        $mitraUser = $ctx['mitrafk'];
        $search = trim((string) $r->input('search', ''));
        $limit = $this->safeLimit($r->input('limit', 20), 50);

        $query = DB::table('mitra_m as mt')
            ->leftJoin('mitraregistrasi_t as mtr', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->select(
                'mt.id',
                'mt.namaperusahaan',
                'mtr.norec as norec_registrasi',
                'mtr.tglregistrasi',
                'mtr.petugas',
                'mtr.nopendaftaran',
                'mtr.lokasikalibrasi',
                'mtr.lokasirepair',
                'mtr.statusorder',
                'mtr.jenisorder',
                'mtr.verifregiscustomer',
                'mtr.filecustomertools',
                'mtr.filecustomerams',
                'mtr.iskaji',
                'lk.lokasi'
            )
            ->where('mt.statusenabled', true)
            ->where('mt.id', $mitraUser)
            ->where('mtr.statusenabled', true);

        $searchCandidates = $this->buildSearchCandidates($search);

        if (count($searchCandidates) > 0) {
            $query->where(function ($q) use ($searchCandidates) {
                foreach ($searchCandidates as $candidate) {
                    $term = '%' . $candidate . '%';

                    $q->orWhere('mt.namaperusahaan', 'ilike', $term)
                        ->orWhere('mtr.nopendaftaran', 'ilike', $term)
                        ->orWhere('mtr.jenisorder', 'ilike', $term);
                }
            });
        }

        $headers = $query
            ->orderByDesc('mtr.tglregistrasi')
            ->limit($limit)
            ->get();

        $items = $headers->map(function ($item) {
            $details = DB::table('mitraregistrasidetail_t as d')
                ->leftJoin('mapunittoalat_m as a', 'a.id', '=', 'd.namaalatfk')
                ->select(
                    'd.norec',
                    'd.noorderalat',
                    'd.tglisilembarkerjapelaksana',
                    'd.tglsetujumanagerlembarkerja',
                    'd.tglsetujumanagerlaporanrepair',
                    'd.isterima',
                    'd.isireviewalat',
                    'a.namaproduk',
                    'a.namamerk',
                    'a.namatipe',
                    'a.namaserialnumber'
                )
                ->where('d.noregistrasifk', $item->norec_registrasi)
                ->where('d.statusenabled', true)
                ->get();

            $jumlahDetail = $details->count();
            $jumlahSelesai = 0;
            $jumlahDiterima = 0;
            $jumlahSurvey = 0;

            foreach ($details as $detail) {
                $done = strtolower((string) $item->jenisorder) === 'repair'
                    ? !empty($detail->tglsetujumanagerlaporanrepair)
                    : !empty($detail->tglsetujumanagerlembarkerja);

                if ($done) {
                    $jumlahSelesai++;
                }

                if ($this->toBool($detail->isterima ?? false)) {
                    $jumlahDiterima++;
                }

                if ($this->toBool($detail->isireviewalat ?? false)) {
                    $jumlahSurvey++;
                }
            }

            return [
                'norec_registrasi' => $item->norec_registrasi,
                'nopendaftaran' => $item->nopendaftaran,
                'tglregistrasi' => $item->tglregistrasi,
                'jenisorder' => $item->jenisorder,
                'lokasi' => $item->lokasi,
                'namaperusahaan' => $item->namaperusahaan,
                'verifregiscustomer' => $item->verifregiscustomer,
                'iskaji' => $item->iskaji,
                'jumlahdetail' => $jumlahDetail,
                'jumlahselesai' => $jumlahSelesai,
                'jumlahbelumselesai' => $jumlahDetail - $jumlahSelesai,
                'jumlahditerima' => $jumlahDiterima,
                'jumlahbelumterima' => $jumlahDetail - $jumlahDiterima,
                'jumlahsurvey' => $jumlahSurvey,
                'jumlahbelumsurvey' => $jumlahDetail - $jumlahSurvey,
                'details_preview' => $details->take(5)->values(),
            ];
        })->values();

        return $this->respond([
            'success' => true,
            'message' => 'History order kelompok customer berhasil diambil.',
            'search' => $search,
            'search_candidates' => $searchCandidates,
            'totalData' => $items->count(),
            'chatbot_summary' => $this->buildCustomerHistoryGroupSummary($items, $search),
            'data' => $items,
        ]);
    }

    /* ============================================================
     * CUSTOMER HELPERS
     * ============================================================ */

    private function getCustomerContext(Request $r)
    {
        $kelompokUserId = $this->getKelompokUserId();
        $pegawaiLogin = $this->getPegawai();
        $userId = $this->getUserId();
        $username = $this->getUsername();

        if (!$pegawaiLogin) {
            return [
                'success' => false,
                'statusCode' => 401,
                'message' => 'Session pegawai/customer tidak ditemukan. Pastikan request membawa cookie token login yang valid.',
                'debug' => [
                    'kelompokUserId' => $kelompokUserId,
                    'pegawaiLogin' => $pegawaiLogin,
                    'userId' => $userId,
                    'username' => $username,
                    'has_cookie_token' => $r->cookies->has('token'),
                    'has_header_cookie' => $r->headers->has('Cookie'),
                    'has_authorization' => $r->headers->has('Authorization'),
                    'has_header_token' => $r->headers->has('token'),
                    'session_login' => session('session_login'),
                ],
            ];
        }

        $mitraUser = $this->resolveMitraUserFromPegawai($pegawaiLogin, $userId);

        if (!$mitraUser) {
            return [
                'success' => false,
                'statusCode' => 400,
                'message' => 'mitrafk customer tidak ditemukan dari session login.',
                'debug' => [
                    'kelompokUserId' => $kelompokUserId,
                    'pegawaiLogin' => $pegawaiLogin,
                    'userId' => $userId,
                    'username' => $username,
                    'session_login' => session('session_login'),
                ],
            ];
        }

        if (!$kelompokUserId) {
            $kelompokUserId = 46;
        }

        if ((int) $kelompokUserId !== 46) {
            return [
                'success' => false,
                'statusCode' => 403,
                'message' => 'Endpoint ini hanya untuk kelompok user customer.',
                'kelompokUserId' => $kelompokUserId,
            ];
        }

        return [
            'success' => true,
            'kelompokUserId' => $kelompokUserId,
            'pegawaiLogin' => $pegawaiLogin,
            'userId' => $userId,
            'username' => $username,
            'mitrafk' => $mitraUser,
        ];
    }

    private function baseCustomerHistoryQuery($mitraUser)
    {
        return DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('mapunittoalat_m as mmp', function ($join) {
                $join->on('mmp.id', '=', 'mtrd.namaalatfk')
                    ->where('mmp.statusenabled', true);
            })
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.statusorderpenyelia',
                'mtrd.statusorderpelaksana',
                'mtrd.tglisilembarkerjapelaksana',
                'mtrd.tglisilaporanrepairpelaksana',
                'mtrd.pelaksanaisilembarkerjafk',
                'mtrd.noorderalat',
                'mtrd.isterima',
                'mtrd.isireviewalat',
                'mtrd.tglsetujupenyelialembarkerja',
                'mtrd.tglsetujuasmanlembarkerja',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglsetujupenyelialaporanrepair',
                'mtrd.tglsetujuasmanlaporanrepair',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mmp.id as idalat',
                'mmp.namaproduk',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mt.id as mitrafk',
                'mt.namaperusahaan',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtr.verifregiscustomer',
                'mtr.tanggalverifregiscustomer',
                'mtr.iskaji as iskaji_header',
                'mtrd.tglverifasman'
            )
            ->where('mt.id', $mitraUser)
            ->where('mtr.statusenabled', true)
            ->whereNull('mtr.isstandarulab')
            ->where('mtrd.statusenabled', true);
    }

    private function applyCustomerHistorySearch($query, array $searchCandidates)
    {
        $query->where(function ($q) use ($searchCandidates) {
            foreach ($searchCandidates as $candidate) {
                $term = '%' . $candidate . '%';

                $q->orWhere('mmp.namaproduk', 'ilike', $term)
                    ->orWhere('mmp.namamerk', 'ilike', $term)
                    ->orWhere('mmp.namatipe', 'ilike', $term)
                    ->orWhere('mmp.namaserialnumber', 'ilike', $term)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $term)
                    ->orWhere('mtrd.noorderalat', 'ilike', $term)
                    ->orWhereRaw('CAST(mmp.id AS TEXT) ILIKE ?', [$term]);
            }
        });
    }

    private function mapCustomerHistoryRows($rows)
    {
        return $rows->map(function ($item) {
            $progress = $this->buildCustomerProgress($item);

            return [
                'norec' => $item->norec,
                'norec_detail' => $item->norec_detail,
                'nopendaftaran' => $item->nopendaftaran,
                'noorderalat' => $item->noorderalat,
                'tglregistrasi' => $item->tglregistrasi,
                'jenisorder' => $item->jenisorder,
                'mitra' => [
                    'id' => $item->mitrafk,
                    'namaperusahaan' => $item->namaperusahaan,
                ],
                'alat' => [
                    'idalat' => $item->idalat,
                    'namaproduk' => $item->namaproduk,
                    'namamerk' => $item->namamerk,
                    'namatipe' => $item->namatipe,
                    'namaserialnumber' => $item->namaserialnumber,
                    'lingkupkalibrasi' => $item->lingkupkalibrasi,
                    'lokasi' => $item->lokasi,
                ],
                'status_raw' => [
                    'verifregiscustomer' => $item->verifregiscustomer,
                    'tanggalverifregiscustomer' => $item->tanggalverifregiscustomer,
                    'iskaji' => $item->iskaji,
                    'tglverifasman' => $item->tglverifasman,
                    'tglisilembarkerjapelaksana' => $item->tglisilembarkerjapelaksana,
                    'tglisilaporanrepairpelaksana' => $item->tglisilaporanrepairpelaksana,
                    'statusorderpenyelia' => $item->statusorderpenyelia,
                    'statusorderpelaksana' => $item->statusorderpelaksana,
                    'tglsetujumanagerlembarkerja' => $item->tglsetujumanagerlembarkerja,
                    'tglsetujumanagerlaporanrepair' => $item->tglsetujumanagerlaporanrepair,
                    'namafile' => $item->namafile,
                ],
                'progress' => $progress,
            ];
        })->values();
    }

    private function buildCustomerProgress($item)
    {
        $isVerifCustomer = $this->toBool($item->verifregiscustomer ?? false);
        $isKaji = $this->toBool($item->iskaji ?? false) || $this->toBool($item->iskaji_header ?? false);

        $hasInputPelaksana = !empty($item->tglisilembarkerjapelaksana) || !empty($item->tglisilaporanrepairpelaksana);

        $jenisOrder = strtolower(trim((string) ($item->jenisorder ?? '')));

        $isDoneKalibrasi = $jenisOrder === 'kalibrasi'
            && !empty($item->tglsetujumanagerlembarkerja);

        $isDoneRepair = $jenisOrder === 'repair'
            && !empty($item->tglsetujumanagerlaporanrepair);

        $isDone = $isDoneKalibrasi || $isDoneRepair;

        if (!$isVerifCustomer) {
            return [
                'step' => 1,
                'percentage' => 25,
                'status' => 'Menunggu Verifikasi',
                'label' => 'Order sudah dibuat dan sedang menunggu verifikasi petugas registrasi.',
                'description' => 'Customer sudah membuat order, tetapi order belum diverifikasi oleh petugas registrasi.',
            ];
        }

        if ($isVerifCustomer && !$isKaji) {
            return [
                'step' => 2,
                'percentage' => 50,
                'status' => 'Menunggu Kaji Ulang',
                'label' => 'Order sudah diverifikasi dan sedang menunggu kaji ulang admin.',
                'description' => 'Order sudah diverifikasi oleh registrasi, tetapi belum selesai tahap kaji ulang.',
            ];
        }

        if ($isKaji && !$hasInputPelaksana && !$isDone) {
            return [
                'step' => 3,
                'percentage' => 75,
                'status' => 'Sedang Diproses',
                'label' => 'Order sudah dikaji ulang dan alat sedang masuk proses pengerjaan.',
                'description' => 'Alat sudah melewati tahap verifikasi dan kaji ulang. Saat ini alat berada pada proses pengerjaan atau menunggu input hasil dari pelaksana.',
            ];
        }

        if ($hasInputPelaksana && !$isDone) {
            return [
                'step' => 3,
                'percentage' => 85,
                'status' => 'Menunggu Persetujuan Hasil',
                'label' => 'Pelaksana sudah mengisi hasil pekerjaan dan sedang menunggu validasi atau persetujuan.',
                'description' => 'Hasil pekerjaan sudah diinput oleh pelaksana. Tahap berikutnya adalah pemeriksaan dan persetujuan hasil.',
            ];
        }

        if ($isDone) {
            return [
                'step' => 4,
                'percentage' => 100,
                'status' => 'Selesai',
                'label' => 'Pengerjaan alat sudah selesai.',
                'description' => 'Alat sudah selesai diproses. Jika sertifikat atau laporan sudah tersedia, customer dapat mengakses atau mencetak dokumen sesuai ketentuan sistem.',
            ];
        }

        return [
            'step' => 0,
            'percentage' => 0,
            'status' => 'Status Tidak Diketahui',
            'label' => 'Status order belum dapat ditentukan.',
            'description' => 'Data progress belum lengkap atau belum sesuai dengan alur status yang tersedia.',
        ];
    }

    /* ============================================================
     * CUSTOMER SUMMARY HELPERS
     * ============================================================ */

    private function buildCustomerHistorySummary($items, $search = '')
    {
        if ($items->count() === 0) {
            if ($search !== '') {
                return 'Saya belum menemukan alat atau order customer yang sesuai dengan kata kunci "' . $search . '".';
            }

            return 'Saya belum menemukan history order customer.';
        }

        if ($items->count() === 1) {
            $item = $items->first();

            return 'Alat yang ditemukan adalah ' . ($item['alat']['namaproduk'] ?? '-') .
                ' merk ' . ($item['alat']['namamerk'] ?? '-') .
                ' tipe ' . ($item['alat']['namatipe'] ?? '-') .
                ' dengan serial number ' . ($item['alat']['namaserialnumber'] ?? '-') .
                '. Nomor order alat: ' . ($item['noorderalat'] ?? '-') .
                '. Status saat ini: ' . ($item['progress']['status'] ?? '-') .
                '. ' . ($item['progress']['label'] ?? '-');
        }

        $parts = [];
        $limit = min($items->count(), 5);

        for ($i = 0; $i < $limit; $i++) {
            $item = $items[$i];

            $parts[] = ($i + 1) . '. ' .
                ($item['alat']['namaproduk'] ?? '-') .
                ' | SN: ' . ($item['alat']['namaserialnumber'] ?? '-') .
                ' | Order: ' . ($item['noorderalat'] ?? '-') .
                ' | Status: ' . ($item['progress']['status'] ?? '-');
        }

        return 'Saya menemukan ' . $items->count() . ' data order customer yang sesuai. Berikut beberapa hasil teratas: ' . implode(' ', $parts);
    }

    private function buildCustomerAlatSummary($items, $search = '')
    {
        if ($items->count() === 0) {
            return $search !== ''
                ? 'Saya belum menemukan alat customer dengan kata kunci "' . $search . '".'
                : 'Saya belum menemukan alat terdaftar pada akun customer ini.';
        }

        if ($items->count() === 1) {
            $item = $items->first();

            return 'Alat customer yang ditemukan adalah ' . ($item['namaproduk'] ?? '-') .
                ' merk ' . ($item['namamerk'] ?? '-') .
                ' tipe ' . ($item['namatipe'] ?? '-') .
                ' dengan serial number ' . ($item['namaserialnumber'] ?? '-') . '.';
        }

        return 'Customer memiliki ' . $items->count() . ' alat yang sesuai dengan pencarian.';
    }

    private function buildCustomerKeranjangSummary($items, $search = '')
    {
        if ($items->count() === 0) {
            return $search !== ''
                ? 'Saya belum menemukan alat di keranjang yang sesuai dengan kata kunci "' . $search . '".'
                : 'Keranjang customer saat ini kosong.';
        }

        if ($items->count() === 1) {
            $item = $items->first();
            $alat = $item['alat'] ?? [];

            return 'Keranjang berisi 1 alat: ' . ($alat['namaproduk'] ?? '-') .
                ' merk ' . ($alat['namamerk'] ?? '-') .
                ' tipe ' . ($alat['namatipe'] ?? '-') .
                ' SN ' . ($alat['namaserialnumber'] ?? '-') .
                '. Jenis order: ' . ($item['jenisorder'] ?? '-') . '.';
        }

        return 'Keranjang customer berisi ' . $items->count() . ' alat.';
    }

    private function buildCustomerCertificateSummary($items, $search = '')
    {
        if ($items->count() === 0) {
            return $search !== ''
                ? 'Saya belum menemukan sertifikat atau laporan yang sesuai dengan kata kunci "' . $search . '".'
                : 'Saya belum menemukan data sertifikat atau laporan customer.';
        }

        if ($items->count() === 1) {
            $item = $items->first();

            return ucfirst($item['document_type'] ?? 'dokumen') .
                ' untuk order ' . ($item['noorderalat'] ?? '-') .
                ' atas alat ' . ($item['alat']['namaproduk'] ?? '-') .
                ' SN ' . ($item['alat']['namaserialnumber'] ?? '-') .
                '. Status: ' . ($item['progress']['status'] ?? '-') .
                '. ' . ($item['print_label'] ?? '-');
        }

        return 'Saya menemukan ' . $items->count() . ' data sertifikat atau laporan customer.';
    }

    private function buildCustomerHistoryGroupSummary($items, $search = '')
    {
        if ($items->count() === 0) {
            return 'Saya belum menemukan data pendaftaran/order customer.';
        }

        if ($items->count() === 1) {
            $item = $items->first();

            return 'Order terakhir/pendaftaran yang ditemukan adalah ' . ($item['nopendaftaran'] ?? '-') .
                ' tanggal ' . ($item['tglregistrasi'] ?? '-') .
                ', jenis order ' . ($item['jenisorder'] ?? '-') .
                ', jumlah alat ' . ($item['jumlahdetail'] ?? 0) .
                ', selesai ' . ($item['jumlahselesai'] ?? 0) .
                ', belum selesai ' . ($item['jumlahbelumselesai'] ?? 0) . '.';
        }

        return 'Saya menemukan ' . $items->count() . ' data pendaftaran/order customer.';
    }

    private function buildCertificateBlockingReason($isDone, $isReceived, $isSurveyFilled, $documentType)
    {
        $reasons = [];

        if (!$isDone) {
            $reasons[] = 'pekerjaan belum selesai';
        }

        if (!$isReceived) {
            $reasons[] = 'alat belum diterima customer';
        }

        if (!$isSurveyFilled) {
            $reasons[] = 'survey pelanggan belum diisi';
        }

        if (count($reasons) === 0) {
            return ucfirst($documentType) . ' belum dapat dicetak.';
        }

        return ucfirst($documentType) . ' belum dapat dicetak karena ' . implode(', ', $reasons) . '.';
    }

    /* ============================================================
     * GENERAL HELPERS
     * ============================================================ */

    private function resolveMitraUserFromPegawai($pegawaiLogin, $userId = null)
    {
        $mitraUser = null;

        if (is_array($pegawaiLogin)) {
            $mitraUser = $pegawaiLogin['mitrafk'] ?? null;
        }

        if (is_object($pegawaiLogin)) {
            $mitraUser = $pegawaiLogin->mitrafk ?? null;
        }

        if (!$mitraUser && $userId) {
            $user = DB::table('users')
                ->where('id', $userId)
                ->where('statusenabled', true)
                ->first();

            if ($user && isset($user->mitrafk)) {
                $mitraUser = $user->mitrafk;
            }
        }

        if (!$mitraUser && is_object($pegawaiLogin) && isset($pegawaiLogin->email)) {
            $user = DB::table('users')
                ->where('email', $pegawaiLogin->email)
                ->where('statusenabled', true)
                ->first();

            if ($user && isset($user->mitrafk)) {
                $mitraUser = $user->mitrafk;
            }
        }

        if (!$mitraUser && is_array($pegawaiLogin) && isset($pegawaiLogin['email'])) {
            $user = DB::table('users')
                ->where('email', $pegawaiLogin['email'])
                ->where('statusenabled', true)
                ->first();

            if ($user && isset($user->mitrafk)) {
                $mitraUser = $user->mitrafk;
            }
        }

        return $mitraUser ? (int) $mitraUser : null;
    }

    private function getCustomerProfileData($ctx)
    {
        $user = DB::table('users')
            ->where('id', $ctx['userId'])
            ->first();

        $mitra = DB::table('mitra_m')
            ->where('id', $ctx['mitrafk'])
            ->first();

        $pegawaiLogin = $ctx['pegawaiLogin'];

        $jabatan = null;
        $namaUser = null;
        $email = null;

        if (is_array($pegawaiLogin)) {
            $jabatan = $pegawaiLogin['jabatan'] ?? null;
            $namaUser = $pegawaiLogin['namalengkap'] ?? ($pegawaiLogin['namaLengkap'] ?? null);
            $email = $pegawaiLogin['email'] ?? null;
        }

        if (is_object($pegawaiLogin)) {
            $jabatan = $pegawaiLogin->jabatan ?? null;
            $namaUser = $pegawaiLogin->namalengkap ?? ($pegawaiLogin->namaLengkap ?? null);
            $email = $pegawaiLogin->email ?? null;
        }

        return [
            'userId' => $ctx['userId'],
            'username' => $ctx['username'],
            'namaUser' => $namaUser ?? ($user->name ?? '-'),
            'email' => $email ?? ($user->email ?? '-'),
            'jabatan' => $jabatan ?? ($user->jabatan ?? '-'),
            'kelompokUserId' => $ctx['kelompokUserId'],
            'mitra' => [
                'id' => $ctx['mitrafk'],
                'namaperusahaan' => $mitra->namaperusahaan ?? '-',
            ],
        ];
    }

    private function buildSearchCandidates($search)
    {
        $search = trim((string) $search);

        if ($search === '') {
            return [];
        }

        $normalized = strtolower($search);
        $normalized = str_replace(['"', "'", ',', ';', ':'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        $candidates = [];

        if ($normalized !== '') {
            $candidates[] = $normalized;
        }

        preg_match_all('/[a-zA-Z]{1,5}-\d{2}\.\d{1,2}\.\d{1,5}/', $search, $matchesOrder);
        foreach ($matchesOrder[0] ?? [] as $match) {
            $candidates[] = strtoupper($match);
        }

        preg_match_all('/[a-zA-Z]{1,5}-\d{2}-\d{1,5}/', $search, $matchesReg);
        foreach ($matchesReg[0] ?? [] as $match) {
            $candidates[] = strtoupper($match);
        }

        preg_match_all('/\b\d{5,}\b/', $search, $matchesNumber);
        foreach ($matchesNumber[0] ?? [] as $match) {
            $candidates[] = $match;
        }

        $equipmentAliases = [
            'thermal imager' => ['thermal imager', 'termal imager', 'thermal camera', 'kamera thermal', 'kamera suhu'],
            'caliper' => ['caliper', 'kaliper', 'jangka sorong'],
            'micrometer' => ['micrometer', 'mikrometer'],
            'thermometer' => ['thermometer', 'termometer'],
            'thermohygrometer' => ['thermohygrometer', 'thermohigrometer', 'hygrometer', 'higrometer'],
            'pressure gauge' => ['pressure gauge', 'pressure gage', 'presure gauge'],
            'clamp meter' => ['clamp meter', 'tang ampere'],
            'vibration meter' => ['vibration meter', 'vibrasi meter'],
            'dry block' => ['dry block', 'dryblock'],
            'dial indicator' => ['dial indicator'],
            'bore gauge' => ['bore gauge'],
            'tachometer' => ['tachometer'],
            'torque wrench' => ['torque wrench'],
        ];

        foreach ($equipmentAliases as $target => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($normalized, $alias)) {
                    $candidates[] = $target;
                    $candidates[] = $alias;
                }
            }
        }

        $ignoreWords = [
            'cara', 'bagaimana', 'gimana', 'apa', 'saja', 'aja', 'ada', 'saya', 'aku',
            'cek', 'lihat', 'melihat', 'status', 'progress', 'progres', 'order',
            'alat', 'sertifikat', 'laporan', 'repair', 'keranjang', 'terakhir',
            'sampai', 'mana', 'sudah', 'belum', 'bisa', 'dicetak', 'cetak',
            'verifikasi', 'verif', 'lembar', 'kerja', 'template', 'amandemen',
        ];

        $words = explode(' ', $normalized);
        $importantWords = [];

        foreach ($words as $word) {
            if ($word === '' || in_array($word, $ignoreWords)) {
                continue;
            }

            $importantWords[] = $word;
        }

        $cleaned = trim(implode(' ', $importantWords));

        if ($cleaned !== '' && strlen($cleaned) >= 3) {
            $candidates[] = $cleaned;
        }

        $final = [];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate === '') {
                continue;
            }

            if (!in_array($candidate, $final)) {
                $final[] = $candidate;
            }
        }

        return $final;
    }

    private function safeLimit($value, $max = 50)
    {
        $limit = (int) $value;

        if ($limit < 1) {
            $limit = 20;
        }

        if ($limit > $max) {
            $limit = $max;
        }

        return $limit;
    }

    private function toBool($value)
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }

        if ($value === false || $value === 0 || $value === '0') {
            return false;
        }

        return !empty($value);
    }
}