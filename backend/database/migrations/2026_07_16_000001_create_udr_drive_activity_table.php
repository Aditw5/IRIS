<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUdrDriveActivityTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('udr_drive_activity_t')) {
            return;
        }

        Schema::create('udr_drive_activity_t', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('udrrootid')->nullable();
            $table->string('jenisudr', 30);
            $table->string('aksi', 40);
            $table->string('namaitem', 255)->nullable();
            $table->integer('parentid')->nullable();
            $table->integer('pegawaifk')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['jenisudr', 'created_at'], 'idx_udr_drive_activity_jenis_date');
            $table->index(['udrrootid', 'created_at'], 'idx_udr_drive_activity_root_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('udr_drive_activity_t');
    }
}
