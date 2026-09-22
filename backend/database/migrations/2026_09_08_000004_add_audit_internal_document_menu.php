<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddAuditInternalDocumentMenu extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            $alamatMenu = 'module-mutu-dokumen-audit-internal';
            $existing = DB::table('objekmodulaplikasi_s')->where('alamaturlform', $alamatMenu)->first();
            $closedMenu = DB::table('objekmodulaplikasi_s')
                ->where('alamaturlform', 'module-mutu-closed-verification-auditor')
                ->first();
            if (!$closedMenu) {
                return;
            }

            if ($existing) {
                $menuId = $existing->id;
            } else {
                $targetOrder = ((int) $closedMenu->nourut) + 1;
                DB::table('objekmodulaplikasi_s')
                    ->where('kdobjekmodulaplikasihead', $closedMenu->kdobjekmodulaplikasihead)
                    ->where('nourut', '>=', $targetOrder)
                    ->increment('nourut');

                $menuId = ((int) DB::table('objekmodulaplikasi_s')->max('id')) + 1;
                DB::table('objekmodulaplikasi_s')->insert([
                    'id' => $menuId,
                    'kdprofile' => $closedMenu->kdprofile,
                    'statusenabled' => true,
                    'norec' => (string) Str::uuid(),
                    'fungsi' => '-',
                    'kdobjekmodulaplikasi' => (string) $menuId,
                    'keterangan' => '-',
                    'objekmodulaplikasi' => 'DOKUMEN AUDIT INTERNAL',
                    'nourut' => $targetOrder,
                    'kdobjekmodulaplikasihead' => $closedMenu->kdobjekmodulaplikasihead,
                    'alamaturlform' => $alamatMenu,
                    'icon' => 'folder',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $moduleMappings = DB::table('mapobjekmodulaplikasitomodulaplikasi_s')
                ->where('objekmodulaplikasiid', $closedMenu->id)
                ->where('statusenabled', true)
                ->get();

            foreach ($moduleMappings as $mapping) {
                if (!DB::table('mapobjekmodulaplikasitomodulaplikasi_s')
                    ->where('modulaplikasiid', $mapping->modulaplikasiid)
                    ->where('objekmodulaplikasiid', $menuId)
                    ->exists()) {
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
                ->where('alamaturlform', 'module-mutu-dokumen-audit-internal')
                ->first();
            if (!$menu) {
                return;
            }

            DB::table('mapobjekmodulaplikasitomodulaplikasi_s')
                ->where('objekmodulaplikasiid', $menu->id)->delete();
            DB::table('objekmodulaplikasi_s')->where('id', $menu->id)->delete();
            DB::table('objekmodulaplikasi_s')
                ->where('kdobjekmodulaplikasihead', $menu->kdobjekmodulaplikasihead)
                ->where('nourut', '>', $menu->nourut)
                ->decrement('nourut');
        });
    }
}
