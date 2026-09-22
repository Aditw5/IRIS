<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_cache_t', function (Blueprint $table) {
            $table->string('source_hash', 64)->primary();
            $table->string('source_language', 10);
            $table->string('target_language', 10);
            $table->text('source_text');
            $table->text('translated_text');
            $table->string('provider', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_cache_t');
    }
};
