@extends('layouts.app')
@section('title', 'Upload Requirements')
@section('content')
<div class="card">
    <h2>Requirements &amp; Manual Review</h2>
    <p class="muted">Upload PDF, JPG, or PNG files (max 5 MB each). Files are reviewed manually by college staff; uploading does not mean approved.</p>
    @if ($errors->any()) <div class="alert error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div> @endif

    <form method="POST" action="{{ route('student.requirements.store') }}" enctype="multipart/form-data">
        @csrf
        <table>
            <tr><th>Requirement</th><th>Status</th><th>Review note</th><th>Upload</th></tr>
            @foreach ($requirements as $label)
                @php $doc = $uploaded[$label] ?? null; @endphp
                <tr>
                    <td><strong>{{ $label }}</strong></td>
                    <td>
                        @if($doc)
                            <span class="badge {{ $doc->isApprovedOrWaived() ? 'ok' : (in_array($doc->status,['rejected','resubmission_requested']) ? 'bad' : 'warn') }}">{{ $doc->status }}</span>
                            <a href="{{ route('student.documents.show', $doc) }}" target="_blank">{{ $doc->original_name }}</a>
                        @else
                            <span class="muted">Not yet uploaded</span>
                        @endif
                    </td>
                    <td>{{ $doc?->student_visible_note }}</td>
                    <td>
                        @if($canUpload && (!$doc || $doc->canBeReplacedByStudent()))
                            <input type="file" name="files[{{ $label }}]" accept=".pdf,.jpg,.jpeg,.png">
                        @else
                            <span class="muted">Locked</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
        <p>
            @if($canUpload)
                <button class="btn">Save uploads</button>
            @endif
            <a class="btn ghost" href="{{ route('student.dashboard') }}">Back to dashboard</a>
        </p>
    </form>

    @if($canSubmit)
        <form method="POST" action="{{ route('student.requirements.submit') }}" style="margin-top:1rem">
            @csrf
            <button class="btn ok" onclick="return confirm('Submit all required documents for manual review?')">Submit for Review</button>
        </form>
    @endif
</div>
@endsection
