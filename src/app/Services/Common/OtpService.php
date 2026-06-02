<?php

namespace App\Services\Common;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Services\AbstractService;
use Exception;
use Illuminate\Support\Facades\Mail;

class OtpService extends AbstractService
{
    private const OTP_EXPIRY_MINUTES = 5;

    private const OTP_LENGTH = 6;

    /**
     * Generate and store a new OTP
     *
     * @throws Exception
     */
    public function generateOtp(string $identifier, string $type): Otp
    {
        $this->beginTransaction();
        try {
            // Invalidate previous unused OTPs for same identifier and type
            Otp::where('identifier', $identifier)
                ->where('type', $type)
                ->where('is_used', false)
                ->update(['is_used' => true]);

            // Generate new OTP
            $code = str_pad(random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

            $otp = Otp::create([
                'identifier' => $identifier,
                'code' => $code,
                'type' => $type,
                'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                'is_used' => false,
            ]);

            $this->commitTransaction();

            // Send OTP via Email
            $this->sendOtpEmail($identifier, $code, $type);

            return $otp;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Send OTP via Email
     */
    private function sendOtpEmail(string $email, string $code, string $type): void
    {
        try {
            Mail::to($email)->queue(new OtpMail($code, $type, self::OTP_EXPIRY_MINUTES));
        } catch (Exception $e) {
            report($e);
        }
    }

    /**
     * Verify OTP code
     *
     * @throws Exception
     */
    public function verifyOtp(string $identifier, string $code, string $type): bool
    {
        $otp = Otp::where('identifier', $identifier)
            ->where('code', $code)
            ->where('type', $type)
            ->where('is_used', false)
            ->first();

        if (! $otp) {
            throw new Exception('otp.invalid');
        }

        if ($otp->isExpired()) {
            throw new Exception('otp.expired');
        }

        $otp->markAsUsed();

        return true;
    }

    /**
     * Resend OTP (generate new one)
     *
     * @throws Exception
     */
    public function resendOtp(string $identifier, string $type): Otp
    {
        return $this->generateOtp($identifier, $type);
    }
}
