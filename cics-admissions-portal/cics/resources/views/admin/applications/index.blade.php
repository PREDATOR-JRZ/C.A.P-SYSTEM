@extends('layouts.app')
@section('title', 'Applications')
@section('content')
<div class="card">
    <form class="grid-form" method="GET">
        <div><label>Search</label><input name="q" value="{{ request('q') }}" placeholder="Name or email"></div>
        <div><label>Stage</label><select name="stage"><option value="">All</option>@foreach ($stages as $k => $l)<option value="{{ $k }}" @selected(request('stage')===$k)>{{ $k }}</option>@endforeach</select></div>
        <div><label>Exam date</label><input type="date" name="exam_date" value="{{ request('exam_date') }}"></div>
        <div><label>Interview date</label><input type="date" name="interview_date" value="{{ request('interview_date') }}"></div>
        <div style="align-self:end"><button class="btn">Filter</button></div>
    </form>
</div>
<div class="card" style="overflow:auto">
    <table>
        <tr><th>Student</th><th>Status</th><th>Stage</th><th>Exam</th><th>Interview</th><th></th></tr>
        @forelse ($applications as $a)
            <tr>
                <td>{{ $a->user->name }}<br><span class="muted">{{ $a->user->email }}</span></td>
                <td>{{ $a->user->profile?->student_status }}<br><span class="muted">{{ $a->user->profile?->course_applied }}</span></td>
                <td><span class="badge">{{ $a->stage }}</span></td>
                <td>{{ $a->examAppointment?->appointment_date->format('M j, Y') ?? '—' }}<br>@if($a->exam_result)<span class="badge {{ $a->exam_result==='passed'?'ok':'bad' }}">{{ $a->exam_result }}</span>@endif</td>
                <td>{{ $a->interviewAppointment?->appointment_date->format('M j, Y') ?? '—' }}</td>
                <td><a class="btn ghost sm" href="{{ route('admin.applications.show', $a) }}">Open</a></td>
            </tr>
        @empty <tr><td colspan="6" class="muted">No applications found.</td></tr> @endforelse
    </table>
    {{ $applications->links() }}
</div>
@endsection
