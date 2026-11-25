@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
    $steps = ['Eligibility confirmed','Requirements approved','CICS exam passed','Interview approved','Fee clearance complete','Eligible for PRISM','Officially enrolled'];
    $rank = $application->rank;
    $done = [
        $rank >= 2,
        $rank >= 4,
        in_array($application->stage, ['exam_passed','interview_scheduled','accepted_for_clearance','eligible_for_official_enrollment','prism_pending','officially_enrolled'], true),
        in_array($application->stage, ['accepted_for_clearance','eligible_for_official_enrollment','prism_pending','officially_enrolled'], true),
        $application->hasAllRequiredFeeClearances(),
        in_array($application->stage, ['eligible_for_official_enrollment','prism_pending','officially_enrolled'], true),
        $application->stage === 'officially_enrolled',
    ];
    $failedAt = match ($application->stage) { 'exam_failed' => 3, 'not_approved' => 4, 'closed_rejected' => 1, 'closed_ineligible' => 1, 'closed_failed' => 3, default => null };
@endphp

<div class="card">
    <h2>Welcome, {{ $user->name }}</h2>
    <p>{{ $profile->student_status }} · {{ $profile->course_applied }}</p>
    <p><span class="badge @if($application->isTerminal() && $application->stage !== 'officially_enrolled') bad @elseif(in_array($application->stage,['officially_enrolled','eligible_for_official_enrollment','prism_pending','exam_passed','requirements_approved'])) ok @endif">{{ $application->stage_label }}</span></p>

    @switch($application->stage)
        @case('eligibility_pending')
            <div class="alert">Your CAT eligibility is pending college confirmation. You will be able to submit requirements after confirmation.</div> @break
        @case('eligible')
        @case('requirements_returned')
            <a class="btn" href="{{ route('student.requirements.create') }}">{{ $application->stage === 'requirements_returned' ? 'Fix & resubmit requirements' : 'Upload requirements' }}</a> @break
        @case('requirements_submitted')
        @case('requirements_in_review')
            <div class="alert">Your requirements are being manually reviewed by college staff.</div> @break
        @case('requirements_approved')
            <a class="btn" href="{{ route('student.appointments.create','exam') }}">Schedule CICS Evaluation Exam</a> @break
        @case('exam_passed')
            <a class="btn ok" href="{{ route('student.appointments.create','interview') }}">Schedule Interview &amp; Requirements</a> @break
        @case('accepted_for_clearance')
            <div class="alert">Complete Department Fee and Insurance Fee clearance to become eligible for official PRISM enrollment.</div> @break
        @case('eligible_for_official_enrollment')
        @case('prism_pending')
            <div class="alert ok">You are eligible for official PRISM enrollment. College staff will record PRISM encoding.</div> @break
        @case('officially_enrolled')
            <div class="alert ok">Officially enrolled in PRISM. Reference: {{ $application->prism_reference_no }}</div> @break
        @case('exam_failed')
            <div class="alert error">You did not pass the CICS evaluation exam. {{ $application->dispose_at ? 'Disposal date: '.$application->dispose_at->format('F j, Y').'.' : '' }}</div> @break
        @case('not_approved')
            <div class="alert error">Your application was not approved after interview/final evaluation.</div> @break
    @endswitch
</div>

<div class="grid">
    <div class="card">
        <h3>Progress</h3>
        <ul class="tracker">
            @foreach ($steps as $i => $label)
                @php $isDone = $done[$i]; $isFailed = $failedAt === ($i + 1); $cls = $isFailed ? 'failed' : ($isDone ? 'done' : ''); @endphp
                <li class="{{ $cls }}"><span class="dot">{{ $isFailed ? '✕' : ($isDone ? '✓' : $i + 1) }}</span>{{ $label }}</li>
            @endforeach
        </ul>
    </div>

    <div>
        @foreach ([$application->examAppointment, $application->interviewAppointment] as $appt)
            @if ($appt)
                <div class="card">
                    <h3>{{ $appt->label }}</h3>
                    <p><strong>{{ $appt->appointment_date->format('l, F j, Y') }}</strong><br>
                       Ref: <code>{{ $appt->reference_no }}</code> · Slot #{{ $appt->slot_number }}</p>
                    <a class="btn ghost sm" href="{{ route('student.appointments.slip', $appt) }}" target="_blank">View / print slip</a>
                </div>
            @endif
        @endforeach

        @if($application->feeClearances->isNotEmpty())
            <div class="card">
                <h3>Fee clearances</h3>
                @foreach($application->feeClearances as $fee)
                    <p><strong>{{ $fee->label }}</strong>: <span class="badge {{ $fee->isCleared() ? 'ok' : ($fee->status === 'pending_verification' ? 'warn' : 'bad') }}">{{ $fee->status }}</span></p>
                @endforeach
                <a href="{{ route('student.status') }}">View details →</a>
            </div>
        @endif

        <div class="card">
            <h3>Notifications</h3>
            @forelse ($notifications as $n)
                <p @if(!$n->read_at) style="font-weight:600" @endif>
                    {{ $n->data['title'] ?? 'Notification' }}<br><span class="muted" style="font-weight:400">{{ $n->data['message'] ?? '' }}</span>
                    @if(!$n->read_at)
                        <form method="POST" action="{{ route('student.notifications.read', $n->id) }}" style="display:inline">@csrf <button class="btn ghost sm">Mark read</button></form>
                    @endif
                </p>
            @empty <p class="muted">Nothing yet.</p> @endforelse
        </div>
    </div>
</div>

<div class="card">
    <h3>Latest announcements</h3>
    @forelse ($announcements as $a)
        <p><strong>{{ $a->title }}</strong> <span class="muted">· {{ $a->created_at->diffForHumans() }}</span><br>{{ \Illuminate\Support\Str::limit($a->body, 160) }}</p>
    @empty <p class="muted">No announcements.</p> @endforelse
    <a href="{{ route('student.announcements') }}">View all →</a>
</div>
@endsection
