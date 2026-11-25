<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeClearance extends Model
{
    public const DEPARTMENT = 'department_fee';
    public const INSURANCE = 'insurance_fee';

    public const STATUSES = [
        'unpaid', 'pending_verification', 'verified_paid', 'waived', 'void',
    ];

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'waived_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function proofDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'proof_document_id');
    }

    public function isCleared(): bool
    {
        return in_array($this->status, ['verified_paid', 'waived'], true);
    }
}
