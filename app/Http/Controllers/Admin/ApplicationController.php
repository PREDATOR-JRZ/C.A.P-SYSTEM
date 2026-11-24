<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Notifications\StatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $applications = Application::with(['user.profile', 'examAppointment', 'interviewAppointment'])
            ->when($request->stage, fn ($q, $v) => $q->where('stage', $v))
            ->when($request->q, fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")))
            ->when($request->exam_date, fn ($q, $v) => $q->whereHas('examAppointment', fn ($a) => $a->whereDate('appointment_date', $v)))
            ->when($request->interview_date, fn ($q, $v) => $q->whereHas('interviewAppointment', fn ($a) => $a->whereDate('appointment_date', $v)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.applications.index', ['applications' => $applications, 'stages' => Application::STAGES]);
    }

    public function show(Application $application)
    {
        $application->load(['user.profile', 'documents', 'examAppointment', 'interviewAppointment']);

        return view('admin.applications.show', compact('application'));
    }

    /** Record the exam result. Students cannot see it until the batch is released. */
    public function examResult(Request $request, Application $application)
    {
        abort_unless($application->stage === 'exam_scheduled', 422, 'Student is not awaiting an exam result.');
        $data = $request->validate(['exam_result' => ['required', Rule::in(['passed', 'failed'])]]);

        $application->update($data);

        return back()->with('success', 'Exam result saved (hidden from the student until you release results).');
    }

    /** STEP 5→6 — "THE EXAM RESULT WAS ALREADY RELEASED". */
    public function releaseResults()
    {
        $count = 0;

        Application::with('user')
            ->where('stage', 'exam_scheduled')
            ->whereNotNull('exam_result')
            ->each(function (Application $app) use (&$count) {
                $passed = $app->exam_result === 'passed';

                $app->update([
                    'stage'                   => $passed ? 'exam_passed' : 'exam_failed',
                    'exam_result_released_at' => now(),
                    // FAILED => account is disposed within 30 days
                    'dispose_at'              => $passed ? null : now()->addDays(config('cics.dispose_days')),
                ]);

                $app->user->notify(new StatusUpdated(
                    'Your CICS exam result is out',
                    $passed
                        ? 'Congratulations, you passed! Log in and schedule your Interview & submission of requirements.'
                        : 'Unfortunately you did not pass the CICS exam. Your account will be removed within ' . config('cics.dispose_days') . ' days.'
                ));
                $count++;
            });

        return back()->with('success', "Released {$count} exam result(s).");
    }

    /** STEP 8 — approve / not approve after interview. */
    public function interviewResult(Request $request, Application $application)
    {
        abort_unless($application->stage === 'interview_scheduled', 422, 'Student is not awaiting an interview evaluation.');
        $data = $request->validate(['interview_result' => ['required', Rule::in(['approved', 'not_approved'])]]);

        $approved = $data['interview_result'] === 'approved';

        $application->update([
            'interview_result' => $data['interview_result'],
            'stage'            => $approved ? 'approved' : 'not_approved',
            'approved_at'      => $approved ? now() : null,
        ]);

        $application->user->notify(new StatusUpdated(
            $approved ? 'You have been approved!' : 'Your application status',
            $approved
                ? 'You passed the interview and your requirements were approved. You will be assigned to a fee payment batch soon.'
                : 'Your application was not approved after the interview. Please contact the CICS office for details.'
        ));

        return back()->with('success', 'Interview result saved and the student was notified.');
    }

    /** STEP 9 — group approved students into 2-3 batches for payment of department & other fees. */
    public function generateBatches(Request $request)
    {
        $max = config('cics.max_fee_batches');
        $data = $request->validate([
            'batches' => ['required', 'integer', 'min:2', 'max:' . $max],
            'dates'   => ['required', 'array'],
            'dates.0' => ['required', 'date', 'after:today'],
            'dates.1' => ['required', 'date', 'after:today'],
            'dates.2' => ['required_if:batches,3', 'nullable', 'date', 'after:today'],
        ]);

        $apps = Application::with('user')->where('stage', 'approved')->whereNull('fee_batch')->orderBy('approved_at')->get();
        if ($apps->isEmpty()) {
            return back()->with('error', 'No approved students are waiting for a batch.');
        }

        $size = (int) ceil($apps->count() / $data['batches']);

        foreach ($apps->chunk($size)->values() as $i => $chunk) {
            foreach ($chunk as $app) {
                $app->update(['stage' => 'batched', 'fee_batch' => $i + 1, 'fee_payment_date' => $data['dates'][$i]]);

                $app->user->notify(new StatusUpdated(
                    'Fee payment batch assigned',
                    'You are in Batch ' . ($i + 1) . '. Please pay your department and other required fees on ' . \Carbon\Carbon::parse($data['dates'][$i])->format('F j, Y') . '.'
                ));
            }
        }

        return back()->with('success', "Grouped {$apps->count()} student(s) into {$data['batches']} batches.");
    }
}
