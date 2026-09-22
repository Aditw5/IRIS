<?php

namespace App\Http\Resources\Dashboard\Register;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PasienDaftarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        return [
            "tglmasuk" => date('d-m-Y', strtotime($this->tglmasuk)),
            "jammasuk" => date('H:i', strtotime($this->tglmasuk)),
            "noregistrasi" => $this->noregistrasi,
            "nosep" => $this->nosep,
            "namapasien" => $this->namapasien,
            "nocm" => $this->nocm,
            "jk" => $this->jk,
            "namakelas" => $this->namakelas,
            "dokterpj" => $this->dokterpj,
            "petugas" =>  $this->petugas,
            "tglregistrasi" => $this->tglregistrasi,
            "tglpulang" => $this->tglpulang,
            'jampulang' => $this->tglpulang,
            "namarekanan" => $this->namarekanan,
            "ruangandaftar" => $this->ruangandaftar,
            "idruangandaftar" => $this->idruangandaftar,
            "iddepartementdaftar" => $this->iddepartementdaftar,
            "kelompokpasien" => $this->kelompokpasien,
            "statuskunjungan" => $this->statuskunjungan,
            "alamatlengkap" => $this->alamatlengkap,
            "statusperkawinan" => $this->statusperkawinan,
            "agama" => $this->agama,
            "pendidikan" => $this->pendidikan,
            "pekerjaan" => $this->pekerjaan,
            "norec" => $this->norec,
            "kelurahan" => $this->kelurahan,
            "kecamatan" => $this->kecamatan,
            "kabupaten" => $this->kabupaten,
            "diagnosamasuk" => $this->diagnosamasuk,
            'statuspasien' => $this->statuspasien,
            'tgllahir' => $this->tgllahir,
            'umur' => \Carbon\Carbon::parse($this->tgllahir)->age,
            'tglpulang' => $this->tglpulang,
            'jampulang' => date('H:i', strtotime($this->tglpulang))
        ];
    }
}
