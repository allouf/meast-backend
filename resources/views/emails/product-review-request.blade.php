@extends('emails.layouts.base')

@section('title', 'How was your purchase?')

@section('header')
    <h1>⭐ Share Your Experience</h1>
    <p>Help others by reviewing your recent purchase</p>
@endsection

@section('content')
    <h2>Hello {{ $customer->name ?? 'Valued Customer' }}!</h2>
    
    <p>We hope you're enjoying your recent purchase from <strong>{{ $store->name ?? 'our store' }}</strong>! Your feedback is incredibly valuable to us and helps other customers make informed decisions.</p>
    
    <div class="info-box">
        <h3>📦 Your Recent Purchase</h3>
        <p><strong>Product:</strong> {{ $product->name ?? 'Your Product' }}</p>
        <p><strong>Order Number:</strong> {{ $order->order_number ?? 'N/A' }}</p>
        <p><strong>Purchase Date:</strong> {{ $order->created_at->format('F j, Y') ?? now()->format('F j, Y') }}</p>
        <p><strong>Store:</strong> {{ $store->name ?? 'N/A' }}</p>
    </div>
    
    <div class="success-box">
        <h3>🌟 Why your review matters:</h3>
        <ul>
            <li><strong>Help other customers</strong> make informed purchasing decisions</li>
            <li><strong>Support small businesses</strong> and independent sellers</li>
            <li><strong>Improve our platform</strong> with your valuable feedback</li>
            <li><strong>Build trust</strong> in our marketplace community</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $reviewUrl ?? '#' }}" class="cta-button">Write a Review</a>
    </div>
    
    <div class="info-box">
        <h3>📝 Review Guidelines</h3>
        <p>When writing your review, please consider:</p>
        <ul>
            <li><strong>Product Quality:</strong> How well did the product meet your expectations?</li>
            <li><strong>Shipping & Delivery:</strong> Was the item delivered on time and in good condition?</li>
            <li><strong>Seller Communication:</strong> How was your experience with the seller?</li>
            <li><strong>Overall Experience:</strong> Would you recommend this product to others?</li>
        </ul>
    </div>
    
    <div class="warning-box">
        <h3>💡 Review Tips</h3>
        <ul>
            <li>Be honest and constructive in your feedback</li>
            <li>Include specific details about your experience</li>
            <li>Rate different aspects (quality, shipping, communication)</li>
            <li>Upload photos if they help illustrate your experience</li>
        </ul>
    </div>
    
    <p>Your review will be visible to other customers and will help them make better purchasing decisions. Thank you for taking the time to share your experience!</p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>Building a community of trusted reviews</p>
    
    <div class="divider"></div>
    
    <p>This review request was sent to {{ $customer->email ?? 'your email address' }} for your recent purchase from {{ $store->name ?? 'our store' }}.</p>
    <p>You can write your review anytime within the next 30 days.</p>
    
    <div class="social-links">
        <a href="#">Review Guidelines</a> |
        <a href="#">Contact Support</a> |
        <a href="#">Browse More Products</a>
    </div>
    
    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
