@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="auth-wrap"><div class="card">
    <div class="auth-head"><h2>Welcome back</h2><p>College of Information and Computing Sciences</p></div>
    <div class="auth-body">
    <form method="POST" action="{{ route('login') }}" class="grid-form" style="grid-template-columns:1fr">
        @csrf
        <div><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autofocus>@error('email')<div class="err">{{ $message }}</div>@enderror</div>
        <div><label>Password</label><input type="password" name="password" required></div>
        <div><label style="font-weight:400"><input type="checkbox" name="remember" style="width:auto"> Remember me</label></div>
        <button class="btn">Log in</button>
    </form>
    <p class="muted">New applicant? <a href="{{ route('register') }}">Register a student account</a></p>
</div></div></div>
@endsection
