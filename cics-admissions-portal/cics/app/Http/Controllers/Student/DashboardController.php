<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

/** STEP 5 — students land here after scheduling the exam. */
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $application = $user->application()->with(['examAppointment', 'interviewAppointment'])->firstOrFail();

        return view('student.dashboard', [
            'user'          => $user,
            'profile'       => $user->profile,
            'application'   => $application,
            'announcements' => Announcement::latest()->take(3)->get(),
            'notifications' => $user->notifications()->latest()->take(5)->get(),
        ]);
    }
}
