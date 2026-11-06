<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WhatsAppOtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
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

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request, WhatsAppOtpService $otpService)
    {
        $data = $request->validate([
            'identifier' => 'required|string',
        ]);

        $user = $this->findUserByIdentifier($data['identifier'], $otpService);
        $result = $otpService->sendOtp($user);

        session([
            'reset_identifier'   => $user->username,
            'reset_phone_masked' => substr($result['phone_number'], 0, 4) . '****' . substr($result['phone_number'], -3),
        ]);

        return redirect()->route('password.reset.form')
            ->with('success', 'Kode OTP 6-digit telah dikirimkan via WhatsApp ke ' . session('reset_phone_masked') . '.');
    }

    public function showResetForm(Request $request)
    {
        $identifier = session('reset_identifier') ?? $request->query('identifier');
        $phoneMasked = session('reset_phone_masked');

        return view('auth.reset-password', compact('identifier', 'phoneMasked'));
    }

    public function resetPassword(Request $request, WhatsAppOtpService $otpService)
    {
        $data = $request->validate([
            'identifier'            => 'required|string',
            'otp_code'              => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = $this->findUserByIdentifier($data['identifier'], $otpService);
        $otpService->resetPassword($user, $data['otp_code'], $data['password']);

        session()->forget(['reset_identifier', 'reset_phone_masked']);

        return redirect()->route('login')
            ->with('success', 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.');
    }
}
