<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePrkMasterAndLinkPengajuanPbj extends Migration
{
    public function up()
    {
        Schema::create('prk_m', function (Blueprint $table) {
            $table->increments('id');
            $table->smallInteger('kdprofile')->nullable();
            $table->boolean('statusenabled')->default(true);
            $table->string('prk', 50)->unique();
            $table->timestamps();
        });

        $now = now();
        DB::table('prk_m')->insert(array_map(function ($prk) use ($now) {
            return [
                'kdprofile' => 1,
                'statusenabled' => true,
                'prk' => $prk,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, [
            '262N0302',
            '262N0303',
            '262N0304',
            '262N0309',
            '262N0310',
        ]));

        Schema::table('pengajuanpbj_t', function (Blueprint $table) {
            $table->unsignedInteger('prkfk')->nullable();
            $table->foreign('prkfk', 'pengajuanpbj_t_prkfk_foreign')
                ->references('id')
                ->on('prk_m')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('pengajuanpbj_t', function (Blueprint $table) {
            $table->dropForeign('pengajuanpbj_t_prkfk_foreign');
            $table->dropColumn('prkfk');
        });

        Schema::dropIfExists('prk_m');
    }
}
