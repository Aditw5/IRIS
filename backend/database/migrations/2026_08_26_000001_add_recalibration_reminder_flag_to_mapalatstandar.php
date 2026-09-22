<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('mapalatstandar_m', 'pengingat_rekalibrasi_aktif')) {
            return;
        }

        Schema::table('mapalatstandar_m', function (Blueprint $table) {
            $table->boolean('pengingat_rekalibrasi_aktif')->default(true);
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('mapalatstandar_m', 'pengingat_rekalibrasi_aktif')) {
            return;
        }

        Schema::table('mapalatstandar_m', function (Blueprint $table) {
            $table->dropColumn('pengingat_rekalibrasi_aktif');
        });
    }
};
