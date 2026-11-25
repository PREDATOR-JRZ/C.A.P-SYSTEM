<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Appointment;
use App\Models\Document;
use App\Models\FeeClearance;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowGatesTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(string $stage = 'eligible'): array
    {
        $user = User::create([
            'name' => 'Student One',
            'email' => 'student@example.com',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
        StudentProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Student',
            'last_name' => 'One',
            'birthdate' => '2000-01-01',
            'sex' => 'Male',
            'contact_number' => '09170000000',
            'address' => 'Test Address',
            'student_status' => 'Freshman',
            'course_applied' => 'BSIT',
        ]);
        $application = Application::create([
            'user_id' => $user->id,
            'stage' => $stage,
            'stage_changed_at' => now(),
            'cat_status' => $stage === 'eligibility_pending' ? 'pending_confirmation' : 'passed',
        ]);
        return [$user, $application->fresh()];
    }

    private function makeStaff(string $role = User::ROLE_ADMISSIONS_ADMIN): User
    {
        return User::create([
            'name' => 'Staff User',
            'email' => $role . '@example.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    public function test_requirements_upload_does_not_auto_approve_or_advance_to_approved(): void
    {
        Storage::fake('local');
        [$user, $application] = $this->makeStudent('eligible');

        $response = $this->actingAs($user)->post(route('student.requirements.store'), [
            'files' => [
                'Admission Test Result' => UploadedFile::fake()->create('atr.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();
        $application->refresh();
        $this->assertSame('eligible', $application->stage);
        $doc = $application->documents()->first();
        $this->assertNotNull($doc);
        $this->assertSame(Document::STATUS_UPLOADED, $doc->status);
    }

    public function test_interview_cannot_be_scheduled_before_exam_passed(): void
    {
        [$user, $application] = $this->makeStudent('requirements_approved');

        $response = $this->actingAs($user)->get(route('student.appointments.create', 'interview'));
        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_prism_handoff_requires_fee_clearance_and_is_idempotent(): void
    {
        $staff = $this->makeStaff(User::ROLE_PRISM_ENCODER);
        [$student, $application] = $this->makeStudent('eligible');
        $application->update(['stage' => 'eligible_for_official_enrollment', 'prism_status' => 'pending']);

        FeeClearance::create(['application_id' => $application->id, 'fee_type' => FeeClearance::DEPARTMENT, 'label' => 'Department Fee', 'status' => 'verified_paid', 'currency' => 'PHP']);
        FeeClearance::create(['application_id' => $application->id, 'fee_type' => FeeClearance::INSURANCE, 'label' => 'Insurance Fee', 'status' => 'unpaid', 'currency' => 'PHP']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ApplicationWorkflow::class)->markOfficiallyEnrolled($application, $staff, 'PRISM-001');
    }

    public function test_prism_handoff_is_idempotent_for_same_reference(): void
    {
        $staff = $this->makeStaff(User::ROLE_PRISM_ENCODER);
        [$student, $application] = $this->makeStudent('eligible');
        $application->update(['stage' => 'eligible_for_official_enrollment', 'prism_status' => 'pending']);
        FeeClearance::create(['application_id' => $application->id, 'fee_type' => FeeClearance::DEPARTMENT, 'label' => 'Department Fee', 'status' => 'verified_paid', 'currency' => 'PHP']);
        FeeClearance::create(['application_id' => $application->id, 'fee_type' => FeeClearance::INSURANCE, 'label' => 'Insurance Fee', 'status' => 'waived', 'currency' => 'PHP']);

        $workflow = app(ApplicationWorkflow::class);
        $first = $workflow->markOfficiallyEnrolled($application->fresh(), $staff, 'PRISM-001');
        $second = $workflow->markOfficiallyEnrolled($first->fresh(), $staff, 'PRISM-001');

        $this->assertSame('officially_enrolled', $second->stage);
        $this->assertSame('PRISM-001', $second->prism_reference_no);
    }
}