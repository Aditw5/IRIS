<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Lingkup hanya diisi jika seluruh riwayat pemakaian IK tersebut menunjuk
        // tepat ke satu lingkup. Data tanpa riwayat tidak ditebak.
        DB::statement(<<<'SQL'
            UPDATE instruksikerja_m AS ik
            SET lingkupfk = pemakaian.lingkupfk,
                updated_at = CURRENT_TIMESTAMP
            FROM (
                SELECT
                    daftar.idalatinstruksikerja AS instruksikerjafk,
                    MAX(detail.lingkupkalibrasifk) AS lingkupfk
                FROM daftarinstruksikerja_t AS daftar
                INNER JOIN mitraregistrasidetail_t AS detail
                    ON detail.norec::text = daftar.detailregistrasifk::text
                WHERE detail.lingkupkalibrasifk IS NOT NULL
                GROUP BY daftar.idalatinstruksikerja
                HAVING COUNT(DISTINCT detail.lingkupkalibrasifk) = 1
            ) AS pemakaian
            WHERE ik.id = pemakaian.instruksikerjafk
              AND ik.lingkupfk IS NULL
            SQL);
    }

    public function down(): void
    {
        // Tidak mengosongkan lingkup pada rollback karena setelah migrasi berjalan
        // nilainya tidak dapat dibedakan dari perubahan manual yang sah.
    }
};
