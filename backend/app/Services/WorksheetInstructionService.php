<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorksheetInstructionService
{
    public function options($profile, ?array $ids = null): Collection
    {
        $query = DB::table('instruksikerja_m')->where('kdprofile', $profile);
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }
        $instructions = $query->orderBy('namainstruksikerja')->get();
        $files = DB::table('instruksikerja_file_t')
            ->where('kdprofile', $profile)
            ->where('statusenabled', true)
            ->whereIn('instruksikerjafk', $instructions->pluck('id'))
            ->get(['instruksikerjafk', 'namafile'])
            ->filter(function ($file) {
                $filename = basename((string) $file->namafile);
                if (!$filename || !in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['pdf', 'doc', 'docx'], true)) {
                    return false;
                }
                // The committed upload history is the source of truth, as in
                // Master IK. The API and document server may use different disks.
                return true;
            })->groupBy('instruksikerjafk');

        return $instructions->map(function ($instruction) use ($files) {
            $active = filter_var($instruction->statusenabled, FILTER_VALIDATE_BOOLEAN);
            $count = $files->get($instruction->id, collect())->count();
            return [
                'value' => (int) $instruction->id,
                'label' => $instruction->namainstruksikerja,
                'number' => $instruction->noisntruksikerja,
                'active' => $active,
                'fileCount' => $count,
                'disabled' => !$active || $count === 0,
                'reason' => !$active ? 'IK nonaktif' : ($count === 0 ? 'Upload file IK terlebih dahulu' : ''),
            ];
        });
    }
}
