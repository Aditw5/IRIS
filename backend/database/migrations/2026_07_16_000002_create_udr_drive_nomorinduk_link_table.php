<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUdrDriveNomorindukLinkTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('udr_drive_nomorinduk_link_t')) {
            return;
        }

        Schema::create('udr_drive_nomorinduk_link_t', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('nomorindukfk');
            $table->integer('udrrootid');
            $table->string('targettype', 10);
            $table->integer('pegawaifk')->nullable();
            $table->timestamps();

            $table->unique('nomorindukfk', 'uq_udr_drive_link_nomorinduk');
            $table->unique('udrrootid', 'uq_udr_drive_link_root');
            $table->index(['targettype', 'udrrootid'], 'idx_udr_drive_link_target');
        });
    }

    public function down()
    {
        Schema::dropIfExists('udr_drive_nomorinduk_link_t');
    }
}
