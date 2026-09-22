<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemuanKetidaksesuaianTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('paktaintegritasauditor_t')) {
            Schema::create('paktaintegritasauditor_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->integer('loginuserfk');
                $table->integer('pegawaifk');
                $table->string('nama', 255);
                $table->string('nid', 100)->nullable();
                $table->string('jabatan', 255)->nullable();
                $table->date('tanggal');
                $table->string('lokasi', 50);
                $table->longText('tandatangan');
                $table->timestamps();

                $table->unique(['kdprofile', 'pegawaifk'], 'uq_pakta_profile_pegawai');
                $table->index('loginuserfk', 'idx_pakta_loginuser');
            });
        }

        if (!Schema::hasTable('temuanketidaksesuaian_t')) {
            Schema::create('temuanketidaksesuaian_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->string('kodejenisaudit', 80);
                $table->string('jenisaudit', 255);
                $table->string('lingkup', 100);
                $table->string('lokasi', 50);
                $table->date('tanggalaudit');
                $table->string('namalpk', 255);
                $table->string('standaracuan', 255);
                $table->integer('pengisifk');
                $table->string('namapengisi', 255);
                $table->timestamps();

                $table->index(['kdprofile', 'tanggalaudit', 'statusenabled'], 'idx_temuan_tahun');
                $table->index('kodejenisaudit', 'idx_temuan_jenis');
            });
        }

        if (!Schema::hasTable('temuanketidaksesuaianpeserta_t')) {
            Schema::create('temuanketidaksesuaianpeserta_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('auditfk');
                $table->boolean('statusenabled')->default(true);
                $table->string('tipe', 20);
                $table->integer('pegawaifk')->nullable();
                $table->string('nama', 255);
                $table->string('tugas', 255);
                $table->unsignedSmallInteger('urutan')->default(1);
                $table->longText('tandatangan')->nullable();
                $table->timestamps();

                $table->index(['auditfk', 'tipe', 'statusenabled'], 'idx_temuan_peserta');
                $table->foreign('auditfk', 'fk_temuan_peserta_audit')
                    ->references('id')
                    ->on('temuanketidaksesuaian_t')
                    ->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('temuanketidaksesuaiandetail_t')) {
            Schema::create('temuanketidaksesuaiandetail_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('auditfk');
                $table->boolean('statusenabled')->default(true);
                $table->unsignedSmallInteger('urutan')->default(1);
                $table->string('bagian', 150);
                $table->string('klausul', 100);
                $table->unsignedSmallInteger('kategoritemuan');
                $table->text('uraianketidaksesuaian');
                $table->integer('auditorfk');
                $table->string('auditor', 255);
                $table->integer('createdby');
                $table->timestamps();

                $table->index(['auditfk', 'statusenabled', 'urutan'], 'idx_temuan_detail');
                $table->index('auditorfk', 'idx_temuan_auditor');
                $table->foreign('auditfk', 'fk_temuan_detail_audit')
                    ->references('id')
                    ->on('temuanketidaksesuaian_t')
                    ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('temuanketidaksesuaiandetail_t');
        Schema::dropIfExists('temuanketidaksesuaianpeserta_t');
        Schema::dropIfExists('temuanketidaksesuaian_t');
        Schema::dropIfExists('paktaintegritasauditor_t');
    }
}
