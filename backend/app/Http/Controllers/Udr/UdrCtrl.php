<?php

namespace App\Http\Controllers\Udr;

use App\Http\Controllers\Controller;
use App\Models\Standar\UlabDigitalRepo;
use App\Traits\Valet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html;

class UdrCtrl extends Controller
{
    use Valet;

    private $newDriveFolderAccessCache = [];
    private $newDriveFolderAccessTablesAvailable;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    /* =========================================================
     * HELPER
     * ========================================================= */
    private function generateStoredFilename($originalName)
    {
        $cleanName = preg_replace('/\s+/', '_', $originalName);
        $cleanName = preg_replace('/[^A-Za-z0-9\-\._]/', '', $cleanName);
        return time() . '_' . uniqid() . '_' . $cleanName;
    }

    private function makeDocumentNameWithoutExtension($originalName)
    {
        $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
        $nameOnly = trim($nameOnly);
        $nameOnly = preg_replace('/\s+/', ' ', $nameOnly);
        return $nameOnly;
    }

    private function buildTree2Pass(array $nodes)
    {
        $indexed = [];
        foreach ($nodes as $node) {
            $indexed[$node['key']] = $node;
        }

        $tree = [];
        foreach ($indexed as $id => &$node) {
            $pid = $node['parent_id'];

            if (!empty($pid) && isset($indexed[$pid])) {
                $indexed[$pid]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        $sortTree = function (&$items) use (&$sortTree) {
            usort($items, function ($a, $b) {
                $na = (int) ($a['nourut'] ?? 0);
                $nb = (int) ($b['nourut'] ?? 0);
                if ($na === $nb) {
                    return (int)$a['key'] <=> (int)$b['key'];
                }
                return $na <=> $nb;
            });

            foreach ($items as &$item) {
                if (!empty($item['children'])) {
                    $sortTree($item['children']);
                }
            }
        };

        $sortTree($tree);

        return $tree;
    }

    private function createNewDocumentRecord(
        $kdProfile,
        $namaIsi,
        $filename,
        $keterangan,
        $nourut,
        $kdrinci,
        $jenisudr,
        $nomorindukfk = null,
        $isHeader = null,
        $alamaturlform = null,
        $pegawaicvfk = null
    )
    {
        $newID = $this->SEQUENCE_MASTER(new UlabDigitalRepo(), 'id', $kdProfile);

        $baru = new UlabDigitalRepo();
        $baru->id                   = $newID;
        $baru->kdprofile            = $kdProfile;
        $baru->statusenabled        = true;
        $baru->kodeexternal         = $isHeader;
        $baru->norec                = substr($this->Uuid4(), 0, 32);
        $baru->kdisidokumen         = $newID;
        $baru->keterangan           = $keterangan;
        $baru->namaisidokumen       = $namaIsi;
        $baru->nourut               = $nourut;
        $baru->kdrinciandokumenhead = $kdrinci;
        $baru->isidokumen           = $filename;
        $baru->alamaturlform        = $alamaturlform;
        $baru->jenisudr             = $jenisudr;
        $baru->nomorindukfk         = $nomorindukfk;
        $baru->pegawaifk            = $this->getPegawaiId();

        if (Schema::hasColumn('ulabdigitalrepo_m', 'pegawaicvfk')) {
            $baru->pegawaicvfk = $pegawaicvfk;
        }

        if (Schema::hasColumn('ulabdigitalrepo_m', 'revisike')) {
            $baru->revisike = 1;
        }
        if (Schema::hasColumn('ulabdigitalrepo_m', 'tglrevisi')) {
            $baru->tglrevisi = now();
        }

        $baru->save();

        return $baru;
    }

    private function createRevisionDocumentRecord($kdProfile, $rootId, $namaIsi, $filename, $keterangan, $nourut, $kdrinci, $jenisudr, $nomorindukfk = null, $isHeader = null)
    {
        $this->lockNomorTransaksi('revisi-udr', [$rootId]);

        $revisiKe = DB::table('ulabdigitalrepo_m')
            ->where('statusenabled', true)
            ->where(function ($q) use ($rootId) {
                $q->where('kdisidokumen', $rootId)
                    ->orWhere('id', $rootId);
            })
            ->count() + 1;

        $newID = $this->SEQUENCE_MASTER(new UlabDigitalRepo(), 'id', $kdProfile);

        $baru = new UlabDigitalRepo();
        $baru->id                   = $newID;
        $baru->kdprofile            = $kdProfile;
        $baru->statusenabled        = true;
        $baru->kodeexternal         = $isHeader;
        $baru->norec                = substr($this->Uuid4(), 0, 32);
        $baru->kdisidokumen         = $rootId;
        $baru->keterangan           = $keterangan;
        $baru->namaisidokumen       = $namaIsi;
        $baru->nourut               = $nourut;
        $baru->kdrinciandokumenhead = $kdrinci;
        $baru->isidokumen           = $filename;
        $baru->jenisudr             = $jenisudr;
        $baru->nomorindukfk         = $nomorindukfk;
        $baru->pegawaifk            = $this->getPegawaiId();

        if (Schema::hasColumn('ulabdigitalrepo_m', 'revisike')) {
            $baru->revisike = $revisiKe;
        }
        if (Schema::hasColumn('ulabdigitalrepo_m', 'tglrevisi')) {
            $baru->tglrevisi = now();
        }

        $baru->save();

        return $baru;
    }

    /* =========================================================
     * MASTER UDR
     * ========================================================= */
    public function masterIsiUdr(Request $request)
    {
        $latestSub = DB::table('ulabdigitalrepo_m as o2')
            ->selectRaw('MAX(o2.id) as latest_id')
            ->selectRaw("COALESCE(NULLIF(o2.kdisidokumen,'')::integer, o2.id) as root_id")
            ->where('o2.statusenabled', true)
            ->groupBy(DB::raw("COALESCE(NULLIF(o2.kdisidokumen,'')::integer, o2.id)"));

        $dataRaw = DB::table('ulabdigitalrepo_m as odm')
            ->joinSub($latestSub, 'l', function ($join) {
                $join->on('odm.id', '=', 'l.latest_id');
            })
            ->leftJoin('udr_nomorinduk_m as ni', 'ni.id', '=', 'odm.nomorindukfk')
            ->where('odm.statusenabled', true)
            ->select(
                'odm.id as key',
                'odm.kdrinciandokumenhead',
                'odm.namaisidokumen as label',
                'odm.nourut',
                'odm.kodeexternal',
                'odm.alamaturlform',
                'odm.keterangan',
                'odm.fungsi',
                'odm.isidokumen',
                'odm.nomorindukfk',
                'ni.nomordokumen as nomordokumeninduk',
                'ni.namadokumen as namanomorinduk',
                DB::raw("COALESCE(NULLIF(odm.kdisidokumen,'')::integer, odm.id) as root_id"),
                DB::raw('COALESCE(odm.revisike, 1) as revisike')
            )
            ->where('odm.jenisudr', $request['jenisudr'])
            ->orderByRaw('COALESCE(odm.kodeexternal, \'\') = \'H\' DESC')
            ->orderBy('odm.nourut')
            ->orderBy('odm.id')
            ->get();

        $nodes = [];
        foreach ($dataRaw as $r) {
            $nodes[] = [
                'key'        => $r->key,
                'label'      => $r->label,
                'data'       => $r,
                'nourut'     => $r->nourut,
                'isidokumen' => $r->isidokumen,
                'parent_id'  => $r->kodeexternal == 'H' ? 0 : $r->kdrinciandokumenhead,
                'icon'       => 'pi pi-fw pi-folder',
                'children'   => [],
            ];
        }

        $res['data'] = $dataRaw;
        $res['tree'] = $this->buildTree2Pass($nodes);

        return $this->respond($res);
    }

    public function logIsiUdr(Request $request)
    {
        $limit = (int) ($request->get('limit') ?? 10);

        if ($limit <= 0) {
            $limit = 10;
        }

        if ($limit > 100) {
            $limit = 100;
        }

        $jenisudr = $request->get('jenisudr');
        $search = trim((string) ($request->get('search') ?? ''));

        $defaultConnection = config('database.default');
        $connectionDriver = config('database.connections.' . $defaultConnection . '.driver');
        $driver = $connectionDriver ?: 'pgsql';

        $likeOperator = $driver === 'pgsql' ? 'ilike' : 'like';

        $isHapusExpr = "COALESCE(odm.ishapus, 0) = 1";

        if (Schema::hasColumn('ulabdigitalrepo_m', 'tglrevisi')) {
            $dateExpr = "odm.tglrevisi";
        } elseif (Schema::hasColumn('ulabdigitalrepo_m', 'updated_at')) {
            $dateExpr = "odm.updated_at";
        } elseif (Schema::hasColumn('ulabdigitalrepo_m', 'created_at')) {
            $dateExpr = "odm.created_at";
        } else {
            $dateExpr = "null";
        }

        if (Schema::hasColumn('ulabdigitalrepo_m', 'updated_at')) {
            $deleteDateExpr = "odm.updated_at";
        } else {
            $deleteDateExpr = $dateExpr;
        }

        $tglRevisiExpr = "
        CASE
            WHEN {$isHapusExpr} THEN {$deleteDateExpr}
            ELSE {$dateExpr}
        END";

        $orderExpr = $dateExpr !== "null"
            ? DB::raw($tglRevisiExpr)
            : DB::raw('odm.id');

        $q = DB::table('ulabdigitalrepo_m as odm')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'odm.pegawaifk')
            ->leftJoin('udr_nomorinduk_m as ni', 'ni.id', '=', 'odm.nomorindukfk')
            ->leftJoin('ulabdigitalrepo_m as parent', 'parent.id', '=', 'odm.kdrinciandokumenhead')
            ->leftJoin('udr_nomorinduk_m as niparent', 'niparent.id', '=', 'parent.nomorindukfk');

        if (!empty($jenisudr)) {
            $q->where('odm.jenisudr', $jenisudr);
        }

        if ($search !== '') {
            $q->where(function ($query) use ($search, $likeOperator) {
                $query->where('odm.namaisidokumen', $likeOperator, '%' . $search . '%')
                    ->orWhere('odm.keterangan', $likeOperator, '%' . $search . '%')
                    ->orWhere('odm.isidokumen', $likeOperator, '%' . $search . '%')
                    ->orWhere('odm.jenisudr', $likeOperator, '%' . $search . '%')
                    ->orWhere('pg.namalengkap', $likeOperator, '%' . $search . '%')
                    ->orWhere('ni.nomordokumen', $likeOperator, '%' . $search . '%')
                    ->orWhere('ni.namadokumen', $likeOperator, '%' . $search . '%')
                    ->orWhere('parent.namaisidokumen', $likeOperator, '%' . $search . '%')
                    ->orWhere('parent.isidokumen', $likeOperator, '%' . $search . '%')
                    ->orWhere('niparent.nomordokumen', $likeOperator, '%' . $search . '%')
                    ->orWhere('niparent.namadokumen', $likeOperator, '%' . $search . '%');
            });
        }

        $log = $q->select(
            'odm.id',
            'odm.kdisidokumen',
            'odm.kdrinciandokumenhead',
            'odm.namaisidokumen',
            'odm.nourut',
            'odm.jenisudr',
            'odm.keterangan',
            'odm.isidokumen',
            'odm.nomorindukfk',
            'ni.nomordokumen',
            'ni.namadokumen',
            'odm.pegawaifk',
            'parent.id as parent_id',
            'parent.namaisidokumen as parent_namaisidokumen',
            'parent.isidokumen as parent_isidokumen',
            'parent.kdrinciandokumenhead as parent_kdrinciandokumenhead',
            'parent.nomorindukfk as parent_nomorindukfk',
            'niparent.nomordokumen as parent_nomordokumen',
            'niparent.namadokumen as parent_namadokumen',
            DB::raw("COALESCE(pg.namalengkap,'-') as namapegawai"),
            DB::raw("
            CASE
                WHEN {$isHapusExpr} THEN 'HAPUS'
                WHEN COALESCE(odm.revisike, 1) = 1 THEN 'CREATE'
                ELSE 'REVISI'
            END as aksi
        "),
            DB::raw("COALESCE(odm.revisike, 1) as revisike"),
            DB::raw($tglRevisiExpr . " as tglrevisi"),
            DB::raw("
            CASE
                WHEN parent.id IS NOT NULL THEN parent.namaisidokumen
                ELSE '-'
            END as folder_induk
        "),
            DB::raw("
            CASE
                WHEN parent.id IS NOT NULL AND niparent.nomordokumen IS NOT NULL THEN niparent.nomordokumen
                WHEN parent.id IS NOT NULL AND ni.nomordokumen IS NOT NULL THEN ni.nomordokumen
                ELSE NULL
            END as folder_nomordokumen
        "),
            DB::raw("
            CASE
                WHEN parent.id IS NOT NULL AND niparent.namadokumen IS NOT NULL THEN niparent.namadokumen
                WHEN parent.id IS NOT NULL AND ni.namadokumen IS NOT NULL THEN ni.namadokumen
                ELSE NULL
            END as folder_namadokumen
        ")
        )
            ->orderByDesc($orderExpr)
            ->limit($limit)
            ->get();

        foreach ($log as $row) {
            $row->folder_path = $this->getPathFolderUdr($row->kdrinciandokumenhead, $row->jenisudr);
        }

        return $this->respond([
            'log' => $log,
            'limit' => $limit,
            'search' => $search,
        ]);
    }

    private function getPathFolderUdr($parentId, $jenisudr = null)
    {
        if (empty($parentId)) {
            return null;
        }

        $parents = [];
        $currentId = $parentId;
        $loopGuard = 0;

        while (!empty($currentId) && $loopGuard < 50) {
            $node = DB::table('ulabdigitalrepo_m')
                ->select(
                    'id',
                    'kdrinciandokumenhead',
                    'namaisidokumen',
                    'jenisudr'
                )
                ->where('id', $currentId)
                ->first();

            if (!$node) {
                break;
            }

            array_unshift($parents, $node->namaisidokumen);

            $currentId = $node->kdrinciandokumenhead;
            $loopGuard++;
        }

        if (count($parents) == 0) {
            return null;
        }

        if (!empty($jenisudr)) {
            array_unshift($parents, $jenisudr);
        }

        return implode(' / ', $parents);
    }

    public function saveIsiDokumenUdr(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();

        try {
            $filename = null;
            $old = null;
            $uploadedNameWithoutExtension = null;

            if (!empty($request['id'])) {
                $old = UlabDigitalRepo::where('id', $request['id'])->first();
                if (!$old) {
                    throw new \Exception("Data lama tidak ditemukan.");
                }
            }

            $alamaturlform = $request->has('alamaturlform') && trim((string)$request['alamaturlform']) !== ''
                ? trim((string)$request['alamaturlform'])
                : null;

            $pegawaicvfk = $request->has('pegawaicvfk') && $request['pegawaicvfk'] !== ''
                ? $request['pegawaicvfk']
                : null;

            $isLinkCv = !empty($alamaturlform) || !empty($pegawaicvfk);

            if (!$isLinkCv && $request->hasFile('fileDokumenMutu')) {
                $file = $request->file('fileDokumenMutu');

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa gambar (jpg, jpeg, png, webp), PDF, Word (doc, docx), atau Excel (xls, xlsx).");
                }

                $uploadedNameWithoutExtension = $this->makeDocumentNameWithoutExtension($file->getClientOriginalName());
                $filename = $this->generateStoredFilename($file->getClientOriginalName());
                $file->move(public_path('berkas-mutu'), $filename);
            } else {
                if ($old) {
                    $filename = $old->isidokumen;
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }
            }

            if ($isLinkCv) {
                $filename = null;
            }

            $namaIsi = trim((string)($request['namaisidokumen'] ?? ''));
            if ($namaIsi === '' && !empty($uploadedNameWithoutExtension)) {
                $namaIsi = $uploadedNameWithoutExtension;
            }

            $isHeader = empty($request['kdrinciandokumenhead']) ? 'H' : null;
            $kdrinci = $request->has('kdrinciandokumenhead') && $request['kdrinciandokumenhead'] !== ''
                ? $request['kdrinciandokumenhead']
                : null;
            $nomorindukfk = $request->has('nomorindukfk') && $request['nomorindukfk'] !== ''
                ? $request['nomorindukfk']
                : null;

            if (empty($request['id'])) {
                $jenisUdr = $request['jenisudr'] ?? null;
                $this->lockNomorTransaksi('nourut-udr', [$jenisUdr]);

                $nourut = DB::table('ulabdigitalrepo_m')
                    ->when($jenisUdr === null || $jenisUdr === '', function ($query) {
                        $query->whereNull('jenisudr');
                    }, function ($query) use ($jenisUdr) {
                        $query->where('jenisudr', $jenisUdr);
                    })
                    ->max('nourut');
                $nourut = ((int) ($nourut ?? 0)) + 1;

                $newID = $this->SEQUENCE_MASTER(new UlabDigitalRepo(), 'id', $kdProfile);
                $rootId = $newID;
                $revisiKe = 1;

                $baru = new UlabDigitalRepo();
                $baru->id                   = $newID;
                $baru->kdprofile            = $kdProfile;
                $baru->statusenabled        = true;
                $baru->kodeexternal         = $isHeader;
                $baru->norec                = substr($this->Uuid4(), 0, 32);
                $baru->kdisidokumen         = $rootId;
                $baru->keterangan           = $request['keterangan'] ?? null;
                $baru->namaisidokumen       = $namaIsi ?: null;
                $baru->nourut               = $nourut;
                $baru->kdrinciandokumenhead = $kdrinci;
                $baru->isidokumen           = $filename;
                $baru->alamaturlform        = $alamaturlform;
                $baru->jenisudr             = $request['jenisudr'] ?? null;
                $baru->nomorindukfk         = $nomorindukfk;
                $baru->pegawaifk            = $this->getPegawaiId();

                if (Schema::hasColumn('ulabdigitalrepo_m', 'pegawaicvfk')) {
                    $baru->pegawaicvfk = $pegawaicvfk;
                }

                if (Schema::hasColumn('ulabdigitalrepo_m', 'revisike')) {
                    $baru->revisike = $revisiKe;
                }

                if (Schema::hasColumn('ulabdigitalrepo_m', 'tglrevisi')) {
                    $baru->tglrevisi = now();
                }

                $baru->save();
            } else {
                $fileChanged = !$isLinkCv && $request->hasFile('fileDokumenMutu') && $filename !== $old->isidokumen;

                if ($fileChanged) {
                    $rootId = $old->kdisidokumen ?: $old->id;

                    $this->lockNomorTransaksi('revisi-udr', [$rootId]);

                    $revisiKe = DB::table('ulabdigitalrepo_m')
                        ->where('statusenabled', true)
                        ->where(function ($q) use ($rootId) {
                            $q->where('kdisidokumen', $rootId)
                                ->orWhere('id', $rootId);
                        })
                        ->count() + 1;

                    $newID = $this->SEQUENCE_MASTER(new UlabDigitalRepo(), 'id', $kdProfile);

                    $baru = new UlabDigitalRepo();
                    $baru->id                   = $newID;
                    $baru->kdprofile            = $kdProfile;
                    $baru->statusenabled        = true;
                    $baru->kodeexternal         = $isHeader;
                    $baru->norec                = substr($this->Uuid4(), 0, 32);
                    $baru->kdisidokumen         = $rootId;
                    $baru->keterangan           = $request['keterangan'] ?? $old->keterangan;
                    $baru->namaisidokumen       = $namaIsi ?: $old->namaisidokumen;
                    $baru->nourut               = $request['nourut'] ?? $old->nourut;
                    $baru->kdrinciandokumenhead = $kdrinci ?? $old->kdrinciandokumenhead;
                    $baru->isidokumen           = $filename;
                    $baru->alamaturlform        = $alamaturlform;
                    $baru->jenisudr             = $request['jenisudr'] ?? null;
                    $baru->nomorindukfk         = $nomorindukfk ?? $old->nomorindukfk;
                    $baru->pegawaifk            = $this->getPegawaiId();

                    if (Schema::hasColumn('ulabdigitalrepo_m', 'pegawaicvfk')) {
                        $baru->pegawaicvfk = $pegawaicvfk;
                    }

                    if (Schema::hasColumn('ulabdigitalrepo_m', 'revisike')) {
                        $baru->revisike = $revisiKe;
                    }

                    if (Schema::hasColumn('ulabdigitalrepo_m', 'tglrevisi')) {
                        $baru->tglrevisi = now();
                    }

                    $baru->save();
                } else {
                    $old->keterangan = $request['keterangan'] ?? $old->keterangan;
                    $old->namaisidokumen = $namaIsi !== '' ? $namaIsi : $old->namaisidokumen;
                    $old->nourut = $request['nourut'] ?? $old->nourut;
                    $old->kdrinciandokumenhead = $kdrinci ?? $old->kdrinciandokumenhead;
                    $old->isidokumen = $isLinkCv ? null : ($filename ?? $old->isidokumen);
                    $old->alamaturlform = $alamaturlform;
                    $old->nomorindukfk = $nomorindukfk;

                    if (Schema::hasColumn('ulabdigitalrepo_m', 'pegawaicvfk')) {
                        $old->pegawaicvfk = $pegawaicvfk;
                    }

                    $old->save();
                }
            }

            DB::commit();

            return $this->respond([
                "as" => 'aditwiran19@gmail.com',
            ], 200, "Sukses");
        } catch (Exception $e) {
            DB::rollback();

            return $this->respond([
                "as" => 'aditwiran19@gmail.com',
                "ex" => $e->getMessage() . ' ' . $e->getLine(),
            ], 400, "Simpan Gagal");
        }
    }

    public function uploadMultiUdr(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();

        try {
            $jenisudr = $request->get('jenisudr');
            $headId = $request->get('kdrinciandokumenhead');
            $nomorindukfk = $request->get('nomorindukfk');

            if (empty($jenisudr) || !in_array($jenisudr, ['Mutu', 'Teknik', 'Admin'])) {
                throw new \Exception('Jenis UDR tidak valid.');
            }

            if (empty($headId)) {
                throw new \Exception('Folder target upload harus dipilih terlebih dahulu.');
            }

            $head = DB::table('ulabdigitalrepo_m')
                ->where('id', $headId)
                ->where('statusenabled', true)
                ->where('jenisudr', $jenisudr)
                ->first();

            if (!$head) {
                throw new \Exception('Folder target upload tidak ditemukan atau tidak aktif.');
            }

            if (!$request->hasFile('files')) {
                throw new \Exception('Tidak ada file yang diupload.');
            }

            $files = $request->file('files');
            if (!is_array($files) || !count($files)) {
                throw new \Exception('Format file upload tidak valid.');
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
            $maxSize = 30 * 1024 * 1024; // 20 MB
            $saved = [];
            $uploadedCount = 0;
            $documentNames = collect($files)
                ->filter()
                ->map(function ($file) {
                    return strtolower($this->makeDocumentNameWithoutExtension($file->getClientOriginalName()));
                })
                ->unique()
                ->values();
            $existingLatestByName = DB::table('ulabdigitalrepo_m as odm')
                ->select('odm.*', DB::raw("LOWER(COALESCE(odm.namaisidokumen, '')) as normalized_name"))
                ->where('odm.statusenabled', true)
                ->where('odm.jenisudr', $jenisudr)
                ->where('odm.kdrinciandokumenhead', $headId)
                ->whereIn(DB::raw("LOWER(COALESCE(odm.namaisidokumen, ''))"), $documentNames)
                ->orderByDesc(DB::raw('COALESCE(odm.revisike, 1)'))
                ->orderByDesc('odm.id')
                ->get()
                ->unique(function ($row) {
                    return $row->normalized_name;
                })
                ->keyBy(function ($row) {
                    return $row->normalized_name;
                });
            $nextNourut = null;

            foreach ($files as $file) {
                if (!$file) {
                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File {$file->getClientOriginalName()} tidak diizinkan. Hanya PDF, Word, Excel, JPG, PNG, atau WebP.");
                }

                if ($file->getSize() > $maxSize) {
                    throw new \Exception("File {$file->getClientOriginalName()} melebihi batas maksimal 30 MB.");
                }

                $originalName = $file->getClientOriginalName();
                $namaDokumen = $this->makeDocumentNameWithoutExtension($originalName);
                $filename = $this->generateStoredFilename($originalName);
                $documentKey = strtolower($namaDokumen);

                $file->move(public_path('berkas-mutu'), $filename);

                $existingLatest = $existingLatestByName->get($documentKey);

                if ($existingLatest) {
                    $rootId = $existingLatest->kdisidokumen ?: $existingLatest->id;

                    $doc = $this->createRevisionDocumentRecord(
                        $kdProfile,
                        $rootId,
                        $namaDokumen,
                        $filename,
                        'Upload massal Program ' . $jenisudr,
                        $existingLatest->nourut,
                        $headId,
                        $jenisudr,
                        $nomorindukfk,
                        null
                    );

                    $saved[] = [
                        'id' => $doc->id,
                        'nama' => $doc->namaisidokumen,
                        'revisike' => $doc->revisike ?? 1,
                        'mode' => 'revisi',
                    ];
                } else {
                    if ($nextNourut === null) {
                        $this->lockNomorTransaksi('nourut-udr', [$jenisudr]);

                        $lastNourut = DB::table('ulabdigitalrepo_m')
                            ->where('jenisudr', $jenisudr)
                            ->max('nourut');

                        $nextNourut = ($lastNourut ?? 0) + 1;
                    }

                    $doc = $this->createNewDocumentRecord(
                        $kdProfile,
                        $namaDokumen,
                        $filename,
                        'Upload massal Program ' . $jenisudr,
                        $nextNourut,
                        $headId,
                        $jenisudr,
                        $nomorindukfk,
                        null
                    );
                    $nextNourut++;

                    $saved[] = [
                        'id' => $doc->id,
                        'nama' => $doc->namaisidokumen,
                        'revisike' => $doc->revisike ?? 1,
                        'mode' => 'baru',
                    ];
                }

                $existingLatestByName->put($documentKey, $doc);

                $uploadedCount++;
            }

            if ($uploadedCount <= 0) {
                throw new \Exception('Tidak ada file valid yang berhasil diproses.');
            }

            DB::commit();

            return $this->respond([
                'message' => $uploadedCount . ' file berhasil diupload ke folder ' . $jenisudr,
                'jumlah' => $uploadedCount,
                'folder' => [
                    'id' => $head->id,
                    'nama' => $head->namaisidokumen,
                ],
                'data' => $saved,
            ], 200, 'Sukses');
        } catch (Exception $e) {
            DB::rollBack();

            return $this->respond([
                'message' => $e->getMessage(),
                'as' => 'aditwiran19@gmail.com',
                'ex' => $e->getMessage(),
            ], 400, 'Upload Gagal');
        }
    }

    public function hapusIsiUdr(Request $request)
    {
        DB::beginTransaction();
        try {
            UlabDigitalRepo::where('id', $request['id'])->update([
                'statusenabled' => false,
                'ishapus' => true,
            ]);

            DB::commit();

            return $this->respond([
                "as" => 'aditwiran19@gmail.com',
            ], 200, "Sukses ");
        } catch (Exception $e) {
            DB::rollback();

            return $this->respond([
                "as" => 'aditwiran19@gmail.com',
            ], 400, "Hapus Gagal");
        }
    }

    public function lastNourutUdr(Request $r)
    {
        $jenisudr = $r->get('jenisudr');

        $q = UlabDigitalRepo::where('statusenabled', true);

        if (!empty($jenisudr)) {
            $q->where('jenisudr', $jenisudr);
        }

        $lastNourut = $q->max('nourut');
        $nextNourut = ($lastNourut ?? 0) + 1;

        return $this->respond([
            'nourut' => $nextNourut,
            'last' => $lastNourut,
        ]);
    }

    public function cetakDokumenUdr(Request $request)
    {
        $data = DB::table('ulabdigitalrepo_m')
            ->where('id', $request['id'])
            ->first();

        if (!$data || !$data->isidokumen) {
            abort(404, 'Data Dokumen atau file tidak ditemukan');
        }

        $filename = basename($data->isidokumen);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, ['doc', 'docx'])) {
            $filepath = asset('berkas-mutu/' . $filename);
        } else {
            $filepath = asset('berkas-mutu/' . $filename);
        }

        return view('report.mutu.view-pdf', compact('filepath', 'data', 'extension'));
    }

    public function previewExcel($id)
    {
        $data = DB::table('ulabdigitalrepo_m')->where('id', $id)->first();
        if (!$data || !$data->isidokumen) {
            abort(404, 'File tidak ditemukan');
        }

        $filename = basename($data->isidokumen);
        $fullPath = public_path('berkas-mutu/' . $filename);
        if (!file_exists($fullPath)) {
            abort(404, "File Excel tidak ditemukan di path: $fullPath");
        }

        $spreadsheet = IOFactory::load($fullPath);
        $writer = new Html($spreadsheet);

        $output = '';
        foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
            $writer->setSheetIndex($index);
            ob_start();
            $writer->save('php://output');
            $sheetHtml = ob_get_clean();

            $output .= "<h2 style=\"font-family:sans-serif;font-size:1.2em;margin:1em 0;\">Sheet: {$sheetName}</h2>";
            $output .= $sheetHtml;
        }

        return response($output, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function riwayatIsiUdr(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return $this->respond(['message' => 'id diperlukan'], 400, 'Bad Request');
        }

        $row = DB::table('ulabdigitalrepo_m')->where('id', $id)->first();
        if (!$row) {
            return $this->respond(['message' => 'Data tidak ditemukan'], 404, 'Not Found');
        }

        $root = $row->kdisidokumen ?: $row->id;

        $hasNullIsi = DB::table('ulabdigitalrepo_m')
            ->where('statusenabled', true)
            ->where(function ($q) use ($root) {
                $q->where('kdisidokumen', $root)
                    ->orWhere('id', $root);
            })
            ->whereNull('isidokumen')
            ->exists();

        $revisiSelect = $hasNullIsi
            ? DB::raw('(COALESCE(revisike, 1) - 1) AS revisike')
            : DB::raw('COALESCE(revisike, 1) AS revisike');

        $list = DB::table('ulabdigitalrepo_m')
            ->where('statusenabled', true)
            ->whereNotNull('isidokumen')
            ->where(function ($q) use ($root) {
                $q->where('kdisidokumen', $root)
                    ->orWhere('id', $root);
            })
            ->orderByRaw('COALESCE(revisike,1) ASC, id ASC')
            ->get([
                'id',
                'namaisidokumen',
                'isidokumen',
                'nomorindukfk',
                $revisiSelect,
                'tglrevisi',
            ]);

        return $this->respond([
            'root_id' => $root,
            'riwayat' => $list,
        ], 200, 'Sukses');
    }

    public function hitIsiDokumen(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID tidak valid');
            }

            DB::table('objekisidokumen_m')
                ->where('id', $id)
                ->update([
                    'dilihat' => DB::raw('COALESCE(dilihat, 0) + 1'),
                ]);

            DB::commit();

            return $this->respond([
                "as" => 'aditwiran19@gmail.com',
            ], 200, "Sukses ");
        } catch (Exception $e) {
            DB::rollback();

            return $this->respond([
                "as" => 'aditwiran19@gmail.com',
            ], 400, "Hapus Gagal");
        }
    }

    /* =========================================================
     * NOMOR INDUK DOKUMEN
     * ========================================================= */
    public function masterNomorInduk(Request $request)
    {
        $latestLinkedSub = DB::table('ulabdigitalrepo_m as u')
            ->selectRaw('MAX(u.id) as latest_udr_id, u.nomorindukfk')
            ->where('u.statusenabled', true)
            ->whereNotNull('u.nomorindukfk')
            ->groupBy('u.nomorindukfk');

        $dataRaw = DB::table('udr_nomorinduk_m as m')
            ->leftJoinSub($latestLinkedSub, 'lu', function ($join) {
                $join->on('lu.nomorindukfk', '=', 'm.id');
            })
            ->leftJoin('ulabdigitalrepo_m as u', 'u.id', '=', 'lu.latest_udr_id')
            ->where('m.statusenabled', true)
            ->select(
                'm.id as key',
                'm.parentid',
                'm.leveldokumen',
                'm.nourut',
                'm.nomordokumen',
                'm.namadokumen',
                'm.tanggaldokumen',
                'm.statusdokumen',
                'm.target',
                'm.pic',
                'm.keterangan',
                'u.id as linked_udr_id',
                'u.namaisidokumen as linked_udr_name',
                'u.jenisudr as linked_jenisudr'
            )
            ->orderBy('m.leveldokumen')
            ->orderBy('m.nourut')
            ->orderBy('m.id')
            ->get();

        $nodes = [];
        foreach ($dataRaw as $r) {
            $nodes[] = [
                'key' => $r->key,
                'label' => $r->nomordokumen,
                'data' => $r,
                'nourut' => $r->nourut,
                'parent_id' => $r->parentid,
                'children' => [],
            ];
        }

        return $this->respond([
            'data' => $dataRaw,
            'tree' => $this->buildTree2Pass($nodes),
        ]);
    }

    public function lookupNomorInduk(Request $request)
    {
        $data = DB::table('udr_nomorinduk_m')
            ->where('statusenabled', true)
            ->orderBy('leveldokumen')
            ->orderBy('nourut')
            ->orderBy('id')
            ->get([
                'id as key',
                'parentid',
                'leveldokumen',
                'nourut',
                'nomordokumen',
                'namadokumen',
                'statusdokumen',
                'target',
                'pic',
            ]);

        $rows = $data->map(function ($r) {
            return [
                'key' => $r->key,
                'parentid' => $r->parentid,
                'leveldokumen' => $r->leveldokumen,
                'nourut' => $r->nourut,
                'nomordokumen' => $r->nomordokumen,
                'namadokumen' => $r->namadokumen,
                'statusdokumen' => $r->statusdokumen,
                'target' => $r->target,
                'pic' => $r->pic,
                'label' => $r->nomordokumen . ' - ' . $r->namadokumen,
            ];
        })->values();

        return $this->respond([
            'data' => $rows,
        ]);
    }

    public function lastNourutNomorInduk(Request $request)
    {
        $parentid = $request->get('parentid');

        $q = DB::table('udr_nomorinduk_m')
            ->where('statusenabled', true);

        if ($parentid !== null && $parentid !== '') {
            $q->where('parentid', $parentid);
        } else {
            $q->whereNull('parentid');
        }

        $lastNourut = $q->max('nourut');
        $nextNourut = ($lastNourut ?? 0) + 1;

        return $this->respond([
            'nourut' => $nextNourut,
            'last' => $lastNourut,
        ]);
    }

    public function saveNomorInduk(Request $request)
    {
        DB::beginTransaction();

        try {
            $kdProfile = $this->kdProfile;

            $id = $request->get('id');
            $parentid = $request->get('parentid');
            $levelInput = (int) $request->get('leveldokumen');
            $nomordokumen = trim((string) $request->get('nomordokumen'));
            $namadokumen = trim((string) $request->get('namadokumen'));

            if ($parentid === '' || $parentid === 'null' || $parentid === 'undefined') {
                $parentid = null;
            }

            if ($nomordokumen === '') {
                throw new \Exception('Nomor dokumen harus diisi.');
            }

            if ($namadokumen === '') {
                throw new \Exception('Nama dokumen harus diisi.');
            }

            $parent = null;
            $minLevel = 1;

            if (!empty($parentid)) {
                $parent = DB::table('udr_nomorinduk_m')
                    ->where('id', $parentid)
                    ->where('statusenabled', true)
                    ->first();

                if (!$parent) {
                    throw new \Exception('Parent nomor induk tidak ditemukan.');
                }

                $minLevel = ((int) $parent->leveldokumen) + 1;
            }

            $level = $levelInput > 0 ? $levelInput : $minLevel;

            if ($level < $minLevel) {
                throw new \Exception('Level dokumen tidak boleh lebih kecil dari level minimal berdasarkan parent.');
            }

            if ($level > 4) {
                throw new \Exception('Maksimal level dokumen adalah level 4.');
            }

            if (empty($id)) {
                $parentKey = $parentid ?: 'root';
                $this->lockNomorTransaksi('nourut-udr-nomorinduk', [$parentKey]);

                $nourut = DB::table('udr_nomorinduk_m')
                    ->when($parentid, function ($query) use ($parentid) {
                        $query->where('parentid', $parentid);
                    }, function ($query) {
                        $query->whereNull('parentid');
                    })
                    ->max('nourut');
                $nourut = ((int) ($nourut ?? 0)) + 1;

                $this->lockNomorTransaksi('udr-nomorinduk-id');

                $newId = DB::table('udr_nomorinduk_m')->max('id');
                $newId = ($newId ?? 0) + 1;

                DB::table('udr_nomorinduk_m')->insert([
                    'id' => $newId,
                    'norec' => substr($this->Uuid4(), 0, 32),
                    'kdprofile' => $kdProfile,
                    'statusenabled' => true,
                    'parentid' => $parentid ?: null,
                    'leveldokumen' => $level,
                    'nourut' => $nourut,
                    'nomordokumen' => $nomordokumen,
                    'namadokumen' => $namadokumen,
                    'tanggaldokumen' => $request->get('tanggaldokumen') ?: null,
                    'statusdokumen' => $request->get('statusdokumen') ?: null,
                    'target' => $request->get('target') ?: null,
                    'pic' => $request->get('pic') ?: null,
                    'keterangan' => $request->get('keterangan') ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $current = DB::table('udr_nomorinduk_m')->where('id', $id)->first();
                if (!$current) {
                    throw new \Exception('Data nomor induk tidak ditemukan.');
                }

                DB::table('udr_nomorinduk_m')
                    ->where('id', $id)
                    ->update([
                        'parentid' => $parentid ?: null,
                        'leveldokumen' => $level,
                        'nourut' => $request->get('nourut') ?: $current->nourut,
                        'nomordokumen' => $nomordokumen,
                        'namadokumen' => $namadokumen,
                        'tanggaldokumen' => $request->get('tanggaldokumen') ?: null,
                        'statusdokumen' => $request->get('statusdokumen') ?: null,
                        'target' => $request->get('target') ?: null,
                        'pic' => $request->get('pic') ?: null,
                        'keterangan' => $request->get('keterangan') ?: null,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return $this->respond([
                'as' => 'aditwiran19@gmail.com',
            ], 200, 'Sukses');
        } catch (Exception $e) {
            DB::rollBack();

            return $this->respond([
                'message' => $e->getMessage(),
                'ex' => $e->getMessage(),
            ], 400, 'Simpan Gagal');
        }
    }

    public function hapusNomorInduk(Request $request)
    {
        DB::beginTransaction();

        try {
            $id = $request->get('id');
            if (!$id) {
                throw new \Exception('ID tidak valid.');
            }

            $hasChild = DB::table('udr_nomorinduk_m')
                ->where('statusenabled', true)
                ->where('parentid', $id)
                ->exists();

            if ($hasChild) {
                throw new \Exception('Nomor induk masih memiliki child, tidak bisa dihapus.');
            }

            $hasLinkedUdr = DB::table('ulabdigitalrepo_m')
                ->where('statusenabled', true)
                ->where('nomorindukfk', $id)
                ->exists();

            if ($hasLinkedUdr) {
                throw new \Exception('Nomor induk masih terhubung ke dokumen UDS, tidak bisa dihapus.');
            }

            DB::table('udr_nomorinduk_m')
                ->where('id', $id)
                ->update([
                    'statusenabled' => false,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return $this->respond([
                'as' => 'aditwiran19@gmail.com',
            ], 200, 'Sukses');
        } catch (Exception $e) {
            DB::rollBack();

            return $this->respond([
                'message' => $e->getMessage(),
                'ex' => $e->getMessage(),
            ], 400, 'Hapus Gagal');
        }
    }

    public function resolveOpenUdrByNomorInduk(Request $request)
    {
        $nomorindukfk = $request->get('nomorindukfk');

        if (!$nomorindukfk) {
            return $this->respond([
                'id' => null,
            ], 200, 'Sukses');
        }

        if (Schema::hasTable('udr_drive_nomorinduk_link_t')) {
            $link = DB::table('udr_drive_nomorinduk_link_t')
                ->where('nomorindukfk', $nomorindukfk)
                ->first();

            if ($link) {
                try {
                    $item = $this->resolveNewDriveItem((int) $link->udrrootid);
                    if (!$this->newDriveUserCanAccessResolvedItem($item)) {
                        return $this->respond([
                            'id' => null,
                            'restricted' => true,
                        ], 200, 'Akses dokumen dibatasi');
                    }

                    return $this->respond([
                        'id' => $link->targettype === 'folder'
                            ? (int) $item->drive_id
                            : (int) $item->root_id,
                        'root_id' => (int) $item->root_id,
                        'nama' => $item->namaisidokumen,
                        'jenisudr' => $item->jenisudr,
                        'target_type' => $link->targettype,
                    ], 200, 'Sukses');
                } catch (Exception $e) {
                    // Relasi baru yang targetnya sudah tidak aktif dilanjutkan ke fallback lama.
                }
            }
        }

        $udr = DB::table('ulabdigitalrepo_m')
            ->where('statusenabled', true)
            ->where('nomorindukfk', $nomorindukfk)
            ->orderByDesc('id')
            ->first(['id', 'namaisidokumen', 'jenisudr']);

        if ($udr) {
            try {
                $item = $this->resolveNewDriveItem((int) $udr->id);
                if (!$this->newDriveUserCanAccessResolvedItem($item)) {
                    return $this->respond([
                        'id' => null,
                        'restricted' => true,
                    ], 200, 'Akses dokumen dibatasi');
                }
            } catch (Exception $e) {
                // Endpoint pembuka UDS akan tetap melakukan validasi final.
            }
        }

        return $this->respond([
            'id' => $udr->id ?? null,
            'nama' => $udr->namaisidokumen ?? null,
            'jenisudr' => $udr->jenisudr ?? null,
            'target_type' => $udr ? 'file' : null,
        ], 200, 'Sukses');
    }

    private function createNewSurveilanDocumentRecord($kdProfile, $namaIsi, $filename, $keterangan, $nourut, $kdrinci, $jenisudr, $nomorindukfk = null, $isHeader = null, $alamaturlform = null, $pegawaicvfk = null)
    {
        $this->lockNomorTransaksi('surveilanrepo-id');

        $newID = (int) DB::table('surveilanrepo_m')->max('id') + 1;

        DB::table('surveilanrepo_m')->insert([
            'id'                   => $newID,
            'kdprofile'            => $kdProfile,
            'statusenabled'        => true,
            'kodeexternal'         => $isHeader,
            'norec'                => substr($this->Uuid4(), 0, 32),
            'kdisidokumen'         => $newID,
            'linkfolder'           => (string) $newID,
            'keterangan'           => $keterangan,
            'namaisidokumen'       => $namaIsi,
            'nourut'               => $nourut,
            'kdrinciandokumenhead' => $kdrinci,
            'isidokumen'           => $filename,
            'alamaturlform'        => $alamaturlform,
            'jenisudr'             => $jenisudr,
            'nomorindukfk'         => $nomorindukfk,
            'pegawaifk'            => $this->getPegawaiId(),
            'pegawaicvfk'          => $pegawaicvfk,
            'revisike'             => 1,
            'tglrevisi'            => now(),
            'ishapus'              => false,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return DB::table('surveilanrepo_m')->where('id', $newID)->first();
    }

    private function createRevisionSurveilanDocumentRecord($kdProfile, $rootId, $namaIsi, $filename, $keterangan, $nourut, $kdrinci, $jenisudr, $nomorindukfk = null, $isHeader = null, $alamaturlform = null, $pegawaicvfk = null)
    {
        $this->lockNomorTransaksi('revisi-surveilan', [$rootId]);

        $revisiKe = DB::table('surveilanrepo_m')
            ->where('statusenabled', true)
            ->where(function ($q) use ($rootId) {
                $q->where('kdisidokumen', $rootId)
                    ->orWhere('id', $rootId);
            })
            ->count() + 1;

        $linkfolder = DB::table('surveilanrepo_m')
            ->where(function ($q) use ($rootId) {
                $q->where('kdisidokumen', $rootId)
                    ->orWhere('id', $rootId);
            })
            ->whereNotNull('linkfolder')
            ->orderBy('id')
            ->value('linkfolder');

        if (empty($linkfolder)) {
            $linkfolder = (string) $rootId;
        }

        $this->lockNomorTransaksi('surveilanrepo-id');

        $newID = (int) DB::table('surveilanrepo_m')->max('id') + 1;

        DB::table('surveilanrepo_m')->insert([
            'id'                   => $newID,
            'kdprofile'            => $kdProfile,
            'statusenabled'        => true,
            'kodeexternal'         => $isHeader,
            'norec'                => substr($this->Uuid4(), 0, 32),
            'kdisidokumen'         => $rootId,
            'linkfolder'           => $linkfolder,
            'keterangan'           => $keterangan,
            'namaisidokumen'       => $namaIsi,
            'nourut'               => $nourut,
            'kdrinciandokumenhead' => $kdrinci,
            'isidokumen'           => $filename,
            'alamaturlform'        => $alamaturlform,
            'jenisudr'             => $jenisudr,
            'nomorindukfk'         => $nomorindukfk,
            'pegawaifk'            => $this->getPegawaiId(),
            'pegawaicvfk'          => $pegawaicvfk,
            'revisike'             => $revisiKe,
            'tglrevisi'            => now(),
            'ishapus'              => false,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return DB::table('surveilanrepo_m')->where('id', $newID)->first();
    }

    public function masterIsiDokumenSurveilan(Request $request)
    {
        $latestSub = DB::table('surveilanrepo_m as o2')
            ->selectRaw('MAX(o2.id) as latest_id')
            ->selectRaw("COALESCE(o2.kdisidokumen, o2.id) as root_id")
            ->where('o2.statusenabled', true)
            ->groupBy(DB::raw("COALESCE(o2.kdisidokumen, o2.id)"));

        $dataRaw = DB::table('surveilanrepo_m as odm')
            ->joinSub($latestSub, 'l', function ($join) {
                $join->on('odm.id', '=', 'l.latest_id');
            })
            ->leftJoin('udr_nomorinduk_m as ni', 'ni.id', '=', 'odm.nomorindukfk')
            ->where('odm.statusenabled', true)
            ->where('odm.jenisudr', $request['jenisudr'])
            ->select(
                'odm.id as key',
                'odm.kdrinciandokumenhead',
                'odm.namaisidokumen as label',
                'odm.nourut',
                'odm.kodeexternal',
                'odm.alamaturlform',
                'odm.keterangan',
                'odm.isidokumen',
                'odm.nomorindukfk',
                'odm.pegawaicvfk',
                'odm.linkfolder',
                'ni.nomordokumen as nomordokumeninduk',
                'ni.namadokumen as namanomorinduk',
                DB::raw("COALESCE(odm.kdisidokumen, odm.id) as root_id"),
                DB::raw("COALESCE(NULLIF(odm.linkfolder, ''), COALESCE(odm.kdisidokumen, odm.id)::varchar) as stable_folder_key"),
                DB::raw('COALESCE(odm.revisike, 1) as revisike')
            )
            ->orderByRaw("COALESCE(odm.kodeexternal, '') = 'H' DESC")
            ->orderBy('odm.nourut')
            ->orderBy('odm.id')
            ->get();

        $nodes = [];
        foreach ($dataRaw as $r) {
            $nodes[] = [
                'key'        => $r->key,
                'label'      => $r->label,
                'data'       => $r,
                'nourut'     => $r->nourut,
                'isidokumen' => $r->isidokumen,
                'parent_id'  => $r->kodeexternal == 'H' ? 0 : $r->kdrinciandokumenhead,
                'icon'       => 'pi pi-fw pi-folder',
                'children'   => [],
            ];
        }

        $res['data'] = $dataRaw;
        $res['tree'] = $this->buildTree2Pass($nodes);

        return $this->respond($res);
    }

    public function logIsiDokumenSurveilan(Request $request)
    {
        $limit = (int) ($request->get('limit') ?? 10);

        if ($limit <= 0) {
            $limit = 10;
        }

        if ($limit > 100) {
            $limit = 100;
        }

        $jenisudr = $request->get('jenisudr');
        $search = trim((string) ($request->get('search') ?? ''));

        $q = DB::table('surveilanrepo_m as odm')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'odm.pegawaifk')
            ->leftJoin('udr_nomorinduk_m as ni', 'ni.id', '=', 'odm.nomorindukfk')
            ->leftJoin('surveilanrepo_m as parent', 'parent.id', '=', 'odm.kdrinciandokumenhead')
            ->leftJoin('udr_nomorinduk_m as niparent', 'niparent.id', '=', 'parent.nomorindukfk');

        if (!empty($jenisudr)) {
            $q->where('odm.jenisudr', $jenisudr);
        }

        if ($search !== '') {
            $q->where(function ($query) use ($search) {
                $query->where('odm.namaisidokumen', 'ilike', '%' . $search . '%')
                    ->orWhere('odm.keterangan', 'ilike', '%' . $search . '%')
                    ->orWhere('odm.isidokumen', 'ilike', '%' . $search . '%')
                    ->orWhere('odm.jenisudr', 'ilike', '%' . $search . '%')
                    ->orWhere('pg.namalengkap', 'ilike', '%' . $search . '%')
                    ->orWhere('ni.nomordokumen', 'ilike', '%' . $search . '%')
                    ->orWhere('ni.namadokumen', 'ilike', '%' . $search . '%')
                    ->orWhere('parent.namaisidokumen', 'ilike', '%' . $search . '%')
                    ->orWhere('parent.isidokumen', 'ilike', '%' . $search . '%')
                    ->orWhere('niparent.nomordokumen', 'ilike', '%' . $search . '%')
                    ->orWhere('niparent.namadokumen', 'ilike', '%' . $search . '%');
            });
        }

        $log = $q->select(
            'odm.id',
            'odm.kdisidokumen',
            'odm.kdrinciandokumenhead',
            'odm.namaisidokumen',
            'odm.nourut',
            'odm.jenisudr',
            'odm.keterangan',
            'odm.isidokumen',
            'odm.nomorindukfk',
            'ni.nomordokumen',
            'ni.namadokumen',
            'odm.pegawaifk',
            'parent.id as parent_id',
            'parent.namaisidokumen as parent_namaisidokumen',
            'parent.isidokumen as parent_isidokumen',
            'parent.kdrinciandokumenhead as parent_kdrinciandokumenhead',
            'parent.nomorindukfk as parent_nomorindukfk',
            'niparent.nomordokumen as parent_nomordokumen',
            'niparent.namadokumen as parent_namadokumen',
            DB::raw("COALESCE(pg.namalengkap,'-') as namapegawai"),
            DB::raw("
            CASE
                WHEN COALESCE(odm.ishapus, false) = true THEN 'HAPUS'
                WHEN COALESCE(odm.revisike, 1) = 1 THEN 'CREATE'
                ELSE 'REVISI'
            END as aksi
        "),
            DB::raw("COALESCE(odm.revisike, 1) as revisike"),
            DB::raw("COALESCE(odm.tglrevisi, odm.updated_at, odm.created_at) as tglrevisi"),
            DB::raw("
            CASE
                WHEN parent.id IS NOT NULL THEN parent.namaisidokumen
                ELSE '-'
            END as folder_induk
        "),
            DB::raw("
            CASE
                WHEN parent.id IS NOT NULL AND niparent.nomordokumen IS NOT NULL THEN niparent.nomordokumen
                WHEN parent.id IS NOT NULL AND ni.nomordokumen IS NOT NULL THEN ni.nomordokumen
                ELSE NULL
            END as folder_nomordokumen
        "),
            DB::raw("
            CASE
                WHEN parent.id IS NOT NULL AND niparent.namadokumen IS NOT NULL THEN niparent.namadokumen
                WHEN parent.id IS NOT NULL AND ni.namadokumen IS NOT NULL THEN ni.namadokumen
                ELSE NULL
            END as folder_namadokumen
        ")
        )
            ->orderByDesc(DB::raw('COALESCE(odm.tglrevisi, odm.updated_at, odm.created_at)'))
            ->limit($limit)
            ->get();

        foreach ($log as $row) {
            $row->folder_path = $this->getPathFolderSurveilan($row->kdrinciandokumenhead, $row->jenisudr);
        }

        return $this->respond([
            'log' => $log,
            'limit' => $limit,
            'search' => $search,
        ]);
    }

    private function getPathFolderSurveilan($parentId, $jenisudr = null)
    {
        if (empty($parentId)) {
            return null;
        }

        $parents = [];
        $currentId = $parentId;
        $loopGuard = 0;

        while (!empty($currentId) && $loopGuard < 50) {
            $query = DB::table('surveilanrepo_m')
                ->select(
                    'id',
                    'kdrinciandokumenhead',
                    'namaisidokumen',
                    'jenisudr'
                )
                ->where('id', $currentId);

            $node = $query->first();

            if (!$node) {
                break;
            }

            array_unshift($parents, $node->namaisidokumen);

            $currentId = $node->kdrinciandokumenhead;
            $loopGuard++;
        }

        if (count($parents) == 0) {
            return null;
        }

        if (!empty($jenisudr)) {
            array_unshift($parents, $jenisudr);
        }

        return implode(' / ', $parents);
    }

    public function saveIsiDokumenSurveilan(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();

        try {
            $filename = null;
            $old = null;
            $uploadedNameWithoutExtension = null;

            if (!empty($request['id'])) {
                $old = DB::table('surveilanrepo_m')->where('id', $request['id'])->first();
                if (!$old) {
                    throw new \Exception("Data lama tidak ditemukan.");
                }
            }

            $alamaturlform = $request->has('alamaturlform') && trim((string)$request['alamaturlform']) !== ''
                ? trim((string)$request['alamaturlform'])
                : null;

            $pegawaicvfk = $request->has('pegawaicvfk') && $request['pegawaicvfk'] !== ''
                ? $request['pegawaicvfk']
                : null;

            $isLinkCv = !empty($alamaturlform) || !empty($pegawaicvfk);

            if (!$isLinkCv && $request->hasFile('fileDokumenMutu')) {
                $file = $request->file('fileDokumenMutu');

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa gambar (jpg, jpeg, png, webp), PDF, Word (doc, docx), atau Excel (xls, xlsx).");
                }

                $uploadedNameWithoutExtension = $this->makeDocumentNameWithoutExtension($file->getClientOriginalName());
                $filename = $this->generateStoredFilename($file->getClientOriginalName());
                $file->move(public_path('berkas-mutu'), $filename);
            } else {
                if ($old) {
                    $filename = $old->isidokumen;
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }
            }

            if ($isLinkCv) {
                $filename = null;
            }

            $namaIsi = trim((string)($request['namaisidokumen'] ?? ''));
            if ($namaIsi === '' && !empty($uploadedNameWithoutExtension)) {
                $namaIsi = $uploadedNameWithoutExtension;
            }

            $isHeader = empty($request['kdrinciandokumenhead']) ? 'H' : null;
            $kdrinci = $request->has('kdrinciandokumenhead') && $request['kdrinciandokumenhead'] !== ''
                ? $request['kdrinciandokumenhead']
                : null;
            $nomorindukfk = $request->has('nomorindukfk') && $request['nomorindukfk'] !== ''
                ? $request['nomorindukfk']
                : null;

            if (empty($request['id'])) {
                $jenisUdr = $request['jenisudr'] ?? null;
                $this->lockNomorTransaksi('nourut-surveilan', [$jenisUdr]);

                $nourut = DB::table('surveilanrepo_m')
                    ->when($jenisUdr === null || $jenisUdr === '', function ($query) {
                        $query->whereNull('jenisudr');
                    }, function ($query) use ($jenisUdr) {
                        $query->where('jenisudr', $jenisUdr);
                    })
                    ->max('nourut');
                $nourut = ((int) ($nourut ?? 0)) + 1;

                $this->lockNomorTransaksi('surveilanrepo-id');

                $newID = (int) DB::table('surveilanrepo_m')->max('id') + 1;
                $rootId = $newID;

                DB::table('surveilanrepo_m')->insert([
                    'id'                   => $newID,
                    'kdprofile'            => $kdProfile,
                    'statusenabled'        => true,
                    'kodeexternal'         => $isHeader,
                    'norec'                => substr($this->Uuid4(), 0, 32),
                    'kdisidokumen'         => $rootId,
                    'linkfolder'           => (string) $newID,
                    'keterangan'           => $request['keterangan'] ?? null,
                    'namaisidokumen'       => $namaIsi ?: null,
                    'nourut'               => $nourut,
                    'kdrinciandokumenhead' => $kdrinci,
                    'isidokumen'           => $filename,
                    'alamaturlform'        => $alamaturlform,
                    'jenisudr'             => $request['jenisudr'] ?? null,
                    'nomorindukfk'         => $nomorindukfk,
                    'pegawaifk'            => $this->getPegawaiId(),
                    'pegawaicvfk'          => $pegawaicvfk,
                    'revisike'             => 1,
                    'tglrevisi'            => now(),
                    'ishapus'              => false,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            } else {
                $fileChanged = !$isLinkCv && $request->hasFile('fileDokumenMutu') && $filename !== $old->isidokumen;

                if ($fileChanged) {
                    $rootId = $old->kdisidokumen ?: $old->id;

                    $this->lockNomorTransaksi('revisi-surveilan', [$rootId]);

                    $revisiKe = DB::table('surveilanrepo_m')
                        ->where('statusenabled', true)
                        ->where(function ($q) use ($rootId) {
                            $q->where('kdisidokumen', $rootId)
                                ->orWhere('id', $rootId);
                        })
                        ->count() + 1;

                    $this->lockNomorTransaksi('surveilanrepo-id');

                    $newID = (int) DB::table('surveilanrepo_m')->max('id') + 1;

                    $linkfolder = $old->linkfolder ?? null;

                    if (empty($linkfolder)) {
                        $linkfolder = (string) $rootId;
                    }

                    DB::table('surveilanrepo_m')->insert([
                        'id'                   => $newID,
                        'kdprofile'            => $kdProfile,
                        'statusenabled'        => true,
                        'kodeexternal'         => $isHeader,
                        'norec'                => substr($this->Uuid4(), 0, 32),
                        'kdisidokumen'         => $rootId,
                        'linkfolder'           => $linkfolder,
                        'keterangan'           => $request['keterangan'] ?? $old->keterangan,
                        'namaisidokumen'       => $namaIsi ?: $old->namaisidokumen,
                        'nourut'               => $request['nourut'] ?? $old->nourut,
                        'kdrinciandokumenhead' => $kdrinci ?? $old->kdrinciandokumenhead,
                        'isidokumen'           => $filename,
                        'alamaturlform'        => $alamaturlform,
                        'jenisudr'             => $request['jenisudr'] ?? $old->jenisudr,
                        'nomorindukfk'         => $nomorindukfk ?? $old->nomorindukfk,
                        'pegawaifk'            => $this->getPegawaiId(),
                        'pegawaicvfk'          => $pegawaicvfk,
                        'revisike'             => $revisiKe,
                        'tglrevisi'            => now(),
                        'ishapus'              => false,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                } else {
                    DB::table('surveilanrepo_m')->where('id', $old->id)->update([
                        'keterangan'           => $request['keterangan'] ?? $old->keterangan,
                        'namaisidokumen'       => $namaIsi !== '' ? $namaIsi : $old->namaisidokumen,
                        'nourut'               => $request['nourut'] ?? $old->nourut,
                        'kdrinciandokumenhead' => $kdrinci ?? $old->kdrinciandokumenhead,
                        'isidokumen'           => $isLinkCv ? null : ($filename ?? $old->isidokumen),
                        'alamaturlform'        => $alamaturlform,
                        'nomorindukfk'         => $nomorindukfk,
                        'pegawaicvfk'          => $pegawaicvfk,
                        'updated_at'           => now(),
                    ]);
                }
            }

            DB::commit();

            return $this->respond([], 200, "Sukses");
        } catch (Exception $e) {
            DB::rollback();

            return $this->respond([
                "ex" => $e->getMessage() . ' ' . $e->getLine(),
            ], 400, "Simpan Gagal");
        }
    }

    public function uploadMultiDokumenSurveilan(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();

        try {
            $jenisudr = $request->get('jenisudr');
            $headId = $request->get('kdrinciandokumenhead');
            $nomorindukfk = $request->get('nomorindukfk');

            if (empty($jenisudr) || !in_array($jenisudr, ['Jakarta', 'Gresik'])) {
                throw new \Exception('Jenis Dokumen Surveilan tidak valid.');
            }

            if (empty($headId)) {
                throw new \Exception('Folder target upload harus dipilih terlebih dahulu.');
            }

            $head = DB::table('surveilanrepo_m')
                ->where('id', $headId)
                ->where('statusenabled', true)
                ->where('jenisudr', $jenisudr)
                ->first();

            if (!$head) {
                throw new \Exception('Folder target upload tidak ditemukan atau tidak aktif.');
            }

            if (!$request->hasFile('files')) {
                throw new \Exception('Tidak ada file yang diupload.');
            }

            $files = $request->file('files');
            if (!is_array($files) || !count($files)) {
                throw new \Exception('Format file upload tidak valid.');
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
            $maxSize = 30 * 1024 * 1024;
            $saved = [];
            $uploadedCount = 0;
            $documentNames = collect($files)
                ->filter()
                ->map(function ($file) {
                    return strtolower($this->makeDocumentNameWithoutExtension($file->getClientOriginalName()));
                })
                ->unique()
                ->values();
            $existingLatestByName = DB::table('surveilanrepo_m as odm')
                ->select('odm.*', DB::raw("LOWER(COALESCE(odm.namaisidokumen, '')) as normalized_name"))
                ->where('odm.statusenabled', true)
                ->where('odm.jenisudr', $jenisudr)
                ->where('odm.kdrinciandokumenhead', $headId)
                ->whereIn(DB::raw("LOWER(COALESCE(odm.namaisidokumen, ''))"), $documentNames)
                ->orderByDesc(DB::raw('COALESCE(odm.revisike, 1)'))
                ->orderByDesc('odm.id')
                ->get()
                ->unique(function ($row) {
                    return $row->normalized_name;
                })
                ->keyBy(function ($row) {
                    return $row->normalized_name;
                });
            $nextNourut = null;

            foreach ($files as $file) {
                if (!$file) continue;

                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File {$file->getClientOriginalName()} tidak diizinkan. Hanya PDF, Word, Excel, JPG, PNG, atau WebP.");
                }

                if ($file->getSize() > $maxSize) {
                    throw new \Exception("File {$file->getClientOriginalName()} melebihi batas maksimal 30 MB.");
                }

                $originalName = $file->getClientOriginalName();
                $namaDokumen = $this->makeDocumentNameWithoutExtension($originalName);
                $filename = $this->generateStoredFilename($originalName);
                $documentKey = strtolower($namaDokumen);

                $file->move(public_path('berkas-mutu'), $filename);

                $existingLatest = $existingLatestByName->get($documentKey);

                if ($existingLatest) {
                    $rootId = $existingLatest->kdisidokumen ?: $existingLatest->id;

                    $doc = $this->createRevisionSurveilanDocumentRecord(
                        $kdProfile,
                        $rootId,
                        $namaDokumen,
                        $filename,
                        'Upload massal Dokumen Surveilan ' . $jenisudr,
                        $existingLatest->nourut,
                        $headId,
                        $jenisudr,
                        $nomorindukfk,
                        null
                    );

                    $saved[] = [
                        'id' => $doc->id,
                        'nama' => $doc->namaisidokumen,
                        'revisike' => $doc->revisike ?? 1,
                        'mode' => 'revisi',
                    ];
                } else {
                    if ($nextNourut === null) {
                        $this->lockNomorTransaksi('nourut-surveilan', [$jenisudr]);

                        $lastNourut = DB::table('surveilanrepo_m')
                            ->where('jenisudr', $jenisudr)
                            ->max('nourut');

                        $nextNourut = ($lastNourut ?? 0) + 1;
                    }

                    $doc = $this->createNewSurveilanDocumentRecord(
                        $kdProfile,
                        $namaDokumen,
                        $filename,
                        'Upload massal Dokumen Surveilan ' . $jenisudr,
                        $nextNourut,
                        $headId,
                        $jenisudr,
                        $nomorindukfk,
                        null
                    );
                    $nextNourut++;

                    $saved[] = [
                        'id' => $doc->id,
                        'nama' => $doc->namaisidokumen,
                        'revisike' => $doc->revisike ?? 1,
                        'mode' => 'baru',
                    ];
                }

                $existingLatestByName->put($documentKey, $doc);

                $uploadedCount++;
            }

            if ($uploadedCount <= 0) {
                throw new \Exception('Tidak ada file valid yang berhasil diproses.');
            }

            DB::commit();

            return $this->respond([
                'message' => $uploadedCount . ' file berhasil diupload ke folder ' . $jenisudr,
                'jumlah'  => $uploadedCount,
                'folder'  => [
                    'id'   => $head->id,
                    'nama' => $head->namaisidokumen,
                ],
                'data' => $saved,
            ], 200, 'Sukses');
        } catch (Exception $e) {
            DB::rollBack();

            return $this->respond([
                'message' => $e->getMessage(),
                'ex'      => $e->getMessage(),
            ], 400, 'Upload Gagal');
        }
    }

    public function hapusIsiDokumenSurveilan(Request $request)
    {
        DB::beginTransaction();
        try {
            DB::table('surveilanrepo_m')
                ->where('id', $request['id'])
                ->update([
                    'statusenabled' => false,
                    'ishapus'       => true,
                    'updated_at'    => now(),
                ]);

            DB::commit();

            return $this->respond([], 200, "Sukses");
        } catch (Exception $e) {
            DB::rollback();

            return $this->respond([], 400, "Hapus Gagal");
        }
    }

    public function lastNourutDokumenSurveilan(Request $r)
    {
        $jenisudr = $r->get('jenisudr');

        $q = DB::table('surveilanrepo_m')->where('statusenabled', true);

        if (!empty($jenisudr)) {
            $q->where('jenisudr', $jenisudr);
        }

        $lastNourut = $q->max('nourut');
        $nextNourut = ($lastNourut ?? 0) + 1;

        return $this->respond([
            'nourut' => $nextNourut,
            'last'   => $lastNourut,
        ]);
    }

    public function cetakDokumenSurveilan(Request $request)
    {
        $data = DB::table('surveilanrepo_m')
            ->where('id', $request['id'])
            ->first();

        if (!$data || !$data->isidokumen) {
            abort(404, 'Data Dokumen atau file tidak ditemukan');
        }

        $filename = basename($data->isidokumen);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Supaya nama file yang ada spasi/karakter khusus tetap bisa dibuka
        $safeFilename = rawurlencode($filename);

        if (in_array($extension, ['doc', 'docx'])) {
            $filepath = asset('berkas-mutu/' . $safeFilename);
        } else {
            $filepath = asset('berkas-mutu/' . $safeFilename);
        }

        return view('report.mutu.view-pdf', compact('filepath', 'data', 'extension'));
    }

    public function previewExcelSurveilan($id)
    {
        $data = DB::table('surveilanrepo_m')->where('id', $id)->first();
        if (!$data || !$data->isidokumen) {
            abort(404, 'File tidak ditemukan');
        }

        $filename = basename($data->isidokumen);
        $fullPath = public_path('berkas-mutu/' . $filename);
        if (!file_exists($fullPath)) {
            abort(404, "File Excel tidak ditemukan di path: $fullPath");
        }

        $spreadsheet = IOFactory::load($fullPath);
        $writer = new Html($spreadsheet);

        $output = '';
        foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
            $writer->setSheetIndex($index);
            ob_start();
            $writer->save('php://output');
            $sheetHtml = ob_get_clean();

            $output .= "<h2 style=\"font-family:sans-serif;font-size:1.2em;margin:1em 0;\">Sheet: {$sheetName}</h2>";
            $output .= $sheetHtml;
        }

        return response($output, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function riwayatIsiDokumenSurveilan(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return $this->respond(['message' => 'id diperlukan'], 400, 'Bad Request');
        }

        $row = DB::table('surveilanrepo_m')->where('id', $id)->first();
        if (!$row) {
            return $this->respond(['message' => 'Data tidak ditemukan'], 404, 'Not Found');
        }

        $root = $row->kdisidokumen ?: $row->id;

        $hasNullIsi = DB::table('surveilanrepo_m')
            ->where('statusenabled', true)
            ->where(function ($q) use ($root) {
                $q->where('kdisidokumen', $root)
                    ->orWhere('id', $root);
            })
            ->whereNull('isidokumen')
            ->exists();

        $revisiSelect = $hasNullIsi
            ? DB::raw('(COALESCE(revisike, 1) - 1) AS revisike')
            : DB::raw('COALESCE(revisike, 1) AS revisike');

        $list = DB::table('surveilanrepo_m')
            ->where('statusenabled', true)
            ->whereNotNull('isidokumen')
            ->where(function ($q) use ($root) {
                $q->where('kdisidokumen', $root)
                    ->orWhere('id', $root);
            })
            ->orderByRaw('COALESCE(revisike,1) ASC, id ASC')
            ->get([
                'id',
                'namaisidokumen',
                'isidokumen',
                'nomorindukfk',
                $revisiSelect,
                'tglrevisi',
            ]);

        return $this->respond([
            'root_id' => $root,
            'riwayat' => $list,
        ], 200, 'Sukses');
    }

    /* =========================================================
     * UDS DRIVE BARU
     * Endpoint di bawah ini sengaja dipisahkan dari endpoint UDS lama.
     * ========================================================= */

    private function normalizeNewDriveJenis($jenis)
    {
        $normalized = strtolower(trim((string) $jenis));
        $programs = [
            'mutu' => 'Mutu',
            'teknik' => 'Teknik',
            'admin' => 'Admin',
            'audit internal' => 'Audit Internal',
        ];

        if (!isset($programs[$normalized])) {
            throw new \Exception('Program UDS tidak valid.');
        }

        $value = $programs[$normalized];
        if ($value === 'Audit Internal'
            && !$this->dapatMembacaDokumenAuditInternal($this->getPegawaiId())) {
            throw new \Exception('Anda belum termapping pada Audit Internal.');
        }

        return $value;
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

    private function dapatMembacaDokumenAuditInternal($pegawaiId)
    {
        return $this->pegawaiAdalahManagerAuditInternal($pegawaiId)
            || DB::table('mappingauditorinternal_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('pegawaifk', (int) $pegawaiId)
                ->where('statusenabled', true)
                ->exists();
    }

    private function lingkupAuditInternalDariItem($itemId)
    {
        $currentId = $itemId ? (int) $itemId : null;
        $guard = 0;
        while ($currentId && $guard < 50) {
            $scope = DB::table('dokumenauditinternalfolder_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tipefolder', 'lingkup')
                ->where('udrfolderfk', $currentId)
                ->where('statusenabled', true)
                ->first(['tahun', 'kodejenisaudit']);
            if ($scope) {
                return $scope;
            }

            $item = DB::table('ulabdigitalrepo_m')
                ->where('id', $currentId)
                ->where('jenisudr', 'Audit Internal')
                ->first(['id', 'kdisidokumen', 'kdrinciandokumenhead', 'kodeexternal']);
            if (!$item) {
                return null;
            }
            $rootId = (int) ($item->kdisidokumen ?: $item->id);
            if ($item->kodeexternal === 'H' || $item->kdrinciandokumenhead === null
                || (int) $item->kdrinciandokumenhead === $rootId) {
                return null;
            }
            $currentId = (int) $item->kdrinciandokumenhead;
            $guard++;
        }

        return null;
    }

    private function dapatMengubahNewDrive($jenis, $targetId = null)
    {
        $kelompokUserId = $this->getKelompokUserId();
        $pegawaiId = $this->getPegawaiId();

        if ($jenis === 'Audit Internal') {
            if ($this->pegawaiAdalahManagerAuditInternal($pegawaiId)) {
                return false;
            }
            $mapping = DB::table('mappingauditorinternal_m')
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->where('pegawaifk', (int) $pegawaiId)
                    ->where('peran', 'auditee')
                    ->where('statusenabled', true)
                    ;
            if ($targetId) {
                $scope = $this->lingkupAuditInternalDariItem($targetId);
                if (!$scope) {
                    return false;
                }
                $mapping->where('tahun', (int) $scope->tahun)
                    ->where('kodejenisaudit', $scope->kodejenisaudit);
            }
            return $mapping->exists();
        }

        $kelompok = DB::table('kelompokuser_s')
            ->where('id', $kelompokUserId)
            ->value('kelompokuser');

        $kelompok = strtolower(trim((string) $kelompok));
        $lokasi = DB::table('pegawai_m')
            ->where('id', $pegawaiId)
            ->value('lokasikalibrasifk');

        return in_array($kelompok, ['asman', 'manager'], true)
            || ($jenis === 'Mutu' && $kelompok === 'mutu' && (int) $lokasi === 1)
            || ($jenis === 'Teknik' && in_array($kelompok, ['pelaksana', 'penyelia'], true))
            || ($jenis === 'Admin' && $kelompok === 'registrasi');
    }

    private function assertNewDriveWriteAccess($jenis, $targetId = null)
    {
        if (!$this->dapatMengubahNewDrive($jenis, $targetId)) {
            throw new \Exception('Anda tidak memiliki akses untuk mengubah Program ' . $jenis . '.');
        }
    }

    private function assertNewDriveFolderAccessManagement($folder)
    {
        $ownerId = DB::table('ulabdigitalrepo_m')
            ->where('id', (int) $folder->root_id)
            ->value('pegawaifk');

        if (empty($ownerId) || (int) $ownerId !== (int) $this->getPegawaiId()) {
            throw new \Exception('Hanya pemilik folder yang dapat mengatur akses folder.');
        }
    }

    private function newDriveFolderAccessMode($rootId)
    {
        $rootId = (int) $rootId;
        if (array_key_exists($rootId, $this->newDriveFolderAccessCache)) {
            return $this->newDriveFolderAccessCache[$rootId];
        }

        if ($this->newDriveFolderAccessTablesAvailable === null) {
            $this->newDriveFolderAccessTablesAvailable = Schema::hasTable('udr_drive_folder_access_m')
                && Schema::hasTable('udr_drive_folder_access_user_t');
        }

        if (!$this->newDriveFolderAccessTablesAvailable) {
            return $this->newDriveFolderAccessCache[$rootId] = [
                'mode' => 'all',
                'user_ids' => [],
            ];
        }

        $access = DB::table('udr_drive_folder_access_m')
            ->where('kdprofile', $this->kdProfile)
            ->where('udrrootid', $rootId)
            ->first(['id', 'accessmode']);

        if (!$access || !in_array($access->accessmode, ['all', 'selected', 'excluded'], true)) {
            return $this->newDriveFolderAccessCache[$rootId] = [
                'mode' => 'all',
                'user_ids' => [],
            ];
        }

        $userIds = DB::table('udr_drive_folder_access_user_t')
            ->where('folderaccessfk', $access->id)
            ->pluck('loginuserfk')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();

        return $this->newDriveFolderAccessCache[$rootId] = [
            'mode' => $access->accessmode,
            'user_ids' => $userIds,
        ];
    }

    private function primeNewDriveFolderAccessCache($rootIds)
    {
        $rootIds = collect($rootIds)
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter()
            ->unique()
            ->filter(function ($id) {
                return !array_key_exists($id, $this->newDriveFolderAccessCache);
            })
            ->values();

        if ($rootIds->isEmpty()) {
            return;
        }

        if ($this->newDriveFolderAccessTablesAvailable === null) {
            $this->newDriveFolderAccessTablesAvailable = Schema::hasTable('udr_drive_folder_access_m')
                && Schema::hasTable('udr_drive_folder_access_user_t');
        }

        if (!$this->newDriveFolderAccessTablesAvailable) {
            foreach ($rootIds as $rootId) {
                $this->newDriveFolderAccessCache[$rootId] = ['mode' => 'all', 'user_ids' => []];
            }
            return;
        }

        $accessRows = DB::table('udr_drive_folder_access_m')
            ->where('kdprofile', $this->kdProfile)
            ->whereIn('udrrootid', $rootIds->all())
            ->get(['id', 'udrrootid', 'accessmode']);
        $accessIds = $accessRows->pluck('id')->all();
        $usersByAccess = empty($accessIds)
            ? collect()
            : DB::table('udr_drive_folder_access_user_t')
                ->whereIn('folderaccessfk', $accessIds)
                ->get(['folderaccessfk', 'loginuserfk'])
                ->groupBy('folderaccessfk');
        $accessByRoot = $accessRows->keyBy(function ($row) {
            return (int) $row->udrrootid;
        });

        foreach ($rootIds as $rootId) {
            $access = $accessByRoot->get($rootId);
            $mode = $access && in_array($access->accessmode, ['all', 'selected', 'excluded'], true)
                ? $access->accessmode
                : 'all';
            $userIds = $access
                ? collect($usersByAccess->get($access->id, []))->pluck('loginuserfk')->map(function ($id) {
                    return (int) $id;
                })->values()->all()
                : [];

            $this->newDriveFolderAccessCache[$rootId] = [
                'mode' => $mode,
                'user_ids' => $userIds,
            ];
        }
    }

    private function newDriveFolderAllowsCurrentUser($rootId)
    {
        $access = $this->newDriveFolderAccessMode($rootId);
        if ($access['mode'] === 'all') {
            return true;
        }

        $selected = in_array((int) $this->getUserId(), $access['user_ids'], true);

        return $access['mode'] === 'selected' ? $selected : !$selected;
    }

    private function newDriveParentIdFromRow($row)
    {
        $rootId = (int) ($row->kdisidokumen ?: $row->id);

        return $row->kodeexternal === 'H'
            || (int) $row->kdrinciandokumenhead === $rootId
            ? null
            : ($row->kdrinciandokumenhead !== null ? (int) $row->kdrinciandokumenhead : null);
    }

    private function newDriveUserCanAccessResolvedItem($item, $includeItemFolder = true)
    {
        if (($item->jenisudr ?? null) === 'Audit Internal') {
            // Folder audit selalu terbuka untuk seluruh pengguna yang termapping;
            // pembatasan hanya berlaku pada aksi tulis berdasarkan lingkup auditee.
            return $this->dapatMembacaDokumenAuditInternal($this->getPegawaiId());
        }

        if ($includeItemFolder && !empty($item->is_folder)
            && !$this->newDriveFolderAllowsCurrentUser($item->root_id)) {
            return false;
        }

        $parentId = $item->drive_parent_id !== null ? (int) $item->drive_parent_id : null;
        $visited = [];
        $guard = 0;

        while ($parentId !== null && $guard < 50) {
            if (isset($visited[$parentId])) {
                return false;
            }
            $visited[$parentId] = true;

            $parent = DB::table('ulabdigitalrepo_m')
                ->where('id', $parentId)
                ->first(['id', 'kdisidokumen', 'kdrinciandokumenhead', 'kodeexternal']);
            if (!$parent) {
                return false;
            }

            $parentRootId = (int) ($parent->kdisidokumen ?: $parent->id);
            if (!$this->newDriveFolderAllowsCurrentUser($parentRootId)) {
                return false;
            }

            $parentId = $this->newDriveParentIdFromRow($parent);
            $guard++;
        }

        return $parentId === null;
    }

    private function assertNewDriveReadAccess($item)
    {
        if (!$this->newDriveUserCanAccessResolvedItem($item)) {
            throw new \Exception('Anda tidak memiliki akses untuk membuka folder atau dokumen ini.');
        }
    }

    private function decorateNewDriveAccess($items, $search = false, $trash = false)
    {
        $items = collect($items);
        $this->primeNewDriveFolderAccessCache(
            $items->where('kind', 'folder')->pluck('root_id')->all()
        );

        return $items->map(function ($item) use ($search, $trash) {
            $ancestorsAccessible = true;
            if ($search || $trash) {
                $resolved = $this->resolveNewDriveItem((int) $item['id'], $trash);
                $ancestorsAccessible = $this->newDriveUserCanAccessResolvedItem($resolved, false);
            }
            $canAccess = $ancestorsAccessible;

            if ($item['kind'] === 'folder') {
                $folderAccess = $this->newDriveFolderAccessMode($item['root_id']);
                $canAccess = $ancestorsAccessible
                    && $this->newDriveFolderAllowsCurrentUser($item['root_id']);
                $item['access_mode'] = $folderAccess['mode'];
                $item['is_restricted'] = $folderAccess['mode'] !== 'all';
            }

            $item['can_access'] = $canAccess;
            $item['_ancestors_accessible'] = $ancestorsAccessible;

            return $item;
        })->filter(function ($item) use ($search) {
            if (!$search) {
                return true;
            }

            return $item['kind'] === 'folder'
                ? $item['_ancestors_accessible']
                : $item['can_access'];
        })->map(function ($item) {
            unset($item['_ancestors_accessible']);
            return $item;
        })->values();
    }

    private function newDriveRootExpression($alias = 'odm')
    {
        return "COALESCE(NULLIF({$alias}.kdisidokumen,'')::integer, {$alias}.id)";
    }

    private function newDriveParentExpression($alias = 'odm')
    {
        return "CASE WHEN COALESCE({$alias}.kodeexternal, '') = 'H' "
            . "OR {$alias}.kdrinciandokumenhead = " . $this->newDriveRootExpression($alias) . ' '
            . "THEN NULL ELSE {$alias}.kdrinciandokumenhead END";
    }

    private function newDriveLatestSub($jenis, $trash = false)
    {
        $alias = 'ndl';
        $rootExpression = $this->newDriveRootExpression($alias);
        $childParentExpression = $this->newDriveParentExpression('drive_child');

        $usedAsParent = DB::table('ulabdigitalrepo_m as drive_child')
            ->selectRaw($childParentExpression . ' as parent_id')
            ->where('drive_child.jenisudr', $jenis)
            ->whereRaw($childParentExpression . ' IS NOT NULL');

        if ($trash) {
            $usedAsParent->where('drive_child.statusenabled', false)
                ->whereRaw('COALESCE(drive_child.ishapus, 0) = 1');
        } else {
            $usedAsParent->where('drive_child.statusenabled', true);
        }

        $usedAsParent->groupBy(DB::raw($childParentExpression));

        $query = DB::table('ulabdigitalrepo_m as ' . $alias)
            ->leftJoinSub($usedAsParent, 'drive_parent_usage', function ($join) use ($alias) {
                $join->on($alias . '.id', '=', 'drive_parent_usage.parent_id');
            })
            ->selectRaw('MAX(' . $alias . '.id) as latest_id')
            ->selectRaw($rootExpression . ' as root_id')
            ->selectRaw(
                'COALESCE(MAX(drive_parent_usage.parent_id), '
                . "MAX(CASE WHEN COALESCE({$alias}.isidokumen, '') = '' "
                . "AND COALESCE({$alias}.alamaturlform, '') = '' THEN {$alias}.id END)) as folder_id"
            )
            ->where($alias . '.jenisudr', $jenis);

        if ($trash) {
            $query->where($alias . '.statusenabled', false)
                ->whereRaw('COALESCE(' . $alias . '.ishapus, 0) = 1');
        } else {
            $query->where($alias . '.statusenabled', true);
        }

        return $query->groupBy(DB::raw($rootExpression));
    }

    private function newDriveItemsQuery($jenis, $trash = false)
    {
        return DB::table('ulabdigitalrepo_m as odm')
            ->joinSub($this->newDriveLatestSub($jenis, $trash), 'latest_drive', function ($join) {
                $join->on('odm.id', '=', 'latest_drive.latest_id');
            })
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'odm.pegawaifk')
            ->leftJoin('udr_nomorinduk_m as ni', 'ni.id', '=', 'odm.nomorindukfk');
    }

    private function newDriveCount($jenis, $trash = false)
    {
        return DB::query()
            ->fromSub($this->newDriveLatestSub($jenis, $trash), 'drive_count')
            ->count();
    }

    private function newDriveBreadcrumbs($parentId, $jenis)
    {
        $breadcrumbs = [];
        $currentId = $parentId;
        $guard = 0;

        while (!empty($currentId) && $guard < 50) {
            $node = DB::table('ulabdigitalrepo_m')
                ->where('id', $currentId)
                ->where('jenisudr', $jenis)
                ->where('statusenabled', true)
                ->first(['id', 'kdisidokumen', 'namaisidokumen', 'kdrinciandokumenhead', 'kodeexternal']);

            if (!$node) {
                break;
            }

            array_unshift($breadcrumbs, [
                'id' => $node->id,
                'name' => $node->namaisidokumen,
            ]);

            $nodeRootId = (int) ($node->kdisidokumen ?: $node->id);
            $currentId = $node->kodeexternal === 'H'
                || (int) $node->kdrinciandokumenhead === $nodeRootId
                ? null
                : $node->kdrinciandokumenhead;
            $guard++;
        }

        array_unshift($breadcrumbs, [
            'id' => null,
            'name' => 'Program ' . $jenis,
        ]);

        return $breadcrumbs;
    }

    private function newDriveFolderPaths($jenis, $folders)
    {
        $allRows = DB::table('ulabdigitalrepo_m')
            ->where('jenisudr', $jenis)
            ->where('statusenabled', true)
            ->orderByDesc('id')
            ->get([
                'id',
                'kdisidokumen',
                'kdrinciandokumenhead',
                'kodeexternal',
                'namaisidokumen',
            ]);

        $rowsById = [];
        $latestNameByRoot = [];
        foreach ($allRows as $row) {
            $rootId = (int) ($row->kdisidokumen ?: $row->id);
            $rowsById[(int) $row->id] = $row;
            if (!isset($latestNameByRoot[$rootId])) {
                $latestNameByRoot[$rootId] = $row->namaisidokumen;
            }
        }

        $paths = [];
        foreach ($folders as $folder) {
            $segments = [];
            $currentId = (int) $folder->id;
            $visited = [];
            $guard = 0;

            while ($currentId && isset($rowsById[$currentId]) && $guard < 50) {
                if (isset($visited[$currentId])) {
                    break;
                }
                $visited[$currentId] = true;

                $node = $rowsById[$currentId];
                $rootId = (int) ($node->kdisidokumen ?: $node->id);
                array_unshift(
                    $segments,
                    $latestNameByRoot[$rootId] ?? $node->namaisidokumen
                );

                $parentId = $node->kodeexternal === 'H'
                    || (int) $node->kdrinciandokumenhead === $rootId
                    ? null
                    : $node->kdrinciandokumenhead;
                $currentId = $parentId !== null ? (int) $parentId : 0;
                $guard++;
            }

            array_unshift($segments, 'Program ' . $jenis);
            $paths[(int) $folder->root_id] = implode(' / ', $segments);
        }

        return $paths;
    }

    private function serializeNewDriveItems($rows)
    {
        return collect($rows)->map(function ($row) {
            $hasFile = trim((string) $row->isidokumen) !== '';
            $hasLink = trim((string) $row->alamaturlform) !== '';
            $folderId = $row->folder_id !== null ? (int) $row->folder_id : null;
            $isFolder = $folderId !== null || (!$hasFile && !$hasLink);

            if ($isFolder) {
                $kind = 'folder';
            } elseif ($hasLink) {
                $kind = 'link';
            } else {
                $kind = 'file';
            }

            $extension = $hasFile
                ? strtolower(pathinfo((string) $row->isidokumen, PATHINFO_EXTENSION))
                : null;
            $size = null;

            if ($hasFile) {
                $fullPath = public_path('berkas-mutu/' . basename((string) $row->isidokumen));
                if (is_file($fullPath)) {
                    $size = filesize($fullPath);
                }
            }

            return [
                'id' => $isFolder ? ($folderId ?: (int) $row->id) : (int) $row->id,
                'root_id' => (int) $row->root_id,
                'parent_id' => $row->parent_id !== null ? (int) $row->parent_id : null,
                'name' => $row->namaisidokumen,
                'kind' => $kind,
                'extension' => $isFolder ? null : $extension,
                'stored_file' => $isFolder ? null : $row->isidokumen,
                'link' => $isFolder ? null : $row->alamaturlform,
                'description' => $row->keterangan,
                'revision' => (int) ($row->revisike ?: 1),
                'modified_at' => $row->tglrevisi ?: ($row->updated_at ?: $row->created_at),
                'created_at' => $row->created_at,
                'owner' => $row->namapegawai ?: '-',
                'owner_id' => property_exists($row, 'owner_id') && $row->owner_id !== null
                    ? (int) $row->owner_id
                    : null,
                'can_manage_access' => $isFolder
                    && property_exists($row, 'owner_id')
                    && (int) $row->owner_id === (int) $this->getPegawaiId(),
                'size' => $size,
                'nomor_induk_fk' => $row->nomorindukfk !== null ? (int) $row->nomorindukfk : null,
                'nomor_induk' => $row->nomordokumen,
                'nama_nomor_induk' => $row->namadokumen,
                'is_attachment' => property_exists($row, 'is_attachment')
                    ? (bool) $row->is_attachment
                    : false,
            ];
        })->values();
    }

    private function newDriveAttachmentItem($rootId, $parentId)
    {
        $rootExpression = $this->newDriveRootExpression('attachment');
        $row = DB::table('ulabdigitalrepo_m as attachment')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'attachment.pegawaifk')
            ->leftJoin('udr_nomorinduk_m as ni', 'ni.id', '=', 'attachment.nomorindukfk')
            ->where('attachment.statusenabled', true)
            ->whereRaw($rootExpression . ' = ?', [(int) $rootId])
            ->where(function ($q) {
                $q->whereRaw("COALESCE(attachment.isidokumen, '') <> ''")
                    ->orWhereRaw("COALESCE(attachment.alamaturlform, '') <> ''");
            })
            ->orderByDesc('attachment.id')
            ->first([
                'attachment.id',
                DB::raw($rootExpression . ' as root_id'),
                DB::raw('NULL::integer as folder_id'),
                DB::raw((int) $parentId . '::integer as parent_id'),
                'attachment.namaisidokumen',
                'attachment.isidokumen',
                'attachment.alamaturlform',
                'attachment.keterangan',
                'attachment.revisike',
                'attachment.tglrevisi',
                'attachment.created_at',
                'attachment.updated_at',
                'attachment.nomorindukfk',
                'attachment.pegawaifk as owner_id',
                'ni.nomordokumen',
                'ni.namadokumen',
                DB::raw("COALESCE(pg.namalengkap, '-') as namapegawai"),
                DB::raw('TRUE as is_attachment'),
            ]);

        if (!$row) {
            return null;
        }

        return $this->serializeNewDriveItems([$row])->first();
    }

    private function newDriveRevisionIds($rootId, $trash = false)
    {
        $query = DB::table('ulabdigitalrepo_m')
            ->where(function ($q) use ($rootId) {
                $q->where('kdisidokumen', (string) $rootId)
                    ->orWhere('id', $rootId);
            });

        if ($trash) {
            $query->where('statusenabled', false)
                ->whereRaw('COALESCE(ishapus, 0) = 1');
        } else {
            $query->where('statusenabled', true);
        }

        return $query->pluck('id')->map(function ($id) {
            return (int) $id;
        })->values()->all();
    }

    private function resolveNewDriveItem($id, $trash = false)
    {
        $seed = DB::table('ulabdigitalrepo_m')->where('id', $id)->first();
        if (!$seed) {
            throw new \Exception('Item UDS tidak ditemukan.');
        }

        $rootId = (int) ($seed->kdisidokumen ?: $seed->id);
        $query = DB::table('ulabdigitalrepo_m')
            ->where(function ($q) use ($rootId) {
                $q->where('kdisidokumen', (string) $rootId)
                    ->orWhere('id', $rootId);
            });

        if ($trash) {
            $query->where('statusenabled', false)
                ->whereRaw('COALESCE(ishapus, 0) = 1');
        } else {
            $query->where('statusenabled', true);
        }

        $item = $query->orderByDesc('id')->first();
        if (!$item) {
            throw new \Exception($trash ? 'Item tidak ditemukan di Sampah.' : 'Item UDS tidak aktif.');
        }

        $item->root_id = $rootId;
        $revisionIds = $this->newDriveRevisionIds($rootId, $trash);
        $childParentExpression = $this->newDriveParentExpression('drive_child_lookup');
        $childQuery = DB::table('ulabdigitalrepo_m')
            ->from('ulabdigitalrepo_m as drive_child_lookup')
            ->where('jenisudr', $item->jenisudr)
            ->whereIn(DB::raw($childParentExpression), $revisionIds);

        if ($trash) {
            $childQuery->where('statusenabled', false)
                ->whereRaw('COALESCE(ishapus, 0) = 1');
        } else {
            $childQuery->where('statusenabled', true);
        }

        $folderRow = $childQuery
            ->selectRaw('MAX(' . $childParentExpression . ') as folder_id')
            ->first();
        $folderId = $folderRow ? $folderRow->folder_id : null;

        $emptyFolderQuery = DB::table('ulabdigitalrepo_m')
            ->whereIn('id', $revisionIds)
            ->whereRaw("COALESCE(isidokumen, '') = ''")
            ->whereRaw("COALESCE(alamaturlform, '') = ''");
        $emptyFolderId = $emptyFolderQuery->max('id');

        $item->is_folder = $folderId !== null || $emptyFolderId !== null;
        $item->drive_id = $item->is_folder
            ? (int) ($folderId ?: $emptyFolderId)
            : (int) $item->id;
        $item->drive_parent_id = $item->kodeexternal === 'H'
            || (int) $item->kdrinciandokumenhead === $rootId
            ? null
            : $item->kdrinciandokumenhead;

        return $item;
    }

    private function validateNewDriveParent($parentId, $jenis)
    {
        if ($parentId === null || $parentId === '') {
            return null;
        }

        $parent = $this->resolveNewDriveItem((int) $parentId);
        $isFolder = (bool) $parent->is_folder;

        if ($parent->jenisudr !== $jenis || !$isFolder) {
            throw new \Exception('Folder tujuan tidak valid.');
        }

        $this->assertNewDriveReadAccess($parent);

        return $parent;
    }

    private function nextNewDriveNourut($jenis)
    {
        $this->lockNomorTransaksi('nourut-udr', [$jenis]);

        return ((int) DB::table('ulabdigitalrepo_m')
            ->where('jenisudr', $jenis)
            ->max('nourut')) + 1;
    }

    private function findExistingNewDriveFile($documentName, $extension, $parentId, $jenis)
    {
        $query = DB::table('ulabdigitalrepo_m')
            ->where('statusenabled', true)
            ->where('jenisudr', $jenis)
            ->whereNotNull('isidokumen')
            ->whereRaw("TRIM(COALESCE(isidokumen, '')) <> ''")
            ->whereRaw('LOWER(namaisidokumen) = ?', [strtolower($documentName)]);

        if ($parentId === null) {
            $query->whereRaw($this->newDriveParentExpression('ulabdigitalrepo_m') . ' IS NULL');
        } else {
            $query->whereRaw(
                $this->newDriveParentExpression('ulabdigitalrepo_m') . ' = ?',
                [(int) $parentId]
            );
        }

        return $query->orderByDesc('id')->get()->first(function ($row) use ($extension) {
            return strtolower(pathinfo((string) $row->isidokumen, PATHINFO_EXTENSION)) === $extension;
        });
    }

    private function findExistingNewDriveFolder($name, $parentId, $jenis)
    {
        $query = DB::table('ulabdigitalrepo_m')
            ->where('statusenabled', true)
            ->where('jenisudr', $jenis)
            ->whereNull('isidokumen')
            ->where(function ($q) {
                $q->whereNull('alamaturlform')->orWhere('alamaturlform', '');
            })
            ->whereRaw('LOWER(namaisidokumen) = ?', [strtolower($name)]);

        if ($parentId === null) {
            $query->whereRaw($this->newDriveParentExpression('ulabdigitalrepo_m') . ' IS NULL');
        } else {
            $query->whereRaw(
                $this->newDriveParentExpression('ulabdigitalrepo_m') . ' = ?',
                [(int) $parentId]
            );
        }

        return $query->orderByDesc('id')->first();
    }

    private function logNewDriveActivity($rootId, $jenis, $action, $name, $parentId = null, array $metadata = [])
    {
        if (!Schema::hasTable('udr_drive_activity_t')) {
            return;
        }

        DB::table('udr_drive_activity_t')->insert([
            'udrrootid' => $rootId,
            'jenisudr' => $jenis,
            'aksi' => $action,
            'namaitem' => $name,
            'parentid' => $parentId,
            'pegawaifk' => $this->getPegawaiId(),
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function findOrCreateNewDriveFolder($name, $parentId, $jenis, &$nextNourut, array &$cache)
    {
        $cacheKey = (string) ($parentId ?: 'root') . '|' . strtolower($name);
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $query = DB::table('ulabdigitalrepo_m')
            ->where('statusenabled', true)
            ->where('jenisudr', $jenis)
            ->whereNull('isidokumen')
            ->where(function ($q) {
                $q->whereNull('alamaturlform')->orWhere('alamaturlform', '');
            })
            ->whereRaw('LOWER(namaisidokumen) = ?', [strtolower($name)]);

        if (empty($parentId)) {
            $query->whereRaw($this->newDriveParentExpression('ulabdigitalrepo_m') . ' IS NULL');
        } else {
            $query->whereRaw(
                $this->newDriveParentExpression('ulabdigitalrepo_m') . ' = ?',
                [(int) $parentId]
            );
        }

        $existing = $query->orderByDesc('id')->first();
        if ($existing) {
            $cache[$cacheKey] = $existing;
            return $existing;
        }

        $folder = $this->createNewDocumentRecord(
            $this->kdProfile,
            $name,
            null,
            'Folder UDS Drive',
            $nextNourut++,
            $parentId ?: null,
            $jenis,
            null,
            empty($parentId) ? 'H' : null
        );

        $this->logNewDriveActivity(
            (int) ($folder->kdisidokumen ?: $folder->id),
            $jenis,
            'CREATE_FOLDER',
            $name,
            $parentId ?: null
        );

        $cache[$cacheKey] = $folder;
        return $folder;
    }

    private function findOrCreateAuditInternalFolder($name, $parentId, &$nextId, &$nextNourut)
    {
        $existing = $this->findExistingNewDriveFolder($name, $parentId, 'Audit Internal');
        if ($existing) {
            return $existing;
        }

        $id = $nextId++;
        $data = [
            'id' => $id,
            'kdprofile' => (int) $this->kdProfile,
            'statusenabled' => true,
            'kodeexternal' => $parentId ? null : 'H',
            'norec' => substr($this->Uuid4(), 0, 32),
            'kdisidokumen' => $id,
            'keterangan' => 'Folder otomatis Dokumen Audit Internal',
            'namaisidokumen' => $name,
            'nourut' => $nextNourut++,
            'kdrinciandokumenhead' => $parentId ?: null,
            'isidokumen' => null,
            'alamaturlform' => null,
            'jenisudr' => 'Audit Internal',
            'pegawaifk' => $this->getPegawaiId(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('ulabdigitalrepo_m', 'revisike')) {
            $data['revisike'] = 1;
        }
        if (Schema::hasColumn('ulabdigitalrepo_m', 'tglrevisi')) {
            $data['tglrevisi'] = now();
        }
        DB::table('ulabdigitalrepo_m')->insert($data);
        $folder = DB::table('ulabdigitalrepo_m')->where('id', $id)->first();
        $this->logNewDriveActivity($id, 'Audit Internal', 'CREATE_FOLDER', $name, $parentId ?: null);

        return $folder;
    }

    private function collectNewDriveTreeRoots($item, $trash = false)
    {
        $query = DB::table('ulabdigitalrepo_m')
            ->where('jenisudr', $item->jenisudr);

        if ($trash) {
            $query->where('statusenabled', false)
                ->whereRaw('COALESCE(ishapus, 0) = 1');
        } else {
            $query->where('statusenabled', true);
        }

        $rows = $query->orderByDesc('id')->get([
            'id',
            'kdisidokumen',
            'kdrinciandokumenhead',
            'kodeexternal',
        ]);

        $latestByRoot = [];
        $rowIdToRoot = [];
        foreach ($rows as $row) {
            $rootId = (int) ($row->kdisidokumen ?: $row->id);
            $rowIdToRoot[(int) $row->id] = $rootId;
            if (!isset($latestByRoot[$rootId])) {
                $row->root_id = $rootId;
                $latestByRoot[$rootId] = $row;
            }
        }

        $childrenByParentRoot = [];
        foreach ($latestByRoot as $childRootId => $row) {
            $parentRowId = $row->kodeexternal !== 'H' && $row->kdrinciandokumenhead !== null
                ? (int) $row->kdrinciandokumenhead
                : null;

            if ($parentRowId !== null
                && isset($rowIdToRoot[$parentRowId])
                && (int) $rowIdToRoot[$parentRowId] === (int) $childRootId) {
                $parentRowId = null;
            }

            if ($parentRowId !== null && isset($rowIdToRoot[$parentRowId])) {
                $parentRootId = $rowIdToRoot[$parentRowId];
                $childrenByParentRoot[$parentRootId][] = (int) $childRootId;
            }
        }

        $queue = [(int) $item->root_id];
        $rootIds = [];
        $visited = [];

        while (!empty($queue) && count($visited) < 5000) {
            $rootId = array_shift($queue);
            if (isset($visited[$rootId])) {
                continue;
            }

            $visited[$rootId] = true;
            $rootIds[] = (int) $rootId;

            foreach ($childrenByParentRoot[$rootId] ?? [] as $childRootId) {
                $queue[] = (int) $childRootId;
            }
        }

        if (!in_array((int) $item->root_id, $rootIds, true)) {
            $rootIds[] = (int) $item->root_id;
        }

        return array_values(array_unique($rootIds));
    }

    public function syncAuditInternalDrive(Request $request)
    {
        try {
            $pegawaiId = $this->getPegawaiId();
            if (!$this->dapatMembacaDokumenAuditInternal($pegawaiId)) {
                throw new \Exception('Anda belum termapping pada Audit Internal.');
            }
            if (!Schema::hasTable('dokumenauditinternalfolder_m')) {
                throw new \Exception('Struktur Dokumen Audit Internal belum tersedia.');
            }

            $urutanKode = [
                'mutu',
                'kelistrikan-jakarta',
                'tekanan-jakarta',
                'suhu-jakarta',
                'vibrasi-jakarta',
                'kelistrikan-gresik',
                'tekanan-gresik',
                'suhu-gresik',
                'dimensi-gresik',
            ];
            $urutan = array_flip($urutanKode);

            $audits = DB::table('temuanketidaksesuaian_t as a')
                ->where('a.kdprofile', (int) $this->kdProfile)
                ->where('a.statusenabled', true)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('mappingauditorinternal_m as ma')
                        ->whereColumn('ma.kodejenisaudit', 'a.kodejenisaudit')
                        ->whereRaw('ma.tahun = EXTRACT(YEAR FROM a.tanggalaudit)::integer')
                        ->where('ma.kdprofile', (int) $this->kdProfile)
                        ->where('ma.statusenabled', true);
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('temuanketidaksesuaiandetail_t as d')
                        ->whereColumn('d.auditfk', 'a.id')
                        ->where('d.statusenabled', true);
                })
                ->get()
                ->sort(function ($a, $b) use ($urutan) {
                    $tahunA = (int) date('Y', strtotime($a->tanggalaudit));
                    $tahunB = (int) date('Y', strtotime($b->tanggalaudit));
                    if ($tahunA !== $tahunB) {
                        return $tahunB <=> $tahunA;
                    }

                    $posisiA = $urutan[$a->kodejenisaudit] ?? PHP_INT_MAX;
                    $posisiB = $urutan[$b->kodejenisaudit] ?? PHP_INT_MAX;
                    return $posisiA === $posisiB ? ((int) $a->id <=> (int) $b->id) : ($posisiA <=> $posisiB);
                })
                ->values();

            $auditIds = $audits->pluck('id')->map(fn ($id) => (int) $id)->all();
            $detailIdsAktif = empty($auditIds) ? collect() : DB::table('temuanketidaksesuaiandetail_t')
                ->whereIn('auditfk', $auditIds)
                ->where('statusenabled', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            // Folder temuan kosong yang sumbernya sudah dinonaktifkan ikut dipindahkan ke sampah.
            // Folder berisi dokumen tetap dipertahankan agar bukti lama tidak hilang.
            $staleTemuanMaps = DB::table('dokumenauditinternalfolder_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tipefolder', 'temuan')
                ->when($detailIdsAktif->isNotEmpty(), fn ($query) => $query->whereNotIn('referensifk', $detailIdsAktif->all()))
                ->when($detailIdsAktif->isEmpty(), fn ($query) => $query)
                ->get(['id', 'udrfolderfk']);
            foreach ($staleTemuanMaps as $staleMap) {
                DB::table('dokumenauditinternalfolder_m')->where('id', $staleMap->id)->update([
                    'statusenabled' => false,
                    'updated_at' => now(),
                ]);
                $memilikiIsi = DB::table('ulabdigitalrepo_m')
                    ->where('statusenabled', true)
                    ->where('kdrinciandokumenhead', (int) $staleMap->udrfolderfk)
                    ->exists();
                if (!$memilikiIsi) {
                    DB::table('ulabdigitalrepo_m')->where('id', (int) $staleMap->udrfolderfk)->update([
                        'statusenabled' => false,
                        'ishapus' => 1,
                        'updated_at' => now(),
                    ]);
                }
            }

            $rootFolderIds = DB::table('dokumenauditinternalfolder_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->where('tipefolder', 'lingkup')
                ->where('statusenabled', true)
                ->pluck('udrfolderfk')
                ->map(fn ($id) => (int) $id)
                ->all();
            if (!empty($rootFolderIds)) {
                DB::table('udr_drive_folder_access_m')
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->whereIn('udrrootid', $rootFolderIds)
                    ->update(['accessmode' => 'all', 'updated_at' => now()]);
            }
            $jumlahDetailAktif = $detailIdsAktif->count();
            $jumlahStrukturSeharusnya = $audits->count() + $jumlahDetailAktif;
            $jumlahStrukturValid = empty($auditIds) ? 0 : DB::table('dokumenauditinternalfolder_m as map')
                ->join('ulabdigitalrepo_m as folder', 'folder.id', '=', 'map.udrfolderfk')
                ->where('map.kdprofile', (int) $this->kdProfile)
                ->whereIn('map.auditfk', $auditIds)
                ->where('map.statusenabled', true)
                ->where('folder.statusenabled', true)
                ->where('folder.jenisudr', 'Audit Internal')
                ->count();
            $sumberTerakhir = collect([
                empty($auditIds) ? null : DB::table('temuanketidaksesuaian_t')->whereIn('id', $auditIds)->max('updated_at'),
                empty($auditIds) ? null : DB::table('temuanketidaksesuaiandetail_t')->whereIn('auditfk', $auditIds)->max('updated_at'),
                DB::table('mappingauditorinternal_m')->where('kdprofile', (int) $this->kdProfile)->max('updated_at'),
            ])->filter()->max();
            $sinkronTerakhir = empty($auditIds) ? null : DB::table('dokumenauditinternalfolder_m')
                ->where('kdprofile', (int) $this->kdProfile)
                ->whereIn('auditfk', $auditIds)
                ->max('updated_at');

            if ($jumlahStrukturSeharusnya === $jumlahStrukturValid
                && $jumlahStrukturSeharusnya > 0
                && $sinkronTerakhir
                && (!$sumberTerakhir || strtotime($sinkronTerakhir) >= strtotime($sumberTerakhir))) {
                return $this->respond([
                    'jumlahLingkup' => $audits->count(),
                    'jumlahTemuan' => $jumlahDetailAktif,
                    'alreadySynced' => true,
                ], 200, 'Struktur Dokumen Audit Internal sudah terbaru');
            }

            DB::beginTransaction();
            $this->lockNomorTransaksi('audit-internal-drive-sync', [(int) $this->kdProfile]);

            $nextNourut = ((int) DB::table('ulabdigitalrepo_m')
                ->where('jenisudr', 'Audit Internal')
                ->max('nourut')) + 1;
            $nextId = ((int) DB::table('ulabdigitalrepo_m')->max('id')) + 1;
            $jumlahLingkup = 0;
            $jumlahTemuan = 0;

            foreach ($audits as $audit) {
                $tahun = (int) date('Y', strtotime($audit->tanggalaudit));
                $posisi = ($urutan[$audit->kodejenisaudit] ?? 98) + 1;
                $namaLingkup = sprintf('%d - %02d - %s', $tahun, $posisi, $audit->jenisaudit);

                $scopeMap = DB::table('dokumenauditinternalfolder_m')
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->where('tipefolder', 'lingkup')
                    ->where('referensifk', (int) $audit->id)
                    ->where('statusenabled', true)
                    ->first();
                $scopeFolder = $scopeMap
                    ? DB::table('ulabdigitalrepo_m')
                        ->where('id', (int) $scopeMap->udrfolderfk)
                        ->where('jenisudr', 'Audit Internal')
                        ->where('statusenabled', true)
                        ->first()
                    : null;

                if (!$scopeFolder) {
                    $scopeFolder = $this->findOrCreateAuditInternalFolder(
                        mb_substr($namaLingkup, 0, 180),
                        null,
                        $nextId,
                        $nextNourut
                    );
                }

                DB::table('dokumenauditinternalfolder_m')->updateOrInsert(
                    [
                        'kdprofile' => (int) $this->kdProfile,
                        'tipefolder' => 'lingkup',
                        'referensifk' => (int) $audit->id,
                    ],
                    [
                        'statusenabled' => true,
                        'norec' => $scopeMap->norec ?? (string) $this->Uuid4(),
                        'auditfk' => (int) $audit->id,
                        'temuanfk' => null,
                        'tahun' => $tahun,
                        'kodejenisaudit' => $audit->kodejenisaudit,
                        'udrfolderfk' => (int) $scopeFolder->id,
                        'updated_at' => now(),
                        'created_at' => $scopeMap->created_at ?? now(),
                    ]
                );

                $access = DB::table('udr_drive_folder_access_m')
                    ->where('kdprofile', (int) $this->kdProfile)
                    ->where('udrrootid', (int) ($scopeFolder->kdisidokumen ?: $scopeFolder->id))
                    ->first();
                if ($access) {
                    DB::table('udr_drive_folder_access_m')->where('id', $access->id)->update([
                        'accessmode' => 'all',
                        'updatedby' => $this->getUserId(),
                        'updated_at' => now(),
                    ]);
                    $accessId = $access->id;
                } else {
                    $accessId = DB::table('udr_drive_folder_access_m')->insertGetId([
                        'kdprofile' => (int) $this->kdProfile,
                        'udrrootid' => (int) ($scopeFolder->kdisidokumen ?: $scopeFolder->id),
                        'accessmode' => 'all',
                        'createdby' => $this->getUserId(),
                        'updatedby' => $this->getUserId(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('udr_drive_folder_access_user_t')->where('folderaccessfk', $accessId)->delete();

                $details = DB::table('temuanketidaksesuaiandetail_t')
                    ->where('auditfk', (int) $audit->id)
                    ->where('statusenabled', true)
                    ->orderBy('urutan')
                    ->orderBy('id')
                    ->get();
                foreach ($details as $index => $detail) {
                    $temuanMap = DB::table('dokumenauditinternalfolder_m')
                        ->where('kdprofile', (int) $this->kdProfile)
                        ->where('tipefolder', 'temuan')
                        ->where('referensifk', (int) $detail->id)
                        ->where('statusenabled', true)
                        ->first();
                    $temuanFolder = $temuanMap
                        ? DB::table('ulabdigitalrepo_m')
                            ->where('id', (int) $temuanMap->udrfolderfk)
                            ->where('jenisudr', 'Audit Internal')
                            ->where('statusenabled', true)
                            ->first()
                        : null;
                    if (!$temuanFolder) {
                        $nomor = (int) ($detail->urutan ?: ($index + 1));
                        $namaTemuan = sprintf(
                            'Temuan %02d - %s - Klausul %s',
                            $nomor,
                            trim((string) $detail->bagian) ?: 'Tanpa Bagian',
                            trim((string) $detail->klausul) ?: '-'
                        );
                        $temuanFolder = $this->findOrCreateAuditInternalFolder(
                            mb_substr($namaTemuan, 0, 180),
                            (int) $scopeFolder->id,
                            $nextId,
                            $nextNourut
                        );
                    }

                    DB::table('dokumenauditinternalfolder_m')->updateOrInsert(
                        [
                            'kdprofile' => (int) $this->kdProfile,
                            'tipefolder' => 'temuan',
                            'referensifk' => (int) $detail->id,
                        ],
                        [
                            'statusenabled' => true,
                            'norec' => $temuanMap->norec ?? (string) $this->Uuid4(),
                            'auditfk' => (int) $audit->id,
                            'temuanfk' => (int) $detail->id,
                            'tahun' => $tahun,
                            'kodejenisaudit' => $audit->kodejenisaudit,
                            'udrfolderfk' => (int) $temuanFolder->id,
                            'updated_at' => now(),
                            'created_at' => $temuanMap->created_at ?? now(),
                        ]
                    );
                    $jumlahTemuan++;
                }

                $jumlahLingkup++;
            }

            DB::commit();
            return $this->respond([
                'jumlahLingkup' => $jumlahLingkup,
                'jumlahTemuan' => $jumlahTemuan,
            ], 200, 'Struktur Dokumen Audit Internal siap');
        } catch (Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal menyiapkan Dokumen Audit Internal');
        }
    }

    public function listNewDriveItems(Request $request)
    {
        try {
            $jenis = $this->normalizeNewDriveJenis($request->get('jenisudr'));
            $trash = filter_var($request->get('trash', false), FILTER_VALIDATE_BOOLEAN);
            $parentId = $request->get('parent_id');
            $parentId = ($parentId === null || $parentId === '') ? null : (int) $parentId;
            $search = trim((string) $request->get('search', ''));

            $parent = null;
            $parentRevisionIds = [];
            if (!$trash && $parentId !== null) {
                $parent = $this->validateNewDriveParent($parentId, $jenis);
                $parentId = (int) $parent->drive_id;
                $parentRevisionIds = $this->newDriveRevisionIds($parent->root_id, false);
            }

            $query = $this->newDriveItemsQuery($jenis, $trash)
                ->select(
                    'odm.id',
                    'latest_drive.root_id',
                    'latest_drive.folder_id',
                    DB::raw($this->newDriveParentExpression('odm') . ' as parent_id'),
                    'odm.namaisidokumen',
                    'odm.isidokumen',
                    'odm.alamaturlform',
                    'odm.keterangan',
                    'odm.revisike',
                    'odm.tglrevisi',
                    'odm.created_at',
                    'odm.updated_at',
                    'odm.nomorindukfk',
                    DB::raw(
                        '(SELECT owner_row.pegawaifk FROM ulabdigitalrepo_m as owner_row '
                        . 'WHERE owner_row.id = latest_drive.root_id LIMIT 1) as owner_id'
                    ),
                    'ni.nomordokumen',
                    'ni.namadokumen',
                    DB::raw("COALESCE(pg.namalengkap, '-') as namapegawai")
                );

            if ($trash) {
                $parentExpression = $this->newDriveParentExpression('odm');
                $query->where(function ($q) use ($parentExpression) {
                    $q->whereRaw($parentExpression . ' IS NULL')
                        ->orWhereNotExists(function ($sub) {
                            $deletedParentExpression = $this->newDriveParentExpression('odm');
                            $sub->select(DB::raw(1))
                                ->from('ulabdigitalrepo_m as deleted_parent')
                                ->whereRaw('deleted_parent.id = ' . $deletedParentExpression)
                                ->where('deleted_parent.statusenabled', false)
                                ->whereRaw('COALESCE(deleted_parent.ishapus, 0) = 1');
                        });
                });
            } elseif ($search === '') {
                $parentExpression = $this->newDriveParentExpression('odm');
                if ($parentId === null) {
                    $query->whereRaw($parentExpression . ' IS NULL');
                } else {
                    $query->whereIn(DB::raw($parentExpression), $parentRevisionIds);
                }
            }

            if ($search !== '') {
                $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';
                $query->where(function ($q) use ($search, $operator) {
                    $q->where('odm.namaisidokumen', $operator, '%' . $search . '%')
                        ->orWhere('odm.keterangan', $operator, '%' . $search . '%')
                        ->orWhere('odm.isidokumen', $operator, '%' . $search . '%')
                        ->orWhere('ni.nomordokumen', $operator, '%' . $search . '%')
                        ->orWhere('ni.namadokumen', $operator, '%' . $search . '%');
                });
            }

            $query->orderByRaw("CASE WHEN latest_drive.folder_id IS NOT NULL THEN 0 ELSE 1 END");
            if ($jenis === 'Audit Internal') {
                $query->orderBy('odm.nourut')->orderBy('odm.id');
            } else {
                $query->orderByRaw('LOWER(odm.namaisidokumen) ASC');
            }

            $rows = $query
                ->limit(1000)
                ->get();

            $items = $this->serializeNewDriveItems($rows);
            if (!$trash && $search === '' && $parent) {
                $attachment = $this->newDriveAttachmentItem($parent->root_id, $parentId);
                if ($attachment) {
                    $items->push($attachment);
                }
            }
            $items = $this->decorateNewDriveAccess($items, $search !== '', $trash);

            return $this->respond([
                'items' => $items,
                'breadcrumbs' => $trash ? [] : $this->newDriveBreadcrumbs($parentId, $jenis),
                'parent_id' => $parentId,
                'search' => $search,
                'trash' => $trash,
                'total_active' => $this->newDriveCount($jenis, false),
                'total_trash' => $this->newDriveCount($jenis, true),
                'can_write' => $this->dapatMengubahNewDrive($jenis, $parentId),
            ], 200, 'Sukses');
        } catch (Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memuat UDS Drive');
        }
    }

    public function listNewDriveFolders(Request $request)
    {
        try {
            $jenis = $this->normalizeNewDriveJenis($request->get('jenisudr'));
            $rows = $this->newDriveItemsQuery($jenis, false)
                ->where(function ($q) {
                    $q->whereNotNull('latest_drive.folder_id')
                        ->orWhere(function ($folder) {
                            $folder->whereNull('odm.isidokumen')
                                ->where(function ($link) {
                                    $link->whereNull('odm.alamaturlform')
                                        ->orWhere('odm.alamaturlform', '');
                                });
                        });
                })
                ->orderByRaw('LOWER(odm.namaisidokumen) ASC')
                ->get([
                    DB::raw('COALESCE(latest_drive.folder_id, odm.id) as id'),
                    'latest_drive.root_id',
                    DB::raw($this->newDriveParentExpression('odm') . ' as parent_id'),
                    'odm.namaisidokumen as name',
                ]);

            $paths = $this->newDriveFolderPaths($jenis, $rows);
            $folders = $rows->filter(function ($row) {
                $folder = $this->resolveNewDriveItem((int) $row->id);
                return $this->newDriveUserCanAccessResolvedItem($folder);
            })->map(function ($row) use ($paths) {

                return [
                    'id' => (int) $row->id,
                    'root_id' => (int) $row->root_id,
                    'parent_id' => $row->parent_id !== null ? (int) $row->parent_id : null,
                    'name' => $row->name,
                    'path' => $paths[(int) $row->root_id] ?? $row->name,
                ];
            })->values();

            return $this->respond(['folders' => $folders], 200, 'Sukses');
        } catch (Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memuat folder');
        }
    }

    public function createNewDriveFolder(Request $request)
    {
        DB::beginTransaction();

        try {
            $jenis = $this->normalizeNewDriveJenis($request->get('jenisudr'));
            $this->assertNewDriveWriteAccess($jenis);

            $name = trim((string) $request->get('name'));
            if ($name === '' || mb_strlen($name) > 180 || preg_match('/[\\\\\/]/', $name)) {
                throw new \Exception('Nama folder wajib diisi, maksimal 180 karakter, dan tidak boleh mengandung / atau \\.');
            }

            $parentId = $request->get('parent_id');
            $parentId = ($parentId === null || $parentId === '') ? null : (int) $parentId;
            if ($jenis === 'Audit Internal' && $parentId === null) {
                throw new \Exception('Folder lingkup Audit Internal dibuat otomatis dari mapping. Buka folder lingkup terlebih dahulu.');
            }
            $this->assertNewDriveWriteAccess($jenis, $parentId);
            $parent = $this->validateNewDriveParent($parentId, $jenis);
            $parentId = $parent ? (int) $parent->drive_id : null;

            $nextNourut = $this->nextNewDriveNourut($jenis);
            $cache = [];
            $folder = $this->findOrCreateNewDriveFolder($name, $parentId, $jenis, $nextNourut, $cache);

            DB::commit();

            return $this->respond([
                'id' => (int) $folder->id,
                'root_id' => (int) ($folder->kdisidokumen ?: $folder->id),
                'name' => $folder->namaisidokumen,
            ], 200, 'Folder berhasil dibuat');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal membuat folder');
        }
    }

    public function createNewDriveCvLink(Request $request)
    {
        DB::beginTransaction();

        try {
            $this->assertNewDriveWriteAccess('Mutu');

            $parentId = $request->get('parent_id');
            if ($parentId === null || $parentId === '') {
                throw new \Exception('Buka folder tujuan terlebih dahulu sebelum memasukkan CV.');
            }

            $parent = $this->validateNewDriveParent((int) $parentId, 'Mutu');
            $parentId = (int) $parent->drive_id;

            $pegawaiId = filter_var($request->get('pegawai_id'), FILTER_VALIDATE_INT);
            if ($pegawaiId === false || (int) $pegawaiId <= 0) {
                throw new \Exception('Pegawai untuk CV wajib dipilih.');
            }

            $pegawai = DB::table('pegawai_m')
                ->where('id', (int) $pegawaiId)
                ->where('statusenabled', true)
                ->first(['id', 'namalengkap']);
            if (!$pegawai) {
                throw new \Exception('Pegawai yang dipilih tidak ditemukan atau sudah tidak aktif.');
            }

            $cvUrl = 'mutu/cetak-cv-pegawai?pdf=true&id=' . (int) $pegawai->id;
            $parentRevisionIds = $this->newDriveRevisionIds($parent->root_id, false);
            $parentExpression = $this->newDriveParentExpression('cv_link');
            $duplicateQuery = DB::table('ulabdigitalrepo_m as cv_link')
                ->where('cv_link.statusenabled', true)
                ->where('cv_link.jenisudr', 'Mutu')
                ->whereIn(DB::raw($parentExpression), $parentRevisionIds)
                ->where(function ($query) use ($pegawai, $cvUrl) {
                    if (Schema::hasColumn('ulabdigitalrepo_m', 'pegawaicvfk')) {
                        $query->where('cv_link.pegawaicvfk', (int) $pegawai->id)
                            ->orWhere('cv_link.alamaturlform', $cvUrl);
                    } else {
                        $query->where('cv_link.alamaturlform', $cvUrl);
                    }
                });

            if ($duplicateQuery->exists()) {
                throw new \Exception('CV pegawai tersebut sudah ada di folder ini.');
            }

            $name = mb_substr(
                'CV - ' . trim((string) $pegawai->namalengkap) . ' (Link Web)',
                0,
                180
            );
            $document = $this->createNewDocumentRecord(
                $this->kdProfile,
                $name,
                null,
                'Link CV pegawai melalui UDS Drive',
                $this->nextNewDriveNourut('Mutu'),
                $parentId,
                'Mutu',
                null,
                null,
                $cvUrl,
                (int) $pegawai->id
            );
            $rootId = (int) ($document->kdisidokumen ?: $document->id);

            $this->logNewDriveActivity(
                $rootId,
                'Mutu',
                'ADD_CV',
                $name,
                $parentId,
                [
                    'pegawai_cv_fk' => (int) $pegawai->id,
                    'pegawai_cv' => $pegawai->namalengkap,
                ]
            );

            DB::commit();

            return $this->respond([
                'id' => (int) $document->id,
                'root_id' => $rootId,
                'name' => $name,
                'pegawai_id' => (int) $pegawai->id,
            ], 200, 'CV berhasil dimasukkan ke folder');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memasukkan CV');
        }
    }

    public function newDriveFolderAccess(Request $request)
    {
        try {
            $folder = $this->resolveNewDriveItem((int) $request->get('id'));
            if (empty($folder->is_folder)) {
                throw new \Exception('Pengaturan akses hanya tersedia untuk folder.');
            }
            if ($folder->jenisudr === 'Audit Internal') {
                throw new \Exception('Folder Dokumen Audit Internal selalu dapat dibaca oleh seluruh pengguna audit.');
            }

            $this->assertNewDriveFolderAccessManagement($folder);
            $access = $this->newDriveFolderAccessMode($folder->root_id);

            $users = DB::table('loginuser_s as lu')
                ->join('pegawai_m as pg', 'pg.id', '=', 'lu.objectpegawaifk')
                ->leftJoin('kelompokuser_s as ku', 'ku.id', '=', 'lu.objectkelompokuserfk')
                ->where('lu.kdprofile', $this->kdProfile)
                ->where('lu.statusenabled', true)
                ->where('pg.statusenabled', true)
                ->orderByRaw('LOWER(pg.namalengkap) ASC')
                ->get([
                    'lu.id',
                    'lu.namauser',
                    'pg.namalengkap',
                    'ku.kelompokuser',
                ])
                ->map(function ($user) {
                    return [
                        'id' => (int) $user->id,
                        'username' => $user->namauser,
                        'name' => $user->namalengkap,
                        'group' => $user->kelompokuser,
                    ];
                })
                ->values();
            $selectedUserIds = array_values(array_intersect(
                $access['user_ids'],
                $users->pluck('id')->all()
            ));

            return $this->respond([
                'folder_id' => (int) $folder->drive_id,
                'root_id' => (int) $folder->root_id,
                'mode' => $access['mode'],
                'selected_user_ids' => $selectedUserIds,
                'users' => $users,
            ], 200, 'Sukses');
        } catch (Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memuat pengaturan akses');
        }
    }

    public function saveNewDriveFolderAccess(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!Schema::hasTable('udr_drive_folder_access_m')
                || !Schema::hasTable('udr_drive_folder_access_user_t')) {
                throw new \Exception('Tabel pengaturan akses belum tersedia. Jalankan migrasi database terlebih dahulu.');
            }

            $folder = $this->resolveNewDriveItem((int) $request->get('id'));
            if (empty($folder->is_folder)) {
                throw new \Exception('Pengaturan akses hanya tersedia untuk folder.');
            }

            $jenis = $this->normalizeNewDriveJenis($folder->jenisudr);
            if ($jenis === 'Audit Internal') {
                throw new \Exception('Folder Dokumen Audit Internal tidak dapat dikunci.');
            }
            $this->assertNewDriveFolderAccessManagement($folder);

            $mode = strtolower(trim((string) $request->get('mode')));
            if (!in_array($mode, ['all', 'selected', 'excluded'], true)) {
                throw new \Exception('Mode akses folder tidak valid.');
            }

            $userIds = $request->get('user_ids', []);
            if (!is_array($userIds) || count($userIds) > 5000) {
                throw new \Exception('Daftar akun pegawai tidak valid.');
            }

            $userIds = collect($userIds)
                ->filter(function ($id) {
                    return filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0;
                })
                ->map(function ($id) {
                    return (int) $id;
                })
                ->unique()
                ->values();

            if ($mode === 'all') {
                $userIds = collect();
            } elseif ($userIds->isEmpty()) {
                throw new \Exception(
                    $mode === 'selected'
                        ? 'Pilih minimal satu akun yang boleh membuka folder.'
                        : 'Pilih minimal satu akun yang dikecualikan dari folder.'
                );
            }

            if ($userIds->isNotEmpty()) {
                $validUserIds = DB::table('loginuser_s as lu')
                    ->join('pegawai_m as pg', 'pg.id', '=', 'lu.objectpegawaifk')
                    ->where('lu.kdprofile', $this->kdProfile)
                    ->where('lu.statusenabled', true)
                    ->where('pg.statusenabled', true)
                    ->whereIn('lu.id', $userIds->all())
                    ->pluck('lu.id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->values();

                if ($validUserIds->count() !== $userIds->count()) {
                    throw new \Exception('Salah satu akun pegawai tidak aktif atau tidak valid.');
                }
                $userIds = $validUserIds;
            }

            $existing = DB::table('udr_drive_folder_access_m')
                ->where('kdprofile', $this->kdProfile)
                ->where('udrrootid', $folder->root_id)
                ->first(['id']);

            if ($existing) {
                $accessId = (int) $existing->id;
                DB::table('udr_drive_folder_access_m')
                    ->where('id', $accessId)
                    ->update([
                        'accessmode' => $mode,
                        'updatedby' => $this->getUserId(),
                        'updated_at' => now(),
                    ]);
            } else {
                $accessId = DB::table('udr_drive_folder_access_m')->insertGetId([
                    'kdprofile' => $this->kdProfile,
                    'udrrootid' => $folder->root_id,
                    'accessmode' => $mode,
                    'createdby' => $this->getUserId(),
                    'updatedby' => $this->getUserId(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('udr_drive_folder_access_user_t')
                ->where('folderaccessfk', $accessId)
                ->delete();

            if ($mode !== 'all' && $userIds->isNotEmpty()) {
                DB::table('udr_drive_folder_access_user_t')->insert(
                    $userIds->map(function ($userId) use ($accessId) {
                        return [
                            'folderaccessfk' => $accessId,
                            'loginuserfk' => $userId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->all()
                );
            }

            $this->logNewDriveActivity(
                $folder->root_id,
                $jenis,
                'ACCESS_UPDATE',
                $folder->namaisidokumen,
                $folder->drive_parent_id,
                ['mode' => $mode, 'account_count' => $userIds->count()]
            );

            DB::commit();
            $this->newDriveFolderAccessCache = [];

            return $this->respond([
                'mode' => $mode,
                'selected_user_ids' => $userIds->all(),
            ], 200, 'Akses folder berhasil disimpan');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal menyimpan akses folder');
        }
    }

    public function checkNewDriveUploadConflicts(Request $request)
    {
        try {
            $jenis = $this->normalizeNewDriveJenis($request->get('jenisudr'));
            $this->assertNewDriveWriteAccess($jenis);

            $parentId = $request->get('parent_id');
            $parentId = ($parentId === null || $parentId === '') ? null : (int) $parentId;
            if ($jenis === 'Audit Internal' && $parentId === null) {
                throw new \Exception('Pilih folder lingkup atau temuan sebelum mengupload dokumen.');
            }
            $this->assertNewDriveWriteAccess($jenis, $parentId);
            $parent = $this->validateNewDriveParent($parentId, $jenis);
            $parentId = $parent ? (int) $parent->drive_id : null;

            $files = $request->get('files', []);
            $relativePaths = $request->get('relative_paths', []);
            if (!is_array($files) || !is_array($relativePaths) || count($files) > 200) {
                throw new \Exception('Daftar file upload tidak valid.');
            }

            $matches = [];
            foreach ($files as $index => $originalName) {
                $originalName = trim((string) $originalName);
                if ($originalName === '' || mb_strlen($originalName) > 255) {
                    throw new \Exception('Nama file upload tidak valid.');
                }

                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $documentName = $this->makeDocumentNameWithoutExtension($originalName);
                $relativePath = $relativePaths[$index] ?? $originalName;
                $relativePath = str_replace('\\', '/', trim((string) $relativePath));
                $segments = array_values(array_filter(explode('/', $relativePath), function ($segment) {
                    return $segment !== '' && $segment !== '.';
                }));

                if (in_array('..', $segments, true)) {
                    throw new \Exception('Path folder upload tidak valid.');
                }

                if (!empty($segments)) {
                    array_pop($segments);
                }

                $targetParentId = $parentId;
                $folderFound = true;
                foreach ($segments as $folderName) {
                    $folderName = trim($folderName);
                    if ($folderName === '' || mb_strlen($folderName) > 180 || preg_match('/[\\\\\/]/', $folderName)) {
                        throw new \Exception('Nama folder pada upload tidak valid.');
                    }

                    $folder = $this->findExistingNewDriveFolder($folderName, $targetParentId, $jenis);
                    if (!$folder) {
                        $folderFound = false;
                        break;
                    }
                    $targetParentId = (int) $folder->id;
                }

                if (!$folderFound) {
                    continue;
                }

                $existing = $this->findExistingNewDriveFile(
                    $documentName,
                    $extension,
                    $targetParentId,
                    $jenis
                );

                if ($existing) {
                    $matches[] = [
                        'index' => (int) $index,
                        'id' => (int) $existing->id,
                        'root_id' => (int) ($existing->kdisidokumen ?: $existing->id),
                        'parent_id' => $targetParentId,
                        'name' => $existing->namaisidokumen,
                        'extension' => $extension,
                        'description' => $existing->keterangan,
                    ];
                }
            }

            return $this->respond(['matches' => $matches], 200, 'Pemeriksaan upload berhasil');
        } catch (Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memeriksa upload');
        }
    }

    public function uploadNewDriveItems(Request $request)
    {
        $movedFiles = [];
        DB::beginTransaction();

        try {
            $jenis = $this->normalizeNewDriveJenis($request->get('jenisudr'));
            $this->assertNewDriveWriteAccess($jenis);

            $parentId = $request->get('parent_id');
            $parentId = ($parentId === null || $parentId === '') ? null : (int) $parentId;
            if ($jenis === 'Audit Internal' && $parentId === null) {
                throw new \Exception('Pilih folder lingkup atau temuan sebelum mengupload dokumen.');
            }
            $this->assertNewDriveWriteAccess($jenis, $parentId);
            $parent = $this->validateNewDriveParent($parentId, $jenis);
            $parentId = $parent ? (int) $parent->drive_id : null;

            $files = $request->file('files');
            if (!is_array($files) || empty($files)) {
                throw new \Exception('Tidak ada file yang dipilih.');
            }

            $relativePaths = $request->get('relative_paths', []);
            if (!is_array($relativePaths)) {
                $relativePaths = [];
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
            $maxSize = 30 * 1024 * 1024;
            $nextNourut = $this->nextNewDriveNourut($jenis);
            $folderCache = [];
            $saved = [];

            foreach ($files as $index => $file) {
                if (!$file || !$file->isValid()) {
                    throw new \Exception('Salah satu file upload tidak valid.');
                }

                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions, true)) {
                    throw new \Exception('File ' . $file->getClientOriginalName() . ' tidak diizinkan.');
                }
                if ($file->getSize() > $maxSize) {
                    throw new \Exception('File ' . $file->getClientOriginalName() . ' melebihi 30 MB.');
                }

                $relativePath = $relativePaths[$index] ?? $file->getClientOriginalName();
                $relativePath = str_replace('\\', '/', trim((string) $relativePath));
                $segments = array_values(array_filter(explode('/', $relativePath), function ($segment) {
                    return $segment !== '' && $segment !== '.';
                }));

                if (in_array('..', $segments, true)) {
                    throw new \Exception('Path folder upload tidak valid.');
                }

                if (!empty($segments)) {
                    array_pop($segments);
                }

                $targetParentId = $parentId;
                foreach ($segments as $folderName) {
                    $folderName = trim($folderName);
                    if ($folderName === '' || mb_strlen($folderName) > 180 || preg_match('/[\\\\\/]/', $folderName)) {
                        throw new \Exception('Nama folder pada upload tidak valid.');
                    }

                    $folder = $this->findOrCreateNewDriveFolder(
                        $folderName,
                        $targetParentId,
                        $jenis,
                        $nextNourut,
                        $folderCache
                    );
                    $targetParentId = (int) $folder->id;
                }

                $originalName = $file->getClientOriginalName();
                $documentName = $this->makeDocumentNameWithoutExtension($originalName);
                $storedName = $this->generateStoredFilename($originalName);
                $file->move(public_path('berkas-mutu'), $storedName);
                $movedFiles[] = public_path('berkas-mutu/' . $storedName);

                $existing = $this->findExistingNewDriveFile(
                    $documentName,
                    $extension,
                    $targetParentId,
                    $jenis
                );

                if ($existing) {
                    throw new \Exception(
                        'File ' . $originalName . ' sudah ada. Upload sebagai revisi dan isi keterangannya.'
                    );
                }

                $document = $this->createNewDocumentRecord(
                    $this->kdProfile,
                    $documentName,
                    $storedName,
                    'Upload melalui UDS Drive',
                    $nextNourut++,
                    $targetParentId,
                    $jenis,
                    null,
                    $targetParentId === null ? 'H' : null
                );
                $rootId = (int) ($document->kdisidokumen ?: $document->id);

                $this->logNewDriveActivity(
                    $rootId,
                    $jenis,
                    'UPLOAD',
                    $documentName,
                    $targetParentId,
                    ['relative_path' => $relativePath, 'extension' => $extension]
                );

                $saved[] = [
                    'id' => (int) $document->id,
                    'root_id' => $rootId,
                    'name' => $documentName,
                    'mode' => 'new',
                ];
            }

            DB::commit();

            return $this->respond([
                'count' => count($saved),
                'items' => $saved,
            ], 200, count($saved) . ' file berhasil diupload');
        } catch (Exception $e) {
            DB::rollBack();

            foreach ($movedFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            return $this->respond(['message' => $e->getMessage()], 400, 'Upload gagal');
        }
    }

    public function replaceNewDriveFile(Request $request)
    {
        $movedFile = null;
        DB::beginTransaction();

        try {
            $item = $this->resolveNewDriveItem((int) $request->get('id'));
            $jenis = $this->normalizeNewDriveJenis($item->jenisudr);
            $this->assertNewDriveWriteAccess($jenis, $item->id);
            $this->assertNewDriveReadAccess($item);

            $currentFile = DB::table('ulabdigitalrepo_m')
                ->where('statusenabled', true)
                ->where(function ($q) use ($item) {
                    $q->where('kdisidokumen', (string) $item->root_id)
                        ->orWhere('id', $item->root_id);
                })
                ->whereNotNull('isidokumen')
                ->whereRaw("TRIM(COALESCE(isidokumen, '')) <> ''")
                ->orderByDesc('id')
                ->first();

            if (!$currentFile) {
                throw new \Exception('Item ini belum memiliki file yang dapat diganti.');
            }

            $file = $request->file('file');
            if (!$file || !$file->isValid()) {
                throw new \Exception('File revisi wajib dipilih dan harus valid.');
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions, true)) {
                throw new \Exception('Format file revisi tidak diizinkan.');
            }
            if ($file->getSize() > 30 * 1024 * 1024) {
                throw new \Exception('File revisi melebihi 30 MB.');
            }

            $storedName = $this->generateStoredFilename($file->getClientOriginalName());
            $file->move(public_path('berkas-mutu'), $storedName);
            $movedFile = public_path('berkas-mutu/' . $storedName);

            $description = trim((string) $request->get('description'));
            if ($description === '') {
                $description = $currentFile->keterangan ?: 'Upload revisi melalui UDS Drive';
            }

            $document = $this->createRevisionDocumentRecord(
                $this->kdProfile,
                (int) $item->root_id,
                $currentFile->namaisidokumen,
                $storedName,
                $description,
                $currentFile->nourut,
                $currentFile->kdrinciandokumenhead,
                $jenis,
                $currentFile->nomorindukfk,
                $currentFile->kodeexternal
            );

            $displayRevision = DB::table('ulabdigitalrepo_m')
                ->where('statusenabled', true)
                ->where(function ($q) use ($item) {
                    $q->where('kdisidokumen', (string) $item->root_id)
                        ->orWhere('id', $item->root_id);
                })
                ->whereNotNull('isidokumen')
                ->whereRaw("TRIM(COALESCE(isidokumen, '')) <> ''")
                ->count();

            DB::commit();

            return $this->respond([
                'id' => (int) $document->id,
                'root_id' => (int) $item->root_id,
                'name' => $document->namaisidokumen,
                'revision' => $displayRevision,
                'extension' => $extension,
            ], 200, 'File berhasil diganti sebagai revisi baru');
        } catch (Exception $e) {
            DB::rollBack();

            if ($movedFile && is_file($movedFile)) {
                @unlink($movedFile);
            }

            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal mengganti file');
        }
    }

    public function newDriveLinkInfo(Request $request)
    {
        try {
            $item = $this->resolveNewDriveItem((int) $request->get('id'));
            $this->assertNewDriveReadAccess($item);
            $link = null;

            if (Schema::hasTable('udr_drive_nomorinduk_link_t')) {
                $link = DB::table('udr_drive_nomorinduk_link_t')
                    ->where('udrrootid', $item->root_id)
                    ->first();
            }

            $nomorIndukFk = $link->nomorindukfk ?? null;
            $targetType = $link->targettype ?? null;

            if (!$nomorIndukFk) {
                $legacy = DB::table('ulabdigitalrepo_m')
                    ->where('statusenabled', true)
                    ->where(function ($q) use ($item) {
                        $q->where('kdisidokumen', (string) $item->root_id)
                            ->orWhere('id', $item->root_id);
                    })
                    ->whereNotNull('nomorindukfk')
                    ->orderByDesc('id')
                    ->first(['nomorindukfk', 'isidokumen', 'alamaturlform']);

                if ($legacy) {
                    $nomorIndukFk = (int) $legacy->nomorindukfk;
                    $targetType = trim((string) $legacy->isidokumen) !== ''
                        || trim((string) $legacy->alamaturlform) !== ''
                        ? 'file'
                        : 'folder';
                }
            }

            $nomorInduk = $nomorIndukFk
                ? DB::table('udr_nomorinduk_m')->where('id', $nomorIndukFk)->first()
                : null;

            return $this->respond([
                'root_id' => (int) $item->root_id,
                'nomorindukfk' => $nomorIndukFk ? (int) $nomorIndukFk : null,
                'target_type' => $targetType,
                'nomor_dokumen' => $nomorInduk->nomordokumen ?? null,
                'nama_dokumen' => $nomorInduk->namadokumen ?? null,
            ], 200, 'Sukses');
        } catch (Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memuat relasi dokumen');
        }
    }

    public function linkNewDriveNomorInduk(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!Schema::hasTable('udr_drive_nomorinduk_link_t')) {
                throw new \Exception('Tabel relasi UDS belum tersedia. Jalankan migration terlebih dahulu.');
            }

            $item = $this->resolveNewDriveItem((int) $request->get('id'));
            $jenis = $this->normalizeNewDriveJenis($item->jenisudr);
            $this->assertNewDriveWriteAccess($jenis, $item->id);
            $this->assertNewDriveReadAccess($item);

            $targetType = strtolower(trim((string) $request->get('target_type')));
            if (!in_array($targetType, ['file', 'folder'], true)) {
                throw new \Exception('Jenis target relasi tidak valid.');
            }
            if ($targetType === 'folder' && !$item->is_folder) {
                throw new \Exception('Item yang dipilih bukan folder.');
            }
            if ($targetType === 'file') {
                $hasFile = DB::table('ulabdigitalrepo_m')
                    ->where('statusenabled', true)
                    ->where(function ($q) use ($item) {
                        $q->where('kdisidokumen', (string) $item->root_id)
                            ->orWhere('id', $item->root_id);
                    })
                    ->whereNotNull('isidokumen')
                    ->whereRaw("TRIM(COALESCE(isidokumen, '')) <> ''")
                    ->exists();

                if (!$hasFile) {
                    throw new \Exception('Item yang dipilih tidak memiliki file.');
                }
            }

            $nomorIndukFk = $request->get('nomorindukfk');
            $nomorIndukFk = ($nomorIndukFk === null || $nomorIndukFk === '')
                ? null
                : (int) $nomorIndukFk;

            $this->lockNomorTransaksi('udr-drive-link-root', [$item->root_id]);
            if ($nomorIndukFk !== null) {
                $this->lockNomorTransaksi('udr-drive-link-nomor', [$nomorIndukFk]);
            }

            if ($nomorIndukFk === null) {
                DB::table('udr_drive_nomorinduk_link_t')
                    ->where('udrrootid', $item->root_id)
                    ->delete();

                DB::table('ulabdigitalrepo_m')
                    ->where(function ($q) use ($item) {
                        $q->where('kdisidokumen', (string) $item->root_id)
                            ->orWhere('id', $item->root_id);
                    })
                    ->update(['nomorindukfk' => null]);

                $this->logNewDriveActivity(
                    $item->root_id,
                    $jenis,
                    'UNLINK_NOMOR_INDUK',
                    $item->namaisidokumen,
                    $item->drive_parent_id,
                    ['target_type' => $targetType]
                );

                DB::commit();
                return $this->respond(['linked' => false], 200, 'Relasi dokumen berhasil dilepas');
            }

            $nomorInduk = DB::table('udr_nomorinduk_m')
                ->where('id', $nomorIndukFk)
                ->where('statusenabled', true)
                ->first();
            if (!$nomorInduk) {
                throw new \Exception('Nomor induk dokumen tidak ditemukan.');
            }

            $mappedElsewhere = DB::table('udr_drive_nomorinduk_link_t')
                ->where('nomorindukfk', $nomorIndukFk)
                ->where('udrrootid', '<>', (int) $item->root_id)
                ->first();
            $legacyRootExpression = $this->newDriveRootExpression('legacy_link');
            $legacyElsewhere = DB::table('ulabdigitalrepo_m as legacy_link')
                ->where('legacy_link.statusenabled', true)
                ->where('legacy_link.nomorindukfk', $nomorIndukFk)
                ->whereRaw($legacyRootExpression . ' <> ?', [(int) $item->root_id])
                ->orderByDesc('legacy_link.id')
                ->first(['legacy_link.namaisidokumen']);

            if ($mappedElsewhere || $legacyElsewhere) {
                $linkedName = $legacyElsewhere->namaisidokumen ?? null;
                throw new \Exception(
                    'Nomor induk ini sudah terhubung ke item UDS lain'
                    . ($linkedName ? ': ' . $linkedName : '')
                    . '. Lepas hubungan lama terlebih dahulu.'
                );
            }

            DB::table('udr_drive_nomorinduk_link_t')
                ->where(function ($q) use ($item, $nomorIndukFk) {
                    $q->where('udrrootid', $item->root_id)
                        ->orWhere('nomorindukfk', $nomorIndukFk);
                })
                ->delete();

            DB::table('ulabdigitalrepo_m')
                ->where('nomorindukfk', $nomorIndukFk)
                ->update(['nomorindukfk' => null]);
            DB::table('ulabdigitalrepo_m')
                ->where(function ($q) use ($item) {
                    $q->where('kdisidokumen', (string) $item->root_id)
                        ->orWhere('id', $item->root_id);
                })
                ->update(['nomorindukfk' => null]);

            DB::table('udr_drive_nomorinduk_link_t')->insert([
                'nomorindukfk' => $nomorIndukFk,
                'udrrootid' => (int) $item->root_id,
                'targettype' => $targetType,
                'pegawaifk' => $this->getPegawaiId(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ulabdigitalrepo_m')
                ->where(function ($q) use ($item) {
                    $q->where('kdisidokumen', (string) $item->root_id)
                        ->orWhere('id', $item->root_id);
                })
                ->update(['nomorindukfk' => $nomorIndukFk]);

            $this->logNewDriveActivity(
                $item->root_id,
                $jenis,
                'LINK_NOMOR_INDUK',
                $item->namaisidokumen,
                $item->drive_parent_id,
                [
                    'target_type' => $targetType,
                    'nomorindukfk' => $nomorIndukFk,
                    'nomor_dokumen' => $nomorInduk->nomordokumen,
                ]
            );

            DB::commit();

            return $this->respond([
                'linked' => true,
                'nomorindukfk' => $nomorIndukFk,
                'target_type' => $targetType,
            ], 200, 'Item berhasil dihubungkan ke Daftar Induk Dokumen');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal menghubungkan dokumen');
        }
    }

    public function renameNewDriveItem(Request $request)
    {
        DB::beginTransaction();

        try {
            $item = $this->resolveNewDriveItem((int) $request->get('id'));
            $jenis = $this->normalizeNewDriveJenis($item->jenisudr);
            $this->assertNewDriveWriteAccess($jenis, $item->id);
            $this->assertNewDriveReadAccess($item);

            $name = trim((string) $request->get('name'));
            if ($name === '' || mb_strlen($name) > 180 || preg_match('/[\\\\\/]/', $name)) {
                throw new \Exception('Nama item tidak valid.');
            }

            DB::table('ulabdigitalrepo_m')
                ->where('statusenabled', true)
                ->where(function ($q) use ($item) {
                    $q->where('kdisidokumen', (string) $item->root_id)
                        ->orWhere('id', $item->root_id);
                })
                ->update([
                    'namaisidokumen' => $name,
                    'updated_at' => now(),
                ]);

            $this->logNewDriveActivity(
                $item->root_id,
                $jenis,
                'RENAME',
                $name,
                $item->drive_parent_id,
                ['old_name' => $item->namaisidokumen]
            );

            DB::commit();
            return $this->respond(['id' => $item->id, 'name' => $name], 200, 'Nama berhasil diubah');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal mengubah nama');
        }
    }

    public function moveNewDriveItem(Request $request)
    {
        DB::beginTransaction();

        try {
            $item = $this->resolveNewDriveItem((int) $request->get('id'));
            $jenis = $this->normalizeNewDriveJenis($item->jenisudr);
            $this->assertNewDriveWriteAccess($jenis, $item->id);
            $this->assertNewDriveReadAccess($item);

            $parentId = $request->get('parent_id');
            $parentId = ($parentId === null || $parentId === '') ? null : (int) $parentId;
            if ($jenis === 'Audit Internal') {
                $this->assertNewDriveWriteAccess($jenis, $parentId ?: $item->id);
            }
            $parent = $this->validateNewDriveParent($parentId, $jenis);

            if ($parent) {
                $treeRoots = $this->collectNewDriveTreeRoots($item, false);
                if (in_array((int) $parent->root_id, $treeRoots, true)) {
                    throw new \Exception('Folder tidak dapat dipindahkan ke dalam dirinya sendiri.');
                }
            }

            DB::table('ulabdigitalrepo_m')
                ->where('statusenabled', true)
                ->where(function ($q) use ($item) {
                    $q->where('kdisidokumen', (string) $item->root_id)
                        ->orWhere('id', $item->root_id);
                })
                ->update([
                    'kdrinciandokumenhead' => $parentId,
                    'kodeexternal' => $parentId === null ? 'H' : null,
                    'updated_at' => now(),
                ]);

            $this->logNewDriveActivity(
                $item->root_id,
                $jenis,
                'MOVE',
                $item->namaisidokumen,
                $parentId,
                ['old_parent_id' => $item->drive_parent_id]
            );

            DB::commit();
            return $this->respond(['id' => $item->id, 'parent_id' => $parentId], 200, 'Item berhasil dipindahkan');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memindahkan item');
        }
    }

    public function trashNewDriveItem(Request $request)
    {
        DB::beginTransaction();

        try {
            $item = $this->resolveNewDriveItem((int) $request->get('id'));
            $jenis = $this->normalizeNewDriveJenis($item->jenisudr);
            $this->assertNewDriveWriteAccess($jenis, $item->id);
            $this->assertNewDriveReadAccess($item);
            $rootIds = $this->collectNewDriveTreeRoots($item, false);

            DB::table('ulabdigitalrepo_m')
                ->where('statusenabled', true)
                ->whereIn(DB::raw($this->newDriveRootExpression('ulabdigitalrepo_m')), $rootIds)
                ->update([
                    'statusenabled' => false,
                    'ishapus' => 1,
                    'updated_at' => now(),
                ]);

            $this->logNewDriveActivity(
                $item->root_id,
                $jenis,
                'TRASH',
                $item->namaisidokumen,
                $item->drive_parent_id,
                ['item_count' => count($rootIds)]
            );

            DB::commit();
            return $this->respond(['count' => count($rootIds)], 200, 'Item dipindahkan ke Sampah');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memindahkan ke Sampah');
        }
    }

    public function restoreNewDriveItem(Request $request)
    {
        DB::beginTransaction();

        try {
            $item = $this->resolveNewDriveItem((int) $request->get('id'), true);
            $jenis = $this->normalizeNewDriveJenis($item->jenisudr);
            $this->assertNewDriveWriteAccess($jenis, $item->id);
            $this->assertNewDriveReadAccess($item);
            $rootIds = $this->collectNewDriveTreeRoots($item, true);

            DB::table('ulabdigitalrepo_m')
                ->where('statusenabled', false)
                ->whereRaw('COALESCE(ishapus, 0) = 1')
                ->whereIn(DB::raw($this->newDriveRootExpression('ulabdigitalrepo_m')), $rootIds)
                ->update([
                    'statusenabled' => true,
                    'ishapus' => 0,
                    'updated_at' => now(),
                ]);

            $this->logNewDriveActivity(
                $item->root_id,
                $jenis,
                'RESTORE',
                $item->namaisidokumen,
                $item->drive_parent_id,
                ['item_count' => count($rootIds)]
            );

            DB::commit();
            return $this->respond(['count' => count($rootIds)], 200, 'Item berhasil dipulihkan');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memulihkan item');
        }
    }

    public function activityNewDriveItem(Request $request)
    {
        try {
            $seed = DB::table('ulabdigitalrepo_m')->where('id', $request->get('id'))->first();
            if (!$seed) {
                throw new \Exception('Item UDS tidak ditemukan.');
            }

            try {
                $accessItem = $this->resolveNewDriveItem((int) $seed->id);
            } catch (Exception $activeException) {
                $accessItem = $this->resolveNewDriveItem((int) $seed->id, true);
            }
            $this->assertNewDriveReadAccess($accessItem);

            $rootId = (int) ($seed->kdisidokumen ?: $seed->id);
            $timeline = [];

            if (Schema::hasTable('udr_drive_activity_t')) {
                $activities = DB::table('udr_drive_activity_t as act')
                    ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'act.pegawaifk')
                    ->where('act.udrrootid', $rootId)
                    ->orderByDesc('act.created_at')
                    ->get([
                        'act.id',
                        'act.aksi',
                        'act.namaitem',
                        'act.metadata',
                        'act.created_at',
                        DB::raw("COALESCE(pg.namalengkap, '-') as namapegawai"),
                    ]);

                foreach ($activities as $activity) {
                    $timeline[] = [
                        'id' => 'activity-' . $activity->id,
                        'action' => $activity->aksi,
                        'name' => $activity->namaitem,
                        'user' => $activity->namapegawai,
                        'date' => $activity->created_at,
                        'metadata' => is_string($activity->metadata)
                            ? json_decode($activity->metadata, true)
                            : $activity->metadata,
                    ];
                }
            }

            $revisions = DB::table('ulabdigitalrepo_m as odm')
                ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'odm.pegawaifk')
                ->where(function ($q) use ($rootId) {
                    $q->where('odm.kdisidokumen', (string) $rootId)
                        ->orWhere('odm.id', $rootId);
                })
                ->orderByDesc('odm.id')
                ->get([
                    'odm.id',
                    'odm.namaisidokumen',
                    'odm.keterangan',
                    'odm.isidokumen',
                    'odm.alamaturlform',
                    'odm.revisike',
                    'odm.statusenabled',
                    'odm.ishapus',
                    'odm.tglrevisi',
                    'odm.updated_at',
                    'odm.created_at',
                    DB::raw("COALESCE(pg.namalengkap, '-') as namapegawai"),
                ]);

            $revisionSequence = [];
            $attachmentRevisions = $revisions
                ->filter(function ($revision) {
                    return trim((string) $revision->isidokumen) !== ''
                        || trim((string) $revision->alamaturlform) !== '';
                })
                ->sort(function ($left, $right) {
                    $revisionCompare = (int) ($left->revisike ?: 1)
                        <=> (int) ($right->revisike ?: 1);

                    return $revisionCompare !== 0
                        ? $revisionCompare
                        : (int) $left->id <=> (int) $right->id;
                });

            $revisionNumber = 1;
            foreach ($attachmentRevisions as $attachmentRevision) {
                $revisionSequence[(int) $attachmentRevision->id] = $revisionNumber++;
            }

            foreach ($revisions as $revision) {
                $hasFile = trim((string) $revision->isidokumen) !== '';
                $hasLink = trim((string) $revision->alamaturlform) !== '';
                $hasAttachment = $hasFile || $hasLink;
                $displayRevision = $hasAttachment
                    ? ($revisionSequence[(int) $revision->id] ?? 1)
                    : null;

                if ((int) $revision->ishapus === 1 && !$revision->statusenabled) {
                    $action = 'TRASH';
                } elseif ($hasAttachment && $displayRevision > 1) {
                    $action = 'REVISION';
                } else {
                    $action = 'CREATE';
                }

                $timeline[] = [
                    'id' => 'revision-' . $revision->id,
                    'action' => $action,
                    'name' => $revision->namaisidokumen,
                    'description' => $revision->keterangan,
                    'user' => $revision->namapegawai,
                    'date' => $revision->tglrevisi ?: ($revision->updated_at ?: $revision->created_at),
                    'metadata' => $displayRevision !== null
                        ? ['revision' => $displayRevision]
                        : [],
                    'revision_id' => (int) $revision->id,
                    'file_name' => $hasFile ? basename((string) $revision->isidokumen) : null,
                    'extension' => $hasFile
                        ? strtolower(pathinfo((string) $revision->isidokumen, PATHINFO_EXTENSION))
                        : ($hasLink ? 'link' : null),
                    'can_open' => $hasAttachment,
                    'can_download' => $hasFile,
                ];
            }

            usort($timeline, function ($a, $b) {
                return strtotime((string) $b['date']) <=> strtotime((string) $a['date']);
            });

            return $this->respond([
                'root_id' => $rootId,
                'name' => $seed->namaisidokumen,
                'timeline' => array_slice($timeline, 0, 100),
            ], 200, 'Sukses');
        } catch (Exception $e) {
            return $this->respond(['message' => $e->getMessage()], 400, 'Gagal memuat aktivitas');
        }
    }

    public function openNewDriveItem(Request $request)
    {
        $item = $this->resolveNewDriveItem((int) $request->get('id'));
        if (!$this->newDriveUserCanAccessResolvedItem($item)) {
            abort(403, 'Anda tidak memiliki akses untuk membuka dokumen ini.');
        }

        if (!empty($item->alamaturlform)) {
            $target = trim((string) $item->alamaturlform);
            if (preg_match('/^https?:\\/\\//i', $target)) {
                return redirect()->away($target);
            }

            return redirect(url('/service/' . ltrim($target, '/')));
        }

        if (empty($item->isidokumen)) {
            abort(404, 'Item ini adalah folder.');
        }

        $filename = basename($item->isidokumen);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filepath = asset('berkas-mutu/' . rawurlencode($filename));
        $data = $item;

        return view('report.mutu.view-pdf', compact('filepath', 'data', 'extension'));
    }

    public function downloadNewDriveItem(Request $request)
    {
        $item = $this->resolveNewDriveItem((int) $request->get('id'));
        if (!$this->newDriveUserCanAccessResolvedItem($item)) {
            abort(403, 'Anda tidak memiliki akses untuk mendownload dokumen ini.');
        }
        if (empty($item->isidokumen)) {
            abort(404, 'File tidak ditemukan.');
        }

        $filename = basename($item->isidokumen);
        $fullPath = public_path('berkas-mutu/' . $filename);
        if (!is_file($fullPath)) {
            abort(404, 'File fisik tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $downloadName = preg_replace('/[^A-Za-z0-9 _.-]/', '', $item->namaisidokumen);
        $downloadName = trim($downloadName) ?: 'dokumen';

        return response()->download($fullPath, $downloadName . ($extension ? '.' . $extension : ''));
    }

    private function resolveNewDriveRevision($id)
    {
        $revision = DB::table('ulabdigitalrepo_m')->where('id', (int) $id)->first();
        if (!$revision) {
            abort(404, 'Revisi dokumen tidak ditemukan.');
        }

        if (empty($revision->isidokumen) && empty($revision->alamaturlform)) {
            abort(404, 'Revisi ini tidak mempunyai file atau tautan.');
        }

        $rootId = (int) ($revision->kdisidokumen ?: $revision->id);
        try {
            $accessItem = $this->resolveNewDriveItem($rootId);
        } catch (Exception $activeException) {
            $accessItem = $this->resolveNewDriveItem($rootId, true);
        }
        if (!$this->newDriveUserCanAccessResolvedItem($accessItem)) {
            abort(403, 'Anda tidak memiliki akses untuk membuka revisi dokumen ini.');
        }

        return $revision;
    }

    public function openNewDriveRevision(Request $request)
    {
        $revision = $this->resolveNewDriveRevision($request->get('id'));

        if (!empty($revision->alamaturlform)) {
            $target = trim((string) $revision->alamaturlform);
            if (preg_match('/^https?:\/\//i', $target)) {
                return redirect()->away($target);
            }

            return redirect(url('/service/' . ltrim($target, '/')));
        }

        $filename = basename((string) $revision->isidokumen);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filepath = asset('berkas-mutu/' . rawurlencode($filename));
        $data = $revision;

        return view('report.mutu.view-pdf', compact('filepath', 'data', 'extension'));
    }

    public function downloadNewDriveRevision(Request $request)
    {
        $revision = $this->resolveNewDriveRevision($request->get('id'));
        if (empty($revision->isidokumen)) {
            abort(404, 'Revisi ini bukan file yang dapat didownload.');
        }

        $filename = basename((string) $revision->isidokumen);
        $fullPath = public_path('berkas-mutu/' . $filename);
        if (!is_file($fullPath)) {
            abort(404, 'File revisi tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $downloadName = preg_replace('/[^A-Za-z0-9 _.-]/', '', $revision->namaisidokumen);
        $downloadName = trim($downloadName) ?: 'dokumen';
        $rootId = (int) ($revision->kdisidokumen ?: $revision->id);
        $revisionIds = DB::table('ulabdigitalrepo_m')
            ->where(function ($q) use ($rootId) {
                $q->where('kdisidokumen', (string) $rootId)
                    ->orWhere('id', $rootId);
            })
            ->where(function ($q) {
                $q->whereRaw("COALESCE(isidokumen, '') <> ''")
                    ->orWhereRaw("COALESCE(alamaturlform, '') <> ''");
            })
            ->orderByRaw('COALESCE(revisike, 1) ASC')
            ->orderBy('id')
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values();
        $revisionIndex = $revisionIds->search((int) $revision->id, true);
        $displayRevision = $revisionIndex === false ? 1 : $revisionIndex + 1;
        $revisionNumber = str_pad((string) $displayRevision, 2, '0', STR_PAD_LEFT);

        return response()->download(
            $fullPath,
            $downloadName . '_Revisi_' . $revisionNumber . ($extension ? '.' . $extension : '')
        );
    }
}
