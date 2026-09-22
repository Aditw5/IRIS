<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReplaceLingkupWithObjectlingkupfkOnMappinglayananMTable extends Migration
{
    public function up()
    {
        $lingkupIdIsUnique = DB::selectOne(
            "SELECT EXISTS (
                SELECT 1
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                    ON kcu.constraint_schema = tc.constraint_schema
                    AND kcu.constraint_name = tc.constraint_name
                WHERE tc.table_schema = 'public'
                    AND tc.table_name = 'lingkupkalibrasi_m'
                    AND tc.constraint_type IN ('PRIMARY KEY', 'UNIQUE')
                GROUP BY tc.constraint_name
                HAVING COUNT(*) = 1 AND MAX(kcu.column_name) = 'id'
            ) AS exists"
        )->exists;

        if (!$lingkupIdIsUnique) {
            DB::statement(
                'ALTER TABLE lingkupkalibrasi_m
                 ADD CONSTRAINT mappinglayanan_m_lingkup_reference_unique UNIQUE (id)'
            );
        }

        Schema::table('mappinglayanan_m', function (Blueprint $table) {
            $table->integer('objectlingkupfk')->nullable()->after('kategori');
        });

        $aliases = [
            'Suhu & Kelembapan' => 'Suhu & Kelembaban',
            'Verfikasi' => 'Verifikasi',
        ];

        $legacyLingkup = DB::table('mappinglayanan_m')
            ->select('lingkup')
            ->distinct()
            ->pluck('lingkup');

        foreach ($legacyLingkup as $lingkup) {
            $canonicalName = $aliases[$lingkup] ?? $lingkup;
            $lingkupId = DB::table('lingkupkalibrasi_m')
                ->whereRaw('LOWER(TRIM(lingkupkalibrasi)) = ?', [strtolower(trim($canonicalName))])
                ->value('id');

            if (!$lingkupId) {
                $lingkupId = ((int) DB::table('lingkupkalibrasi_m')->max('id')) + 1;
                DB::table('lingkupkalibrasi_m')->insert([
                    'id' => $lingkupId,
                    'statusenabled' => true,
                    'lingkupkalibrasi' => $canonicalName,
                    'deskripsi' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('mappinglayanan_m')
                ->where('lingkup', $lingkup)
                ->update(['objectlingkupfk' => $lingkupId]);
        }

        if (DB::table('mappinglayanan_m')->whereNull('objectlingkupfk')->exists()) {
            throw new RuntimeException('Sebagian data mapping layanan belum memiliki FK lingkup kalibrasi.');
        }

        Schema::table('mappinglayanan_m', function (Blueprint $table) {
            $table->dropUnique('mappinglayanan_m_unique');
            $table->dropIndex('mappinglayanan_m_filter_index');
            $table->dropColumn('lingkup');
        });

        DB::statement('ALTER TABLE mappinglayanan_m ALTER COLUMN objectlingkupfk SET NOT NULL');

        Schema::table('mappinglayanan_m', function (Blueprint $table) {
            $table->foreign('objectlingkupfk', 'mappinglayanan_m_lingkup_fk')
                ->references('id')
                ->on('lingkupkalibrasi_m');
            $table->unique(
                ['kategori', 'objectlingkupfk', 'namalayanan', 'merktipe'],
                'mappinglayanan_m_unique'
            );
            $table->index(
                ['statusenabled', 'kategori', 'objectlingkupfk'],
                'mappinglayanan_m_filter_index'
            );
        });
    }

    public function down()
    {
        Schema::table('mappinglayanan_m', function (Blueprint $table) {
            $table->string('lingkup', 150)->nullable()->after('kategori');
        });

        DB::statement(
            'UPDATE mappinglayanan_m AS ml
             SET lingkup = lk.lingkupkalibrasi
             FROM lingkupkalibrasi_m AS lk
             WHERE lk.id = ml.objectlingkupfk'
        );

        Schema::table('mappinglayanan_m', function (Blueprint $table) {
            $table->dropForeign('mappinglayanan_m_lingkup_fk');
            $table->dropUnique('mappinglayanan_m_unique');
            $table->dropIndex('mappinglayanan_m_filter_index');
            $table->dropColumn('objectlingkupfk');
        });

        DB::statement('ALTER TABLE mappinglayanan_m ALTER COLUMN lingkup SET NOT NULL');

        Schema::table('mappinglayanan_m', function (Blueprint $table) {
            $table->unique(
                ['kategori', 'lingkup', 'namalayanan', 'merktipe'],
                'mappinglayanan_m_unique'
            );
            $table->index(
                ['statusenabled', 'kategori', 'lingkup'],
                'mappinglayanan_m_filter_index'
            );
        });

        $referenceConstraintExists = DB::selectOne(
            "SELECT EXISTS (
                SELECT 1
                FROM information_schema.table_constraints
                WHERE table_schema = 'public'
                    AND table_name = 'lingkupkalibrasi_m'
                    AND constraint_name = 'mappinglayanan_m_lingkup_reference_unique'
            ) AS exists"
        )->exists;

        if ($referenceConstraintExists) {
            DB::statement(
                'ALTER TABLE lingkupkalibrasi_m
                 DROP CONSTRAINT mappinglayanan_m_lingkup_reference_unique'
            );
        }
    }
}
