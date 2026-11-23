@extends('layouts.app')
@section('title', 'Announcements')
@section('content')
<div class="card">
    <h3>Post an announcement</h3>
    <form method="POST" action="{{ route('admin.announcements.store') }}" class="grid-form" style="grid-template-columns:1fr">
        @csrf
        <div><label>Title</label><input name="title" value="{{ old('title') }}" required>@error('title')<div class="err">{{ $message }}</div>@enderror</div>
        <div><label>Message</label><textarea name="body" rows="4" required>{{ old('body') }}</textarea>@error('body')<div class="err">{{ $message }}</div>@enderror</div>
        <div><button class="btn">Post</button></div>
    </form>
</div>
@foreach ($announcements as $a)
    <div class="card">
        <div class="row" style="justify-content:space-between"><h3 style="margin:0">{{ $a->title }}</h3>
            <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn bad sm">Delete</button></form></div>
        <div class="muted">{{ $a->created_at->format('F j, Y g:i A') }}</div><p>{!! nl2br(e($a->body)) !!}</p>
    </div>
@endforeach
{{ $announcements->links() }}
@endsection
