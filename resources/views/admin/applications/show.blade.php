@extends('layouts.app')
@section('title', $application->user->name)
@section('content')
@php $p = $application->user->profile; @endphp
<p><a href="{{ route('admin.applications.index') }}">← All applications</a></p>
<div class="grid">
    <div class="card">
        <h2>{{ $p->full_name }}</h2>
        <p><span class="badge">{{ $application->stage }}</span></p>
        <p>{{ $p->student_status }} · {{ $p->course_applied }}<br>{{ $application->user->email }} · {{ $p->contact_number }}<br>{{ $p->address }}<br>Born {{ $p->birthdate->format('M j, Y') }} · {{ $p->sex }}
        @if($p->previous_school)<br>Previous school: {{ $p->previous_school }}@endif</p>
    </div>
    <div class="card">
        <h3>Uploaded requirements</h3>
        @forelse ($application->documents as $d)
            <div><a href="{{ route('admin.documents.show', $d) }}" target="_blank">{{ $d->label }}</a> <span class="muted">({{ $d->original_name }})</span></div>
        @empty <p class="muted">None.</p> @endforelse
    </div>
</div>

<div class="card">
    <h3>Exam</h3>
    <p>Date: {{ $application->examAppointment?->appointment_date->format('F j, Y') ?? '—' }} · Ref: {{ $application->examAppointment?->reference_no ?? '—' }}</p>
    @if ($application->stage === 'exam_scheduled')
        <form method="POST" action="{{ route('admin.applications.exam-result', $application) }}" class="row">
            @csrf @method('PATCH')
            <select name="exam_result" style="width:auto"><option value="passed" @selected($application->exam_result==='passed')>Passed</option><option value="failed" @selected($application->exam_result==='failed')>Failed</option></select>
            <button class="btn sm">Save result (not visible until released)</button>
        </form>
    @elseif ($application->exam_result)
        <p>Result: <span class="badge {{ $application->exam_result==='passed'?'ok':'bad' }}">{{ $application->exam_result }}</span> · released {{ $application->exam_result_released_at?->format('M j, Y') }}
        @if($application->dispose_at) · disposal on {{ $application->dispose_at->format('M j, Y') }} @endif</p>
    @endif
</div>

<div class="card">
    <h3>Interview &amp; requirements</h3>
    <p>Date: {{ $application->interviewAppointment?->appointment_date->format('F j, Y') ?? '—' }} · Ref: {{ $application->interviewAppointment?->reference_no ?? '—' }}</p>
    @if ($application->stage === 'interview_scheduled')
        <form method="POST" action="{{ route('admin.applications.interview-result', $application) }}" class="row">
            @csrf @method('PATCH')
            <button name="interview_result" value="approved" class="btn ok sm">Approve</button>
            <button name="interview_result" value="not_approved" class="btn bad sm" onclick="return confirm('Mark as NOT approved?')">Not approved</button>
        </form>
    @endif
    @if ($application->fee_batch) <p>Fee batch: <strong>{{ $application->fee_batch }}</strong> · {{ $application->fee_payment_date->format('F j, Y') }}</p> @endif
</div>
@endsection
