<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Transaksi\PasienDaftar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanRadiologiCtrl extends Controller
{
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function getTimeRespon(Request $r)
    {
        $kdProfile = (int)$this->kdProfile;
        $sDokterPemeriksa = $this->settingFix('jenisPetugasDokterPemeriksa');

        $data = DB::table('pelayananpasien_t as pp')
            ->join('antrianpasiendiperiksa_t as apd', 'apd.norec', '=', 'pp.noregistrasifk')
            ->join('kelas_m as kls', 'kls.id', '=', 'apd.objectkelasfk')
            ->join('produk_m as prd', 'prd.id', '=', 'pp.produkfk')
            ->join('ruangan_m as ru', 'ru.id', '=', 'apd.objectruanganfk')
            ->leftJOIN('strukpelayanan_t as sp', 'sp.norec', '=', 'pp.strukfk')
            ->leftJOIN('strukresep_t as sr', 'sr.norec', '=', 'pp.strukresepfk')
            ->leftJOIN('pegawai_m as pg', 'pg.id', '=', 'sr.penulisresepfk')
            ->leftJOIN('strukorder_t as so', 'so.norec', '=', 'pp.strukorderfk')
            ->leftJOIN(
                'ris_order as ris',
                'ris.order_no',
                '=',
                DB::raw('so.noorder AND ris.order_code=cast(pp.produkfk as text)')
            )
            ->select(
                'pp.norec',
                'apd.norec as norec_apd',
                'prd.namaproduk',
                'kls.namakelas',
                'pp.tglpelayanan',
                'ru.namaruangan',
                'pp.strukresepfk',
                'pp.jumlah',
                'pp.hargasatuan',
                'pg.namalengkap',
                'apd.norec as norec_apd',
                'so.noorder',
                'pp.strukfk',
                'pp.produkfk',
                'ru.objectdepartemenfk',
                'ris.order_key as idbridging',
                'ris.order_complete',
                'ris.description',
                'ris.report_date',
                'ris.link',
                'so.objectruangantujuanfk',
                DB::raw("  ris.patient_id || '-' || ris.order_cnt as radiologiid,
                case when pp.jasa is not null then pp.jasa else 0 end jasa,
                case when pp.hargadiscount is not null then pp.hargadiscount else 0 end hargadiscount,
                (
                    (pp.hargasatuan  - case when pp.hargadiscount is null then 0 else pp.hargadiscount end)
                     * pp.jumlah)
                + (case when pp.jasa is not null then pp.jasa else 0 end)
                 as total,
                to_char(pp.tglpelayanan,'yyyy-MM-dd')  as tglpelayanan_group ,
                case when pp.strukresepfk is null then 'Layanan' else 'Resep' end as jenis
               ")
            )
            ->where('pp.statusenabled', true)
            ->where('pp.kdprofile', $kdProfile)
            ->where('ru.id', $this->settingFix('idRuanganRadiologi'));
            // ->where('apd.noregistrasifk', $r['norec_pd']);
        if (isset($r['strukfk']) && $r['strukfk'] != '' && $r['strukfk'] == null) {
            $data = $data->whereNull('pp.strukfk');
        }
        $data = $data->orderByDesc('pp.tglpelayanan');
        $data = $data->get();

        $pelayananpetugas = DB::table('pelayananpasienpetugas_t as ptu')
            ->leftjoin('pegawai_m as pg', 'pg.id', '=', 'ptu.objectpegawaifk')
            ->select('ptu.pelayananpasien', 'pg.namalengkap', 'pg.id')
            ->where('ptu.kdprofile', $kdProfile)
            ->where('ptu.objectjenispetugaspefk', $sDokterPemeriksa)
            // ->where('ptu.noregistrasi', $pd->noregistrasi)
            ->get();


        $result['total'] = 0;
        $result['deposit'] = 0;
        $result['diskon'] = 0;
        $result['dibayar'] = 0;
        $result['sisa'] = 0;

        $sama = false;
        $group  = [];
        foreach ($data as $item) {
            $item->checked = false;
            $result['total']  = $result['total']  + (float) $item->total;
            $result['diskon']  = $result['diskon']  + (float) $item->hargadiscount;
            foreach ($pelayananpetugas as $itemd) {
                if ($itemd->pelayananpasien == $item->norec) {
                    $item->dokterpemeriksa = $itemd->namalengkap;
                    $item->pegawaifk = $itemd->id;
                }
            }
            $sama = false;
            $i = 0;
            foreach ($group as $itemg) {
                if ($item->tglpelayanan_group == $group[$i]['tglpelayanan_group']) {
                    $sama = true;
                }
                $i = $i + 1;
            }
            if ($sama == false) {
                $group[] = array(
                    'tglpelayanan_group' => $item->tglpelayanan_group,
                    'details' => []
                );
            }
        }
        foreach ($group as $k => $d) {
            foreach ($data as $d2) {
                if ($d['tglpelayanan_group'] == $d2->tglpelayanan_group) {
                    $group[$k]['details'][] = $d2;
                }
            }
        }


        $result['length'] = count($data);
        $result['detail'] = $group; //collect($data)->groupBy('tglpelayanan_group')->sortByDesc('tglpelayanan_group');
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

}
