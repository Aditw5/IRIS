<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDocumentLinksPerFollowUpField extends Migration
{
    public function up()
    {
        Schema::table('closedverificationauditor_t', function (Blueprint $table) {
            foreach (['analisa', 'koreksi', 'korektif'] as $field) {
                if (!Schema::hasColumn('closedverificationauditor_t', $field . 'dokumenauditfk')) {
                    $table->integer($field . 'dokumenauditfk')->nullable();
                }
                if (!Schema::hasColumn('closedverificationauditor_t', $field . 'namadokumen')) {
                    $table->string($field . 'namadokumen', 255)->nullable();
                }
                if (!Schema::hasColumn('closedverificationauditor_t', $field . 'tautandokumen')) {
                    $table->text($field . 'tautandokumen')->nullable();
                }
            }
        });

        // Pertahankan bukti yang pernah tersimpan pada skema tunggal sebagai bukti Analisa Penyebab.
        if (Schema::hasColumn('closedverificationauditor_t', 'dokumenauditfk')) {
            DB::table('closedverificationauditor_t')
                ->whereNull('analisadokumenauditfk')
                ->whereNull('analisatautandokumen')
                ->where(function ($query) {
                    $query->whereNotNull('dokumenauditfk')->orWhereNotNull('tautandokumen');
                })
                ->update([
                    'analisadokumenauditfk' => DB::raw('dokumenauditfk'),
                    'analisanamadokumen' => DB::raw('namadokumen'),
                    'analisatautandokumen' => DB::raw('tautandokumen'),
                ]);
        }
    }

    public function down()
    {
        Schema::table('closedverificationauditor_t', function (Blueprint $table) {
            $columns = [];
            foreach (['analisa', 'koreksi', 'korektif'] as $field) {
                foreach (['dokumenauditfk', 'namadokumen', 'tautandokumen'] as $suffix) {
                    $column = $field . $suffix;
                    if (Schema::hasColumn('closedverificationauditor_t', $column)) {
                        $columns[] = $column;
                    }
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
