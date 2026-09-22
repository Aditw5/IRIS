<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KalibrasiAiVibrasiSeeder extends Seeder
{
    public function run()
    {
        $lingkupId = DB::table('lingkupkalibrasi_m')
            ->whereRaw('LOWER(TRIM(lingkupkalibrasi)) = ?', ['vibrasi'])
            ->value('id');

        if (!$lingkupId) {
            throw new RuntimeException('Master lingkup Vibrasi tidak ditemukan.');
        }

        $existing = DB::table('sublingkupkalibrasi_m')
            ->whereRaw('UPPER(TRIM(namasublingkup)) = ?', ['KALIBRASI AI VIBRASI'])
            ->first();

        if ($existing) {
            DB::table('sublingkupkalibrasi_m')
                ->where('id', $existing->id)
                ->update([
                    'lingkupfk' => $lingkupId,
                    'statusenabled' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('sublingkupkalibrasi_m')->insert([
            'id' => ((int) DB::table('sublingkupkalibrasi_m')->max('id')) + 1,
            'namasublingkup' => 'KALIBRASI AI VIBRASI',
            'deskripsi' => null,
            'kdprofile' => 1,
            'lingkupfk' => $lingkupId,
            'statusenabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
