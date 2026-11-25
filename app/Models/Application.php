<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * College-owned pre-enrollment evaluation application.
 * PRISM remains the official university enrollment system of record.
 */
class Application extends Model
{
    public const STAGES = [
        'eligibility_pending'              => 'Eligibility pending - CAT pass confirmation required',
        'eligible'                         => 'Eligible - submit college requirements',
        'requirements_submitted'           => 'Requirements submitted - awaiting staff review',
        'requirements_in_review'           => 'Requirements in review',
        'requirements_returned'            => 'Requirements returned - resubmission required',
        'requirements_approved'            => 'Requirements approved - schedule CICS evaluation exam',
        'exam_scheduled'                   => 'CICS exam scheduled - awaiting result',
        'exam_passed'                      => 'Exam passed - schedule interview',
        'exam_failed'                      => 'Exam failed',
        'interview_scheduled'              => 'Interview scheduled - awaiting evaluation',
        'accepted_for_clearance'           => 'Accepted - complete payment clearance',
        'not_approved'                     => 'Not approved',
        'eligible_for_official_enrollment' => 'Eligible for official enrollment - PRISM handoff pending',
        'prism_pending'                    => 'PRISM encoding in progress',
        'officially_enrolled'              => 'Officially enrolled in PRISM',
        'closed_rejected'                  => 'Closed - rejected',
        'closed_ineligible'                => 'Closed - ineligible',
        'closed_failed'                    => 'Closed - failed evaluation',
        'closed_withdrawn'                 => 'Closed - withdrawn',
        'closed_expired'                   => 'Closed - expired',
    ];

    public const STAGE_GROUPS = [
        'Eligibility' => ['eligibility_pending', 'eligible'],
        'Requirements' => ['requirements_submitted', 'requirements_in_review', 'requirements_returned', 'requirements_approved'],
        'CICS Exam' => ['exam_scheduled', 'exam_passed', 'exam_failed'],
        'Interview' => ['interview_scheduled', 'accepted_for_clearance', 'not_approved'],
        'Fee Clearance & PRISM' => ['eligible_for_official_enrollment', 'prism_pending', 'officially_enrolled'],
        'Closed' => ['closed_rejected', 'closed_ineligible', 'closed_failed', 'closed_withdrawn', 'closed_expired'],
    ];

    public const RANK = [
        'eligibility_pending' => 1, 'eligible' => 2,
        'requirements_submitted' => 3, 'requirements_in_review' => 3, 'requirements_returned' => 3,
        'requirements_approved' => 4,
        'exam_scheduled' => 5, 'exam_passed' => 6, 'exam_failed' => 6,
        'interview_scheduled' => 7,
        'accepted_for_clearance' => 8, 'not_approved' => 8,
        'eligible_for_official_enrollment' => 9, 'prism_pending' => 9,
        'officially_enrolled' => 10,
        'closed_rejected' => 10, 'closed_ineligible' => 10, 'closed_failed' => 10, 'closed_withdrawn' => 10, 'closed_expired' => 10,
    ];

    public const TERMINAL_STAGES = [
        'exam_failed', 'not_approved', 'officially_enrolled',
        'closed_rejected', 'closed_ineligible', 'closed_failed', 'closed_withdrawn', 'closed_expired',
    ];

    protected $guarded = [];

    protected $casts = [
        'exam_result_released_at' => 'datetime',
        'dispose_at' => 'datetime',
        'approved_at' => 'datetime',
        'fee_payment_date' => 'date',
        'cat_verified_at' => 'datetime',
        'stage_changed_at' => 'datetime',
        'interviewed_at' => 'datetime',
        'eligible_for_official_enrollment_at' => 'datetime',
        'prism_encoded_at' => 'datetime',
        'officially_enrolled_at' => 'datetime',
        'closed_at' => 'datetime',
        'exam_score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function examAppointment(): HasOne
    {
        return $this->hasOne(Appointment::class)->where('type', Appointment::EXAM);
    }

    public function interviewAppointment(): HasOne
    {
        return $this->hasOne(Appointment::class)->where('type', Appointment::INTERVIEW);
    }

    public function feeClearances(): HasMany
    {
        return $this->hasMany(FeeClearance::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ApplicationStatusLog::class)->latest('created_at');
    }

    public function catVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cat_verified_by');
    }

    public function prismEncoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prism_encoded_by');
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? ucfirst(str_replace('_', ' ', $this->stage));
    }

    public function getRankAttribute(): int
    {
        return self::RANK[$this->stage] ?? 1;
    }

    public function appointmentFor(string $type): ?Appointment
    {
        return $type === Appointment::EXAM ? $this->examAppointment : $this->interviewAppointment;
    }

    public function isTerminal(): bool
    {
        return in_array($this->stage, self::TERMINAL_STAGES, true);
    }

    public function hasClearedFee(string $feeType): bool
    {
        $fee = $this->feeClearances->firstWhere('fee_type', $feeType);
        return $fee !== null && $fee->isCleared();
    }

    public function hasAllRequiredFeeClearances(): bool
    {
        foreach ([FeeClearance::DEPARTMENT, FeeClearance::INSURANCE] as $type) {
            if (! $this->hasClearedFee($type)) {
                return false;
            }
        }
        return true;
    }
}
