# CICS Pre-Enrollment Evaluation System — Implementation Spec

## 1. Core Goal

Build a **standalone college-owned admissions evaluation system** for CICS (and configurable for other colleges) that handles the manual college process **after a student passes the university CAT** and **before official enrollment in PRISM**.

The system fills PRISM’s pre-enrollment gap. It does **not** replace PRISM. PRISM remains the official university enrollment system of record. The college operates this system; the university registrar does not own or run the college evaluation workflow.

Primary outcome: move an applicant from CAT-passed intake to a college-verified **Eligible for Official Enrollment** state, then let authorized college staff record the manual PRISM encoding and close the cycle as **Officially Enrolled**.

## 2. Current Baseline (Repo)

Repository: `justinosaurus/C.A.P-SYSTEM` (Laravel app).

- **Stack**: PHP `^8.3`, Laravel `^13.17`, Laravel Tinker `^3.0`; dev tools include PHPUnit `^12.5.12`, Pint, Pail, Collision. Frontend/build: Vite `^8`, `laravel-vite-plugin`, Tailwind `^4`, `concurrently`. Default env example uses MySQL, database sessions/cache/queue, local filesystem, log mailer.
- **Current flow**: `registered -> requirements_submitted -> exam_scheduled -> exam_passed|exam_failed -> interview_scheduled -> approved|not_approved -> batched` ([app/Models/Application.php](C:/Users/Justine/Desktop/Playground/GitHub/C.A.P-SYSTEM/app/Models/Application.php)).
- **Current entry**: student self-registers ([app/Http/Controllers/Auth/RegisterController.php](C:/Users/Justine/Desktop/Playground/GitHub/C.A.P-SYSTEM/app/Http/Controllers/Auth/RegisterController.php)); creates user/profile/application.
- **Current requirements**: upload-only; uploading `Admission Test Result` auto-advances to `requirements_submitted` ([app/Http/Controllers/Student/RequirementController.php](C:/Users/Justine/Desktop/Playground/GitHub/C.A.P-SYSTEM/app/Http/Controllers/Student/RequirementController.php)). No per-document admin review state.
- **Current exam/interview**: exam/interview scheduling gated by stage ([app/Http/Controllers/Student/AppointmentController.php](C:/Users/Justine/Desktop/Playground/GitHub/C.A.P-SYSTEM/app/Http/Controllers/Student/AppointmentController.php)); admin records/release exam result and interview result ([app/Http/Controllers/Admin/ApplicationController.php](C:/Users/Justine/Desktop/Playground/GitHub/C.A.P-SYSTEM/app/Http/Controllers/Admin/ApplicationController.php)).
- **Current end state**: fee batch assignment (`batched`) with `fee_batch`/`fee_payment_date`; no PRISM handoff, no verified department/insurance fee clearance, no audit log.
- **Current schema**: users, student_profiles, applications, documents, appointments, announcements, notifications ([database/migrations/2026_01_01_000003_create_applications_table.php](C:/Users/Justine/Desktop/Playground/GitHub/C.A.P-SYSTEM/database/migrations/2026_01_01_000003_create_applications_table.php), [database/migrations/2026_01_01_000002_create_student_profiles_table.php](C:/Users/Justine/Desktop/Playground/GitHub/C.A.P-SYSTEM/database/migrations/2026_01_01_000002_create_student_profiles_table.php)).
- **Current config**: student statuses, courses, requirement labels, daily slots, booking window, dispose days, max fee batches ([config/cics.php](C:/Users/Justine/Desktop/Playground/GitHub/C.A.P-SYSTEM/config/cics.php)).

## 3. Scope and Boundaries

### In Scope
- College-managed applicant evaluation workflow after university CAT pass.
- Manual college review of submitted requirements.
- CICS/college evaluation exam and interview gates.
- Pre-PRISM payment clearance tracking (Department Fee, Insurance Fee) as requirements.
- Eligibility-for-official-enrollment flag/state.
- Manual PRISM handoff recording by authorized college staff.
- Audit trail, notifications, role-based access, secure document handling.

### Out of Scope / External
- Replacing PRISM or becoming the official university enrollment system of record.
- University registrar ownership of the college evaluation workflow.
- Automatic PRISM enrollment integration unless a future verified API is explicitly added; default is manual handoff recording.
- Online payment gateway processing as a requirement for MVP; manual verification is acceptable.
- Fixed payment scheduling/batching as a required gate.

## 4. Tech Stack

### Baseline
| Layer | Technology | Notes |
|---|---|---|
| Backend | PHP 8.3+, Laravel 13 | Existing app foundation. |
| Auth/App | Laravel built-in auth/session | Database sessions in env example. |
| DB | MySQL supported; Laravel migrations/schema | Env example defaults to MySQL; tests may use configured test DB. |
| Queue/Cache | Database driver (default example) | Use queues for mail/notifications/jobs. |
| Files | Laravel filesystem, private `local` disk | Requirement/proof files must not be public. |
| Frontend | Blade + Tailwind 4 + Vite 8 | Existing UI stack. |
| Tests | PHPUnit 12 | Add feature tests for workflow, authz, uploads, transitions. |
| Mail | Laravel mail; log mailer in dev | Appointment/status notifications. |

### Implementation Constraints
- Keep stack compatible with existing Laravel app unless a migration is justified.
- Use Laravel policies/gates, form requests/validation, queued notifications/mail, scheduled commands, and migrations.
- Add no secrets to source. Use env/config for credentials and deployment-specific values.

## 5. Roles and Permissions

All roles are **college-scoped** unless explicitly configured otherwise.

| Role | Responsibilities | Key Permissions |
|---|---|---|
| Applicant / Student | Submit profile, upload requirements/proofs, schedule allowed appointments, view status | View own application; upload/replace own pending/returned docs; submit proofs; no review/approval powers. |
| Admissions Staff | Manual document review, applicant communication support | Review documents, request resubmission, add notes, view queues; no final PRISM encoding unless granted. |
| Admissions Admin / College Evaluator | Exam/interview decisions, final acceptance, overrides | Record/release exam results, record interview results, mark accepted, manage batches/config as needed; audited overrides only. |
| PRISM Encoder / College Enrollment Staff | Manual PRISM handoff | Mark eligible record as PRISM encoded/officially enrolled; cannot bypass missing gates. |
| System Admin | Configuration and user/role management | Manage college config, roles, audit access; actions audited. |

Rules:
- Enforce authorization server-side on every route/action.
- Separate “review” from “approve final enrollment” where possible.
- PRISM handoff is a college staff action, not registrar-owned workflow.

## 6. Target Workflow / State Machine

### 6.1 Stages (Target)

| Stage | Owner | Description | Entry Gate | Exit / Next |
|---|---|---|---|---|
| `eligibility_pending` | Staff/System | CAT-passed intake captured; eligibility not yet confirmed. | Registration/import with CAT evidence | `eligible` or `closed_rejected/closed_ineligible` |
| `eligible` | Applicant | Eligible to submit college requirements. | CAT/eligibility confirmed | `requirements_submitted` |
| `requirements_submitted` | Applicant | All required docs submitted for review. | Required docs uploaded and student clicks submit | `requirements_in_review` |
| `requirements_in_review` | Admissions Staff | Manual per-document review in progress. | Submitted for review | `requirements_approved` or `requirements_returned` |
| `requirements_returned` | Applicant | One or more docs rejected/resubmission requested. | Staff returned with notes | `requirements_submitted` after resubmission |
| `requirements_approved` | Applicant | Required docs approved; can proceed to evaluation exam scheduling. | All required docs approved/waived | `exam_scheduled` |
| `exam_scheduled` | Applicant/Admin | CICS evaluation exam appointment scheduled. | Requirements approved | `exam_passed` or `exam_failed` after release |
| `exam_passed` | Applicant | Passed evaluation exam; eligible for interview scheduling. | Admin recorded/passed and released result | `interview_scheduled` |
| `exam_failed` | System/Admin | Failed evaluation exam. Terminal unless retake policy is explicitly enabled. | Released failed result | `closed_failed` / disposal flow |
| `interview_scheduled` | Admin | Interview scheduled; awaiting evaluation. | `exam_passed` only | `accepted_for_clearance` or `not_approved` |
| `accepted_for_clearance` | Applicant/Staff | Passed interview/final college acceptance; payment clearance required. | Interview approved and required checks complete | `eligible_for_official_enrollment` |
| `not_approved` | Admin | Not approved after interview/final college evaluation. | Interview/final review not approved | terminal/closed with reason |
| `eligible_for_official_enrollment` | College PRISM Encoder | All gates and required fee clearances satisfied; ready for PRISM. | All required gates + both fees verified/waived | `prism_pending` or `officially_enrolled` |
| `prism_pending` | College PRISM Encoder | Optional pickup/encoding-in-progress state. | Encoder claims/starts handoff | `officially_enrolled` |
| `officially_enrolled` | College PRISM Encoder | PRISM encoding recorded; admission cycle closed. | Manual PRISM confirmation recorded | terminal |
| `closed_*` | Admin/System | Closed/rejected/expired/failed/withdrawn as applicable. | Policy-specific | terminal |

### 6.2 Hard Gates

- **CAT gate**: no college evaluation progression without CAT-pass eligibility evidence/confirmation.
- **Requirements gate**: no exam scheduling before required documents are manually approved/waived.
- **Evaluation exam gate**: no interview scheduling and no interview result/final acceptance path unless `exam_passed`. This is a separate gate.
- **Payment clearance gate**: no `eligible_for_official_enrollment` until **Department Fee** and **Insurance Fee** are `verified_paid` or explicitly `waived`.
- **PRISM handoff gate**: only authorized college staff can mark `officially_enrolled`; requires PRISM reference/date and prior eligibility.

### 6.3 State Machine Rules

- Implement transitions in a single service (e.g., `ApplicationWorkflow`/domain service). Controllers must call the service, not mutate stage ad hoc.
- Validate current stage, required data, permissions, and audit reason before transition.
- All transitions write audit log entries.
- Terminal states are read-only except privileged, audited correction.

## 7. Data Models (Target)

> Target/proposed schema. Adapt names to Laravel conventions during implementation; preserve compatibility/migrations from existing tables where possible.

### 7.1 `users` (existing, extend)
- Existing: name, email, password, role.
- Target: support college-scoped roles/permissions; optionally `college_id` if multi-college in same deployment.
- Rules: unique email; inactive/disabled users cannot act.

### 7.2 `colleges` (new, optional for MVP; required for multi-college)
- `id`, `name`, `code`, `slug`, `is_active`, timestamps.
- Use if supporting “other colleges” in one deployment. If single-college deployment initially, keep config simple but avoid hardcoding CICS-only assumptions in domain logic.

### 7.3 `student_profiles` (existing, extend)
- Existing: personal info, student_status, course_applied, previous_school, guardian.
- Add/consider: `college_id`, `academic_year`, `term`, `cat_reference_no`, `cat_passed_at`/evidence fields, `contact_verified_at`.
- PII: treat as sensitive; limit access by role and ownership.

### 7.4 `applications` (existing, extend)
- Existing: `user_id`, `stage`, exam/interview result fields, fee_batch fields, timestamps.
- Target fields:
  - `college_id` (if multi-college)
  - `academic_year`, `term`, `intake_batch`
  - `cat_status` / `cat_reference_no` / `cat_verified_at` / `cat_verified_by`
  - `stage`, `stage_changed_at`
  - `exam_result`, `exam_score` (optional), `exam_recorded_by`, `exam_released_at`, `exam_released_by`
  - `interview_result`, `interview_notes` (staff-only), `interviewed_at`, `interviewed_by`
  - `accepted_at`, `accepted_by`
  - `eligible_for_official_enrollment_at`, `eligible_by`
  - `prism_status` (`not_ready|pending|encoded|corrected` as needed)
  - `prism_reference_no`, `prism_encoded_at`, `prism_encoded_by`, `prism_notes`
  - `officially_enrolled_at`
  - `closed_reason`, `closed_at`, `closed_by`
  - `dispose_at` / retention fields as policy requires
- Deprecate scheduling-oriented fee fields after migration to fee clearance records (`fee_batch`, `fee_payment_date`) or keep read-only for historical data.

### 7.5 `requirement_definitions` (new or config-backed table)
- `id`, `college_id`, `student_status`/applicability rule, `key`, `label`, `description`, `is_required`, `requires_manual_review`, `active`, `sort_order`, timestamps.
- Source of truth for required docs. Seed/default from [config/cics.php](C:/Users/Justine/Desktop/Playground/GitHub/C.A.P-SYSTEM/config/cics.php).

### 7.6 `application_documents` / extend `documents`
- Existing: application_id, label, path, original_name.
- Target fields:
  - `requirement_key`, `requirement_label`
  - `version` or submission group
  - `status`: `uploaded | submitted | pending_review | approved | rejected | resubmission_requested | waived`
  - `review_notes` (staff-visible), `student_visible_note`
  - `reviewed_by`, `reviewed_at`
  - `submitted_at`
  - `file_path`, `original_name`, `mime_type`, `size_bytes`, `checksum`
  - `replaces_document_id` or version linkage
- Rules: keep history; do not silently delete prior reviewed versions.

### 7.7 `appointments` (existing)
- Existing: application_id, type, appointment_date, slot_number, reference_no.
- Keep exam/interview appointment types. Add audit/created_by/cancelled status if rescheduling/cancellation is needed.
- Enforce booking gates in service: exam only after requirements approved; interview only after `exam_passed`.

### 7.8 `fee_clearances` (new)
- `id`, `application_id`, `fee_type` (`department_fee|insurance_fee`), `label`, `amount` nullable/configured, `currency`
- `status`: `unpaid | pending_verification | verified_paid | waived | void`
- `payment_method`, `reference_no`
- `proof_document_id` or proof path
- `submitted_by`, `submitted_at`
- `verified_by`, `verified_at`
- `waived_by`, `waived_at`, `waiver_reason`
- `notes`, timestamps
- Unique constraint: one active record per `application_id + fee_type` (or versioned with active flag).

### 7.9 `application_status_logs` / audit log (new, required)
- `id`, `application_id`, `actor_user_id`, `actor_role`
- `action` (stage_change, document_review, exam_recorded, exam_released, interview_result, fee_verified, prism_encoded, override, etc.)
- `from_stage`, `to_stage`
- `entity_type`, `entity_id`
- `old_values`, `new_values` (JSON, redacted as needed)
- `reason/notes`, `ip`, `user_agent`, `created_at`
- Append-only in normal operation.

### 7.10 `announcements`, `notifications` (existing)
- Keep for applicant communication. Add notification events for: returned requirements, approved requirements, exam released, interview result, accepted for clearance, fee verified/rejected proof, eligible for PRISM, officially enrolled.

## 8. Edge Cases

| Case | Expected Handling |
|---|---|
| Applicant has not actually passed university CAT | Block eligibility confirmation; keep in `eligibility_pending` or close as ineligible. Require evidence/reference. |
| Student uploads wrong/incomplete/illegible document | Staff marks rejected/resubmission requested with student-visible reason; stage returns to `requirements_returned`. |
| Student resubmits after review started | Lock uploads during active review unless staff returns specific item; preserve prior version/history. |
| All docs uploaded but no submit action | Do not advance to review automatically unless product explicitly decides so; prefer explicit “Submit for review”. |
| Exam failed | Move to `exam_failed`; do not allow interview scheduling. Disposal/closure follows policy. Retakes only if explicitly configured. |
| Exam passed but result not released | Student cannot schedule interview until released/pass state is effective. |
| Interview attempted before exam pass | Reject server-side with 403/422; no appointment creation. |
| Staff tries to mark eligible for PRISM with missing gates | Workflow service rejects transition; show exact unmet requirements/fees. |
| Only one fee paid | Keep other fee unpaid/pending; application not eligible for PRISM. |
| Fee proof submitted but invalid | Mark fee proof rejected/pending correction with reason; do not count as verified. |
| Fee waived | Allowed only for authorized role with required reason; audit logged; counts toward clearance only if policy allows. |
| Duplicate PRISM encoding / double submit | Idempotent action: if already officially enrolled with same reference, no-op/safe response; conflicting reference requires audited correction. |
| Student account deletion/disposal | Respect retention policy; anonymize/delete PII as required; preserve minimal audit records if legally/policy allowed. |
| Staff overrides stage | Privileged role only, mandatory reason, audit log, never silent. |
| Multiple colleges in one deployment | Scope users, applicants, requirements, queues, and reports by `college_id`; prevent cross-college data access. |
| External mail/service outage | Do not fail core state transition if non-critical; queue notification and surface warning/log. |

## 9. Security, Privacy, and Operations

- **Access control**: Laravel policies/gates for all resources; owner checks for student documents/proofs; college-scope checks for staff.
- **Workflow integrity**: single workflow service; DB transactions for multi-step transitions; unique constraints for one active application/appointment type/fee type as applicable.
- **Uploads**: private storage only; validate extension, MIME sniffing, size; random storage names; checksum; authorized streaming/download endpoint. Consider malware scanning queue.
- **Auditability**: append-only audit logs for reviews, decisions, gate changes, fee verification, PRISM handoff, overrides.
- **PII**: least privilege, HTTPS, encrypted backups, retention/disposal jobs, avoid exposing staff-only notes to applicants.
- **Availability/ops**: queue mail/notifications; scheduled commands for disposal, reminders, stale review SLA alerts; idempotent jobs.
- **Observability**: log failures with context; admin dashboard for pending reviews, gate blockers, and handoff queue.
- **Testing**: feature tests for every gate, unauthorized access, invalid transitions, file validation/download authz, fee clearance, PRISM handoff idempotency.

## 10. Explicit Rules — What NOT To Do (Rejected / Forbidden)

- **Do NOT replace PRISM.** This system is pre-enrollment evaluation/handoff only; PRISM remains the official enrollment system of record.
- **Do NOT make this a university registrar-owned workflow.** It is college-owned and operated by college staff.
- **Do NOT auto-approve or auto-advance requirements on upload.** Manual college review is required. Uploading files must not be treated as approved.
- **Do NOT allow interview scheduling/evaluation before passing the CICS evaluation exam.** The evaluation exam is a separate hard gate.
- **Do NOT use fixed payment scheduling/batching as the payment model.** No required exact payment date/batch gate. Department Fee and Insurance Fee are pre-PRISM clearance requirements that can be completed when the student is able.
- **Do NOT mark fees paid based on student self-report alone.** Students may submit proof/reference; only authorized staff can verify/waive with audit reason.
- **Do NOT mark `eligible_for_official_enrollment` unless all required gates and both fee clearances are satisfied.**
- **Do NOT treat PRISM handoff as automatic.** Default is manual college staff encoding in PRISM, then recording reference/date/encoder in this system.
- **Do NOT allow silent overrides or undocumented state changes.** Every override/approval/rejection/verification/handoff needs actor, timestamp, and reason/audit record.
- **Do NOT expose private documents publicly or trust client-side file validation.**
- **Do NOT hardcode CICS-only assumptions if the system is intended for other colleges.** Keep college-specific requirements/rules/configurable.

## 11. Assumptions / Open Decisions

- **CAT evidence source**: manual upload/reference and staff confirmation, unless a verified university data source exists.
- **Retake policy**: whether failed CICS evaluation exam allows retakes, how many, and within what window/term.
- **Fee amounts and waivers**: fixed per college/term or configurable; waiver policy and approver role.
- **Payment proof**: receipt upload/reference number/manual cashier confirmation; no online gateway required initially.
- **Multi-college deployment**: single-tenant-per-deployment first or `college_id` multi-college model; decide before schema finalization.
- **PRISM integration**: manual recording first; API integration only if official, secure, idempotent, and audited.
- **Document retention/disposal**: legal/policy retention period, anonymization rules, and whether audit logs are immutable.
