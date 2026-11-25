@extends('layouts.app')
@section('title', 'Appointment Slip')
@section('content')
<p class="noprint"><button class="btn" onclick="window.print()">Print slip</button> <a class="btn ghost" href="{{ route('student.dashboard') }}">Back</a></p>
@include('partials.slip', ['appointment' => $appointment])
@endsection
