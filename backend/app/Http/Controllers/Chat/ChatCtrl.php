<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ChatCtrl extends Controller
{
    private const MAX_ATTACHMENT_KB = 20480;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function contacts(Request $request)
    {
        $employee = $this->employeeContext();
        if (!$employee) {
            return $this->employeeOnlyResponse();
        }

        $query = DB::table('pegawai_m as pg')
            ->where('pg.statusenabled', true)
            ->where('pg.kdprofile', $employee->kdprofile)
            ->where('pg.id', '<>', $employee->id);

        if ($search = trim((string) $request->get('search'))) {
            $query->whereRaw('LOWER(pg.namalengkap) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $data = $query
            ->select(
                'pg.id',
                DB::raw("COALESCE(pg.namalengkap, '-') as namalengkap"),
                DB::raw('COALESCE(pg.jabatan1fk, pg.jabatan2fk) as jabatanfk'),
                DB::raw("
                    CASE
                        WHEN NULLIF(COALESCE(pg.\"filenameFoto\", ''), '') IS NOT NULL THEN pg.\"filenameFoto\"
                        WHEN NULLIF(COALESCE(pg.filename, ''), '') IS NOT NULL THEN pg.filename
                        ELSE NULL
                    END as filename_foto
                ")
            )
            ->orderBy('pg.namalengkap')
            ->limit(300)
            ->get();

        return $this->respond(['data' => $data], 200, 'Sukses');
    }

    public function upsertPrivateRoom(Request $request)
    {
        $employee = $this->employeeContext();
        if (!$employee) {
            return $this->employeeOnlyResponse();
        }

        $targetId = (int) ($request->input('targetPegawaiId') ?? $request->input('target_pegawai_id'));
        if (!$targetId || $targetId === (int) $employee->id) {
            return $this->respond(null, 422, 'Pegawai tujuan tidak valid');
        }

        $target = DB::table('pegawai_m')
            ->where('id', $targetId)
            ->where('kdprofile', $employee->kdprofile)
            ->where('statusenabled', true)
            ->first();

        if (!$target) {
            return $this->respond(null, 404, 'Pegawai tujuan tidak ditemukan');
        }

        $room = $this->findPrivateRoom((int) $employee->id, $targetId);
        if ($room) {
            return $this->respond(['room' => $room], 200, 'Sukses');
        }

        DB::beginTransaction();
        try {
            $roomId = DB::table('chat_room_m')->insertGetId([
                'kdprofile' => $employee->kdprofile,
                'statusenabled' => true,
                'norec' => (string) Str::uuid(),
                'tipe' => 'private',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('chat_room_member_m')->insert([
                [
                    'kdprofile' => $employee->kdprofile,
                    'statusenabled' => true,
                    'norec' => (string) Str::uuid(),
                    'roomfk' => $roomId,
                    'pegawaifk' => $employee->id,
                    'last_read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'kdprofile' => $employee->kdprofile,
                    'statusenabled' => true,
                    'norec' => (string) Str::uuid(),
                    'roomfk' => $roomId,
                    'pegawaifk' => $targetId,
                    'last_read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            DB::commit();
            return $this->respond(['room' => ['id' => $roomId, 'tipe' => 'private']], 200, 'Sukses');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respond(null, 400, 'Gagal membuat ruang chat: ' . $e->getMessage());
        }
    }

    public function rooms()
    {
        $employee = $this->employeeContext();
        if (!$employee) {
            return $this->employeeOnlyResponse();
        }

        $me = (int) $employee->id;
        $rooms = DB::table('chat_room_member_m as mine')
            ->join('chat_room_m as room', 'room.id', '=', 'mine.roomfk')
            ->join('chat_room_member_m as other_member', function ($join) use ($me) {
                $join->on('other_member.roomfk', '=', 'room.id')
                    ->where('other_member.statusenabled', true)
                    ->where('other_member.pegawaifk', '<>', $me);
            })
            ->join('pegawai_m as other', function ($join) {
                $join->on('other.id', '=', 'other_member.pegawaifk')
                    ->where('other.statusenabled', true);
            })
            ->where('mine.statusenabled', true)
            ->where('room.statusenabled', true)
            ->where('room.tipe', 'private')
            ->where('mine.pegawaifk', $me)
            ->select(
                'room.id as room_id',
                'other.id as pegawai_id',
                DB::raw("COALESCE(other.namalengkap, '-') as namalengkap"),
                DB::raw("
                    CASE
                        WHEN NULLIF(COALESCE(other.\"filenameFoto\", ''), '') IS NOT NULL THEN other.\"filenameFoto\"
                        WHEN NULLIF(COALESCE(other.filename, ''), '') IS NOT NULL THEN other.filename
                        ELSE NULL
                    END as filename_foto
                "),
                DB::raw("(
                    SELECT CASE
                        WHEN msg.tipe = 'image' THEN COALESCE(msg.message, 'Gambar')
                        WHEN msg.tipe = 'file' THEN COALESCE(msg.message, msg.attachment_name, 'File')
                        ELSE msg.message
                    END
                    FROM chat_message_t msg
                    WHERE msg.statusenabled = true AND msg.roomfk = room.id
                    ORDER BY msg.created_at DESC, msg.id DESC LIMIT 1
                ) as last_message"),
                DB::raw("(
                    SELECT msg.created_at FROM chat_message_t msg
                    WHERE msg.statusenabled = true AND msg.roomfk = room.id
                    ORDER BY msg.created_at DESC, msg.id DESC LIMIT 1
                ) as last_message_at"),
                DB::raw("(
                    SELECT COUNT(1) FROM chat_message_t msg
                    WHERE msg.statusenabled = true
                      AND msg.roomfk = room.id
                      AND msg.senderfk <> {$me}
                      AND msg.created_at > COALESCE(mine.last_read_at, '1900-01-01'::timestamp)
                ) as unread")
            )
            ->orderByDesc(DB::raw("COALESCE((
                SELECT msg.created_at FROM chat_message_t msg
                WHERE msg.statusenabled = true AND msg.roomfk = room.id
                ORDER BY msg.created_at DESC, msg.id DESC LIMIT 1
            ), room.created_at)"))
            ->get();

        return $this->respond([
            'rooms' => $rooms,
            'unread' => (int) $rooms->sum('unread'),
        ], 200, 'Sukses');
    }

    public function messages(Request $request)
    {
        $employee = $this->employeeContext();
        if (!$employee) {
            return $this->employeeOnlyResponse();
        }

        $roomId = (int) $request->get('roomfk');
        if (!$this->isRoomMember($roomId, (int) $employee->id)) {
            return $this->respond(null, 403, 'Tidak punya akses ke ruang chat');
        }

        $limit = max(1, min((int) ($request->get('limit') ?: 50), 100));
        $query = DB::table('chat_message_t as msg')
            ->join('pegawai_m as pg', 'pg.id', '=', 'msg.senderfk')
            ->leftJoin('chat_message_t as reply_msg', 'reply_msg.id', '=', 'msg.reply_to_message_id')
            ->leftJoin('pegawai_m as reply_pg', 'reply_pg.id', '=', 'reply_msg.senderfk')
            ->where('msg.statusenabled', true)
            ->where('msg.roomfk', $roomId);

        if ($beforeId = (int) $request->get('beforeId')) {
            $query->where('msg.id', '<', $beforeId);
        }

        $messages = $query
            ->select($this->messageSelectColumns())
            ->orderByDesc('msg.id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return $this->respond(['messages' => $messages], 200, 'Sukses');
    }

    public function send(Request $request)
    {
        $employee = $this->employeeContext();
        if (!$employee) {
            return $this->employeeOnlyResponse();
        }

        $validator = Validator::make($request->all(), [
            'roomfk' => 'required|integer',
            'message' => 'nullable|string|max:5000',
            'attachment' => 'nullable|file|max:' . self::MAX_ATTACHMENT_KB,
            'reply_to_message_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->respond($validator->errors(), 422, $validator->errors()->first());
        }

        $roomId = (int) $request->input('roomfk');
        $replyToMessageId = (int) $request->input('reply_to_message_id');
        $messageText = trim((string) $request->input('message'));
        $attachment = $request->file('attachment');

        if ($messageText === '' && !$attachment) {
            return $this->respond(null, 422, 'Pesan atau lampiran wajib diisi');
        }

        if (!$this->isRoomMember($roomId, (int) $employee->id)) {
            return $this->respond(null, 403, 'Tidak punya akses ke ruang chat');
        }

        if ($replyToMessageId && !DB::table('chat_message_t')
            ->where('id', $replyToMessageId)
            ->where('roomfk', $roomId)
            ->where('statusenabled', true)
            ->exists()) {
            return $this->respond(null, 422, 'Pesan yang dibalas sudah tidak tersedia');
        }

        $attachmentData = [
            'tipe' => 'text',
            'attachment_url' => null,
            'attachment_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
            'absolute_path' => null,
        ];

        if ($attachment) {
            try {
                $attachmentData = $this->storeAttachment($attachment);
                if (isset($attachmentData['error'])) {
                    return $this->respond(null, 422, $attachmentData['error']);
                }
            } catch (Throwable $e) {
                Log::error('Gagal menyimpan lampiran chat', [
                    'pegawai_id' => $employee->id,
                    'room_id' => $roomId,
                    'filename' => $attachment->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);

                return $this->respond(
                    null,
                    500,
                    'Gagal menyimpan lampiran. Folder public/chat-attachments belum dapat ditulis server.'
                );
            }
        }

        DB::beginTransaction();
        try {
            $messageId = DB::table('chat_message_t')->insertGetId([
                'kdprofile' => $employee->kdprofile,
                'statusenabled' => true,
                'norec' => (string) Str::uuid(),
                'roomfk' => $roomId,
                'senderfk' => $employee->id,
                'reply_to_message_id' => $replyToMessageId ?: null,
                'tipe' => $attachmentData['tipe'],
                'message' => $messageText !== '' ? $messageText : null,
                'attachment_url' => $attachmentData['attachment_url'],
                'attachment_name' => $attachmentData['attachment_name'],
                'attachment_mime' => $attachmentData['attachment_mime'],
                'attachment_size' => $attachmentData['attachment_size'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('chat_room_member_m')
                ->where('roomfk', $roomId)
                ->where('pegawaifk', $employee->id)
                ->where('statusenabled', true)
                ->update(['last_read_at' => now(), 'updated_at' => now()]);

            $recipientIds = $this->roomRecipientIds($roomId, (int) $employee->id);

            $saved = DB::table('chat_message_t as msg')
                ->join('pegawai_m as pg', 'pg.id', '=', 'msg.senderfk')
                ->leftJoin('chat_message_t as reply_msg', 'reply_msg.id', '=', 'msg.reply_to_message_id')
                ->leftJoin('pegawai_m as reply_pg', 'reply_pg.id', '=', 'reply_msg.senderfk')
                ->where('msg.id', $messageId)
                ->select($this->messageSelectColumns())
                ->first();

            DB::commit();
            try {
                $this->emitChatMessage($recipientIds, $saved);
            } catch (Exception $e) {
                // Pesan tetap tersimpan jika socket sedang tidak tersedia.
            }

            return $this->respond(['message' => $saved], 200, 'Pesan terkirim');
        } catch (Throwable $e) {
            DB::rollBack();
            if ($attachmentData['absolute_path'] && File::exists($attachmentData['absolute_path'])) {
                File::delete($attachmentData['absolute_path']);
            }
            return $this->respond(null, 400, 'Gagal mengirim pesan: ' . $e->getMessage());
        }
    }

    public function deleteMessages(Request $request)
    {
        $employee = $this->employeeContext();
        if (!$employee) {
            return $this->employeeOnlyResponse();
        }

        $validator = Validator::make($request->all(), [
            'roomfk' => 'required|integer',
            'messageIds' => 'required|array|min:1|max:100',
            'messageIds.*' => 'required|integer|distinct',
        ]);

        if ($validator->fails()) {
            return $this->respond($validator->errors(), 422, $validator->errors()->first());
        }

        $roomId = (int) $request->input('roomfk');
        $messageIds = collect($request->input('messageIds'))
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        if (!$this->isRoomMember($roomId, (int) $employee->id)) {
            return $this->respond(null, 403, 'Tidak punya akses ke ruang chat');
        }

        $ownedMessageIds = DB::table('chat_message_t')
            ->where('roomfk', $roomId)
            ->where('senderfk', $employee->id)
            ->where('statusenabled', true)
            ->whereIn('id', $messageIds)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values();

        if ($ownedMessageIds->count() !== $messageIds->count()) {
            return $this->respond(null, 403, 'Hanya pesan milik sendiri yang dapat dihapus');
        }

        DB::beginTransaction();
        try {
            DB::table('chat_message_t')
                ->where('roomfk', $roomId)
                ->where('senderfk', $employee->id)
                ->whereIn('id', $ownedMessageIds)
                ->update([
                    'statusenabled' => false,
                    'updated_at' => now(),
                ]);

            $recipientIds = $this->roomRecipientIds($roomId, (int) $employee->id);
            DB::commit();

            $payload = [
                'roomfk' => $roomId,
                'message_ids' => $ownedMessageIds->all(),
            ];

            try {
                $this->emitChatDeletion($recipientIds, $payload);
            } catch (Exception $e) {
                // Penghapusan tetap tersimpan jika socket sedang tidak tersedia.
            }

            return $this->respond($payload, 200, 'Pesan terpilih dihapus');
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->respond(null, 400, 'Gagal menghapus pesan: ' . $e->getMessage());
        }
    }

    public function markRead(Request $request)
    {
        $employee = $this->employeeContext();
        if (!$employee) {
            return $this->employeeOnlyResponse();
        }

        $roomId = (int) $request->input('roomfk');
        if (!$this->isRoomMember($roomId, (int) $employee->id)) {
            return $this->respond(null, 403, 'Tidak punya akses ke ruang chat');
        }

        DB::table('chat_room_member_m')
            ->where('roomfk', $roomId)
            ->where('pegawaifk', $employee->id)
            ->where('statusenabled', true)
            ->update(['last_read_at' => now(), 'updated_at' => now()]);

        return $this->respond(['roomfk' => $roomId], 200, 'Pesan telah dibaca');
    }

    private function employeeContext()
    {
        $pegawaiId = (int) $this->getPegawaiId();
        $loginId = (int) $this->getUserId();

        if (!$pegawaiId || !$loginId) {
            return null;
        }

        $isEmployeeLogin = DB::table('loginuser_s')
            ->where('id', $loginId)
            ->where('namauser', $this->getUsername())
            ->where('objectpegawaifk', $pegawaiId)
            ->where('kdprofile', session('kdProfile'))
            ->where('statusenabled', true)
            ->exists();

        if (!$isEmployeeLogin) {
            return null;
        }

        return DB::table('pegawai_m')
            ->where('id', $pegawaiId)
            ->where('statusenabled', true)
            ->first();
    }

    private function employeeOnlyResponse()
    {
        return $this->respond(null, 403, 'Fitur chat hanya tersedia untuk pegawai');
    }

    private function findPrivateRoom(int $me, int $target)
    {
        return DB::table('chat_room_m as room')
            ->where('room.statusenabled', true)
            ->where('room.tipe', 'private')
            ->whereExists(function ($query) use ($me) {
                $query->select(DB::raw(1))
                    ->from('chat_room_member_m as member_me')
                    ->whereColumn('member_me.roomfk', 'room.id')
                    ->where('member_me.statusenabled', true)
                    ->where('member_me.pegawaifk', $me);
            })
            ->whereExists(function ($query) use ($target) {
                $query->select(DB::raw(1))
                    ->from('chat_room_member_m as member_target')
                    ->whereColumn('member_target.roomfk', 'room.id')
                    ->where('member_target.statusenabled', true)
                    ->where('member_target.pegawaifk', $target);
            })
            ->whereRaw('(
                SELECT COUNT(1) FROM chat_room_member_m members
                WHERE members.roomfk = room.id AND members.statusenabled = true
            ) = 2')
            ->select('room.id', 'room.tipe')
            ->first();
    }

    private function isRoomMember(int $roomId, int $pegawaiId): bool
    {
        if (!$roomId) {
            return false;
        }

        return DB::table('chat_room_member_m as member')
            ->join('chat_room_m as room', 'room.id', '=', 'member.roomfk')
            ->where('member.roomfk', $roomId)
            ->where('member.pegawaifk', $pegawaiId)
            ->where('member.statusenabled', true)
            ->where('room.statusenabled', true)
            ->exists();
    }

    private function messageSelectColumns(): array
    {
        return [
            'msg.id',
            'msg.roomfk',
            'msg.senderfk',
            'msg.reply_to_message_id',
            DB::raw("COALESCE(pg.namalengkap, '-') as sender_name"),
            DB::raw("
                CASE
                    WHEN NULLIF(COALESCE(pg.\"filenameFoto\", ''), '') IS NOT NULL THEN pg.\"filenameFoto\"
                    WHEN NULLIF(COALESCE(pg.filename, ''), '') IS NOT NULL THEN pg.filename
                    ELSE NULL
                END as sender_avatar
            "),
            'msg.tipe',
            'msg.message',
            'msg.attachment_url',
            'msg.attachment_name',
            'msg.attachment_mime',
            'msg.attachment_size',
            'msg.created_at',
            'reply_msg.statusenabled as reply_statusenabled',
            DB::raw("CASE
                WHEN reply_msg.id IS NOT NULL THEN COALESCE(reply_pg.namalengkap, '-')
                ELSE NULL
            END as reply_sender_name"),
            DB::raw('CASE WHEN reply_msg.statusenabled = true THEN reply_msg.tipe ELSE NULL END as reply_tipe'),
            DB::raw('CASE WHEN reply_msg.statusenabled = true THEN reply_msg.message ELSE NULL END as reply_message'),
            DB::raw('CASE WHEN reply_msg.statusenabled = true THEN reply_msg.attachment_name ELSE NULL END as reply_attachment_name'),
        ];
    }

    private function roomRecipientIds(int $roomId, int $senderId): array
    {
        return DB::table('chat_room_member_m')
            ->where('roomfk', $roomId)
            ->where('pegawaifk', '<>', $senderId)
            ->where('statusenabled', true)
            ->pluck('pegawaifk')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();
    }

    private function storeAttachment($attachment): array
    {
        $mime = (string) $attachment->getMimeType();
        $extension = strtolower((string) ($attachment->getClientOriginalExtension() ?: $attachment->extension()));
        $imageMime = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
        ];
        $fileMime = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
            'application/x-rar-compressed',
            'application/vnd.rar',
            'text/plain',
            'text/csv',
        ];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $fileExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'csv'];
        $isImage = in_array($mime, $imageMime, true) && in_array($extension, $imageExtensions, true);
        $isFile = in_array($mime, $fileMime, true) && in_array($extension, $fileExtensions, true);

        if (!$isImage && !$isFile) {
            return ['error' => 'Jenis file tidak didukung'];
        }

        $baseDirectory = public_path('chat-attachments');
        $this->ensureWritableDirectory($baseDirectory);

        $directory = 'chat-attachments/' . now()->format('Y/m');
        $destination = public_path($directory);
        $this->ensureWritableDirectory($destination);

        $filename = Str::uuid() . '.' . $extension;
        $originalName = mb_substr($attachment->getClientOriginalName(), 0, 255);
        $size = (int) $attachment->getSize();
        $attachment->move($destination, $filename);
        $absolutePath = $destination . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($absolutePath)) {
            throw new RuntimeException('File lampiran tidak ditemukan setelah proses upload');
        }

        return [
            'tipe' => $isImage ? 'image' : 'file',
            'attachment_url' => '/' . $directory . '/' . $filename,
            'attachment_name' => $originalName,
            'attachment_mime' => $mime,
            'attachment_size' => $size,
            'absolute_path' => $absolutePath,
        ];
    }

    private function ensureWritableDirectory(string $directory): void
    {
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0775, true, true);
        }

        if (File::isDirectory($directory) && !is_writable($directory)) {
            @chmod($directory, 0775);
        }

        if (!File::isDirectory($directory) || !is_writable($directory)) {
            throw new RuntimeException('Direktori upload tidak dapat ditulis: ' . $directory);
        }
    }

    private function emitChatMessage(array $recipientIds, $message): void
    {
        if (!$recipientIds) {
            return;
        }

        $this->emitSocket('/emit-chat', [
            'recipientIds' => $recipientIds,
            'message' => $message,
        ]);
    }

    private function emitChatDeletion(array $recipientIds, array $payload): void
    {
        if (!$recipientIds) {
            return;
        }

        $this->emitSocket('/emit-chat-delete', [
            'recipientIds' => $recipientIds,
            'payload' => $payload,
        ]);
    }

    private function emitSocket(string $path, array $payload): void
    {
        if (!env('SOCKET_SERVER_URL') || !env('SOCKET_SERVER_SECRET')) {
            return;
        }

        try {
            Http::timeout(3)
                ->withHeaders([
                    'x-socket-secret' => env('SOCKET_SERVER_SECRET'),
                    'Accept' => 'application/json',
                ])
                ->post(rtrim(env('SOCKET_SERVER_URL'), '/') . $path, $payload);
        } catch (Exception $e) {
            // Pesan tetap tersimpan walaupun server socket sedang tidak tersedia.
        }
    }
}
