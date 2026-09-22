<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('instruksikerja_m', 'lokasifk')) {
            Schema::table('instruksikerja_m', function (Blueprint $table) {
                // Nullable untuk data lama yang kode lokasinya tidak dapat dipastikan.
                // API mewajibkan lokasi pada data baru dan setiap update.
                $table->integer('lokasifk')->nullable()->index();
            });
        }

        $jakartaId = DB::table('lokasikalibrasi_m')
            ->whereRaw('LOWER(lokasi) = ?', ['jakarta'])
            ->value('id');
        $gresikId = DB::table('lokasikalibrasi_m')
            ->whereRaw('LOWER(lokasi) = ?', ['gresik'])
            ->value('id');

        // Kode setelah tanda hubung terakhir: J... = Jakarta, G... = Gresik.
        // Pola lain sengaja tidak dipetakan agar dapat ditentukan manual.
        if ($jakartaId) {
            DB::table('instruksikerja_m')
                ->whereNull('lokasifk')
                ->whereRaw("noisntruksikerja ~* '-J[^-]*$'")
                ->update(['lokasifk' => (int) $jakartaId, 'updated_at' => now()]);
        }
        if ($gresikId) {
            DB::table('instruksikerja_m')
                ->whereNull('lokasifk')
                ->whereRaw("noisntruksikerja ~* '-G[^-]*$'")
                ->update(['lokasifk' => (int) $gresikId, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('instruksikerja_m', 'lokasifk')) {
            Schema::table('instruksikerja_m', function (Blueprint $table) {
                $table->dropColumn('lokasifk');
            });
        }
    }
};
