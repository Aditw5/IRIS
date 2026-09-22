<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFeelerGaugeWorksheetTable extends Migration
{
    private const SUBLINGKUP_ID = 33;

    public function up()
    {
        Schema::create('lembarkerjafeelergauge_t', function (Blueprint $table) {
            $table->char('norec', 36)->primary();
            $table->boolean('statusenabled')->nullable();
            $table->string('group')->nullable();
            $table->string('rentang')->nullable();
            $table->string('rentang_satuan')->nullable();
            $table->string('penunjukan_standar')->nullable();
            $table->string('penunjukan_standar_satuan')->nullable();
            $table->string('pembacaan_alat')->nullable();
            $table->string('pembacaan_alat_satuan')->nullable();
            $table->string('koreksi')->nullable();
            $table->string('koreksi_satuan')->nullable();
            $table->string('ketidakpastian')->nullable();
            $table->string('ketidakpastian_standar')->nullable();
            $table->timestamp('tglupload')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('excelfilename')->nullable();
            $table->char('detailregistraifk', 36)->nullable();
            $table->integer('pengisifk')->nullable();
            $table->string('no')->nullable();
        });

        $existing = DB::table('sublingkupkalibrasi_m')
            ->whereRaw(
                'UPPER(TRIM(namasublingkup)) = ?',
                ['FEELER GAUGE']
            )
            ->first();

        if ($existing && (int) $existing->id !== self::SUBLINGKUP_ID) {
            throw new \RuntimeException(
                'Sub lingkup FEELER GAUGE sudah memakai ID yang berbeda.'
            );
        }

        $values = [
            'namasublingkup' => 'FEELER GAUGE',
            'deskripsi' => null,
            'kdprofile' => 1,
            'lingkupfk' => 6,
            'statusenabled' => true,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('sublingkupkalibrasi_m')
                ->where('id', self::SUBLINGKUP_ID)
                ->update($values);

            return;
        }

        DB::table('sublingkupkalibrasi_m')->insert(
            $values + [
                'id' => self::SUBLINGKUP_ID,
                'created_at' => now(),
            ]
        );
    }

    public function down()
    {
        Schema::dropIfExists('lembarkerjafeelergauge_t');

        DB::table('sublingkupkalibrasi_m')
            ->where('id', self::SUBLINGKUP_ID)
            ->whereRaw(
                'UPPER(TRIM(namasublingkup)) = ?',
                ['FEELER GAUGE']
            )
            ->delete();
    }
}