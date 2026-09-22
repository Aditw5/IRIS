<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\Agama;
use App\Models\Master\Jabatan;
use App\Models\Master\JenisKelamin;
use App\Models\Master\JenisPegawai;
use App\Models\Master\Pegawai;
use App\Models\Master\Pendidikan;
use App\Models\Master\StatusPegawai;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MasterPegawaiCtrl extends Controller
{
    use Valet;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function masterPegawai(Request $r)
    {
        $data  = DB::table('pegawai_m as pg')
            ->leftjoin('agama_m as ag', 'pg.objectagamafk', '=', 'ag.id')
            ->leftjoin('jabatan_m as jb', 'pg.jabatan1fk', '=', 'jb.id')
            ->leftjoin('jeniskelamin_m as jk', 'pg.objectjeniskelaminfk', '=', 'jk.id')
            ->leftjoin('jenispegawai_m as jp', 'pg.objectjenispegawaifk', '=', 'jp.id')
            ->leftjoin('statuspegawai_m as sp', 'sp.id', '=', 'pg.statuspegawaifk')
            ->select(
                'pg.id',
                'ag.agama',
                'jb.namajabatanulab as namajabatan',
                'jk.jeniskelamin',
                'jp.jenispegawai',
                'jp.id as idjenispegawai',
                'pg.namalengkap',
                'pg.statusenabled',
                'pg.email',
                'pg.nohandphone',
                'pg.notlp',
                'pg.alamat',
                'pg.tgllahir',
                'pg.tglmasuk',
                'pg.tglkeluar',
                'pg.nik',
                'pg.tempatlahir',
                'pg.statuspegawaifk',
                'pg.filenameFoto',
                'pg.email',
                'sp.statuspegawai',
            )
            ->where('pg.statusenabled', true)
            ->where('pg.kdprofile', $this->kdProfile);

        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('pg.id', '=',  $r['id']);
        }
        if (isset($r['namalengkap']) && $r['namalengkap'] != '') {
            $data = $data->where('pg.namalengkap', 'ilike', '%' . $r['namalengkap'] . '%');
        }
        if (isset($r['nik']) && $r['nik'] != '') {
            $data = $data->where('pg.nik_intern', '=',  $r['nik']);
        }
        if (isset($r['nobpjs']) && $r['nobpjs'] != '') {
            $data = $data->where('pg.nobpjs', '=',  $r['nobpjs']);
        }
        if (isset($r['jenisPegawiFk']) && $r['jenisPegawiFk'] != '') {
            $data = $data->where('jp.id', '=',  $r['jenisPegawiFk']);
        }
        if (isset($r['offset']) && $r['offset'] != '') {
            $data = $data->offset($r['offset']);
        }
        if (isset($r['limit']) && $r['limit'] != '') {
            $data = $data->limit($r['limit']);
        }

        $data = $data->get();

        foreach ($data as $d) {
            if (!empty($d->tglmasuk)) {
                $d->tglmasuk = date('Y-m-d', strtotime($d->tglmasuk));
            }
            if ($d->statuspegawaifk == 1) {
                $d->status   = 'Aktif';
                $d->status_c = 'green';
            } elseif ($d->statuspegawaifk == 2) {
                $d->status   = 'Nonaktif';
                $d->status_c = 'danger';
            } else {
                $d->status   = 'Tidak Diketahui';
                $d->status_c = 'grey';
            }
        }

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function getDetailPegawai(Request $r)
    {
        $kdProfile = $this->kdProfile;
        $data = DB::table('pegawai_m as pg')
            ->leftJoin('agama_m as ag', 'ag.id', '=', 'pg.objectagamafk')
            ->leftJoin('detailkategorypegawai_m as dkp', 'dkp.id', '=', 'pg.objectdetailkategorypegawaifk ')
            ->leftJoin('golongandarah_m as gd', 'gd.id', '=', 'pg.objectgolongandarahfk')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.objectjabatanfungsionalfk')
            ->leftJoin('jabatan_m as jb1', 'jb1.id', '=', 'pg.objectjabatanstrukturalfk')
            ->leftJoin('golonganpegawai_m as gp', 'gp.id', '=', 'pg.objectgolonganpegawaifk')
            ->leftJoin('unitkerjapegawai_m as uk', 'uk.id', '=', 'pg.objectunitkerjapegawaifk')
            ->leftJoin('statuspegawai_m as sp', 'sp.id', '=', 'pg.objectstatuspegawaifk')
            ->leftJoin('kelompokjabatan_m as kj', 'kj.id', '=', 'pg.objectkelompokjabatanfk')
            ->leftJoin('statusperkawinanpegawai_m as spp', 'spp.id', '=', 'pg.objectstatusperkawinanpegawaifk')
            ->leftJoin('eselon_m as ese', 'ese.id', '=', 'pg.objecteselonfk')
            ->leftJoin('pendidikan_m as pend', 'pend.id', '=', 'pg.objectpendidikanterakhirfk')
            ->leftJoin('jeniskelamin_m as jk', 'jk.id', '=', 'pg.objectjeniskelaminfk')
            ->leftJoin('sdm_golongan_m as gol', 'gol.id', '=', 'pg.objectgolonganfk')
            ->leftJoin('nilaikelompokjabatan_m as nkj', 'nkj.id', '=', 'pg.objectkelompokjabatanfk')
            ->leftJoin('sdm_kelompokshift_m as ks', 'ks.id', '=', 'pg.objectshiftkerja')
            ->leftJoin('sdm_kedudukan_m as kdd', 'kdd.id', '=', 'pg.kedudukanfk')
            ->leftJoin('kategorypegawai_m as kp', 'kp.id', '=', 'pg.kategorypegawai')
            ->leftJoin('jenispegawai_m as jp', 'jp.id', '=', 'pg.objectjenispegawaifk')
            ->select(DB::raw("pg.*,ag.agama,dkp.detailkategorypegawai,gd.golongandarah,jb.namajabatan as jbfungsional,
			                        jb1.namajabatan as jbstruktural,gp.golonganpegawai,uk.name as unitkerja,sp.statuspegawai,
			                        kj.namakelompokjabatan,spp.statusperkawinan,ese.eselon,pend.pendidikan,jk.jeniskelamin,
			                        gol.name as golongan,nkj.detailkelompokjabatan,nkj.grade,ks.kelompokshiftkerja,
			                        kp.kategorypegawai as namakategorypegawai,kdd.name as kedudukan,pg.objectjenispegawaifk,jp.jenispegawai"))
            ->where('pg.kdprofile', $kdProfile)
            ->where('pg.statusenabled', true)
            ->orderBy('pg.namalengkap');

        if (isset($r['idPegawai']) && $r['idPegawai'] != "" && $r['idPegawai'] != "undefined") {
            $data = $data->where('pg.id', '=', $r['idPegawai']);
        }
        $data = $data->get();

        $dataKeluarga = DB::table('keluargapegawai_m as kp')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'kp.objectpegawaifk')
            ->leftJoin('jeniskelamin_m as jk', 'jk.id', '=', 'kp.objectjeniskelaminfk')
            ->leftJoin('hubungankeluarga_m as hk', 'hk.id', '=', 'kp.objectkdhubunganfk')
            ->leftJoin('statusperkawinanpegawai_m as spp', 'spp.id', '=', 'kp.objectstatusperkawinanpegawaifk')
            ->leftJoin('pendidikan_m as pend', 'pend.id', '=', 'kp.objectpendidikanterakhirfk')
            ->leftJoin('pekerjaan_m as pe', 'pe.id', '=', 'kp.objectpekerjaanfk')
            ->select(DB::raw("kp.*,pg.namalengkap as namapegawai,jk.jeniskelamin,hk.reportdisplay as hubungankeluarga,
	                          spp.statusperkawinan,pend.pendidikan,pe.pekerjaan"))
            ->where('kp.kdprofile', $kdProfile)
            ->where('pg.statusenabled', true)
            ->orderBy('pg.namalengkap');

        if (isset($r['idPegawai']) && $r['idPegawai'] != "" && $r['idPegawai'] != "undefined") {
            $dataKeluarga = $dataKeluarga->where('pg.id', '=', $r['idPegawai']);
        }
        $dataKeluarga = $dataKeluarga->get();

        $dataPendidikan = DB::table('riwayatpendidikan_t as rp')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'rp.objectpegawaifk')
            ->leftJoin('pendidikan_m as pend', 'pend.id', '=', 'rp.objectpendidikanfk')
            ->select(DB::raw("rp.*,pend.pendidikan,pg.namalengkap"))
            ->where('rp.kdprofile', $kdProfile)
            ->where('pg.statusenabled', true)
            ->orderBy('rp.tgllulus');

        if (isset($r['idPegawai']) && $r['idPegawai'] != "" && $r['idPegawai'] != "undefined") {
            $dataPendidikan = $dataPendidikan->where('pg.id', '=', $r['idPegawai']);
        }
        $dataPendidikan = $dataPendidikan->get();

        $dataPelatihan = DB::table('riwayatpelatihan_t as rpl')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'rpl.objectpegawaifk')
            ->select(DB::raw("rpl.*,pg.namalengkap"))
            ->where('rpl.kdprofile', $kdProfile)
            ->where('pg.statusenabled', true)
            ->orderBy('rpl.tglmulai');

        if (isset($r['idPegawai']) && $r['idPegawai'] != "" && $r['idPegawai'] != "undefined") {
            $dataPelatihan = $dataPelatihan->where('pg.id', '=', $r['idPegawai']);
        }
        $dataPelatihan = $dataPelatihan->get();

        $dataJabatan = DB::table('riwayatjabatan_t as rj')
            ->leftJoin('jenisjabatan_m as jb', 'jb.id', '=', 'rj.objectjenisjabatanfk')
            ->leftJoin('jabatan_m as jab', 'jab.id', '=', 'rj.objectjabatanttdfk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'rj.objectpegawaifk')
            ->leftJoin('pegawai_m as pg1', 'pg1.id', '=', 'rj.objectpegawaittdfk')
            ->select(DB::raw("rj.*,jb.jenisjabatan,jab.namajabatan as namajabatanttd,
			                  pg.namalengkap as namapegawai,pg1.namalengkap as pegawaittd,
			                  pg1.namalengkap || ' / ' || jab.namajabatan as pegawaipenanggungjawab"))
            ->where('pg.statusenabled', true)
            ->where('rj.kdprofile', $kdProfile)
            ->orderBy('rj.tglsk');

        if (isset($r['idPegawai']) && $r['idPegawai'] != "" && $r['idPegawai'] != "undefined") {
            $dataJabatan = $dataJabatan->where('pg.id', '=', $r['idPegawai']);
        }
        $dataJabatan = $dataJabatan->get();

        $result = array(
            'datapegawai' => $data,
            'datakeluarga' => $dataKeluarga,
            'datapendidikan' => $dataPendidikan,
            'datapelatihan' => $dataPelatihan,
            'datajabatan' => $dataJabatan,
            "createby : " => 'eaaditwiran19@gmail.com',
        );

        return $this->respond($result);
    }

    public function profileUserDetail(Request $r)
    {
        $data = DB::table('users as u')
            ->leftJoin('mitra_m as mm', DB::raw('CAST(u.mitrafk AS TEXT)'), '=', 'mm.id')
            ->select(
                'u.id',
                'u.name',
                'u.email',
                'u.nowa',
                'u.kdprofile',
                'u.isuserbaru',
                'u.mitrafk',
                'u.jabatan',
                'u.iseksternal',
                'u.filenameFoto',
                'u.statusenabled',
                'u.created_at',
                'u.updated_at',
                'mm.namaperusahaan as unit',
            )
            ->where('u.kdprofile', $this->kdProfile);

        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('u.id', $r['id']);
        }

        $data = $data->first();

        return $this->respond([
            'data' => $data,
        ]);
    }


    public function saveProfileUser(Request $r)
    {
        DB::beginTransaction();
        try {
            $id = $r['id'];
            $name = trim((string) $r['name']);
            $email = trim((string) $r['email']);
            $nowa = trim((string) $r['nowa']);
            $jabatan = trim((string) $r['jabatan']);

            if ($id == '') {
                return $this->respond(null, 400, 'ID user tidak ditemukan');
            }
            if ($name == '') {
                return $this->respond(null, 400, 'Nama harus diisi');
            }
            if ($email == '') {
                return $this->respond(null, 400, 'Email harus diisi');
            }
            if ($nowa == '') {
                return $this->respond(null, 400, 'No. WhatsApp harus diisi');
            }
            if ($jabatan == '') {
                return $this->respond(null, 400, 'Jabatan harus diisi');
            }

            $cekUser = DB::table('users')
                ->where('id', $id)
                ->where('kdprofile', $this->kdProfile)
                ->first();

            if (!$cekUser) {
                return $this->respond(null, 404, 'User tidak ditemukan');
            }

            $cekEmail = DB::table('users')
                ->where('email', $email)
                ->where('id', '<>', $id)
                ->where('kdprofile', $this->kdProfile)
                ->first();

            if ($cekEmail) {
                return $this->respond(null, 400, 'Email sudah digunakan user lain');
            }

            DB::table('users')
                ->where('id', $id)
                ->where('kdprofile', $this->kdProfile)
                ->update([
                    'name' => $name,
                    'email' => $email,
                    'nowa' => $nowa,
                    'jabatan' => strtoupper($jabatan),
                    'updated_at' => now(),
                ]);

            $data = DB::table('users as u')
                ->leftJoin('mitra_m as mm', DB::raw('CAST(u.mitrafk AS TEXT)'), '=', 'mm.id')
                ->select(
                    'u.id',
                    'u.name',
                    'u.email',
                    'u.nowa',
                    'u.kdprofile',
                    'u.isuserbaru',
                    'u.mitrafk',
                    'u.jabatan',
                    'u.iseksternal',
                    'u.filenameFoto',
                    'u.statusenabled',
                    'u.created_at',
                    'u.updated_at',
                    'mm.namaperusahaan as unit'
                )
                ->where('u.id', $id)
                ->where('u.kdprofile', $this->kdProfile)
                ->first();

            DB::commit();

            return $this->respond([
                'data' => $data,
                'as' => 'aditwiran19@gmail.com',
            ], 200, 'Profile user berhasil diupdate');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('saveProfileUser error', [
                'msg' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return $this->respond(null, 400, 'Simpan Profile User Gagal');
        }
    }
    public function savePegawaiFoto(Request $r)
    {
        DB::beginTransaction();
        try {
            if (!$r->hasFile('file')) {
                return $this->respond(null, 400, 'File tidak ditemukan');
            }

            $uploadFoto = $r->file('file');
            $extension  = strtolower($uploadFoto->getClientOriginalExtension());
            $timestamp = now()->format('Ymd_His');
            $filename  = 'foto_pegawai_' . $r->id . '_' . $timestamp . '.' . $extension;

            $destinationPath = public_path('berkas-mutu');
            $currentPegawai = Pegawai::where('id', $r->id)->first();

            if ($currentPegawai && !empty($currentPegawai->filenameFoto)) {
                $oldFilePath = $destinationPath . '/' . $currentPegawai->filenameFoto;
                if (File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }
            }

            Pegawai::where('id', $r->id)->update([
                'isfotoPegawai' => true,
                'filenameFoto'  => $filename,
            ]);

            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            $uploadFoto->move($destinationPath, $filename);

            DB::commit();

            $result = [
                'status' => 200,
                'result' => [
                    'as' => 'aditwiran19@gmail.com',
                    'filename' => $filename
                ],
            ];

            return $this->respond($result['result'], $result['status'], 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('savePegawaiFoto error', [
                'msg' => $e->getMessage(),
            ]);

            $result = [
                'status' => 400,
                'result' => null,
            ];

            return $this->respond($result['result'], $result['status'], 'Simpan Gagal');
        }
    }

    public function saveUserFoto(Request $r)
    {
        DB::beginTransaction();
        try {
            if (!$r->hasFile('file')) {
                return $this->respond(null, 400, 'File tidak ditemukan');
            }

            $uploadFoto = $r->file('file');
            $extension  = strtolower($uploadFoto->getClientOriginalExtension());
            $timestamp = now()->format('Ymd_His');
            $filename  = 'foto_user_' . $r->id . '_' . $timestamp . '.' . $extension;

            $destinationPath = public_path('berkas-user');

            $currentUser = DB::table('users')->where('id', $r->id)->first();
            if ($currentUser && !empty($currentUser->filenameFoto)) {
                $oldFilePath = $destinationPath . '/' . $currentUser->filenameFoto;
                if (File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }
            }

            DB::table('users')
                ->where('id', $r->id)
                ->update([
                    'filenameFoto' => $filename,
                    'updated_at' => now(),
                ]);

            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            $uploadFoto->move($destinationPath, $filename);

            DB::commit();

            return $this->respond([
                'filename' => $filename,
            ], 200, 'Sukses');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('saveUserFoto error', [
                'msg' => $e->getMessage(),
            ]);

            return $this->respond(null, 400, 'Simpan Gagal');
        }
    }

    public function savePegawai(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();

        try {
            $dataPegawai = $request['datapegawai'];
            $message = "Simpan Data Pegawai Berhasil";

            if ($dataPegawai['id'] == '') {
                $idPegawai = $this->SEQUENCE_MASTER(new Pegawai(), 'id', $this->kdProfile);
                $dataSavePegawai = new Pegawai();
                $dataSavePegawai->id = $idPegawai;
                $dataSavePegawai->kdprofile = $kdProfile;
                $dataSavePegawai->statusenabled = true;
                $dataSavePegawai->norec = $dataSavePegawai->generateNewId();
            } else {
                $dataSavePegawai = Pegawai::where('id', $dataPegawai['id'])->where('kdprofile', $kdProfile)->first();
                $message = "Update Data Pegawai Berhasil";
            }

            $dataSavePegawai->namalengkap = $dataPegawai['namalengkap'];
            $dataSavePegawai->nik = $dataPegawai['nik'];
            $dataSavePegawai->nid = $dataPegawai['nid'];
            $dataSavePegawai->gelardepan = $dataPegawai['gelardepan'];
            $dataSavePegawai->gelarbelakang = $dataPegawai['gelarbelakang'];
            $dataSavePegawai->tempatlahir = $dataPegawai['tempatlahir'];
            $dataSavePegawai->kodepos = $dataPegawai['kodepos'];
            $dataSavePegawai->email = $dataPegawai['email'];
            $dataSavePegawai->nohandphone = $dataPegawai['nohandphone'];
            $dataSavePegawai->alamat = $dataPegawai['alamat'];
            $dataSavePegawai->tgllahir = $dataPegawai['tgllahir'];
            $dataSavePegawai->tglmasuk = $dataPegawai['tglmasuk'];
            $dataSavePegawai->objectagamafk = $dataPegawai['objectagamafk'];
            $dataSavePegawai->objectjeniskelaminfk = $dataPegawai['objectjeniskelaminfk'];
            $dataSavePegawai->statuspegawaifk =  $dataPegawai['statuspegawaifk'];
            $dataSavePegawai->jabatan1fk = $dataPegawai['jabatan1fk'];
            $dataSavePegawai->objectpendidikanterakhirfk =  $dataPegawai['objectpendidikanterakhirfk'];
            $dataSavePegawai->objectjenispegawaifk = $dataPegawai['objectjenispegawaifk'];
            $dataSavePegawai->save();

            DB::commit();

            $result = array(
                "status" => 200,
                "message" => $message,
                "result" => array(
                    "data"  => $dataSavePegawai,
                    "as" => '@aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => "Simpan Gagal",
                "result"  => $e->getMessage() . ' ' . $e->getLine()
            );
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function deletePegawai(Request $r)
    {
        DB::beginTransaction();
        try {
            $dataPS = Pegawai::where('id', $r['id'])
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

    public function masterPegawaidropdown(Request $r)
    {
        $res['jeniskelamin'] = JenisKelamin::mine()->get();
        $res['agama'] = Agama::mine()->get();
        $res['jenispegawai'] = JenisPegawai::mine()->get();
        $res['pendidikan'] = Pendidikan::mine()->get();
        $res['namajabatanulab'] = Jabatan::mine()->get();
        $res['statuspegawai'] = StatusPegawai::mine()->get();

        return $this->respond($res);
    }

    public function getFotoPegawai($filename)
    {
        $path = public_path('berkas-mutu/' . $filename);

        if (!File::exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $mime    = File::mimeType($path);
        $content = File::get($path);
        $base64  = base64_encode($content);

        $dataUrl = 'data:' . $mime . ';base64,' . $base64;

        return response()->json([
            'image' => $dataUrl,
        ], 200);
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
                'jb.id as idjabatan',
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

    public function updatePegawai(Request $r)
    {
        DB::beginTransaction();
        try {
            $dataPS = Pegawai::where('id', $r['id'])->update([
                'ihs_id' => $r['ihs_id'],
                'noidentitas' => $r['noidentitas']
            ]);

            DB::commit();
            $result = [
                'status' => 201,
                'message' => 'Sukses',
                'result' => $dataPS,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $result = [
                'status' => 400,
                'message' => "Something Want Wrong",
                'result' => $e->getMessage(),
            ];
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function jadwalKerja(Request $r)
    {
        $login  = DB::table('loginuser_s as ru')
            ->join('kelompokuser_s as dp', 'ru.objectkelompokuserfk', '=', 'dp.id')
            ->join('pegawai_m as pg', 'ru.objectpegawaifk', '=', 'pg.id')
            ->select(
                'ru.id',
                'ru.namauser',
                'dp.kelompokuser',
                'pg.namalengkap',
            )
            ->where('ru.statusenabled', true);

        if (isset($r['idpegawai']) && $r['idpegawai'] != '') {
            $login = $login->where('pg.id', '=',  $r['idpegawai']);
        }

        $login = $login->get();
        $res['login'] = $login;

        return $this->respond($res);
    }

    public function savePegawaiFace(Request $r)
    {
        DB::beginTransaction();
        try {
            $idPegawai = $r['id'];
            $descriptor = $r['descriptor'];
            $imageBase64 = $r['image'];

            if (!$idPegawai || !$imageBase64) {
                return $this->respond([], 400, "Data tidak lengkap");
            }

            $image = str_replace('data:image/jpeg;base64,', '', $imageBase64);
            $image = str_replace(' ', '+', $image);
            $imageData = base64_decode($image);

            $folderPath = 'faces/' . $idPegawai;
            if (!Storage::disk('public')->exists($folderPath)) {
                Storage::disk('public')->makeDirectory($folderPath);
            }

            $filename = $idPegawai . '_' . time() . '.jpg';
            $path = $folderPath . '/' . $filename;

            Storage::disk('public')->put($path, $imageData);

            $check = DB::table('facerecognition_m')
                ->where('objectpegawaifk', $idPegawai)
                ->first();

            if ($check) {
                DB::table('facerecognition_m')
                    ->where('objectpegawaifk', $idPegawai)
                    ->update([
                        'filename' => $filename,
                        'descriptor' => json_encode($descriptor),
                        'updated_at' => now()
                    ]);
            } else {
                DB::table('facerecognition_m')->insert([
                    'objectpegawaifk' => $idPegawai,
                    'filename' => $filename,
                    'descriptor' => json_encode($descriptor),
                    'statusenabled' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            Pegawai::where('id', $idPegawai)->update([
                'isfoto' => true,
                'filename' => $filename,
            ]);

            DB::commit();

            $result = [
                'status' => 200,
                'message' => 'Registrasi wajah berhasil disimpan',
                'result' => [
                    'image_path' => asset('storage/' . $path),
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $result = [
                'status' => 400,
                'message' => 'Gagal menyimpan data wajah',
                'result' => $e->getMessage(),
            ];
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }
}
