<?php

namespace App\Http\Middleware;

use App\Services\WorksheetInstructionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ValidateWorksheetInstructions
{
    public function handle(Request $request, Closure $next)
    {
        // Only worksheet writes: viewing/printing existing certificates is unaffected.
        if (!$request->isMethod('POST')
            || !preg_match('~(?:^|/)(pelaksana|penyelia)/(?:save-data-upload-lembar-kerja(?:-[a-z-]+)?|update-metadata-lembar-kerja)$~', $request->path())
            || !$request->has('daftarinstruksikerja')) {
            return $next($request);
        }

        $rows = $request->input('daftarinstruksikerja');
        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        }
        if (!is_array($rows)) {
            return $this->reject('Daftar Instruksi Kerja tidak valid. Pilih kembali IK dari daftar.');
        }

        $ids = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                return $this->reject('Daftar Instruksi Kerja tidak valid. Pilih kembali IK dari daftar.');
            }
            $id = $row['instruksikerja'] ?? null;
            if ($id === null || $id === '') {
                continue;
            }
            if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id <= 0) {
                return $this->reject('Instruksi Kerja tidak valid. Pilih kembali IK dari daftar.');
            }
            $ids[] = (int) $id;
        }

        if ($ids) {
            $profile = session('kdProfile') ?: Cache::get('kdProfile');
            // Grandfather only references already saved on this exact worksheet,
            // verified on the server. A newly added/replaced IK still needs a file.
            $savedIds = [];
            if ($request->filled('norec_detail')) {
                $savedIds = DB::table('daftarinstruksikerja_t as dik')
                    ->join('instruksikerja_m as ik', 'ik.id', '=', 'dik.idalatinstruksikerja')
                    ->where('ik.kdprofile', $profile)
                    ->where('dik.detailregistrasifk', $request->input('norec_detail'))
                    ->where('dik.statusenabled', true)
                    ->pluck('dik.idalatinstruksikerja')->map(function ($id) { return (int) $id; })->all();
            }
            $newIds = array_values(array_diff(array_unique($ids), $savedIds));
            $options = app(WorksheetInstructionService::class)->options($profile, $newIds)->keyBy('value');
            foreach ($newIds as $id) {
                $option = $options->get($id);
                if (!$option || $option['disabled']) {
                    $name = $option ? $option['number'] . ' - ' . $option['label'] : 'ID ' . $id;
                    return $this->reject('IK ' . $name . ' belum dapat digunakan. Pastikan IK aktif dan upload file PDF/Word melalui Tambah / Update IK, lalu pilih kembali.');
                }
            }
        }

        return $next($request);
    }

    private function reject(string $message)
    {
        return response()->json([
            'metaData' => ['code' => 422, 'message' => $message],
            'response' => null,
        ], 422);
    }
}
