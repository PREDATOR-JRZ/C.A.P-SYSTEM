@extends('layouts.app')
@section('title', 'Status')
@section('content')
<div class="card">
    <h2>My Application Status</h2>
    <p><span class="badge">{{ $application->stage_label }}</span></p>
</div>

<div class="card">
    <h3>CICS Evaluation Exam</h3>
    @if ($application->examAppointment) <p>Scheduled: {{ $application->examAppointment->appointment_date->format('F j, Y') }} · Ref <code>{{ $application->examAppointment->reference_no }}</code></p> @endif
    @if ($application->exam_result_released_at)
        @if ($application->exam_result === 'passed')
            <p><span class="badge ok">PASSED</span> You may proceed according to the next gate.</p>
        @else
            <p><span class="badge bad">FAILED</span></p>
            <div class="alert error">This account will be handled according to disposal policy{{ $application->dispose_at ? ' on '.$application->dispose_at->format('F j, Y') : '' }}.</div>
        @endif
    @else
        <p class="muted">{{ $application->examAppointment ? 'Your result has not been released yet.' : 'No exam scheduled yet.' }}</p>
    @endif
</div>

<div class="card">
    <h3>Interview &amp; Final Evaluation</h3>
    @if ($application->interviewAppointment)
        <p>Scheduled: {{ $application->interviewAppointment->appointment_date->format('F j, Y') }} · Ref <code>{{ $application->interviewAppointment->reference_no }}</code></p>
    @else
        <p class="muted">Not scheduled yet.</p>
    @endif

    @if (in_array($application->stage, ['accepted_for_clearance','eligible_for_official_enrollment','prism_pending','officially_enrolled']))
        <p><span class="badge ok">APPROVED / ACCEPTED FOR CLEARANCE</span></p>
    @elseif ($application->stage === 'not_approved')
        <p><span class="badge bad">NOT APPROVED</span> Please contact the CICS office.</p>
    @elseif ($application->stage === 'interview_scheduled')
        <p class="muted">Awaiting evaluation after your interview.</p>
    @endif
</div>

<div class="card">
    <h3>Pre-PRISM Fee Clearance</h3>
    @if ($application->feeClearances->isEmpty())
        <p class="muted">Fee clearance records will appear after interview acceptance.</p>
    @else
        @foreach ($application->feeClearances as $fee)
            <div style="border-top:1px solid #eee;padding:.75rem 0">
                <p><strong>{{ $fee->label }}</strong> — <span class="badge {{ $fee->isCleared() ? 'ok' : ($fee->status === 'pending_verification' ? 'warn' : 'bad') }}">{{ $fee->status }}</span></p>
                @if($fee->reference_no)<p class="muted">Reference: {{ $fee->reference_no }}</p>@endif
                @if($fee->student_visible_note ?? false)<p class="muted">{{ $fee->student_visible_note }}</p>@endif
                @if(!$fee->isCleared() && in_array($application->stage, ['accepted_for_clearance','eligible_for_official_enrollment','prism_pending'], true))
                    <form method="POST" action="{{ route('student.fee-clearances.proof', $fee) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <input type="text" name="payment_method" placeholder="Payment method" value="{{ old('payment_method', $fee->payment_method) }}">
                            <input type="text" name="reference_no" placeholder="Receipt/reference no." value="{{ old('reference_no', $fee->reference_no) }}">
                            <input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png">
                            <button class="btn sm">Submit proof</button>
                        </div>
                    </form>
                @endif
            </div>
        @endforeach
    @endif
</div>

<div class="card">
    <h3>PRISM Enrollment Handoff</h3>
    @if ($application->stage === 'officially_enrolled')
        <p><span class="badge ok">OFFICIALLY ENROLLED</span></p>
        <p>PRISM reference: <strong>{{ $application->prism_reference_no }}</strong></p>
        <p class="muted">Encoded at {{ $application->prism_encoded_at?->format('F j, Y g:i A') }}</p>
    @elseif (in_array($application->stage, ['eligible_for_official_enrollment','prism_pending'], true))
        <p><span class="badge ok">ELIGIBLE FOR OFFICIAL ENROLLMENT</span></p>
        <p class="muted">College staff will record the manual PRISM encoding.</p>
    @else
        <p class="muted">Not yet eligible for official PRISM enrollment.</p>
    @endif
</div>
@endsection
