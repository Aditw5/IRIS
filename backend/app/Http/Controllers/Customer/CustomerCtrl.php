<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Master\Mitra;
use App\Models\Master\PaketKalibrasi;
use App\Models\Transaksi\KeranjangCustomer;
use App\Models\Transaksi\MitraRegistrasi;
use App\Models\Transaksi\MitraRegistrasiDetail;
use App\Services\ImageOptimizerService;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class CustomerCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function getAlatCustomer(Request $r, ImageOptimizerService $imageOptimizer)
    {
        $data = DB::table('mapunittoalat_m as mmp')
            ->select(
                'mmp.id',
                'mmp.namaproduk',
                'mmp.namatipe',
                'mmp.namamerk',
                'mmp.namaserialnumber',
                'mmp.fotoproduk',
                'mmp.statuskanfk',
            )
            ->where('mmp.objectmitrafk', $r['mitrauser'])
            ->where('mmp.statusenabled', true);

        if (isset($r['search']) && $r['search'] != '') {
            $searchTerm = '%' . $r['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mmp.namaproduk', 'ilike', $searchTerm)
                    ->orWhere('mmp.namamerk', 'ilike', $searchTerm)
                    ->orWhere('mmp.namaserialnumber', 'ilike', $searchTerm)
                    ->orWhere('mmp.namatipe', 'ilike', $searchTerm)
                    ->orWhere('mmp.id', 'ilike', $searchTerm);
            });
        }

        $page = 1;
        if (isset($r['page']) && $r['page'] != '') {
            $page = $r['page'];
        }

        // $data = $data->orderBy('mmp.namaproduk');
        $data = $data->orderBy('mmp.created_at', 'desc');
        $data = $data->paginate(isset($r['limit']) ? $r['limit'] : 10, ['*'], 'page', $page);

        $data->getCollection()->each(function ($item) use ($imageOptimizer) {
            $item->fotoproduk_thumbnail = $imageOptimizer->thumbnailFilename($item->fotoproduk);
        });

        return $this->respond($data);
    }

    public function keranjangCustomer(Request $r, ImageOptimizerService $imageOptimizer)
    {
        $data = DB::table('keranjangcustomer_t as kc')
            ->leftJoin('mapunittoalat_m as mmp', 'mmp.id', '=', 'kc.idalat')
            ->select(
                'kc.norec',
                'kc.jenisorder',
                'mmp.id',
                'mmp.namaproduk',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mmp.fotoproduk',
            )
            ->where('kc.customerid', $this->getPegawaiId())
            ->where('mmp.statusenabled', true)
            ->where('kc.statusenabled', true);

        if (isset($r['search']) && $r['search'] != '') {
            $searchTerm = '%' . $r['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mmp.namaproduk', 'ilike', $searchTerm)
                    ->orWhere('mmp.id', 'ilike', $searchTerm)
                    ->orWhere('mmp.namamerk', 'ilike', $searchTerm)
                    ->orWhere('mmp.namatipe', 'ilike', $searchTerm)
                    ->orWhere('mmp.namaserialnumber', 'ilike', $searchTerm);
            });
        }
        $data = $data->orderBy('kc.jenisorder');
        $data = $data->get();

        $data->each(function ($item) use ($imageOptimizer) {
            $item->fotoproduk_thumbnail = $imageOptimizer->thumbnailFilename($item->fotoproduk);
        });

        return $this->respond($data);
    }

    public function historyOrderKelompok(Request $r)
    {

        $data  = DB::table('mitra_m as mt')
            ->leftjoin('mitraregistrasi_t as mtr', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->leftJoin('users as us', DB::raw('CAST(us.mitrafk AS TEXT)'), '=', 'mt.id')
            ->select(
                'mt.id',
                'mt.namaperusahaan',
                'mt.tgldaftar',
                'mt.email',
                'mt.nohp',
                'mt.foto',
                'mt.progress',
                'mtr.tglregistrasi',
                'mtr.petugas',
                'mtr.nopendaftaran',
                'mtr.lokasikalibrasi',
                'mtr.lokasirepair',
                'mtr.norec as iddetail',
                'mtr.statusorder',
                'mtr.jenisorder',
                'mtr.verifregiscustomer',
                'mtr.isikepuasanpelanggan',
                'mtr.filecustomertools',
                'mtr.iskaji',
                'mtr.statusorder',
            )
            ->distinct()
            ->where('mt.statusenabled', true)
            // ->where('mtr.isregiscustomer', true)
            ->where('mt.id', $r['mtrauser'])
            ->where('mtr.statusenabled', true);

        if (isset($r['search']) && $r['search'] != '') {
            $searchTerm = '%' . $r['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mt.namaperusahaan', 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm);
            });
        }
        // $page = 1;
        // if (isset($r['page']) && $r['page'] != '') {
        //     $page = $r['page'];
        // }
        $data = $data->orderByDesc('mtr.tglregistrasi');
        // $data = $data->paginate(isset($r['limit']) ? $r['limit'] : 10, ['*'], 'page', $page);
        $data = $data->get();

        $detailAll = DB::table('mitraregistrasidetail_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregistrasifk');

        foreach ($data as $item) {
            $detailList = $detailAll[$item->iddetail] ?? collect();
            $item->jumlahdetail = $detailList->count();
            $item->jumlahselesai = 0;
            $item->jumlahditerima = 0;

            foreach ($detailList as $detail) {
                if (($item->jenisorder === 'kalibrasi' && $detail->tglsetujumanagerlembarkerja !== null) || ($item->jenisorder === 'repair' && $detail->tglsetujumanagerlaporanrepair !== null)) {
                    $item->jumlahselesai++;
                }
                if ($detail->isterima == true) {
                    $item->jumlahditerima++;
                }
            }
            $item->jumlahbelumselesai = $item->jumlahdetail - $item->jumlahselesai;
            $item->jumlahbelumterima = $item->jumlahdetail - $item->jumlahditerima;
        }

        return $this->respond($data);
    }

    public function historyOrder(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftjoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.statusorderpenyelia',
                'mtrd.tglisilembarkerjapelaksana',
                'mtrd.pelaksanaisilembarkerjafk',
                'mtrd.noorderalat',
                'mmp.id as idalat',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mt.namaperusahaan',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'mtr.jenisorder',
                'mtr.verifregiscustomer',
                'mtr.tanggalverifregiscustomer',
                'mtrd.tglverifasman',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglsetujumanagerlaporanrepair',
            )
            ->where('mt.id', $r['mtrauser'])
            ->where('mtr.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->whereNull('mtr.isstandarulab')
            ->where('mtrd.statusenabled', true);

        if (isset($r['search']) && $r['search'] != '') {
            $searchTerm = '%' . $r['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mmp.namaproduk', 'ilike', $searchTerm)
                    ->orWhere('mmp.id', 'ilike', $searchTerm)
                    ->orWhere('mmp.namamerk', 'ilike', $searchTerm)
                    ->orWhere('mmp.namatipe', 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm)
                    ->orWhere('mtrd.noorderalat', 'ilike', $searchTerm)
                    ->orWhere('mmp.namaserialnumber', 'ilike', $searchTerm);
            });
        }
        $page = 1;
        if (isset($r['page']) && $r['page'] != '') {
            $page = $r['page'];
        }

        $totalQuery = clone $data;
        $totalData = $totalQuery->count();

        $totalVerifQuery = clone $data;
        $countVerif = $totalVerifQuery->where('mtr.verifregiscustomer', true)->count();

        $totalBelumVerifQuery = clone $data;
        $countBelumVerif = $totalBelumVerifQuery->whereNull('mtr.verifregiscustomer')->count();

        $data = $data->orderBy('mtr.jenisorder', 'desc')
            ->orderBy('mtr.tglregistrasi', 'desc');
        $data = $data->paginate(isset($r['limit']) ? $r['limit'] : 10, ['*'], 'page', $page);

        $res['data'] = $data;
        $res['totalData'] = $totalData;
        $res['countVerif'] = $countVerif;
        $res['countBelumVerif'] = $countBelumVerif;
        return $this->respond($res);
    }

    public function laporanCustomer(Request $r)
    {
        $r->validate([
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
            'jenis_order' => 'nullable|in:kalibrasi,repair',
            'search' => 'nullable|string|max:120',
        ]);

        $pegawaiId = $this->getPegawaiId();
        $mitraFk = DB::table('users')->where('id', $pegawaiId)->value('mitrafk');

        if (empty($mitraFk)) {
            return $this->respond([
                'message' => 'Unit customer pada sesi login tidak ditemukan.',
            ], 422, 'Data unit customer tidak ditemukan');
        }

        $dari = $r->filled('dari') ? Carbon::parse($r->dari)->startOfDay() : null;
        $sampai = $r->filled('sampai') ? Carbon::parse($r->sampai)->endOfDay() : null;
        $jenisOrder = $r->filled('jenis_order') ? strtolower($r->jenis_order) : null;
        $search = trim((string) $r->input('search', ''));

        $applyRegistrationFilters = function ($query) use ($dari, $sampai, $jenisOrder, $search) {
            if ($dari) {
                $query->where('mtr.tglregistrasi', '>=', $dari);
            }
            if ($sampai) {
                $query->where('mtr.tglregistrasi', '<=', $sampai);
            }
            if ($jenisOrder) {
                $query->whereRaw('LOWER(mtr.jenisorder) = ?', [$jenisOrder]);
            }
            if ($search !== '') {
                $searchTerm = '%' . $search . '%';
                $query->where(function ($filter) use ($searchTerm) {
                    $filter->where('mtr.nopendaftaran', 'ilike', $searchTerm)
                        ->orWhere('mtr.jenisorder', 'ilike', $searchTerm)
                        ->orWhereExists(function ($subQuery) use ($searchTerm) {
                            $subQuery->select(DB::raw(1))
                                ->from('mitraregistrasidetail_t as cari_detail')
                                ->join('mapunittoalat_m as cari_alat', 'cari_alat.id', '=', 'cari_detail.namaalatfk')
                                ->whereColumn('cari_detail.noregistrasifk', 'mtr.norec')
                                ->where('cari_detail.statusenabled', true)
                                ->where(function ($alatFilter) use ($searchTerm) {
                                    $alatFilter->where('cari_detail.noorderalat', 'ilike', $searchTerm)
                                        ->orWhere('cari_alat.namaproduk', 'ilike', $searchTerm)
                                        ->orWhere('cari_alat.namamerk', 'ilike', $searchTerm)
                                        ->orWhere('cari_alat.namatipe', 'ilike', $searchTerm)
                                        ->orWhere('cari_alat.namaserialnumber', 'ilike', $searchTerm);
                                });
                        });
                });
            }
        };

        $unit = DB::table('mitra_m')
            ->select('id', 'namaperusahaan', 'email', 'nohp', 'alamatktr')
            ->where('id', $mitraFk)
            ->where('statusenabled', true)
            ->first();

        $registrationQuery = DB::table('mitraregistrasi_t as mtr')
            ->leftJoin('lokasikalibrasi_m as lokasi_kalibrasi', 'lokasi_kalibrasi.id', '=', 'mtr.lokasikalibrasi')
            ->leftJoin('lokasikalibrasi_m as lokasi_repair', 'lokasi_repair.id', '=', 'mtr.lokasirepair')
            ->select(
                'mtr.norec',
                'mtr.nopendaftaran',
                'mtr.tglregistrasi',
                'mtr.jenisorder',
                'mtr.statusorder',
                'mtr.petugas',
                'mtr.catatan',
                'mtr.verifregiscustomer',
                'mtr.tanggalverifregiscustomer',
                'mtr.filecustomertools',
                'mtr.iskaji',
                DB::raw("COALESCE(NULLIF(TRIM(lokasi_kalibrasi.lokasi), ''), NULLIF(TRIM(lokasi_repair.lokasi), ''), '-') as lokasi")
            )
            ->where('mtr.nomitrafk', $mitraFk)
            ->where('mtr.statusenabled', true)
            ->whereNull('mtr.isstandarulab');

        $applyRegistrationFilters($registrationQuery);
        $registrations = $registrationQuery
            ->orderByDesc('mtr.tglregistrasi')
            ->get();

        $detailQuery = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as alat', 'alat.id', '=', 'mtrd.namaalatfk')
            ->leftJoin('lingkupkalibrasi_m as lingkup', 'lingkup.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftJoin('lokasikalibrasi_m as lokasi_kaji', 'lokasi_kaji.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lokasikalibrasi_m as lokasi_repair', 'lokasi_repair.id', '=', 'mtrd.lokasirepairfk')
            ->select(
                'mtr.norec as noregistrasifk',
                'mtr.nopendaftaran',
                'mtr.tglregistrasi',
                'mtr.jenisorder',
                'mtr.verifregiscustomer',
                'mtrd.norec as norec_detail',
                'mtrd.noorderalat',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.keterangan',
                'mtrd.statusorderpenyelia',
                'mtrd.tglverifasman',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.isterima',
                'mtrd.bintangpenilaian',
                'alat.id as idalat',
                'alat.namaproduk',
                'alat.namamerk',
                'alat.namatipe',
                'alat.namaserialnumber',
                DB::raw("COALESCE(NULLIF(TRIM(lingkup.lingkupkalibrasi), ''), 'Belum Ada Lingkup') as lingkupkalibrasi"),
                DB::raw("COALESCE(NULLIF(TRIM(lokasi_kaji.lokasi), ''), NULLIF(TRIM(lokasi_repair.lokasi), ''), 'Belum Ada Lokasi') as lokasi")
            )
            ->where('mtr.nomitrafk', $mitraFk)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('alat.statusenabled', true)
            ->whereNull('mtr.isstandarulab');

        $applyRegistrationFilters($detailQuery);
        $details = $detailQuery
            ->orderByDesc('mtr.tglregistrasi')
            ->orderBy('mtrd.noorderalat')
            ->get()
            ->map(function ($row) {
                $jenis = strtolower((string) $row->jenisorder);
                $tanggalSelesai = $jenis === 'repair'
                    ? $row->tglsetujumanagerlaporanrepair
                    : $row->tglsetujumanagerlembarkerja;
                $selesai = !empty($tanggalSelesai);
                $diterima = (bool) $row->isterima;

                $row->selesai = $selesai;
                $row->diterima = $diterima;
                $row->tanggal_selesai = $tanggalSelesai;
                $row->status_verifikasi = $row->verifregiscustomer !== null
                    ? 'Terverifikasi'
                    : 'Menunggu Verifikasi';
                $row->status_proses = $diterima
                    ? 'Diterima'
                    : ($selesai
                        ? 'Selesai Belum Diterima'
                        : (!empty($row->tglverifasman) ? 'Dalam Proses' : 'Menunggu Verifikasi'));
                $row->rating = is_numeric($row->bintangpenilaian)
                    ? (float) $row->bintangpenilaian
                    : null;

                return $row;
            });

        $detailsByRegistration = $details->groupBy('noregistrasifk');
        $registrations = $registrations->map(function ($registration) use ($detailsByRegistration) {
            $rows = $detailsByRegistration->get($registration->norec, collect());
            $registration->jumlah_alat = $rows->count();
            $registration->jumlah_selesai = $rows->where('selesai', true)->count();
            $registration->jumlah_diterima = $rows->where('diterima', true)->count();
            $registration->jumlah_belum_selesai = max($registration->jumlah_alat - $registration->jumlah_selesai, 0);
            $registration->jumlah_belum_diterima = max($registration->jumlah_alat - $registration->jumlah_diterima, 0);
            $registration->status_verifikasi = $registration->verifregiscustomer !== null
                ? 'Terverifikasi'
                : 'Menunggu Verifikasi';

            return $registration;
        });

        $distribution = function ($items, $labelResolver) {
            $total = $items->count();
            if ($total === 0) {
                return collect();
            }

            return $items
                ->groupBy(function ($item) use ($labelResolver) {
                    $label = trim((string) $labelResolver($item));
                    return $label !== '' ? $label : 'Lainnya';
                })
                ->map(function ($rows, $label) use ($total) {
                    $count = $rows->count();
                    return [
                        'label' => $label,
                        'count' => $count,
                        'percent' => round(($count / $total) * 100, 1),
                    ];
                })
                ->sortByDesc('count')
                ->values();
        };

        $totalOrder = $details->count();
        $totalSelesai = $details->where('selesai', true)->count();
        $totalDiterima = $details->where('diterima', true)->count();
        $ratings = $details->pluck('rating')->filter(function ($value) {
            return $value !== null;
        });

        $summary = [
            'total_alat' => DB::table('mapunittoalat_m')
                ->where('objectmitrafk', $mitraFk)
                ->where('statusenabled', true)
                ->count(),
            'total_registrasi' => $registrations->count(),
            'total_order_detail' => $totalOrder,
            'total_selesai' => $totalSelesai,
            'total_diterima' => $totalDiterima,
            'total_belum_selesai' => max($totalOrder - $totalSelesai, 0),
            'total_belum_diterima' => max($totalOrder - $totalDiterima, 0),
            'pendaftaran_kalibrasi' => $registrations->filter(function ($row) {
                return strtolower((string) $row->jenisorder) === 'kalibrasi';
            })->count(),
            'pendaftaran_repair' => $registrations->filter(function ($row) {
                return strtolower((string) $row->jenisorder) === 'repair';
            })->count(),
            'pendaftaran_terverifikasi' => $registrations->whereNotNull('verifregiscustomer')->count(),
            'pendaftaran_menunggu' => $registrations->whereNull('verifregiscustomer')->count(),
            'completion_percent' => $totalOrder > 0 ? round(($totalSelesai / $totalOrder) * 100, 1) : 0,
            'acceptance_percent' => $totalOrder > 0 ? round(($totalDiterima / $totalOrder) * 100, 1) : 0,
            'average_rating' => $ratings->count() > 0 ? round($ratings->avg(), 2) : 0,
        ];

        $monthly = $registrations
            ->groupBy(function ($row) {
                return Carbon::parse($row->tglregistrasi)->format('Y-m');
            })
            ->map(function ($rows, $month) use ($detailsByRegistration) {
                $toolCount = $rows->sum(function ($row) use ($detailsByRegistration) {
                    return $detailsByRegistration->get($row->norec, collect())->count();
                });

                return [
                    'month' => $month,
                    'label' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y'),
                    'registrations' => $rows->count(),
                    'tools' => $toolCount,
                ];
            })
            ->sortBy('month')
            ->values();

        $topTools = $details
            ->groupBy('idalat')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'alat' => $first->namaproduk ?: 'Alat',
                    'merk_tipe' => trim(($first->namamerk ?: '-') . ' / ' . ($first->namatipe ?: '-')),
                    'serial' => $first->namaserialnumber ?: '-',
                    'jumlah_order' => $rows->count(),
                ];
            })
            ->sortByDesc('jumlah_order')
            ->take(10)
            ->values();

        $response = [
            'unit' => $unit,
            'generated_at' => Carbon::now()->toIso8601String(),
            'filters' => [
                'dari' => $dari ? $dari->format('Y-m-d') : null,
                'sampai' => $sampai ? $sampai->format('Y-m-d') : null,
                'jenis_order' => $jenisOrder,
                'search' => $search,
            ],
            'summary' => $summary,
            'monthly' => $monthly,
            'jenis_order' => $distribution($registrations, function ($row) {
                return ucfirst(strtolower((string) $row->jenisorder));
            }),
            'progress' => $distribution($details, function ($row) {
                return $row->status_proses;
            }),
            'lingkup' => $distribution($details, function ($row) {
                return $row->lingkupkalibrasi;
            })->take(8)->values(),
            'lokasi' => $distribution($details, function ($row) {
                return $row->lokasi;
            }),
            'top_tools' => $topTools,
            'registrations' => $registrations->values(),
            'tools' => $details->values(),
        ];

        return $this->respond($response);
    }

    public function saveKeranjangCustomer(Request $r)
    {
        DB::beginTransaction();
        try {
            $PD = $r['keranjangcustomer'];

            $customerId = $this->getPegawaiId();
            $idalat     = (int) $PD['idalat'];
            $mitrafk = DB::table('users')->where('id', $customerId)->value('mitrafk');

            if (empty($mitrafk)) {
                DB::rollBack();
                return $this->respond(['message' => 'mitrafk user tidak ditemukan'], 422, 'Validasi gagal');
            }


            $dupExists = DB::table('keranjangcustomer_t')
                ->where('statusenabled', true)
                ->where('customerid', $customerId)
                ->where(function ($q) use ($idalat) {
                    $q->where('idalat', $idalat);
                })
                ->exists();

            if ($dupExists) {
                DB::rollBack();
                return $this->respond(['message' => 'Barang sudah ada di keranjang'], 409, 'Barang sudah ada di keranjang');
            }

            $activeOrderQuery = DB::table('mitraregistrasidetail_t as d')
                ->join('mitraregistrasi_t as h', 'h.norec', '=', 'd.noregistrasifk')
                ->where('d.statusenabled', true)
                ->where('h.statusenabled', true)
                ->where('d.namaalatfk', $idalat)
                ->where('h.nomitrafk', $mitrafk);

            $inProgressOrder = (clone $activeOrderQuery)
                ->select('h.jenisorder')
                ->whereRaw("(
                    CASE
                        WHEN LOWER(COALESCE(h.jenisorder, '')) = 'repair'
                            THEN d.tglsetujumanagerlaporanrepair
                        ELSE d.tglsetujumanagerlembarkerja
                    END
                ) IS NULL")
                ->orderByDesc('h.tglregistrasi')
                ->first();

            if ($inProgressOrder) {
                $jenisOrder = strtolower(trim((string) $inProgressOrder->jenisorder));
                $dokumen = $jenisOrder === 'repair' ? 'Laporan repair' : 'Sertifikat kalibrasi';
                $message = "Alat masih dikerjakan. {$dokumen} belum disetujui Manager.";

                DB::rollBack();
                return $this->respond(
                    [
                        'message' => $message,
                        'block_reason' => 'work_in_progress',
                        'jenisorder' => $jenisOrder,
                    ],
                    409,
                    $message
                );
            }

            $ratingPending = (clone $activeOrderQuery)
                ->whereRaw("(
                    CASE
                        WHEN LOWER(COALESCE(h.jenisorder, '')) = 'repair'
                            THEN d.tglsetujumanagerlaporanrepair
                        ELSE d.tglsetujumanagerlembarkerja
                    END
                ) IS NOT NULL")
                ->whereRaw('COALESCE(h.iskalibrasiinternal, false) = false')
                ->whereRaw('COALESCE(d.isireviewalat, false) = false')
                ->exists();

            if ($ratingPending) {
                $message = 'Pekerjaan alat sudah selesai, tetapi rating layanan belum diberikan. Berikan rating terlebih dahulu sebelum mendaftarkan alat kembali.';

                DB::rollBack();
                return $this->respond(
                    [
                        'message' => $message,
                        'block_reason' => 'rating_required',
                    ],
                    409,
                    $message
                );
            }

            $model_PD = new KeranjangCustomer();
            $model_PD->norec = $model_PD->generateNewId();
            $model_PD->statusenabled = true;
            $model_PD->idalat = $PD['idalat'];
            $model_PD->jenisorder = $PD['jenisorder'];
            $model_PD->customerid = $this->getPegawaiId();
            $model_PD->save();

            $transMessage = "Simpan Keranjang Customer Sukses";
            DB::commit();
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

    public function saveRegistrasiCustomer(Request $r)
    {
        DB::beginTransaction();
        try {
            $APD = json_decode($r->input('mitraregistrasidetail'), true);

            if (!is_array($APD) || empty($APD)) {
                throw new \Exception('Detail alat registrasi harus diisi.');
            }

            $this->getLokasiNomor($r->input('lokasi'));

            foreach ($APD as $index => $alat) {
                if (!is_array($alat)) {
                    throw new \Exception('Format detail alat registrasi tidak valid.');
                }

                $jenisOrder = strtolower(trim((string) ($alat['jenisorder'] ?? '')));
                if (!in_array($jenisOrder, ['kalibrasi', 'repair'], true)) {
                    throw new \Exception('Jenis order alat ke-' . ($index + 1) . ' harus kalibrasi atau repair.');
                }

                if (empty($alat['id'])) {
                    throw new \Exception('Master alat ke-' . ($index + 1) . ' tidak valid.');
                }

                $APD[$index]['jenisorder'] = $jenisOrder;
            }

            $file = $r->file('fileCustomer');
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

            $customerId = $this->getPegawaiId();
            $customer = DB::table('users')->where('id', $customerId)->first();
            $nohpCustomer = $customer->nowa ?? null;
            $namaCustomer = $customer->name ?? '-';

            $alatCheckout = [];
            $jumlahAlat = 0;

            $groupedByJenisOrder = collect($APD)->groupBy('jenisorder');
            $alatIds = collect($APD)->pluck('id')->filter(function ($id) {
                return $id !== null && $id !== '';
            })->unique()->values();
            $alatRows = $alatIds->isNotEmpty()
                ? DB::table('mapunittoalat_m')->whereIn('id', $alatIds)->get()->keyBy('id')
                : collect();

            foreach ($APD as $index => $alat) {
                if (!$alatRows->has($alat['id'])) {
                    throw new \Exception('Master alat ke-' . ($index + 1) . ' tidak ditemukan.');
                }
            }

            $registrasiData = [];
            $keranjangNorecs = [];
            foreach ($groupedByJenisOrder as $jenisorder => $alatList) {
                $model_PD = new MitraRegistrasi();
                $model_PD->norec = $model_PD->generateNewId();
                $model_PD->statusenabled = true;
                $model_PD->nomitrafk = $r['nomitrafk'];
                $model_PD->namapenanggungjawab = $r['namapenanggungjawab'];
                $model_PD->nohppenanggungjawab = $r['nohppenanggungjawab'];
                $model_PD->jabatanpenanggungjawab = $r['jabatanpenanggungjawab'];
                $model_PD->catatan = $r['catatan'];
                if ($jenisorder == 'kalibrasi') {
                    $model_PD->lokasikalibrasi = $r['lokasi'];
                    $model_PD->paketkalibrasi = $r['paketkalibrasi'];
                    $model_PD->rentangUkur = $r['rentangUkur'];
                    $model_PD->rentangUkurketPermintaanPelanggan = $r['rentangUkurketPermintaanPelanggan'];
                } else {
                    $model_PD->lokasirepair = $r['lokasi'];
                }
                $model_PD->tglregistrasi = now();
                $model_PD->customerfk = $customerId;
                $model_PD->isregiscustomer = true;
                $model_PD->statusorder = 0;
                $model_PD->statusordermanager = 0;
                $model_PD->jenisorder = $jenisorder;
                $model_PD->filecustomerams = $filename;
                $model_PD->filecustomertools =  $filenameTools ?? null;
                $model_PD->save();

                $durasikalibrasi = null;
                if ($jenisorder == 'kalibrasi') {
                    if (!empty($r['paketkalibrasi'])) {
                        $paket = PaketKalibrasi::find($r['paketkalibrasi']);
                        if ($paket) {
                            $durasikalibrasi = $paket->hari;
                        }
                    }
                }

                foreach ($alatList as $alat) {
                    $alatRow = $alatRows->get($alat['id']);
                    $detailAlat = '-';

                    $model_APD = new MitraRegistrasiDetail;
                    $model_APD->norec = $model_APD->generateNewId();
                    $model_APD->statusenabled = true;
                    $model_APD->namaalatfk = $alat['id'] ?? null;
                    $model_APD->noregistrasifk = $model_PD->norec;
                    if ($durasikalibrasi !== null) {
                        $model_APD->durasikalbrasi = $durasikalibrasi;
                    }
                    $model_APD->save();

                    $detailAlat =
                        ($alatRow->namaproduk ?? 'Alat Tidak Dikenal')
                        . ' | Merk: ' . ($alatRow->namamerk ?? '-')
                        . ' | Tipe: ' . ($alatRow->namatipe ?? '-')
                        . ' | SN: ' . ($alatRow->namaserialnumber ?? '-');

                    $alatCheckout[] = $detailAlat;
                    $jumlahAlat++;

                    $keranjangNorecs[] = $alat['norec'];
                }

                $registrasiData[] = $model_PD->norec;
            }
            if (!empty($keranjangNorecs)) {
                DB::table('keranjangcustomer_t')
                    ->whereIn('norec', $keranjangNorecs)
                    ->update([
                        'statusenabled' => false
                    ]);
            }

            DB::commit();

            $waktuOrder = now();
            $waktuOrderStr = $waktuOrder->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            $playStoreLink = 'https://play.google.com/store/apps/details?id=id.ulabumro.mobile';
            if ($nohpCustomer) {
                $pesan = "Yth. {$namaCustomer},\n\n"
                    . "Pendaftaran alat kalibrasi/repair Anda telah berhasil diterima.\n"
                    . "Tanggal/Waktu Pendaftaran: *$waktuOrderStr*\n"
                    . "Total alat yang didaftarkan: *$jumlahAlat* buah.\n\n"
                    . "Rincian alat yang didaftarkan:\n";

                foreach ($alatCheckout as $idx => $detailAlat) {
                    $pesan .= ($idx + 1) . ". " . $detailAlat . "\n";
                }
                $pesan .= "\nStatus dan perkembangan order dapat dipantau melalui aplikasi U-LAB Mobile.\n"
                    . "Jika belum memiliki aplikasinya, silakan unduh melalui Play Store:\n"
                    . "$playStoreLink\n\n";
                $unit = '-';
                if ($customer && $customer->mitrafk) {
                    $mitra = DB::table('mitra_m')
                        ->whereRaw('CAST(id AS INTEGER) = ?', [$customer->mitrafk])
                        ->first();
                    $unit = $mitra->namaperusahaan ?? '-';
                }
                $pesan .= "Terima kasih Yth Bpk/Ibu $namaCustomer dari $unit\n"
                    . "atas kepercayaan Anda kepada Tim U-LAB\n"
                    . "Laboratorium Kalibrasi Multilokasi PT PLN Nusantara Power terakreditasi KAN.\n\n"
                    . "Bagi kami, setiap alat ukur bukan hanya sekadar perangkat, tapi bagian penting dari produktivitas dan keselamatan kerja Anda.\n\n"
                    . "Dengan akurasi, kecepatan, dan layanan 24/7, kami hadir sebagai mitra strategis untuk industri Anda.\n\n"
                    . "🌐 Layanan Multilokasi (Jakarta & Gresik)\n"
                    . "🕐 Operasional 24/7\n"
                    . "🔧 Kalibrasi & Repair dalam satu pintu\n"
                    . "📄 Sertifikat ISO/IEC 17025\n\n"
                    . "Karena kepuasan Anda adalah tolak ukur keberhasilan kami.\n"
                    . "Bersama U-LAB, akurasi & presisi bukan lagi sekadar janji — tapi bukti.\n\n"
                    . "Pagi cerah langit membiru,\n"
                    . "Langkah mantap menuju tujuan.\n"
                    . "Mari bekerja penuh rindu,\n"
                    . "Demi pelayanan dan kepuasan pelanggan.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpCustomer, $pesan);
            }

            try {
                $customerPushResult = app(\App\Services\MobilePushNotificationService::class)->sendToUser(
                    $customerId,
                    'Pendaftaran Alat Berhasil',
                    "Pendaftaran $jumlahAlat alat berhasil diterima. Pantau perkembangan order Anda melalui U-LAB Mobile.",
                    [
                        'type' => 'customer_registration_created',
                        'screen' => 'history_screen',
                        'target_screen' => 'history_screen',
                        'action' => 'open_order',
                        'norec_registrasi' => (string) ($registrasiData[0] ?? ''),
                        'registrasi_norec' => implode(',', $registrasiData),
                        'jumlah_alat' => (string) $jumlahAlat,
                        'waktu_order' => (string) $waktuOrderStr,
                        'source' => 'saveRegistrasiCustomer',
                    ]
                );

                Log::info('FCM registrasi customer berhasil diproses', [
                    'customerfk' => $customerId,
                    'registrasi_norec' => $registrasiData,
                    'push_result' => $customerPushResult,
                ]);
            } catch (\Throwable $pushError) {
                Log::error('Gagal kirim FCM registrasi ke customer', [
                    'message' => $pushError->getMessage(),
                    'customerfk' => $customerId,
                    'registrasi_norec' => $registrasiData,
                ]);
            }

            $lokasiId = $r['lokasi'] ?? null;
            $idAdmin = null;
            $pegawaiAdmin = DB::table('pegawai_m')
                ->where('objectjenispegawaifk', 13)
                ->where('lokasikalibrasifk', $lokasiId)
                ->get();

            if ($lokasiId == 1) {
                $idAdmin = $pegawaiAdmin->pluck('id')->filter()->values()->toArray();
            } elseif ($lokasiId == 2) {
                $idAdmin = $pegawaiAdmin->pluck('id')->filter()->values()->toArray();
            }

            if (!empty($idAdmin)) {
                $admins = DB::table('pegawai_m')->whereIn('id', $idAdmin)->get();

                foreach ($admins as $admin) {
                    $nohpAdmin = $admin->nohandphone ?? null;
                    $namaAdmin = $admin->namalengkap ?? '-';

                    $pesanAdmin = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAdmin,\n\n"
                        . "Pemberitahuan: Telah masuk pesanan registrasi alat dari customer atas nama *$namaCustomer* pada *$waktuOrderStr*.\n"
                        . "Total alat yang didaftarkan: *$jumlahAlat* buah.\n\n"
                        . "Rincian alat yang didaftarkan:\n";

                    foreach ($alatCheckout as $idx => $detailAlat) {
                        $pesanAdmin .= ($idx + 1) . ". " . $detailAlat . "\n";
                    }
                    $pesanAdmin .= "\nMohon untuk segera melakukan verifikasi dan proses lebih lanjut di aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    if ($nohpAdmin) {
                        $this->kirimWhatsappNotifikasi($nohpAdmin, $pesanAdmin);
                    }


                    $notif = new \App\Models\Master\ListNotif();
                    $notif->norec = (string) \Illuminate\Support\Str::uuid();
                    $notif->norec_trans = $registrasiData[0] ?? (string) \Illuminate\Support\Str::uuid();
                    $notif->judul = 'Registrasi Customer Baru';
                    $notif->jenis = 'Registrasi Customer';
                    $notif->pegawaifk = $admin->id;
                    $notif->namapegawai = $admin->namalengkap ?? '-';
                    $notif->keterangan = 'Registrasi baru dari customer ' . $namaCustomer . ' sebanyak ' . $jumlahAlat . ' alat.';
                    $notif->tgl = now();
                    $notif->tgl_string = now()->format('d-m-Y H:i');
                    $notif->urlform = 'module-dashboard-registrasi';
                    $notif->params = json_encode([
                        'norec' => $registrasiData[0] ?? null,
                        'customer' => $namaCustomer,
                    ]);
                    $notif->dataarray = json_encode([
                        'registrasi_norec' => $registrasiData,
                        'jumlahAlat' => $jumlahAlat,
                        'waktuOrder' => $waktuOrderStr,
                    ]);
                    $notif->statusenabled = true;
                    $notif->isread = false;
                    $notif->save();

                    try {
                        \Illuminate\Support\Facades\Http::timeout(3)
                            ->withHeaders([
                                'x-socket-secret' => env('SOCKET_SERVER_SECRET'),
                                'Accept' => 'application/json',
                            ])
                            ->post(rtrim(env('SOCKET_SERVER_URL'), '/') . '/emit-notification', [
                                'norec' => $notif->norec,
                                'norec_trans' => $notif->norec_trans,
                                'judul' => $notif->judul,
                                'jenis' => $notif->jenis,
                                'idPegawai' => $notif->pegawaifk,
                                'namapegawai' => $notif->namapegawai,
                                'pesanNotifikasi' => $notif->keterangan,
                                'tgl' => $notif->tgl,
                                'tgl_string' => $notif->tgl_string,
                                'urlForm' => $notif->urlform,
                                'params' => json_decode($notif->params, true),
                                'dataArray' => json_decode($notif->dataarray, true),
                            ]);
                    } catch (\Exception $e) {
                        // abaikan jika socket gagal, DB notif tetap tersimpan
                    }
                }
            }

            try {
                $adminPushResult = app(\App\Services\MobilePushNotificationService::class)->sendToAdminsByLocation(
                    $lokasiId,
                    'Pendaftaran Alat Baru',
                    "$namaCustomer mendaftarkan $jumlahAlat alat. Buka aplikasi untuk melakukan verifikasi.",
                    [
                        'type' => 'admin_registration',
                        'screen' => 'admin_notifications',
                        'target_screen' => 'admin_notifications',
                        'action' => 'open_admin_registration',
                        'norec_registrasi' => implode(',', $registrasiData),
                        'registration_id' => (string) ($registrasiData[0] ?? ''),
                        'customer' => (string) $namaCustomer,
                        'jumlah_alat' => (string) $jumlahAlat,
                        'waktu_order' => (string) $waktuOrderStr,
                        'source' => 'saveRegistrasiCustomer',
                    ]
                );

                Log::info('FCM registrasi customer ke admin berhasil diproses', [
                    'lokasi_id' => $lokasiId,
                    'registrasi_norec' => $registrasiData,
                    'push_result' => $adminPushResult,
                ]);
            } catch (\Throwable $pushError) {
                Log::error('Gagal kirim FCM registrasi customer ke admin', [
                    'message' => $pushError->getMessage(),
                    'lokasi_id' => $lokasiId,
                    'registrasi_norec' => $registrasiData,
                ]);
            }

            $idPenyeliaAdmin = $this->settingFix('idPenyeliaAdmin') ?? null;

            if ($idPenyeliaAdmin) {
                $penyeliaAdmin = DB::table('pegawai_m')->where('id', $idPenyeliaAdmin)->first();
                $nohpPenyeliaAdmin = $penyeliaAdmin->nohandphone ?? null;
                $namaPenyeliaAdmin = $penyeliaAdmin->namalengkap ?? '-';

                $pesanPenyeliaAdmin = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenyeliaAdmin,\n\n"
                    . "Pemberitahuan: Telah masuk pesanan registrasi alat dari customer atas nama *$namaCustomer* pada *$waktuOrderStr*.\n"
                    . "Total alat yang didaftarkan: *$jumlahAlat* buah.\n\n"
                    . "Rincian alat yang didaftarkan:\n";

                foreach ($alatCheckout as $idx => $detailAlat) {
                    $pesanPenyeliaAdmin .= ($idx + 1) . ". " . $detailAlat . "\n";
                }
                $pesanPenyeliaAdmin .= "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                if ($nohpPenyeliaAdmin) {
                    $this->kirimWhatsappNotifikasi($nohpPenyeliaAdmin, $pesanPenyeliaAdmin);
                }
            }

            $transMessage = "Simpan Registrasi Sukses untuk " . count($registrasiData) . " jenis order.";
            $result = [
                "status" => 200,
                "result" => [
                    "registrasi_norec" => $registrasiData,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $result = [
                "status" => 400,
                "result" => $e->getMessage(),
            ];
            $transMessage = "Simpan Gagal";
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


    public function getStatusCustomer(Request $r)
    {
        $data = DB::table('users')
            ->select(
                'id',
                'name',
                'email',
                'nowa',
                'mitrafk',
                'jabatan',
                'isuserbaru',
                'iseksternal',
            )
            ->where('id', $this->getPegawaiId())
            ->first();

        $result['data'] = $data;
        $result['as'] = '@adit';

        return $this->respond($result);
    }

    public function dropdownUnitEksternal(Request $r)
    {
        try {
            $search = $r['query'] ?? '';

            $data = DB::table('mitra_m as pk')
                ->select(
                    'pk.id as value',
                    'pk.namaperusahaan as label',
                )
                ->where('pk.iseksternal', true)
                ->where('pk.statusenabled', true);

            if (!empty($search)) {
                $data = $data->where('mt.namaperusahaan', 'ilike', $search);
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

    public function saveStatusCustomer(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r->input('statusCustomer');

            $kategori  = strtoupper($VI['kategori'] ?? '');
            $mitrafk   = $VI['mitrafk'] ?? null;
            $institusi = $VI['institusi'] ?? null;
            $jabatan   = $VI['jabatan'] ?? null;

            if (empty($kategori)) throw new \Exception('Kategori belum dipilih');
            if (empty($jabatan))  throw new \Exception('Jabatan harus di isi');

            $finalMitraFk = null;
            if ($kategori === 'INTERNAL') {
                if (empty($mitrafk)) throw new \Exception('Unit harus di isi');
                $finalMitraFk = $mitrafk;
            } else if ($kategori === 'EKSTERNAL') {
                if (!empty($mitrafk)) {
                    $finalMitraFk = $mitrafk;
                } else {
                    $institusi = trim((string) $institusi);
                    if ($institusi === '') {
                        throw new \Exception('Institusi harus di isi (pilih dari daftar atau isi manual)');
                    }

                    $dataPS = new Mitra();
                    $dataPS->id = $this->SEQUENCE_MASTER(new Mitra(), 'id', $this->kdProfile);
                    $dataPS->kdprofile = $this->kdProfile;
                    $dataPS->statusenabled = true;
                    $dataPS->namaperusahaan = strtoupper($institusi);
                    $dataPS->tgldaftar = date('Y-m-d H:i:s');
                    $dataPS->iseksternal = true;
                    $dataPS->save();

                    $finalMitraFk = $dataPS->id;
                }
            } else {
                throw new \Exception('Kategori tidak valid');
            }

            DB::table('users')
                ->where('id', $this->getPegawaiId())
                ->update([
                    'mitrafk'    => $finalMitraFk,
                    'jabatan'    => strtoupper((string) $jabatan),
                    'iseksternal' => ($kategori === 'EKSTERNAL') ? true : null,
                    'isuserbaru' => false,
                ]);

            DB::commit();
            $transMessage = "Simpan Status Customer Sukses";
            $result = [
                "status" => 200,
                "result" => [
                    "mitrafk" => $finalMitraFk,
                    "kategori" => $kategori,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $transMessage = "Simpan Gagal";
            $result = [
                "status" => 400,
                "result" => $e->getMessage(),
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function hapusKeranjangCustomer(Request $r)
    {
        DB::beginTransaction();
        try {
            $norecList = $r->input('norec');
            if (!is_array($norecList) || count($norecList) === 0) {
                return response()->json([
                    "metaData" => [
                        "code" => 400,
                        "message" => "Data norec kosong/tidak valid"
                    ],
                    "response" => null
                ], 400);
            }

            DB::table('keranjangcustomer_t')
                ->whereIn('norec', $norecList)
                ->update([
                    'statusenabled' => false,
                ]);

            $transMessage = "Hapus Keranjang Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@adit',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function fetchAlat(Request $r)
    {
        $data = DB::table('mapunittoalat_m as mmp')
            ->select(
                'mmp.namaproduk',
                'mmp.id as idalat',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mmp.fotoproduk',
            )
            ->where('mmp.statusenabled', true)
            ->where('mmp.id', $r['id_alat']);

        $data = $data->first();

        $result['data'] = $data;
        $result['as'] = '@aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function historyAlat(Request $r)
    {
        $isOwner = true;

        if (empty($r['id_alat'])) {
            return $this->respond([
                'length'   => 0,
                'detail'   => [],
                'is_owner' => false,
                'message'  => 'ID alat tidak ditemukan',
            ]);
        }

        $idAlat = $r['id_alat'];
        $kelompokUserId = $this->getKelompokUserId();
        $mitraFk = null;

        if ($kelompokUserId == null) {
            $pegawaiId = $this->getPegawaiId();

            $mitraFk = DB::table('users')
                ->where('id', $pegawaiId)
                ->where('statusenabled', true)
                ->value('mitrafk');

            if (empty($mitraFk)) {
                return $this->respond([
                    'length'   => 0,
                    'detail'   => [],
                    'is_owner' => false,
                    'message'  => 'Mitra user tidak ditemukan',
                ]);
            }

            $alat = DB::table('mapunittoalat_m')
                ->select('id', 'objectmitrafk', 'statusenabled')
                ->where('id', $idAlat)
                ->first();

            if (!$alat || !$alat->statusenabled) {
                return $this->respond([
                    'length'   => 0,
                    'detail'   => [],
                    'is_owner' => false,
                    'message'  => 'Data alat tidak ditemukan atau tidak aktif',
                ]);
            }

            if ((string) $alat->objectmitrafk !== (string) $mitraFk) {
                return $this->respond([
                    'length'   => 0,
                    'detail'   => [],
                    'is_owner' => false,
                    'message'  => 'Alat ini tidak terdaftar sebagai milik akun Anda',
                ]);
            }
        }

        $query = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtrd.lokasirepairfk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftJoin('paketkalibrasi_m as pk', 'pk.id', '=', 'mtr.paketkalibrasi')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.alasanpenolakanregis',
                'mtrd.tanggalpenolakanregis',
                'mtrd.noorderalat',
                'mtrd.versisertifikat',
                'mtrd.versilaporanrepair',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtr.iskalibrasiinternal',
                'mmp.id as idalat',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mmp.fotoproduk',
                'mmp.objectmitrafk',
                'mt.namaperusahaan',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lk1.id as lokasirepairfk',
                'lk1.lokasi as lokasirepair',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'pk.id as idpaket',
                'pk.namapaket',
                'pk.hari as hari_paket',
                'mtr.isikepuasanpelanggan',
                'mtr.verifregiscustomer',
                'mtr.tanggalverifregiscustomer',
                'mtrd.tglverifasman',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.isVendor',
                'mtrd.fileSertiVendor',
                'mtrd.tglupladosertivendor',
                'mtrd.isireviewalat',
                'mtrd.isverifikasi',
                'mtrd.bintangpenilaian',
                'mtrd.tglkalibrasilembarkerja',
                'mtrd.tanggalmulai'
            )
            ->where('mtr.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereNull('mtr.isstandarulab')
            ->where('mmp.id', $idAlat);

        if ($kelompokUserId == null && !empty($mitraFk)) {
            $query->where(function ($q) use ($mitraFk) {
                $q->where('mtr.nomitrafk', $mitraFk)
                    ->orWhere('mmp.objectmitrafk', $mitraFk);
            });
        }

        $data = $query->orderByDesc('mtr.tglregistrasi')->get();

        $holidayDates = DB::table('master_hari_libur_m')
            ->where('statusenabled', true)
            ->pluck('tanggal')
            ->map(function ($tanggal) {
                return Carbon::parse($tanggal)->format('Y-m-d');
            })
            ->toArray();

        $calcWorkingSeconds = function (?Carbon $start, ?Carbon $end) use ($holidayDates): ?int {
            if (!$start || !$end) {
                return null;
            }

            if ($end->lessThan($start)) {
                [$start, $end] = [$end, $start];
            }

            $workStart = '07:30:00';
            $workEnd   = '16:00:00';

            $totalSeconds = 0;
            $cursor = $start->copy()->startOfDay();
            $lastDay = $end->copy()->startOfDay();

            while ($cursor->lte($lastDay)) {
                $isoDay = $cursor->isoWeekday();
                $currentDate = $cursor->format('Y-m-d');
                $isWeekend = ($isoDay == 6 || $isoDay == 7);
                $isHoliday = in_array($currentDate, $holidayDates);
                $isWorkday = !$isWeekend && !$isHoliday;

                if ($isWorkday) {
                    $dayWorkStart = $cursor->copy()->setTimeFromTimeString($workStart);
                    $dayWorkEnd   = $cursor->copy()->setTimeFromTimeString($workEnd);

                    $effectiveStart = $start->copy()->max($dayWorkStart);
                    $effectiveEnd   = $end->copy()->min($dayWorkEnd);

                    if ($effectiveEnd->greaterThan($effectiveStart)) {
                        $totalSeconds += $effectiveEnd->diffInSeconds($effectiveStart);
                    }
                }

                $cursor->addDay();
            }

            return $totalSeconds;
        };

        foreach ($data as $item) {
            $tglMulai  = $item->tanggalmulai ? Carbon::parse($item->tanggalmulai) : null;
            $tglSetuju = $item->tglsetujumanagerlembarkerja ? Carbon::parse($item->tglsetujumanagerlembarkerja) : null;

            $diffInSeconds = $calcWorkingSeconds($tglMulai, $tglSetuju);

            if ($diffInSeconds !== null) {
                $workdaySeconds = (8 * 60 * 60) + (30 * 60);

                $days = intdiv($diffInSeconds, $workdaySeconds);
                $rem  = $diffInSeconds % $workdaySeconds;

                $hours = intdiv($rem, 3600);
                $rem   = $rem % 3600;

                $minutes = intdiv($rem, 60);
                $seconds = $rem % 60;

                $item->durasi_proses = "{$days} hari kerja {$hours} jam {$minutes} menit {$seconds} detik";
                $item->durasi_detik  = $diffInSeconds;
            } else {
                $item->durasi_proses = '-';
                $item->durasi_detik  = null;
            }
        }

        $result = [];
        $result['length']   = count($data);
        $result['detail']   = $data;
        $result['is_owner'] = true;
        $result['as']       = '@aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function getHistoryRegisAlatCustomer(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftjoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftjoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftjoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtrd.lokasirepairfk')
            ->leftjoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftjoin('paketkalibrasi_m as pk', 'pk.id', '=', 'mtr.paketkalibrasi')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.alasanpenolakanregis',
                'mtrd.tanggalpenolakanregis',
                'mtrd.noorderalat',
                'mtrd.isireviewalat',
                'mtrd.versisertifikat',
                'mtrd.versilaporanrepair',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtr.iskalibrasiinternal',
                'mmp.id as idalat',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mmp.fotoproduk',
                'mt.namaperusahaan',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lk1.id as lokasirepairfk',
                'lk1.lokasi as lokasirepair',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'pk.id as idpaket',
                'pk.namapaket',
                'pk.hari as hari_paket',
                'mtr.isikepuasanpelanggan',
                'mtr.verifregiscustomer',
                'mtr.tanggalverifregiscustomer',
                'mtrd.tglverifasman',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.bintangpenilaian',
                'mtrd.ulasanpenilaian',
                'mtrd.isverifikasi',
                'mtrd.isVendor',
                'mtrd.fileSertiVendor',
                'mtrd.tglupladosertivendor',
                'mtrd.tglkalibrasilembarkerja',
                'mtrd.tanggalmulai'
            )
            ->where('mtr.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereNull('mtr.isstandarulab')
            ->where('mtr.norec', $r['norec_pd']);

        $data = $data->orderByDesc('mmp.namaproduk')->get();

        $holidayDates = DB::table('master_hari_libur_m')
            ->where('statusenabled', true)
            ->pluck('tanggal')
            ->map(function ($tanggal) {
                return Carbon::parse($tanggal)->format('Y-m-d');
            })
            ->toArray();

        $calcWorkingSeconds = function (?Carbon $start, ?Carbon $end) use ($holidayDates): ?int {
            if (!$start || !$end) {
                return null;
            }

            if ($end->lessThan($start)) {
                [$start, $end] = [$end, $start];
            }

            $workStart = '07:30:00';
            $workEnd   = '16:00:00';

            $totalSeconds = 0;
            $cursor = $start->copy()->startOfDay();
            $lastDay = $end->copy()->startOfDay();

            while ($cursor->lte($lastDay)) {
                $isoDay = $cursor->isoWeekday();
                $currentDate = $cursor->format('Y-m-d');

                $isWeekend = ($isoDay == 6 || $isoDay == 7);
                $isHoliday = in_array($currentDate, $holidayDates);
                $isWorkday = !$isWeekend && !$isHoliday;

                if ($isWorkday) {
                    $dayWorkStart = $cursor->copy()->setTimeFromTimeString($workStart);
                    $dayWorkEnd   = $cursor->copy()->setTimeFromTimeString($workEnd);

                    $effectiveStart = $start->copy()->max($dayWorkStart);
                    $effectiveEnd   = $end->copy()->min($dayWorkEnd);

                    if ($effectiveEnd->greaterThan($effectiveStart)) {
                        $totalSeconds += $effectiveEnd->diffInSeconds($effectiveStart);
                    }
                }

                $cursor->addDay();
            }

            return $totalSeconds;
        };

        foreach ($data as $item) {
            $tglMulai  = $item->tanggalmulai ? Carbon::parse($item->tanggalmulai) : null;
            $tglSetuju = $item->tglsetujumanagerlembarkerja ? Carbon::parse($item->tglsetujumanagerlembarkerja) : null;

            $diffInSeconds = $calcWorkingSeconds($tglMulai, $tglSetuju);

            if ($diffInSeconds !== null) {
                $workdaySeconds = (8 * 60 * 60) + (30 * 60); // 30600 detik

                $days = intdiv($diffInSeconds, $workdaySeconds);
                $rem  = $diffInSeconds % $workdaySeconds;

                $hours = intdiv($rem, 3600);
                $rem   = $rem % 3600;

                $minutes = intdiv($rem, 60);
                $seconds = $rem % 60;

                $item->durasi_proses = "{$days} hari kerja {$hours} jam {$minutes} menit {$seconds} detik";
                $item->durasi_detik  = $diffInSeconds;
            } else {
                $item->durasi_proses = '-';
                $item->durasi_detik  = null;
            }
        }

        $result['length'] = count($data);
        $result['detail'] = $data;
        $result['as'] = '@aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function cetakBarcodeAlat(Request $r)
    {

        $profile = $this->profile();
        $print = false;
        $customWidth = 18 * 2.83465;
        $customHeight = 56 * 2.83465;
        $pageWidth = $customWidth;

        $res['alat'] =  $data = DB::table('mapunittoalat_m as mmp')
            ->leftJoin('mitraregistrasidetail_t as mtrd', 'mtrd.namaalatfk', '=', 'mmp.id')
            ->leftJoin('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('pegawai_m as pg3', 'pg3.id', '=', 'mtrd.asmanveriffk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg2.jabatan1fk')
            ->leftJoin('jabatan_m as jb1', 'jb1.id', '=', 'pg.jabatan1fk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.keterangan',
                'mtrd.namafile',
                'mtrd.pelaksanateknikfk',
                'mtrd.noorderalat',
                'mtrd.durasikalbrasi',
                'mtrd.namamanager',
                'mtrd.namaasman',
                'mtrd.tglisilembarkerjapelaksana',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mmp.id as idalat',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg3.id as asmanfk',
                'pg3.namalengkap as asamanverifikasi',
                'jb1.namajabatanulab as jabatanpenyelia',
                'jb.namajabatanulab as jabatanpelaksana',
            )
            ->where('mtrd.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->whereNull('mtr.isstandarulab')
            ->where('mmp.id', $r['idalat'])
            ->get();

        $res['pdf']  = $r['pdf'];


        $blade = 'report.customer.barcodealat';

        if ($res['pdf'] == 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->setPaper([0, 0, $customWidth, $customHeight]);
            $pdf->loadView($blade, [
                'profile' => $profile,
                'pageWidth' => $pageWidth,
                'print' => $print,
                'res' => $res,
            ]);
            return $pdf->stream();
        }
        if (isset($r['storage'])) {
            $res['storage']  = true;
            $pdf = App::make('dompdf.wrapper');
            $pdf->setPaper([0, 0, $customWidth, $customHeight]);
            $pdf->loadView($blade, [
                'profile' => $profile,
                'pageWidth' => $pageWidth,
                'print' => $print,
                'res' => $res,
            ]);
            return $pdf;
        }

        return view(
            $blade,
            compact('profile', 'pageWidth', 'print', 'res')
        );
    }

    public function getRiwayatAmandemen(Request $request)
    {
        $data = DB::table('sertifikat_log as sl')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'sl.norec_detail')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('sl.*', 'mtr.jenisorder')
            ->where('sl.norec_detail', $request->norec_detail)
            ->orderBy('sl.created_at', 'ASC')
            ->get();

        return $this->respond($data);
    }
}
