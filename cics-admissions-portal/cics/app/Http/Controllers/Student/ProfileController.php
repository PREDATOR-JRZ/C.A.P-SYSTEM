<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('student.profile', ['profile' => auth()->user()->profile]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'first_name'       => ['required', 'string', 'max:80'],
            'middle_name'      => ['nullable', 'string', 'max:80'],
            'last_name'        => ['required', 'string', 'max:80'],
            'birthdate'        => ['required', 'date', 'before:today'],
            'sex'              => ['required', Rule::in(['Male', 'Female'])],
            'contact_number'   => ['required', 'string', 'max:30'],
            'address'          => ['required', 'string', 'max:255'],
            'previous_school'  => ['nullable', 'string', 'max:255'],
            'guardian_name'    => ['nullable', 'string', 'max:120'],
            'guardian_contact' => ['nullable', 'string', 'max:30'],
        ]);

        $user = auth()->user();
        $user->profile->update($data);
        $user->update(['name' => trim($data['first_name'] . ' ' . $data['last_name'])]);

        return back()->with('success', 'Personal information updated.');
    }
}
