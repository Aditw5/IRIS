<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\AlatUnit;
use App\Services\ImageOptimizerService;
use App\Traits\Valet;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterProdukCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function masterProduk(Request $r, ImageOptimizerService $imageOptimizer)
    {

        $data = DB::table('mapunittoalat_m as mmp')
            ->leftJoin(DB::raw('mitra_m as mt'), DB::raw('CAST(mt.id AS INTEGER)'), '=', 'mmp.objectmitrafk')
            ->select(
                'mmp.id',
                'mmp.namaproduk',
                'mmp.namatipe',
                'mmp.namamerk',
                'mmp.namaserialnumber',
                'mmp.fotoproduk',
                'mmp.objectmitrafk',
                'mt.namaperusahaan',
                'mt.id as idunit',
            )
            ->where('mmp.statusenabled', true);

        $count = 0;
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('mmp.id', '=',  $r['id']);
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
            $data = $data->where('mmp.statusenabled', '=',  $r['statusenabled']);
        }
        if (isset($r['unitfk']) && $r['unitfk'] != '') {
            $data = $data->where('mt.id', '=',  $r['unitfk']);
        }
        if (isset($r['namaproduk']) && $r['namaproduk'] != '') {
            $searchTerm = '%' . $r['namaproduk'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mmp.namaproduk', 'ilike', $searchTerm)
                    ->orWhere('mmp.namamerk', 'ilike', $searchTerm)
                    ->orWhere('mmp.namaserialnumber', 'ilike', $searchTerm)
                    ->orWhere('mmp.namatipe', 'ilike', $searchTerm)
                    ->orWhere('mmp.id', 'ilike', $searchTerm);
            });
        }
        $count = $data->count();
        if (isset($r['_total']) && $r['_total'] != '') {
        }
        if (isset($r['offset']) && $r['offset'] != '') {
            $data = $data->offset($r['offset']);
        }
        if (isset($r['limit']) && $r['limit'] != '') {
            $data = $data->limit($r['limit']);
        }

        $data = $data->orderByDesc('mmp.created_at');
        $data = $data->get();

        $data->each(function ($item) use ($imageOptimizer) {
            $item->fotoproduk_thumbnail = $imageOptimizer->thumbnailFilename($item->fotoproduk);
        });

        $res['data'] = $data;
        $res['count'] = $count;
        return $this->respond($res);
    }

    public function produkByID(Request $r)
    {
        $data  = DB::table('mapunittoalat_m as mmp')
            ->leftJoin(DB::raw('mitra_m as mt'), DB::raw('CAST(mt.id AS INTEGER)'), '=', 'mmp.objectmitrafk')
            ->select(
                'mmp.id',
                'mmp.namaproduk',
                'mmp.namatipe',
                'mmp.namamerk',
                'mmp.namaserialnumber',
                'mmp.fotoproduk',
                'mmp.objectmitrafk',
                'mmp.statusenabled',
                'mt.namaperusahaan',
            )
            ->where('mmp.statusenabled', true);
        if (isset($r['id']) && $r['id'] != '' && $r['id'] != 'undefined') {
            $data = $data->where('mmp.id', $r['id']);
        }
        $result = array(
            'produk' => $data,
            'as' => '@aditwiran19@gmail.com',
        );
        return $this->respond($result);
    }

    public function saveProduk(Request $r, ImageOptimizerService $imageOptimizer)
    {
        $serialNormalized = Str::upper(trim((string) $r->input('namaserialnumber')));
        if ($serialNormalized === '') {
            return $this->respond(['message' => 'Serial Number wajib diisi.'], 422, 'Serial Number wajib diisi.');
        }

        $duplicateMessage = sprintf('Alat dengan Serial Number "%s" sudah ada.', $serialNormalized);
        $uploadedFilePaths = [];

        DB::beginTransaction();
        try {
            $currentId = trim((string) $r->input('id'));
            $statusEnabled = filter_var($r->input('statusenabled', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($statusEnabled === null) {
                $statusEnabled = true;
            }

            $dataPS = null;
            if ($currentId !== '') {
                $dataPS = AlatUnit::where('id', $currentId)->lockForUpdate()->first();
                if (!$dataPS) {
                    throw new \Exception('Data alat tidak ditemukan.');
                }
            }

            $mustCheckSerial = !$dataPS || ($statusEnabled && (
                !$dataPS->statusenabled ||
                Str::upper(trim((string) $dataPS->namaserialnumber)) !== $serialNormalized
            ));

            if ($mustCheckSerial) {
                DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', [
                    'mapunittoalat-sn:' . $serialNormalized,
                ]);

                $exists = AlatUnit::where('statusenabled', true)
                    ->whereRaw('UPPER(TRIM(namaserialnumber)) = ?', [$serialNormalized])
                    ->when($currentId !== '', fn($query) => $query->where('id', '!=', $currentId))
                    ->exists();

                if ($exists) {
                    DB::rollBack();
                    return $this->respond(['message' => $duplicateMessage], 409, $duplicateMessage);
                }
            }

            $filename = null;
            if ($r->hasFile('fileMitra')) {
                $file = $r->file('fileMitra');
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa gambar (jpg, jpeg, atau png).");
                }
                $storedImage = $imageOptimizer->storeToolImage($file);
                $filename = $storedImage['filename'];
                $uploadedFilePaths = $storedImage['paths'];
            } else {
                $filename = $r['namaFileLama'] ?? null;
            }

            if (!$dataPS) {
                $id = $this->SEQUENCE_MASTER(new AlatUnit(), 'id', $this->kdProfile);
                $dataPS = new AlatUnit();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
            } else {
                $id = $dataPS->id;
            }
            $dataPS->namaproduk = $r['namaproduk'] ? strtoupper($r['namaproduk']) : null;
            $dataPS->namamerk = $r['namamerk'] ? strtoupper($r['namamerk']) : null;
            $dataPS->namatipe = $r['namatipe'] ? strtoupper($r['namatipe']) : null;
            $dataPS->namaserialnumber = $serialNormalized;
            $dataPS->objectmitrafk =  $r['mitrafk'];
            $dataPS->statusenabled = $statusEnabled;
            $dataPS->fotoproduk =  $filename;

            $dataPS->save();
            DB::commit();

            return $this->respond([
                'data' => $dataPS,
                'as' => '@aditwiran19@gmail.com',
            ], 200, 'Sukses');
        } catch (QueryException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($uploadedFilePaths as $uploadedFilePath) {
                if (is_file($uploadedFilePath)) {
                    @unlink($uploadedFilePath);
                }
            }

            if ((string) $e->getCode() === '23505' || ($e->errorInfo[0] ?? null) === '23505') {
                return $this->respond(['message' => $duplicateMessage], 409, $duplicateMessage);
            }

            return $this->respond($e->getMessage(), 400, 'Simpan Gagal');
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($uploadedFilePaths as $uploadedFilePath) {
                if (is_file($uploadedFilePath)) {
                    @unlink($uploadedFilePath);
                }
            }

            return $this->respond($e->getMessage(), 400, 'Simpan Gagal');
        }
    }

    public function deleteProduk(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = AlatUnit::where('id', $r['id'])
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
                    "as" => '@aditwiran19@gmail.com',
                ),
            );
        } else {
            $transMessage = "Hapus Gagal";
            DB::rollBack();

            $result = array(
                "status" => 400,
                "result"  => $e->getMessage()

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }
}
