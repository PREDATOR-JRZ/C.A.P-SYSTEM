<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class PageController extends Controller
{
    public function status()
    {
        $application = auth()->user()->application()->with(['examAppointment', 'interviewAppointment', 'feeClearances'])->firstOrFail();

        return view('student.status', compact('application'));
    }

    public function checklist()
    {
        $user = auth()->user();
        $application = $user->application()->with(['documents', 'feeClearances'])->firstOrFail();
        $rank = $application->rank;

        $steps = [
            ['CAT eligibility confirmed', $rank >= 2],
            ['Requirements submitted and manually approved', $rank >= 4],
            ['CICS evaluation exam passed', in_array($application->stage, ['exam_passed', 'interview_scheduled', 'accepted_for_clearance', 'eligible_for_official_enrollment', 'prism_pending', 'officially_enrolled'], true)],
            ['Interview/final evaluation approved', in_array($application->stage, ['accepted_for_clearance', 'eligible_for_official_enrollment', 'prism_pending', 'officially_enrolled'], true)],
            ['Department Fee and Insurance Fee cleared', $application->hasAllRequiredFeeClearances()],
            ['Eligible for official PRISM enrollment', in_array($application->stage, ['eligible_for_official_enrollment', 'prism_pending', 'officially_enrolled'], true)],
            ['Officially enrolled in PRISM', $application->stage === 'officially_enrolled'],
        ];

        $labels = config('cics.requirements.' . $user->profile->student_status, []);
        $latestDocs = $application->documents->sortByDesc('id')->unique('label')->keyBy('label');

        return view('student.checklist', [
            'steps'        => $steps,
            'requirements' => collect($labels)->map(fn ($l) => [$l, ($latestDocs[$l]->status ?? null) === 'approved' || ($latestDocs[$l]->status ?? null) === 'waived']),
            'application'  => $application,
        ]);
    }

    public function announcements()
    {
        return view('student.announcements', ['announcements' => Announcement::latest()->paginate(10)]);
    }

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
