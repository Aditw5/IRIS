<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePagumakspbjMaster extends Migration
{
    public function up()
    {
        Schema::create('pagumakspbj_m', function (Blueprint $table) {
            $table->increments('id');
            $table->smallInteger('kdprofile')->nullable();
            $table->boolean('statusenabled')->default(true);
            $table->unsignedInteger('prkfk')->unique();
            $table->string('detail', 100);
            $table->decimal('pagumaks', 20, 2);
            $table->timestamps();

            $table->foreign('prkfk', 'pagumakspbj_m_prkfk_foreign')
                ->references('id')
                ->on('prk_m')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        $definitions = [
            '262N0302' => ['detail' => 'Material ULAB', 'pagumaks' => 1066000000],
            '262N0303' => ['detail' => 'Pemeliharaan Tools', 'pagumaks' => 531000000],
            '262N0304' => ['detail' => 'Kalibrasi/Sertifikasi', 'pagumaks' => 800300000],
            '262N0309' => ['detail' => 'Jasa ULAB', 'pagumaks' => 848712351],
            '262N0310' => ['detail' => 'Workshop Center', 'pagumaks' => 250000000],
        ];

        $prkIds = DB::table('prk_m')
            ->whereIn('prk', array_keys($definitions))
            ->pluck('id', 'prk');

        $missingPrks = array_diff(array_keys($definitions), $prkIds->keys()->all());
        if (!empty($missingPrks)) {
            throw new RuntimeException(
                'Master PRK belum lengkap untuk pagu PBJ: ' . implode(', ', $missingPrks)
            );
        }

        $now = now();
        $rows = [];
        foreach ($definitions as $prk => $definition) {
            $rows[] = [
                'kdprofile' => 1,
                'statusenabled' => true,
                'prkfk' => $prkIds[$prk],
                'detail' => $definition['detail'],
                'pagumaks' => $definition['pagumaks'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('pagumakspbj_m')->insert($rows);
    }

    public function down()
    {
        Schema::dropIfExists('pagumakspbj_m');
    }
}
