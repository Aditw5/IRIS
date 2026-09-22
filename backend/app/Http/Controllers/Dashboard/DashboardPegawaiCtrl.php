<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Master\Pegawai;
use App\Models\Master\Ruangan;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardPegawaiCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function dashboardPegawai(Request $r)
    {
        $kdProfile = $this->kdProfile;
        //get data jenis kelamin
        $jk =  collect(DB::select("
            select 
            count(jk.id) as total
            ,jk.jeniskelamin as name
            from pegawai_m as pg
            inner join jeniskelamin_m as jk on jk.id= pg.objectjeniskelaminfk
            where pg.kdprofile = $kdProfile
            and pg.statusenabled = true
            group by jk.jeniskelamin
        "));
        $seriesJK = [];
        $labelJK = [];
        foreach ($jk as $d) {
            $seriesJK[] = $d->total;
            $labelJK[]  = $d->name;
        }
        //get data pendidikan terakhir
        $pk =  collect(DB::select("
            select 
            count(pe.id) as total
            ,pe.pendidikan as pendidikan
            from pegawai_m as pg
            inner join pendidikan_m as pe on pe.id= pg.objectpendidikanterakhirfk
            where pg.kdprofile = $kdProfile
            and pg.statusenabled = true
            group by pe.pendidikan
        "));
        $seriesPK = [];
        $labelPK = [];
        foreach ($pk as $d) {
            $seriesPK[] = $d->total;
            $labelPK[]  = $d->pendidikan;
        }
        // get data jenis pegawai
        $jp =  collect(DB::select("
        select 
        count(jp.id) as total
        ,jp.jenispegawai as jenispegawai
        from pegawai_m as pg
        inner join jenispegawai_m as jp on jp.id= pg.objectjenispegawaifk
        where pg.kdprofile = $kdProfile
        and pg.statusenabled = true
        group by jp.jenispegawai
        "));
        $seriesJP = [];
        $labelJP = [];
        foreach ($jp as $d) {
            $seriesJP[] = $d->total;
            $labelJP[]  = $d->jenispegawai;
        }

        // get data unit kerja pegawai
        $uk =  collect(DB::select("
        select 
        count(pg.id) as total
        ,coalesce(uk.name, 'Tanpa Unit Kerja') as departemen
        from pegawai_m as pg
        left join unitkerjapegawai_m as uk on uk.id= pg.objectunitkerjapegawaifk
        where pg.kdprofile = $kdProfile
        and pg.statusenabled = true
        group by uk.name
        "));
        $seriesUK = [];
        $labelUK = [];
        foreach ($uk as $d) {
            $seriesUK[] = $d->total;
            $labelUK[]  = $d->departemen;
        }

        // get data kelompok jabatan
        $kj =  collect(DB::select("
        select 
        count(kj.id) as total
        ,kj.namakelompokjabatan as kelompokjabatan
        from pegawai_m as pg
        inner join kelompokjabatan_m as kj on kj.id= pg.objectkelompokjabatanfk
        where pg.kdprofile = $kdProfile
        and pg.statusenabled = true
        group by kj.namakelompokjabatan
        "));
        $seriesKJ = [];
        $categoriesKJ = [];
        foreach ($kj as $d) {
            $seriesKJ[] = $d->total;
            $categoriesKJ[]  = $d->kelompokjabatan; 
        }

        //get total pegawai
        $totalPe =  collect(DB::select("
        select 
        count(id) as total
        from pegawai_m
        "))->first()->total;

        //get total pegawai aktif
        $totalPegAktif = collect(DB::select("
        select count (st.statuspegawai) as total  from statuspegawai_m as st
        inner join pegawai_m as pg on st.id= pg.objectstatuspegawaifk
        WHERE st.statuspegawai = 'Aktif'
        "))->first()->total;

        //get total pegawai nonaktif
        $totalPegNA = collect(DB::select("
        select count (st.statuspegawai) as total from statuspegawai_m as st
        inner join pegawai_m as pg on st.id= pg.objectstatuspegawaifk
        WHERE st.statuspegawai = 'Nonaktif'
        "))->first()->total;

        $result['chartJK']['series'] = $seriesJK;
        $result['chartJK']['labels'] = $labelJK;
        $result['chartPK']['series'] = $seriesPK;
        $result['chartPK']['labels'] = $labelPK;
        $result['chartJP']['series'] = $seriesJP;
        $result['chartJP']['labels'] = $labelJP;
        $result['chartUK']['series'] = $seriesUK;
        $result['chartUK']['labels'] = $labelUK;
        $result['chartKJ']['series'] =  [array(
            'name'=> 'Jumlah',
            'data'=> $seriesKJ
        )];
        $result['chartKJ']['categories'] = $categoriesKJ;
        $result['totalPegawai']= $totalPe;
        $result['totalPegawaiAktif']= $totalPegAktif;
        $result['totalPegawai']= $totalPe;
        $result['totalPegawaiNonaktif']= $totalPegNA;
        
        return $this->respond($result);
    }

    // get detail data pegawai aktif

    public function DataPegawaiAktif(Request $r)
    {
        $data  = DB::table('pegawai_m as pg')
        ->leftjoin('ruangan_m as ru', 'ru.id','=','pg.objectruangankerjafk')
        ->leftjoin('agama_m as ag', 'pg.objectagamafk', '=', 'ag.id')
        ->leftjoin('jabatan_m as jb', 'pg.objectjabatanfungsionalfk', '=', 'jb.id')
        ->leftjoin('unitkerjapegawai_m as uk', 'pg.objectunitkerjapegawaifk', '=', 'uk.id')
        ->leftjoin('jeniskelamin_m as jk', 'pg.objectjeniskelaminfk', '=', 'jk.id')
        ->leftjoin('detailkategorypegawai_m as dkp', 'pg.objectdetailkategorypegawaifk', '=', 'dkp.id')
        ->leftjoin('jenispegawai_m as jp', 'pg.objectjenispegawaifk', '=', 'jp.id')
        ->leftjoin('negara_m as ne', 'pg.objectnegarafk', '=', 'ne.id')
        ->leftjoin('pendidikan_m as pe', 'pg.objectpendidikanterakhirfk', '=', 'pe.id')
        ->leftjoin('statuspegawai_m as sp', 'pg.objectstatuspegawaifk', '=', 'sp.id')
        ->leftjoin('statusperkawinan_m as spk', 'pg.objectstatusperkawinanfk', '=', 'spk.id')
        ->leftjoin('suku_m as su', 'pg.objectsukufk', '=', 'su.id')
        ->leftjoin('typepegawai_m as tp', 'pg.objecttypepegawaifk', '=', 'tp.id')
        ->leftjoin('kelompokjabatan_m as kj', 'pg.objectkelompokjabatanfk', '=', 'kj.id')
        ->leftjoin('sdm_kedudukan_m as sku', 'pg.kedudukanfk', '=', 'sku.id')
        ->leftjoin('sdm_golongan_m as gpi', 'pg.objectgolonganpegawaifk', '=', 'gpi.id')
        ->leftjoin('eselon_m as es', 'pg.objecteselonfk', '=', 'es.id')
        ->leftjoin('sdm_kelompokshift_m as ksk', 'pg.objectshiftkerja', '=', 'ksk.id')
        ->select(
            'pg.id',
            'ag.agama',
            'sp.statuspegawai',
            'pe.pendidikan',
            'spk.statusperkawinan',
            'ne.namanegara',
            'su.suku',
            'pg.kedudukanfk',
            'sku.kedudukan',
            'uk.name as namadepartemen',
            'kj.namakelompokjabatan',
            'pg.objectkelompokjabatanfk',
            'tp.typepegawai',
            'jk.jeniskelamin',
            'jp.jenispegawai',
            'pg.namalengkap',
            'pg.statusenabled',
            'pg.namapanggilan',
            'pg.statusenabled',
            'pg.bankrekeningatasnama',
            'pg.bankrekeningnama',
            'pg.bankrekeningnomor',
            'pg.fingerprintid',
            'pg.nik_intern',
            'pg.nip_pns',
            'pg.noidentitas',
            'pg.npwp',
            'pg.photodiri',
            'pg.qpegawai',
            'pg.qtyanak',
            'pg.qtytanggungan',
            'pg.qtytotaljiwa',
            'pg.tempatlahir',
            'pg.email',
            'pg.nohandphone',
            'pg.notlp',
            'pg.nobpjs',
            'pg.alamat',
            'pg.tgllahir',
            'pg.tglmasuk',
            'pg.tglkeluar',
            'pg.nosip',
            'pg.nostr',
            'pg.tglberakhirsip',
            'pg.tglberakhirstr',
            'pg.tglterbitsip',
            'pg.tglterbitstr',
            'dkp.detailkategorypegawai',
            'gpi.name',
            'es.eselon',
            'ksk.kelompokshiftkerja',
            'pg.objectshiftkerja'

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
            $data = $data->where('pg.noidentitas', '=',  $r['nik']);
        }
        if (isset($r['ruanganid']) && $r['ruanganid'] != '') {
            $data = $data->where('pg.objectruangankerjafk', '=',  $r['ruanganid']);
        }

 

        // $data = $data->orderByDesc('pg.namalengkap');
        $data = $data->get();

        foreach ($data as $d) {
            $d->tglmasuk = date('Y-m-d', strtotime($d->tgllahir));
            $d->status = 'Aktif';
            $d->status_c = 'purple';
            if ($d->tglkeluar != null) {
                $d->status = 'Nonaktif';
                $d->status_c = 'danger';
            }
        }
        $res['data'] = $data;
        return $this->respond($res);
    }
    
    public function getJumlah (Request $request) 
    {
        $TotalPeg = Pegawai::where('kdprofile', $this->kdProfile)
            ->where('statusenabled', true);
        if (isset($request['ruanganid']) && $request['ruanganid'] != '') {
            $TotalPeg = $TotalPeg->where('objectruangankerjafk', '=',  $request['ruanganid']);
        }
        $TotalPeg = $TotalPeg->count();

        $res['idJenisPegawaiDokter'] = explode(',', $this->settingFix('idJenisPegawaiDokter'));

        $Dokter = Pegawai::mine()
        ->where('objectjenispegawaifk', $res['idJenisPegawaiDokter']);
        if (isset($request['ruanganid']) && $request['ruanganid'] != '') {
            $Dokter = $Dokter->where('objectruangankerjafk', '=',  $request['ruanganid']);
        }
        $Dokter = $Dokter->count();
        
        $PegawaiAktif = Pegawai::where('kdprofile', $this->kdProfile)
        ->whereNull('tglkeluar')
        ->where('statusenabled', true);
        if (isset($request['ruanganid']) && $request['ruanganid'] != '') {
            $PegawaiAktif = $PegawaiAktif->where('objectruangankerjafk', '=',  $request['ruanganid']);
        }
        $PegawaiAktif = $PegawaiAktif->count();

        $PegawaiNonAktif = Pegawai::where('kdprofile', $this->kdProfile)
        ->whereNotNull('tglkeluar')
        ->where('statusenabled', true);
        if (isset($request['ruanganid']) && $request['ruanganid'] != '') {
            $PegawaiNonAktif = $PegawaiNonAktif->where('objectruangankerjafk', '=',  $request['ruanganid']);
        }
        $PegawaiNonAktif = $PegawaiNonAktif->count();

        $res['pegawai'] = $TotalPeg;
        $res['dokter'] = $Dokter;
        $res['pegAktif'] = $PegawaiAktif;
        $res['pegNon'] = $PegawaiNonAktif;
      
        return $this->respond($res);

    }

    public function getRuangKerja(Request $r)
    {
        $res['ruangan'] = Ruangan::mine()->get();

        return $this->respond($res);
    }
    
}
