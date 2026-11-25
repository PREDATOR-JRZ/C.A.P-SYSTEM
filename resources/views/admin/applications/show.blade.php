@extends('layouts.app')
@section('title', $application->user->name)
@section('content')
@php $p = $application->user->profile; $actor = auth()->user(); @endphp
<p><a href="{{ route('admin.applications.index') }}">← All applications</a></p>

<div class="grid">
    <div class="card">
        <h2>{{ $p?->full_name ?? $application->user->name }}</h2>
        <p><span class="badge">{{ $application->stage_label }}</span></p>
        <p>{{ $p?->student_status }} · {{ $p?->course_applied }}<br>{{ $application->user->email }} · {{ $p?->contact_number }}<br>{{ $p?->address }}</p>
        <p class="muted">CAT: {{ $application->cat_status ?? '—' }} {{ $application->cat_reference_no ? ' · Ref '.$application->cat_reference_no : '' }}</p>
        @if($application->stage === 'eligibility_pending' && $actor->canReviewRequirements())
            <form method="POST" action="{{ route('admin.applications.eligibility', $application) }}" class="row">
                @csrf @method('PATCH')
                <input type="text" name="cat_reference_no" placeholder="CAT reference no." required>
                <button class="btn sm">Confirm CAT eligibility</button>
            </form>
        @endif
    </div>

    <div class="card">
        <h3>Requirements (manual review)</h3>
        @forelse ($application->documents->sortByDesc('id')->unique('label') as $d)
            <div style="border-top:1px solid #eee;padding:.5rem 0">
                <div><a href="{{ route('admin.documents.show', $d) }}" target="_blank">{{ $d->label }}</a> <span class="muted">({{ $d->original_name }})</span></div>
                <div>Status: <span class="badge {{ $d->isApprovedOrWaived() ? 'ok' : (in_array($d->status,['rejected','resubmission_requested']) ? 'bad' : 'warn') }}">{{ $d->status }}</span></div>
                @if($d->student_visible_note)<div class="muted">Note: {{ $d->student_visible_note }}</div>@endif
                @if($actor->canReviewRequirements() && in_array($application->stage, ['requirements_submitted','requirements_in_review','requirements_returned'], true))
                    <form method="POST" action="{{ route('admin.applications.documents.review', [$application, $d]) }}" class="row" style="margin-top:.25rem">
                        @csrf @method('PATCH')
                        <select name="status" style="width:auto">
                            @foreach (['approved','rejected','resubmission_requested','waived'] as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="note" placeholder="Reason/note (required for return/reject)">
                        <button class="btn sm">Save review</button>
                    </form>
                @endif
            </div>
        @empty <p class="muted">No documents uploaded.</p> @endforelse
    </div>
</div>

<div class="card">
    <h3>Exam</h3>
    <p>Date: {{ $application->examAppointment?->appointment_date->format('F j, Y') ?? '—' }} · Ref: {{ $application->examAppointment?->reference_no ?? '—' }}</p>
    @if ($application->stage === 'exam_scheduled' && $actor->canRecordEvaluation())
        <form method="POST" action="{{ route('admin.applications.exam-result', $application) }}" class="row">
            @csrf @method('PATCH')
            <select name="exam_result" style="width:auto"><option value="passed" @selected($application->exam_result==='passed')>Passed</option><option value="failed" @selected($application->exam_result==='failed')>Failed</option></select>
            <input type="number" name="exam_score" step="0.01" min="0" max="100" placeholder="Score (optional)" value="{{ $application->exam_score }}">
            <button class="btn sm">Save result (not visible until released)</button>
        </form>
    @elseif ($application->exam_result)
        <p>Result: <span class="badge {{ $application->exam_result==='passed'?'ok':'bad' }}">{{ $application->exam_result }}</span> · released {{ $application->exam_result_released_at?->format('M j, Y g:i A') ?? 'not yet' }}</p>
    @endif
</div>

<div class="card">
    <h3>Interview &amp; Final Evaluation</h3>
    <p>Date: {{ $application->interviewAppointment?->appointment_date->format('F j, Y') ?? '—' }} · Ref: {{ $application->interviewAppointment?->reference_no ?? '—' }}</p>
    @if ($application->stage === 'interview_scheduled' && $actor->canRecordEvaluation())
        <form method="POST" action="{{ route('admin.applications.interview-result', $application) }}" style="margin-top:.5rem">
            @csrf @method('PATCH')
            <div><label>Interview notes</label><textarea name="interview_notes" rows="2">{{ old('interview_notes') }}</textarea></div>
            <div class="row" style="margin-top:.5rem">
                <button name="interview_result" value="approved" class="btn ok sm">Approve for clearance</button>
                <button name="interview_result" value="not_approved" class="btn bad sm" onclick="return confirm('Mark as NOT approved?')">Not approved</button>
            </div>
        </form>
    @elseif($application->interview_result)
        <p>Result: <span class="badge {{ $application->interview_result==='approved'?'ok':'bad' }}">{{ $application->interview_result }}</span></p>
        @if($application->interview_notes)<p class="muted">{{ $application->interview_notes }}</p>@endif
    @endif
</div>

<div class="card">
    <h3>Pre-PRISM Fee Clearance</h3>
    @forelse ($application->feeClearances as $fee)
        <div style="border-top:1px solid #eee;padding:.5rem 0">
            <p><strong>{{ $fee->label }}</strong> — <span class="badge {{ $fee->isCleared() ? 'ok' : ($fee->status === 'pending_verification' ? 'warn' : 'bad') }}">{{ $fee->status }}</span></p>
            <p class="muted">Ref: {{ $fee->reference_no ?? '—' }} {{ $fee->proof_path ? ' · proof uploaded' : '' }}</p>
            @if($actor->canReviewRequirements())
                <form method="POST" action="{{ route('admin.applications.fees.verify', [$application, $fee]) }}" class="row" style="margin-top:.25rem">
                    @csrf @method('PATCH')
                    <select name="status" style="width:auto"><option value="verified_paid">verified_paid</option><option value="pending_verification">pending_verification</option><option value="unpaid">unpaid/rejected</option></select>
                    <input type="text" name="notes" placeholder="Notes">
                    <button class="btn sm">Update verification</button>
                </form>
            @endif
            @if($actor->canRecordEvaluation() && !$fee->isCleared())
                <form method="POST" action="{{ route('admin.applications.fees.waive', [$application, $fee]) }}" class="row" style="margin-top:.25rem">
                    @csrf @method('PATCH')
                    <input type="text" name="reason" placeholder="Waiver reason (required)" required>
                    <button class="btn sm warn">Waive fee</button>
                </form>
            @endif
        </div>
    @empty
        <p class="muted">Fee clearances are created after interview acceptance.</p>
    @endforelse
</div>

<div class="card">
    <h3>PRISM Handoff</h3>
    <p>PRISM status: <span class="badge">{{ $application->prism_status ?? 'not_ready' }}</span></p>
    @if($application->prism_reference_no)<p>Reference: <strong>{{ $application->prism_reference_no }}</strong> · encoded {{ $application->prism_encoded_at?->format('M j, Y g:i A') }}</p>@endif
    @if(in_array($application->stage, ['eligible_for_official_enrollment','prism_pending'], true) && $actor->canEncodePrism())
        @if($application->stage === 'eligible_for_official_enrollment')
            <form method="POST" action="{{ route('admin.applications.prism.pending', $application) }}" style="margin-bottom:.5rem">@csrf <button class="btn sm ghost">Mark PRISM encoding in progress</button></form>
        @endif
        <form method="POST" action="{{ route('admin.applications.prism.enroll', $application) }}">
            @csrf
            <div class="row">
                <input type="text" name="prism_reference_no" placeholder="PRISM reference no." required>
                <input type="text" name="prism_notes" placeholder="Notes (optional)">
                <button class="btn ok sm" onclick="return confirm('Record PRISM encoding and mark officially enrolled?')">Record Official Enrollment</button>
            </div>
        </form>
    @elseif($application->stage === 'officially_enrolled')
        <p><span class="badge ok">OFFICIALLY ENROLLED</span></p>
    @else
        <p class="muted">PRISM handoff becomes available when all gates and fee clearances are satisfied.</p>
    @endif
</div>

<div class="card">
    <h3>Audit log</h3>
    @forelse($application->statusLogs->take(20) as $log)
        <div style="border-top:1px solid #eee;padding:.25rem 0">
            <strong>{{ $log->action }}</strong> <span class="muted">{{ $log->created_at?->format('M j, Y g:i A') }} · {{ $log->actor?->name ?? 'System' }} ({{ $log->actor_role ?? 'n/a' }})</span>
            @if($log->from_stage || $log->to_stage)<div class="muted">{{ $log->from_stage }} → {{ $log->to_stage }}</div>@endif
            @if($log->reason)<div class="muted">Reason: {{ $log->reason }}</div>@endif
        </div>
    @empty
        <p class="muted">No audit entries yet.</p>
    @endforelse
</div>
@endsection
