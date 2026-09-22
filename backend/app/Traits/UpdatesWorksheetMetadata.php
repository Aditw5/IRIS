<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait UpdatesWorksheetMetadata
{
    public function updateMetadataLembarKerja(Request $request)
    {
        $request->validate([
            'norec_detail' => ['required'],
            'tglkalibrasi' => ['required', 'date'],
            'tempatKalibrasi' => ['required'],
            'suhu' => ['required'],
            'kelembabanRelatif' => ['required'],
            'notes' => ['nullable', 'string'],
            'fileMeter' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        DB::beginTransaction();
        try {
            $detail = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request->norec_detail)
                ->first();
            if (!$detail) {
                throw new \RuntimeException('Data registrasi alat tidak ditemukan.');
            }

            $imageName = $detail->gambarsuhu ?? null;
            if ($request->hasFile('fileMeter')) {
                $image = $request->file('fileMeter');
                $imageName = now()->format('YmdHisv') . '_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $image->getClientOriginalName());
                $directory = public_path('gambar-suhu');
                if (!File::isDirectory($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                $image->move($directory, $imageName);
            }

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $request->norec_detail)
                ->update([
                    'tglkalibrasilembarkerja' => $request->tglkalibrasi,
                    'tempatKalibrasilembarkerja' => $request->tempatKalibrasi,
                    'suhulembarkerja' => $request->suhu,
                    'kelembabanRelatiflembarkerja' => $request->kelembabanRelatif,
                    'noteslembarkerja' => $request->notes,
                    'gambarsuhu' => $imageName,
                    'updated_at' => now(),
                ]);

            $instructions = json_decode($request->input('daftarinstruksikerja', '[]'), true) ?: [];
            DB::table('daftarinstruksikerja_t')->where('detailregistrasifk', $request->norec_detail)->delete();
            foreach ($instructions as $instruction) {
                $id = $instruction['instruksikerja'] ?? null;
                if (!$id) continue;
                DB::table('daftarinstruksikerja_t')->insert([
                    'norec' => (string) Str::uuid(),
                    'statusenabled' => true,
                    'idalatinstruksikerja' => $id,
                    'detailregistrasifk' => $request->norec_detail,
                    'petugas' => $this->getPegawaiId(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $standards = json_decode($request->input('daftarperalatanstandar', '[]'), true) ?: [];
            DB::table('daftaralatstandar_t')->where('detailregistrasifk', $request->norec_detail)->delete();
            foreach ($standards as $standard) {
                $id = $standard['peralatanstandar'] ?? null;
                if (!$id) continue;
                DB::table('daftaralatstandar_t')->insert([
                    'norec' => (string) Str::uuid(),
                    'statusenabled' => true,
                    'alatstandarfk' => $id,
                    'detailregistrasifk' => $request->norec_detail,
                    'petugas' => $this->getPegawaiId(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return $this->respond(['gambarsuhu' => $imageName], 200, 'Metadata lembar kerja berhasil diperbarui');
        } catch (\Throwable $exception) {
            DB::rollBack();
            return $this->respond($exception->getMessage(), 400, 'Metadata lembar kerja gagal diperbarui');
        }
    }
}
