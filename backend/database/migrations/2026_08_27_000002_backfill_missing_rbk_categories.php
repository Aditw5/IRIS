<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillMissingRbkCategories extends Migration
{
    public function up()
    {
        DB::statement(<<<'SQL'
            UPDATE pengajuandetailpbj_t AS detail
            SET kategorirbk = CASE
                WHEN UPPER(COALESCE(jenis.jenispbj, '')) = 'MATERIAL'
                    THEN 'material'
                WHEN NULLIF(BTRIM(COALESCE(detail.grupmobilisasi, '')), '') IS NOT NULL
                    THEN 'mobilisasi'
                WHEN LOWER(
                    COALESCE(detail.namaitem, '') || ' ' ||
                    COALESCE(detail.uraianitem, '') || ' ' ||
                    COALESCE(detail.judulmobilisasi, '')
                ) ~ '(mobilisasi|mobilisai|demobilisasi|transportasi|pengantaran|pengiriman|tiket|travel|ojek|bagasi)'
                    THEN 'mobilisasi'
                WHEN LOWER(
                    COALESCE(detail.namaitem, '') || ' ' ||
                    COALESCE(detail.uraianitem, '') || ' ' ||
                    COALESCE(detail.judulmobilisasi, '')
                ) LIKE '%penugasan%'
                    AND LOWER(
                        COALESCE(detail.namaitem, '') || ' ' ||
                        COALESCE(detail.uraianitem, '') || ' ' ||
                        COALESCE(detail.judulmobilisasi, '')
                    ) ~ '(tool|alat|peralatan)'
                    AND LOWER(
                        COALESCE(detail.namaitem, '') || ' ' ||
                        COALESCE(detail.uraianitem, '') || ' ' ||
                        COALESCE(detail.judulmobilisasi, '')
                    ) ~ '(kalibrasi|pengambilan|penyerahan|serah[[:space:]]*terima)'
                    THEN 'mobilisasi'
                ELSE 'manpower'
            END
            FROM pengajuanpbj_t AS pbj
            LEFT JOIN jenispbj_m AS jenis ON jenis.id = pbj.jenispbj
            WHERE pbj.norec = detail.noregpbjfk
              AND LOWER(COALESCE(detail.kategorirbk, '')) NOT IN ('manpower', 'material', 'mobilisasi')
        SQL);
    }

    public function down()
    {
        // Backfill memperbaiki data lama; kategori yang sudah valid tidak dikosongkan kembali.
    }
}
