@extends('emails.layouts.base')

@section('title', 'Reset Your Password')

@section('header')
    <h1>🔑 Password Reset</h1>
    <p>Secure your account with a new password</p>
@endsection

@section('content')
    <h2>Hello {{ $user->name ?? 'there' }}!</h2>

    <p>We received a request to reset the password for your {{ config('app.name') }} account. If you made this request, use
        the verification code below to proceed with creating a new password.</p>

    <div class="otp-code">
        <span>{{ $otp }}</span>
    </div>

    <div class="info-box">
        <h3>📋 How to reset your password:</h3>
        <ol>
            <li>Copy the verification code above</li>
            <li>Return to the password reset page</li>
            <li>Enter the verification code</li>
            <li>Create a strong new password</li>
            <li>Confirm your new password</li>
        </ol>
    </div>

    <div class="warning-box">
        <h3>🛡️ Security Alert:</h3>
        <ul>
            <li>This verification code expires in <strong>15 minutes</strong></li>
            <li>Never share this code with anyone</li>
            <li>Our support team will never ask for this code</li>
            <li>If you didn't request this reset, please ignore this email</li>
            <li>Consider enabling two-factor authentication for added security</li>
        </ul>
    </div>

    <div class="success-box">
        <h3>💡 Password Security Tips:</h3>
        <ul>
            <li>Use at least 8 characters with a mix of letters, numbers, and symbols</li>
            <li>Avoid using personal information or common words</li>
            <li>Don't reuse passwords from other accounts</li>
            <li>Consider using a password manager</li>
        </ul>
    </div>

    <p>If you're having trouble resetting your password or didn't request this change, please contact our support team
        immediately.</p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>Keeping your account secure</p>

    <div class="divider"></div>

    <p>This password reset was requested for {{ $user->email ?? 'your email address' }} on
        {{ now()->format('F j, Y \a\t g:i A') }}.</p>
    <p>If you didn't request this password reset, please contact our support team immediately.</p>

    <div class="social-links">
        <a href="#">Security Center</a> |
        <a href="#">Contact Support</a> |
        <a href="#">Report Suspicious Activity</a>
    </div>

    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
