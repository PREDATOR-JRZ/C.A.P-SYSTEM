<?php

namespace App\Services;

use App\Exceptions\WorkflowException;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Document;
use App\Models\FeeClearance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationWorkflow
{
    public function transition(Application $application, string $toStage, User $actor, string $action, array $attributes = [], array $context = []): Application
    {
        return DB::transaction(function () use ($application, $toStage, $actor, $action, $attributes, $context) {
            $application = Application::query()->lockForUpdate()->findOrFail($application->id);
            $from = $application->stage;

            if ($from === $toStage && ($context['allow_same'] ?? false) !== true) {
                return $application;
            }

            $this->assertTransitionAllowed($application, $toStage, $context);

            $oldValues = ['stage' => $from];
            $application->fill($attributes);
            $application->stage = $toStage;
            $application->stage_changed_at = now();
            $application->save();

            $newValues = ['stage' => $toStage];
            foreach ($attributes as $key => $value) {
                $newValues[$key] = $value;
            }

            $this->log($application, $actor, $action, $from, $toStage, $context['entity_type'] ?? null, $context['entity_id'] ?? null, $oldValues, $newValues, $context['reason'] ?? null);

            return $application->fresh();
        });
    }

    public function log(Application $application, ?User $actor, string $action, ?string $fromStage, ?string $toStage, ?string $entityType = null, ?int $entityId = null, array $oldValues = [], array $newValues = [], ?string $reason = null): ApplicationStatusLog
    {
        return ApplicationStatusLog::create([
            'application_id' => $application->id,
            'actor_user_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'action' => $action,
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'reason' => $reason,
            'ip' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
    }

    public function confirmEligibility(Application $application, User $actor, array $data): Application
    {
        $this->assertActorCan($actor, 'canReviewRequirements');
        if (! in_array($application->stage, ['eligibility_pending', 'eligible'], true)) {
            throw WorkflowException::invalidTransition($application->stage, 'eligible', 'Eligibility can only be confirmed from eligibility_pending.');
        }
        if (empty($data['cat_reference_no']) && empty($data['cat_verified_at'])) {
            throw ValidationException::withMessages(['cat_reference_no' => 'CAT reference/evidence is required to confirm eligibility.']);
        }

        return $this->transition($application, 'eligible', $actor, 'cat_eligibility_confirmed', [
            'cat_status' => 'passed',
            'cat_reference_no' => $data['cat_reference_no'] ?? $application->cat_reference_no,
            'cat_verified_at' => $data['cat_verified_at'] ?? now(),
            'cat_verified_by' => $actor->id,
        ], ['reason' => $data['reason'] ?? null]);
    }

    public function submitRequirements(Application $application, User $actor): Application
    {
        if ($application->user_id !== $actor->id) {
            abort(403);
        }
        if (! in_array($application->stage, ['eligible', 'requirements_returned'], true)) {
            throw WorkflowException::invalidTransition($application->stage, 'requirements_submitted', 'Requirements can only be submitted when eligible or returned.');
        }
        $missing = $this->missingRequiredDocuments($application);
        if ($missing !== []) {
            throw ValidationException::withMessages(['documents' => 'Missing required documents: ' . implode(', ', $missing)]);
        }

        $application->documents()->whereIn('status', [Document::STATUS_UPLOADED, Document::STATUS_REJECTED, Document::STATUS_RESUBMISSION_REQUESTED])->update([
            'status' => Document::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return $this->transition($application, 'requirements_submitted', $actor, 'requirements_submitted');
    }

    public function startRequirementsReview(Application $application, User $actor): Application
    {
        $this->assertActorCan($actor, 'canReviewRequirements');
        if ($application->stage !== 'requirements_submitted') {
            throw WorkflowException::invalidTransition($application->stage, 'requirements_in_review');
        }
        $application->documents()->where('status', Document::STATUS_SUBMITTED)->update(['status' => Document::STATUS_PENDING_REVIEW]);
        return $this->transition($application, 'requirements_in_review', $actor, 'requirements_review_started');
    }

    public function reviewDocument(Application $application, Document $document, User $actor, string $status, ?string $note = null): Document
    {
        $this->assertActorCan($actor, 'canReviewRequirements');
        if ($document->application_id !== $application->id) {
            abort(404);
        }
        if (! in_array($application->stage, ['requirements_submitted', 'requirements_in_review', 'requirements_returned'], true)) {
            throw WorkflowException::invalidTransition($application->stage, $application->stage, 'Documents can only be reviewed during requirements review.');
        }
        if (! in_array($status, [Document::STATUS_APPROVED, Document::STATUS_REJECTED, Document::STATUS_RESUBMISSION_REQUESTED, Document::STATUS_WAIVED], true)) {
            throw ValidationException::withMessages(['status' => 'Invalid document review status.']);
        }
        if (in_array($status, [Document::STATUS_REJECTED, Document::STATUS_RESUBMISSION_REQUESTED], true) && blank($note)) {
            throw ValidationException::withMessages(['note' => 'A student-visible reason is required when returning a document.']);
        }

        $old = ['status' => $document->status, 'review_notes' => $document->review_notes];
        $document->update([
            'status' => $status,
            'review_notes' => $note,
            'student_visible_note' => $note,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);

        $this->log($application, $actor, 'document_reviewed', $application->stage, $application->stage, Document::class, $document->id, $old, [
            'status' => $status, 'review_notes' => $note,
        ], $note);

        $this->syncRequirementsStage($application->fresh(), $actor);
        return $document->fresh();
    }

    public function syncRequirementsStage(Application $application, User $actor): Application
    {
        $application->loadMissing('documents');
        if (! in_array($application->stage, ['requirements_submitted', 'requirements_in_review', 'requirements_returned'], true)) {
            return $application;
        }

        $requiredLabels = $this->requiredDocumentLabels($application);
        $latestByLabel = $application->documents->sortByDesc('id')->groupBy('label');
        $allApproved = true;
        $hasReturned = false;
        foreach ($requiredLabels as $label) {
            $doc = $latestByLabel->get($label)?->first();
            if (! $doc || ! $doc->isApprovedOrWaived()) {
                $allApproved = false;
            }
            if ($doc && in_array($doc->status, [Document::STATUS_REJECTED, Document::STATUS_RESUBMISSION_REQUESTED], true)) {
                $hasReturned = true;
            }
        }

        if ($allApproved) {
            return $this->transition($application, 'requirements_approved', $actor, 'requirements_approved', [], ['allow_same' => false]);
        }
        if ($hasReturned) {
            return $this->transition($application, 'requirements_returned', $actor, 'requirements_returned', [], ['allow_same' => false]);
        }
        if ($application->stage === 'requirements_submitted') {
            return $this->transition($application, 'requirements_in_review', $actor, 'requirements_review_started', [], ['allow_same' => false]);
        }

        return $application;
    }

    public function recordExamResult(Application $application, User $actor, string $result, ?float $score = null): Application
    {
        $this->assertActorCan($actor, 'canRecordEvaluation');
        if ($application->stage !== 'exam_scheduled') {
            throw WorkflowException::invalidTransition($application->stage, $application->stage, 'Exam result can only be recorded when exam is scheduled.');
        }
        if (! in_array($result, ['passed', 'failed'], true)) {
            throw ValidationException::withMessages(['exam_result' => 'Invalid exam result.']);
        }

        $old = ['exam_result' => $application->exam_result, 'exam_score' => $application->exam_score];
        $application->update([
            'exam_result' => $result,
            'exam_score' => $score,
            'exam_recorded_by' => $actor->id,
        ]);
        $this->log($application->fresh(), $actor, 'exam_result_recorded', $application->stage, $application->stage, Application::class, $application->id, $old, ['exam_result' => $result, 'exam_score' => $score]);

        return $application->fresh();
    }

    public function releaseExamResults(User $actor): int
    {
        $this->assertActorCan($actor, 'canRecordEvaluation');
        $count = 0;
        Application::query()->where('stage', 'exam_scheduled')->whereNotNull('exam_result')->each(function (Application $app) use ($actor, &$count) {
            $passed = $app->exam_result === 'passed';
            $to = $passed ? 'exam_passed' : 'exam_failed';
            $this->transition($app, $to, $actor, 'exam_result_released', [
                'exam_result_released_at' => now(),
                'exam_released_by' => $actor->id,
                'dispose_at' => $passed ? null : now()->addDays(config('cics.dispose_days', 30)),
                'closed_reason' => $passed ? null : 'Failed CICS evaluation exam.',
                'closed_at' => $passed ? null : now(),
                'closed_by' => $passed ? null : $actor->id,
            ]);
            $count++;
        });
        return $count;
    }

    public function recordInterviewResult(Application $application, User $actor, string $result, ?string $notes = null): Application
    {
        $this->assertActorCan($actor, 'canRecordEvaluation');
        if ($application->stage !== 'interview_scheduled') {
            throw WorkflowException::invalidTransition($application->stage, $application->stage, 'Interview result can only be recorded when interview is scheduled.');
        }
        if (! in_array($result, ['approved', 'not_approved'], true)) {
            throw ValidationException::withMessages(['interview_result' => 'Invalid interview result.']);
        }

        $approved = $result === 'approved';
        $to = $approved ? 'accepted_for_clearance' : 'not_approved';
        $app = $this->transition($application, $to, $actor, 'interview_result_recorded', [
            'interview_result' => $result,
            'interview_notes' => $notes,
            'interviewed_at' => now(),
            'interviewed_by' => $actor->id,
            'approved_at' => $approved ? now() : null,
            'accepted_by' => $approved ? $actor->id : null,
            'closed_reason' => $approved ? null : ($notes ?: 'Not approved after interview/final evaluation.'),
            'closed_at' => $approved ? null : now(),
            'closed_by' => $approved ? null : $actor->id,
        ], ['reason' => $notes]);

        if ($approved) {
            $this->ensureDefaultFeeClearances($app);
            $this->refreshEligibilityForOfficialEnrollment($app->fresh(), $actor);
        }

        return $app->fresh();
    }

    public function ensureDefaultFeeClearances(Application $application): void
    {
        foreach ([
            [FeeClearance::DEPARTMENT, 'Department Fee'],
            [FeeClearance::INSURANCE, 'Insurance Fee'],
        ] as [$type, $label]) {
            $application->feeClearances()->firstOrCreate(['fee_type' => $type], [
                'label' => $label,
                'status' => 'unpaid',
                'currency' => 'PHP',
            ]);
        }
    }

    public function submitFeeProof(Application $application, FeeClearance $fee, User $actor, array $data): FeeClearance
    {
        if ($application->user_id !== $actor->id) {
            abort(403);
        }
        if ($fee->application_id !== $application->id) {
            abort(404);
        }
        if (! in_array($application->stage, ['accepted_for_clearance', 'eligible_for_official_enrollment', 'prism_pending'], true)) {
            throw WorkflowException::invalidTransition($application->stage, $application->stage, 'Fee proof can only be submitted during clearance/handoff stages.');
        }

        $old = $fee->only(['status', 'reference_no', 'proof_path']);
        $fee->update([
            'status' => 'pending_verification',
            'payment_method' => $data['payment_method'] ?? $fee->payment_method,
            'reference_no' => $data['reference_no'] ?? $fee->reference_no,
            'proof_document_id' => $data['proof_document_id'] ?? $fee->proof_document_id,
            'proof_path' => $data['proof_path'] ?? $fee->proof_path,
            'proof_original_name' => $data['proof_original_name'] ?? $fee->proof_original_name,
            'submitted_by' => $actor->id,
            'submitted_at' => now(),
            'notes' => $data['notes'] ?? $fee->notes,
        ]);
        $this->log($application, $actor, 'fee_proof_submitted', $application->stage, $application->stage, FeeClearance::class, $fee->id, $old, $fee->only(['status', 'reference_no', 'proof_path']));
        return $fee->fresh();
    }

    public function verifyFee(Application $application, FeeClearance $fee, User $actor, string $status, ?string $note = null): FeeClearance
    {
        $this->assertActorCan($actor, 'canReviewRequirements');
        if ($fee->application_id !== $application->id) {
            abort(404);
        }
        if (! in_array($status, ['verified_paid', 'unpaid', 'pending_verification'], true)) {
            throw ValidationException::withMessages(['status' => 'Invalid fee verification status.']);
        }
        $old = $fee->only(['status', 'verified_by', 'verified_at', 'notes']);
        $fee->update([
            'status' => $status,
            'verified_by' => $status === 'verified_paid' ? $actor->id : null,
            'verified_at' => $status === 'verified_paid' ? now() : null,
            'notes' => $note ?? $fee->notes,
        ]);
        $this->log($application, $actor, 'fee_verified', $application->stage, $application->stage, FeeClearance::class, $fee->id, $old, $fee->only(['status', 'verified_by', 'verified_at', 'notes']), $note);
        $this->refreshEligibilityForOfficialEnrollment($application->fresh(), $actor);
        return $fee->fresh();
    }

    public function waiveFee(Application $application, FeeClearance $fee, User $actor, string $reason): FeeClearance
    {
        $this->assertActorCan($actor, 'canRecordEvaluation');
        if ($fee->application_id !== $application->id) {
            abort(404);
        }
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A waiver reason is required.']);
        }
        $old = $fee->only(['status', 'waived_by', 'waived_at', 'waiver_reason']);
        $fee->update([
            'status' => 'waived',
            'waived_by' => $actor->id,
            'waived_at' => now(),
            'waiver_reason' => $reason,
        ]);
        $this->log($application, $actor, 'fee_waived', $application->stage, $application->stage, FeeClearance::class, $fee->id, $old, $fee->only(['status', 'waived_by', 'waived_at', 'waiver_reason']), $reason);
        $this->refreshEligibilityForOfficialEnrollment($application->fresh(), $actor);
        return $fee->fresh();
    }

    public function refreshEligibilityForOfficialEnrollment(Application $application, User $actor): Application
    {
        $application->loadMissing('feeClearances');
        if (! in_array($application->stage, ['accepted_for_clearance', 'eligible_for_official_enrollment', 'prism_pending'], true)) {
            return $application;
        }

        $allCleared = $application->hasAllRequiredFeeClearances();
        if ($allCleared && $application->stage === 'accepted_for_clearance') {
            return $this->transition($application, 'eligible_for_official_enrollment', $actor, 'eligible_for_official_enrollment', [
                'eligible_for_official_enrollment_at' => now(),
                'eligible_by' => $actor->id,
                'prism_status' => 'pending',
            ]);
        }

        if (! $allCleared && in_array($application->stage, ['eligible_for_official_enrollment', 'prism_pending'], true) && $application->officially_enrolled_at === null) {
            return $this->transition($application, 'accepted_for_clearance', $actor, 'eligibility_revoked_fee_clearance_missing', [
                'eligible_for_official_enrollment_at' => null,
                'eligible_by' => null,
                'prism_status' => 'not_ready',
            ], ['reason' => 'Required fee clearance no longer satisfied.']);
        }

        return $application;
    }

    public function markPrismPending(Application $application, User $actor): Application
    {
        $this->assertActorCan($actor, 'canEncodePrism');
        $application->loadMissing('feeClearances');
        $this->assertPrismReady($application);
        if ($application->stage !== 'eligible_for_official_enrollment') {
            throw WorkflowException::invalidTransition($application->stage, 'prism_pending');
        }
        return $this->transition($application, 'prism_pending', $actor, 'prism_encoding_started', ['prism_status' => 'pending']);
    }

    public function markOfficiallyEnrolled(Application $application, User $actor, string $prismReferenceNo, ?string $notes = null): Application
    {
        $this->assertActorCan($actor, 'canEncodePrism');
        $application = Application::query()->lockForUpdate()->findOrFail($application->id);
        $application->loadMissing('feeClearances');

        if ($application->stage === 'officially_enrolled') {
            if ($application->prism_reference_no === $prismReferenceNo) {
                return $application; // idempotent
            }
            throw WorkflowException::invalidTransition($application->stage, 'officially_enrolled', 'Conflicting PRISM reference requires audited correction.');
        }

        $this->assertPrismReady($application);
        if (! in_array($application->stage, ['eligible_for_official_enrollment', 'prism_pending'], true)) {
            throw WorkflowException::invalidTransition($application->stage, 'officially_enrolled');
        }

        $from = $application->stage;
        $application->update([
            'stage' => 'officially_enrolled',
            'stage_changed_at' => now(),
            'prism_status' => 'encoded',
            'prism_reference_no' => $prismReferenceNo,
            'prism_encoded_at' => now(),
            'prism_encoded_by' => $actor->id,
            'prism_notes' => $notes,
            'officially_enrolled_at' => now(),
        ]);
        $this->log($application->fresh(), $actor, 'prism_encoded_officially_enrolled', $from, 'officially_enrolled', Application::class, $application->id, ['stage' => $from], [
            'stage' => 'officially_enrolled', 'prism_reference_no' => $prismReferenceNo,
        ], $notes);

        return $application->fresh();
    }

    public function requiredDocumentLabels(Application $application): array
    {
        $status = $application->user?->profile?->student_status;
        $requirements = config("cics.requirements.{$status}", []);
        return array_values(array_filter($requirements, fn ($label) => $label !== null && $label !== ''));
    }

    public function missingRequiredDocuments(Application $application): array
    {
        $application->loadMissing('documents');
        $latestByLabel = $application->documents->sortByDesc('id')->groupBy('label');
        $missing = [];
        foreach ($this->requiredDocumentLabels($application) as $label) {
            $doc = $latestByLabel->get($label)?->first();
            if (! $doc || ! in_array($doc->status, [Document::STATUS_UPLOADED, Document::STATUS_SUBMITTED, Document::STATUS_PENDING_REVIEW, Document::STATUS_APPROVED, Document::STATUS_WAIVED], true)) {
                $missing[] = $label;
            }
        }
        return $missing;
    }

    public function assertCanScheduleExam(Application $application): void
    {
        if ($application->stage !== 'requirements_approved') {
            throw WorkflowException::invalidTransition($application->stage, 'exam_scheduled', 'Exam scheduling requires requirements_approved.');
        }
    }

    public function assertCanScheduleInterview(Application $application): void
    {
        if ($application->stage !== 'exam_passed') {
            throw WorkflowException::invalidTransition($application->stage, 'interview_scheduled', 'Interview scheduling requires exam_passed.');
        }
    }

    private function assertPrismReady(Application $application): void
    {
        $unmet = [];
        if (! in_array($application->stage, ['eligible_for_official_enrollment', 'prism_pending', 'officially_enrolled'], true)) {
            $unmet[] = 'stage must be eligible_for_official_enrollment';
        }
        if (! $application->hasAllRequiredFeeClearances()) {
            $unmet[] = 'Department Fee and Insurance Fee must be verified_paid or waived';
        }
        if ($unmet !== []) {
            throw ValidationException::withMessages(['prism' => $unmet]);
        }
    }

    private function assertActorCan(User $actor, string $ability): void
    {
        abort_unless($actor->{$ability}(), 403);
    }

    private function assertTransitionAllowed(Application $application, string $toStage, array $context): void
    {
        if (($context['force'] ?? false) === true) {
            return;
        }
        if ($application->isTerminal() && ! ($context['allow_terminal'] ?? false)) {
            throw WorkflowException::invalidTransition($application->stage, $toStage, 'Terminal applications require audited correction.');
        }
    }
}
