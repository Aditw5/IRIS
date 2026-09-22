<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Master\Alamat;
use App\Models\Master\JenisPegawai as MasterJenisPegawai;
use App\Models\Master\Pasien;
use App\Models\Master\Pegawai;
use App\Models\Master\Profile;
use App\Models\Master\Ruangan;
use App\Models\Master\User;
use App\Models\Standar\JenisPegawai;
use App\Models\Standar\KelompokUser;
use App\Models\Standar\LoginUser;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Builder;
use Illuminate\Auth\Events\Registered;
use App\Traits\Valet;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;
use App\Models\GoEmailOtp;
use App\Mail\OtpMail;
use App\Http\Controllers\Registrasi\MitraCtrl;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuthCtrl extends Controller
{
    use Valet;

    private int $otpTtlMinutes = 10;
    private int $maxVerifyAttempts = 5;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    private function verifyTurnstile(?string $token, ?string $remoteIp = null): array
    {
        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Captcha wajib diisi.',
            ];
        }

        $secret = config('services.turnstile.secret_key');

        if (empty($secret)) {
            Log::error('TURNSTILE_SECRET_KEY belum diset.');
            return [
                'success' => false,
                'message' => 'Konfigurasi captcha belum lengkap.',
            ];
        }

        try {
            $response = Http::asForm()->timeout(10)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]
            );

            $result = $response->json();

            if (($result['success'] ?? false) === true) {
                return [
                    'success' => true,
                    'message' => 'Captcha valid',
                    'result'  => $result,
                ];
            }

            Log::warning('Turnstile validation failed', [
                'ip' => $remoteIp,
                'result' => $result,
            ]);

            return [
                'success' => false,
                'message' => 'Captcha tidak valid atau sudah kedaluwarsa.',
                'result'  => $result,
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal verifikasi Turnstile: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal memverifikasi captcha.',
            ];
        }
    }

    public function login(Request $r)
    {
        if (empty($r->input('kataSandi')) || empty($r->input('namaUser'))) {
            $response = array(
                'metaData' => array(
                    "code" => 400,
                    "message" => 'Username atau Password harus di isi',
                ),
                'response' => null,
            );

            return response()->json($response, $response['metaData']['code']);
        }

        $captchaToken = $r->input('captcha_token');
        $captchaCheck = $this->verifyTurnstile($captchaToken, $r->ip());

        if (!$captchaCheck['success']) {
            return response()->json([
                'metaData' => [
                    'code' => 422,
                    'message' => $captchaCheck['message'],
                ],
                'response' => null,
            ], 422);
        }

        $loginInput = $r->namaUser;
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            return $this->loginByEmail($r);
        }

        return $this->loginByUsername($r);
    }

    protected function loginByUsername(Request $r)
    {
        $login = LoginUser::where('namauser', '=', $r->input('namaUser'))
            ->where('statusenabled', true)
            ->first();

        if (!$login || !$this->checkHashEncypt($r->input('kataSandi'), $login->katasandi)) {
            return response()->json([
                'metaData' => ['code' => 400, 'message' => 'Login gagal, Username atau Password salah'],
                'response' => null
            ], 400);
        }

        $kelompokUser = KelompokUser::select('id', 'kelompokuser as kelompokUser', 'menu')
            ->where('id', '=', $login->objectkelompokuserfk)
            ->where('statusenabled', true)
            ->first();

        $pegawai = Pegawai::where('id', $login->objectpegawaifk)
            ->where('statusenabled', true)
            ->first();

        if (empty($pegawai)) {
            $response = array(
                'metaData' => array(
                    "code" => 400,
                    "message" => 'Pegawai tidak aktif',
                ),
                'response' => null,
            );
            return response()->json($response, $response['metaData']['code']);
        }

        $jenisPegawai = MasterJenisPegawai::where('id', '=', $pegawai->objectjenispegawaifk)
            ->select('id', 'jenispegawai')
            ->first();

        $ruangKerja = Ruangan::where('id', '=', $pegawai->objectruangankerjafk)
            ->where('statusenabled', true)
            ->first();

        $profile = Profile::where('id', '=', $login->kdprofile)
            ->select(
                'id',
                'namalengkap as namaprofile',
                'alamatlengkap',
                'alamatemail',
                'fixedphone',
                'website',
                'namakota',
                'lat',
                'lng',
                'logoprofil',
                'namaexternal'
            )
            ->first();

        // tambahkan lokasikalibrasifk dari pegawai ke object kelompokUser
        if ($kelompokUser) {
            $kelompokUser->lokasiKalibrasiFk = $pegawai->lokasikalibrasifk;
        }

        $resPegawai = array(
            'id' => $pegawai->id,
            'namaLengkap' => $pegawai->namalengkap,
            'tempatLahir' => $pegawai->tempatlahir,
            'tglLahir' => $pegawai->tgllahir,
            'noIdentitas' => $pegawai->noidentitas,
            'statusEnabled' => $pegawai->statusenabled,
            'jenisPegawai' => $jenisPegawai,
            'ruangan' => $ruangKerja,
            'jenisKelamin_id' => $pegawai->objectjeniskelaminfk,
            'fotopegawai' => $pegawai->filenameFoto,
        );

        $dataLogin = array(
            'id' => $login->id,
            'kdProfile' => $login->kdprofile,
            'namaUser' => $login->namauser,
            'kelompokUser' => $kelompokUser,
            'pegawai' => $resPegawai,
            'profile' => $profile,
        );

        $expired = date("Y-m-d H:i:s", strtotime("+" . config('app.JWT_EXPIRED_MINUTE') . " minutes"));
        $response = array(
            'metaData' => array(
                "code" => 200,
                "message" => 'Sukses',
            ),
            'response' => array(
                'token' => $this->createToken($login->namauser) . '.' . base64_encode((string)$login->kdprofile),
                'expired' => $expired,
                'data' => $dataLogin,
            ),
        );

        return response()
            ->json($response, $response['metaData']['code'])
            ->cookie(
                'token',
                $response['response']['token'],
                120,
                '/',
                null,
                true,
                true,
                false,
                'Strict'
            );
    }

    protected function loginByEmail(Request $r)
    {
        $user = User::where('email', $r->input('namaUser'))
            ->where('statusenabled', true)
            ->first();

        if (!$user || !$this->checkHashEncypt($r->input('kataSandi'), $user->password)) {
            return response()->json([
                'metaData' => ['code' => 400, 'message' => 'Login gagal, Email atau Password salah'],
                'response' => null
            ], 200);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'metaData' => ['code' => 403, 'message' => 'Email belum diverifikasi. Cek inbox/spam.'],
                'response' => null
            ], 200);
        }

        if (!empty($user) && $this->checkHashEncypt($r->input('kataSandi'), $user->password)) {
            $kelompokUser = KelompokUser::select('id', 'kelompokuser as kelompokUser', 'menu')
                ->where('id', '=', 46)
                ->where('statusenabled', true)
                ->first();

            $pegawai = User::where('id', $user->id)
                ->where('statusenabled', true)
                ->first();

            $profile = Profile::where('id', '=', $user->kdprofile)
                ->select(
                    'id',
                    'namalengkap as namaprofile',
                    'alamatlengkap',
                    'alamatemail',
                    'fixedphone',
                    'website',
                    'namakota',
                    'lat',
                    'lng',
                    'logoprofil',
                    'namaexternal'
                )
                ->first();

            $resPegawai = array(
                'id' => $pegawai->id,
                'namaLengkap' => $pegawai->name,
                'mitrafk' => $pegawai->mitrafk,
                'jabatan' => $pegawai->jabatan,
                'statusEnabled' => $pegawai->statusenabled,
            );

            $dataLogin = array(
                'id' => $user->id,
                'kdProfile' => $user->kdprofile,
                'namaUser' => $user->name,
                'kelompokUser' => $kelompokUser,
                'pegawai' => $resPegawai,
                'profile' => $profile,
            );

            $expired = date("Y-m-d H:i:s", strtotime("+" . config('app.JWT_EXPIRED_MINUTE') . " minutes"));
            $response = array(
                'metaData' => array(
                    "code" => 200,
                    "message" => 'Sukses',
                ),
                'response' => array(
                    'token' => $this->createToken($user->email) . '.' . base64_encode((string)$user->kdprofile),
                    'expired' => $expired,
                    'data' => $dataLogin,
                ),
            );
        }

        return response()
            ->json($response, $response['metaData']['code'])
            ->cookie(
                'token',
                $response['response']['token'],
                120,
                '/',
                null,
                true,
                true,
                false,
                'Strict'
            );
    }

    public function registerUser(Request $r)
    {
        DB::beginTransaction();
        try {
            $PSN = $r['loginuser'];

            $channel = isset($PSN['channel']) ? strtolower($PSN['channel']) : 'email';
            if (!in_array($channel, ['email', 'wa'])) $channel = 'email';

            $normalizedNoWa = $this->normalizePhone($PSN['nowa'] ?? '');
            if (!preg_match('/^628\d{8,12}$/', $normalizedNoWa)) {
                return $this->respond(
                    [
                        'status' => 400,
                        'result' => 'Nomor WhatsApp tidak valid. Setelah awalan 62, ketik nomor mulai dari angka 8.'
                    ],
                    400,
                    'Gagal'
                );
            }

            if (User::where('email', $PSN['email'])->exists()) {
                return $this->respond(
                    [
                        'status' => 400,
                        'result' => 'Email sudah digunakan'
                    ],
                    400,
                    'Gagal'
                );
            }

            $user = new User();
            $user->id = $this->SEQUENCE_MASTER(new User(), 'id', $this->kdProfile);
            $user->kdprofile = (int)$this->kdProfile;
            $user->statusenabled = true;
            $user->name = $PSN['name'];
            $user->password = $this->hashing_password($PSN['password']);
            $user->email = $PSN['email'];
            $user->nowa = $normalizedNoWa;
            $user->isuserbaru = true;
            $user->save();

            $purpose = 'register';
            $otp = $this->createOtp($user, $purpose);
            $send = $this->sendOtp($user, $channel, $otp);

            if (empty($send['ok'])) {
                DB::rollBack();
                return $this->respond(
                    ['status' => 500, 'result' => 'Gagal mengirim OTP'],
                    500,
                    'Gagal'
                );
            }

            DB::commit();

            return $this->respond([
                'status' => 200,
                'result' => [
                    'pending_verification' => true,
                    'user_id' => $user->id,
                    'channel' => $channel,
                    'purpose' => $purpose,
                    'expires_at' => $otp->expires_at->toIso8601String(),
                ],
            ], 200, 'Sukses, OTP telah dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'status' => 400,
                'result' => $e->getMessage() . ' ' . $e->getLine()
            ], 400, 'Simpan Gagal');
        }
    }

    private function createOtp(User $user, string $purpose = 'register'): GoEmailOtp
    {
        GoEmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['expires_at' => Carbon::now()]);

        return GoEmailOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => $this->generateOtp(),
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes($this->otpTtlMinutes),
            'consumed_at' => null,
            'attempts' => 0,
        ]);
    }

    private function generateOtp(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendOtp(User $user, string $channel, GoEmailOtp $otp, int $expiresMinutes = 10): array
    {
        $purposeLabel = strtoupper($otp->purpose ?? 'VERIFIKASI');
        $spacedCode = trim(chunk_split($otp->code, 3, ' '));

        $msg = "🔐  U-LAB — Kode OTP {$purposeLabel}\n"
            . "Kode: {$spacedCode}\n"
            . "Berlaku {$expiresMinutes} menit.\n\n"
            . "Jangan bagikan kode ini kepada siapa pun.\n"
            . "Jika Anda tidak merasa meminta kode ini, abaikan pesan ini.";

        if ($channel === 'wa') {
            try {
                $wa = $this->kirimWhatsappNotifikasi($user->nowa, $msg);

                if ($wa['ok'] ?? false) {
                    return [
                        'ok' => true,
                        'channel' => 'wa',
                        'body' => $wa['raw'] ?? null,
                        'fallback' => false,
                    ];
                }

                Mail::to($user->email)->send(new OtpMail($otp->code, $otp->purpose, $expiresMinutes));
                return [
                    'ok' => true,
                    'channel' => 'email',
                    'body' => 'sent via email (fallback from wa)',
                    'fallback' => true,
                ];
            } catch (\Throwable $e) {
                Mail::to($user->email)->send(new OtpMail($otp->code, $otp->purpose, $expiresMinutes));
                return [
                    'ok' => true,
                    'channel' => 'email',
                    'body' => 'sent via email (fallback from wa: ' . $e->getMessage() . ')',
                    'fallback' => true,
                ];
            }
        }

        Mail::to($user->email)->send(new OtpMail($otp->code, $otp->purpose, $expiresMinutes));
        return [
            'ok' => true,
            'channel' => 'email',
            'body' => 'sent via email',
            'fallback' => false,
        ];
    }

    private function kirimWhatsappNotifikasi($nohp, $pesan)
    {
        $nomor = preg_replace('/^0/', '62', $nohp);
        $response = \App\Helpers\WhatsAppHelper::sendQueryString($nomor, $pesan);
        $result = $response->json();

        if (!$result['status']) {
            throw new \Exception("Gagal kirim WhatsApp: " . ($result['message'] ?? 'Tidak diketahui'));
        }

        return $result;
    }

    public function resendOtp(Request $r)
    {
        $userId = $r->input('user_id');
        $channel = strtolower($r->input('channel', 'email'));
        $purpose = $r->input('purpose', 'register');

        if (!$userId) {
            return response()->json(['status' => 400, 'message' => 'user_id wajib diisi'], 400);
        }

        if (!in_array($channel, ['email', 'wa'])) $channel = 'email';

        $user = User::findOrFail($userId);
        if ($channel === 'wa' && empty($user->nowa)) {
            return response()->json(['status' => 400, 'message' => 'No. WhatsApp belum ada'], 400);
        }

        $otp = $this->createOtp($user, $purpose);
        $send = $this->sendOtp($user, $channel, $otp);

        if (empty($send['ok'])) {
            return response()->json(['status' => 500, 'message' => 'Gagal mengirim OTP'], 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'OTP dikirim ulang.',
            'expires_at' => $otp->expires_at->toIso8601String(),
        ]);
    }

    public function verifyOtp(Request $r)
    {
        $userId = $r->input('user_id');
        $code = $r->input('code');
        $purpose = $r->input('purpose', 'register');

        if (!$userId || !$code) {
            return response()->json(['status' => 400, 'message' => 'user_id dan code wajib diisi'], 400);
        }

        $user = User::findOrFail($userId);

        $otp = GoEmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->first();

        if (!$otp) {
            return response()->json(['status' => 400, 'message' => 'OTP tidak ditemukan atau sudah kedaluwarsa.'], 400);
        }

        if ($otp->expires_at->isPast()) {
            return response()->json(['status' => 400, 'message' => 'OTP telah kedaluwarsa.'], 400);
        }

        if ($otp->attempts >= $this->maxVerifyAttempts) {
            return response()->json(['status' => 429, 'message' => 'Percobaan verifikasi melebihi batas. Minta OTP baru.'], 429);
        }

        if ($code !== $otp->code) {
            $otp->increment('attempts');
            return response()->json(['status' => 400, 'message' => 'Kode OTP salah.'], 400);
        }

        $otp->consumed_at = Carbon::now();
        $otp->save();

        if (is_null($user->email_verified_at)) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $user->save();

        return response()->json(['status' => 200, 'message' => 'Verifikasi berhasil.']);
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = (string)$phone;
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if ($phone === '') return '';

        if (Str::startsWith($phone, '62')) {
            return $phone;
        }

        if (Str::startsWith($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (Str::startsWith($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }

    private function isPhoneMatched(?string $dbPhone, ?string $inputPhone): bool
    {
        return $this->normalizePhone($dbPhone) === $this->normalizePhone($inputPhone);
    }

    public function forgotPasswordRequestOtp(Request $r)
    {
        $email = trim((string)$r->input('email'));
        $nowa = trim((string)$r->input('nowa'));
        $channel = strtolower((string)$r->input('channel', 'email'));
        $purpose = 'reset_password';

        if (empty($email) || empty($nowa)) {
            return response()->json([
                'status' => 400,
                'message' => 'Email dan No. HP wajib diisi.'
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'status' => 400,
                'message' => 'Format email tidak valid.'
            ], 400);
        }

        if (!in_array($channel, ['email', 'wa'])) {
            $channel = 'email';
        }

        $user = User::where('email', $email)
            ->where('statusenabled', true)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Customer dengan email tersebut tidak ditemukan.'
            ], 404);
        }

        if (empty($user->nowa)) {
            return response()->json([
                'status' => 400,
                'message' => 'No. HP customer belum terdaftar.'
            ], 400);
        }

        if (!$this->isPhoneMatched($user->nowa, $nowa)) {
            return response()->json([
                'status' => 400,
                'message' => 'Email dan No. HP tidak cocok.'
            ], 400);
        }

        if ($channel === 'wa' && empty($user->nowa)) {
            return response()->json([
                'status' => 400,
                'message' => 'No. WhatsApp customer belum tersedia.'
            ], 400);
        }

        $otp = $this->createOtp($user, $purpose);
        $send = $this->sendOtp($user, $channel, $otp, $this->otpTtlMinutes);

        if (empty($send['ok'])) {
            return response()->json([
                'status' => 500,
                'message' => 'Gagal mengirim OTP.'
            ], 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'OTP reset password berhasil dikirim.',
            'result' => [
                'user_id' => $user->id,
                'purpose' => $purpose,
                'channel' => $send['channel'] ?? $channel,
                'fallback' => $send['fallback'] ?? false,
                'expires_at' => $otp->expires_at->toIso8601String(),
                'email' => $user->email,
                'nowa' => $user->nowa,
            ]
        ], 200);
    }

    public function verifyForgotPasswordOtp(Request $r)
    {
        $userId = $r->input('user_id');
        $code = trim((string)$r->input('code'));
        $purpose = 'reset_password';

        if (!$userId || !$code) {
            return response()->json([
                'status' => 400,
                'message' => 'user_id dan code wajib diisi.'
            ], 400);
        }

        $user = User::where('id', $userId)
            ->where('statusenabled', true)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Customer tidak ditemukan.'
            ], 404);
        }

        $otp = GoEmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->first();

        if (!$otp) {
            return response()->json([
                'status' => 400,
                'message' => 'OTP tidak ditemukan atau sudah tidak aktif.'
            ], 400);
        }

        if ($otp->expires_at->isPast()) {
            return response()->json([
                'status' => 400,
                'message' => 'OTP telah kedaluwarsa.'
            ], 400);
        }

        if ($otp->attempts >= $this->maxVerifyAttempts) {
            return response()->json([
                'status' => 429,
                'message' => 'Percobaan OTP melebihi batas. Silakan minta OTP baru.'
            ], 429);
        }

        if ($code !== $otp->code) {
            $otp->increment('attempts');
            return response()->json([
                'status' => 400,
                'message' => 'Kode OTP salah.'
            ], 400);
        }

        return response()->json([
            'status' => 200,
            'message' => 'OTP valid. Silakan buat password baru.'
        ], 200);
    }

    public function resetPasswordByOtp(Request $r)
    {
        DB::beginTransaction();
        try {
            $userId = $r->input('user_id');
            $code = trim((string)$r->input('code'));
            $password = (string)$r->input('password');
            $passwordConfirmation = (string)$r->input('password_confirmation');
            $purpose = 'reset_password';

            if (!$userId || !$code || !$password || !$passwordConfirmation) {
                return response()->json([
                    'status' => 400,
                    'message' => 'user_id, code, password, dan password_confirmation wajib diisi.'
                ], 400);
            }

            if ($password !== $passwordConfirmation) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Konfirmasi password tidak sama.'
                ], 400);
            }

            if (strlen($password) < 6) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Password minimal 6 karakter.'
                ], 400);
            }

            $user = User::where('id', $userId)
                ->where('statusenabled', true)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Customer tidak ditemukan.'
                ], 404);
            }

            $otp = GoEmailOtp::where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->orderByDesc('id')
                ->first();

            if (!$otp) {
                return response()->json([
                    'status' => 400,
                    'message' => 'OTP tidak ditemukan atau sudah digunakan.'
                ], 400);
            }

            if ($otp->expires_at->isPast()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'OTP telah kedaluwarsa.'
                ], 400);
            }

            if ($otp->attempts >= $this->maxVerifyAttempts) {
                return response()->json([
                    'status' => 429,
                    'message' => 'Percobaan OTP melebihi batas. Silakan minta OTP baru.'
                ], 429);
            }

            if ($code !== $otp->code) {
                $otp->increment('attempts');
                return response()->json([
                    'status' => 400,
                    'message' => 'Kode OTP salah.'
                ], 400);
            }

            $user->password = $this->hashing_password($password);
            $user->save();

            $otp->consumed_at = Carbon::now();
            $otp->save();

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Password berhasil diperbarui. Silakan login kembali.'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'Gagal reset password: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkHashEncypt($password, $dbpass)
    {
        if (config('app.IS_PASSWORD_HASH')) {
            return Hash::check($password, $dbpass);
        } else {
            return $this->hashing_password($password) == $dbpass;
        }
    }

    public function createToken($namaUser)
    {
        $class = new Builder();
        $signer = new Sha512();
        $now = time();
        $token = $class->setHeader('alg', config('app.JWT_ALG'))
            ->set('sub', $namaUser)
            ->expiresAt($now + (config('app.JWT_EXPIRED_MINUTE') * 60))
            ->sign($signer, config('app.JWT_KEY'))
            ->getToken();

        return $token;
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        if (!$request->hasValidSignature()) {
            return response()->json([
                'status' => 403,
                'message' => 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.',
            ], 403);
        }

        $user = User::findOrFail($id);

        if (!hash_equals((string)$hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'status' => 403,
                'message' => 'Hash verifikasi tidak cocok.',
            ], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response()->json([
            'status' => 200,
            'message' => 'Email berhasil diverifikasi.',
        ]);
    }

    public function loginByFace(Request $r)
    {
        $descriptor = $r->input('descriptor');
        if (empty($descriptor)) {
            return response()->json([
                'metaData' => ['code' => 400, 'message' => 'Descriptor wajah kosong'],
                'response' => null
            ], 400);
        }

        $faces = DB::table('facerecognition_m as f')
            ->join('pegawai_m as p', 'p.id', '=', 'f.objectpegawaifk')
            ->join('loginuser_s as l', 'l.objectpegawaifk', '=', 'p.id')
            ->select(
                'f.id',
                'f.descriptor',
                'p.id as pegawai_id',
                'p.namalengkap',
                'l.id as login_id',
                'l.namauser',
                'l.katasandi',
                'l.kdprofile',
                'l.objectkelompokuserfk'
            )
            ->where('f.statusenabled', true)
            ->where('l.statusenabled', true)
            ->get();

        if ($faces->isEmpty()) {
            return response()->json([
                'metaData' => ['code' => 404, 'message' => 'Belum ada data wajah terdaftar'],
                'response' => null
            ], 404);
        }

        $input = collect($descriptor);
        $bestMatch = null;
        $lowestDistance = 999;

        foreach ($faces as $face) {
            $stored = collect(json_decode($face->descriptor, true));
            if ($stored->count() !== $input->count()) continue;

            $distance = sqrt($input->zip($stored)->reduce(fn($c, $pair) => $c + pow($pair[0] - $pair[1], 2), 0));
            if ($distance < $lowestDistance) {
                $lowestDistance = $distance;
                $bestMatch = $face;
            }
        }

        if (!$bestMatch || $lowestDistance > 0.45) {
            return response()->json([
                'metaData' => ['code' => 401, 'message' => 'Wajah tidak cocok'],
                'response' => null
            ], 401);
        }

        $login = DB::table('loginuser_s')->where('id', $bestMatch->login_id)->first();
        $pegawai = Pegawai::where('id', $bestMatch->pegawai_id)->where('statusenabled', true)->first();

        if (empty($pegawai)) {
            return response()->json([
                'metaData' => ['code' => 400, 'message' => 'Pegawai tidak aktif'],
                'response' => null
            ], 400);
        }

        $jenisPegawai = \App\Models\Master\JenisPegawai::where('id', '=', $pegawai->objectjenispegawaifk)
            ->select('id', 'jenispegawai')
            ->first();

        $ruangKerja = \App\Models\Master\Ruangan::where('id', '=', $pegawai->objectruangankerjafk)
            ->where('statusenabled', true)
            ->first();

        $kelompokUser = \App\Models\Standar\KelompokUser::select('id', 'kelompokuser as kelompokUser', 'menu')
            ->where('id', '=', $login->objectkelompokuserfk)
            ->where('statusenabled', true)
            ->first();

        $profile = \App\Models\Master\Profile::where('id', '=', $login->kdprofile)
            ->select(
                'id',
                'namalengkap as namaprofile',
                'alamatlengkap',
                'alamatemail',
                'fixedphone',
                'website',
                'namakota',
                'lat',
                'lng',
                'logoprofil',
                'namaexternal'
            )
            ->first();

        $resPegawai = array(
            'id' => $pegawai->id,
            'namaLengkap' => $pegawai->namalengkap,
            'tempatLahir' => $pegawai->tempatlahir,
            'tglLahir' => $pegawai->tgllahir,
            'noIdentitas' => $pegawai->noidentitas,
            'statusEnabled' => $pegawai->statusenabled,
            'jenisPegawai' => $jenisPegawai,
            'ruangan' => $ruangKerja,
            'jenisKelamin_id' => $pegawai->objectjeniskelaminfk,
        );

        $dataLogin = array(
            'id' => $login->id,
            'kdProfile' => $login->kdprofile,
            'namaUser' => $login->namauser,
            'kelompokUser' => $kelompokUser,
            'pegawai' => $resPegawai,
            'profile' => $profile,
        );

        $expired = date("Y-m-d H:i:s", strtotime("+" . config('app.JWT_EXPIRED_MINUTE') . " minutes"));
        $response = array(
            'metaData' => array(
                "code" => 200,
                "message" => 'Sukses',
            ),
            'response' => array(
                'token' => $this->createToken($login->namauser) . '.' . base64_encode((string)$login->kdprofile),
                'expired' => $expired,
                'data' => $dataLogin,
            ),
        );

        return response()
            ->json($response, $response['metaData']['code'])
            ->cookie(
                'token',
                $response['response']['token'],
                120,
                '/',
                null,
                true,
                true,
                false,
                'Strict'
            );
    }

    public function mobilePrintLogin(Request $r)
    {
        try {
            $namaUser = trim((string) ($r->input('namaUser') ?? $r->input('username')));
            $kataSandi = trim((string) ($r->input('kataSandi') ?? $r->input('password')));
            $norecDetail = trim((string) $r->input('norec_detail'));
            $jenis = trim((string) $r->input('jenis', 'sertifikat'));

            if ($namaUser === '' || $kataSandi === '') {
                return response()->json([
                    'metaData' => [
                        'code' => 400,
                        'message' => 'Username atau Password harus di isi',
                    ],
                    'response' => null,
                ], 400);
            }

            if ($norecDetail === '') {
                return response()->json([
                    'metaData' => [
                        'code' => 400,
                        'message' => 'norec_detail harus di isi',
                    ],
                    'response' => null,
                ], 400);
            }

            // pakai loginuser_s seperti login username biasa
            $login = LoginUser::where('namauser', '=', $namaUser)
                ->where('statusenabled', true)
                ->first();

            if (!$login || !$this->checkHashEncypt($kataSandi, $login->katasandi)) {
                return response()->json([
                    'metaData' => [
                        'code' => 400,
                        'message' => 'Login gagal, Username atau Password salah',
                    ],
                    'response' => null,
                ], 400);
            }

            $pegawai = Pegawai::where('id', $login->objectpegawaifk)
                ->where('statusenabled', true)
                ->first();

            if (empty($pegawai)) {
                return response()->json([
                    'metaData' => [
                        'code' => 400,
                        'message' => 'Pegawai tidak aktif',
                    ],
                    'response' => null,
                ], 400);
            }

            $profile = Profile::where('id', '=', $login->kdprofile)
                ->select(
                    'id',
                    'namalengkap as namaprofile',
                    'alamatlengkap',
                    'alamatemail',
                    'fixedphone',
                    'website',
                    'namakota',
                    'lat',
                    'lng',
                    'logoprofil',
                    'namaexternal'
                )
                ->first();

            // TOKEN HARUS PERSIS SEPERTI LOGIN WEB
            $token = $this->createToken($login->namauser) . '.' . base64_encode((string)$login->kdprofile);

            $baseService = rtrim(config('app.url'), '/') . '/service/';
            $printUrl = null;

            if (strtolower($jenis) === 'repair') {
                $printUrl = $baseService
                    . 'registrasi/cetak-laporan-repair-pdf?norec_detail=' . urlencode($norecDetail)
                    . '&user=' . urlencode($pegawai->namalengkap ?? $login->namauser)
                    . '&kdprofile=' . urlencode((string)$login->kdprofile)
                    . '&token=' . urlencode($token);
            } else {
                $printUrl = $baseService
                    . 'registrasi/cetak-sertif-customer-pdf?norec_detail=' . urlencode($norecDetail)
                    . '&user=' . urlencode($pegawai->namalengkap ?? $login->namauser)
                    . '&kdprofile=' . urlencode((string)$login->kdprofile)
                    . '&token=' . urlencode($token);
            }

            $expired = date("Y-m-d H:i:s", strtotime("+" . config('app.JWT_EXPIRED_MINUTE') . " minutes"));

            return response()->json([
                'metaData' => [
                    'code' => 200,
                    'message' => 'Sukses',
                ],
                'response' => [
                    'token' => $token,
                    'expired' => $expired,
                    'kdprofile' => $login->kdprofile,
                    'namaUser' => $login->namauser,
                    'namaLengkap' => $pegawai->namalengkap ?? $login->namauser,
                    'profile' => $profile,
                    'jenis' => $jenis,
                    'norec_detail' => $norecDetail,
                    'print_url' => $printUrl,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'metaData' => [
                    'code' => 500,
                    'message' => 'Gagal generate token print',
                ],
                'response' => $e->getMessage(),
            ], 500);
        }
    }

    public function mobilePrintFile(Request $r)
    {
        try {
            $auth = $this->validateMobilePrintCredentials($r);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            $document = $this->resolveMobilePrintDocument($r);
            if (isset($document['error'])) {
                return $document['error'];
            }

            $filename = $document['filename'];

            return response()->file($document['path'], [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $filename) . '"',
                'X-Mobile-Print-Filename' => $filename,
                'X-Mobile-Print-Jenis' => $document['jenis'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'metaData' => [
                    'code' => 500,
                    'message' => 'Gagal mengambil file print',
                ],
                'response' => $e->getMessage(),
            ], 500);
        }
    }

    public function mobilePrintRegistrationDocument(Request $r)
    {
        try {
            $auth = $this->validateMobilePrintCredentials($r);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            $norec = trim((string) $r->input('norec'));
            $jenis = strtolower(str_replace('-', '_', trim((string) $r->input('jenis'))));

            if ($norec === '') {
                return $this->mobilePrintErrorResponse(400, 'norec registrasi harus di isi');
            }

            session(['kdProfile' => (int) $auth['login']->kdprofile]);

            if ($jenis === 'ams') {
                $document = $this->resolveMobileAmsDocument($norec);
                if (isset($document['error'])) {
                    return $document['error'];
                }

                return $this->filePdfResponse($document['path'], $document['filename'], 'ams');
            }

            if ($jenis === 'tanda_terima') {
                $pdf = app(MitraCtrl::class)->cetakTandaTerima(new Request([
                    'norec' => $norec,
                    'storage' => true,
                ]));

                return $this->generatedPdfResponse(
                    $pdf,
                    'tanda_terima_' . $this->safeFilenamePart($norec) . '.pdf',
                    'tanda_terima'
                );
            }

            if ($jenis === 'permintaan_kalibrasi') {
                $pdf = app(MitraCtrl::class)->cetakPermintaanKalibrasi(new Request([
                    'norec' => $norec,
                    'pdf' => 'false',
                    'storage' => true,
                ]));

                return $this->generatedPdfResponse(
                    $pdf,
                    'permintaan_kalibrasi_' . $this->safeFilenamePart($norec) . '.pdf',
                    'permintaan_kalibrasi'
                );
            }

            if ($jenis === 'label_order' || $jenis === 'barcode_order') {
                $pdf = app(MitraCtrl::class)->cetakBarcodeOrder(new Request([
                    'norec' => $norec,
                    'pdf' => 'false',
                    'storage' => true,
                ]));

                return $this->generatedPdfResponse(
                    $pdf,
                    'label_order_' . $this->safeFilenamePart($norec) . '.pdf',
                    'label_order'
                );
            }

            if ($jenis === 'selesai_terima') {
                $terimafk = trim((string) $r->input('terimafk'));
                if ($terimafk === '') {
                    $terimafk = (string) DB::table('mitraregistrasi_terimah_t')
                        ->where('noregistrasifk', $norec)
                        ->where('statusenabled', true)
                        ->orderByDesc('versi')
                        ->orderByDesc('created_at')
                        ->value('norec');
                }

                if ($terimafk === '') {
                    return $this->mobilePrintErrorResponse(
                        404,
                        'Data tanda terima selesai belum tersedia'
                    );
                }

                $pdf = app(MitraCtrl::class)->cetakSelesaiTerima(new Request([
                    'terimafk' => $terimafk,
                    'storage' => true,
                ]));

                return $this->generatedPdfResponse(
                    $pdf,
                    'tanda_terima_selesai_' . $this->safeFilenamePart($norec) . '.pdf',
                    'selesai_terima'
                );
            }

            return $this->mobilePrintErrorResponse(400, 'Jenis dokumen tidak valid');
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil dokumen registrasi mobile', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return $this->mobilePrintErrorResponse(500, 'Gagal mengambil dokumen registrasi');
        }
    }

    public function mobileSelesaiTerimaTools(Request $r)
    {
        try {
            $auth = $this->validateMobilePrintCredentials($r);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            $norec = trim((string) ($r->input('norec') ?? $r->input('norec_pd')));
            if ($norec === '') {
                return $this->mobilePrintErrorResponse(400, 'norec registrasi harus di isi');
            }

            session(['kdProfile' => (int) $auth['login']->kdprofile]);

            return app(MitraCtrl::class)->LayananVerif(new Request([
                'norec_pd' => $norec,
            ]));
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil alat tanda terima selesai mobile', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return $this->mobilePrintErrorResponse(500, 'Gagal mengambil daftar alat tanda terima selesai');
        }
    }

    public function mobileSelesaiTerimaVersions(Request $r)
    {
        try {
            $auth = $this->validateMobilePrintCredentials($r);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            $norec = trim((string) $r->input('norec'));
            if ($norec === '') {
                return $this->mobilePrintErrorResponse(400, 'norec registrasi harus di isi');
            }

            session(['kdProfile' => (int) $auth['login']->kdprofile]);

            return app(MitraCtrl::class)->listVersiTerima(new Request([
                'norec' => $norec,
            ]));
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil versi tanda terima selesai mobile', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return $this->mobilePrintErrorResponse(500, 'Gagal mengambil riwayat tanda terima selesai');
        }
    }

    public function mobileSaveSelesaiTerima(Request $r)
    {
        try {
            $auth = $this->validateMobilePrintCredentials($r);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            $norec = trim((string) $r->input('norec'));
            if ($norec === '') {
                return $this->mobilePrintErrorResponse(400, 'norec registrasi harus di isi');
            }

            session(['kdProfile' => (int) $auth['login']->kdprofile]);

            return app(MitraCtrl::class)->saveSelesaiTerima($r);
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan tanda terima selesai mobile', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return $this->mobilePrintErrorResponse(500, 'Gagal menyimpan tanda terima selesai');
        }
    }

    private function validateMobilePrintCredentials(Request $r)
    {
        $namaUser = trim((string) ($r->input('namaUser') ?? $r->input('username')));
        $kataSandi = trim((string) ($r->input('kataSandi') ?? $r->input('password')));

        if ($namaUser === '' || $kataSandi === '') {
            return [
                'error' => response()->json([
                    'metaData' => [
                        'code' => 400,
                        'message' => 'Username atau Password harus di isi',
                    ],
                    'response' => null,
                ], 400),
            ];
        }

        $login = LoginUser::where('namauser', '=', $namaUser)
            ->where('statusenabled', true)
            ->first();

        if (!$login || !$this->checkHashEncypt($kataSandi, $login->katasandi)) {
            return [
                'error' => response()->json([
                    'metaData' => [
                        'code' => 400,
                        'message' => 'Login gagal, Username atau Password salah',
                    ],
                    'response' => null,
                ], 400),
            ];
        }

        $pegawai = Pegawai::where('id', $login->objectpegawaifk)
            ->where('statusenabled', true)
            ->first();

        if (empty($pegawai)) {
            return [
                'error' => response()->json([
                    'metaData' => [
                        'code' => 400,
                        'message' => 'Pegawai tidak aktif',
                    ],
                    'response' => null,
                ], 400),
            ];
        }

        return [
            'login' => $login,
            'pegawai' => $pegawai,
        ];
    }

    private function resolveMobileAmsDocument(string $norec): array
    {
        $data = DB::table('mitraregistrasi_t')
            ->where('norec', $norec)
            ->where('statusenabled', true)
            ->first();

        if (!$data || !$data->filecustomerams) {
            return [
                'error' => $this->mobilePrintErrorResponse(
                    404,
                    'Data AMS atau file tidak ditemukan'
                ),
            ];
        }

        $filename = basename($data->filecustomerams);
        $basePath = realpath(public_path('berkas-customer'));
        $filePath = realpath(public_path('berkas-customer' . DIRECTORY_SEPARATOR . $filename));

        if (!$basePath || !$filePath || !is_file($filePath)) {
            return [
                'error' => $this->mobilePrintErrorResponse(
                    404,
                    'File AMS tidak ditemukan di server'
                ),
            ];
        }

        $basePathWithSeparator = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($filePath, $basePathWithSeparator) !== 0) {
            return [
                'error' => $this->mobilePrintErrorResponse(403, 'Path file tidak valid'),
            ];
        }

        return [
            'path' => $filePath,
            'filename' => $filename,
        ];
    }

    private function generatedPdfResponse($pdf, string $filename, string $jenis)
    {
        if (!is_object($pdf) || !method_exists($pdf, 'output')) {
            return $this->mobilePrintErrorResponse(500, 'Generator PDF tidak valid');
        }

        $bytes = $pdf->output();
        if (empty($bytes)) {
            return $this->mobilePrintErrorResponse(404, 'File PDF kosong');
        }

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $filename) . '"',
            'X-Mobile-Print-Filename' => $filename,
            'X-Mobile-Print-Jenis' => $jenis,
        ]);
    }

    private function filePdfResponse(string $path, string $filename, string $jenis)
    {
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $filename) . '"',
            'X-Mobile-Print-Filename' => $filename,
            'X-Mobile-Print-Jenis' => $jenis,
        ]);
    }

    private function mobilePrintErrorResponse(int $status, string $message)
    {
        return response()->json([
            'metaData' => [
                'code' => $status,
                'message' => $message,
            ],
            'response' => null,
        ], $status);
    }

    private function safeFilenamePart(string $value): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $value);
        $safe = trim((string) $safe, '_');
        return $safe === '' ? 'dokumen' : $safe;
    }

    private function resolveMobilePrintDocument(Request $r)
    {
        $norecDetail = trim((string) $r->input('norec_detail'));
        $jenis = strtolower(trim((string) $r->input('jenis', 'sertifikat')));

        if ($norecDetail === '') {
            return [
                'error' => response()->json([
                    'metaData' => [
                        'code' => 400,
                        'message' => 'norec_detail harus di isi',
                    ],
                    'response' => null,
                ], 400),
            ];
        }

        $isVendor = in_array($jenis, ['vendor', 'vendor_sertifikat', 'sertifikat_vendor'], true);
        if ($isVendor) {
            $data = DB::table('mitraregistrasidetail_t')
                ->where('norec', $norecDetail)
                ->first();

            if (!$data || !$data->fileSertiVendor) {
                return [
                    'error' => response()->json([
                        'metaData' => [
                            'code' => 404,
                            'message' => 'Data sertifikat vendor atau file tidak ditemukan',
                        ],
                        'response' => null,
                    ], 404),
                ];
            }

            $filename = basename($data->fileSertiVendor);
            $basePath = realpath(public_path('berkas-vendor'));
            $filePath = realpath(public_path('berkas-vendor' . DIRECTORY_SEPARATOR . $filename));

            if (!$basePath || !$filePath || !is_file($filePath)) {
                return [
                    'error' => response()->json([
                        'metaData' => [
                            'code' => 404,
                            'message' => 'File sertifikat vendor tidak ditemukan di server',
                        ],
                        'response' => null,
                    ], 404),
                ];
            }

            $basePathWithSeparator = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (strpos($filePath, $basePathWithSeparator) !== 0) {
                return [
                    'error' => response()->json([
                        'metaData' => [
                            'code' => 403,
                            'message' => 'Path file tidak valid',
                        ],
                        'response' => null,
                    ], 403),
                ];
            }

            return [
                'path' => $filePath,
                'filename' => $filename,
                'jenis' => 'sertifikat_vendor',
                'data' => $data,
            ];
        }

        $isRepair = $jenis === 'repair';
        $table = $isRepair ? 'laporan_repair_log' : 'sertifikat_log';
        $folder = $isRepair ? 'laporan-repair' : 'sertifikat';
        $label = $isRepair ? 'laporan repair' : 'sertifikat';

        $query = DB::table($table)
            ->where('norec_detail', $norecDetail);

        if ($r->filled('version')) {
            $query->where('version', $r->input('version'));
        } else {
            $query->orderByDesc('version')->orderByDesc('id');
        }

        $data = $query->first();

        if (!$data || !$data->file_path) {
            return [
                'error' => response()->json([
                    'metaData' => [
                        'code' => 404,
                        'message' => 'Data ' . $label . ' atau file tidak ditemukan',
                    ],
                    'response' => null,
                ], 404),
            ];
        }

        $filename = basename($data->file_path);
        $basePath = realpath(public_path($folder));
        $filePath = realpath(public_path($folder . DIRECTORY_SEPARATOR . $filename));

        if (!$basePath || !$filePath || !is_file($filePath)) {
            return [
                'error' => response()->json([
                    'metaData' => [
                        'code' => 404,
                        'message' => 'File ' . $label . ' tidak ditemukan di server',
                    ],
                    'response' => null,
                ], 404),
            ];
        }

        $basePathWithSeparator = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($filePath, $basePathWithSeparator) !== 0) {
            return [
                'error' => response()->json([
                    'metaData' => [
                        'code' => 403,
                        'message' => 'Path file tidak valid',
                    ],
                    'response' => null,
                ], 403),
            ];
        }

        return [
            'path' => $filePath,
            'filename' => $filename,
            'jenis' => $isRepair ? 'repair' : 'sertifikat',
            'data' => $data,
        ];
    }
}
