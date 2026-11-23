<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class PageController extends Controller
{
    /** Status page (Step 6 + Step 8): shows results only after the admin releases them. */
    public function status()
    {
        $application = auth()->user()->application()->with(['examAppointment', 'interviewAppointment'])->firstOrFail();

        return view('student.status', compact('application'));
    }

    /** Check List: process steps + the requirements for the student's status. */
    public function checklist()
    {
        $user = auth()->user();
        $application = $user->application()->with('documents')->firstOrFail();
        $rank = $application->rank;

        $steps = [
            ['Register a student account', $rank >= 1],
            ['Upload Admission Test Result & requirements', $rank >= 2],
            ['Schedule CICS Exam', $rank >= 3],
            ['Exam result released', $application->exam_result_released_at !== null],
            ['Schedule Interview & submission of requirements', $rank >= 5],
            ['Approved by CICS', in_array($application->stage, ['approved', 'batched'], true)],
            ['Assigned to a fee payment batch', $application->stage === 'batched'],
        ];

        $labels = config('cics.requirements.' . $user->profile->student_status);
        $uploaded = $application->documents->pluck('label')->all();

        return view('student.checklist', [
            'steps'        => $steps,
            'requirements' => collect($labels)->map(fn ($l) => [$l, in_array($l, $uploaded, true)]),
            'application'  => $application,
        ]);
    }

    /** Announcements (Newsfeed). */
    public function announcements()
    {
        return view('student.announcements', ['announcements' => Announcement::latest()->paginate(10)]);
    }

    /** Information about the History of CICS and the Courses. */
    public function about()
    {
        return view('student.about', ['courses' => config('cics.courses')]);
    }

    public function readNotification(string $id)
    {
        auth()->user()->notifications()->whereKey($id)->first()?->markAsRead();

        return back();
    }
}
