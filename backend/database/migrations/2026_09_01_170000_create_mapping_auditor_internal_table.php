<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateMappingAuditorInternalTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('mappingauditorinternal_m')) {
            Schema::create('mappingauditorinternal_m', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->boolean('statusenabled')->default(true);
                $table->uuid('norec')->unique();
                $table->unsignedSmallInteger('tahun');
                $table->string('kodejenisaudit', 80);
                $table->string('jenisaudit', 255);
                $table->string('lingkup', 100);
                $table->string('lokasi', 50);
                $table->integer('pegawaifk');
                $table->string('namapegawai', 255);
                $table->string('peran', 30);
                $table->string('tugas', 255);
                $table->unsignedSmallInteger('urutan')->default(1);
                $table->string('sumberdokumen', 255)->nullable();
                $table->integer('createdby')->nullable();
                $table->integer('updatedby')->nullable();
                $table->timestamps();

                $table->unique(
                    ['kdprofile', 'tahun', 'kodejenisaudit', 'pegawaifk', 'peran'],
                    'uq_mapping_auditor_periode'
                );
                $table->index(['kdprofile', 'tahun', 'statusenabled'], 'idx_mapping_auditor_tahun');
                $table->index(['pegawaifk', 'tahun', 'statusenabled'], 'idx_mapping_auditor_pegawai');
            });
        }

        $this->seedPenunjukanAuditor2026();
    }

    private function seedPenunjukanAuditor2026()
    {
        $jenisAudit = [
            'mutu' => ['Audit Internal Mutu', 'Mutu', 'Jakarta & Gresik'],
            'kelistrikan-jakarta' => ['Audit Internal Teknik Kelistrikan Jakarta', 'Kelistrikan', 'Jakarta'],
            'tekanan-jakarta' => ['Audit Internal Teknik Tekanan Jakarta', 'Tekanan', 'Jakarta'],
            'suhu-jakarta' => ['Audit Internal Teknik Suhu & Kelembapan Jakarta', 'Suhu', 'Jakarta'],
            'vibrasi-jakarta' => ['Audit Internal Teknik Vibrasi Jakarta', 'Vibrasi', 'Jakarta'],
            'kelistrikan-gresik' => ['Audit Internal Teknik Kelistrikan Gresik', 'Kelistrikan', 'Gresik'],
            'tekanan-gresik' => ['Audit Internal Teknik Tekanan Gresik', 'Tekanan', 'Gresik'],
            'suhu-gresik' => ['Audit Internal Teknik Suhu Gresik', 'Suhu', 'Gresik'],
            'dimensi-gresik' => ['Audit Internal Teknik Dimensi Gresik', 'Dimensi', 'Gresik'],
        ];

        $namaPegawai = [
            'Aditya Sukmana Putra',
            'Eluvian Satria Arilanggaaji',
            'Fahri Iqbal Gifhari',
            'Alfian Budiarmoko',
            'Deden Denni Rendra',
            'Rodes Agung Suprihatno',
            'Veri Hendrayawan',
            'Muhammad Sayid Dwi Tantoro',
            'Witjaksono Adi S',
            'Arief Pontiadarma',
            'Mahzumi',
            'Muhsin Hidayat',
        ];

        $pegawai = [];
        foreach ($namaPegawai as $nama) {
            $row = DB::table('pegawai_m')
                ->select('id', 'kdprofile', 'namalengkap')
                ->whereRaw('LOWER(TRIM(namalengkap)) = ?', [strtolower($nama)])
                ->where('statusenabled', true)
                ->first();

            if (!$row) {
                throw new RuntimeException('Pegawai untuk mapping auditor 2026 tidak ditemukan: ' . $nama);
            }

            $pegawai[$nama] = $row;
        }

        $assignments = [];
        foreach (array_keys($jenisAudit) as $kodeJenis) {
            $assignments[] = [$kodeJenis, 'Aditya Sukmana Putra', 'lead_auditor', 'Lead Auditor', 1];
        }

        $assignments = array_merge($assignments, [
            ['mutu', 'Eluvian Satria Arilanggaaji', 'auditor_observer', 'Auditor Observer', 2],
            ['mutu', 'Fahri Iqbal Gifhari', 'auditor_observer', 'Auditor Observer', 3],
            ['mutu', 'Mahzumi', 'auditee', 'Auditee Mutu', 10],
            ['mutu', 'Deden Denni Rendra', 'auditee', 'Auditee Mutu', 11],
            ['mutu', 'Muhsin Hidayat', 'auditee', 'Auditee Mutu', 12],

            ['kelistrikan-jakarta', 'Alfian Budiarmoko', 'auditor', 'Anggota Auditor Kelistrikan Jakarta', 2],
            ['kelistrikan-jakarta', 'Arief Pontiadarma', 'auditee', 'Auditee Kelistrikan Jakarta', 10],
            ['kelistrikan-gresik', 'Deden Denni Rendra', 'auditor', 'Anggota Auditor Kelistrikan Gresik', 2],
            ['kelistrikan-gresik', 'Alfian Budiarmoko', 'auditee', 'Auditee Kelistrikan Gresik', 10],

            ['tekanan-jakarta', 'Rodes Agung Suprihatno', 'auditor', 'Anggota Auditor Tekanan Jakarta', 2],
            ['tekanan-jakarta', 'Veri Hendrayawan', 'auditee', 'Auditee Tekanan Jakarta', 10],
            ['vibrasi-jakarta', 'Rodes Agung Suprihatno', 'auditor', 'Anggota Auditor Vibrasi Jakarta', 2],
            ['vibrasi-jakarta', 'Veri Hendrayawan', 'auditee', 'Auditee Vibrasi Jakarta', 10],
            ['tekanan-gresik', 'Veri Hendrayawan', 'auditor', 'Anggota Auditor Tekanan Gresik', 2],
            ['tekanan-gresik', 'Rodes Agung Suprihatno', 'auditee', 'Auditee Tekanan Gresik', 10],

            ['suhu-jakarta', 'Muhammad Sayid Dwi Tantoro', 'auditor', 'Anggota Auditor Suhu dan Kelembapan Jakarta', 2],
            ['suhu-jakarta', 'Witjaksono Adi S', 'auditee', 'Auditee Suhu Jakarta', 10],
            ['suhu-gresik', 'Witjaksono Adi S', 'auditor', 'Anggota Auditor Suhu Gresik', 2],
            ['suhu-gresik', 'Alfian Budiarmoko', 'auditee', 'Auditee Suhu Gresik', 10],

            ['dimensi-gresik', 'Arief Pontiadarma', 'auditor', 'Anggota Auditor Dimensi Gresik', 2],
            ['dimensi-gresik', 'Muhammad Sayid Dwi Tantoro', 'auditee', 'Auditee Dimensi Gresik', 10],
        ]);

        $sumber = 'FMMO-163-14.4.3.b-88.2 Penunjukan Auditor Internal 2026';
        foreach ($assignments as $assignment) {
            [$kodeJenis, $nama, $peran, $tugas, $urutan] = $assignment;
            [$labelJenis, $lingkup, $lokasi] = $jenisAudit[$kodeJenis];
            $pegawaiRow = $pegawai[$nama];

            $exists = DB::table('mappingauditorinternal_m')
                ->where('kdprofile', (int) $pegawaiRow->kdprofile)
                ->where('tahun', 2026)
                ->where('kodejenisaudit', $kodeJenis)
                ->where('pegawaifk', $pegawaiRow->id)
                ->where('peran', $peran)
                ->exists();

            if (!$exists) {
                DB::table('mappingauditorinternal_m')->insert([
                    'kdprofile' => (int) $pegawaiRow->kdprofile,
                    'statusenabled' => true,
                    'norec' => (string) Str::uuid(),
                    'tahun' => 2026,
                    'kodejenisaudit' => $kodeJenis,
                    'jenisaudit' => $labelJenis,
                    'lingkup' => $lingkup,
                    'lokasi' => $lokasi,
                    'pegawaifk' => $pegawaiRow->id,
                    'namapegawai' => trim($pegawaiRow->namalengkap),
                    'peran' => $peran,
                    'tugas' => $tugas,
                    'urutan' => $urutan,
                    'sumberdokumen' => $sumber,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('mappingauditorinternal_m');
    }
}
