<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJenisPenyimpanganToLembarkerjadialindicatorTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('lembarkerjadialindicator_t')) {
            return;
        }

        Schema::table('lembarkerjadialindicator_t', function (Blueprint $table) {
            if (!Schema::hasColumn('lembarkerjadialindicator_t', 'jenis_penyimpangan')) {
                $table->string('jenis_penyimpangan', 20)->default('naik_turun');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('lembarkerjadialindicator_t')) {
            return;
        }

        Schema::table('lembarkerjadialindicator_t', function (Blueprint $table) {
            if (Schema::hasColumn('lembarkerjadialindicator_t', 'jenis_penyimpangan')) {
                $table->dropColumn('jenis_penyimpangan');
            }
        });
    }
}
