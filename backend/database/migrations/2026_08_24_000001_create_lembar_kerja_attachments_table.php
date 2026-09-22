<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lembar_kerja_attachment_t', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Data lama memakai ID numerik, sedangkan data baru memakai UUID.
            $table->string('detailregistrasifk', 100)->index();
            $table->string('namafile');
            $table->string('namaasli');
            $table->unsignedBigInteger('ukuran')->nullable();
            $table->string('mimetype')->nullable();
            $table->boolean('statusenabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lembar_kerja_attachment_t');
    }
};
