<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCatatanVerifikasiToClosedVerificationAuditor extends Migration
{
    public function up()
    {
        if (Schema::hasTable('closedverificationauditor_t')
            && !Schema::hasColumn('closedverificationauditor_t', 'catatanverifikasi')) {
            Schema::table('closedverificationauditor_t', function (Blueprint $table) {
                $table->text('catatanverifikasi')->nullable()->after('statusverifikasi');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('closedverificationauditor_t')
            && Schema::hasColumn('closedverificationauditor_t', 'catatanverifikasi')) {
            Schema::table('closedverificationauditor_t', function (Blueprint $table) {
                $table->dropColumn('catatanverifikasi');
            });
        }
    }
}
