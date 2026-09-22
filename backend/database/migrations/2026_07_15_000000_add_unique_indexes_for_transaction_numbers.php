<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUniqueIndexesForTransactionNumbers extends Migration
{
    /**
     * Protect all future writes without renumbering documents that were already issued.
     */
    public function up()
    {
        $indexes = array_values(array_filter([
            $this->indexDefinition(
                'mitraregistrasi_t',
                ['nopendaftaran'],
                'uq_mitraregistrasi_nopendaftaran',
                $this->notBlank('nopendaftaran'),
                'norec'
            ),
            $this->indexDefinition(
                'pengajuanpbj_t',
                ['nosuratpbj'],
                'uq_pengajuanpbj_nosuratpbj',
                $this->notBlank('nosuratpbj'),
                'norec'
            ),
            $this->indexDefinition(
                'mitraregistrasidetail_t',
                ['noorderalat'],
                'uq_mitraregistrasidetail_noorderalat',
                $this->notBlank('noorderalat'),
                'norec'
            ),
            $this->indexDefinition(
                'mitraregistrasidetail_t',
                ['nosertifikat'],
                'uq_mitraregistrasidetail_nosertifikat',
                $this->notBlank('nosertifikat'),
                'norec'
            ),
            $this->indexDefinition(
                'mitraregistrasidetail_t',
                ['nolaporanrepair'],
                'uq_mitraregistrasidetail_nolaporanrepair',
                $this->notBlank('nolaporanrepair'),
                'norec'
            ),
            $this->indexDefinition(
                'suratjalan_t',
                ['nosuratjalan'],
                'uq_suratjalan_nosuratjalan',
                $this->notBlank('nosuratjalan'),
                'norec'
            ),
            $this->indexDefinition(
                'sertifikat_log',
                ['norec_detail', 'version'],
                'uq_sertifikat_log_detail_version',
                '"norec_detail" IS NOT NULL AND "version" IS NOT NULL',
                'id'
            ),
            $this->indexDefinition(
                'laporan_repair_log',
                ['norec_detail', 'version'],
                'uq_laporan_repair_log_detail_version',
                '"norec_detail" IS NOT NULL AND "version" IS NOT NULL',
                'id'
            ),
        ]));

        // Keep one canonical legacy row indexed. Surplus legacy rows are excluded only
        // while their identity and duplicated number remain exactly unchanged.
        foreach ($indexes as $key => $definition) {
            $indexes[$key] = $this->preserveLegacyDuplicates($definition);
        }

        // Verify the generated exception predicates before creating any index.
        foreach ($indexes as $definition) {
            $this->ensureNoDuplicates($definition);
        }

        foreach ($indexes as $definition) {
            $columns = implode(', ', array_map(function ($column) {
                return '"' . $column . '"';
            }, $definition['columns']));

            DB::statement(sprintf(
                'CREATE UNIQUE INDEX IF NOT EXISTS "%s" ON "%s" (%s) WHERE %s',
                $definition['name'],
                $definition['table'],
                $columns,
                $definition['where']
            ));
        }
    }

    public function down()
    {
        foreach ([
            'uq_mitraregistrasi_nopendaftaran',
            'uq_pengajuanpbj_nosuratpbj',
            'uq_mitraregistrasidetail_noorderalat',
            'uq_mitraregistrasidetail_nosertifikat',
            'uq_mitraregistrasidetail_nolaporanrepair',
            'uq_suratjalan_nosuratjalan',
            'uq_sertifikat_log_detail_version',
            'uq_laporan_repair_log_detail_version',
        ] as $index) {
            DB::statement('DROP INDEX IF EXISTS "' . $index . '"');
        }
    }

    private function indexDefinition($table, array $columns, $name, $where, $identity)
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return null;
            }
        }

        if (!Schema::hasColumn($table, $identity)) {
            return null;
        }

        return compact('table', 'columns', 'name', 'where', 'identity');
    }

    private function notBlank($column)
    {
        return sprintf('"%1$s" IS NOT NULL AND BTRIM("%1$s"::text) <> \'\'', $column);
    }

    private function preserveLegacyDuplicates(array $definition)
    {
        $duplicateGroups = DB::table($definition['table'])
            ->select($definition['columns'])
            ->selectRaw('COUNT(*) AS duplicate_count')
            ->whereRaw($definition['where'])
            ->groupBy($definition['columns'])
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $exceptions = [];

        foreach ($duplicateGroups as $group) {
            $rows = DB::table($definition['table'])
                ->select(array_merge([$definition['identity']], $definition['columns']))
                ->whereRaw($definition['where']);

            foreach ($definition['columns'] as $column) {
                $rows->where($column, $group->{$column});
            }

            if (Schema::hasColumn($definition['table'], 'created_at')) {
                $rows->orderBy('created_at');
            }

            $rows = $rows->orderBy($definition['identity'])->get();

            // The first row stays in the index and blocks reuse of this legacy number.
            foreach ($rows->slice(1) as $row) {
                $parts = [sprintf(
                    '"%s" = %s',
                    $definition['identity'],
                    $this->quoteLiteral($row->{$definition['identity']})
                )];

                foreach ($definition['columns'] as $column) {
                    $parts[] = sprintf(
                        '"%s" = %s',
                        $column,
                        $this->quoteLiteral($row->{$column})
                    );
                }

                $exceptions[] = '(' . implode(' AND ', $parts) . ')';
            }
        }

        if (!empty($exceptions)) {
            $definition['where'] = sprintf(
                '(%s) AND NOT (%s)',
                $definition['where'],
                implode(' OR ', $exceptions)
            );
        }

        return $definition;
    }

    private function quoteLiteral($value)
    {
        return DB::connection()->getPdo()->quote((string) $value);
    }

    private function ensureNoDuplicates(array $definition)
    {
        $query = DB::table($definition['table'])
            ->select($definition['columns'])
            ->selectRaw('COUNT(*) AS duplicate_count')
            ->whereRaw($definition['where'])
            ->groupBy($definition['columns'])
            ->havingRaw('COUNT(*) > 1')
            ->limit(5)
            ->get();

        if ($query->isEmpty()) {
            return;
        }

        $examples = $query->map(function ($row) use ($definition) {
            return implode(' / ', array_map(function ($column) use ($row) {
                return (string) $row->{$column};
            }, $definition['columns'])) . ' (' . $row->duplicate_count . 'x)';
        })->implode(', ');

        throw new \RuntimeException(sprintf(
            'Unique index %s belum dapat dibuat. Bersihkan duplikat lama pada %s: %s',
            $definition['name'],
            $definition['table'],
            $examples
        ));
    }
}
