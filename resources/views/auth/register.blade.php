@extends('layouts.app')
@section('title', 'Register')
@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="card">
        <h2>Step 1 · Register a Student Account</h2>
        @if ($errors->any()) <div class="alert error">Please fix the highlighted fields.</div> @endif

        <h3>Personal Information</h3>
        <div class="grid-form">
            @foreach ([['first_name','First name','text',true],['middle_name','Middle name','text',false],['last_name','Last name','text',true],['birthdate','Birthdate','date',true]] as [$n,$l,$t,$r])
                <div><label>{{ $l }}</label><input type="{{ $t }}" name="{{ $n }}" value="{{ old($n) }}" @required($r)>@error($n)<div class="err">{{ $message }}</div>@enderror</div>
            @endforeach
            <div><label>Sex</label>
                <select name="sex" required><option value="">Select…</option>@foreach (['Male','Female'] as $s)<option @selected(old('sex')===$s)>{{ $s }}</option>@endforeach</select>
                @error('sex')<div class="err">{{ $message }}</div>@enderror</div>
        </div>

        <h3>Student Status</h3>
        <div class="grid-form">
            <div><label>Status</label>
                <select name="student_status" id="student_status" required>
                    <option value="">Select…</option>
                    @foreach (config('cics.student_statuses') as $s)<option @selected(old('student_status')===$s)>{{ $s }}</option>@endforeach
                </select>@error('student_status')<div class="err">{{ $message }}</div>@enderror</div>
            <div><label>Course applying for</label>
                <select name="course_applied" required>
                    <option value="">Select…</option>
                    @foreach (config('cics.courses') as $code => $name)<option value="{{ $code }}" @selected(old('course_applied')===$code)>{{ $code }} — {{ $name }}</option>@endforeach
                </select>@error('course_applied')<div class="err">{{ $message }}</div>@enderror</div>
            <div id="prev_school_box"><label>Previous school <span class="muted">(Transferee / Shifter / Irregular)</span></label>
                <input name="previous_school" value="{{ old('previous_school') }}">@error('previous_school')<div class="err">{{ $message }}</div>@enderror</div>
        </div>

        <h3>Contacts</h3>
        <div class="grid-form">
            <div><label>Contact number</label><input name="contact_number" value="{{ old('contact_number') }}" required>@error('contact_number')<div class="err">{{ $message }}</div>@enderror</div>
            <div><label>Home address</label><input name="address" value="{{ old('address') }}" required>@error('address')<div class="err">{{ $message }}</div>@enderror</div>
            <div><label>Parent / Guardian</label><input name="guardian_name" value="{{ old('guardian_name') }}"></div>
            <div><label>Guardian contact no.</label><input name="guardian_contact" value="{{ old('guardian_contact') }}"></div>
        </div>

        <h3>Account</h3>
        <div class="grid-form">
            <div><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="err">{{ $message }}</div>@enderror</div>
            <div><label>Password</label><input type="password" name="password" required>@error('password')<div class="err">{{ $message }}</div>@enderror</div>
            <div><label>Confirm password</label><input type="password" name="password_confirmation" required></div>
        </div>
        <p><button class="btn">Create account</button> <a href="{{ route('login') }}">I already have an account</a></p>
    </div>
</form>
<script>
    const st = document.getElementById('student_status'), box = document.getElementById('prev_school_box');
    const sync = () => box.style.display = (st.value === 'Freshman') ? 'none' : '';
    st.addEventListener('change', sync); sync();
</script>
@endsection
