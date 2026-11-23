<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One admission application per student account. `stage` drives the whole workflow:
 *
 *  registered -> requirements_submitted -> exam_scheduled -> (admin releases results)
 *    -> exam_passed | exam_failed
 *  exam_passed -> interview_scheduled -> approved | not_approved -> batched
 */
class Application extends Model
{
    public const STAGES = [
        'registered'             => 'Registered — upload your requirements',
        'requirements_submitted' => 'Requirements uploaded — schedule your CICS exam',
        'exam_scheduled'         => 'CICS exam scheduled — awaiting result',
        'exam_passed'            => 'Exam passed — schedule your interview',
        'exam_failed'            => 'Exam failed',
        'interview_scheduled'    => 'Interview scheduled — awaiting evaluation',
        'approved'               => 'Approved — awaiting fee batch assignment',
        'not_approved'           => 'Not approved',
        'batched'                => 'Approved — fee payment batch assigned',
    ];

    /** Progress rank used by the dashboard tracker. */
    public const RANK = [
        'registered' => 1, 'requirements_submitted' => 2, 'exam_scheduled' => 3,
        'exam_passed' => 4, 'exam_failed' => 4, 'interview_scheduled' => 5,
        'approved' => 6, 'not_approved' => 6, 'batched' => 7,
    ];

    protected $guarded = [];
    protected $casts = [
        'exam_result_released_at' => 'datetime',
        'dispose_at'              => 'datetime',
        'approved_at'             => 'datetime',
        'fee_payment_date'        => 'date',
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

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? ucfirst($this->stage);
    }

    public function getRankAttribute(): int
    {
        return self::RANK[$this->stage] ?? 1;
    }

    public function appointmentFor(string $type): ?Appointment
    {
        return $type === Appointment::EXAM ? $this->examAppointment : $this->interviewAppointment;
    }
}
