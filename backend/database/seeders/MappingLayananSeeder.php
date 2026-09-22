<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MappingLayananSeeder extends Seeder
{
    public function run()
    {
        $path = database_path('seeders/data/mapping_layanan.json');
        $rows = json_decode(file_get_contents($path), true);

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $lingkupName = $this->canonicalLingkupName($row['lingkup']);
                $lingkupId = DB::table('lingkupkalibrasi_m')
                    ->whereRaw('LOWER(TRIM(lingkupkalibrasi)) = ?', [strtolower(trim($lingkupName))])
                    ->value('id');

                if (!$lingkupId) {
                    $lingkupId = ((int) DB::table('lingkupkalibrasi_m')->max('id')) + 1;
                    DB::table('lingkupkalibrasi_m')->insert([
                        'id' => $lingkupId,
                        'statusenabled' => true,
                        'lingkupkalibrasi' => $lingkupName,
                        'deskripsi' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $key = [
                    'kategori' => $row['kategori'],
                    'objectlingkupfk' => $lingkupId,
                    'namalayanan' => $row['namalayanan'],
                    'merktipe' => $row['merktipe'],
                ];

                $exists = DB::table('mappinglayanan_m')->where($key)->exists();

                if ($exists) {
                    DB::table('mappinglayanan_m')
                        ->where($key)
                        ->update(['updated_at' => now()]);
                    continue;
                }

                DB::table('mappinglayanan_m')->insert(array_merge($key, [
                    'gambar' => null,
                    'statusenabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        });
    }

    private function canonicalLingkupName($name)
    {
        $aliases = [
            'Suhu & Kelembapan' => 'Suhu & Kelembaban',
            'Verfikasi' => 'Verifikasi',
        ];

        return $aliases[$name] ?? $name;
    }
}
