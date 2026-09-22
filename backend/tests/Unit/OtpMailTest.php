<?php

namespace Tests\Unit;

use App\Mail\OtpMail;
use Tests\TestCase;

class OtpMailTest extends TestCase
{
    public function test_registration_otp_email_uses_professional_ulab_branding(): void
    {
        $mail = new OtpMail('123456', 'register', 7);
        $html = $mail->render();

        $this->assertSame('Kode OTP Pendaftaran U-LAB', $mail->build()->subject);
        $this->assertStringContainsString('https://www.ulabumro.id/UMRO.png', $html);
        $this->assertStringContainsString('123 456', $html);
        $this->assertStringContainsString('7 menit', $html);
        $this->assertStringContainsString('© 2026 UMRO Laboratory. All rights reserved.', html_entity_decode($html));
    }

    public function test_reset_password_otp_email_has_correct_subject(): void
    {
        $mail = new OtpMail('654321', 'reset_password', 10);

        $this->assertSame('Kode OTP Reset Password U-LAB', $mail->build()->subject);
    }
}
