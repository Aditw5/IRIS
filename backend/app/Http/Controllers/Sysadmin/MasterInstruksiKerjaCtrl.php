<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\InstruksiKerja;
use App\Models\Standar\UlabDigitalRepo;
use App\Traits\Valet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MasterInstruksiKerjaCtrl extends Controller
{
    use Valet;

    private const FILE_DIRECTORY = 'berkas-mutu';
    private const LEGACY_FILE_DIRECTORY = 'berkas-instruksi-kerja';
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];
    private const UDS_PROGRAM = 'Teknik';
    private const UDS_SOURCE = 'MASTER_INSTRUKSI_KERJA';

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function worksheetInstructions(Request $request)
    {
        return $this->respond(
            app(\App\Services\WorksheetInstructionService::class)->options($this->kdProfile),
            200,
            'OK',
            ['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0']
        );
    }

    public function masterInstruksiKerja(Request $request)
    {
        $fileSummary = DB::table('instruksikerja_file_t')
            ->select(
                'kdprofile',
                'instruksikerjafk',
                DB::raw('COUNT(*) as jumlahversi'),
                DB::raw('MAX(versi) as versiterbaru')
            )
            ->where('statusenabled', true)
            ->groupBy('kdprofile', 'instruksikerjafk');

        $data = DB::table('instruksikerja_m as ik')
            ->leftJoin('lingkupkalibrasi_m as lk', 'lk.id', '=', 'ik.lingkupfk')
            ->leftJoin('lokasikalibrasi_m as lokasi', 'lokasi.id', '=', 'ik.lokasifk')
            ->leftJoinSub($fileSummary, 'fs', function ($join) {
                $join->on('fs.instruksikerjafk', '=', 'ik.id')
                    ->on('fs.kdprofile', '=', 'ik.kdprofile');
            })
            ->leftJoin('instruksikerja_file_t as latest', function ($join) {
                $join->on('latest.instruksikerjafk', '=', 'ik.id')
                    ->on('latest.kdprofile', '=', 'ik.kdprofile')
                    ->on('latest.versi', '=', 'fs.versiterbaru')
                    ->where('latest.statusenabled', true);
            })
            ->select(
                'ik.id',
                'ik.statusenabled',
                'ik.namainstruksikerja',
                'ik.noisntruksikerja',
                'ik.lingkupfk',
                'lk.lingkupkalibrasi',
                'ik.lokasifk',
                'lokasi.lokasi',
                'ik.udrrootfk',
                'ik.udsfolderfk',
                'ik.created_at',
                'ik.updated_at',
                DB::raw('COALESCE(fs.jumlahversi, 0) as jumlahversi'),
                'latest.id as fileterbaruid',
                'latest.versi as versiterbaru',
                'latest.namaasli as namafileterbaru',
                'latest.mimetype as mimetypeterbaru',
                'latest.ukuran as ukuranfileterbaru',
                'latest.created_at as tanggaluploadterbaru'
            )
            ->where('ik.kdprofile', $this->kdProfile);

        if ($request->filled('id')) {
            $data->where('ik.id', $request->get('id'));
        }
        if ($request->filled('search')) {
            $keyword = trim((string) $request->get('search'));
            $data->where(function ($query) use ($keyword) {
                $query->where('ik.noisntruksikerja', 'ilike', '%' . $keyword . '%')
                    ->orWhere('ik.namainstruksikerja', 'ilike', '%' . $keyword . '%')
                    ->orWhere('lk.lingkupkalibrasi', 'ilike', '%' . $keyword . '%')
                    ->orWhere('lokasi.lokasi', 'ilike', '%' . $keyword . '%')
                    ->orWhere('latest.namaasli', 'ilike', '%' . $keyword . '%');
            });
        }
        if ($request->filled('noisntruksikerja')) {
            $data->where('ik.noisntruksikerja', 'ilike', '%' . $request->get('noisntruksikerja') . '%');
        }
        if ($request->filled('namainstruksikerja')) {
            $data->where('ik.namainstruksikerja', 'ilike', '%' . $request->get('namainstruksikerja') . '%');
        }
        if ($request->filled('lingkupfk')) {
            $data->where('ik.lingkupfk', (int) $request->get('lingkupfk'));
        }
        if ($request->filled('lokasifk')) {
            $data->where('ik.lokasifk', (int) $request->get('lokasifk'));
        }
        if ($request->has('statusenabled') && $request->get('statusenabled') !== '') {
            $data->where('ik.statusenabled', filter_var(
                $request->get('statusenabled'),
                FILTER_VALIDATE_BOOLEAN
            ));
        }
        if ($request->get('file') === 'ada') {
            $data->whereNotNull('fs.jumlahversi');
        } elseif ($request->get('file') === 'kosong') {
            $data->whereNull('fs.jumlahversi');
        }

        $rows = $data->orderByDesc('ik.created_at')->get();
        foreach ($rows as $row) {
            $enabled = filter_var($row->statusenabled, FILTER_VALIDATE_BOOLEAN);
            $row->statusenabled = $enabled;
            $row->status = $enabled ? 'Aktif' : 'Nonaktif';
            $row->status_c = $enabled ? 'info' : 'danger';
            $row->jumlahversi = (int) $row->jumlahversi;
            $row->versiterbaru = $row->versiterbaru === null ? null : (int) $row->versiterbaru;
        }

        return $this->respond(['data' => $rows]);
    }

    public function saveInstruksiKerja(Request $request)
    {
        $payload = $request->input('datainstruksikereja', []);
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: [];
        }
        if (!is_array($payload)) {
            $payload = [];
        }

        $id = $payload['id'] ?? null;
        $isNew = empty($id);
        $file = $request->file('file');
        $validator = Validator::make(
            array_merge($payload, ['file' => $file]),
            [
                'noisntruksikerja' => 'required|string|max:255',
                'namainstruksikerja' => 'required|string|max:500',
                'lingkupfk' => 'required|integer|exists:lingkupkalibrasi_m,id',
                'lokasifk' => 'required|integer|exists:lokasikalibrasi_m,id',
                'file' => ($isNew ? 'required|' : 'nullable|') . 'file|mimes:pdf,doc,docx|max:30720',
            ],
            [
                'noisntruksikerja.required' => 'No Instruksi Kerja wajib diisi.',
                'namainstruksikerja.required' => 'Nama Instruksi Kerja wajib diisi.',
                'lingkupfk.required' => 'Lingkup wajib dipilih.',
                'lingkupfk.exists' => 'Lingkup yang dipilih tidak valid.',
                'lokasifk.required' => 'Lokasi wajib dipilih.',
                'lokasifk.exists' => 'Lokasi yang dipilih tidak valid.',
                'file.required' => 'File IK wajib diupload saat menambah data.',
                'file.mimes' => 'File IK hanya boleh berformat PDF, DOC, atau DOCX.',
                'file.max' => 'Ukuran file IK maksimal 30 MB.',
            ]
        );

        if ($validator->fails()) {
            return $this->respond(null, 422, $validator->errors()->first());
        }

        $storedPath = null;
        $failureMessage = null;
        DB::beginTransaction();
        try {
            if ($isNew) {
                $id = $this->SEQUENCE_MASTER(new InstruksiKerja(), 'id', $this->kdProfile);
                $instruction = new InstruksiKerja();
                $instruction->id = $id;
                $instruction->kdprofile = (int) $this->kdProfile;
                $instruction->statusenabled = true;
            } else {
                $instruction = InstruksiKerja::where('id', $id)
                    ->where('kdprofile', $this->kdProfile)
                    ->lockForUpdate()
                    ->firstOrFail();
                if (array_key_exists('statusenabled', $payload)) {
                    $instruction->statusenabled = filter_var(
                        $payload['statusenabled'],
                        FILTER_VALIDATE_BOOLEAN
                    );
                }
            }

            $instruction->namainstruksikerja = mb_strtoupper(trim($payload['namainstruksikerja']));
            $instruction->noisntruksikerja = trim($payload['noisntruksikerja']);
            $instruction->lingkupfk = (int) $payload['lingkupfk'];
            $instruction->lokasifk = (int) $payload['lokasifk'];
            $instruction->save();

            $version = null;
            $udsSync = null;
            if ($file) {
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    throw new Exception('Format file tidak didukung.');
                }

                // The instruction row is already locked above when updating, so
                // version requests for the same IK are serialized. PostgreSQL does
                // not allow FOR UPDATE on aggregate queries such as MAX().
                $version = ((int) DB::table('instruksikerja_file_t')
                    ->where('kdprofile', $this->kdProfile)
                    ->where('instruksikerjafk', $id)
                    ->max('versi')) + 1;

                $directory = public_path(self::FILE_DIRECTORY);
                File::ensureDirectoryExists($directory);
                $storedName = Str::uuid()->toString() . '.' . $extension;
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getClientMimeType();
                $file->move($directory, $storedName);
                $storedPath = $directory . DIRECTORY_SEPARATOR . $storedName;

                $fileVersionId = DB::table('instruksikerja_file_t')->insertGetId([
                    'kdprofile' => (int) $this->kdProfile,
                    'instruksikerjafk' => (int) $id,
                    'versi' => $version,
                    'namaasli' => $originalName,
                    'namafile' => $storedName,
                    'mimetype' => $mimeType,
                    'ukuran' => File::size($storedPath),
                    'petugasfk' => $this->getPegawaiId(),
                    'namapetugas' => $this->getNamaPegawai(),
                    'statusenabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $fileVersion = DB::table('instruksikerja_file_t')
                    ->where('id', $fileVersionId)
                    ->first();
                $udsSync = $this->syncInstructionFileToUds($instruction, $fileVersion);
                if (empty($udsSync['synced'])) {
                    $failureMessage = $udsSync['reason']
                        ?? 'Versi file IK gagal disinkronkan ke UDS.';
                    throw new Exception($failureMessage);
                }
            } elseif (!empty($instruction->udrrootfk)) {
                $udsSync = $this->syncInstructionUdsMetadata($instruction);
            }

            DB::commit();

            return $this->respond([
                'data' => $instruction,
                'versi' => $version,
                'uds_sync' => $udsSync,
            ], 200, $isNew ? 'Instruksi Kerja berhasil disimpan' : 'Instruksi Kerja berhasil diperbarui');
        } catch (Exception $exception) {
            DB::rollBack();
            if ($storedPath && File::exists($storedPath)) {
                File::delete($storedPath);
            }
            report($exception);

            return $this->respond(
                null,
                400,
                $failureMessage ?: 'Instruksi Kerja gagal disimpan. Silakan coba kembali.'
            );
        }
    }

    public function historyInstruksiKerja(Request $request)
    {
        $instruction = DB::table('instruksikerja_m')
            ->where('id', (int) $request->get('id'))
            ->where('kdprofile', $this->kdProfile)
            ->first();
        if (!$instruction) {
            return $this->respond(null, 404, 'Instruksi Kerja tidak ditemukan.');
        }

        $versions = DB::table('instruksikerja_file_t')
            ->where('instruksikerjafk', $instruction->id)
            ->where('kdprofile', $this->kdProfile)
            ->where('statusenabled', true)
            ->orderByDesc('versi')
            ->get();

        return $this->respond([
            'instruksikerja' => $instruction,
            'versions' => $versions,
        ]);
    }

    public function openInstruksiKerjaFile(Request $request)
    {
        $version = $this->resolveFileVersion((int) $request->get('id'));
        $filename = basename($version->namafile);
        $this->ensureFileIsPublished($filename);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filepath = asset(self::FILE_DIRECTORY . '/' . rawurlencode($filename));
        $data = $version;

        return view('report.mutu.view-pdf', compact('filepath', 'data', 'extension'));
    }

    public function downloadInstruksiKerjaFile(Request $request)
    {
        $version = $this->resolveFileVersion((int) $request->get('id'));
        $fullPath = $this->ensureFileIsPublished(basename($version->namafile));

        $extension = strtolower(pathinfo($version->namafile, PATHINFO_EXTENSION));
        $baseName = pathinfo($version->namaasli, PATHINFO_FILENAME) ?: 'instruksi-kerja';
        $downloadName = $baseName . '_Versi_' . str_pad((string) $version->versi, 2, '0', STR_PAD_LEFT);

        return response()->download($fullPath, $downloadName . '.' . $extension);
    }

    public function syncExistingInstruksiKerjaToUds(): array
    {
        $stats = [
            'instructions' => 0,
            'files_synced' => 0,
            'files_skipped' => 0,
            'errors' => [],
        ];

        $instructionIds = DB::table('instruksikerja_m as ik')
            ->join('instruksikerja_file_t as f', function ($join) {
                $join->on('f.instruksikerjafk', '=', 'ik.id')
                    ->on('f.kdprofile', '=', 'ik.kdprofile');
            })
            ->where('ik.kdprofile', $this->kdProfile)
            ->where('f.statusenabled', true)
            ->whereNotNull('ik.lingkupfk')
            ->whereNotNull('ik.lokasifk')
            ->distinct()
            ->orderBy('ik.id')
            ->pluck('ik.id');

        foreach ($instructionIds as $instructionId) {
            try {
                DB::transaction(function () use ($instructionId, &$stats) {
                    $instruction = InstruksiKerja::where('id', $instructionId)
                        ->where('kdprofile', $this->kdProfile)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $versions = DB::table('instruksikerja_file_t')
                        ->where('kdprofile', $this->kdProfile)
                        ->where('instruksikerjafk', $instruction->id)
                        ->where('statusenabled', true)
                        ->orderBy('versi')
                        ->orderBy('id')
                        ->get();

                    foreach ($versions as $fileVersion) {
                        $result = $this->syncInstructionFileToUds($instruction, $fileVersion);
                        if (!empty($result['synced'])) {
                            $stats['files_synced']++;
                        } else {
                            $stats['files_skipped']++;
                        }
                    }
                    $stats['instructions']++;
                });
            } catch (Exception $exception) {
                $stats['errors'][] = [
                    'instruction_id' => (int) $instructionId,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $stats;
    }

    private function syncInstructionFileToUds($instruction, $fileVersion): array
    {
        $targetFolder = $this->resolveUdsInstructionFolder($instruction, true);
        if (!$targetFolder) {
            return [
                'synced' => false,
                'reason' => 'Folder UDS untuk kombinasi lingkup dan lokasi belum tersedia.',
            ];
        }

        $marker = $this->udsFileMarker((int) $instruction->id, (int) $fileVersion->id);
        $existingRecord = null;
        if (!empty($fileVersion->udrrecordfk)) {
            $existingRecord = DB::table('ulabdigitalrepo_m')
                ->where('id', (int) $fileVersion->udrrecordfk)
                ->first();
        }
        if (!$existingRecord) {
            $existingRecord = DB::table('ulabdigitalrepo_m')
                ->where('kdprofile', $this->kdProfile)
                ->where('fungsi', self::UDS_SOURCE)
                ->where('namaexternal', $marker)
                ->first();
        }

        if ($existingRecord) {
            $rootId = (int) ($existingRecord->kdisidokumen ?: $existingRecord->id);
            DB::table('instruksikerja_file_t')
                ->where('id', $fileVersion->id)
                ->update(['udrrecordfk' => (int) $existingRecord->id]);
            DB::table('instruksikerja_m')
                ->where('id', $instruction->id)
                ->where('kdprofile', $this->kdProfile)
                ->update([
                    'udrrootfk' => $rootId,
                    'udsfolderfk' => (int) $targetFolder->id,
                ]);
            $instruction->udrrootfk = $rootId;
            $instruction->udsfolderfk = (int) $targetFolder->id;
            $this->syncInstructionUdsMetadata($instruction, $targetFolder);

            return [
                'synced' => true,
                'root_id' => $rootId,
                'record_id' => (int) $existingRecord->id,
                'folder_id' => (int) $targetFolder->id,
                'existing' => true,
            ];
        }

        $rootId = (int) ($instruction->udrrootfk ?? 0);
        $rootRecord = null;
        if ($rootId > 0) {
            $rootRecord = DB::table('ulabdigitalrepo_m')
                ->where('kdprofile', $this->kdProfile)
                ->where(function ($query) use ($rootId) {
                    $query->where('id', $rootId)
                        ->orWhere('kdisidokumen', (string) $rootId);
                })
                ->orderBy('id')
                ->first();
        }
        if (!$rootRecord) {
            $rootRecord = DB::table('ulabdigitalrepo_m')
                ->where('kdprofile', $this->kdProfile)
                ->where('fungsi', self::UDS_SOURCE)
                ->where('namaexternal', 'like', $this->udsInstructionMarker((int) $instruction->id) . ':%')
                ->orderBy('id')
                ->first();
            if ($rootRecord) {
                $rootId = (int) ($rootRecord->kdisidokumen ?: $rootRecord->id);
            }
        }

        $isFirstVersion = !$rootRecord;
        if ($isFirstVersion) {
            $nourut = $this->nextUdsInstructionOrder();
        } else {
            $nourut = (int) ($rootRecord->nourut ?? 0);
        }
        $recordId = $this->SEQUENCE_MASTER(new UlabDigitalRepo(), 'id', $this->kdProfile);
        if ($isFirstVersion) {
            $rootId = $recordId;
        }

        $document = new UlabDigitalRepo();
        $document->id = $recordId;
        $document->kdprofile = (int) $this->kdProfile;
        $document->statusenabled = true;
        $document->kodeexternal = null;
        $document->namaexternal = $marker;
        $document->norec = substr((string) $this->Uuid4(), 0, 32);
        $document->fungsi = self::UDS_SOURCE;
        $document->kdisidokumen = $rootId;
        $document->keterangan = 'Sinkron otomatis dari Master Instruksi Kerja (IK), versi '
            . (int) $fileVersion->versi;
        $document->namaisidokumen = $this->udsInstructionName($instruction);
        $document->nourut = $nourut;
        $document->kdrinciandokumenhead = (int) $targetFolder->id;
        $document->alamaturlform = null;
        $document->isidokumen = basename((string) $fileVersion->namafile);
        $document->revisike = (int) $fileVersion->versi;
        $document->tglrevisi = $fileVersion->created_at ?: now();
        $document->dilihat = 0;
        $document->jenisudr = self::UDS_PROGRAM;
        $document->pegawaifk = $fileVersion->petugasfk ?: $this->getPegawaiId();
        $document->ishapus = 0;
        $document->created_at = $fileVersion->created_at ?: now();
        $document->updated_at = $fileVersion->updated_at ?: now();
        $document->save();

        DB::table('instruksikerja_file_t')
            ->where('id', $fileVersion->id)
            ->update(['udrrecordfk' => $recordId]);
        DB::table('instruksikerja_m')
            ->where('id', $instruction->id)
            ->where('kdprofile', $this->kdProfile)
            ->update([
                'udrrootfk' => $rootId,
                'udsfolderfk' => (int) $targetFolder->id,
            ]);
        $instruction->udrrootfk = $rootId;
        $instruction->udsfolderfk = (int) $targetFolder->id;
        $this->syncInstructionUdsMetadata($instruction, $targetFolder);

        $this->logUdsInstructionActivity(
            $rootId,
            $isFirstVersion ? 'UPLOAD' : 'REPLACE_FILE',
            $this->udsInstructionName($instruction),
            (int) $targetFolder->id,
            $fileVersion
        );

        return [
            'synced' => true,
            'root_id' => $rootId,
            'record_id' => $recordId,
            'folder_id' => (int) $targetFolder->id,
            'existing' => false,
        ];
    }

    private function syncInstructionUdsMetadata($instruction, $targetFolder = null): array
    {
        $rootId = (int) ($instruction->udrrootfk ?? 0);
        if ($rootId <= 0) {
            return ['synced' => false, 'reason' => 'IK belum mempunyai dokumen UDS.'];
        }

        $targetFolder = $targetFolder ?: $this->resolveUdsInstructionFolder($instruction, true);
        if (!$targetFolder) {
            return [
                'synced' => false,
                'reason' => 'Folder UDS untuk kombinasi lingkup dan lokasi belum tersedia.',
            ];
        }

        DB::table('ulabdigitalrepo_m')
            ->where('kdprofile', $this->kdProfile)
            ->where(function ($query) use ($rootId) {
                $query->where('id', $rootId)
                    ->orWhere('kdisidokumen', (string) $rootId);
            })
            ->update([
                'namaisidokumen' => $this->udsInstructionName($instruction),
                'kdrinciandokumenhead' => (int) $targetFolder->id,
                'statusenabled' => (bool) $instruction->statusenabled,
                'ishapus' => 0,
                'updated_at' => now(),
            ]);

        DB::table('instruksikerja_m')
            ->where('id', $instruction->id)
            ->where('kdprofile', $this->kdProfile)
            ->update(['udsfolderfk' => (int) $targetFolder->id]);
        $instruction->udsfolderfk = (int) $targetFolder->id;

        return [
            'synced' => true,
            'root_id' => $rootId,
            'folder_id' => (int) $targetFolder->id,
        ];
    }

    private function resolveUdsInstructionFolder($instruction, bool $createIfMissing = false)
    {
        $scope = DB::table('lingkupkalibrasi_m')
            ->where('id', (int) $instruction->lingkupfk)
            ->value('lingkupkalibrasi');
        $location = DB::table('lokasikalibrasi_m')
            ->where('id', (int) $instruction->lokasifk)
            ->value('lokasi');
        $locationCode = $this->udsLocationCode($location);
        if (!$scope || !$locationCode) {
            return null;
        }

        // The master scope contains both historical spellings (Kelembaban and
        // Kelembapan), while the UDS folder uses the standardized spelling.
        $scopeParentName = stripos((string) $scope, 'suhu') !== false
            ? 'Suhu & Kelembapan'
            : trim((string) $scope);
        $parentName = $scopeParentName . ' ' . $locationCode;
        $parent = DB::table('ulabdigitalrepo_m')
            ->where('kdprofile', $this->kdProfile)
            ->where('statusenabled', true)
            ->where('jenisudr', self::UDS_PROGRAM)
            ->whereRaw("TRIM(COALESCE(isidokumen, '')) = ''")
            ->whereRaw("TRIM(COALESCE(alamaturlform, '')) = ''")
            ->whereRaw('LOWER(TRIM(namaisidokumen)) = ?', [mb_strtolower($parentName)])
            ->where(function ($query) {
                $query->where('kodeexternal', 'H')
                    ->orWhereNull('kdrinciandokumenhead')
                    ->orWhereColumn('kdrinciandokumenhead', 'id');
            })
            ->orderByDesc('id')
            ->first();
        if (!$parent) {
            return null;
        }

        $scopeFolderName = stripos((string) $scope, 'suhu') !== false
            ? 'Suhu'
            : trim((string) $scope);
        $folderName = 'Master Instruksi Kerja (IK) ' . $scopeFolderName . ' ' . $locationCode;
        $folder = $this->findUdsInstructionFolder($folderName, (int) $parent->id);
        if ($folder || !$createIfMissing) {
            return $folder;
        }

        $this->lockNomorTransaksi('folder-master-ik-uds', [
            $this->kdProfile,
            (int) $parent->id,
            mb_strtolower($folderName),
        ]);
        $folder = $this->findUdsInstructionFolder($folderName, (int) $parent->id);
        if ($folder) {
            return $folder;
        }

        $nourut = $this->nextUdsInstructionOrder();
        $folderId = $this->SEQUENCE_MASTER(new UlabDigitalRepo(), 'id', $this->kdProfile);
        $folder = new UlabDigitalRepo();
        $folder->id = $folderId;
        $folder->kdprofile = (int) $this->kdProfile;
        $folder->statusenabled = true;
        $folder->kodeexternal = null;
        $folder->namaexternal = 'MASTER_IK_FOLDER:' . (int) $parent->id;
        $folder->norec = substr((string) $this->Uuid4(), 0, 32);
        $folder->fungsi = self::UDS_SOURCE;
        $folder->kdisidokumen = $folderId;
        $folder->keterangan = 'Folder sinkron otomatis Master Instruksi Kerja (IK)';
        $folder->namaisidokumen = $folderName;
        $folder->nourut = $nourut;
        $folder->kdrinciandokumenhead = (int) $parent->id;
        $folder->isidokumen = null;
        $folder->alamaturlform = null;
        $folder->revisike = 1;
        $folder->tglrevisi = now();
        $folder->dilihat = 0;
        $folder->jenisudr = self::UDS_PROGRAM;
        $folder->pegawaifk = $this->getPegawaiId() ?: $parent->pegawaifk;
        $folder->ishapus = 0;
        $folder->save();

        return $folder;
    }

    private function findUdsInstructionFolder(string $folderName, int $parentId)
    {
        return DB::table('ulabdigitalrepo_m')
            ->where('kdprofile', $this->kdProfile)
            ->where('statusenabled', true)
            ->where('jenisudr', self::UDS_PROGRAM)
            ->where('kdrinciandokumenhead', $parentId)
            ->whereRaw("TRIM(COALESCE(isidokumen, '')) = ''")
            ->whereRaw("TRIM(COALESCE(alamaturlform, '')) = ''")
            ->whereRaw('LOWER(TRIM(namaisidokumen)) = ?', [mb_strtolower($folderName)])
            ->orderByDesc('id')
            ->first();
    }

    private function nextUdsInstructionOrder(): int
    {
        $this->lockNomorTransaksi('nourut-udr', [self::UDS_PROGRAM]);

        return ((int) DB::table('ulabdigitalrepo_m')
            ->where('jenisudr', self::UDS_PROGRAM)
            ->max('nourut')) + 1;
    }

    private function udsInstructionName($instruction): string
    {
        return mb_substr(
            trim((string) $instruction->noisntruksikerja)
                . ' - ' . trim((string) $instruction->namainstruksikerja),
            0,
            255
        );
    }

    private function udsInstructionMarker(int $instructionId): string
    {
        return 'MASTER_IK:' . $instructionId;
    }

    private function udsFileMarker(int $instructionId, int $fileId): string
    {
        return $this->udsInstructionMarker($instructionId) . ':FILE:' . $fileId;
    }

    private function udsLocationCode($location): ?string
    {
        $location = mb_strtolower(trim((string) $location));
        if (strpos($location, 'gresik') !== false) {
            return 'GRK';
        }
        if (strpos($location, 'jakarta') !== false) {
            return 'JKT';
        }

        return null;
    }

    private function logUdsInstructionActivity(
        int $rootId,
        string $action,
        string $name,
        int $folderId,
        $fileVersion
    ): void {
        if (!Schema::hasTable('udr_drive_activity_t')) {
            return;
        }

        DB::table('udr_drive_activity_t')->insert([
            'udrrootid' => $rootId,
            'jenisudr' => self::UDS_PROGRAM,
            'aksi' => $action,
            'namaitem' => $name,
            'parentid' => $folderId,
            'pegawaifk' => $fileVersion->petugasfk ?: $this->getPegawaiId(),
            'metadata' => json_encode([
                'source' => self::UDS_SOURCE,
                'instruksi_kerja_id' => (int) $fileVersion->instruksikerjafk,
                'file_version_id' => (int) $fileVersion->id,
                'revision' => (int) $fileVersion->versi,
            ]),
            'created_at' => $fileVersion->created_at ?: now(),
            'updated_at' => $fileVersion->updated_at ?: now(),
        ]);
    }

    private function ensureFileIsPublished(string $filename): string
    {
        $filename = basename($filename);
        $publicPath = public_path(self::FILE_DIRECTORY . '/' . $filename);
        if (is_file($publicPath)) {
            return $publicPath;
        }

        $legacyPath = public_path(self::LEGACY_FILE_DIRECTORY . '/' . $filename);
        if (!is_file($legacyPath)) {
            abort(404, 'File IK tidak ditemukan.');
        }

        File::ensureDirectoryExists(dirname($publicPath));
        if (!File::copy($legacyPath, $publicPath) || !is_file($publicPath)) {
            abort(500, 'File IK gagal dipublikasikan.');
        }

        return $publicPath;
    }

    private function resolveFileVersion(int $id)
    {
        $version = DB::table('instruksikerja_file_t as f')
            ->join('instruksikerja_m as ik', 'ik.id', '=', 'f.instruksikerjafk')
            ->select('f.*')
            ->where('f.id', $id)
            ->where('f.kdprofile', $this->kdProfile)
            ->where('ik.kdprofile', $this->kdProfile)
            ->where('f.statusenabled', true)
            ->first();

        if (!$version) {
            abort(404, 'Versi file IK tidak ditemukan.');
        }

        return $version;
    }

    public function deleteInstruksiKerja(Request $request)
    {
        $instruction = InstruksiKerja::where('id', $request->get('id'))
            ->where('kdprofile', $this->kdProfile)
            ->first();

        if (!$instruction) {
            return $this->respond(null, 404, 'Instruksi Kerja tidak ditemukan.');
        }

        DB::transaction(function () use ($instruction) {
            $instruction->statusenabled = false;
            $instruction->save();

            $rootId = (int) ($instruction->udrrootfk ?? 0);
            if ($rootId > 0) {
                DB::table('ulabdigitalrepo_m')
                    ->where('kdprofile', $this->kdProfile)
                    ->where(function ($query) use ($rootId) {
                        $query->where('id', $rootId)
                            ->orWhere('kdisidokumen', (string) $rootId);
                    })
                    ->update([
                        'statusenabled' => false,
                        'updated_at' => now(),
                    ]);
            }
        });

        return $this->respond(['data' => 1], 200, 'Instruksi Kerja dinonaktifkan');
    }
}
