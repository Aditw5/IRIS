<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lembar_kerja_attachment_t')
            || !Schema::hasColumn('lembar_kerja_attachment_t', 'detailregistrasifk')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement(
                'ALTER TABLE lembar_kerja_attachment_t '
                . 'ALTER COLUMN detailregistrasifk TYPE VARCHAR(100) '
                . 'USING detailregistrasifk::text'
            );
        } elseif ($driver === 'mysql') {
            DB::statement(
                'ALTER TABLE lembar_kerja_attachment_t '
                . 'MODIFY detailregistrasifk VARCHAR(100) NOT NULL'
            );
        }
    }

    public function down(): void
    {
        // Tidak dipersempit kembali ke UUID karena ID numerik legacy harus tetap valid.
    }
};
