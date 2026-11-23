<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** STEP 1 — Register a Student Account. */
class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $statuses = config('cics.student_statuses');

        $data = $request->validate([
            'first_name'       => ['required', 'string', 'max:80'],
            'middle_name'      => ['nullable', 'string', 'max:80'],
            'last_name'        => ['required', 'string', 'max:80'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'confirmed', 'min:8'],
            'birthdate'        => ['required', 'date', 'before:today'],
            'sex'              => ['required', Rule::in(['Male', 'Female'])],
            'contact_number'   => ['required', 'string', 'max:30'],
            'address'          => ['required', 'string', 'max:255'],
            'student_status'   => ['required', Rule::in($statuses)],
            'course_applied'   => ['required', Rule::in(array_keys(config('cics.courses')))],
            'previous_school'  => ['required_unless:student_status,Freshman', 'nullable', 'string', 'max:255'],
            'guardian_name'    => ['nullable', 'string', 'max:120'],
            'guardian_contact' => ['nullable', 'string', 'max:30'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => trim($data['first_name'] . ' ' . $data['last_name']),
                'email'    => $data['email'],
                'password' => $data['password'],
                'role'     => 'student',
            ]);

            $user->profile()->create(collect($data)->except(['email', 'password', 'password_confirmation'])->all());
            Application::create(['user_id' => $user->id, 'stage' => 'registered']);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        // Step 2 comes next
        return redirect()->route('student.requirements.create')
            ->with('success', 'Account created! Upload your Admission Test Result and other requirements.');
    }
}
