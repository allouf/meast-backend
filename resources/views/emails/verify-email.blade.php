@extends('emails.layouts.base')

@section('title', 'Verify Your Email Address')

@section('header')
    <h1>🔐 Email Verification</h1>
    <p>Welcome to {{ config('app.name') }}!</p>
@endsection

@section('content')
    <h2>Hello {{ $user->name ?? 'there' }}!</h2>

    <p>Thank you for joining {{ config('app.name') }}! To complete your registration and secure your account, please verify
        your email address using the verification code below.</p>

    <div class="otp-code">
        <span>{{ $otp }}</span>
    </div>

    <div class="info-box">
        <h3>📋 Instructions:</h3>
        <ol>
            <li>Copy the verification code above</li>
            <li>Return to the verification page in your browser</li>
            <li>Enter the code when prompted</li>
            <li>Click "Verify Email" to complete the process</li>
        </ol>
    </div>

    <div class="warning-box">
        <h3>⚠️ Important Security Information:</h3>
        <ul>
            <li>This verification code will expire in <strong>10 minutes</strong></li>
            <li>Never share this code with anyone</li>
            <li>Our team will never ask for your verification code</li>
            <li>If you didn't create an account, please ignore this email</li>
        </ul>
    </div>

    <p>Once verified, you'll have full access to all {{ config('app.name') }} features and can start exploring our platform.
    </p>

    <p>If you're having trouble with the verification process, please contact our support team and we'll be happy to help.
    </p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>Your trusted e-commerce platform</p>

    <div class="divider"></div>

    <p>This email was sent to {{ $user->email ?? 'your email address' }} because you requested to verify your email address.
    </p>
    <p>If you didn't request this verification, please ignore this email or contact our support team.</p>

    <div class="social-links">
        <a href="#">Help Center</a> |
        <a href="#">Contact Support</a> |
        <a href="#">Privacy Policy</a>
    </div>

    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
