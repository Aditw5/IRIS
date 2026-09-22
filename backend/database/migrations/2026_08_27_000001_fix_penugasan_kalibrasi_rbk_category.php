<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixPenugasanKalibrasiRbkCategory extends Migration
{
    public function up()
    {
        DB::statement(<<<'SQL'
            UPDATE pengajuandetailpbj_t AS detail
            SET kategorirbk = 'mobilisasi'
            FROM pengajuanpbj_t AS pbj
            LEFT JOIN jenispbj_m AS jenis ON jenis.id = pbj.jenispbj
            WHERE pbj.norec = detail.noregpbjfk
              AND LOWER(COALESCE(detail.kategorirbk, '')) = 'manpower'
              AND UPPER(COALESCE(jenis.jenispbj, '')) <> 'MATERIAL'
              AND LOWER(
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
        SQL);
    }

    public function down()
    {
        // Data lama tidak dikembalikan karena kategori awalnya memang salah.
    }
}
