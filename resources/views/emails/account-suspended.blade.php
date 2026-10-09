@extends('emails.layouts.base')

@section('title', 'Account Suspension Notice')

@section('header')
    <h1>⚠️ Account Suspended</h1>
    <p>Important notice regarding your account</p>
@endsection

@section('content')
    <h2>Hello {{ $user->name ?? 'there' }}!</h2>
    
    <p>We're writing to inform you that your {{ config('app.name') }} account has been temporarily suspended due to a violation of our Terms of Service.</p>
    
    <div class="warning-box">
        <h3>🚨 Suspension Details</h3>
        <p><strong>Reason:</strong> {{ $suspensionReason ?? 'Terms of Service Violation' }}</p>
        <p><strong>Suspension Date:</strong> {{ $suspensionDate ?? now()->format('F j, Y \a\t g:i A') }}</p>
        <p><strong>Account Status:</strong> Temporarily Suspended</p>
    </div>
    
    <div class="info-box">
        <h3>📋 What this means:</h3>
        <ul>
            <li>You cannot access your account or make new transactions</li>
            <li>Your existing listings and content are temporarily hidden</li>
            <li>You cannot send or receive messages</li>
            <li>Your account data remains secure and intact</li>
        </ul>
    </div>
    
    <div class="success-box">
        <h3>🔧 How to resolve this:</h3>
        <ol>
            <li><strong>Review our Terms of Service</strong> to understand the violation</li>
            <li><strong>Contact our support team</strong> to discuss the suspension</li>
            <li><strong>Provide any requested information</strong> to help resolve the issue</li>
            <li><strong>Wait for review</strong> - we'll respond within 24-48 hours</li>
        </ol>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $appealUrl ?? '#' }}" class="cta-button">Appeal Suspension</a>
    </div>
    
    <div class="warning-box">
        <h3>📞 Need Help?</h3>
        <p>If you believe this suspension is in error or have questions, please contact our support team immediately:</p>
        <ul>
            <li>Email: support@{{ config('app.domain', 'example.com') }}</li>
            <li>Include your account email and this suspension notice</li>
            <li>Provide any relevant information or documentation</li>
        </ul>
    </div>
    
    <p>We're committed to maintaining a safe and fair environment for all users. Thank you for your understanding and cooperation.</p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>Maintaining a safe community for everyone</p>
    
    <div class="divider"></div>
    
    <p>This suspension notice was sent to {{ $user->email ?? 'your email address' }} on {{ now()->format('F j, Y \a\t g:i A') }}.</p>
    <p>Please keep this email for your records and reference when contacting support.</p>
    
    <div class="social-links">
        <a href="#">Terms of Service</a> |
        <a href="#">Community Guidelines</a> |
        <a href="#">Contact Support</a>
    </div>
    
    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
