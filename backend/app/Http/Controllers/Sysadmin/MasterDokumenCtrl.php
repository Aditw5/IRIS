<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Standar\Dokumen;
use App\Models\Standar\MapisiDokumentoRincianDokumen;
use App\Models\Standar\MapObjekModulAplikasiToModulAplikasi;
use App\Models\Standar\ModulAplikasi;
use App\Models\Standar\ObjekIsiDokumen;
use App\Models\Standar\ObjekModulAplikasi;
use App\Traits\Valet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use setasign\Fpdi\Fpdi;

class WatermarkFpdi extends Fpdi
{
    protected $angle = 0;
    protected $extgstates = [];

    // ===== ROTATE =====
    public function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1) {
            $x = $this->w / 2;
        }
        if ($y == -1) {
            $y = $this->h / 2;
        }

        if ($this->angle != 0) {
            $this->_out('Q');
        }

        $this->angle = $angle;

        if ($angle != 0) {
            $angleRad = $angle * M_PI / 180;
            $c = cos($angleRad);
            $s = sin($angleRad);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;

            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm',
                $c,
                $s,
                -$s,
                $c,
                $cx,
                $cy,
                -$cx,
                -$cy
            ));
        }
    }

    public function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    // ===== TRANSPARENSI (ALPHA) =====
    public function SetAlpha($alpha, $bm = 'Normal')
    {
        // alpha 0 = transparan, 1 = solid
        $gs = $this->AddExtGState(['ca' => $alpha, 'CA' => $alpha, 'BM' => '/' . $bm]);
        $this->SetExtGState($gs);
    }

    protected function AddExtGState($parms)
    {
        $n = count($this->extgstates) + 1;
        $this->extgstates[$n]['parms'] = $parms;
        return $n;
    }

    protected function SetExtGState($gs)
    {
        $this->_out(sprintf('/GS%d gs', $gs));
    }

    protected function _putextgstates()
    {
        for ($i = 1; $i <= count($this->extgstates); $i++) {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_out('<</Type /ExtGState');
            $parms = $this->extgstates[$i]['parms'];
            $this->_out(sprintf('/ca %.3F', $parms['ca'])); // non-stroking
            $this->_out(sprintf('/CA %.3F', $parms['CA'])); // stroking
            $this->_out('/BM ' . $parms['BM']);
            $this->_out('>>');
            $this->_out('endobj');
        }
    }

    protected function _putresourcedict()
    {
        parent::_putresourcedict();
        if (!empty($this->extgstates)) {
            $this->_out('/ExtGState <<');
            foreach ($this->extgstates as $k => $extgstate) {
                $this->_out('/GS' . $k . ' ' . $extgstate['n'] . ' 0 R');
            }
            $this->_out('>>');
        }
    }

    protected function _putresources()
    {
        $this->_putextgstates();
        parent::_putresources();
    }

    protected function _enddoc()
    {
        if (!empty($this->extgstates) && $this->PDFVersion < '1.4') {
            $this->PDFVersion = '1.4';
        }
        parent::_enddoc();
    }
}

class MasterDokumenCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterDokumen(Request $r)
    {
        $data  = DB::table('dokumen_m')
            ->select(
                'id',
                'statusenabled',
                'namadokumen',
                'kddokumen',
            )
            ->where('kdprofile', $this->kdProfile)
            ->where('reportdisplay', '=', 'Dokumen');
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('id', '=',  $r['id']);
        }
        if (isset($r['namadokumen']) && $r['namadokumen'] != '') {
            $data = $data->where('namadokumen', 'ilike', '%' . $r['namadokumen'] . '%');
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '' && $r['statusenabled'] == 'true') {
            $data = $data->where('statusenabled', '=', $r['statusenabled']);
        }
        if (isset($r['_total']) && $r['_total'] != '') {
        }
        if (isset($r['offset']) && $r['offset'] != '') {
            $data = $data->offset($r['offset']);
        }


        $data = $data->orderBy('id');
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function saveDokumen(Request $r)
    {
        DB::beginTransaction();
        try {
            $PSN =  $r['namadokumen'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Dokumen(), 'id', $this->kdProfile); //$this->Uuid4();
                $dataPS = new Dokumen();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Dokumen::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->kddokumen = $id;
            $dataPS->kddokumenhead =  $PSN['kddokumenhead'];
            $dataPS->namadokumen =  $PSN['namadokumen'];
            $dataPS->reportdisplay =  $PSN['reportdisplay'];
            $dataPS->statusenabled =  $PSN['statusenabled'];
            $dataPS->save();

            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
        }

        if ($transStatus == 'true') {
            $transMessage = "Sukses";
            DB::commit();

            $result = array(
                "status" => 200,
                "result" => array(
                    "data"  => $dataPS,
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } else {
            $transMessage = "Simpan Gagal";
            DB::rollBack();

            $result = array(
                "status" => 400,
                "result"  => $e->getMessage() . ' ' . $e->getLine()

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function deleteRincianDokumen(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Dokumen::where('id', $r['id'])
                ->update([
                    'statusenabled' => false
                ]);

            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
        }

        if ($transStatus == 'true') {
            $transMessage = "Sukses";
            DB::commit();

            $result = array(
                "status" => 200,
                "result" => array(
                    "data"  => $dataPS,
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } else {
            $transMessage = "Hapus Gagal";
            DB::rollBack();

            $result = array(
                "status" => 400,
                "result"  => null

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function masterDokumenHead(Request $request)
    {
        $data = DB::table('dokumen_m as dm')
            ->where('dm.statusenabled', true)
            ->where('dm.kdprofile', $this->kdProfile)
            ->orderBy('dm.id')
            ->whereNotNull('dm.kddokumenhead');
        if (isset($request['id'])) {
            $data = $data->where('dm.kddokumenhead', $request['id']);
        }
        $data = $data->get();
        return $this->respond($data);
    }

    public function masterIsiDokumen(Request $request)
    {
        $latestSub = DB::table('objekisidokumen_m as o2')
            ->selectRaw('MAX(o2.id) as latest_id')
            ->selectRaw("COALESCE(NULLIF(o2.kdisidokumen,'')::integer, o2.id) as root_id")
            ->where('o2.statusenabled', true)
            ->groupBy(DB::raw("COALESCE(NULLIF(o2.kdisidokumen,'')::integer, o2.id)"));

        $dataRaw = DB::table('objekisidokumen_m as odm')
            ->joinSub($latestSub, 'l', function ($join) {
                $join->on('odm.id', '=', 'l.latest_id');
            })
            ->join('mapisidokumentorinciandokumen_t as acdc', 'acdc.isidokumenfk', '=', 'odm.id')
            ->join('dokumen_m as ma', 'ma.id', '=', 'acdc.rincianfk')
            ->where('odm.statusenabled', true)
            ->where('ma.statusenabled', true)
            ->where('acdc.statusenabled', true)
            ->where('ma.kdprofile', $this->kdProfile)
            ->where('acdc.rincianfk', $request['id'])
            ->select(
                'odm.id as key',
                'odm.kdrinciandokumenhead',
                'odm.namaisidokumen as label',
                'acdc.rincianfk',
                'odm.nourut',
                'odm.kodeexternal',
                'odm.alamaturlform',
                'odm.keterangan',
                'odm.fungsi',
                'odm.isidokumen',
                DB::raw("COALESCE(NULLIF(odm.kdisidokumen,'')::integer, odm.id) as root_id"),
                DB::raw('COALESCE(odm.revisike, 1) as revisike')
            )
            ->orderBy('odm.nourut')
            ->get();
        $dataraw3 = [];
        foreach ($dataRaw as $r) {
            if ($r->kodeexternal == 'H') {
                $dataraw3[] = [
                    'key'        => $r->key,
                    'label'      => $r->label,
                    'data'       => $r,
                    'nourut'     => $r->nourut,
                    'isidokumen' => $r->isidokumen,
                    'parent_id'  => 0,
                    'icon'       => 'pi pi-fw pi-folder',
                ];
            } else {
                $dataraw3[] = [
                    'key'        => $r->key,
                    'label'      => $r->label,
                    'data'       => $r,
                    'nourut'     => $r->nourut,
                    'isidokumen' => $r->isidokumen,
                    'parent_id'  => $r->kdrinciandokumenhead,
                    'icon'       => 'pi pi-fw pi-folder',
                ];
            }
        }

        $data = $dataraw3;

        $res['data'] = $dataRaw;
        $res['tree'] = (function ($data) {
            $elements = [];
            $tree = [];
            foreach ($data as &$el) {
                $id = $el['key'];
                $pid = $el['parent_id'];
                $elements[$id] = &$el;
                if (isset($elements[$pid])) {
                    $elements[$pid]['children'][] = &$el;
                } else {
                    if ($pid <= 10) $tree[] = &$el;
                }
            }
            return $tree;
        })($data);

        return $this->respond($res);
    }

    public function saveIsiDokumenMap(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();

        try {
            $filename = null;
            $old      = null;
            if (!empty($request['id'])) {
                $old = ObjekIsiDokumen::where('id', $request['id'])->first();
                if (!$old) {
                    throw new \Exception("Data lama tidak ditemukan.");
                }
            }
            if ($request->hasFile('fileDokumenMutu')) {
                $file = $request->file('fileDokumenMutu');

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa gambar (jpg, jpeg, png, webp), PDF, Word (doc, docx), atau Excel (xls, xlsx).");
                }

                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move(public_path('berkas-mutu'), $filename);
            } else {
                if ($old) {
                    $filename = $old->isidokumen;
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }
            }
            $rootId = null;
            $namaIsi = trim((string)($request['namaisidokumen'] ?? ''));

            $isHeader = empty($request['kdrinciandokumenhead']) ? 'H' : null;
            $kdrinci  = $request->has('kdrinciandokumenhead') && $request['kdrinciandokumenhead'] !== ''
                ? $request['kdrinciandokumenhead'] : null;
            if (empty($request['id'])) {
                $newID   = $this->SEQUENCE_MASTER(new ObjekIsiDokumen(), 'id', $kdProfile);
                $rootId  = $newID;
                $revisiKe = 1;

                $baru = new ObjekIsiDokumen();
                $baru->id                    = $newID;
                $baru->kdprofile             = $kdProfile;
                $baru->statusenabled         = true;
                $baru->kodeexternal          = $isHeader;
                $baru->norec                 = substr($this->Uuid4(), 0, 32);
                $baru->kdisidokumen          = $rootId;
                $baru->keterangan            = $request['keterangan'] ?? null;
                $baru->namaisidokumen        = $namaIsi ?: null;
                $baru->nourut                = $request['nourut'] ?? null;
                $baru->kdrinciandokumenhead  = $kdrinci;
                $baru->isidokumen            = $filename;

                if (Schema::hasColumn('objekisidokumen_m', 'revisike')) {
                    $baru->revisike = $revisiKe;
                }
                if (Schema::hasColumn('objekisidokumen_m', 'tglrevisi')) {
                    $baru->tglrevisi = now();
                }
                $baru->save();
                $map = new MapisiDokumentoRincianDokumen();
                $map->id            = $this->SEQUENCE_MASTER(new MapisiDokumentoRincianDokumen(), 'id', $kdProfile);
                $map->kdprofile     = $kdProfile;
                $map->statusenabled = true;
                $map->norec         = substr($this->Uuid4(), 0, 32);
                $map->rincianfk     = $request['rincianfk'];
                $map->isidokumenfk  = $newID;
                $map->save();
            } else {
                $fileChanged = $request->hasFile('fileDokumenMutu') && $filename !== $old->isidokumen;
                if ($fileChanged) {
                    $rootId = $old->kdisidokumen ?: $old->id;

                    $revisiKe = DB::table('objekisidokumen_m')
                        ->where('statusenabled', true)
                        ->where(function ($q) use ($rootId) {
                            $q->where('kdisidokumen', $rootId)
                                ->orWhere('id', $rootId);
                        })
                        ->count() + 1;

                    $newID = $this->SEQUENCE_MASTER(new ObjekIsiDokumen(), 'id', $kdProfile);

                    $baru = new ObjekIsiDokumen();
                    $baru->id                    = $newID;
                    $baru->kdprofile             = $kdProfile;
                    $baru->statusenabled         = true;
                    $baru->kodeexternal          = $isHeader;
                    $baru->norec                 = substr($this->Uuid4(), 0, 32);
                    $baru->kdisidokumen          = $rootId;
                    $baru->keterangan            = $request['keterangan'] ?? $old->keterangan;
                    $baru->namaisidokumen        = $namaIsi ?: $old->namaisidokumen;
                    $baru->nourut                = $request['nourut'] ?? $old->nourut;
                    $baru->kdrinciandokumenhead  = $kdrinci ?? $old->kdrinciandokumenhead;
                    $baru->isidokumen            = $filename;

                    if (Schema::hasColumn('objekisidokumen_m', 'revisike')) {
                        $baru->revisike = $revisiKe;
                    }
                    if (Schema::hasColumn('objekisidokumen_m', 'tglrevisi')) {
                        $baru->tglrevisi = now();
                    }
                    $baru->save();
                    $map = new MapisiDokumentoRincianDokumen();
                    $map->id            = $this->SEQUENCE_MASTER(new MapisiDokumentoRincianDokumen(), 'id', $kdProfile);
                    $map->kdprofile     = $kdProfile;
                    $map->statusenabled = true;
                    $map->norec         = substr($this->Uuid4(), 0, 32);
                    $map->rincianfk     = $request['rincianfk'];
                    $map->isidokumenfk  = $newID;
                    $map->save();
                } else {
                    $old->keterangan           = $request['keterangan'] ?? $old->keterangan;
                    $old->namaisidokumen       = $namaIsi !== '' ? $namaIsi : $old->namaisidokumen;
                    $old->nourut               = $request['nourut'] ?? $old->nourut;
                    $old->kdrinciandokumenhead = $kdrinci ?? $old->kdrinciandokumenhead;
                    $old->isidokumen           = $filename ?? $old->isidokumen;
                    $old->save();
                    $map = MapisiDokumentoRincianDokumen::where('isidokumenfk', $old->id)
                        ->where('statusenabled', true)
                        ->first();

                    if ($map) {
                        if ($request->has('rincianfk') && $request['rincianfk'] !== null) {
                            $map->rincianfk = $request['rincianfk'];
                        }
                        $map->save();
                    }
                }
            }

            DB::commit();

            $transMessage = "Sukses";
            $result = [
                "status" => 200,
                "result" => [
                    "as" => 'aditwiran19@gmail.com',
                ],
            ];
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Simpan Gagal";
            $result = [
                "status" => 400,
                "result" => [
                    "as" => 'aditwiran19@gmail.com',
                    "ex" => $e->getMessage() . ' ' . $e->getLine(),
                ],
            ];
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function hapusIsiDokumen(Request $request)
    {
        DB::beginTransaction();
        try {

            ObjekIsiDokumen::where('id', $request['id'])->update([
                'statusenabled' => false
            ]);
            MapisiDokumentoRincianDokumen::where('isidokumenfk', $request['id'])->update([
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
    public function lastNourutObjekModul(Request $r)
    {

        $dataPS = ObjekModulAplikasi::where('statusenabled', true)->orderByDesc('nourut')->first();

        return $this->respond($dataPS);
    }

    public function cetakDokumenMutu(Request $request)
    {
        $data = DB::table('objekisidokumen_m')
            ->where('id', $request['id'])
            ->first();

        if (!$data || !$data->isidokumen) {
            abort(404, 'Data Dokumen atau file tidak ditemukan');
        }

        $filename = basename($data->isidokumen);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, ['doc', 'docx'])) {
            $filepath = $this->publicFileUrl('berkas-mutu', $filename);
        } else {
            $filepath = $this->publicFileUrl('berkas-mutu', $filename);
        }

        return view('report.mutu.view-pdf', compact('filepath', 'data', 'extension'));
    }

    public function previewExcel($id)
    {
        $data = DB::table('objekisidokumen_m')->where('id', $id)->first();
        if (! $data || ! $data->isidokumen) {
            abort(404, 'File tidak ditemukan');
        }

        $filename = basename($data->isidokumen);
        $fullPath = public_path('berkas-mutu/' . $filename);
        if (! file_exists($fullPath)) {
            abort(404, "File Excel tidak ditemukan di path: $fullPath");
        }

        $spreadsheet = IOFactory::load($fullPath);
        $writer     = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);

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

    public function riwayatIsiDokumen(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return $this->respond(['message' => 'id diperlukan'], 400, 'Bad Request');
        }

        $row = DB::table('objekisidokumen_m')->where('id', $id)->first();
        if (!$row) {
            return $this->respond(['message' => 'Data tidak ditemukan'], 404, 'Not Found');
        }

        $root = $row->kdisidokumen ?: $row->id;

        $list = DB::table('objekisidokumen_m')
            ->where('statusenabled', true)
            ->where(function ($q) use ($root) {
                $q->where('kdisidokumen', $root)
                    ->orWhere('id', $root);
            })
            ->orderByRaw('COALESCE(revisike,1) ASC, id ASC')
            ->get([
                'id',
                'namaisidokumen',
                'isidokumen',
                DB::raw('COALESCE(revisike, 1) AS revisike'),
                'tglrevisi',
            ]);

        return $this->respond([
            'root_id' => $root,
            'riwayat' => $list,
        ], 200, 'Sukses');
    }

    public function isiDokumenViewer(Request $request)
    {
        $data = DB::table('objekisidokumen_m as odm')
            ->select(
                'odm.id',
                'odm.namaisidokumen',
                'odm.revisike',
                'odm.tglrevisi',
                'odm.dilihat',
            )
            ->whereNotNull('odm.isidokumen')
            ->where('odm.statusenabled', true);

        if (isset($request['search']) && $request['search'] != '') {
            $searchTerm = '%' . $request['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('odm.namaisidokumen', 'ilike', $searchTerm);
            });
        }
        $page = 1;
        if (isset($request['page']) && $request['page'] != '') {
            $page = $request['page'];
        }

        $data = $data->orderBy('odm.namaisidokumen');
        $data = $data->paginate(isset($request['limit']) ? $request['limit'] : 10, ['*'], 'page', $page);

        return $this->respond($data);
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

    protected function generateWatermarkPdf(string $sourcePath, string $outputPath, string $watermarkText = 'SALINAN'): void
    {
        if (!file_exists($sourcePath)) {
            Log::warning('generateWatermarkPdf: source not found', [
                'source' => $sourcePath,
            ]);
            return;
        }

        // pastikan folder output ada
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0755, true);
        }

        // pakai class yang sudah ada Rotate + Alpha
        $pdf = new WatermarkFpdi();

        try {
            $pageCount = $pdf->setSourceFile($sourcePath);
        } catch (\Throwable $e) {
            Log::error('generateWatermarkPdf: setSourceFile failed', [
                'source' => $sourcePath,
                'error'  => $e->getMessage(),
            ]);
            return;
        }

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $size  = $pdf->getTemplateSize($tplId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

            // gambar halaman asli
            $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

            // ===== WATERMARK =====
            // transparansi: makin kecil makin "di belakang"
            $pdf->SetAlpha(0.15);             // coba 0.10–0.20 sesuai selera
            $pdf->SetFont('Arial', 'B', 60);
            $pdf->SetTextColor(80, 80, 80);   // agak hitam

            $centerX = $size['width'] / 2;
            $centerY = $size['height'] / 2;

            $pdf->Rotate(45, $centerX, $centerY);  // miring

            $pdf->SetXY(0, $centerY);
            $pdf->Cell($size['width'], 0, $watermarkText, 0, 0, 'C');

            // reset supaya tidak ngaruh ke halaman berikutnya
            $pdf->Rotate(0);
            $pdf->SetAlpha(1);
        }

        $pdf->Output('F', $outputPath);

        Log::info('generateWatermarkPdf: watermark created', [
            'source' => $sourcePath,
            'output' => $outputPath,
        ]);
    }

    protected function buildFilePath(?string $isidokumen): array
    {
        if (!$isidokumen) {
            return [null, null];
        }

        $filename  = basename($isidokumen);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $basePath = public_path('berkas-mutu');

        if (!is_dir($basePath)) {
            @mkdir($basePath, 0755, true);
        }

        if ($extension === 'pdf') {
            $sourcePath = $basePath . DIRECTORY_SEPARATOR . $filename;

            if (!file_exists($sourcePath)) {
                Log::warning('buildFilePath: pdf source missing', [
                    'sourcePath' => $sourcePath,
                    'isidokumen' => $isidokumen,
                ]);

                // fallback ke file asli (kalau memang masih mau ditampilkan)
                return [$this->publicFileUrl('berkas-mutu', $filename), $extension];
            }

            $watermarkName = pathinfo($filename, PATHINFO_FILENAME) . '_salinan.pdf';
            $watermarkPath = $basePath . DIRECTORY_SEPARATOR . $watermarkName;

            if (!file_exists($watermarkPath)) {
                try {
                    $this->generateWatermarkPdf($sourcePath, $watermarkPath, 'SALINAN');
                } catch (\Throwable $e) {
                    Log::error('buildFilePath: generateWatermarkPdf failed', [
                        'error' => $e->getMessage(),
                    ]);

                    // kalau gagal, jangan bikin error di user – pakai file asli
                    return [$this->publicFileUrl('berkas-mutu', $filename), $extension];
                }
            }

            $filepath = $this->publicFileUrl('berkas-mutu', $watermarkName);
        } else {
            // selain pdf, langsung file asli
            $filepath = $this->publicFileUrl('berkas-mutu', $filename);
        }

        Log::info('buildFilePath resolved', [
            'isidokumen' => $isidokumen,
            'filename'   => $filename,
            'extension'  => $extension,
            'filepath'   => $filepath,
        ]);

        return [$filepath, $extension];
    }


    public function viewerIsiDokumenDetail(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return response()->json([
                'message' => 'Parameter id wajib diisi',
            ], 422);
        }

        $data = DB::table('objekisidokumen_m as o')
            ->leftJoin('mapisidokumentorinciandokumen_t as acdc', 'acdc.isidokumenfk', '=', 'o.id')
            ->leftJoin('dokumen_m as d', 'd.id', '=', 'acdc.rincianfk')
            ->select(
                'o.id',
                'o.namaisidokumen',
                'o.isidokumen',
                'o.revisike',
                'o.dilihat',
                'o.created_at',
                'o.tglrevisi',
                'o.created_at',
                'o.updated_at',
            )
            ->where('o.id', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'message' => 'Data dokumen tidak ditemukan',
            ], 404);
        }

        [$filepath, $extension] = $this->buildFilePath($data->isidokumen);


        $result = [
            'id'             => $data->id,
            'namaisidokumen' => $data->namaisidokumen,
            'nomordokumen'   => $data->nomordokumen ?? null,
            'revisike'       => (int)($data->revisike ?? 1),
            'tglrevisi'      => $data->tglrevisi ?? $data->tanggal,
            'tglupload'      => $data->created_at ?? null,
            'kategori'       => $data->kategori ?? null,
            'statusdokumen'  => $data->statusdokumen ?? 'Aktif',
            'dilihat'        => (int)($data->dilihat ?? 0),
            'pembuat'        => $data->pembuat ?? null,
            'file_url'       => $filepath,
            'extension'      => $extension,
            'created_at'     => $data->created_at,
            'updated_at'     => $data->updated_at,
        ];

        return $this->respond($result);
    }
}
