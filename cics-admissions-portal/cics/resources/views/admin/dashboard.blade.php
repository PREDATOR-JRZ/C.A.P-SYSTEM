@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<div class="grid">
    <div class="card"><div class="muted">Exams today</div><h2 style="margin:0">{{ $todayExam }} / {{ $slots }}</h2></div>
    <div class="card"><div class="muted">Interviews today</div><h2 style="margin:0">{{ $todayInterview }} / {{ $slots }}</h2></div>
    <div class="card"><div class="muted">Results ready to release</div><h2 style="margin:0">{{ $readyToRelease }}</h2></div>
    <div class="card"><div class="muted">Approved, awaiting batch</div><h2 style="margin:0">{{ $awaitingBatch }}</h2></div>
</div>

<div class="card">
    <h3>Applications by stage</h3>
    <div class="row">
        @foreach (\App\Models\Application::STAGES as $key => $label)
            <a class="badge" href="{{ route('admin.applications.index', ['stage' => $key]) }}">{{ $key }}: {{ $counts[$key] ?? 0 }}</a>
        @endforeach
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3>Release exam results</h3>
        <p class="muted">Publishes every saved pass/fail result to students. Failed accounts get a {{ config('cics.dispose_days') }}-day disposal date.</p>
        <form method="POST" action="{{ route('admin.results.release') }}" onsubmit="return confirm('Release all saved exam results now?')">
            @csrf <button class="btn" @disabled(!$readyToRelease)>Release {{ $readyToRelease }} result(s)</button>
        </form>
    </div>

    <div class="card">
        <h3>Group approved students into fee batches</h3>
        @if ($errors->any()) <div class="alert error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div> @endif
        <form method="POST" action="{{ route('admin.batches.generate') }}">
            @csrf
            <div><label>Number of batches</label><select name="batches" id="batches">@for ($i = 2; $i <= $maxBatches; $i++)<option>{{ $i }}</option>@endfor</select></div>
            @for ($i = 0; $i < $maxBatches; $i++)
                <div class="batch-date" data-n="{{ $i + 1 }}" style="margin-top:.5rem"><label>Batch {{ $i + 1 }} payment date</label><input type="date" name="dates[{{ $i }}]" value="{{ old("dates.$i") }}"></div>
            @endfor
            <p><button class="btn" @disabled(!$awaitingBatch)>Group {{ $awaitingBatch }} student(s)</button></p>
        </form>
    </div>
</div>
<script>
    const sel = document.getElementById('batches');
    const sync = () => document.querySelectorAll('.batch-date').forEach(el => el.style.display = (+el.dataset.n <= +sel.value) ? '' : 'none');
    sel.addEventListener('change', sync); sync();
</script>
@endsection
