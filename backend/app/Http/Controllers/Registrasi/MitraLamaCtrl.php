<?php

namespace App\Http\Controllers\Registrasi;

use App\Http\Controllers\Controller;
use App\Models\Master\Mitra;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception\InvalidOrderException;

class MitraLamaCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function mitraLama(Request $r)
    {
        $data  = DB::table('mitra_m as mt')
            ->select(
                'mt.id',
                'mt.namaperusahaan',
                'mt.nohp',
                'mt.tgldaftar',
                'mt.progress',
                'mt.isfoto',
                'mt.filename',
                'mt.alamatktr',
            )
            ->where('mt.statusenabled', true)
            ->where('mt.islab', null);
            
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('ps.id', '=',  $r['id']);
        }
         $filter = false;
        if (isset($r['namaperusahaan']) && $r['namaperusahaan'] != '') {
            $filter = true;
            $data = $data->where('mt.namaperusahaan', 'ilike', '%' . $r['namaperusahaan'] . '%');

        }
        if (isset($r['tgldaftar']) && $r['tgldaftar'] != '') {
            $filter = true;
            $data = $data->where('mt.tgldaftar', '=',  $r['tgldaftar']);
        }
        $page = 1;
        if (isset($r['page']) && $r['page'] != '' && $filter == false) {
            $page = $r['page'];
        }

        $data = $data->orderByDesc('mt.namaperusahaan');
        $data = $data ->paginate(isset($r['limit'])?$r['limit']: 10, ['*'], 'page', $page);

        foreach ($data as $d) {
            $d->foto = null;

            $d->progress = $d->progress == null ? 0 : (int)  $d->progress;
            if ((int)  $d->progress <= 50) {
                $d->class_proggress = 'danger';
            }
            if ((int)  $d->progress > 50 && (int)  $d->progress <= 80) {
                $d->class_proggress = 'warning';
            }
            if ((int)  $d->progress > 80) {
                $d->class_proggress = 'success';
            }
            if ($d->isfoto) {
                $path = 'foto_pasien/' . $d->id . '/' . $d->filename;
                $disk = Storage::disk('public');

                if (!$disk->exists($path)) {
                    $d->foto = null;
                } else {
                    $file = $disk->read($path);

                    $b64Doc = chunk_split(base64_encode($file));
                    $sourcePdf = "data:application/pdf;base64," . $b64Doc;

                    $d->foto  =  $sourcePdf;
                }
            }
        }

        return $this->respond($data);
    }

    public function deleteMitra(Request $r)
    {
        DB::beginTransaction();
        try {
            $dataPS = Mitra::where('id', $r['id'])->update(['statusenabled' => false]);
            DB::commit();

            $result = [
                "statusCode" => 201,
                "message" => "Hapus Data Berhasil",
                "result" => [
                    "data"  => $dataPS,
                    "as" => 'aditwiran19@gmail.com',
                ],
            ];
        } catch (InvalidOrderException $e) {
            DB::rollBack();
            $result = array(
                "statusCode" => $e->getCode(),
                "message" => "Something Want Wrong",
                "result"  => $e->getMessage()

            );
        }

        return $this->respond($result['result'], $result['statusCode'], $result['message']);
    }
  
}
