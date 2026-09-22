<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceToRbkRealisasi extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('rbk_realisasi_t', 'sumberrealisasi')) {
            Schema::table('rbk_realisasi_t', function (Blueprint $table) {
                // Data sebelum fitur hybrid adalah input lama yang umumnya sudah
                // terwakili oleh PBJ. Jangan ikutkan otomatis di halaman RBK baru.
                $table->string('sumberrealisasi', 20)
                    ->default('legacy')
                    ->index();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('rbk_realisasi_t', 'sumberrealisasi')) {
            Schema::table('rbk_realisasi_t', function (Blueprint $table) {
                $table->dropColumn('sumberrealisasi');
            });
        }
    }
}
