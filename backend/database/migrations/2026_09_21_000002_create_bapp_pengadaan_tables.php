<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBappPengadaanTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bapppengadaan_t')) {
            Schema::create('bapppengadaan_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->string('pbjfk', 50);
                $table->string('hpsfk', 50);
                $table->string('ppfk', 50);
                $table->string('nomorbapp', 80);
                $table->date('tanggalbapp')->nullable();
                $table->integer('createdby')->nullable();
                $table->integer('updatedby')->nullable();
                $table->timestamps();

                $table->unique(['kdprofile', 'pbjfk'], 'uq_bapp_pengadaan_pbj');
                $table->index(['kdprofile', 'statusenabled'], 'idx_bapp_pengadaan_profile');
            });
        }

        if (!Schema::hasTable('bapppengadaanpenyedia_t')) {
            Schema::create('bapppengadaanpenyedia_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->string('bappfk', 50);
                $table->string('pbjfk', 50);
                $table->string('namapenyedia', 255);
                $table->decimal('totalharga', 18, 2)->default(0);
                $table->text('keterangan')->nullable();
                $table->integer('createdby')->nullable();
                $table->integer('updatedby')->nullable();
                $table->timestamps();

                $table->index(['kdprofile', 'bappfk', 'statusenabled'], 'idx_bapp_penyedia_bapp');
                $table->index(['kdprofile', 'pbjfk', 'statusenabled'], 'idx_bapp_penyedia_pbj');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bapppengadaanpenyedia_t');
        Schema::dropIfExists('bapppengadaan_t');
    }
}
