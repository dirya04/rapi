<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WhatsAppOtpService
{
    /**
     * Format phone number to standard international WhatsApp format without leading + or 0.
     * E.g. 08123456789 -> 628123456789
     */
    public function formatPhoneNumber(string $phone): string
    {
        $number = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        } elseif (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        } elseif (str_starts_with($number, '8')) {
            $number = '62' . $number;
        }

        return $number;
    }

    /**
     * Generate and dispatch a 6-digit OTP code to the user's WhatsApp number.
     */
    public function sendOtp(User $user): array
    {
        if (empty($user->phone_number)) {
            throw ValidationException::withMessages([
                'phone_number' => ['Nomor WhatsApp tidak terdaftar pada akun ini.'],
            ]);
        }

        $formattedPhone = $this->formatPhoneNumber($user->phone_number);

        if (!preg_match('/^\d{9,16}$/', $formattedPhone)) {
            throw ValidationException::withMessages([
                'phone_number' => ['Nomor telepon akun tidak valid untuk pengiriman WhatsApp.'],
            ]);
        }

        // Generate 6-digit numeric OTP
        $otp = sprintf('%06d', random_int(100000, 999999));

        $user->update([
            'otp_code'       => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $message = "Halo {$user->fullname},\n\n"
            . "Kode OTP pemulihan kata sandi RAPI-Sekolah Anda adalah:\n"
            . "*{$otp}*\n\n"
            . "Kode ini berlaku selama 10 menit. Jangan berikan kode ini kepada siapa pun demi keamanan akun Anda.";

        $this->dispatchWhatsAppMessage($formattedPhone, $message);

        return [
            'success'      => true,
            'phone_number' => $formattedPhone,
            'otp'          => $otp, // returned for debugging/simulation in dev/tests
            'expires_at'   => $user->otp_expires_at->toIso8601String(),
        ];
    }

    /**
     * Dispatch WhatsApp message through configured gateway API, with fallback to log.
     */
    protected function dispatchWhatsAppMessage(string $formattedPhone, string $message): void
    {
        $gatewayUrl = trim((string) (config('services.whatsapp.url') ?: env('WA_GATEWAY_URL')));
        $apiKey     = trim((string) (config('services.whatsapp.key') ?: env('WA_API_KEY')));

        if (!empty($gatewayUrl) && !empty($apiKey)) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['Authorization' => $apiKey])
                    ->post($gatewayUrl, [
                        'target'  => $formattedPhone,
                        'message' => $message,
                    ]);

                Log::info("WhatsApp Gateway [{$gatewayUrl}] status {$response->status()}: " . $response->body());
            } catch (\Throwable $e) {
                Log::warning('Gagal memanggil WhatsApp Gateway API: ' . $e->getMessage());
            }
        } else {
            Log::warning("Pesan WhatsApp ke [{$formattedPhone}] tidak terkirim via internet karena WA_API_KEY di file .env masih KOSONG. Isi WA_API_KEY untuk mengirimkan ke WhatsApp asli.");
        }

        // Always log OTP for auditable record and local simulation
        Log::info("WhatsApp OTP dispatched to [{$formattedPhone}]: {$message}");
    }

    /**
     * Check if given OTP is valid and unexpired for the user.
     */
    public function verifyOtp(User $user, string $otp): bool
    {
        if (empty($user->otp_code) || empty($user->otp_expires_at)) {
            return false;
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return false;
        }

        return hash_equals((string) $user->otp_code, trim($otp));
    }

    /**
     * Reset the user password after verifying OTP.
     */
    public function resetPassword(User $user, string $otp, string $newPassword): bool
    {
        if (!$this->verifyOtp($user, $otp)) {
            throw ValidationException::withMessages([
                'otp_code' => ['Kode OTP tidak valid atau telah kedaluwarsa.'],
            ]);
        }

        $user->update([
            'password'          => Hash::make($newPassword),
            'otp_code'          => null,
            'otp_expires_at'    => null,
            'phone_verified_at' => now(),
        ]);

        Log::info("Password reset successful for user [{$user->username}] via WhatsApp OTP.");

        return true;
    }
}
