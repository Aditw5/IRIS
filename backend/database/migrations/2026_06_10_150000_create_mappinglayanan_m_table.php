<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMappinglayananMTable extends Migration
{
    public function up()
    {
        Schema::create('mappinglayanan_m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kategori', 30);
            $table->string('lingkup', 150);
            $table->string('namalayanan', 255);
            $table->string('merktipe', 255);
            $table->string('gambar', 255)->nullable();
            $table->boolean('statusenabled')->default(true);
            $table->timestamps();

            $table->unique(
                ['kategori', 'lingkup', 'namalayanan', 'merktipe'],
                'mappinglayanan_m_unique'
            );
            $table->index(
                ['statusenabled', 'kategori', 'lingkup'],
                'mappinglayanan_m_filter_index'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('mappinglayanan_m');
    }
}
