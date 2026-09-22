<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnforceFutureUniqueActiveAlatSerialNumber extends Migration
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

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_future_unique_active_alat_serial_number()
            RETURNS trigger AS $$
            DECLARE
                normalized_sn text;
            BEGIN
                normalized_sn := UPPER(BTRIM(COALESCE(NEW.namaserialnumber::text, '')));

                IF COALESCE(NEW.statusenabled, FALSE) IS NOT TRUE OR normalized_sn = '' THEN
                    RETURN NEW;
                END IF;

                -- Data duplikat aktif lama tetap dapat diedit selama SN-nya tidak diubah.
                IF TG_OP = 'UPDATE'
                   AND COALESCE(OLD.statusenabled, FALSE) IS TRUE
                   AND UPPER(BTRIM(COALESCE(OLD.namaserialnumber::text, ''))) = normalized_sn THEN
                    RETURN NEW;
                END IF;

                PERFORM pg_advisory_xact_lock(
                    hashtextextended('mapunittoalat-sn:' || normalized_sn, 0)
                );

                IF EXISTS (
                    SELECT 1
                    FROM mapunittoalat_m
                    WHERE statusenabled IS TRUE
                      AND id <> COALESCE(NEW.id, -1)
                      AND UPPER(BTRIM(namaserialnumber::text)) = normalized_sn
                ) THEN
                    RAISE EXCEPTION USING
                        ERRCODE = '23505',
                        MESSAGE = format(
                            'Alat dengan Serial Number "%s" sudah ada.',
                            normalized_sn
                        ),
                        CONSTRAINT = 'uq_mapunittoalat_active_serial_number';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS trg_enforce_future_unique_active_alat_sn
                ON mapunittoalat_m;

            CREATE TRIGGER trg_enforce_future_unique_active_alat_sn
                BEFORE INSERT OR UPDATE OF namaserialnumber, statusenabled
                ON mapunittoalat_m
                FOR EACH ROW
                EXECUTE FUNCTION enforce_future_unique_active_alat_serial_number();
            SQL);
    }

    public function down()
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS trg_enforce_future_unique_active_alat_sn
                ON mapunittoalat_m;
            DROP FUNCTION IF EXISTS enforce_future_unique_active_alat_serial_number();
            SQL);
    }
}
