@extends('layouts.app')
@section('title', 'Announcements')
@section('content')
<h2>Announcements</h2>
@forelse ($announcements as $a)
    <div class="card"><h3 style="margin-bottom:.25rem">{{ $a->title }}</h3><div class="muted">{{ $a->created_at->format('F j, Y g:i A') }}</div><p>{!! nl2br(e($a->body)) !!}</p></div>
@empty <div class="card muted">No announcements yet.</div> @endforelse
{{ $announcements->links() }}
@endsection
