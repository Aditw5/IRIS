<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddThermalColumnsToKalibrasiAiTable extends Migration
{
    public function up()
    {
        Schema::table('kalibrasi_ai', function (Blueprint $table) {
            $table->text('set_point')->nullable();
            $table->text('temperature_reference')->nullable();
            $table->text('temperature_uut')->nullable();
        });

        DB::statement(
            "UPDATE kalibrasi_ai AS kai
             SET set_point = kai.rentang,
                 temperature_reference = kai.penunjukan_standar,
                 temperature_uut = kai.pembacaan_alat
             FROM mitraregistrasidetail_t AS mtrd
             JOIN lingkupkalibrasi_m AS lk ON lk.id = mtrd.lingkupkalibrasifk
             WHERE mtrd.noorderalat = kai.noorder
               AND LOWER(TRIM(lk.lingkupkalibrasi)) IN ('suhu', 'suhu & kelembaban')"
        );
    }

    public function down()
    {
        DB::statement(
            "UPDATE kalibrasi_ai AS kai
             SET rentang = COALESCE(kai.rentang, kai.set_point),
                 penunjukan_standar = COALESCE(kai.penunjukan_standar, kai.temperature_reference),
                 pembacaan_alat = COALESCE(kai.pembacaan_alat, kai.temperature_uut)
             FROM mitraregistrasidetail_t AS mtrd
             JOIN lingkupkalibrasi_m AS lk ON lk.id = mtrd.lingkupkalibrasifk
             WHERE mtrd.noorderalat = kai.noorder
               AND LOWER(TRIM(lk.lingkupkalibrasi)) IN ('suhu', 'suhu & kelembaban')"
        );

        Schema::table('kalibrasi_ai', function (Blueprint $table) {
            $table->dropColumn([
                'set_point',
                'temperature_reference',
                'temperature_uut',
            ]);
        });
    }
}
