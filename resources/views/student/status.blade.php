@extends('layouts.app')
@section('title', 'Status')
@section('content')
<div class="card">
    <h2>My Application Status</h2>
    <p><span class="badge">{{ $application->stage_label }}</span></p>
</div>

{{-- Step 6: exam result (only once released) --}}
<div class="card">
    <h3>CICS Exam</h3>
    @if ($application->examAppointment) <p>Scheduled: {{ $application->examAppointment->appointment_date->format('F j, Y') }} · Ref <code>{{ $application->examAppointment->reference_no }}</code></p> @endif
    @if ($application->exam_result_released_at)
        @if ($application->exam_result === 'passed')
            <p><span class="badge ok">PASSED</span> You may now schedule your Interview &amp; submission of requirements.</p>
        @else
            <p><span class="badge bad">FAILED</span></p>
            <div class="alert error">This account will be disposed on <strong>{{ $application->dispose_at?->format('F j, Y') }}</strong> (within {{ config('cics.dispose_days') }} days of the release).</div>
        @endif
    @else
        <p class="muted">{{ $application->rank >= 3 ? 'Your result has not been released yet. Check back later.' : 'You have not taken the exam yet.' }}</p>
    @endif
</div>

{{-- Steps 7-9 --}}
@if ($application->exam_result === 'passed' && $application->exam_result_released_at)
<div class="card">
    <h3>Interview &amp; Requirements</h3>
    @if ($application->interviewAppointment)
        <p>Scheduled: {{ $application->interviewAppointment->appointment_date->format('F j, Y') }} · Ref <code>{{ $application->interviewAppointment->reference_no }}</code></p>
    @else <p class="muted">Not scheduled yet.</p> @endif

    @if (in_array($application->stage, ['approved','batched']))
        <p><span class="badge ok">APPROVED</span></p>
    @elseif ($application->stage === 'not_approved')
        <p><span class="badge bad">NOT APPROVED</span> Please contact the CICS office.</p>
    @elseif ($application->stage === 'interview_scheduled')
        <p class="muted">Awaiting evaluation after your interview.</p>
    @endif
</div>
@endif

@if ($application->stage === 'batched')
<div class="card">
    <h3>Department &amp; Other Fees</h3>
    <p>You are in <span class="badge ok">Batch {{ $application->fee_batch }}</span> — payment date: <strong>{{ $application->fee_payment_date->format('l, F j, Y') }}</strong></p>
</div>
@elseif ($application->stage === 'approved')
<div class="card"><h3>Department &amp; Other Fees</h3><p class="muted">You will be assigned to a payment batch soon.</p></div>
@endif
@endsection
