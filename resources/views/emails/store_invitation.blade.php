@extends('emails.layouts.base')

@section('title', 'Store Team Invitation')

@section('header')
    <h1>🎉 You're Invited!</h1>
    <p>Join {{ $store->name }} as a team member</p>
@endsection

@section('content')
    <h2>Hello there!</h2>

    <p>You've been invited to join <strong>{{ $store->name }}</strong> as a team member! This is an exciting opportunity to
        collaborate and contribute to a growing business.</p>

    <div class="info-box">
        <h3>🏪 Store Information</h3>
        <p><strong>Store Name:</strong> {{ $store->name }}</p>
        @if ($store->description)
            <p><strong>Description:</strong> {{ $store->description }}</p>
        @endif
        <p><strong>Invited by:</strong> {{ $inviter->name ?? 'Store Owner' }}</p>
        <p><strong>Invited on:</strong> {{ $invitedAt->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="success-box">
        <h3>👤 Your Role: {{ $roleDisplay }}</h3>
        <p>You have been assigned the <strong>{{ $roleDisplay }}</strong> role in this store. This role comes with specific
            permissions and responsibilities that will help you contribute effectively to the team.</p>
    </div>

    <div class="info-box">
        <h3>🔐 Your Permissions</h3>
        <p>As a {{ $roleDisplay }}, you will have access to the following features:</p>

        @foreach ($permissionsByCategory as $category => $categoryPermissions)
            <div style="margin: 20px 0;">
                <h4 style="color: #4a5568; margin-bottom: 10px;">{{ $category }}</h4>
                <ul style="list-style: none; padding: 0;">
                    @foreach ($categoryPermissions as $permission)
                        <li
                            style="background: #f0f8ff; margin: 5px 0; padding: 8px 12px; border-radius: 4px; border-left: 3px solid #667eea;">
                            {{ ucwords(str_replace('.', ' ', $permission)) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="warning-box">
        <h3>⚠️ Important Information</h3>
        <ul>
            <li>This invitation will expire in <strong>7 days</strong></li>
            <li>You must use the email address that received this invitation</li>
            <li>By accepting, you agree to the store's terms and policies</li>
            <li>Your role and permissions can be modified by store owners</li>
            <li>You can leave the store at any time if needed</li>
        </ul>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $inviteUrl }}" class="cta-button">View Invitation Details & Accept</a>
    </div>

    <p>If you have any questions about this invitation or need more information about the store, feel free to contact the
        store owner or our support team.</p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>Connecting businesses with talented team members</p>

    <div class="divider"></div>

    <p>If you cannot click the button above, copy and paste this link into your browser to view the invitation details and
        accept it:</p>
    <p
        style="word-break: break-all; color: #667eea; font-family: monospace; background: #f7fafc; padding: 10px; border-radius: 4px;">
        {{ $inviteUrl }}</p>

    <p>If you did not expect this invitation, please ignore this email or contact our support team.</p>

    <div class="social-links">
        <a href="#">Help Center</a> |
        <a href="#">Contact Support</a> |
        <a href="#">Store Guidelines</a>
    </div>

    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
