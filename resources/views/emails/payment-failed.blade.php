@extends('emails.layouts.base')

@section('title', 'Payment Failed - Action Required')

@section('header')
    <h1>💳 Payment Failed</h1>
    <p>We need your attention to complete your order</p>
@endsection

@section('content')
    <h2>Hello {{ $customer->name ?? 'Valued Customer' }}!</h2>
    
    <p>We encountered an issue processing your payment for order <strong>{{ $order->order_number ?? 'your recent order' }}</strong>. Don't worry - your order is still reserved for you, but we need to update your payment information.</p>
    
    <div class="warning-box">
        <h3>🚨 Payment Issue Details</h3>
        <p><strong>Order Number:</strong> {{ $order->order_number ?? 'N/A' }}</p>
        <p><strong>Amount:</strong> ${{ number_format($order->total_amount ?? 0, 2) }}</p>
        <p><strong>Payment Method:</strong> {{ $order->payment_method ?? 'Credit Card' }}</p>
        <p><strong>Failure Reason:</strong> {{ $failureReason ?? 'Payment could not be processed' }}</p>
    </div>
    
    <div class="info-box">
        <h3>📋 Common reasons for payment failure:</h3>
        <ul>
            <li>Insufficient funds in your account</li>
            <li>Expired credit card</li>
            <li>Incorrect billing information</li>
            <li>Bank security restrictions</li>
            <li>International transaction limits</li>
        </ul>
    </div>
    
    <div class="success-box">
        <h3>🔧 How to resolve this:</h3>
        <ol>
            <li><strong>Check your payment method</strong> - ensure it's valid and has sufficient funds</li>
            <li><strong>Update your payment information</strong> if needed</li>
            <li><strong>Try a different payment method</strong> if the issue persists</li>
            <li><strong>Contact your bank</strong> if you suspect a security block</li>
        </ol>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $retryPaymentUrl ?? '#' }}" class="cta-button">Update Payment Method</a>
    </div>
    
    <div class="warning-box">
        <h3>⏰ Important Timeline</h3>
        <p>Your order will be held for <strong>24 hours</strong> while you update your payment information. After this period, your order may be automatically cancelled to free up inventory for other customers.</p>
    </div>
    
    <div class="info-box">
        <h3>💡 Need Help?</h3>
        <p>If you're having trouble updating your payment or have questions, our support team is here to help:</p>
        <ul>
            <li>Live chat support available 24/7</li>
            <li>Email support: payments@{{ config('app.domain', 'example.com') }}</li>
            <li>Phone support during business hours</li>
        </ul>
    </div>
    
    <p>We want to make sure you receive your order successfully. Please don't hesitate to reach out if you need any assistance.</p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>Secure and reliable payment processing</p>
    
    <div class="divider"></div>
    
    <p>This payment failure notice was sent to {{ $customer->email ?? 'your email address' }} for order {{ $order->order_number ?? 'your order' }}.</p>
    <p>Please update your payment information as soon as possible to complete your order.</p>
    
    <div class="social-links">
        <a href="#">Payment Methods</a> |
        <a href="#">Contact Support</a> |
        <a href="#">Order Status</a>
    </div>
    
    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
