<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitraregistrasidetail_t', function (Blueprint $table) {
            $table->text('noteslembarkerja_en')->nullable();
            $table->string('noteslembarkerja_en_source_hash', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('mitraregistrasidetail_t', function (Blueprint $table) {
            $table->dropColumn([
                'noteslembarkerja_en',
                'noteslembarkerja_en_source_hash',
            ]);
        });
    }
};
