<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddWahyuAsAuditManager extends Migration
{
    public function up()
    {
        // Manager audit sekarang ditentukan langsung dari pegawai_m.jabatan1fk = 2.
        // Bersihkan mapping role manager manual dari implementasi sebelumnya.
        DB::table('mappingauditorinternal_m')
            ->where('peran', 'manager')
            ->delete();
    }

    public function down()
    {
        // Tidak mengembalikan mapping manual karena jabatan pegawai adalah sumber role Manager.
    }
}
