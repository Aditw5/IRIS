<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMobilisasiGroupToPengajuanDetailPbj extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('pengajuandetailpbj_t', 'grupmobilisasi')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->string('grupmobilisasi', 64)->nullable();
            });
        }

        if (!Schema::hasColumn('pengajuandetailpbj_t', 'judulmobilisasi')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->string('judulmobilisasi', 255)->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('pengajuandetailpbj_t', 'judulmobilisasi')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->dropColumn('judulmobilisasi');
            });
        }

        if (Schema::hasColumn('pengajuandetailpbj_t', 'grupmobilisasi')) {
            Schema::table('pengajuandetailpbj_t', function (Blueprint $table) {
                $table->dropColumn('grupmobilisasi');
            });
        }
    }
}
