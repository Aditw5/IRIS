<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHpsPengadaanTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hpspengadaan_t')) {
            Schema::create('hpspengadaan_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->string('pbjfk', 50);
                $table->unsignedInteger('nourut');
                $table->unsignedSmallInteger('tahun');
                $table->string('nomorhps', 80);
                $table->date('tanggalhps');
                $table->integer('createdby')->nullable();
                $table->integer('updatedby')->nullable();
                $table->timestamps();

                $table->unique(['kdprofile', 'pbjfk'], 'uq_hps_pengadaan_pbj');
                $table->unique(['kdprofile', 'tahun', 'nourut'], 'uq_hps_pengadaan_nomor');
                $table->index(['kdprofile', 'tahun', 'statusenabled'], 'idx_hps_pengadaan_tahun');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('hpspengadaan_t');
    }
}
