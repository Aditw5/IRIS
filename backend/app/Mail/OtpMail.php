<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $purpose;
    public int $expiresMinutes;

    public function __construct(string $code, string $purpose = 'register', int $expiresMinutes = 10)
    {
        $this->code = $code;
        $this->purpose = $purpose;
        $this->expiresMinutes = $expiresMinutes > 0 ? $expiresMinutes : 10;
    }

    public function build()
    {
        $isPasswordReset = in_array(
            strtolower($this->purpose),
            ['reset_password', 'forgot_password'],
            true
        );
        $subject = $isPasswordReset
            ? 'Kode OTP Reset Password U-LAB'
            : 'Kode OTP Pendaftaran U-LAB';

        return $this->subject($subject)
            ->view('emails.otp')
            ->with([
                'code' => $this->code,
                'purpose' => $this->purpose,
                'expiresMinutes' => $this->expiresMinutes,
            ]);
    }
}
