<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSertifikatStandarLogTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sertifikat_standar_log')) {
            return;
        }

        Schema::create('sertifikat_standar_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('kdprofile')->nullable()->index();
            $table->boolean('statusenabled')->default(true);
            $table->integer('alatstandarfk')->index();
            $table->timestamp('calldate');
            $table->timestamp('duedate');
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->integer('created_by')->nullable();
            $table->timestamps();

            $table->index(
                ['alatstandarfk', 'calldate'],
                'sertifikat_standar_log_alat_cal_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('sertifikat_standar_log');
    }
}
