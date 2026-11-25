<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RESUBMISSION_REQUESTED = 'resubmission_requested';
    public const STATUS_WAIVED = 'waived';

    protected $guarded = [];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'size_bytes' => 'integer',
        'version' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_document_id');
    }

    public function isApprovedOrWaived(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_WAIVED], true);
    }

    public function canBeReplacedByStudent(): bool
    {
        return in_array($this->status, [
            self::STATUS_UPLOADED,
            self::STATUS_REJECTED,
            self::STATUS_RESUBMISSION_REQUESTED,
        ], true);
    }
}
