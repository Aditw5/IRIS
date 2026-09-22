<?php

namespace App\Http\Controllers\Registrasi;

use App\Http\Controllers\Controller;
use App\Models\Master\DesaKelurahan;
use App\Models\Master\Kecamatan;
use App\Models\Master\KotaKabupaten;
use App\Models\Master\Negara;
use App\Models\Master\Mitra;
use App\Models\Master\Provinsi;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MitraBaruCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function listDesaKelurahanPaging(Request $req)
    {

        if (isset($req['namadesakelurahan'])) {
            $explode = explode(',', $req['namadesakelurahan']);
            if (count($explode) > 1) {
                $namaDesa = $explode[0];
                $namaKec = $explode[1];
            }
        }
        $Desa = DB::table('desakelurahan_m as ds')
            ->join('kecamatan_m as kc', 'ds.objectkecamatanfk', '=', 'kc.id')
            ->join('kotakabupaten_m as kk', 'ds.objectkotakabupatenfk', '=', 'kk.id')
            ->join('propinsi_m as pp', 'ds.objectpropinsifk', '=', 'pp.id')
            ->select(DB::raw("ds.id
                             ,UPPER(ds.namadesakelurahan) as namadesakelurahan
                             ,ds.kodepos
                             ,ds.objectkecamatanfk
                             ,ds.objectkotakabupatenfk
                             ,ds.objectpropinsifk
                             ,UPPER(kc.namakecamatan) as namakecamatan
                             ,UPPER(kk.namakotakabupaten) as namakotakabupaten
                             ,UPPER( pp.namapropinsi) as namapropinsi"))
            ->where('ds.statusenabled', true)
            ->where('kc.statusenabled', true)
            ->where('kk.statusenabled', true)
            ->where('pp.statusenabled', true)
            ->where('ds.kdprofile', $this->kdProfile)
            ->orderBy('ds.namadesakelurahan');
        if (
            isset($req['namakecamatan']) &&
            $req['namakecamatan'] != "" &&
            $req['namakecamatan'] != "undefined"
        ) {
            $Desa = $Desa->where('kc.namakecamatan', 'ilike', '%' . $req['namakecamatan'] . '%');
        };
        if (
            isset($req['iddesakelurahan']) &&
            $req['iddesakelurahan'] != "" &&
            $req['iddesakelurahan'] != "undefined"
        ) {
            $Desa = $Desa->where('ds.id', $req['iddesakelurahan']);
        };
        if (
            isset($req['namadesakelurahan']) &&
            $req['namadesakelurahan'] != "" &&
            $req['namadesakelurahan'] != "undefined"
        ) {
            if (isset($namaDesa) && isset($namaKec) && $namaDesa != '' && $namaKec != '') {
                $Desa = $Desa
                    ->where('ds.namadesakelurahan', 'ilike', '%' . $namaDesa . '%')
                    ->where('kc.namakecamatan', 'ilike', '%' . $namaKec . '%');
            }
            if (isset($namaDesa) && !isset($namaKec)) {
                $Desa = $Desa
                    ->where('ds.namadesakelurahan', 'ilike', '%' . $namaDesa . '%');
            }
            if (!isset($namaDesa) && !isset($namaKec)) {
                $Desa = $Desa
                    ->where('ds.namadesakelurahan', 'ilike', '%' . $req['namadesakelurahan'] . '%');
            }
        }

        $Desa = $Desa->take(10);
        $Desa = $Desa->get();
        $tempDesa = [];
        if (count($Desa) != 0) {
            foreach ($Desa as $item) {
                $tempDesa[] = array(
                    'id' => $item->id,
                    'namadesakelurahan' => $item->namadesakelurahan,
                    'kodepos' => $item->kodepos,
                    'namakecamatan' => $item->namakecamatan,
                    'namakotakabupaten' => $item->namakotakabupaten,
                    'namapropinsi' => $item->namapropinsi,
                    'desa' => $item->namadesakelurahan . ', ' . $item->namakecamatan . ',  ' . $item->namakotakabupaten . ', ' .
                        $item->namapropinsi,
                    'objectkecamatanfk' => $item->objectkecamatanfk,
                    'objectkotakabupatenfk' => $item->objectkotakabupatenfk,
                    'objectpropinsifk' => $item->objectpropinsifk,
                );
            }
        }
        return $this->respond($tempDesa);
    }

    public function listKecamatanPaging(Request $req)
    {
        $kecamatan = DB::table('kecamatan_m as ru')
            ->select(DB::raw("ru.id, UPPER(ru.namakecamatan) as namakecamatan"))
            ->orderBy('ru.namakecamatan');

        if (
            isset($req['namakecamatan']) &&
            $req['namakecamatan'] != "" &&
            $req['namakecamatan'] != "undefined"
        ) {
            $kecamatan = $kecamatan->where('ru.namakecamatan', 'ilike', '%' .  $req['namakecamatan'] . '%');
        };
        $kecamatan = $kecamatan->take(10);
        $kecamatan = $kecamatan->get();
        return $this->respond($kecamatan);
    }

    public function listDropdown(Request $r)
    {
        $res['negara'] = Negara::mine()->get();
        $res['provinsi'] = Provinsi::mine()->orderBy('namapropinsi')->get();
        return $this->respond($res);
    }

    public function listKotaKab(Request $r)
    {
        $res['kotakabupaten'] = KotaKabupaten::mine();
        if (isset($r['provfk']) && $r['provfk'] != '') {
            $res['kotakabupaten'] = $res['kotakabupaten']->where('objectpropinsifk', $r['provfk']);
        }
        $res['kotakabupaten'] = $res['kotakabupaten']
            ->orderby('namakotakabupaten')
            ->get();
        return $this->respond($res);
    }
    public function listKecamatan(Request $r)
    {
        $res['kecamatan'] = Kecamatan::mine();
        if (isset($r['kotafk']) && $r['kotafk'] != '') {
            $res['kecamatan'] = $res['kecamatan']->where('objectkotakabupatenfk', $r['kotafk']);
        }
        $res['kecamatan'] = $res['kecamatan']
            ->orderby('namakecamatan')
            ->get();
        return $this->respond($res);
    }
    public function listDesa(Request $r)
    {
        $res['desa'] = DesaKelurahan::mine();
        if (isset($r['kecfk']) && $r['kecfk'] != '') {
            $res['desa'] = $res['desa']->where('objectkecamatanfk', $r['kecfk']);
        }
        $res['desa'] = $res['desa']
            ->orderby('namadesakelurahan')
            ->get();
        return $this->respond($res);
    }
    public function saveMitra(Request $r)
    {
        DB::beginTransaction();
        try {
            $PSN =  $r['pasien'];
            $ALM =  $r['alamat'];
            if ($PSN['id'] == '') {
                $dataPS = new Mitra();
                $dataPS->id = $this->SEQUENCE_MASTER(new Mitra(), 'id', $this->kdProfile);
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Mitra::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
            }
            $dataPS->namaperusahaan = $PSN['namaperusahaan'];
            $dataPS->tgldaftar = date('Y-m-d H:i:s');
            $dataPS->objectnegarafk = $PSN['objectnegarafk'];
            $dataPS->nohp = $PSN['nohp'];
            $dataPS->alamatktr = isset($PSN['alamat']) ? $PSN['alamat'] : null;
            $dataPS->foto = isset($PSN['foto']) ? $PSN['foto'] : null;
            $dataPS->email = isset($PSN['email']) ? $PSN['email'] : null;
            $dataPS->progress = isset($PSN['progress']) ? (int) $PSN['progress'] : 0;
            $dataPS->rtrw = $ALM['rtrw'];
            $dataPS->objectdesakelurahanfk = $ALM['objectdesakelurahanfk'];
            $dataPS->objectkecamatanfk = $ALM['objectkecamatanfk'];
            $dataPS->kodepos = $ALM['kodepos'];
            $dataPS->objectkotakabupatenfk = $ALM['objectkotakabupatenfk'];
            $dataPS->objectpropinsifk = $ALM['objectpropinsifk'];
            $dataPS->save();

            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
        }

        if ($transStatus == 'true') {
            $transMessage = "Sukses Simpan Mitra";
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
                "result"  => array(
                    "e"  => $e->getLine() . ' ' . $e->getMessage(),
                ),

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }
    public function mitraByID(Request $r)
    {
        $data = DB::table('mitra_m as mt')
            ->leftjoin('desakelurahan_m as dsk', 'dsk.id', '=', 'mt.objectdesakelurahanfk')
            ->leftjoin('kecamatan_m as kcm', 'kcm.id', '=', 'mt.objectkecamatanfk')
            ->leftjoin('kotakabupaten_m as kkb', 'kkb.id', '=', 'mt.objectkotakabupatenfk')
            ->leftjoin('propinsi_m as prp', 'prp.id', '=', 'mt.objectpropinsifk')
            ->select(
                'mt.id as mitrafk',
                'mt.namaperusahaan',
                'mt.alamatktr',
                'mt.nohp',
                'mt.email',
                'mt.rtrw',
                'dsk.namadesakelurahan',
                'mt.objectkecamatanfk',
                'kcm.namakecamatan',
                'mt.objectkotakabupatenfk',
                'kkb.namakotakabupaten',
                'mt.objectpropinsifk',
                'prp.namapropinsi',
            )
            ->where('mt.statusenabled', true);

        if (isset($r['id']) && $r['id'] != '' && $r['id'] != 'undefined') {
            $data = $data->where('mt.id', $r['id']);
        }
        $data = $data->first();

        $result = array(
            'data' => $data,
            'as' => '@adit',
        );
        return $this->respond($result);
    }

    public function riwayatRegistrasi(Request $r)
    {
        $data = DB::table('mitra_m as mt')
            ->leftjoin('mitraregistrasi_t as mtr', 'mtr.nomitrafk', '=', 'mt.id')
            ->select(DB::raw("mtr.tglregistrasi,
                mt.namaperusahaan,
                mtr.jenisorder,
                mtr.nopendaftaran,
                mtr.norec,
                mt.id
                "))
            ->where('mt.statusenabled', true)
            ->where('mt.id', $r['id'])
            ->where('mtr.statusenabled', true);

        $data = $data->orderBy('mtr.tglregistrasi', 'desc');
        $data = $data->get();


        $dataAlat = DB::table('mitra_m as mt')
            ->leftjoin('mitraregistrasi_t as mtr', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftjoin('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(DB::raw("
                lk.id as lokasikalibrasifk,
                lk.lokasi,
                lp.id as lingkupfk,
                mt.id,
                mtr.norec
                "))
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->get();


        foreach ($data as $ds) {
            $ds->detailalat = [];
            foreach ($dataAlat as $sd) {
                if ($ds->norec == $sd->norec) {
                    $ds->detailalat[] = $sd;
                }
            }
            $ds->jumlah_detailalat = count($ds->detailalat);
        }

        return $this->respond($data);
    }

    public function saveMitraFoto(Request $r)
    {
        DB::beginTransaction();
        try {

            $uploadBerkasPasien = $r->file('file');
            $path = 'foto_pasien/' . $r['id'];
            $extension = $uploadBerkasPasien->getClientOriginalExtension();
            $filename =  $r['id'] . '.' . $extension;
            Mitra::where('id', $r['id'])->update([
                'isfoto' => true,
                'filename' => $filename
            ]);


            $r->fPut(
                $path . '/' . $filename,
                File::get($r->file('file')->getRealPath()),
                'public'
            );
            DB::commit();
            $transMessage = 'Sukses';
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (\Exception $e) {
            $transMessage = 'Simpan Gagal';
            DB::rollBack();
            $result = array(
                "status" => 400,
                "result"  => null
            );
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }
}
