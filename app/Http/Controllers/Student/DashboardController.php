<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $application = $user->application()->with(['examAppointment', 'interviewAppointment', 'feeClearances'])->firstOrFail();

        return view('student.dashboard', [
            'user'          => $user,
            'profile'       => $user->profile,
            'application'   => $application,
            'announcements' => Announcement::latest()->take(3)->get(),
            'notifications' => $user->notifications()->latest()->take(5)->get(),
        ]);
    }
}
