<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUniqueActiveAlatSerialNumberIndex extends Migration
{
    public function up()
    {
        if (
            !Schema::hasTable('mapunittoalat_m') ||
            !Schema::hasColumn('mapunittoalat_m', 'id') ||
            !Schema::hasColumn('mapunittoalat_m', 'namaserialnumber') ||
            !Schema::hasColumn('mapunittoalat_m', 'statusenabled')
        ) {
            return;
        }

        $where = '"statusenabled" IS TRUE
            AND "namaserialnumber" IS NOT NULL
            AND BTRIM("namaserialnumber"::text) <> \'\'';

        $legacyExceptions = $this->legacyDuplicateExceptions();
        if (!empty($legacyExceptions)) {
            $where .= ' AND NOT (' . implode(' OR ', $legacyExceptions) . ')';
        }

        $duplicates = DB::table('mapunittoalat_m')
            ->selectRaw('UPPER(BTRIM("namaserialnumber"::text)) AS serial_normalized, COUNT(*) AS duplicate_count')
            ->whereRaw($where)
            ->groupByRaw('UPPER(BTRIM("namaserialnumber"::text))')
            ->havingRaw('COUNT(*) > 1')
            ->limit(5)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $examples = $duplicates->map(function ($row) {
                return $row->serial_normalized . ' (' . $row->duplicate_count . 'x)';
            })->implode(', ');

            throw new \RuntimeException(
                'Unique index alat belum dapat dibuat. Duplikat yang belum terlindungi: ' . $examples
            );
        }

        DB::statement(sprintf(
            'CREATE UNIQUE INDEX IF NOT EXISTS "uq_mapunittoalat_active_serial_number"
                ON "mapunittoalat_m" (UPPER(BTRIM("namaserialnumber"::text)))
                WHERE %s',
            $where
        ));
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS "uq_mapunittoalat_active_serial_number"');
    }

    private function legacyDuplicateExceptions()
    {
        $groups = DB::table('mapunittoalat_m')
            ->selectRaw('UPPER(BTRIM("namaserialnumber"::text)) AS serial_normalized, COUNT(*) AS duplicate_count')
            ->where('statusenabled', true)
            ->whereNotNull('namaserialnumber')
            ->whereRaw('BTRIM("namaserialnumber"::text) <> \'\'')
            ->groupByRaw('UPPER(BTRIM("namaserialnumber"::text))')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $exceptions = [];
        foreach ($groups as $group) {
            $rows = DB::table('mapunittoalat_m')
                ->select('id', 'namaserialnumber')
                ->where('statusenabled', true)
                ->whereRaw('UPPER(BTRIM("namaserialnumber"::text)) = ?', [$group->serial_normalized]);

            if (Schema::hasColumn('mapunittoalat_m', 'created_at')) {
                $rows->orderBy('created_at');
            }

            $rows = $rows->orderBy('id')->get();

            // Baris pertama tetap masuk index untuk memblokir pemakaian ulang SN lama.
            foreach ($rows->slice(1) as $row) {
                $exceptions[] = sprintf(
                    '("id" = %s AND UPPER(BTRIM("namaserialnumber"::text)) = %s)',
                    $this->quoteLiteral($row->id),
                    $this->quoteLiteral($group->serial_normalized)
                );
            }
        }

        return $exceptions;
    }

    private function quoteLiteral($value)
    {
        return DB::connection()->getPdo()->quote((string) $value);
    }
}
