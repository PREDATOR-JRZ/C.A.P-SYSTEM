<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Document;
use App\Models\FeeClearance;
use App\Notifications\StatusUpdated;
use App\Services\ApplicationWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationWorkflow $workflow)
    {
    }

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
        $application->load(['user.profile', 'documents.reviewer', 'examAppointment', 'interviewAppointment', 'feeClearances.proofDocument', 'statusLogs.actor']);
        $this->workflow->ensureDefaultFeeClearances($application);

        return view('admin.applications.show', [
            'application' => $application->fresh(['user.profile', 'documents.reviewer', 'examAppointment', 'interviewAppointment', 'feeClearances.proofDocument', 'statusLogs.actor']),
        ]);
    }

    public function confirmEligibility(Request $request, Application $application)
    {
        abort_unless(auth()->user()->canReviewRequirements(), 403);
        $data = $request->validate([
            'cat_reference_no' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $data['cat_verified_at'] = now();
        $this->workflow->confirmEligibility($application, auth()->user(), $data);

        return back()->with('success', 'CAT eligibility confirmed. Applicant can now submit college requirements.');
    }

    public function reviewDocument(Request $request, Application $application, Document $document)
    {
        abort_unless(auth()->user()->canReviewRequirements(), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in([Document::STATUS_APPROVED, Document::STATUS_REJECTED, Document::STATUS_RESUBMISSION_REQUESTED, Document::STATUS_WAIVED])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->workflow->reviewDocument($application, $document, auth()->user(), $data['status'], $data['note'] ?? null);

        return back()->with('success', 'Document review saved.');
    }

    public function examResult(Request $request, Application $application)
    {
        abort_unless(auth()->user()->canRecordEvaluation(), 403);
        $data = $request->validate([
            'exam_result' => ['required', Rule::in(['passed', 'failed'])],
            'exam_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $this->workflow->recordExamResult($application, auth()->user(), $data['exam_result'], isset($data['exam_score']) ? (float) $data['exam_score'] : null);

        return back()->with('success', 'Exam result saved (hidden from the student until you release results).');
    }

    public function releaseResults()
    {
        abort_unless(auth()->user()->canRecordEvaluation(), 403);
        $count = $this->workflow->releaseExamResults(auth()->user());

        Application::with('user')
            ->whereIn('stage', ['exam_passed', 'exam_failed'])
            ->whereNotNull('exam_result_released_at')
            ->where('exam_result_released_at', '>=', now()->subMinute())
            ->get()
            ->each(function (Application $app) {
                $passed = $app->exam_result === 'passed';
                $app->user->notify(new StatusUpdated(
                    'Your CICS exam result is out',
                    $passed
                        ? 'Congratulations, you passed the CICS evaluation exam. Log in and schedule your interview.'
                        : 'Unfortunately you did not pass the CICS evaluation exam. Your account will be handled according to disposal policy.'
                ));
            });

        return back()->with('success', "Released {$count} exam result(s).");
    }

    public function interviewResult(Request $request, Application $application)
    {
        abort_unless(auth()->user()->canRecordEvaluation(), 403);
        $data = $request->validate([
            'interview_result' => ['required', Rule::in(['approved', 'not_approved'])],
            'interview_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $app = $this->workflow->recordInterviewResult($application, auth()->user(), $data['interview_result'], $data['interview_notes'] ?? null);
        $approved = $data['interview_result'] === 'approved';
        $app->user->notify(new StatusUpdated(
            $approved ? 'You have been accepted for clearance' : 'Your application status',
            $approved
                ? 'You passed the interview/final evaluation. Please complete Department Fee and Insurance Fee clearance before PRISM enrollment.'
                : 'Your application was not approved after the interview/final evaluation. Please contact the CICS office for details.'
        ));

        return back()->with('success', 'Interview result saved and the student was notified.');
    }

    public function verifyFee(Request $request, Application $application, FeeClearance $feeClearance)
    {
        abort_unless(auth()->user()->canReviewRequirements(), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['verified_paid', 'unpaid', 'pending_verification'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->workflow->verifyFee($application, $feeClearance, auth()->user(), $data['status'], $data['notes'] ?? null);
        return back()->with('success', 'Fee clearance updated.');
    }

    public function waiveFee(Request $request, Application $application, FeeClearance $feeClearance)
    {
        abort_unless(auth()->user()->canRecordEvaluation(), 403);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $this->workflow->waiveFee($application, $feeClearance, auth()->user(), $data['reason']);
        return back()->with('success', 'Fee waived and audit logged.');
    }

    public function markPrismPending(Application $application)
    {
        abort_unless(auth()->user()->canEncodePrism(), 403);
        $this->workflow->markPrismPending($application, auth()->user());
        return back()->with('success', 'Application marked as PRISM encoding in progress.');
    }

    public function markOfficiallyEnrolled(Request $request, Application $application)
    {
        abort_unless(auth()->user()->canEncodePrism(), 403);
        $data = $request->validate([
            'prism_reference_no' => ['required', 'string', 'max:100'],
            'prism_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $app = $this->workflow->markOfficiallyEnrolled($application, auth()->user(), $data['prism_reference_no'], $data['prism_notes'] ?? null);
        $app->user->notify(new StatusUpdated(
            'Officially enrolled in PRISM',
            'Your college pre-enrollment evaluation is complete and PRISM encoding has been recorded. Reference: ' . $data['prism_reference_no']
        ));

        return back()->with('success', 'PRISM encoding recorded. Application is officially enrolled.');
    }

    public function generateBatches(Request $request)
    {
        return back()->with('error', 'Fixed payment batching is no longer used. Use Department/Insurance fee clearance verification and PRISM handoff instead.');
    }
}
