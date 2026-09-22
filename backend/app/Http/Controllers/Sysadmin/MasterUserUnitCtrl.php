<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterUserUnitCtrl extends Controller
{
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    private function baseQuery()
    {
        return DB::table('users as u')
            ->leftJoin('mitra_m as mm', DB::raw('CAST(u.mitrafk AS TEXT)'), '=', 'mm.id')
            ->select(
                'u.id',
                'u.name',
                'u.email',
                'u.nowa',
                'u.jabatan',
                'u.iseksternal',
                'u.isuserbaru',
                'u.mitrafk',
                'u.statusenabled',
                'u.email_verified_at',
                'u.created_at',
                'mm.namaperusahaan as unit'
            )
            ->where('u.kdprofile', $this->kdProfile);
    }

    public function masterUserUnit(Request $r)
    {
        $data = $this->baseQuery();

        if (isset($r['search']) && $r['search'] != '') {
            $search = $r['search'];
            $data = $data->where(function ($q) use ($search) {
                $q->where('u.name', 'ilike', '%' . $search . '%')
                    ->orWhere('u.email', 'ilike', '%' . $search . '%')
                    ->orWhere('u.nowa', 'ilike', '%' . $search . '%')
                    ->orWhere('mm.namaperusahaan', 'ilike', '%' . $search . '%');
            });
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
            $data = $data->where('u.statusenabled', '=', $r['statusenabled']);
        }
        if (isset($r['status_verifikasi']) && $r['status_verifikasi'] == 'terverifikasi') {
            $data = $data->whereNotNull('u.email_verified_at');
        } elseif (isset($r['status_verifikasi']) && $r['status_verifikasi'] == 'belum') {
            $data = $data->whereNull('u.email_verified_at');
        }

        $data = $data->orderByDesc('u.created_at')->get();

        foreach ($data as $d) {
            $d->status = $d->statusenabled ? 'Aktif' : 'Nonaktif';
            $d->status_c = $d->statusenabled ? 'info' : 'danger';
            $d->status_verifikasi = $d->email_verified_at ? 'Terverifikasi' : 'Belum Verifikasi';
            $d->status_verifikasi_c = $d->email_verified_at ? 'success' : 'warning';
        }

        return $this->respond(['data' => $data]);
    }

    public function verifyUserUnit(Request $r)
    {
        DB::beginTransaction();
        try {
            $id = $r['id'];
            $verified = $r->boolean('verified', true);

            if (empty($id)) {
                return $this->respond(null, 400, 'ID user tidak ditemukan');
            }

            $user = DB::table('users')->where('id', $id)->where('kdprofile', $this->kdProfile)->first();
            if (!$user) {
                return $this->respond(null, 404, 'User tidak ditemukan');
            }

            DB::table('users')->where('id', $id)->where('kdprofile', $this->kdProfile)->update([
                'email_verified_at' => $verified ? now() : null,
                'updated_at' => now(),
            ]);

            $data = $this->baseQuery()->where('u.id', $id)->first();
            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
        }

        if ($transStatus == 'true') {
            DB::commit();
            return $this->respond(
                ['data' => $data],
                200,
                $verified ? 'User berhasil diverifikasi secara manual' : 'Verifikasi user dibatalkan'
            );
        }

        DB::rollBack();
        return $this->respond(null, 400, 'Gagal memperbarui status verifikasi');
    }

    public function updateUnitUser(Request $r)
    {
        DB::beginTransaction();
        try {
            $id = $r['id'];
            $mitrafk = $r['mitrafk'];

            if (empty($id)) {
                return $this->respond(null, 400, 'ID user tidak ditemukan');
            }
            if (empty($mitrafk)) {
                return $this->respond(null, 400, 'Unit harus dipilih');
            }

            $user = DB::table('users')->where('id', $id)->where('kdprofile', $this->kdProfile)->first();
            if (!$user) {
                return $this->respond(null, 404, 'User tidak ditemukan');
            }

            $mitra = DB::table('mitra_m')->where('id', $mitrafk)->first();
            if (!$mitra) {
                return $this->respond(null, 404, 'Unit tidak ditemukan');
            }

            DB::table('users')->where('id', $id)->where('kdprofile', $this->kdProfile)->update([
                'mitrafk' => $mitrafk,
                'updated_at' => now(),
            ]);

            $data = $this->baseQuery()->where('u.id', $id)->first();
            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
        }

        if ($transStatus == 'true') {
            DB::commit();
            return $this->respond(['data' => $data], 200, 'Unit user berhasil diubah');
        }

        DB::rollBack();
        return $this->respond(null, 400, 'Gagal mengubah unit user');
    }
}
