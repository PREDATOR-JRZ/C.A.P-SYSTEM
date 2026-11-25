@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<div class="grid">
    <div class="card"><div class="muted">Exams today</div><h2 style="margin:0">{{ $todayExam }} / {{ $slots }}</h2></div>
    <div class="card"><div class="muted">Interviews today</div><h2 style="margin:0">{{ $todayInterview }} / {{ $slots }}</h2></div>
    <div class="card"><div class="muted">Results ready to release</div><h2 style="margin:0">{{ $readyToRelease }}</h2></div>
    <div class="card"><div class="muted">Requirements awaiting review</div><h2 style="margin:0">{{ $awaitingReview }}</h2></div>
</div>

<div class="grid">
    <div class="card"><div class="muted">Accepted, awaiting fee clearance</div><h2 style="margin:0">{{ $awaitingClearance }}</h2></div>
    <div class="card"><div class="muted">Eligible/pending PRISM handoff</div><h2 style="margin:0">{{ $awaitingPrism }}</h2></div>
</div>

<div class="card">
    <div class="row" style="justify-content:space-between;align-items:flex-end;margin-bottom:1rem">
        <div>
            <h3 style="margin:0">Applications by Stage</h3>
            <p class="muted" style="margin:.25rem 0 0">Track where applicants are in the pre-enrollment workflow. Click a stage to filter the applications list.</p>
        </div>
        <a class="btn ghost sm" href="{{ route('admin.applications.index') }}">View all applications</a>
    </div>

    <div class="stage-board">
        @foreach (\App\Models\Application::STAGE_GROUPS as $group => $stageKeys)
            @php
                $groupTotal = collect($stageKeys)->sum(fn ($key) => (int) ($counts[$key] ?? 0));
                $isTerminalGroup = $group === 'Closed';
            @endphp
            <section class="stage-group">
                <div class="stage-group-head">
                    <h4>{{ $group }}</h4>
                    <span class="stage-group-total">{{ $groupTotal }} {{ \Illuminate\Support\Str::plural('applicant', $groupTotal) }}</span>
                </div>
                <div class="stage-list">
                    @foreach ($stageKeys as $key)
                        @php
                            $count = (int) ($counts[$key] ?? 0);
                            $label = \App\Models\Application::STAGES[$key] ?? $key;
                            $shortLabel = \Illuminate\Support\Str::before($label, ' - ');
                        @endphp
                        <a class="stage-row {{ $isTerminalGroup ? 'is-terminal' : '' }} {{ $count > 0 ? 'is-active' : '' }}" href="{{ route('admin.applications.index', ['stage' => $key]) }}">
                            <span class="stage-name">
                                {{ $shortLabel }}
                                <span class="stage-key">{{ $key }}</span>
                            </span>
                            <span class="stage-count {{ $count > 0 ? 'has-items' : '' }} {{ $isTerminalGroup ? 'is-terminal' : '' }}">{{ $count }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>

<div class="card">
    <h3>Release exam results</h3>
    <p class="muted">Publishes every saved pass/fail result to students. Failed accounts get a {{ config('cics.dispose_days') }}-day disposal date.</p>
    <form method="POST" action="{{ route('admin.results.release') }}" onsubmit="return confirm('Release all saved exam results now?')">
        @csrf <button class="btn" @disabled(!$readyToRelease)>Release {{ $readyToRelease }} result(s)</button>
    </form>
</div>
@endsection
