<?php

namespace Modules\Core\Services;

use Modules\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Core\Mail\ResetPasswordMail;
use Modules\Core\Mail\VerifyEmailMail;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerificationService
{
    public function __construct(protected UserService $userService) {}

    public function sendVerification(User $user, string $case = 'verify'): void
    {
        $contact = $this->getContact($user);
        $type = $this->getContactType($contact);
        $otp = $this->generateOtp();
        DB::table('verifications')->where('contact', $contact)->where('type', $type)->delete();
        $this->storeOtp($contact, $type, $otp);
        match ($case) {
            'verify' => $type === 'email' ? $this->sendVerifyEmail($user, $otp) : $this->sendSms($contact, $otp),
            'reset_password' => $type === 'email' ? $this->sendResetPasswordEmail($user, $otp) : $this->sendSms($contact, $otp),
            default => null,
        };
    }

    public function resendOtp(User $user, string $case = 'verify'): void
    {
        $contact = $this->getContact($user);
        $type = $this->getContactType($contact);
        $otp = $this->generateOtp();
        $existingOtp = DB::table('verifications')->where('contact', $contact)->where('type', $type)->first();
        if ($existingOtp) {
            DB::table('verifications')->where('contact', $contact)->where('type', $type)->update([
                'code' => $otp,
                'expires_at' => now()->addMinutes(10),
            ]);
        } else {
            $this->storeOtp($contact, $type, $otp);
        }
        match ($case) {
            'verify' => $type === 'email' ? $this->sendVerifyEmail($user, $otp) : $this->sendSms($contact, $otp),
            'reset_password' => $type === 'email' ? $this->sendResetPasswordEmail($user, $otp) : $this->sendSms($contact, $otp),
            default => null,
        };
    }

    public function checkOtp(string $contact, string $otp, string $case = 'verify'): bool|string
    {
        $type = $this->getContactType($contact);
        $record = DB::table('verifications')->where('contact', $contact)->where('type', $type)->where('code', $otp)->first();
        if (!$record || Carbon::parse($record->expires_at)->isPast()) {
            return false;
        }
        DB::table('verifications')->where('contact', $contact)->where('type', $type)->where('code', $otp)->delete();
        $user = $this->getUserByContact($contact);
        if ($user && !$user->verified_at && $case === 'verify') {
            $this->userService->markVerified($user);
        }
        if ($case === 'reset_password') {
            return $this->generatePasswordResetToken($contact);
        }
        return true;
    }

    public function verifyPasswordResetToken(string $contact, string $token): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $contact)->first();
        if (!$record) return false;
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $contact)->delete();
            return false;
        }
        return hash_equals($record->token, hash('sha256', $token));
    }

    public function deletePasswordResetToken(string $contact): void
    {
        DB::table('password_reset_tokens')->where('email', $contact)->delete();
    }

    protected function generatePasswordResetToken(string $contact): string
    {
        DB::table('password_reset_tokens')->where('email', $contact)->delete();
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $contact,
            'token' => hash('sha256', $token),
            'created_at' => now(),
        ]);
        return $token;
    }

    public function getUserByContact(string $contact): ?User
    {
        return $this->getContactType($contact) === 'email'
            ? $this->userService->getUserByEmail($contact)
            : $this->userService->getUserByPhone($contact);
    }

    protected function getContact(User $user): string
    {
        return $user->email ?? $user->phone ?? throw new HttpException(400, 'User has no email or phone');
    }

    protected function getContactType(string $contact): string
    {
        return filter_var($contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
    }

    protected function generateOtp(): string
    {
        if (app()->environment(['local', 'testing'])) {
            return '123456';
        }
        return (string) random_int(100000, 999999);
    }

    protected function storeOtp(string $contact, string $type, string $otp): void
    {
        try {
            DB::table('verifications')->insert([
                'contact' => $contact,
                'type' => $type,
                'code' => $otp,
                'expires_at' => now()->addMinutes(10),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to store OTP for {$contact}: {$e->getMessage()}");
        }
    }

    protected function sendVerifyEmail(User $user, string $otp): void
    {
        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user, $otp));
        } catch (\Exception $e) {
            Log::error("Failed to send verification email to {$user->id}: {$e->getMessage()}");
        }
    }

    protected function sendResetPasswordEmail(User $user, string $otp): void
    {
        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $otp));
        } catch (\Exception $e) {
            Log::error("Failed to send reset password email to {$user->id}: {$e->getMessage()}");
        }
    }

    protected function sendSms(string $phone, string $otp): void
    {
        Log::info("Send SMS to {$phone} with OTP {$otp}");
    }
}
