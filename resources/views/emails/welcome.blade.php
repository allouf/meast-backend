@extends('emails.layouts.base')

@section('title', 'Welcome to ' . config('app.name'))

@section('header')
    <h1>🎉 Welcome Aboard!</h1>
    <p>Your journey with {{ config('app.name') }} starts now</p>
@endsection

@section('content')
    <h2>Hello {{ $user->name ?? 'there' }}!</h2>

    <p>Welcome to {{ config('app.name') }}! We're thrilled to have you join our community of entrepreneurs, freelancers, and
        business owners.</p>

    <div class="success-box">
        <h3>🎯 What's next?</h3>
        <p>Your account has been successfully created and verified. Here's what you can do to get started:</p>
        <ol>
            <li><strong>Complete your profile:</strong> Add your photo, bio, and contact information</li>
            <li><strong>Explore the platform:</strong> Browse stores, products, and services</li>
            <li><strong>Create your first store:</strong> Start selling your products or services</li>
            <li><strong>Connect with others:</strong> Join our community and network</li>
        </ol>
    </div>

    <div class="info-box">
        <h3>🚀 Quick Start Guide</h3>
        <ul>
            <li><strong>For Sellers:</strong> Set up your store, add products, and start selling</li>
            <li><strong>For Buyers:</strong> Browse products, compare prices, and make purchases</li>
            <li><strong>For Freelancers:</strong> Create a professional profile and find opportunities</li>
            <li><strong>For Everyone:</strong> Explore our community features and connect</li>
        </ul>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl ?? '#' }}" class="cta-button">Go to Dashboard</a>
    </div>

    <div class="info-box">
        <h3>💡 Pro Tips for Success</h3>
        <ul>
            <li>Keep your profile updated and professional</li>
            <li>Use high-quality images for your products</li>
            <li>Respond quickly to messages and inquiries</li>
            <li>Read our community guidelines and best practices</li>
            <li>Take advantage of our analytics and insights</li>
        </ul>
    </div>

    <p>If you have any questions or need help getting started, our support team is here to assist you. Don't hesitate to
        reach out!</p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>Empowering your business journey</p>

    <div class="divider"></div>

    <p>This welcome email was sent to {{ $user->email ?? 'your email address' }} because you just joined
        {{ config('app.name') }}.</p>

    <div class="social-links">
        <a href="#">Getting Started Guide</a> |
        <a href="#">Contact Support</a> |
        <a href="#">Community Forum</a>
    </div>

    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
