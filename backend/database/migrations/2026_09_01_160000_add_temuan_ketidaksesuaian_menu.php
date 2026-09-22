<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddTemuanKetidaksesuaianMenu extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            $existing = DB::table('objekmodulaplikasi_s')
                ->where('alamaturlform', 'module-mutu-temuan-ketidaksesuaian')
                ->first();

            if ($existing) {
                $menuId = $existing->id;
            } else {
                $cvMenu = DB::table('objekmodulaplikasi_s')
                    ->where('alamaturlform', 'module-mutu-cv-pegawai')
                    ->first();

                if (!$cvMenu) {
                    return;
                }

                $menuId = ((int) DB::table('objekmodulaplikasi_s')->max('id')) + 1;
                $nextOrder = ((int) DB::table('objekmodulaplikasi_s')
                    ->where('kdobjekmodulaplikasihead', $cvMenu->kdobjekmodulaplikasihead)
                    ->max('nourut')) + 1;

                DB::table('objekmodulaplikasi_s')->insert([
                    'id' => $menuId,
                    'kdprofile' => $cvMenu->kdprofile,
                    'statusenabled' => true,
                    'norec' => (string) Str::uuid(),
                    'fungsi' => '-',
                    'kdobjekmodulaplikasi' => (string) $menuId,
                    'keterangan' => '-',
                    'objekmodulaplikasi' => 'TEMUAN KETIDAKSESUAIAN',
                    'nourut' => $nextOrder,
                    'kdobjekmodulaplikasihead' => $cvMenu->kdobjekmodulaplikasihead,
                    'alamaturlform' => 'module-mutu-temuan-ketidaksesuaian',
                    'icon' => 'alert-triangle',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $moduleMappings = DB::table('mapobjekmodulaplikasitomodulaplikasi_s')
                ->where('objekmodulaplikasiid', function ($query) {
                    $query->select('id')
                        ->from('objekmodulaplikasi_s')
                        ->where('alamaturlform', 'module-mutu-cv-pegawai')
                        ->limit(1);
                })
                ->where('statusenabled', true)
                ->get();

            foreach ($moduleMappings as $mapping) {
                $hasMapping = DB::table('mapobjekmodulaplikasitomodulaplikasi_s')
                    ->where('modulaplikasiid', $mapping->modulaplikasiid)
                    ->where('objekmodulaplikasiid', $menuId)
                    ->exists();

                if (!$hasMapping) {
                    DB::table('mapobjekmodulaplikasitomodulaplikasi_s')->insert([
                        'id' => ((int) DB::table('mapobjekmodulaplikasitomodulaplikasi_s')->max('id')) + 1,
                        'kdprofile' => $mapping->kdprofile,
                        'statusenabled' => true,
                        'norec' => (string) Str::uuid(),
                        'modulaplikasiid' => $mapping->modulaplikasiid,
                        'objekmodulaplikasiid' => $menuId,
                    ]);
                }
            }
        });
    }

    public function down()
    {
        DB::transaction(function () {
            $menu = DB::table('objekmodulaplikasi_s')
                ->where('alamaturlform', 'module-mutu-temuan-ketidaksesuaian')
                ->first();

            if (!$menu) {
                return;
            }

            DB::table('mapobjekmodulaplikasitomodulaplikasi_s')
                ->where('objekmodulaplikasiid', $menu->id)
                ->delete();
            DB::table('objekmodulaplikasi_s')->where('id', $menu->id)->delete();
        });
    }
}
