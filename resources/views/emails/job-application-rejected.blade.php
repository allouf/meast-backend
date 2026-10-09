@extends('emails.layouts.base')

@section('title', __('Application could not be accepted'))

@section('header')
    <h1>Application update</h1>
    <p>Your job application could not be accepted</p>
@endsection

@section('content')
    <h2>Hello {{ $user->name ?? 'there' }}!</h2>

    <p>Your application for <strong>{{ $jobTitle }}</strong> could not be accepted.</p>

    <div class="info-box">
        <p><strong>Reason:</strong> {{ $reason }}</p>
    </div>

    <p>You may submit a new application with a different resume file if the issue was related to the uploaded document.</p>

    <p>If you have questions, please contact support.</p>
@endsection

@section('footer')
    <p><strong>{{ config('app.name') }}</strong></p>
    <p>This email was sent to {{ $user->email ?? 'your email address' }}.</p>
    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
