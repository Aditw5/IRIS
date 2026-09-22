<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('instruksikerja_m', 'lingkupfk')) {
            Schema::table('instruksikerja_m', function (Blueprint $table) {
                // Nullable hanya untuk menjaga data lama tetap dapat dimigrasikan.
                // Record baru dan hasil update diwajibkan mengisi lingkup oleh API.
                $table->integer('lingkupfk')->nullable()->index();
            });
        }

        if (!Schema::hasTable('instruksikerja_file_t')) {
            Schema::create('instruksikerja_file_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->smallInteger('kdprofile')->index();
                $table->integer('instruksikerjafk')->index();
                $table->unsignedInteger('versi');
                $table->string('namaasli', 255);
                $table->string('namafile', 255);
                $table->string('mimetype', 150)->nullable();
                $table->unsignedBigInteger('ukuran')->nullable();
                $table->integer('petugasfk')->nullable();
                $table->string('namapetugas', 255)->nullable();
                $table->boolean('statusenabled')->default(true);
                $table->timestamps();

                $table->unique(
                    ['kdprofile', 'instruksikerjafk', 'versi'],
                    'instruksi_kerja_file_versi_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instruksikerja_file_t');

        if (Schema::hasColumn('instruksikerja_m', 'lingkupfk')) {
            Schema::table('instruksikerja_m', function (Blueprint $table) {
                $table->dropColumn('lingkupfk');
            });
        }
    }
};
