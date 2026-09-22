<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaporanHasilAuditTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('laporanhasilaudit_t')) {
            Schema::create('laporanhasilaudit_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->unsignedSmallInteger('tahun');
                $table->date('tanggalpenetapan');
                $table->integer('createdby')->nullable();
                $table->integer('updatedby')->nullable();
                $table->timestamps();

                $table->unique(['kdprofile', 'tahun'], 'uq_laporan_hasil_audit_tahun');
                $table->index(['kdprofile', 'tahun', 'statusenabled'], 'idx_laporan_hasil_audit_tahun');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('laporanhasilaudit_t');
    }
}
