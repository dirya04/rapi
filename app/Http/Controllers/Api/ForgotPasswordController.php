<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppOtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /**
     * Find user by identifier (phone_number, username, or email).
     */
    protected function findUserByIdentifier(string $identifier, WhatsAppOtpService $otpService): User
    {
        $rawPhone = $otpService->formatPhoneNumber($identifier);

        $user = User::query()
            ->where('username', $identifier)
            ->orWhere('phone_number', $identifier)
            ->orWhere('phone_number', '0' . substr($rawPhone, 2))
            ->orWhere('phone_number', $rawPhone)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'identifier' => ['Akun dengan identitas / nomor WhatsApp tersebut tidak ditemukan.'],
            ]);
        }

        return $user;
    }

    /**
     * 1. Request OTP via WhatsApp
     */
    public function sendOtp(Request $request, WhatsAppOtpService $otpService)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $user = $this->findUserByIdentifier($data['identifier'], $otpService);
        $result = $otpService->sendOtp($user);

        $masked = substr($result['phone_number'], 0, 4) . '****' . substr($result['phone_number'], -3);

        return response()->json([
            'message'      => 'Kode OTP verifikasi telah dikirimkan ke WhatsApp Anda.',
            'phone_masked' => $masked,
            'expires_at'   => $result['expires_at'],
        ]);
    }

    /**
     * 2. Verify OTP code (Optional pre-check)
     */
    public function verifyOtp(Request $request, WhatsAppOtpService $otpService)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'otp_code'   => ['required', 'string', 'size:6'],
        ]);

        $user = $this->findUserByIdentifier($data['identifier'], $otpService);

        if (!$otpService->verifyOtp($user, $data['otp_code'])) {
            return response()->json([
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.',
                'valid'   => false,
            ], 422);
        }

        return response()->json([
            'message' => 'Kode OTP valid.',
            'valid'   => true,
        ]);
    }

    /**
     * 3. Submit OTP code and reset password
     */
    public function reset(Request $request, WhatsAppOtpService $otpService)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'otp_code'   => ['required', 'string', 'size:6'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->findUserByIdentifier($data['identifier'], $otpService);
        $otpService->resetPassword($user, $data['otp_code'], $data['password']);

        return response()->json([
            'message' => 'Kata sandi berhasil diperbarui. Silakan login kembali dengan password baru.',
        ]);
    }
}
