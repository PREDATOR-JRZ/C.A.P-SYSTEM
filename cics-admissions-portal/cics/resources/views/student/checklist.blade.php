@extends('layouts.app')
@section('title', 'Check List')
@section('content')
<div class="grid">
    <div class="card">
        <h3>Admission steps</h3>
        <ul class="tracker">
            @foreach ($steps as [$label, $done])
                <li class="{{ $done ? 'done' : '' }}"><span class="dot">{{ $done ? '✓' : '' }}</span>{{ $label }}</li>
            @endforeach
        </ul>
    </div>
    <div class="card">
        <h3>Requirements ({{ auth()->user()->profile->student_status }})</h3>
        <ul class="tracker">
            @foreach ($requirements as [$label, $done])
                <li class="{{ $done ? 'done' : '' }}"><span class="dot">{{ $done ? '✓' : '' }}</span>{{ $label }}</li>
            @endforeach
        </ul>
        <p><a class="btn ghost sm" href="{{ route('student.requirements.create') }}">Upload / replace files</a></p>
        <p class="muted">Bring the original copies on your interview date.</p>
    </div>
</div>
@endsection
