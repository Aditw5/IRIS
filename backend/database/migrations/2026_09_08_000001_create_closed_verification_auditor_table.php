<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClosedVerificationAuditorTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('closedverificationauditor_t')) {
            Schema::create('closedverificationauditor_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->unsignedBigInteger('auditfk');
                $table->unsignedBigInteger('temuanfk');
                $table->text('analisapenyebab')->nullable();
                $table->text('tindakankoreksi')->nullable();
                $table->text('tindakankorektif')->nullable();
                $table->date('rencanapenyelesaian')->nullable();
                $table->string('statusverifikasi', 40)->nullable();
                $table->integer('pengisifk')->nullable();
                $table->string('namapengisi', 255)->nullable();
                $table->timestamp('tanggaldiperbarui')->nullable();
                $table->integer('verifikatorfk')->nullable();
                $table->string('namaverifikator', 255)->nullable();
                $table->timestamp('tanggalverifikasi')->nullable();
                $table->integer('createdby')->nullable();
                $table->integer('updatedby')->nullable();
                $table->timestamps();

                $table->unique(['kdprofile', 'temuanfk'], 'uq_closed_verification_temuan');
                $table->index(['kdprofile', 'auditfk', 'statusenabled'], 'idx_closed_verification_audit');
                $table->foreign('auditfk', 'fk_closed_verification_audit')
                    ->references('id')
                    ->on('temuanketidaksesuaian_t')
                    ->onDelete('cascade');
                $table->foreign('temuanfk', 'fk_closed_verification_temuan')
                    ->references('id')
                    ->on('temuanketidaksesuaiandetail_t')
                    ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('closedverificationauditor_t');
    }
}
