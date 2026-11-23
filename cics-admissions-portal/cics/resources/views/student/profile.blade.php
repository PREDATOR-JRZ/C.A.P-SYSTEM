@extends('layouts.app')
@section('title', 'Personal Information')
@section('content')
<form method="POST" action="{{ route('student.profile.update') }}" class="card">
    @csrf @method('PUT')
    <h2>Personal Information</h2>
    <p class="muted">Status: <strong>{{ $profile->student_status }}</strong> · Course: <strong>{{ $profile->course_applied }}</strong> · Email: {{ auth()->user()->email }}</p>
    <div class="grid-form">
        @foreach ([['first_name','First name','text'],['middle_name','Middle name','text'],['last_name','Last name','text'],['birthdate','Birthdate','date'],['contact_number','Contact number','text'],['address','Address','text'],['previous_school','Previous school','text'],['guardian_name','Parent / Guardian','text'],['guardian_contact','Guardian contact','text']] as [$n,$l,$t])
            <div><label>{{ $l }}</label>
                <input type="{{ $t }}" name="{{ $n }}" value="{{ old($n, $n === 'birthdate' ? $profile->birthdate?->format('Y-m-d') : $profile->$n) }}">
                @error($n)<div class="err">{{ $message }}</div>@enderror</div>
        @endforeach
        <div><label>Sex</label><select name="sex">@foreach (['Male','Female'] as $s)<option @selected(old('sex',$profile->sex)===$s)>{{ $s }}</option>@endforeach</select></div>
    </div>
    <p><button class="btn">Save changes</button></p>
</form>
@endsection
