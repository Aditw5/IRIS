<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddLokasiMutuToAuditFindings extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('temuanketidaksesuaiandetail_t', 'lokasimutu')) {
            Schema::table('temuanketidaksesuaiandetail_t', function (Blueprint $table) {
                $table->smallInteger('lokasimutu')->nullable();
            });
        }

        $auditMutuIds = DB::table('temuanketidaksesuaian_t')
            ->where('kodejenisaudit', 'mutu')
            ->pluck('id');

        if ($auditMutuIds->isNotEmpty()) {
            DB::table('temuanketidaksesuaiandetail_t')
                ->whereIn('auditfk', $auditMutuIds)
                ->whereBetween('urutan', [1, 8])
                ->whereNull('lokasimutu')
                ->update(['lokasimutu' => 2]);

            DB::table('temuanketidaksesuaiandetail_t')
                ->whereIn('auditfk', $auditMutuIds)
                ->whereBetween('urutan', [9, 11])
                ->whereNull('lokasimutu')
                ->update(['lokasimutu' => 1]);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('temuanketidaksesuaiandetail_t', 'lokasimutu')) {
            Schema::table('temuanketidaksesuaiandetail_t', function (Blueprint $table) {
                $table->dropColumn('lokasimutu');
            });
        }
    }
}
