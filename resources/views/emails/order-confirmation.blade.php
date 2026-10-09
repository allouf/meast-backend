@extends('emails.layouts.base')

@section('title', 'Order Confirmation - ' . ($order->order_number ?? 'Order'))

@section('header')
    <h1>✅ Order Confirmed!</h1>
    <p>Thank you for your purchase</p>
@endsection

@section('content')
    <h2>Hello {{ $customer->name ?? 'Valued Customer' }}!</h2>

    <p>Great news! Your order has been confirmed and is being processed. We'll send you another email once your items are
        shipped.</p>

    <div class="info-box">
        <h3>📋 Order Details</h3>
        <p><strong>Order Number:</strong> {{ $order->order_number ?? 'N/A' }}</p>
        <p><strong>Order Date:</strong>
            {{ $order->created_at->format('F j, Y \a\t g:i A') ?? now()->format('F j, Y \a\t g:i A') }}</p>
        <p><strong>Payment Method:</strong> {{ $order->payment_method ?? 'Credit Card' }}</p>
        <p><strong>Total Amount:</strong> ${{ number_format($order->total_amount ?? 0, 2) }}</p>
    </div>

    <div class="success-box">
        <h3>📦 Items Ordered</h3>
        @if (isset($orderItems) && count($orderItems) > 0)
            @foreach ($orderItems as $item)
                <div style="border-bottom: 1px solid #e2e8f0; padding: 15px 0;">
                    <p><strong>{{ $item->product_name ?? 'Product' }}</strong></p>
                    <p>Quantity: {{ $item->quantity ?? 1 }}</p>
                    <p>Price: ${{ number_format($item->price ?? 0, 2) }}</p>
                </div>
            @endforeach
        @else
            <p>Product details will be available shortly.</p>
        @endif
    </div>

    <div class="info-box">
        <h3>🚚 Shipping Information</h3>
        <p><strong>Shipping Address:</strong></p>
        <p>
            {{ $shippingAddress->name ?? 'N/A' }}<br>
            {{ $shippingAddress->address ?? 'N/A' }}<br>
            {{ $shippingAddress->city ?? 'N/A' }}, {{ $shippingAddress->state ?? 'N/A' }}
            {{ $shippingAddress->postal_code ?? 'N/A' }}<br>
            {{ $shippingAddress->country ?? 'N/A' }}
        </p>
        <p><strong>Estimated Delivery:</strong> {{ $estimatedDelivery ?? '3-5 business days' }}</p>
    </div>

    <div class="warning-box">
        <h3>📞 Need Help?</h3>
        <p>If you have any questions about your order or need to make changes, please contact us:</p>
        <ul>
            <li>Order tracking and updates</li>
            <li>Shipping address changes</li>
            <li>Product questions</li>
            <li>Returns and exchanges</li>
        </ul>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $trackingUrl ?? '#' }}" class="cta-button">Track Your Order</a>
    </div>

    <p>Thank you for choosing {{ config('app.name') }}! We appreciate your business and look forward to serving you again.
    </p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>Your trusted shopping destination</p>

    <div class="divider"></div>

    <p>This order confirmation was sent to {{ $customer->email ?? 'your email address' }} for order
        {{ $order->order_number ?? 'your order' }}.</p>
    <p>Keep this email for your records and order tracking.</p>

    <div class="social-links">
        <a href="#">Order Tracking</a> |
        <a href="#">Contact Support</a> |
        <a href="#">Return Policy</a>
    </div>

    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
