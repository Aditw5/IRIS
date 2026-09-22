<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRbkSyncMarkersToPbj extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('pengajuanpbj_t', 'sinkronrbk')) {
            Schema::table('pengajuanpbj_t', function (Blueprint $table) {
                $table->boolean('sinkronrbk')->default(true);
            });
        }

        if (!Schema::hasColumn('pengajuandetailpbj_t', 'kategorirbk')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->string('kategorirbk', 20)->nullable()->index();
            });
        }

        DB::statement(<<<'SQL'
            UPDATE pengajuandetailpbj_t AS detail
            SET kategorirbk = CASE
                WHEN NULLIF(BTRIM(COALESCE(detail.grupmobilisasi, '')), '') IS NOT NULL
                    THEN 'mobilisasi'
                WHEN UPPER(COALESCE(jenis.jenispbj, '')) = 'MATERIAL'
                    THEN 'material'
                WHEN LOWER(
                    COALESCE(detail.namaitem, '') || ' ' ||
                    COALESCE(detail.uraianitem, '') || ' ' ||
                    COALESCE(detail.judulmobilisasi, '')
                ) ~ '(mobilisasi|mobilisai|demobilisasi|transportasi|pengantaran|pengiriman|tiket|travel|ojek|bagasi)'
                    THEN 'mobilisasi'
                ELSE 'manpower'
            END
            FROM pengajuanpbj_t AS pbj
            LEFT JOIN jenispbj_m AS jenis ON jenis.id = pbj.jenispbj
            WHERE pbj.norec = detail.noregpbjfk
              AND detail.kategorirbk IS NULL
        SQL);
    }

    public function down()
    {
        if (Schema::hasColumn('pengajuandetailpbj_t', 'kategorirbk')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->dropColumn('kategorirbk');
            });
        }

        if (Schema::hasColumn('pengajuanpbj_t', 'sinkronrbk')) {
            Schema::table('pengajuanpbj_t', function (Blueprint $table) {
                $table->dropColumn('sinkronrbk');
            });
        }
    }
}
