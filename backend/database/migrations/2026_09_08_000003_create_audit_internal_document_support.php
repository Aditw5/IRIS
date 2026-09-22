<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditInternalDocumentSupport extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('dokumenauditinternalfolder_m')) {
            Schema::create('dokumenauditinternalfolder_m', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->string('tipefolder', 20);
                $table->unsignedBigInteger('referensifk');
                $table->unsignedBigInteger('auditfk');
                $table->unsignedBigInteger('temuanfk')->nullable();
                $table->integer('tahun');
                $table->string('kodejenisaudit', 100);
                $table->integer('udrfolderfk');
                $table->timestamps();

                $table->unique(
                    ['kdprofile', 'tipefolder', 'referensifk'],
                    'uq_dokumen_audit_folder_reference'
                );
                $table->index(['kdprofile', 'tahun', 'kodejenisaudit'], 'idx_dokumen_audit_folder_scope');
                $table->index('udrfolderfk', 'idx_dokumen_audit_udr_folder');
                $table->foreign('auditfk', 'fk_dokumen_audit_folder_audit')
                    ->references('id')->on('temuanketidaksesuaian_t')->onDelete('cascade');
                $table->foreign('temuanfk', 'fk_dokumen_audit_folder_temuan')
                    ->references('id')->on('temuanketidaksesuaiandetail_t')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('closedverificationauditor_t')) {
            Schema::table('closedverificationauditor_t', function (Blueprint $table) {
                if (!Schema::hasColumn('closedverificationauditor_t', 'dokumenauditfk')) {
                    $table->integer('dokumenauditfk')->nullable();
                }
                if (!Schema::hasColumn('closedverificationauditor_t', 'namadokumen')) {
                    $table->string('namadokumen', 255)->nullable();
                }
                if (!Schema::hasColumn('closedverificationauditor_t', 'tautandokumen')) {
                    $table->text('tautandokumen')->nullable();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('closedverificationauditor_t')) {
            Schema::table('closedverificationauditor_t', function (Blueprint $table) {
                $columns = array_values(array_filter(
                    ['dokumenauditfk', 'namadokumen', 'tautandokumen'],
                    fn ($column) => Schema::hasColumn('closedverificationauditor_t', $column)
                ));
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('dokumenauditinternalfolder_m');
    }
}
