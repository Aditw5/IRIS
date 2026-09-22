<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LembarKerjaAttachmentService
{
    private const FOLDER = 'berkas-mitra-excel';
    private const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public function store(Request $request): array
    {
        $detail = DB::table('mitraregistrasidetail_t')
            ->select('namafileexcel')
            ->where('norec', $request->norec)
            ->first();
        if (!$detail) {
            throw new \InvalidArgumentException('Data registrasi alat tidak ditemukan.');
        }

        $files = $request->file('fileMitraExcel', []);
        $files = is_array($files) ? $files : [$files];
        $files = array_values(array_filter($files));

        if (count($files) === 0) {
            throw new \InvalidArgumentException('Minimal satu file harus diunggah.');
        }

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                throw new \InvalidArgumentException('File harus berupa Excel (.xlsx, .xls) atau CSV.');
            }
            if ($file->getSize() > 10000000) {
                throw new \InvalidArgumentException('Ukuran maksimal setiap file adalah 10 MB.');
            }
        }

        $stored = [];
        $directory = public_path(self::FOLDER);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $safeOriginal = preg_replace('/[^A-Za-z0-9._-]+/', '_', $originalName);
            $filename = now()->format('YmdHisv') . '_' . Str::random(8) . '_' . $safeOriginal;
            $file->move($directory, $filename);

            $id = DB::table('lembar_kerja_attachment_t')->insertGetId([
                'detailregistrasifk' => $request->norec,
                'namafile' => $filename,
                'namaasli' => $originalName,
                'ukuran' => File::size($directory . DIRECTORY_SEPARATOR . $filename),
                'mimetype' => File::mimeType($directory . DIRECTORY_SEPARATOR . $filename),
                'statusenabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stored[] = ['id' => $id, 'name' => $originalName, 'stored_name' => $filename];
        }

        // Kolom lama tetap diisi agar layar/versi aplikasi lama masih mengenali adanya lampiran.
        if ($detail && empty($detail->namafileexcel) && isset($stored[0])) {
            DB::table('mitraregistrasidetail_t')
                ->where('norec', $request->norec)
                ->update(['namafileexcel' => $stored[0]['stored_name'], 'updated_at' => now()]);
        }

        return $stored;
    }

    public function list(string $norec): array
    {
        $attachments = DB::table('lembar_kerja_attachment_t')
            ->select('id', 'namafile', 'namaasli', 'ukuran', 'mimetype', 'created_at')
            ->where('detailregistrasifk', $norec)
            ->where('statusenabled', true)
            ->orderBy('created_at')
            ->get();

        $files = [];
        $storedNames = [];
        foreach ($attachments as $attachment) {
            $storedNames[] = $attachment->namafile;
            $files[] = [
                'id' => $attachment->id,
                'name' => $attachment->namaasli ?: $attachment->namafile,
                'stored_name' => $attachment->namafile,
                'size' => $attachment->ukuran,
                'mime_type' => $attachment->mimetype,
                'uploaded_at' => $attachment->created_at,
                'legacy' => false,
            ];
        }

        $legacy = DB::table('mitraregistrasidetail_t')
            ->select('namafileexcel')
            ->where('norec', $norec)
            ->first();

        if ($legacy && !empty($legacy->namafileexcel) && !in_array($legacy->namafileexcel, $storedNames, true)) {
            $legacyPath = public_path(self::FOLDER . DIRECTORY_SEPARATOR . $legacy->namafileexcel);
            $files[] = [
                'id' => null,
                'name' => $this->originalName($legacy->namafileexcel),
                'stored_name' => $legacy->namafileexcel,
                'size' => File::exists($legacyPath) ? File::size($legacyPath) : null,
                'mime_type' => File::exists($legacyPath) ? File::mimeType($legacyPath) : null,
                'uploaded_at' => null,
                'legacy' => true,
            ];
        }

        return $files;
    }

    public function resolve(Request $request): ?array
    {
        $norec = (string) $request->get('norec');
        $fileId = $request->get('file_id');

        if ($fileId !== null && $fileId !== '') {
            $attachment = DB::table('lembar_kerja_attachment_t')
                ->where('id', $fileId)
                ->where('detailregistrasifk', $norec)
                ->where('statusenabled', true)
                ->first();

            if ($attachment) {
                return ['stored_name' => $attachment->namafile, 'name' => $attachment->namaasli ?: $attachment->namafile];
            }
        }

        $requestedName = basename((string) $request->get('filename', ''));
        if ($requestedName !== '') {
            $match = collect($this->list($norec))->firstWhere('stored_name', $requestedName);
            if ($match) {
                return ['stored_name' => $match['stored_name'], 'name' => $match['name']];
            }
        }

        $files = $this->list($norec);
        if (count($files) === 1) {
            return ['stored_name' => $files[0]['stored_name'], 'name' => $files[0]['name']];
        }

        return null;
    }

    public function download(Request $request)
    {
        $file = $this->resolve($request);
        if (!$file) {
            return response()->json(['message' => 'File tidak ditemukan atau file belum dipilih.'], 404);
        }

        $path = public_path(self::FOLDER . DIRECTORY_SEPARATOR . basename($file['stored_name']));
        if (!File::exists($path)) {
            return response()->json(['message' => 'File tidak ditemukan di direktori.'], 404);
        }

        return response()->download($path, $file['name'], [
            'Content-Type' => File::mimeType($path),
        ]);
    }

    public function remove(Request $request): array
    {
        $norec = (string) $request->input('norec');
        $detail = DB::table('mitraregistrasidetail_t')
            ->select('namafileexcel')
            ->where('norec', $norec)
            ->first();

        if (!$detail) {
            throw new \InvalidArgumentException('Data registrasi alat tidak ditemukan.');
        }

        $attachment = null;
        $fileId = $request->input('file_id');
        if ($fileId !== null && $fileId !== '') {
            $attachment = DB::table('lembar_kerja_attachment_t')
                ->where('id', $fileId)
                ->where('detailregistrasifk', $norec)
                ->where('statusenabled', true)
                ->first();
        }

        if ($attachment) {
            $storedName = $attachment->namafile;
            $displayName = $attachment->namaasli ?: $attachment->namafile;
            DB::table('lembar_kerja_attachment_t')
                ->where('id', $attachment->id)
                ->update(['statusenabled' => false, 'updated_at' => now()]);
        } else {
            $storedName = basename((string) $request->input('filename', ''));
            if ($storedName === '' || $storedName !== ($detail->namafileexcel ?? null)) {
                throw new \InvalidArgumentException('File lembar kerja tidak ditemukan.');
            }
            $displayName = $this->originalName($storedName);
        }

        if (($detail->namafileexcel ?? null) === $storedName) {
            $replacement = DB::table('lembar_kerja_attachment_t')
                ->select('namafile')
                ->where('detailregistrasifk', $norec)
                ->where('statusenabled', true)
                ->orderBy('created_at')
                ->first();

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $norec)
                ->update([
                    'namafileexcel' => $replacement->namafile ?? null,
                    'updated_at' => now(),
                ]);
        }

        $stillUsed = DB::table('lembar_kerja_attachment_t')
            ->where('namafile', $storedName)
            ->where('statusenabled', true)
            ->exists();
        $stillUsedAsLegacy = DB::table('mitraregistrasidetail_t')
            ->where('namafileexcel', $storedName)
            ->exists();
        if (!$stillUsed && !$stillUsedAsLegacy) {
            $path = public_path(self::FOLDER . DIRECTORY_SEPARATOR . basename($storedName));
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        return [
            'name' => $displayName,
            'files' => $this->list($norec),
        ];
    }

    private function originalName(string $storedName): string
    {
        return preg_replace('/^(?:\d{10,17}_|\d{14,17}_[A-Za-z0-9]{8}_)/', '', $storedName) ?: $storedName;
    }
}
