<?php

namespace App\Http\Resources\Dashboard\TotalPasien;

use Illuminate\Http\Resources\Json\JsonResource;

class TotalPasienResource extends JsonResource
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
            'id' => $this->id,
            'statusenabled' => $this->statusenabled,
            'namaruangan' => $this->namaruangan,
            'objectruanganasalfk' => $this->objectruanganasalfk,
            'objectpegawaifk' => $this->objectpegawaifk,
            'norec_pd' => $this->norec_pd,
            'nocmfk' => $this->nocmfk,
            'nocm' => $this->nocm,
            'noregistrasi' => $this->noregistrasi,
            'namalengkap' => $this->namalengkap,
            'namapasien' => $this->namapasien,
            'tglpulang' =>  $this->tglmeninggal ?  $this->tglmeninggal : $this->tglpulang,
            'tglmeninggal' => $this->tglmeninggal,
            'tglregistrasi' => $this->tglregistrasi,
            'status' => $this->tglmeninggal ? "Meninggal"  : "Pulang",
            'color' => $this->tglmeninggal ? "danger"  : "purple",
            'dokterfk' => $this->dokterfk,
            'lamarawat' => $this->lamarawat
        ];
    }
}
