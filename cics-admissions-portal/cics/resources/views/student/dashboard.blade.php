@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
    $steps = ['Account registered','Requirements uploaded','CICS exam scheduled','Exam result','Interview scheduled','Approval','Fee payment batch'];
    $rank = $application->rank;
    $failedAt = match ($application->stage) { 'exam_failed' => 4, 'not_approved' => 6, default => null };
@endphp

<div class="card">
    <h2>Welcome, {{ $user->name }}</h2>
    <p>{{ $profile->student_status }} · {{ $profile->course_applied }}</p>
    <p><span class="badge @if(in_array($application->stage,['exam_failed','not_approved'])) bad @elseif(in_array($application->stage,['approved','batched','exam_passed'])) ok @endif">{{ $application->stage_label }}</span></p>

    {{-- Next action --}}
    @switch($application->stage)
        @case('registered')
            <a class="btn" href="{{ route('student.requirements.create') }}">Upload requirements</a> @break
        @case('requirements_submitted')
            <a class="btn" href="{{ route('student.appointments.create','exam') }}">Schedule CICS Exam</a> @break
        @case('exam_passed')
            <a class="btn ok" href="{{ route('student.appointments.create','interview') }}">Schedule Interview &amp; Requirements</a> @break
        @case('exam_failed')
            <div class="alert error">Your account will be removed on {{ $application->dispose_at?->format('F j, Y') }}.</div> @break
    @endswitch
</div>

<div class="grid">
    <div class="card">
        <h3>Progress</h3>
        <ul class="tracker">
            @foreach ($steps as $i => $label)
                @php $n = $i + 1; $cls = $failedAt === $n ? 'failed' : (($failedAt && $n > $failedAt) ? '' : ($rank >= $n ? 'done' : ($rank + 1 === $n ? 'current' : ''))); @endphp
                <li class="{{ $cls }}"><span class="dot">{{ $failedAt === $n ? '✕' : ($cls === 'done' ? '✓' : $n) }}</span>{{ $label }}</li>
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

        <div class="card">
            <h3>Notifications</h3>
            @forelse ($notifications as $n)
                <p @if(!$n->read_at) style="font-weight:600" @endif>
                    {{ $n->data['title'] }}<br><span class="muted" style="font-weight:400">{{ $n->data['message'] }}</span>
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
