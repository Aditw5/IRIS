<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkInstruksiKerjaToUds extends Migration
{
    public function up()
    {
        Schema::table('instruksikerja_m', function (Blueprint $table) {
            if (!Schema::hasColumn('instruksikerja_m', 'udrrootfk')) {
                $table->bigInteger('udrrootfk')->nullable()->index();
            }
            if (!Schema::hasColumn('instruksikerja_m', 'udsfolderfk')) {
                $table->bigInteger('udsfolderfk')->nullable()->index();
            }
        });

        Schema::table('instruksikerja_file_t', function (Blueprint $table) {
            if (!Schema::hasColumn('instruksikerja_file_t', 'udrrecordfk')) {
                $table->bigInteger('udrrecordfk')->nullable()->index();
            }
        });
    }

    public function down()
    {
        Schema::table('instruksikerja_file_t', function (Blueprint $table) {
            if (Schema::hasColumn('instruksikerja_file_t', 'udrrecordfk')) {
                $table->dropColumn('udrrecordfk');
            }
        });

        Schema::table('instruksikerja_m', function (Blueprint $table) {
            $columns = [];
            foreach (['udrrootfk', 'udsfolderfk'] as $column) {
                if (Schema::hasColumn('instruksikerja_m', $column)) {
                    $columns[] = $column;
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
