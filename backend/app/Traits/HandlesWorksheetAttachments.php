<?php

namespace App\Traits;

use App\Services\LembarKerjaAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

trait HandlesWorksheetAttachments
{
    public function saveExcelLembarKerja(Request $request)
    {
        DB::beginTransaction();
        try {
            $files = app(LembarKerjaAttachmentService::class)->store($request);
            DB::commit();
            return $this->respond(['files' => $files], 200, 'Simpan File Lembar Kerja Sukses');
        } catch (\Throwable $exception) {
            DB::rollBack();
            return $this->respond($exception->getMessage(), 400, 'Simpan Gagal');
        }
    }

    public function downloadFileTerunggah(Request $request)
    {
        if ($request->get('laporan') !== 'verifikasi') {
            return app(LembarKerjaAttachmentService::class)->download($request);
        }

        $data = DB::table('mitraregistrasidetail_t')
            ->select('excellaporanverif')
            ->where('norec', $request->get('norec'))
            ->first();
        $filename = $data->excellaporanverif ?? null;
        $path = $filename ? public_path('berkas-verifikasi/' . basename($filename)) : null;

        if (!$path || !File::exists($path)) {
            return response()->json(['message' => 'File tidak ditemukan.'], 404);
        }

        return response()->download($path, basename($filename), ['Content-Type' => File::mimeType($path)]);
    }

    public function getExcelLength(Request $request)
    {
        $files = app(LembarKerjaAttachmentService::class)->list((string) $request->get('norec'));
        $payload = [
            'namafileexcel' => $files[0]['name'] ?? null,
            'files' => $files,
            'count' => count($files),
        ];

        return $this->respond([
            // `data` dipertahankan untuk kompatibilitas dengan halaman lembar kerja lama.
            'data' => $payload,
            'files' => $files,
            'count' => count($files),
        ]);
    }

    public function deleteFileLembarKerja(Request $request)
    {
        $request->validate([
            'norec' => ['required', 'string'],
            'file_id' => ['nullable', 'integer'],
            'filename' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $result = app(LembarKerjaAttachmentService::class)->remove($request);
            DB::commit();
            return $this->respond($result, 200, 'File lembar kerja berhasil dihapus');
        } catch (\Throwable $exception) {
            DB::rollBack();
            return $this->respond($exception->getMessage(), 400, 'Hapus file gagal');
        }
    }
}
