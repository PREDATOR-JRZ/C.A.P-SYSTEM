@extends('layouts.app')
@section('title', 'Upload Requirements')
@section('content')
<div class="card">
    <h2>Step 2 · Upload Admission Test Result &amp; Other Requirements</h2>
    <p class="muted">PDF, JPG or PNG · max 5 MB per file. The Admission Test Result is required to continue.</p>
    @if ($errors->any()) <div class="alert error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div> @endif

    <form method="POST" action="{{ route('student.requirements.store') }}" enctype="multipart/form-data">
        @csrf
        <table>
            <tr><th>Requirement</th><th>Status</th><th>Upload</th></tr>
            @foreach ($requirements as $label)
                @php $doc = $uploaded[$label] ?? null; $required = $label === 'Admission Test Result'; @endphp
                <tr>
                    <td><strong>{{ $label }}</strong> @if($required)<span class="badge warn">required</span>@endif</td>
                    <td>@if($doc) <span class="badge ok">Uploaded</span> <a href="{{ route('student.documents.show', $doc) }}" target="_blank">{{ $doc->original_name }}</a>
                        @else <span class="muted">Not yet</span> @endif</td>
                    <td><input type="file" name="files[{{ $label }}]" accept=".pdf,.jpg,.jpeg,.png" @required($required && !$doc)></td>
                </tr>
            @endforeach
        </table>
        <p>
            <button class="btn">{{ $application->stage === 'registered' ? 'Submit & continue' : 'Save uploads' }}</button>
            <a class="btn ghost" href="{{ route('student.dashboard') }}">Back to dashboard</a>
        </p>
    </form>
</div>
@endsection
