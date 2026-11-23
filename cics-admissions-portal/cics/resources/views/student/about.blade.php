@extends('layouts.app')
@section('title', 'About CICS')
@section('content')
<div class="card">
    <h2>History of CICS</h2>
    {{-- TODO: replace with the college's official history. --}}
    <p class="muted">[Insert the official history of the College of Information and Computing Sciences here.]</p>
</div>
<div class="card">
    <h2>Courses Offered</h2>
    @foreach ($courses as $code => $name)
        <div style="margin-bottom:.75rem"><strong>{{ $code }}</strong> — {{ $name }}
            {{-- TODO: add course description, curriculum and career paths --}}</div>
    @endforeach
</div>
@endsection
