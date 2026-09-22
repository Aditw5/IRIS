<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePpPengadaanTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pppengadaan_t')) {
            Schema::create('pppengadaan_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->string('pbjfk', 50);
                $table->string('hpsfk', 50);
                $table->string('nomorpp', 80);
                $table->date('tanggalpp')->nullable();
                $table->date('bataspemasukan')->nullable();
                $table->integer('createdby')->nullable();
                $table->integer('updatedby')->nullable();
                $table->timestamps();

                $table->unique(['kdprofile', 'pbjfk'], 'uq_pp_pengadaan_pbj');
                $table->index(['kdprofile', 'statusenabled'], 'idx_pp_pengadaan_profile');
            });
        }

        if (!Schema::hasTable('pppengadaanfile_t')) {
            Schema::create('pppengadaanfile_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->string('ppfk', 50);
                $table->string('pbjfk', 50);
                $table->string('namaasli', 255);
                $table->string('namafile', 255);
                $table->string('mimetype', 120)->nullable();
                $table->unsignedBigInteger('ukuran')->default(0);
                $table->integer('createdby')->nullable();
                $table->timestamps();

                $table->index(['kdprofile', 'pbjfk', 'statusenabled'], 'idx_pp_file_pbj');
                $table->index(['ppfk', 'statusenabled'], 'idx_pp_file_pp');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('pppengadaanfile_t');
        Schema::dropIfExists('pppengadaan_t');
    }
}
