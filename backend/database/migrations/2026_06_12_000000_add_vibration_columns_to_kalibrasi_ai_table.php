<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddVibrationColumnsToKalibrasiAiTable extends Migration
{
    public function up()
    {
        Schema::table('kalibrasi_ai', function (Blueprint $table) {
            $table->text('frekuensi')->nullable();
            $table->text('vibrasi_reference')->nullable();
            $table->text('uut')->nullable();
        });

        DB::statement(
            "UPDATE kalibrasi_ai AS kai
             SET frekuensi = kai.rentang,
                 vibrasi_reference = kai.penunjukan_standar,
                 uut = kai.pembacaan_alat
             FROM mitraregistrasidetail_t AS mtrd
             JOIN lingkupkalibrasi_m AS lk ON lk.id = mtrd.lingkupkalibrasifk
             WHERE mtrd.noorderalat = kai.noorder
               AND LOWER(TRIM(lk.lingkupkalibrasi)) = 'vibrasi'"
        );
    }

    public function down()
    {
        DB::statement(
            "UPDATE kalibrasi_ai AS kai
             SET rentang = COALESCE(kai.rentang, kai.frekuensi),
                 penunjukan_standar = COALESCE(kai.penunjukan_standar, kai.vibrasi_reference),
                 pembacaan_alat = COALESCE(kai.pembacaan_alat, kai.uut)
             FROM mitraregistrasidetail_t AS mtrd
             JOIN lingkupkalibrasi_m AS lk ON lk.id = mtrd.lingkupkalibrasifk
             WHERE mtrd.noorderalat = kai.noorder
               AND LOWER(TRIM(lk.lingkupkalibrasi)) = 'vibrasi'"
        );

        Schema::table('kalibrasi_ai', function (Blueprint $table) {
            $table->dropColumn([
                'frekuensi',
                'vibrasi_reference',
                'uut',
            ]);
        });
    }
}
