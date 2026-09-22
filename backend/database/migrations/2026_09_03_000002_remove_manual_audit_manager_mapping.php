<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RemoveManualAuditManagerMapping extends Migration
{
    public function up()
    {
        DB::table('mappingauditorinternal_m')
            ->where('peran', 'manager')
            ->delete();
    }

    public function down()
    {
        // Mapping Manager tidak dibuat ulang; role mengikuti pegawai_m.jabatan1fk = 2.
    }
}
