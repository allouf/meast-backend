@extends('emails.layouts.base')

@section('title', __('Application submitted successfully'))

@section('header')
    <h1>Application submitted</h1>
    <p>Your job application has been received and validated</p>
@endsection

@section('content')
    <h2>Hello {{ $user->name ?? 'there' }}!</h2>

    <p>Your application for <strong>{{ $jobTitle }}</strong> has been successfully submitted and validated.</p>

    <div class="success-box">
        <p>We have received your resume and cover letter. The employer may contact you if your profile matches their requirements.</p>
    </div>

    <p>Thank you for using {{ config('app.name') }}.</p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>This email was sent to {{ $user->email ?? 'your email address' }}.</p>
    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
