<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMobilisasiHeadDetailToPengajuanDetailPbj extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('pengajuandetailpbj_t', 'uraianmobilisasi')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->text('uraianmobilisasi')->nullable();
            });
        }

        if (!Schema::hasColumn('pengajuandetailpbj_t', 'keteranganmobilisasi')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->text('keteranganmobilisasi')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('pengajuandetailpbj_t', 'keteranganmobilisasi')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->dropColumn('keteranganmobilisasi');
            });
        }

        if (Schema::hasColumn('pengajuandetailpbj_t', 'uraianmobilisasi')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->dropColumn('uraianmobilisasi');
            });
        }
    }
}
