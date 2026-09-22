<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddClosedVerificationAuditorMenu extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            $alamatMenu = 'module-mutu-closed-verification-auditor';
            $existing = DB::table('objekmodulaplikasi_s')
                ->where('alamaturlform', $alamatMenu)
                ->first();

            $temuanMenu = DB::table('objekmodulaplikasi_s')
                ->where('alamaturlform', 'module-mutu-temuan-ketidaksesuaian')
                ->first();
            if (!$temuanMenu) {
                return;
            }

            if ($existing) {
                $menuId = $existing->id;
            } else {
                $targetOrder = ((int) $temuanMenu->nourut) + 1;
                DB::table('objekmodulaplikasi_s')
                    ->where('kdobjekmodulaplikasihead', $temuanMenu->kdobjekmodulaplikasihead)
                    ->where('nourut', '>=', $targetOrder)
                    ->increment('nourut');

                $menuId = ((int) DB::table('objekmodulaplikasi_s')->max('id')) + 1;
                DB::table('objekmodulaplikasi_s')->insert([
                    'id' => $menuId,
                    'kdprofile' => $temuanMenu->kdprofile,
                    'statusenabled' => true,
                    'norec' => (string) Str::uuid(),
                    'fungsi' => '-',
                    'kdobjekmodulaplikasi' => (string) $menuId,
                    'keterangan' => '-',
                    'objekmodulaplikasi' => 'CLOSED VERIFICATION AUDITOR',
                    'nourut' => $targetOrder,
                    'kdobjekmodulaplikasihead' => $temuanMenu->kdobjekmodulaplikasihead,
                    'alamaturlform' => $alamatMenu,
                    'icon' => 'check-circle',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $moduleMappings = DB::table('mapobjekmodulaplikasitomodulaplikasi_s')
                ->where('objekmodulaplikasiid', $temuanMenu->id)
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
                ->where('alamaturlform', 'module-mutu-closed-verification-auditor')
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
