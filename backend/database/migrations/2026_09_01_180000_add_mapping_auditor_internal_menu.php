<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddMappingAuditorInternalMenu extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            $existing = DB::table('objekmodulaplikasi_s')
                ->where('alamaturlform', 'module-mutu-mapping-auditor')
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

                $targetOrder = ((int) $cvMenu->nourut) + 1;
                DB::table('objekmodulaplikasi_s')
                    ->where('kdobjekmodulaplikasihead', $cvMenu->kdobjekmodulaplikasihead)
                    ->where('nourut', '>=', $targetOrder)
                    ->increment('nourut');

                $menuId = ((int) DB::table('objekmodulaplikasi_s')->max('id')) + 1;
                DB::table('objekmodulaplikasi_s')->insert([
                    'id' => $menuId,
                    'kdprofile' => $cvMenu->kdprofile,
                    'statusenabled' => true,
                    'norec' => (string) Str::uuid(),
                    'fungsi' => '-',
                    'kdobjekmodulaplikasi' => (string) $menuId,
                    'keterangan' => '-',
                    'objekmodulaplikasi' => 'MAPPING AUDITOR INTERNAL',
                    'nourut' => $targetOrder,
                    'kdobjekmodulaplikasihead' => $cvMenu->kdobjekmodulaplikasihead,
                    'alamaturlform' => 'module-mutu-mapping-auditor',
                    'icon' => 'users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $cvMenuId = DB::table('objekmodulaplikasi_s')
                ->where('alamaturlform', 'module-mutu-cv-pegawai')
                ->value('id');
            $moduleMappings = DB::table('mapobjekmodulaplikasitomodulaplikasi_s')
                ->where('objekmodulaplikasiid', $cvMenuId)
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
                ->where('alamaturlform', 'module-mutu-mapping-auditor')
                ->first();
            if (!$menu) {
                return;
            }

            DB::table('mapobjekmodulaplikasitomodulaplikasi_s')
                ->where('objekmodulaplikasiid', $menu->id)
                ->delete();
            DB::table('objekmodulaplikasi_s')->where('id', $menu->id)->delete();
            DB::table('objekmodulaplikasi_s')
                ->where('kdobjekmodulaplikasihead', $menu->kdobjekmodulaplikasihead)
                ->where('nourut', '>', $menu->nourut)
                ->decrement('nourut');
        });
    }
}
