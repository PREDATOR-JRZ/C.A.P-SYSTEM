<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    public const EXAM = 'exam';
    public const INTERVIEW = 'interview';

    protected $guarded = [];
    protected $casts = ['appointment_date' => 'date'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->type === self::EXAM ? 'CICS Exam' : 'Interview & Submission of Requirements';
    }
}
